<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';
header('Content-Type: application/json');
ini_set('display_errors','0');
Auth::getInstance()->requireLogin();
$db = Database::getInstance();
$cfg = ['external_api_url' => null, 'external_api_token' => null, 'external_api_header_name' => 'X-API-Key', 'external_api_insecure' => 0];
try { $row = $db->query("SELECT external_api_url, external_api_token, external_api_header_name, external_api_insecure FROM settings ORDER BY id DESC LIMIT 1"); $cfg = $row[0] ?? $cfg; } catch (\Exception $e) {}
$url = is_string($cfg['external_api_url'] ?? '') ? trim($cfg['external_api_url']) : '';
$url = preg_replace('/^[`"\']+|[`"\']+$/', '', $url);
$headerName = is_string($cfg['external_api_header_name'] ?? '') ? trim($cfg['external_api_header_name']) : 'X-API-Key';
if ($headerName === '') { $headerName = 'X-API-Key'; }
$token = is_string($cfg['external_api_token'] ?? '') ? trim($cfg['external_api_token']) : '';
$allowUrlFopen = ini_get('allow_url_fopen');
$hasCurl = function_exists('curl_init');
$hasOpenssl = extension_loaded('openssl');
$parsed = parse_url($url);
$host = $parsed['host'] ?? null;
$ip = $host ? @gethostbyname($host) : null;
$req = $url ? $url . (strpos($url, '?') === false ? '?' : '&') . http_build_query(['limit'=>1]) : '';
$headersArr = [];
if ($token) { $headersArr[] = ($headerName ?: 'X-API-Key') . ': ' . $token; }
$curlInfo = ['ok'=>false,'error'=>null,'http_code'=>null,'content_sample'=>null];
if ($hasCurl && $req) {
    $ch = curl_init($req);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    if (!empty($headersArr)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArr);
    // Apply insecure SSL if configured and scheme is https
    $scheme = $parsed['scheme'] ?? '';
    $insecure = (int)($cfg['external_api_insecure'] ?? 0) === 1;
    if ($scheme === 'https' && $insecure) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }
    $resp = curl_exec($ch);
    if ($resp !== false) { $curlInfo['ok'] = true; $curlInfo['http_code'] = curl_getinfo($ch, CURLINFO_RESPONSE_CODE); $curlInfo['content_sample'] = substr($resp, 0, 200); }
    else { $curlInfo['error'] = curl_error($ch); }
    unset($ch);
}
$fopenInfo = ['ok'=>false,'error'=>null,'content_sample'=>null];
if ($allowUrlFopen && $req) {
    $opts = [ 'http' => [ 'method' => 'GET', 'header' => implode("\r\n", $headersArr), 'timeout' => 8 ] ];
    $ctx = stream_context_create($opts);
    $resp = @file_get_contents($req, false, $ctx);
    if ($resp !== false) { $fopenInfo['ok'] = true; $fopenInfo['content_sample'] = substr($resp, 0, 200); }
    else { $last = error_get_last(); $fopenInfo['error'] = $last['message'] ?? 'unknown error'; }
}
Utils::successResponse([
    'config_url' => $url,
    'header_name' => $headerName,
    'token_present' => $token !== '',
    'insecure_flag' => (int)($cfg['external_api_insecure'] ?? 0) === 1,
    'allow_url_fopen' => $allowUrlFopen,
    'has_curl' => $hasCurl,
    'has_openssl' => $hasOpenssl,
    'dns_host' => $host,
    'resolved_ip' => $ip,
    'request_url' => $req,
    'curl' => $curlInfo,
    'fopen' => $fopenInfo
], 'External API debug');
