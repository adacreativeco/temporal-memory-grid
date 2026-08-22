<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

$auth = \Temporal\Auth::getInstance();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden: Admin access required']);
    exit;
}

$action = $_REQUEST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $users = $auth->getUsers();
            echo json_encode(['success' => true, 'data' => $users]);
            break;

        case 'create':
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = trim($_POST['role'] ?? 'viewer');

            $res = $auth->createUser($username, $password, $role);
            echo json_encode([
                'success' => true,
                'message' => "Kullanıcı ({$username}) başarıyla oluşturuldu.",
                'data' => $res
            ]);
            break;

        case 'update_role':
            $userId = (int)($_POST['id'] ?? 0);
            $role = trim($_POST['role'] ?? 'viewer');

            $auth->updateUserRole($userId, $role);
            echo json_encode([
                'success' => true,
                'message' => 'Kullanıcı rolü başarıyla güncellendi.'
            ]);
            break;

        case 'delete':
            $userId = (int)($_POST['id'] ?? 0);
            $auth->deleteUser($userId);
            echo json_encode([
                'success' => true,
                'message' => 'Kullanıcı hesabı silindi.'
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
