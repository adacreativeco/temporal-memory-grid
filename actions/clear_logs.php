<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

$auth = \Temporal\Auth::getInstance();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = \Temporal\Database::getInstance();
    $db->execute("DELETE FROM system_logs");
    $db->execute("DELETE FROM aggregation_jobs_log");

    echo json_encode([
        'success' => true,
        'message' => 'All system logs have been cleared successfully.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
