<?php
require_once 'database_pdo.php';
require_once 'config.php';

function setupDatabase() {
    $db = Database::getInstance();
    
    try {
        if (DB_TYPE === 'mysql') {
            // Create database if not exists
            $connection = $db->getConnection();
            $connection->query("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
            $connection->query("USE " . DB_NAME);
        }
        
        // Create time_buckets table
        $sql = "
        CREATE TABLE IF NOT EXISTS time_buckets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bucket_start DATETIME NOT NULL,
            bucket_end DATETIME NOT NULL,
            bucket_size ENUM('1m', '5m', '15m', '1h', '1d') NOT NULL,
            total_events INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_bucket_time (bucket_start, bucket_end),
            INDEX idx_bucket_size (bucket_size)
        )";
        
        if (DB_TYPE === 'sqlite') {
            $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
            $sql = str_replace('ENUM(', 'TEXT CHECK(bucket_size IN (', $sql);
            $sql = str_replace("'1m', '5m', '15m', '1h', '1d')", "'1m', '5m', '15m', '1h', '1d'))", $sql);
        }
        
        $db->execute($sql);
        
        // Create bucket_metrics table
        $sql = "
        CREATE TABLE IF NOT EXISTS bucket_metrics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            bucket_id INT NOT NULL,
            metric_type VARCHAR(50) NOT NULL,
            metric_subtype VARCHAR(100),
            metric_value VARCHAR(255),
            count INT DEFAULT 0,
            FOREIGN KEY (bucket_id) REFERENCES time_buckets(id) ON DELETE CASCADE,
            INDEX idx_metric_type (metric_type),
            INDEX idx_bucket_metric (bucket_id, metric_type)
        )";
        
        if (DB_TYPE === 'sqlite') {
            $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
            $sql = str_replace('FOREIGN KEY (bucket_id) REFERENCES time_buckets(id) ON DELETE CASCADE', '', $sql);
        }
        
        $db->execute($sql);
        
        // Create system_logs table
        $sql = "
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(50) NOT NULL,
            status ENUM('success', 'failed', 'running') NOT NULL,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_job_type (job_type),
            INDEX idx_created_at (created_at)
        )";
        
        if (DB_TYPE === 'sqlite') {
            $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
            $sql = str_replace("ENUM('success', 'failed', 'running')", "TEXT CHECK(status IN ('success', 'failed', 'running'))", $sql);
        }
        
        $db->execute($sql);
        
        // Create events table (sample data source)
        $sql = "
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(100) NOT NULL,
            source_id VARCHAR(100) NOT NULL,
            event_time DATETIME NOT NULL,
            geo_region VARCHAR(100),
            event_data JSON,
            INDEX idx_event_time (event_time),
            INDEX idx_event_type (event_type),
            INDEX idx_source_id (source_id)
        )";
        
        if (DB_TYPE === 'sqlite') {
            $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
            $sql = str_replace('JSON', 'TEXT', $sql);
        }
        
        $db->execute($sql);
        
        // Insert sample data
        insertSampleData($db);
        
        echo "Database setup completed successfully!\n";
        
    } catch (Exception $e) {
        echo "Error setting up database: " . $e->getMessage() . "\n";
    }
}

function insertSampleData($db) {
    // Check if sample data already exists
    $result = $db->query("SELECT COUNT(*) as count FROM events");
    if ($result[0]['count'] > 0) {
        return;
    }
    
    $event_types = ['sensor_alert', 'user_login', 'system_error', 'data_update', 'connection_lost'];
    $sources = ['sensor_001', 'sensor_002', 'web_app', 'mobile_app', 'api_gateway'];
    $regions = ['region_north', 'region_south', 'region_east', 'region_west'];
    
    $now = time();
    $one_day_ago = $now - (24 * 60 * 60);
    
    for ($i = 0; $i < 1000; $i++) {
        $event_time = date('Y-m-d H:i:s', rand($one_day_ago, $now));
        $event_type = $event_types[array_rand($event_types)];
        $source_id = $sources[array_rand($sources)];
        $geo_region = $regions[array_rand($regions)];
        
        $sql = "INSERT INTO events (event_type, source_id, event_time, geo_region, event_data) VALUES (?, ?, ?, ?, ?)";
        $event_data = json_encode([
            'severity' => rand(1, 5),
            'message' => 'Sample event ' . $i,
            'metadata' => ['index' => $i]
        ]);
        
        $db->execute($sql, [$event_type, $source_id, $event_time, $geo_region, $event_data]);
    }
    
    echo "Sample data inserted successfully!\n";
}

// Run setup
setupDatabase();