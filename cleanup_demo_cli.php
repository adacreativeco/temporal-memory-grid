<?php
require_once __DIR__ . '/database_pdo.php';
require_once __DIR__ . '/system_logs.php';

// CLI-only cleanup of demo data and aggregated buckets
if (php_sapi_name() !== 'cli') { echo "CLI only\n"; exit(0); }

$db = \Temporal\Database::getInstance();
$logger = \Temporal\SystemLogs::getInstance();

try {
    // Remove demo events by signature
    $db->execute("DELETE FROM events WHERE event_data LIKE '%Sample event%'");
    $demoDeleted = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;

    // Reset aggregated data completely
    $db->execute("DELETE FROM bucket_metrics");
    $metricsDeleted = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;
    $db->execute("DELETE FROM time_buckets");
    $bucketsDeleted = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;

    $logger->log('cleanup_demo_cli', 'success', "demo={$demoDeleted} buckets={$bucketsDeleted} metrics={$metricsDeleted}");
    echo json_encode([
        'deleted_demo_events' => $demoDeleted,
        'deleted_buckets' => $bucketsDeleted,
        'deleted_metrics' => $metricsDeleted
    ], JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    $logger->log('cleanup_demo_cli', 'failed', $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

