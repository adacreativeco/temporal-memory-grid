<?php
use Temporal\Auth;
use Temporal\Database;
use Temporal\Utils;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../utils.php';

Auth::getInstance()->requireLogin();
$db = Database::getInstance();

try {
    // Detect columns
    $cols = $db->query("PRAGMA table_info(settings)");
    $names = array_map(fn($c) => $c['name'], $cols);
    $added = [];
    if (!in_array('external_api_url', $names)) {
        $db->execute("ALTER TABLE settings ADD COLUMN external_api_url TEXT");
        $added[] = 'external_api_url';
    }
    if (!in_array('external_api_token', $names)) {
        $db->execute("ALTER TABLE settings ADD COLUMN external_api_token TEXT");
        $added[] = 'external_api_token';
    }
    if (!in_array('external_api_header_name', $names)) {
        $db->execute("ALTER TABLE settings ADD COLUMN external_api_header_name TEXT");
        $added[] = 'external_api_header_name';
    }
    if (!in_array('external_api_insecure', $names)) {
        $db->execute("ALTER TABLE settings ADD COLUMN external_api_insecure INTEGER");
        $added[] = 'external_api_insecure';
    }
    Utils::successResponse(['added' => $added], 'Settings kolon migrasyonu tamamlandı');
} catch (\Exception $e) {
    Utils::errorResponse($e->getMessage(), 500);
}
