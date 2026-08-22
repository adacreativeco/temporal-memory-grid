<?php
namespace Temporal;
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');
$auth = Auth::getInstance();
$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Utils::errorResponse('Yalnızca POST istekleri kabul edilir', 405);
}

$currentUser = $auth->getCurrentUser();
if (!$currentUser) {
    Utils::errorResponse('Oturum bulunamadı', 401);
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    Utils::errorResponse('Lütfen tüm alanları doldurun', 400);
}

if ($newPassword !== $confirmPassword) {
    Utils::errorResponse('Yeni şifreler eşleşmiyor', 400);
}

try {
    $auth->changePassword($currentUser['id'], $currentPassword, $newPassword);
    Utils::successResponse(null, 'Şifreniz başarıyla güncellendi.');
} catch (\Exception $e) {
    Utils::errorResponse($e->getMessage(), 400);
}
