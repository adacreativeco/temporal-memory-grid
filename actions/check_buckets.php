<?php
use Temporal\Auth;
use Temporal\Database;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
header('Content-Type: application/json');
Auth::getInstance()->requireLogin();
$db = Database::getInstance()->getConnection();
$rows = $db->query("SELECT id, bucket_start, bucket_end, bucket_size, total_events FROM time_buckets ORDER BY bucket_start DESC LIMIT 10")->fetchAll();
$metrics = $db->query("SELECT bucket_id, metric_type, metric_subtype, count FROM bucket_metrics ORDER BY bucket_id DESC, metric_type LIMIT 30")->fetchAll();
echo json_encode(['buckets'=>$rows,'metrics'=>$metrics]);
