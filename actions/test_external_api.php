<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json');
ini_set('display_errors','0');
ob_start();
register_shutdown_function(function(){
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo json_encode(['success'=>false,'error'=>'Sunucu hatası','data'=>['message'=>$e['message']]]);
    }
});

Auth::getInstance()->requireLogin();
$db = Database::getInstance();
$row = $db->query("SELECT external_api_url, external_api_token, external_api_header_name, external_api_insecure FROM settings ORDER BY id DESC LIMIT 1");
$cfg = $row[0] ?? null;
if (!$cfg || empty($cfg['external_api_url'])) {
    Utils::errorResponse('External API URL ayarlı değil', 400);
}
$limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
$qs = http_build_query(['limit' => $limit]);
$url = $cfg['external_api_url'] . (strpos($cfg['external_api_url'], '?') === false ? '?' : '&') . $qs;

// DNS çözümleme
$parsed = parse_url($cfg['external_api_url']);
$host = $parsed['host'] ?? null;
$ip = $host ? gethostbyname($host) : null;
$headers = [];
if (!empty($cfg['external_api_token'])) {
    $headers[] = ($cfg['external_api_header_name'] ?: 'X-API-Key') . ': ' . $cfg['external_api_token'];
}
// Insecure toggle
$insecure = isset($_GET['insecure']) ? ($_GET['insecure'] === '1') : ((int)($cfg['external_api_insecure'] ?? 0) === 1);

$resp = null; $headersResp = null; $netError = null; $usingCurl = false;

if (function_exists('curl_init')) {
    $usingCurl = true;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($insecure && ($parsed['scheme'] ?? '') === 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    $resp = curl_exec($ch);
    if ($resp === false) { $netError = curl_error($ch); }
    $headersResp = []; // curl doesn't fetch headers here; kept empty
    unset($ch);
} else {
    $opts = [ 'http' => [ 'method' => 'GET', 'header' => implode("\r\n", $headers), 'timeout' => 8 ] ];
    if ($insecure && ($parsed['scheme'] ?? '') === 'https') {
        $opts['ssl'] = [ 'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true ];
    }
    $ctx = stream_context_create($opts);
    $headersResp = @get_headers($url);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        $last = error_get_last();
        $netError = $last['message'] ?? 'unknown error';
    }
}

if ($resp === false || $resp === null) {
    Utils::errorResponse('External API erişilemedi: ' . ($netError ?: 'bilinmeyen hata'), 502);
}
$json = json_decode($resp, true);
if (!$json || ($json['status'] ?? null) !== 'success') {
    Utils::errorResponse('External API yanıtı geçersiz', 500);
}
Utils::successResponse([
    'count' => $json['count'] ?? count($json['data'] ?? []),
    'sample' => array_slice($json['data'] ?? [], 0, 3),
    'resolved_ip' => $ip,
    'response_headers' => $headersResp,
    'request_url' => $url,
    'using_curl' => $usingCurl,
    'insecure' => $insecure
], 'External API test OK');
