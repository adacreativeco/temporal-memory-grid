<?php
require_once '../../config.php';
require_once '../../database_pdo.php';
require_once '../../utils.php';
require_once '../../auth.php';
require_once '../../cache.php';

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// API Key validation
$api_key = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
if (!$api_key || !\Temporal\Auth::getInstance()->validateApiKey($api_key)) {
    \Temporal\Utils::errorResponse('Invalid or missing API key', 401);
}

// Rate limiting
$rate_limit_key = 'api_rate_limit_' . $_SERVER['REMOTE_ADDR'];
$cache = \Temporal\Cache::getInstance();
$request_count = $cache->get($rate_limit_key) ?? 0;

if ($request_count >= API_RATE_LIMIT) {
    \Temporal\Utils::errorResponse('Rate limit exceeded. Maximum ' . API_RATE_LIMIT . ' requests per minute.', 429);
}

$cache->set($rate_limit_key, $request_count + 1);

// Get request parameters
$metric_type = $_GET['metric_type'] ?? null;
$type = $_GET['type'] ?? null;
$source_id = $_GET['source_id'] ?? null;
$start_time = $_GET['start_time'] ?? null;
$end_time = $_GET['end_time'] ?? null;
$bucket_size = $_GET['bucket_size'] ?? null;
$deviation_threshold = $_GET['deviation_threshold'] ?? 50; // Default 50% deviation
$baseline = $_GET['baseline'] ?? 'historical';
$ma_window = isset($_GET['ma_window']) ? (int)$_GET['ma_window'] : 6;

// Validate required parameters
if (!$metric_type || !$start_time || !$end_time || !$bucket_size) {
    \Temporal\Utils::errorResponse('Missing required parameters: metric_type, start_time, end_time, bucket_size', 400);
}

try {
    // Validate parameters
    \Temporal\Utils::validateMetricType($metric_type);
    \Temporal\Utils::validateTimeRange($start_time, $end_time);
    \Temporal\Utils::validateBucketSize($bucket_size);
    
    // Check cache
    $cache_key = 'anomalies_' . md5(serialize($_GET));
    $cached_result = $cache->get($cache_key);
    
    if ($cached_result !== false) {
        \Temporal\Utils::successResponse($cached_result);
    }
    
    // Normalize time format to database-friendly 'Y-m-d H:i:s'
    $start_time_db = gmdate('Y-m-d H:i:s', strtotime($start_time));
    $end_time_db = gmdate('Y-m-d H:i:s', strtotime($end_time));

    // Get data from database
    $db = \Temporal\Database::getInstance();
    
    // Get current period data
    $current_data = getCurrentData($db, $metric_type, $start_time_db, $end_time_db, $bucket_size, $type, $source_id);
    
    // Expected values
    $expected = [];
    if ($baseline === 'moving_average') {
        $expected = getMovingAverageExpected($current_data, $ma_window);
    } else {
        $expected = getHistoricalAverage($db, $metric_type, $start_time_db, $end_time_db, $bucket_size, $type, $source_id);
        $allZero = true;
        foreach ($expected as $v) { if ($v > 0) { $allZero = false; break; } }
        if ($allZero) {
            $expected = getMovingAverageExpected($current_data, $ma_window);
        }
    }
    
    $anomalies = detectAnomalies($current_data, $expected, $deviation_threshold);
    
    // Prepare response
    $response = [
        'metric_type' => $metric_type,
        'type' => $type,
        'source_id' => $source_id,
        'bucket_size' => $bucket_size,
        'start_time' => $start_time_db,
        'end_time' => $end_time_db,
        'deviation_threshold' => $deviation_threshold,
        'anomaly_buckets' => $anomalies,
        'baseline' => $baseline,
        'ma_window' => $ma_window
    ];
    
    // Cache result
    $cache->set($cache_key, $response);
    
    \Temporal\Utils::successResponse($response);
    
} catch (Exception $e) {
    \Temporal\Utils::errorResponse($e->getMessage(), 400);
}

