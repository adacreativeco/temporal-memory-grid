<?php
namespace Temporal;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');
$auth = Auth::getInstance();
$auth->requireLogin();

try {
    $db = Database::getInstance();
    $row = $db->query("SELECT worker_last_beat, worker_interval, worker_is_running, worker_last_status FROM settings LIMIT 1");
    $settings = $row[0] ?? [];

    $lastBeat = $settings['worker_last_beat'] ?? null;
    $interval = (int)($settings['worker_interval'] ?? 10);
    $statusText = $settings['worker_last_status'] ?? 'Bilinmiyor';

    // Worker is considered active if heartbeat was received within (interval * 2 + 10) seconds
    $isLive = false;
    $secondsAgo = null;

    if (!empty($lastBeat)) {
        $beatTime = strtotime($lastBeat);
        if ($beatTime !== false) {
            $secondsAgo = time() - $beatTime;
            $isLive = ($secondsAgo <= ($interval * 2 + 15));
        }
    }

    // Get latest aggregation job info
    $latestJob = $db->query("SELECT job_type, status, message, created_at FROM aggregation_jobs_log ORDER BY id DESC LIMIT 1");

    Utils::successResponse([
        'is_live' => $isLive,
        'last_beat' => $lastBeat,
        'seconds_ago' => $secondsAgo,
        'interval' => $interval,
        'status_text' => $statusText,
        'latest_job' => $latestJob[0] ?? null
    ], 'Worker durumu getirildi');

} catch (\Exception $e) {
    Utils::errorResponse($e->getMessage(), 500);
}
