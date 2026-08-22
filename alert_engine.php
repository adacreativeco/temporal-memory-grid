<?php
namespace Temporal;

require_once __DIR__ . '/database_pdo.php';
require_once __DIR__ . '/system_logs.php';
require_once __DIR__ . '/utils.php';

class AlertEngine {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = Database::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Evaluates all active alerting rules against current time buckets
     */
    public function evaluateRules() {
        $rules = $this->db->query("SELECT * FROM alert_rules WHERE is_active = 1");
        if (empty($rules)) {
            return ['checked_rules' => 0, 'triggered_alerts' => 0];
        }

        $triggered = 0;
        $now = time();

        foreach ($rules as $rule) {
            // Cooldown check
            if (!empty($rule['last_triggered_at'])) {
                $lastTriggered = strtotime($rule['last_triggered_at']);
                $cooldownSecs = ((int)$rule['cooldown_minutes']) * 60;
                if ($now - $lastTriggered < $cooldownSecs) {
                    continue; // Skip because in cooldown period
                }
            }

            $result = $this->checkRule($rule);
            if ($result['triggered']) {
                $this->fireAlert($rule, $result);
                $triggered++;
            }
        }

        return ['checked_rules' => count($rules), 'triggered_alerts' => $triggered];
    }

    /**
     * Checks if a single rule condition is met
     */
    private function checkRule($rule) {
        $bucketSize = $rule['bucket_size'] ?: '1m';
        $threshold = (float)$rule['threshold_value'];
        $ruleType = $rule['rule_type'];

        // Get latest bucket
        $latest = $this->db->query(
            "SELECT bucket_start, bucket_end, total_events FROM time_buckets WHERE bucket_size = ? ORDER BY bucket_start DESC LIMIT 1",
            [$bucketSize]
        );

        if (empty($latest)) {
            return ['triggered' => false];
        }

        $bucket = $latest[0];
        $observedValue = (float)$bucket['total_events'];

        if ($ruleType === 'volume_threshold') {
            if ($observedValue >= $threshold) {
                return [
                    'triggered' => true,
                    'reason' => "Volume spike detected: {$observedValue} events in {$bucketSize} bucket (threshold: {$threshold})",
                    'observed' => $observedValue,
                    'threshold' => $threshold,
                    'bucket_time' => $bucket['bucket_start']
                ];
            }
        } elseif ($ruleType === 'anomaly_spike') {
            // Compute historical baseline average for this bucket size
            $history = $this->db->query(
                "SELECT AVG(total_events) as avg_events FROM time_buckets WHERE bucket_size = ?",
                [$bucketSize]
            );
            $baseline = (float)($history[0]['avg_events'] ?? 0);

            if ($baseline > 0) {
                $deviationPercent = round((($observedValue - $baseline) / $baseline) * 100, 1);
                if ($deviationPercent >= $threshold) {
                    return [
                        'triggered' => true,
                        'reason' => "Anomaly spike detected: +{$deviationPercent}% deviation from historical average (threshold: {$threshold}%)",
                        'observed' => $deviationPercent,
                        'threshold' => $threshold,
                        'bucket_time' => $bucket['bucket_start']
                    ];
                }
            }
        }

        return ['triggered' => false];
    }

    /**
     * Dispatches the webhook notification and records history
     */
    public function fireAlert($rule, $checkResult, $isTest = false) {
        $url = $rule['webhook_url'];
        $format = $rule['webhook_format'] ?: 'generic_json';
        $ruleName = $rule['name'];
        $reason = $checkResult['reason'] ?? 'Manual test notification';
        $observed = (float)($checkResult['observed'] ?? 0);
        $threshold = (float)($checkResult['threshold'] ?? $rule['threshold_value']);

        // Build Payload
        $payload = $this->buildPayload($format, $rule, $reason, $observed, $threshold, $isTest);

        // Send HTTP Request
        $sendResult = $this->dispatchHttp($url, $payload);

        // Record in alert_history
        $sql = "
            INSERT INTO alert_history 
            (rule_id, rule_name, trigger_reason, observed_value, threshold_value, webhook_status, response_code, payload_json) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $this->db->execute($sql, [
            $rule['id'] ?? 0,
            $ruleName,
            $reason,
            $observed,
            $threshold,
            $sendResult['status'],
            $sendResult['code'],
            json_encode($payload)
        ]);

        // Update rule last_triggered_at if not a test
        if (!$isTest && !empty($rule['id'])) {
            $this->db->execute(
                "UPDATE alert_rules SET last_triggered_at = ? WHERE id = ?",
                [gmdate('Y-m-d H:i:s'), $rule['id']]
            );
        }

        // Log system event
        SystemLogs::getInstance()->log(
            'alerting',
            $sendResult['status'] === 'sent' ? 'success' : 'failed',
            "Alert [{$ruleName}]: {$reason} -> Webhook: {$sendResult['status']} (HTTP {$sendResult['code']})"
        );

        return $sendResult;
    }

