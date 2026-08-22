<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';

Auth::getInstance()->requireLogin();
$db = Database::getInstance();
$row = $db->query("SELECT raw_retention_days, agg_retention_days, updated_at FROM settings ORDER BY id DESC LIMIT 1");
$settings = $row[0] ?? ['raw_retention_days' => 60, 'agg_retention_days' => 365, 'updated_at' => null];
Utils::successResponse($settings);
