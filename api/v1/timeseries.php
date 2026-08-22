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
$geo_region = $_GET['geo_region'] ?? null;
$format = $_GET['format'] ?? 'json'; // json or csv

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
    $cache_key = 'timeseries_' . md5(serialize($_GET));
    $cached_result = $cache->get($cache_key);
    
    if ($cached_result !== false) {
        if ($format === 'csv') {
            outputCsv($cached_result);
        } else {
            \Temporal\Utils::successResponse($cached_result);
        }
    }
    
    // Get data from database
    $db = \Temporal\Database::getInstance();
    
    // Normalize time format to database-friendly 'Y-m-d H:i:s'
    $start_time_db = gmdate('Y-m-d H:i:s', strtotime($start_time));
    $end_time_db = gmdate('Y-m-d H:i:s', strtotime($end_time));

    // Build query based on metric type
    $buckets = [];
    
    switch ($metric_type) {
        case 'total_events':
            $buckets = getTotalEvents($db, $start_time_db, $end_time_db, $bucket_size);
            break;
            
        case 'events_by_type':
            if (!$type) {
                \Temporal\Utils::errorResponse('Event type is required for events_by_type metric', 400);
            }
            $buckets = getEventsByType($db, $start_time_db, $end_time_db, $bucket_size, $type);
            break;
            
        case 'events_by_source':
            if (!$source_id) {
                \Temporal\Utils::errorResponse('Source ID is required for events_by_source metric', 400);
            }
            $buckets = getEventsBySource($db, $start_time_db, $end_time_db, $bucket_size, $source_id);
            break;
            
        default:
            \Temporal\Utils::errorResponse('Invalid metric type', 400);
    }
    
    // Prepare response
    $response = [
        'metric_type' => $metric_type,
        'type' => $type,
        'source_id' => $source_id,
        'bucket_size' => $bucket_size,
        'start_time' => $start_time_db,
        'end_time' => $end_time_db,
        'buckets' => $buckets
    ];
    
    // Cache result
    $cache->set($cache_key, $response);
    
    // Output based on format
    if ($format === 'csv') {
        outputCsv($response);
    } else {
        \Temporal\Utils::successResponse($response);
    }
    
} catch (Exception $e) {
    \Temporal\Utils::errorResponse($e->getMessage(), 400);
}

function getTotalEvents($db, $start_time, $end_time, $bucket_size) {
    $sql = "
        SELECT 
            tb.bucket_start,
            tb.total_events as count
        FROM time_buckets tb
        WHERE tb.bucket_size = ?
        AND tb.bucket_end > ? 
        AND tb.bucket_start < ? 
        ORDER BY tb.bucket_start
    ";
    
    $result = $db->query($sql, [$bucket_size, $start_time, $end_time]);
    
    if (empty($result)) {
        // If no aggregated data exists, generate empty buckets
        return generateEmptyBuckets($start_time, $end_time, $bucket_size);
    }
    
    return array_map(function($row) {
        return [
            'bucket_start' => $row['bucket_start'],
            'count' => (int)$row['count']
        ];
    }, $result);
}

function getEventsByType($db, $start_time, $end_time, $bucket_size, $type) {
    $sql = "
        SELECT 
            tb.bucket_start,
            COALESCE(bm.count, 0) as count
        FROM time_buckets tb
        LEFT JOIN bucket_metrics bm ON tb.id = bm.bucket_id 
            AND bm.metric_type = 'events_by_type' 
            AND bm.metric_subtype = ?
        WHERE tb.bucket_size = ?
        AND tb.bucket_end > ? 
        AND tb.bucket_start < ? 
        ORDER BY tb.bucket_start
    ";
    
    $result = $db->query($sql, [$type, $bucket_size, $start_time, $end_time]);
    
    if (empty($result)) {
        return generateEmptyBuckets($start_time, $end_time, $bucket_size);
    }
    
    return array_map(function($row) {
        return [
            'bucket_start' => $row['bucket_start'],
            'count' => (int)$row['count']
        ];
    }, $result);
}

function getEventsBySource($db, $start_time, $end_time, $bucket_size, $source_id) {
    $sql = "
        SELECT 
            tb.bucket_start,
            COALESCE(bm.count, 0) as count
        FROM time_buckets tb
        LEFT JOIN bucket_metrics bm ON tb.id = bm.bucket_id 
            AND bm.metric_type = 'events_by_source' 
            AND bm.metric_subtype = ?
        WHERE tb.bucket_size = ?
        AND tb.bucket_end > ? 
        AND tb.bucket_start < ? 
        ORDER BY tb.bucket_start
    ";
    
    $result = $db->query($sql, [$source_id, $bucket_size, $start_time, $end_time]);
    
    if (empty($result)) {
        return generateEmptyBuckets($start_time, $end_time, $bucket_size);
    }
    
    return array_map(function($row) {
        return [
            'bucket_start' => $row['bucket_start'],
            'count' => (int)$row['count']
        ];
    }, $result);
}

function generateEmptyBuckets($start_time, $end_time, $bucket_size) {
    $buckets = \Temporal\Utils::generateTimeBuckets($start_time, $end_time, $bucket_size);
    
    return array_map(function($bucket) {
        return [
            'bucket_start' => $bucket['bucket_start'],
            'count' => 0
        ];
    }, $buckets);
}

function outputCsv($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="timeseries_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write metadata
    fputcsv($output, ['Metric Type', $data['metric_type']]);
    fputcsv($output, ['Start Time', $data['start_time']]);
    fputcsv($output, ['End Time', $data['end_time']]);
    fputcsv($output, ['Bucket Size', $data['bucket_size']]);
    if ($data['type']) {
        fputcsv($output, ['Event Type', $data['type']]);
    }
    if ($data['source_id']) {
        fputcsv($output, ['Source ID', $data['source_id']]);
    }
    fputcsv($output, []); // Empty row
    
    // Write headers
    fputcsv($output, ['Bucket Start', 'Count']);
    
    // Write data
    foreach ($data['buckets'] as $bucket) {
        fputcsv($output, [$bucket['bucket_start'], $bucket['count']]);
    }
    
    fclose($output);
    exit();
}
