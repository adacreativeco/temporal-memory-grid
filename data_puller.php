<?php
namespace Temporal;
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_pdo.php';
require_once __DIR__ . '/system_logs.php';
require_once __DIR__ . '/aggregation_engine.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/derive_rollups.php';

class DataPuller {
    private static function getExternalApiConfig() {
        // Prefer DB settings; fall back to config constants
        try {
            $db = Database::getInstance();
            $row = $db->query("SELECT external_api_url, external_api_token, external_api_header_name, external_api_insecure FROM settings ORDER BY id DESC LIMIT 1");
            $cfg = $row[0] ?? [];
            $url = ($cfg['external_api_url'] ?? null) ?: \EVENT_GRID_API_URL;
            $token = ($cfg['external_api_token'] ?? null) ?: \EVENT_PULLER_TOKEN;
            $header = ($cfg['external_api_header_name'] ?? null) ?: 'X-API-Key';
            $insecure = (int)($cfg['external_api_insecure'] ?? 0) === 1;
            // Sanitize URL/header/token
            $url = is_string($url) ? trim($url) : '';
            $url = preg_replace('/^[`"\']+|[`"\']+$/', '', $url);
            if (preg_match('/https?:\/\/[^\s`"\'<>\)\,]+/i', $url, $m)) { $url = $m[0]; }
            $header = is_string($header) ? trim($header) : 'X-API-Key';
            $token = is_string($token) ? trim($token) : '';
            return [ 'url' => $url, 'token' => $token, 'header' => $header, 'insecure' => $insecure ];
        } catch (\Exception $e) {
            return [ 'url' => \EVENT_GRID_API_URL, 'token' => \EVENT_PULLER_TOKEN, 'header' => 'X-API-Key', 'insecure' => false ];
        }
    }
    public static function run($start_time, $end_time, $options = []) {
        $db = Database::getInstance();
        $logger = SystemLogs::getInstance();
        $processed_events = 0;
        $processed_buckets = 0;
        $logger->log('data_puller', 'running', "pull {$start_time} - {$end_time}");
        try {
            $events = self::fetchEvents($start_time, $end_time, $options);
            $minTs = null; $maxTs = null;
            foreach ($events as $ev) {
                $data = json_encode($ev);
                $eventType = $ev['event_type'] ?? ($ev['type'] ?? 'unknown');
                $sourceId = $ev['source_id'] ?? ($ev['source'] ?? 'unknown');
                $eventTime = null;
                if (isset($ev['event_time'])) {
                    $eventTime = $ev['event_time'];
                } elseif (isset($ev['timestamp'])) {
                    $eventTime = gmdate('Y-m-d H:i:s', (int)$ev['timestamp']);
                } elseif (isset($ev['created_at'])) {
                    $eventTime = $ev['created_at'];
                } else {
                    $eventTime = gmdate('Y-m-d H:i:s');
                }
                $ts = strtotime($eventTime);
                if ($ts !== false) {
                    if ($minTs === null || $ts < $minTs) $minTs = $ts;
                    if ($maxTs === null || $ts > $maxTs) $maxTs = $ts;
                }
                $geo = null;
                if (isset($ev['geo_region'])) { $geo = $ev['geo_region']; }
                elseif (isset($ev['lat']) && isset($ev['lon'])) {
                    $latCell = floor($ev['lat'] * 100) / 100;
                    $lonCell = floor($ev['lon'] * 100) / 100;
                    $geo = 'cell_' . $latCell . '_' . $lonCell;
                }
                $externalId = $ev['external_event_id'] ?? ($ev['event_id'] ?? null);
                if ($externalId) {
                    $sql = "INSERT OR IGNORE INTO events (event_type, source_id, event_time, geo_region, event_data, external_event_id) VALUES (?, ?, ?, ?, ?, ?)";
                    $db->execute($sql, [$eventType, $sourceId, $eventTime, $geo, $data, $externalId]);
                } else {
                    $sql = "INSERT INTO events (event_type, source_id, event_time, geo_region, event_data) VALUES (?, ?, ?, ?, ?)";
                    $db->execute($sql, [$eventType, $sourceId, $eventTime, $geo, $data]);
                }
                $processed_events++;
            }
            // Determine aggregation window (ISO 8601)
            $aggStartIso = null;
            $aggEndIso = null;
            if ($minTs !== null && $maxTs !== null) {
                $aggStartIso = gmdate('Y-m-d\TH:i:s\Z', $minTs - 1); // slight padding
                $aggEndIso = gmdate('Y-m-d\TH:i:s\Z', $maxTs + 1);
            }
            if ($aggStartIso === null || $aggEndIso === null) {
                // No events fetched; skip aggregation
                self::logJob($start_time, $end_time, '1m', 'success', $processed_events, 0, 'pull-no-events');
                return ['processed_events' => $processed_events, 'processed_buckets' => 0];
            }
            $res = AggregationEngine::getInstance()->aggregateEvents($aggStartIso, $aggEndIso, '1m');
            $processed_buckets = (int)($res['buckets_processed'] ?? 0);
            // Auto-derive 5m rollups for dashboard default view
            try {
                $aggStartDb = gmdate('Y-m-d H:i:s', strtotime($aggStartIso));
                $aggEndDb = gmdate('Y-m-d H:i:s', strtotime($aggEndIso));
                \Temporal\Rollups::derive($aggStartDb, $aggEndDb, '1m', '5m');
            } catch (\Exception $e) {}
            self::logJob($aggStartIso, $aggEndIso, '1m', 'success', $processed_events, $processed_buckets, 'pull-and-aggregate');
            return ['processed_events' => $processed_events, 'processed_buckets' => $processed_buckets];
        } catch (\Exception $e) {
            self::logJob($start_time, $end_time, '1m', 'failed', $processed_events, $processed_buckets, $e->getMessage());
            throw $e;
        }
    }

