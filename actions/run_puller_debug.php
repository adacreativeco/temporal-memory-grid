<?php
use Temporal\Auth;
use Temporal\Utils;
use Temporal\DataPuller;
use Temporal\Cache;
use Temporal\Database;
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../data_puller.php';
header('Content-Type: application/json');
ini_set('display_errors','0');
Auth::getInstance()->requireLogin();

$end = isset($_GET['end_time']) ? $_GET['end_time'] : null;
$minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : null;
$start = isset($_GET['start_time']) ? $_GET['start_time'] : null;
if ($minutes && !$start && !$end) {
    if ($minutes <= 0) { $minutes = 5; }
    if ($minutes > (30*24*60)) { $minutes = 5; }
    $end = gmdate('Y-m-d\TH:i:s\Z');
    $start = gmdate('Y-m-d\TH:i:s\Z', strtotime($end) - $minutes * 60);
}
$opts = [];
foreach (['type','source_id','limit','offset','after_id'] as $k) { if (isset($_GET[$k])) $opts[$k] = $_GET[$k]; }

$out = [ 'ok' => true, 'error' => null, 'data' => null ];
try {
    $db = Database::getInstance();
    $row = $db->query("SELECT external_api_url FROM settings ORDER BY id DESC LIMIT 1");
    $extUrl = $row[0]['external_api_url'] ?? (defined('EVENT_GRID_API_URL') ? \EVENT_GRID_API_URL : null);
    if (is_string($extUrl)) {
        $extUrl = trim($extUrl);
        $extUrl = preg_replace('/^[`"\']+|[`"\']+$/', '', $extUrl);
    }
    if (!$extUrl || trim($extUrl) === '') {
        throw new \Exception('External API URL ayarlı değil');
    }
    $res = DataPuller::run($start, $end, $opts);
    Cache::getInstance()->clear();
    $out['data'] = [ 'start_time' => $start, 'end_time' => $end, 'processed_events' => $res['processed_events'], 'processed_buckets' => $res['processed_buckets'] ];
} catch (\Exception $e) {
    $out['ok'] = false;
    $out['error'] = $e->getMessage();
}
echo json_encode($out);
