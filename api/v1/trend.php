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
$primary_start_time = $_GET['primary_start_time'] ?? null;
$primary_end_time = $_GET['primary_end_time'] ?? null;
$compare_start_time = $_GET['compare_start_time'] ?? null;
$compare_end_time = $_GET['compare_end_time'] ?? null;

// Validate required parameters
if (!$metric_type || !$primary_start_time || !$primary_end_time || !$compare_start_time || !$compare_end_time) {
    \Temporal\Utils::errorResponse('Missing required parameters: metric_type, primary_start_time, primary_end_time, compare_start_time, compare_end_time', 400);
}

try {
    // Validate parameters
    \Temporal\Utils::validateMetricType($metric_type);
    \Temporal\Utils::validateTimeRange($primary_start_time, $primary_end_time);
    \Temporal\Utils::validateTimeRange($compare_start_time, $compare_end_time);
    
    // Check cache
    $cache_key = 'trend_' . md5(serialize($_GET));
    $cached_result = $cache->get($cache_key);
    
    if ($cached_result !== false) {
        \Temporal\Utils::successResponse($cached_result);
    }
    
    // Get data from database
    $db = \Temporal\Database::getInstance();
    
    // Normalize time format to database-friendly 'Y-m-d H:i:s'
    $primary_start_db = gmdate('Y-m-d H:i:s', strtotime($primary_start_time));
    $primary_end_db = gmdate('Y-m-d H:i:s', strtotime($primary_end_time));
    $compare_start_db = gmdate('Y-m-d H:i:s', strtotime($compare_start_time));
    $compare_end_db = gmdate('Y-m-d H:i:s', strtotime($compare_end_time));

    // Build queries for both periods
    $primary_count = getMetricCount($db, $metric_type, $primary_start_db, $primary_end_db, $type, $source_id);
    $compare_count = getMetricCount($db, $metric_type, $compare_start_db, $compare_end_db, $type, $source_id);
    
    // Calculate differences
    $difference_absolute = $primary_count - $compare_count;
    $difference_percent = $compare_count > 0 ? round(($difference_absolute / $compare_count) * 100, 2) : 0;
    
    // Prepare response
    $response = [
        'metric_type' => $metric_type,
        'type' => $type,
        'source_id' => $source_id,
        'primary_start_time' => $primary_start_db,
        'primary_end_time' => $primary_end_db,
        'compare_start_time' => $compare_start_db,
        'compare_end_time' => $compare_end_db,
        'primary_count' => $primary_count,
        'compare_count' => $compare_count,
        'difference_absolute' => $difference_absolute,
        'difference_percent' => $difference_percent
    ];
    
    // Cache result
    $cache->set($cache_key, $response);
    
    \Temporal\Utils::successResponse($response);
    
} catch (Exception $e) {
    \Temporal\Utils::errorResponse($e->getMessage(), 400);
}

function getMetricCount($db, $metric_type, $start_time, $end_time, $type = null, $source_id = null) {
    $sql = "";
    $params = [];
    
    switch ($metric_type) {
        case 'total_events':
            $sql = "
                SELECT COALESCE(SUM(total_events), 0) as count
                FROM time_buckets
                WHERE bucket_start >= ? AND bucket_end <= ?
            ";
            $params = [$start_time, $end_time];
            break;
            
        case 'events_by_type':
            if (!$type) {
                return 0;
            }
            $sql = "
                SELECT COALESCE(SUM(bm.count), 0) as count
                FROM time_buckets tb
                JOIN bucket_metrics bm ON tb.id = bm.bucket_id
                WHERE tb.bucket_start >= ? AND tb.bucket_end <= ?
                AND bm.metric_type = 'events_by_type' AND bm.metric_subtype = ?
            ";
            $params = [$start_time, $end_time, $type];
            break;
            
        case 'events_by_source':
            if (!$source_id) {
                return 0;
            }
            $sql = "
                SELECT COALESCE(SUM(bm.count), 0) as count
                FROM time_buckets tb
                JOIN bucket_metrics bm ON tb.id = bm.bucket_id
                WHERE tb.bucket_start >= ? AND tb.bucket_end <= ?
                AND bm.metric_type = 'events_by_source' AND bm.metric_subtype = ?
            ";
            $params = [$start_time, $end_time, $source_id];
            break;
            
        default:
            return 0;
    }
    
    $result = $db->query($sql, $params);
    return (int)($result[0]['count'] ?? 0);
}
