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
try {
    $row = $db->query("SELECT id, external_api_url FROM settings ORDER BY id DESC LIMIT 1");
    if (empty($row)) { Utils::errorResponse('Settings kaydı bulunamadı', 404); }
    $id = $row[0]['id'];
    $url = (string)($row[0]['external_api_url'] ?? '');
    $clean = trim($url);
    $clean = preg_replace('/^[`"\']+|[`"\']+$/', '', $clean);
    if (strpos($clean, '```') === 0) {
        $clean = preg_replace('/^```[\s\S]*?\n/', '', $clean);
        $clean = preg_replace('/\n```$/', '', $clean);
        $clean = trim($clean);
    }
    if (preg_match('/https?:\/\/[^\s`"\'<>\)\,]+/i', $clean, $m)) {
        $clean = $m[0];
    }
    $db->execute("UPDATE settings SET external_api_url = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$clean, $id]);
    Utils::successResponse(['old'=>$url,'new'=>$clean],'External API URL düzeltildi');
} catch (\Exception $e) {
    Utils::errorResponse($e->getMessage(), 500);
}
