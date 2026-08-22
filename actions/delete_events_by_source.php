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

$source = $_POST['source_id'] ?? $_GET['source_id'] ?? '';
if (!$source) {
    Utils::errorResponse('source_id zorunlu', 400);
}

$db = Database::getInstance();
$logger = SystemLogs::getInstance();

try {
    $db->execute("DELETE FROM events WHERE source_id = ?", [$source]);
    $deleted = $db->query("SELECT changes() AS cnt")[0]['cnt'] ?? 0;
    $logger->log('delete_events', 'success', "source_id={$source}, deleted={$deleted}");
    Utils::successResponse(['deleted_events' => $deleted], 'Belirtilen source için eventler silindi');
} catch (\Exception $e) {
    $logger->log('delete_events', 'failed', $e->getMessage());
    Utils::errorResponse($e->getMessage(), 500);
}

