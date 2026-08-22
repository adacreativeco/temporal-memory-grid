<?php
namespace Temporal;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../data_puller.php';
require_once __DIR__ . '/../aggregation_engine.php';
require_once __DIR__ . '/../derive_rollups.php';
require_once __DIR__ . '/../cache.php';

header('Content-Type: application/json');
$auth = Auth::getInstance();
$auth->requireLogin();

$action = $_POST['action'] ?? ($_GET['action'] ?? 'run_once');

try {
    if ($action === 'run_once') {
        $now = time();
        $startTimeIso = gmdate('Y-m-d\TH:i:s\Z', $now - 900); // 15m window
        $endTimeIso = gmdate('Y-m-d\TH:i:s\Z', $now + 60);

        $result = DataPuller::run($startTimeIso, $endTimeIso, ['limit' => 100]);
        $events = $result['processed_events'] ?? 0;
        $buckets = $result['processed_buckets'] ?? 0;

        // Auto derive rollups
        $startDb = gmdate('Y-m-d H:i:s', $now - 3600);
        $endDb = gmdate('Y-m-d H:i:s', $now);
        try {
            Rollups::derive($startDb, $endDb, '1m', '5m');
            Rollups::derive($startDb, $endDb, '5m', '15m');
        } catch (\Exception $e) {}

        Cache::getInstance()->clear();

        Utils::successResponse([
            'events_processed' => $events,
            'buckets_processed' => $buckets,
            'window_start' => $startTimeIso,
            'window_end' => $endTimeIso
        ], "Döngü başarıyla tamamlandı: {$events} event çekildi, {$buckets} kova güncellendi.");
    } else {
        Utils::errorResponse('Bilinmeyen worker eylemi', 400);
    }
} catch (\Exception $e) {
    Utils::errorResponse('Hata: ' . $e->getMessage(), 500);
}
