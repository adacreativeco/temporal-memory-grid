<?php
require_once 'database_pdo.php';
require_once 'config.php';

function setupDatabaseSqlite() {
$db = \Temporal\Database::getInstance();
    
    try {
        // Create time_buckets table (SQLite compatible)
        $sql = "
        CREATE TABLE IF NOT EXISTS time_buckets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bucket_start DATETIME NOT NULL,
            bucket_end DATETIME NOT NULL,
            bucket_size TEXT CHECK(bucket_size IN ('1m', '5m', '15m', '1h', '1d')) NOT NULL,
            total_events INTEGER DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->execute($sql);
        
        // Create bucket_metrics table (SQLite compatible)
        $sql = "
        CREATE TABLE IF NOT EXISTS bucket_metrics (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bucket_id INTEGER NOT NULL,
            metric_type VARCHAR(50) NOT NULL,
            metric_subtype VARCHAR(100),
            metric_value VARCHAR(255),
            count INTEGER DEFAULT 0,
            FOREIGN KEY (bucket_id) REFERENCES time_buckets(id) ON DELETE CASCADE
        )";
        
        $db->execute($sql);
        
        // Create system_logs table (SQLite compatible)
        $sql = "
        CREATE TABLE IF NOT EXISTS system_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_type VARCHAR(50) NOT NULL,
            status TEXT CHECK(status IN ('success', 'failed', 'running')) NOT NULL,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        $db->execute($sql);
        
        // Create events table (SQLite compatible)
        $sql = "
        CREATE TABLE IF NOT EXISTS events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            event_type VARCHAR(100) NOT NULL,
            source_id VARCHAR(100) NOT NULL,
            event_time DATETIME NOT NULL,
            geo_region VARCHAR(100),
            event_data TEXT
        )";
        
        $db->execute($sql);

        // Ensure external_event_id column exists for deduplication
        try {
            $db->execute("ALTER TABLE events ADD COLUMN external_event_id VARCHAR(255)");
        } catch (Exception $e) {
            // ignore if column already exists
        }

        // Create settings table for retention configuration
        $sql = "
        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            raw_retention_days INTEGER NOT NULL DEFAULT 60,
            agg_retention_days INTEGER NOT NULL DEFAULT 365,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->execute($sql);
        
        // Create indexes for better performance
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_bucket_time ON time_buckets(bucket_start, bucket_end)",
            "CREATE INDEX IF NOT EXISTS idx_bucket_size ON time_buckets(bucket_size)",
            "CREATE INDEX IF NOT EXISTS idx_metric_type ON bucket_metrics(metric_type)",
            "CREATE INDEX IF NOT EXISTS idx_bucket_metric ON bucket_metrics(bucket_id, metric_type)",
            "CREATE INDEX IF NOT EXISTS idx_job_type ON system_logs(job_type)",
            "CREATE INDEX IF NOT EXISTS idx_created_at ON system_logs(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_event_time ON events(event_time)",
            "CREATE INDEX IF NOT EXISTS idx_event_type ON events(event_type)",
            "CREATE INDEX IF NOT EXISTS idx_source_id ON events(source_id)",
            "CREATE UNIQUE INDEX IF NOT EXISTS idx_events_external_id ON events(external_event_id)"
        ];
        
        foreach ($indexes as $index_sql) {
            $db->execute($index_sql);
        }

        // Aggregation jobs log table
        $sql = "
        CREATE TABLE IF NOT EXISTS aggregation_jobs_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            job_type VARCHAR(50) NOT NULL,
            status TEXT CHECK(status IN ('success','failed','running')) NOT NULL,
            start_time DATETIME,
            end_time DATETIME,
            bucket_size TEXT,
            processed_events INTEGER DEFAULT 0,
            processed_buckets INTEGER DEFAULT 0,
            message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->execute($sql);

        // Create users table
        $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login_at TIMESTAMP NULL
        )";
        $db->execute($sql);

        // Create api_keys table
        $sql = "
        CREATE TABLE IF NOT EXISTS api_keys (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            key_value VARCHAR(255) UNIQUE NOT NULL,
            rate_limit INTEGER DEFAULT 100,
            is_active INTEGER DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_used_at TIMESTAMP NULL
        )";
        $db->execute($sql);

        // Create alert_rules table
        $sql = "
        CREATE TABLE IF NOT EXISTS alert_rules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(150) NOT NULL,
            rule_type VARCHAR(50) NOT NULL, -- 'volume_threshold', 'anomaly_spike'
            metric_type VARCHAR(50) DEFAULT 'total_events',
            threshold_value REAL NOT NULL,
            bucket_size VARCHAR(10) DEFAULT '1m',
            webhook_url TEXT NOT NULL,
            webhook_format VARCHAR(50) DEFAULT 'generic_json', -- 'generic_json', 'slack', 'discord'
            cooldown_minutes INTEGER DEFAULT 5,
            is_active INTEGER DEFAULT 1,
            last_triggered_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->execute($sql);

        // Create alert_history table
        $sql = "
        CREATE TABLE IF NOT EXISTS alert_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            rule_id INTEGER,
            rule_name VARCHAR(150),
            trigger_reason TEXT,
            observed_value REAL,
            threshold_value REAL,
            webhook_status VARCHAR(50),
            response_code INTEGER,
            payload_json TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->execute($sql);
        
        // Ensure a single settings row exists
        $existing = $db->query("SELECT COUNT(*) as count FROM settings");
        if (($existing[0]['count'] ?? 0) == 0) {
            $db->execute("INSERT INTO settings (raw_retention_days, agg_retention_days) VALUES (?, ?)", [60, 365]);
        }

        // Extend settings table for external API & worker configuration
        try { $db->execute("ALTER TABLE settings ADD COLUMN external_api_url TEXT"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN external_api_token TEXT"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN external_api_header_name TEXT"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN external_api_insecure INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN worker_last_beat DATETIME NULL"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN worker_interval INTEGER DEFAULT 10"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN worker_is_running INTEGER DEFAULT 0"); } catch (Exception $e) {}
        try { $db->execute("ALTER TABLE settings ADD COLUMN worker_last_status TEXT"); } catch (Exception $e) {}

        // Seed default admin and viewer users if none exists
        $userCount = $db->query("SELECT COUNT(*) as count FROM users");
        if (($userCount[0]['count'] ?? 0) == 0) {
            $adminHash = password_hash('temporal123', PASSWORD_BCRYPT);
            $viewerHash = password_hash('viewer123', PASSWORD_BCRYPT);
            $analystHash = password_hash('analyst123', PASSWORD_BCRYPT);
            $db->execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", ['admin', $adminHash, 'admin']);
            $db->execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", ['viewer', $viewerHash, 'viewer']);
            $db->execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", ['analyst', $analystHash, 'analyst']);
            echo "Default users seeded (admin / temporal123, viewer / viewer123, analyst / analyst123)\n";
        } else {
            // Ensure viewer user exists
            $viewerCheck = $db->query("SELECT id FROM users WHERE username = 'viewer'");
            if (empty($viewerCheck)) {
                $viewerHash = password_hash('viewer123', PASSWORD_BCRYPT);
                $db->execute("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)", ['viewer', $viewerHash, 'viewer']);
                echo "Viewer user seeded (viewer / viewer123)\n";
            }
        }

        // Seed default API keys if none exist
        $keyCount = $db->query("SELECT COUNT(*) as count FROM api_keys");
        if (($keyCount[0]['count'] ?? 0) == 0) {
            $db->execute("INSERT INTO api_keys (name, key_value, rate_limit, is_active) VALUES (?, ?, ?, ?)", [
                'Default Production Key', 'temporal_grid_api_key_2024', 100, 1
            ]);
            $db->execute("INSERT INTO api_keys (name, key_value, rate_limit, is_active) VALUES (?, ?, ?, ?)", [
                'Demo Key', 'demo_key_12345', 100, 1
            ]);
            echo "Default API keys seeded\n";
        }

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
setupDatabaseSqlite();
