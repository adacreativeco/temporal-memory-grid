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
    $row = $db->query("SELECT raw_retention_days, agg_retention_days FROM settings ORDER BY id DESC LIMIT 1");
    $rawDays = (int)($row[0]['raw_retention_days'] ?? 60);
    $aggDays = (int)($row[0]['agg_retention_days'] ?? 365);

    $now = time();
    $rawCutoff = date('Y-m-d H:i:s', $now - ($rawDays * 24 * 60 * 60));
    $aggCutoff = date('Y-m-d H:i:s', $now - ($aggDays * 24 * 60 * 60));

    $logger->log('cleanup', 'running', "Cleanup start: raw<{$rawCutoff}, agg<{$aggCutoff}");

    // Cleanup raw events
    $deletedEvents = 0;
    $res = $db->execute("DELETE FROM events WHERE event_time < ?", [$rawCutoff]);
    if ($res) { $deletedEvents = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0; }

    // Find buckets to delete
    $bucketsToDelete = $db->query("SELECT id FROM time_buckets WHERE bucket_end < ?", [$aggCutoff]);
    $bucketIds = array_map(fn($r) => $r['id'], $bucketsToDelete);
    $deletedBuckets = 0;
    $deletedMetrics = 0;
    if (!empty($bucketIds)) {
        // Delete metrics for those buckets
        $placeholders = implode(',', array_fill(0, count($bucketIds), '?'));
        $db->execute("DELETE FROM bucket_metrics WHERE bucket_id IN ($placeholders)", $bucketIds);
        $deletedMetrics = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;
        // Delete buckets
        $db->execute("DELETE FROM time_buckets WHERE id IN ($placeholders)", $bucketIds);
        $deletedBuckets = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;
    }

    $logger->log('cleanup', 'success', "Deleted events={$deletedEvents}, buckets={$deletedBuckets}, metrics={$deletedMetrics}");
    Utils::successResponse([
        'deleted_events' => $deletedEvents,
        'deleted_buckets' => $deletedBuckets,
        'deleted_metrics' => $deletedMetrics,
        'raw_cutoff' => $rawCutoff,
        'agg_cutoff' => $aggCutoff,
    ], 'Cleanup tamamlandı');

} catch (\Exception $e) {
    $logger->log('cleanup', 'failed', $e->getMessage());
    Utils::errorResponse($e->getMessage(), 500);
}
