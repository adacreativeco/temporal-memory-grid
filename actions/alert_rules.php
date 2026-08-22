<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../alert_engine.php';

header('Content-Type: application/json; charset=utf-8');

// Require authentication
$auth = \Temporal\Auth::getInstance();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = \Temporal\Database::getInstance();
$action = $_REQUEST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $rules = $db->query("SELECT * FROM alert_rules ORDER BY id DESC");
            echo json_encode(['success' => true, 'data' => $rules]);
            break;

        case 'create':
            $name = trim($_POST['name'] ?? '');
            $ruleType = trim($_POST['rule_type'] ?? 'volume_threshold');
            $metricType = trim($_POST['metric_type'] ?? 'total_events');
            $threshold = (float)($_POST['threshold_value'] ?? 100);
            $bucketSize = trim($_POST['bucket_size'] ?? '1m');
            $webhookUrl = trim($_POST['webhook_url'] ?? '');
            $webhookFormat = trim($_POST['webhook_format'] ?? 'generic_json');
            $cooldown = (int)($_POST['cooldown_minutes'] ?? 5);

            if (!$name || !$webhookUrl) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Rule name and Webhook URL are required']);
                exit;
            }

            $sql = "
                INSERT INTO alert_rules 
                (name, rule_type, metric_type, threshold_value, bucket_size, webhook_url, webhook_format, cooldown_minutes, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ";
            $db->execute($sql, [$name, $ruleType, $metricType, $threshold, $bucketSize, $webhookUrl, $webhookFormat, $cooldown]);

            echo json_encode([
                'success' => true,
                'message' => 'Alert rule created successfully',
                'id' => $db->lastInsertId()
            ]);
            break;

        case 'toggle':
            $id = (int)($_POST['id'] ?? 0);
            $status = (int)($_POST['is_active'] ?? 0);
            $db->execute("UPDATE alert_rules SET is_active = ? WHERE id = ?", [$status, $id]);
            echo json_encode(['success' => true, 'message' => 'Alert rule updated']);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            $db->execute("DELETE FROM alert_rules WHERE id = ?", [$id]);
            echo json_encode(['success' => true, 'message' => 'Alert rule deleted']);
            break;

        case 'test':
            $id = (int)($_POST['id'] ?? 0);
            $result = \Temporal\AlertEngine::getInstance()->testRule($id);
            echo json_encode([
                'success' => $result['status'] === 'sent',
                'data' => $result,
                'message' => $result['status'] === 'sent' ? 'Test webhook delivered successfully (HTTP ' . $result['code'] . ')' : 'Failed to deliver test webhook (HTTP ' . $result['code'] . ')'
            ]);
            break;

        case 'history':
            $history = $db->query("SELECT * FROM alert_history ORDER BY id DESC LIMIT 20");
            echo json_encode(['success' => true, 'data' => $history]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
