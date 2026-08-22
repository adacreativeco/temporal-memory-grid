<?php
namespace Temporal;
use Temporal\SystemLogs;
use Temporal\Database;
use Temporal\Utils;
require_once __DIR__ . '/database_pdo.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/system_logs.php';

class AggregationEngine {
    private static $instance = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function aggregateEvents($start_time, $end_time, $bucket_size) {
        try {
            $db = Database::getInstance();
            $logger = SystemLogs::getInstance();
            
            // Validate inputs
            Utils::validateTimeRange($start_time, $end_time);
            Utils::validateBucketSize($bucket_size);
            
            // Log start of aggregation
            $logger->log('aggregation', 'running', "Starting aggregation for {$start_time} to {$end_time} with bucket size {$bucket_size}");
            
            // Generate time buckets
            $buckets = Utils::generateTimeBuckets($start_time, $end_time, $bucket_size);
            $total_buckets = count($buckets);
            $processed_buckets = 0;
            
            foreach ($buckets as $bucket) {
                $this->processBucket($bucket, $bucket_size);
                $processed_buckets++;
                
                // Log progress every 10 buckets
                if ($processed_buckets % 10 === 0) {
                    $logger->log('aggregation', 'running', "Processed {$processed_buckets}/{$total_buckets} buckets");
                }
            }
            
            // Log completion
            $logger->log('aggregation', 'success', "Completed aggregation for {$start_time} to {$end_time}. Processed {$processed_buckets} buckets.");
            
            return [
                'success' => true,
                'buckets_processed' => $processed_buckets,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'bucket_size' => $bucket_size
            ];
            
        } catch (\Exception $e) {
            $logger->log('aggregation', 'failed', "Aggregation failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function processBucket($bucket, $bucket_size) {
        $db = Database::getInstance();
        
        // Check if bucket already exists
        $existing_bucket = $db->query(
            "SELECT id FROM time_buckets WHERE bucket_start = ? AND bucket_end = ? AND bucket_size = ?",
            [$bucket['bucket_start'], $bucket['bucket_end'], $bucket_size]
        );
        
        if (!empty($existing_bucket)) {
            // Update existing bucket
            $bucket_id = $existing_bucket[0]['id'];
            $this->updateBucket($bucket_id, $bucket);
        } else {
            // Create new bucket
            $this->createBucket($bucket, $bucket_size);
        }
    }
    
    private function createBucket($bucket, $bucket_size) {
        $db = Database::getInstance();
        
        // Count total events in bucket
        $total_events = $this->countEventsInRange($bucket['bucket_start'], $bucket['bucket_end']);
        
        // Create time bucket
        $sql = "INSERT INTO time_buckets (bucket_start, bucket_end, bucket_size, total_events) VALUES (?, ?, ?, ?)";
        $db->execute($sql, [$bucket['bucket_start'], $bucket['bucket_end'], $bucket_size, $total_events]);
        
        $bucket_id = $db->lastInsertId();
        
        // Create metrics for this bucket
        $this->createBucketMetrics($bucket_id, $bucket['bucket_start'], $bucket['bucket_end']);
    }
    
    private function updateBucket($bucket_id, $bucket) {
        $db = Database::getInstance();
        
        // Count total events in bucket
        $total_events = $this->countEventsInRange($bucket['bucket_start'], $bucket['bucket_end']);
        
        // Update time bucket
        $sql = "UPDATE time_buckets SET total_events = ? WHERE id = ?";
        $db->execute($sql, [$total_events, $bucket_id]);
        
        // Delete existing metrics
        $db->execute("DELETE FROM bucket_metrics WHERE bucket_id = ?", [$bucket_id]);
        
        // Recreate metrics
        $this->createBucketMetrics($bucket_id, $bucket['bucket_start'], $bucket['bucket_end']);
    }
    
    private function createBucketMetrics($bucket_id, $start_time, $end_time) {
        $db = Database::getInstance();
        
        // Total events metric (already stored in time_buckets, but also in metrics for consistency)
        $total_count = $this->countEventsInRange($start_time, $end_time);
        $sql = "INSERT INTO bucket_metrics (bucket_id, metric_type, count) VALUES (?, 'total_events', ?)";
        $db->execute($sql, [$bucket_id, $total_count]);
        
        // Events by type
        $events_by_type = $this->countEventsByType($start_time, $end_time);
        foreach ($events_by_type as $type => $count) {
            $sql = "INSERT INTO bucket_metrics (bucket_id, metric_type, metric_subtype, count) VALUES (?, 'events_by_type', ?, ?)";
            $db->execute($sql, [$bucket_id, $type, $count]);
        }
        
        // Events by source
        $events_by_source = $this->countEventsBySource($start_time, $end_time);
        foreach ($events_by_source as $source => $count) {
            $sql = "INSERT INTO bucket_metrics (bucket_id, metric_type, metric_subtype, count) VALUES (?, 'events_by_source', ?, ?)";
            $db->execute($sql, [$bucket_id, $source, $count]);
        }
        
        // Events by geo region (if applicable)
        $events_by_region = $this->countEventsByRegion($start_time, $end_time);
        foreach ($events_by_region as $region => $count) {
            $sql = "INSERT INTO bucket_metrics (bucket_id, metric_type, metric_subtype, count) VALUES (?, 'events_by_geo_region', ?, ?)";
            $db->execute($sql, [$bucket_id, $region, $count]);
        }
    }
    
    private function countEventsInRange($start_time, $end_time) {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT COUNT(*) as count FROM events WHERE event_time >= ? AND event_time < ?",
            [$start_time, $end_time]
        );
        return $result[0]['count'] ?? 0;
    }
    
    private function countEventsByType($start_time, $end_time) {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT event_type, COUNT(*) as count FROM events WHERE event_time >= ? AND event_time < ? GROUP BY event_type",
            [$start_time, $end_time]
        );
        
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['event_type']] = $row['count'];
        }
        
        return $counts;
    }
    
    private function countEventsBySource($start_time, $end_time) {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT source_id, COUNT(*) as count FROM events WHERE event_time >= ? AND event_time < ? GROUP BY source_id",
            [$start_time, $end_time]
        );
        
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['source_id']] = $row['count'];
        }
        
        return $counts;
    }
    
    private function countEventsByRegion($start_time, $end_time) {
        $db = Database::getInstance();
        $result = $db->query(
            "SELECT geo_region, COUNT(*) as count FROM events WHERE event_time >= ? AND event_time < ? AND geo_region IS NOT NULL GROUP BY geo_region",
            [$start_time, $end_time]
        );
        
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['geo_region']] = $row['count'];
        }
        
        return $counts;
    }
    
    public function getAggregationStatus() {
        $db = Database::getInstance();
        
        // Get latest aggregation info
        $latest_bucket = $db->query(
            "SELECT MAX(bucket_end) as latest_end FROM time_buckets"
        );
        
        $total_buckets = $db->query(
            "SELECT COUNT(*) as count FROM time_buckets"
        );
        
        $total_events = $db->query(
            "SELECT SUM(total_events) as total FROM time_buckets"
        );
        
        return [
            'latest_bucket_end' => $latest_bucket[0]['latest_end'] ?? null,
            'total_buckets' => $total_buckets[0]['count'] ?? 0,
            'total_events_aggregated' => $total_events[0]['total'] ?? 0
        ];
    }
}
