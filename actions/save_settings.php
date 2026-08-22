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

$raw = isset($_POST['raw_retention_days']) ? (int)$_POST['raw_retention_days'] : null;
$agg = isset($_POST['agg_retention_days']) ? (int)$_POST['agg_retention_days'] : null;

if ($raw === null || $agg === null || $raw < 1 || $agg < 1) {
    Utils::errorResponse('Geçersiz retention değerleri', 400);
}

$db = Database::getInstance();
$db->execute("INSERT INTO settings (raw_retention_days, agg_retention_days, updated_at) VALUES (?, ?, CURRENT_TIMESTAMP)", [$raw, $agg]);

SystemLogs::getInstance()->log('settings', 'success', "Retention güncellendi: raw={$raw}, agg={$agg}");
Utils::successResponse(['raw_retention_days' => $raw, 'agg_retention_days' => $agg], 'Ayarlar kaydedildi');
