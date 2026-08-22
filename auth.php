<?php
namespace Temporal;
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_pdo.php';

class Auth {
    private static $instance = null;
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function login($username, $password) {
        $username = trim($username);
        if (empty($username) || empty($password)) {
            return false;
        }

        try {
            $db = Database::getInstance();
            $users = $db->query("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
            
            if (!empty($users)) {
                $user = $users[0];
                $hash = $user['password_hash'];
                
                $isPasswordValid = false;
                if (password_verify($password, $hash)) {
                    $isPasswordValid = true;
                } elseif ($password === $hash) {
                    // Upgrade legacy plain password to bcrypt hash
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    $db->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $user['id']]);
                    $isPasswordValid = true;
                }
                
                if ($isPasswordValid) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'] ?? 'admin';
                    $_SESSION['login_time'] = time();
                    
                    // Update last login timestamp
                    $db->execute("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?", [$user['id']]);
                    return true;
                }
            }
        } catch (\Exception $e) {
            // Fallback for emergency admin login if DB is unreachable
            if ($username === 'admin' && $password === 'temporal123') {
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = 'admin';
                $_SESSION['role'] = 'admin';
                $_SESSION['login_time'] = time();
                return true;
            }
        }
        
        return false;
    }
    
    public function logout() {
        $_SESSION = [];
        if (session_id() !== '' || isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        session_start();
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > \SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit();
        }
    }
    
    public function validateApiKey($api_key) {
        if (empty($api_key)) {
            return false;
        }

        try {
            $db = Database::getInstance();
            $keys = $db->query("SELECT * FROM api_keys WHERE key_value = ? AND is_active = 1 LIMIT 1", [$api_key]);
            if (!empty($keys)) {
                // Update last used timestamp
                $db->execute("UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?", [$keys[0]['id']]);
                return true;
            }
        } catch (\Exception $e) {
            // Fallback hardcoded keys if DB table query fails
            $valid_keys = [
                'temporal_grid_api_key_2024',
                'demo_key_12345'
            ];
            return in_array($api_key, $valid_keys);
        }
        
        return false;
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'] ?? 'admin'
            ];
        }
        return null;
    }

    public function changePassword($userId, $currentPassword, $newPassword) {
        if (empty($newPassword) || strlen($newPassword) < 6) {
            throw new \Exception('Yeni şifre en az 6 karakter olmalıdır.');
        }

        $db = Database::getInstance();
        $users = $db->query("SELECT * FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (empty($users)) {
            throw new \Exception('Kullanıcı bulunamadı.');
        }

        $user = $users[0];
        if (!password_verify($currentPassword, $user['password_hash']) && $currentPassword !== $user['password_hash']) {
            throw new \Exception('Mevcut şifreniz hatalı.');
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $db->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$newHash, $userId]);
        return true;
    }

    public function getApiKeys() {
        $db = Database::getInstance();
        return $db->query("SELECT id, name, key_value, rate_limit, is_active, created_at, last_used_at FROM api_keys ORDER BY id DESC");
    }

    public function createApiKey($name, $rateLimit = 100, $customKey = null) {
        $name = trim($name);
        if (empty($name)) {
            throw new \Exception('API Anahtarı adı boş olamaz.');
        }
        $rateLimit = max(1, (int)$rateLimit);
        $keyValue = $customKey ? trim($customKey) : 'tmg_' . bin2hex(random_bytes(16));

        $db = Database::getInstance();
        $db->execute("INSERT INTO api_keys (name, key_value, rate_limit, is_active) VALUES (?, ?, ?, 1)", [
            $name, $keyValue, $rateLimit
        ]);

        return [
            'id' => $db->lastInsertId(),
            'name' => $name,
            'key_value' => $keyValue,
            'rate_limit' => $rateLimit,
            'is_active' => 1
        ];
    }

    public function toggleApiKey($id, $isActive) {
        $db = Database::getInstance();
        $db->execute("UPDATE api_keys SET is_active = ? WHERE id = ?", [(int)$isActive, (int)$id]);
        return true;
    }

    public function deleteApiKey($id) {
        $db = Database::getInstance();
        $db->execute("DELETE FROM api_keys WHERE id = ?", [(int)$id]);
        return true;
    }
}
