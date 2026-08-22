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
$url = $_POST['external_api_url'] ?? '';
$token = $_POST['external_api_token'] ?? '';
$header = $_POST['external_api_header_name'] ?? 'X-API-Key';
$insecure = isset($_POST['external_api_insecure']) ? (int)$_POST['external_api_insecure'] : 0;
// Sanitize
$url = trim($url);
$url = preg_replace('/^[`"\']+|[`"\']+$/', '', $url);
// Extract first valid http(s) URL if extra characters exist
if (preg_match('/https?:\/\/[^\s`"\'<>\)\,]+/i', $url, $m)) {
    $url = $m[0];
}
$token = trim($token);
$header = trim($header) ?: 'X-API-Key';
if (!$url) { Utils::errorResponse('external_api_url zorunlu', 400); }

$db = Database::getInstance();
$db->execute("UPDATE settings SET external_api_url = ?, external_api_token = ?, external_api_header_name = ?, external_api_insecure = ?, updated_at = CURRENT_TIMESTAMP WHERE id = (SELECT id FROM settings ORDER BY id DESC LIMIT 1)", [$url, $token, $header, $insecure]);
SystemLogs::getInstance()->log('settings', 'success', "external_api_url updated");
Utils::successResponse(['external_api_url' => $url], 'External API ayarlandı');
