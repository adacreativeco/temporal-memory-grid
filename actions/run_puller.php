<?php
use Temporal\Auth;
use Temporal\Utils;
use Temporal\DataPuller;
use Temporal\Cache;
use Temporal\Database;
require_once __DIR__ . '/../database_pdo.php';
header('Content-Type: application/json');
ini_set('display_errors','0');
ob_start();
register_shutdown_function(function(){
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode(['success'=>false,'error'=>'Sunucu hatası','data'=>['message'=>$e['message']]]);
    }
});
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../data_puller.php';
require_once __DIR__ . '/../cache.php';

Auth::getInstance()->requireLogin();

$end = isset($_GET['end_time']) ? $_GET['end_time'] : null;
$minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : null;
$start = isset($_GET['start_time']) ? $_GET['start_time'] : null;
if ($minutes && !$start && !$end) {
    if ($minutes <= 0) { $minutes = 5; }
    if ($minutes > (30*24*60)) { $minutes = 5; } // limit to <=30 days
    $end = gmdate('Y-m-d\TH:i:s\Z');
    $start = gmdate('Y-m-d\TH:i:s\Z', strtotime($end) - $minutes * 60);
}
$opts = [];
if (isset($_GET['type'])) $opts['type'] = $_GET['type'];
if (isset($_GET['source_id'])) $opts['source_id'] = $_GET['source_id'];
if (isset($_GET['limit'])) $opts['limit'] = $_GET['limit'];
if (isset($_GET['offset'])) $opts['offset'] = $_GET['offset'];
if (isset($_GET['after_id'])) $opts['after_id'] = $_GET['after_id'];

try {
    $db = Database::getInstance();
    $extUrl = null;
    try {
        $row = $db->query("SELECT external_api_url FROM settings ORDER BY id DESC LIMIT 1");
        $extUrl = $row[0]['external_api_url'] ?? null;
    } catch (\Exception $e) {
        $extUrl = defined('EVENT_GRID_API_URL') ? \EVENT_GRID_API_URL : null;
    }
    // Sanitize possible pasted code-fence/backticks/quotes
    if (is_string($extUrl)) {
        $extUrl = trim($extUrl);
        $extUrl = preg_replace('/^[`"\']+|[`"\']+$/', '', $extUrl);
    }
    if (!$extUrl || trim($extUrl) === '') {
        Utils::errorResponse('External API URL ayarlı değil. Lütfen Ayarlar → External API Ayarı bölümünden URL’yi kaydedin.', 400);
    }
    $res = DataPuller::run($start, $end, $opts);
    // Clear cache so dashboard/API reads fresh aggregates
    Cache::getInstance()->clear();
    Utils::successResponse(['start_time' => $start, 'end_time' => $end, 'processed_events' => $res['processed_events'], 'processed_buckets' => $res['processed_buckets']], 'Puller çalıştı');
} catch (\Exception $e) {
    Utils::errorResponse($e->getMessage(), 500);
}
