<?php
use Temporal\DataPuller;
use Temporal\Cache;
use Temporal\Database;
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../data_puller.php';
require_once __DIR__ . '/../cache.php';
header('Content-Type: application/json');
ini_set('display_errors','0');
$minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 5;
$end = gmdate('Y-m-d\TH:i:s\Z');
$start = gmdate('Y-m-d\TH:i:s\Z', strtotime($end) - $minutes * 60);
$out = [ 'ok' => true, 'error' => null, 'data' => null ];
try {
    $res = DataPuller::run($start, $end, []);
    Cache::getInstance()->clear();
    $out['data'] = [ 'start_time' => $start, 'end_time' => $end, 'processed_events' => $res['processed_events'], 'processed_buckets' => $res['processed_buckets'] ];
} catch (\Exception $e) {
    $out['ok'] = false;
    $out['error'] = $e->getMessage();
}
echo json_encode($out);
