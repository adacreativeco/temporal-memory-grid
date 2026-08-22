<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
use Temporal\SystemLogs;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../system_logs.php';

Auth::getInstance()->requireLogin();
$db = Database::getInstance();
$logger = SystemLogs::getInstance();

try {
    // Delete seed demo events (inserted via setup script): match by event_data containing "Sample event"
    $db->execute("DELETE FROM events WHERE event_data LIKE '%Sample event%'");
    $deletedDemo = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;

    // Optionally clear aggregation to remove demo influence
    $db->execute("DELETE FROM bucket_metrics");
    $deletedMetrics = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;
    $db->execute("DELETE FROM time_buckets");
    $deletedBuckets = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;

    $logger->log('cleanup_demo', 'success', "Removed demo events={$deletedDemo}, buckets={$deletedBuckets}, metrics={$deletedMetrics}");
    Utils::successResponse([
        'deleted_demo_events' => $deletedDemo,
        'deleted_buckets' => $deletedBuckets,
        'deleted_metrics' => $deletedMetrics
    ], 'Demo verileri kaldırıldı ve agregasyon sıfırlandı');

} catch (\Exception $e) {
    $logger->log('cleanup_demo', 'failed', $e->getMessage());
    Utils::errorResponse($e->getMessage(), 500);
}