function getCurrentData($db, $metric_type, $start_time, $end_time, $bucket_size, $type = null, $source_id = null) {
    $sql = "";
    $params = [];
    
    switch ($metric_type) {
        case 'total_events':
            $sql = "
                SELECT 
                    tb.bucket_start,
                    tb.total_events as count
                FROM time_buckets tb
                WHERE tb.bucket_start >= ? 
                AND tb.bucket_end <= ? 
                AND tb.bucket_size = ?
                ORDER BY tb.bucket_start
            ";
            $params = [$start_time, $end_time, $bucket_size];
            break;
            
        case 'events_by_type':
            if (!$type) {
                return [];
            }
            $sql = "
                SELECT 
                    tb.bucket_start,
                    COALESCE(bm.count, 0) as count
                FROM time_buckets tb
                LEFT JOIN bucket_metrics bm ON tb.id = bm.bucket_id 
                    AND bm.metric_type = 'events_by_type' 
                    AND bm.metric_subtype = ?
                WHERE tb.bucket_start >= ? 
                AND tb.bucket_end <= ? 
                AND tb.bucket_size = ?
                ORDER BY tb.bucket_start
            ";
            $params = [$type, $start_time, $end_time, $bucket_size];
            break;
            
        case 'events_by_source':
            if (!$source_id) {
                return [];
            }
            $sql = "
                SELECT 
                    tb.bucket_start,
                    COALESCE(bm.count, 0) as count
                FROM time_buckets tb
                LEFT JOIN bucket_metrics bm ON tb.id = bm.bucket_id 
                    AND bm.metric_type = 'events_by_source' 
                    AND bm.metric_subtype = ?
                WHERE tb.bucket_start >= ? 
                AND tb.bucket_end <= ? 
                AND tb.bucket_size = ?
                ORDER BY tb.bucket_start
            ";
            $params = [$source_id, $start_time, $end_time, $bucket_size];
            break;
            
        default:
            return [];
    }
    
    $result = $db->query($sql, $params);
    return $result;
}

function getHistoricalAverage($db, $metric_type, $start_time, $end_time, $bucket_size, $type = null, $source_id = null) {
    // Calculate historical period (same length, but 7 days before)
    $start_ts = strtotime($start_time);
    $end_ts = strtotime($end_time);
    $duration = $end_ts - $start_ts;
    
    $hist_start = date('Y-m-d H:i:s', $start_ts - (7 * 24 * 60 * 60));
    $hist_end = date('Y-m-d H:i:s', $end_ts - (7 * 24 * 60 * 60));
    
    // Get historical data
    $historical_data = getCurrentData($db, $metric_type, $hist_start, $hist_end, $bucket_size, $type, $source_id);
    
    // Calculate average per bucket
    $bucket_averages = [];
    $bucket_counts = [];
    
    foreach ($historical_data as $row) {
        $bucket_start = $row['bucket_start'];
        $count = $row['count'];
        
        if (!isset($bucket_averages[$bucket_start])) {
            $bucket_averages[$bucket_start] = 0;
            $bucket_counts[$bucket_start] = 0;
        }
        
        $bucket_averages[$bucket_start] += $count;
        $bucket_counts[$bucket_start]++;
    }
    
    // Calculate actual averages
    $averages = [];
    foreach ($bucket_averages as $bucket_start => $total) {
        $count = $bucket_counts[$bucket_start];
        $averages[$bucket_start] = $count > 0 ? $total / $count : 0;
    }
    
    return $averages;
}

function detectAnomalies($current_data, $historical_data, $deviation_threshold) {
    $anomalies = [];
    
    foreach ($current_data as $row) {
        $bucket_start = $row['bucket_start'];
        $observed_value = $row['count'];
        
        // Get historical average for this bucket
        $expected_value = $historical_data[$bucket_start] ?? 0;
        
        // Skip if no historical data
        if ($expected_value == 0) {
            continue;
        }
        
        // Calculate deviation
        $deviation_percent = abs((($observed_value - $expected_value) / $expected_value) * 100);
        
        // Check if it's an anomaly
        if ($deviation_percent >= $deviation_threshold) {
            $anomalies[] = [
                'bucket_start' => $bucket_start,
                'observed_value' => $observed_value,
                'expected_value' => round($expected_value, 2),
                'deviation_percent' => round($deviation_percent, 2)
            ];
        }
    }
    
    return $anomalies;
}

function getMovingAverageExpected($current_data, $window) {
    $expected = [];
    $counts = [];
    foreach ($current_data as $row) {
        $counts[] = ['k'=>$row['bucket_start'], 'v'=>$row['count']];
    }
    $sum = 0;
    $q = [];
    for ($i = 0; $i < count($counts); $i++) {
        $k = $counts[$i]['k'];
        $v = (int)$counts[$i]['v'];
        if (count($q) >= $window) {
            $sum -= $q[0];
            array_shift($q);
        }
        $expected[$k] = (count($q) > 0) ? ($sum / count($q)) : 0;
        $q[] = $v;
        $sum += $v;
    }
    return $expected;
}
