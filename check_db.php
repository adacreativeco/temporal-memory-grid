<?php
require_once 'config.php';
require_once 'database_pdo.php';

try {
$db = \Temporal\Database::getInstance();
    
    echo "Checking database tables...\n";
    
    // Check if tables exist
    $tables = ['time_buckets', 'bucket_metrics', 'system_logs', 'events'];
    
    foreach ($tables as $table) {
        $result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$table]);
        if (!empty($result)) {
            echo "✓ Table '$table' exists\n";
            
            // Get row count
            $count = $db->query("SELECT COUNT(*) as count FROM $table");
            echo "  - Row count: " . $count[0]['count'] . "\n";
        } else {
            echo "✗ Table '$table' does not exist\n";
        }
    }
    
    echo "\nDatabase check completed.\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
