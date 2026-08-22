<?php
namespace Temporal;

class Utils {
    
    public static function validateTimeRange($start_time, $end_time) {
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        
        if ($start === false || $end === false) {
            throw new \Exception("Invalid time format. Use ISO 8601 format.");
        }
        
        if ($start >= $end) {
            throw new \Exception("Start time must be before end time.");
        }
        
        $max_range = 30 * 24 * 60 * 60; // 30 days in seconds
        if ($end - $start > $max_range) {
            throw new \Exception("Time range cannot exceed 30 days.");
        }
        
        return true;
    }
    
    public static function validateBucketSize($bucket_size) {
        $valid_sizes = ['1m', '5m', '15m', '1h', '1d'];
        
        if (!in_array($bucket_size, $valid_sizes)) {
            throw new \Exception("Invalid bucket size. Valid sizes: 1m, 5m, 15m, 1h, 1d");
        }
        
        return true;
    }
    
    public static function validateMetricType($metric_type) {
        $valid_types = ['total_events', 'events_by_type', 'events_by_source'];
        
        if (!in_array($metric_type, $valid_types)) {
            throw new \Exception("Invalid metric type. Valid types: total_events, events_by_type, events_by_source");
        }
        
        return true;
    }
    
    public static function getBucketInterval($bucket_size) {
        $intervals = [
            '1m' => 60,
            '5m' => 300,
            '15m' => 900,
            '1h' => 3600,
            '1d' => 86400
        ];
        
        return $intervals[$bucket_size] ?? 300; // Default to 5 minutes
    }
    
    public static function generateTimeBuckets($start_time, $end_time, $bucket_size) {
        $buckets = [];
        $interval = self::getBucketInterval($bucket_size);
        
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        
        // Align to bucket boundaries
        $current_bucket = floor($start / $interval) * $interval;
        
        while ($current_bucket < $end) {
            $bucket_start = date('Y-m-d H:i:s', $current_bucket);
            $bucket_end = date('Y-m-d H:i:s', $current_bucket + $interval);
            
            $buckets[] = [
                'bucket_start' => $bucket_start,
                'bucket_end' => $bucket_end,
                'bucket_start_ts' => $current_bucket,
                'bucket_end_ts' => $current_bucket + $interval
            ];
            
            $current_bucket += $interval;
        }
        
        return $buckets;
    }
    
    public static function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
    
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit();
    }
    
    public static function errorResponse($message, $status = 400) {
        self::jsonResponse(['error' => $message], $status);
    }
    
    public static function successResponse($data, $message = 'Success') {
        self::jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
}
