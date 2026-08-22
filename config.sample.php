<?php
// Temporal Memory Grid — Sample Configuration File
// Rename to config.php or customize as needed.

// Database Configuration
define('DB_TYPE', 'sqlite'); // 'mysql' or 'sqlite'
define('DB_HOST', 'localhost');
define('DB_NAME', 'temporal_memory_grid');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Cache Configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_TTL', 60); // seconds

// Security Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('SESSION_TIMEOUT', 3600); // seconds

// Timezone
define('DEFAULT_TIMEZONE', 'UTC');

// Error Reporting
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set(DEFAULT_TIMEZONE);

// Create cache directory if it doesn't exist
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0777, true);
}

// External Realtime Event Ingestion Feed
define('EVENT_PULLER_ENABLED', true);
define('EVENT_GRID_API_URL', 'http://localhost:8081/api/v1/public/events.php');
define('EVENT_PULLER_TOKEN', 'test_key');