    /**
     * Builds platform-specific JSON payload (Generic, Discord, Slack)
     */
    private function buildPayload($format, $rule, $reason, $observed, $threshold, $isTest) {
        $timeStr = gmdate('Y-m-d\TH:i:s\Z');
        $title = ($isTest ? '[TEST] ' : '🚨 ') . 'TMG Alert: ' . $rule['name'];

        if ($format === 'discord') {
            return [
                'username' => 'Temporal Memory Grid',
                'avatar_url' => 'https://adacreative.co/favicon.ico',
                'embeds' => [
                    [
                        'title' => $title,
                        'description' => $reason,
                        'color' => $isTest ? 3447003 : 15158332, // Blue for test, Red for alert
                        'fields' => [
                            ['name' => 'Rule Type', 'value' => $rule['rule_type'], 'inline' => true],
                            ['name' => 'Observed Value', 'value' => (string)$observed, 'inline' => true],
                            ['name' => 'Threshold', 'value' => (string)$threshold, 'inline' => true],
                        ],
                        'footer' => ['text' => 'Temporal Memory Grid (TMG) • ' . $timeStr]
                    ]
                ]
            ];
        } elseif ($format === 'slack') {
            return [
                'text' => "*{$title}*\n{$reason}",
                'attachments' => [
                    [
                        'color' => $isTest ? '#3b82f6' : '#ef4444',
                        'fields' => [
                            ['title' => 'Rule Type', 'value' => $rule['rule_type'], 'short' => true],
                            ['title' => 'Observed', 'value' => (string)$observed, 'short' => true],
                            ['title' => 'Threshold', 'value' => (string)$threshold, 'short' => true],
                            ['title' => 'Timestamp', 'value' => $timeStr, 'short' => true]
                        ]
                    ]
                ]
            ];
        }

        // Default: generic_json
        return [
            'event' => $isTest ? 'alert_test' : 'alert_triggered',
            'severity' => $isTest ? 'info' : 'critical',
            'rule_id' => $rule['id'] ?? null,
            'rule_name' => $rule['name'],
            'rule_type' => $rule['rule_type'],
            'reason' => $reason,
            'metrics' => [
                'observed_value' => $observed,
                'threshold_value' => $threshold,
                'bucket_size' => $rule['bucket_size'] ?? '1m'
            ],
            'timestamp' => $timeStr,
            'generator' => 'Temporal Memory Grid (ADA Creative Co.)'
        ];
    }

    /**
     * Dispatches HTTP POST via cURL
     */
    private function dispatchHttp($url, $payload) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['status' => 'failed', 'code' => 0, 'response' => 'Invalid Webhook URL format'];
        }

        $ch = curl_init();
        $json = json_encode($payload);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json),
            'User-Agent: Temporal-Memory-Grid-AlertEngine/1.0'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300);

        return [
            'status' => $success ? 'sent' : 'failed',
            'code' => $httpCode ?: 0,
            'response' => $response ?: $curlError
        ];
    }

    /**
     * Send test alert for a specific rule
     */
    public function testRule($ruleId) {
        $rule = $this->db->query("SELECT * FROM alert_rules WHERE id = ?", [$ruleId]);
        if (empty($rule)) {
            throw new \Exception("Alert rule #{$ruleId} not found");
        }
        return $this->fireAlert($rule[0], [
            'reason' => 'Verification test from TMG Alerting Console',
            'observed' => 520,
            'threshold' => $rule[0]['threshold_value']
        ], true);
    }
}
