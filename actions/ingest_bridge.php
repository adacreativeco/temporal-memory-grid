<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
use Temporal\AggregationEngine;
use Temporal\Rollups;
use Temporal\Cache;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../aggregation_engine.php';
require_once __DIR__ . '/../derive_rollups.php';
require_once __DIR__ . '/../cache.php';
header('Content-Type: application/json');
ini_set('display_errors','0');
Auth::getInstance()->requireLogin();
$db = Database::getInstance();
$raw = file_get_contents('php://input');
$json = json_decode($raw, true);
$parseStage = 'raw';
if (!$json || !is_array($json)) {
    // Temizlik: baş/son tırnak ve code fence kaldırmayı dene
    $clean = trim($raw);
    if (strlen($clean) > 2 && (($clean[0] === '"' && substr($clean,-1) === '"') || ($clean[0] === '\'' && substr($clean,-1) === '\''))) {
        $clean = substr($clean, 1, -1);
    }
    if (strpos($clean, '```') === 0) {
        $clean = preg_replace('/^```[\s\S]*?\n/', '', $clean);
        $clean = preg_replace('/\n```$/', '', $clean);
        $clean = trim($clean);
    }
    $json = json_decode($clean, true);
    $parseStage = 'clean';
    if (!$json || !is_array($json)) { Utils::errorResponse('Geçersiz gövde', 400); }
}
$events = $json['data'] ?? $json;
if (!is_array($events)) { Utils::errorResponse('Geçersiz veri', 400); }
$processed_events = 0;
$received_count = count($events);
$inserted_events = 0;
$minTs = null; $maxTs = null;
foreach ($events as $ev) {
    $data = json_encode($ev);
    $eventType = $ev['event_type'] ?? ($ev['type'] ?? 'unknown');
    $sourceId = $ev['source_id'] ?? ($ev['source'] ?? 'unknown');
    $eventTime = null;
    if (isset($ev['event_time'])) { $eventTime = $ev['event_time']; }
    elseif (isset($ev['timestamp'])) { $eventTime = gmdate('Y-m-d H:i:s', (int)$ev['timestamp']); }
    elseif (isset($ev['created_at'])) { $eventTime = $ev['created_at']; }
    else { $eventTime = gmdate('Y-m-d H:i:s'); }
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
    $chg = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;
    if ((int)$chg > 0) { $inserted_events++; }
    $processed_events++;
}
if ($minTs === null || $maxTs === null) {
    $dbg = [
        'raw_length' => strlen($raw),
        'parse_stage' => $parseStage,
        'data_length' => $received_count,
        'sample_keys' => $received_count > 0 ? array_keys($events[0]) : [],
    ];
    Utils::successResponse([
        'received_count'=>$received_count,
        'inserted_events'=>$inserted_events,
        'processed_events'=>$processed_events,
        'skipped_duplicates'=>$received_count-$inserted_events,
        'processed_buckets'=>0,
        'debug'=>$dbg
    ],'Veri alındı');
}
$aggStartIso = gmdate('Y-m-d\TH:i:s\Z', $minTs - 1);
$aggEndIso = gmdate('Y-m-d\TH:i:s\Z', $maxTs + 1);
$res = AggregationEngine::getInstance()->aggregateEvents($aggStartIso, $aggEndIso, '1m');
$processed_buckets = (int)($res['buckets_processed'] ?? 0);
try { Rollups::derive($aggStartIso, $aggEndIso, '1m', '5m'); } catch (\Exception $e) {}
Cache::getInstance()->clear();
$dbg = [
    'raw_length' => strlen($raw),
    'parse_stage' => $parseStage,
    'data_length' => $received_count,
    'sample_keys' => $received_count > 0 ? array_keys($events[0]) : [],
    'window_start' => $aggStartIso,
    'window_end' => $aggEndIso
];
Utils::successResponse([
    'received_count'=>$received_count,
    'inserted_events'=>$inserted_events,
    'processed_events'=>$processed_events,
    'skipped_duplicates'=>$received_count-$inserted_events,
    'processed_buckets'=>$processed_buckets,
    'window_start'=>$aggStartIso,
    'window_end'=>$aggEndIso,
    'debug'=>$dbg
],'Veri alındı');