    private static function fetchEvents($start_time, $end_time, $options = []) {
        $ext = self::getExternalApiConfig();
        $url = $ext['url'];
        if (!$url) {
            return [];
        }
        $limit = isset($options['limit']) ? min((int)$options['limit'], 100) : 100;
        $qsParams = [ 'limit' => $limit ];
        if (!empty($start_time)) { $qsParams['start_time'] = $start_time; }
        if (!empty($end_time)) { $qsParams['end_time'] = $end_time; }
        if (!empty($options['type'])) { $qsParams['type'] = $options['type']; }
        if (!empty($options['source_id'])) { $qsParams['source_id'] = $options['source_id']; }
        if (!empty($options['offset'])) { $qsParams['offset'] = (int)$options['offset']; }
        if (!empty($options['after_id'])) { $qsParams['after_id'] = $options['after_id']; }
        $qs = http_build_query($qsParams);
        $req = $url . (strpos($url, '?') === false ? '?' : '&') . $qs;
        $headers = [];
        if (!empty($ext['token'])) {
            $headers[] = ($ext['header'] ?: 'X-API-Key') . ': ' . $ext['token'];
        }
        $resp = null; $netError = null; $parsed = parse_url($url);
        if (function_exists('curl_init')) {
            $ch = curl_init($req);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $scheme = $parsed['scheme'] ?? '';
            if ($scheme === 'https' && !empty($ext['insecure'])) {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            }
            $resp = curl_exec($ch);
            if ($resp === false) { $netError = curl_error($ch); }
            unset($ch);
        } else {
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => 8
                ]
            ];
            $scheme = $parsed['scheme'] ?? '';
            if ($scheme === 'https' && !empty($ext['insecure'])) {
                $opts['ssl'] = [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ];
            }
            $ctx = stream_context_create($opts);
            $resp = @file_get_contents($req, false, $ctx);
            if ($resp === false) {
                $last = error_get_last();
                $netError = $last['message'] ?? 'unknown error';
            }
        }
        if ($resp === false || $resp === null) {
            throw new \Exception('External API erişilemedi: ' . ($netError ?: 'bilinmeyen hata'));
        }
        $json = json_decode($resp, true);
        if (isset($json['data'])) { $json = $json['data']; }
        return is_array($json) ? $json : [];
    }

    private static function logJob($start_time, $end_time, $bucket_size, $status, $processed_events, $processed_buckets, $message) {
        $db = Database::getInstance();
        $sql = "INSERT INTO aggregation_jobs_log (job_type, status, start_time, end_time, bucket_size, processed_events, processed_buckets, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $db->execute($sql, ['data_puller', $status, $start_time, $end_time, $bucket_size, $processed_events, $processed_buckets, $message]);
    }
}

if (php_sapi_name() === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    if ($argc < 3) {
        echo "Usage: php data_puller.php <start_iso> <end_iso> [type] [source_id] [limit] [offset] [after_id]\n";
        exit(1);
    }
    $opts = [];
    if (isset($argv[3])) $opts['type'] = $argv[3];
    if (isset($argv[4])) $opts['source_id'] = $argv[4];
    if (isset($argv[5])) $opts['limit'] = $argv[5];
    if (isset($argv[6])) $opts['offset'] = $argv[6];
    if (isset($argv[7])) $opts['after_id'] = $argv[7];
    $res = DataPuller::run($argv[1], $argv[2], $opts);
    echo json_encode($res, JSON_PRETTY_PRINT) . "\n";
}

