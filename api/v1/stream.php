<?php
/**
 * Temporal Memory Grid — Real-time Server-Sent Events (SSE) Stream
 * 
 * Endpoint: GET /api/v1/stream.php?api_key=YOUR_API_KEY&bucket_size=1m
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database_pdo.php';
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../aggregation_engine.php';

// Set SSE Headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
header('X-Accel-Buffering: no'); // Nginx buffering off

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Authentication Check: API Key or Admin Session
$apiKey = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
$auth = \Temporal\Auth::getInstance();
$isAuthenticated = false;

if ($apiKey && $auth->validateApiKey($apiKey)) {
    $isAuthenticated = true;
} elseif ($auth->isLoggedIn()) {
    $isAuthenticated = true;
}

if (!$isAuthenticated) {
    echo "event: error\n";
    echo "data: " . json_encode(['error' => 'Yetkisiz erişim. Geçerli bir API Key veya aktif oturum gereklidir.']) . "\n\n";
    flush();
    exit();
}

// Disable output buffering
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

$bucketSize = $_GET['bucket_size'] ?? '1m';
$metricType = $_GET['metric_type'] ?? 'total_events';
$db = \Temporal\Database::getInstance();
$aggEngine = \Temporal\AggregationEngine::getInstance();

$lastDataHash = '';
$maxTicks = 60; // Keep connection active for ~120-180 seconds then client automatically reconnects
$tick = 0;

while ($tick < $maxTicks) {
    if (connection_aborted()) {
        break;
    }

    try {
        // 1. Get Aggregation KPI Status
        $status = $aggEngine->getAggregationStatus();

        // 2. Get latest 15 time buckets for live charting
        $sql = "
            SELECT bucket_start, total_events as count 
            FROM time_buckets 
            WHERE bucket_size = ? 
            ORDER BY bucket_start DESC 
            LIMIT 20
        ";
        $rawBuckets = $db->query($sql, [$bucketSize]);
        $buckets = array_reverse($rawBuckets);

        // 3. Get recent raw event count in last 5 minutes
        $recentCutoff = gmdate('Y-m-d H:i:s', time() - 300);
        $recentCountRow = $db->query("SELECT COUNT(*) as count FROM events WHERE event_time >= ?", [$recentCutoff]);
        $recentEvents = (int)($recentCountRow[0]['count'] ?? 0);

        $payload = [
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'bucket_size' => $bucketSize,
            'metric_type' => $metricType,
            'kpis' => [
                'total_buckets' => (int)($status['total_buckets'] ?? 0),
                'total_events_aggregated' => (int)($status['total_events_aggregated'] ?? 0),
                'latest_bucket_end' => $status['latest_bucket_end'] ?? null,
                'recent_5m_events' => $recentEvents
            ],
            'buckets' => $buckets
        ];

        $currentHash = md5(json_encode($payload));

        // Send update only if data changed or every 10 seconds
        if ($currentHash !== $lastDataHash || ($tick % 5 === 0)) {
            echo "event: update\n";
            echo "data: " . json_encode($payload) . "\n\n";
            $lastDataHash = $currentHash;
        } else {
            // Heartbeat comment to keep connection alive
            echo ": heartbeat " . time() . "\n\n";
        }

        flush();

    } catch (\Exception $e) {
        echo "event: error\n";
        echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        flush();
    }

    $tick++;
    sleep(2);
}
