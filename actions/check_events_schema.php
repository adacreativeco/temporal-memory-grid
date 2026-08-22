<?php
use Temporal\Auth;
use Temporal\Database;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
header('Content-Type: application/json');
Auth::getInstance()->requireLogin();
$db = Database::getInstance()->getConnection();
$stmt = $db->query('PRAGMA table_info(events)');
$cols = [];
foreach ($stmt->fetchAll() as $row) { $cols[] = $row['name']; }
echo json_encode(['columns'=>$cols]);
