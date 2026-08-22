<?php
namespace Temporal;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');
$auth = Auth::getInstance();
$auth->requireLogin();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

try {
    switch ($action) {
        case 'list':
            $keys = $auth->getApiKeys();
            Utils::successResponse($keys, 'API anahtarları listelendi');
            break;

        case 'create':
            $name = $_POST['name'] ?? '';
            $rateLimit = $_POST['rate_limit'] ?? 100;
            $customKey = $_POST['custom_key'] ?? null;
            $created = $auth->createApiKey($name, $rateLimit, $customKey);
            Utils::successResponse($created, 'Yeni API anahtarı başarıyla oluşturuldu');
            break;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            $isActive = (int)($_POST['is_active'] ?? 1);
            if ($id <= 0) {
                Utils::errorResponse('Geçersiz anahtar ID', 400);
            }
            $auth->toggleApiKey($id, $isActive);
            Utils::successResponse(['id' => $id, 'is_active' => $isActive], 'API anahtarı durumu güncellendi');
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                Utils::errorResponse('Geçersiz anahtar ID', 400);
            }
            $auth->deleteApiKey($id);
            Utils::successResponse(['id' => $id], 'API anahtarı silindi');
            break;

        default:
            Utils::errorResponse('Geçersiz işlem', 400);
            break;
    }
} catch (\Exception $e) {
    Utils::errorResponse($e->getMessage(), 400);
}
