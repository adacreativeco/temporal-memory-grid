<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';

Auth::getInstance()->requireLogin();
$db = Database::getInstance();
$cfg = ['external_api_url' => null, 'external_api_token' => null, 'external_api_header_name' => 'X-API-Key', 'external_api_insecure' => 0];
try {
    $row = $db->query("SELECT external_api_url, external_api_token, external_api_header_name, external_api_insecure FROM settings ORDER BY id DESC LIMIT 1");
    $cfg = $row[0] ?? $cfg;
} catch (\Exception $e) {
    // Column may not exist yet; fall back to config
    $cfg['external_api_url'] = defined('EVENT_GRID_API_URL') ? \EVENT_GRID_API_URL : null;
}
Utils::successResponse($cfg);
