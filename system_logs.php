<?php
namespace Temporal;
require_once __DIR__ . '/database_pdo.php';

class SystemLogs {
    private static $instance = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function log($job_type, $status, $message = '') {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO system_logs (job_type, status, message) VALUES (?, ?, ?)";
        return $db->execute($sql, [$job_type, $status, $message]);
    }
    
    public function getLogs($job_type = null, $status = null, $limit = 100) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM system_logs WHERE 1=1";
        $params = [];
        
        if ($job_type !== null) {
            $sql .= " AND job_type = ?";
            $params[] = $job_type;
        }
        
        if ($status !== null) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        return $db->query($sql, $params);
    }
    
    public function getLatestLog($job_type = null) {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM system_logs WHERE 1=1";
        $params = [];
        
        if ($job_type !== null) {
            $sql .= " AND job_type = ?";
            $params[] = $job_type;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 1";
        
        $result = $db->query($sql, $params);
        return $result[0] ?? null;
    }
    
    public function cleanupLogs($days_to_keep = 30) {
        $db = Database::getInstance();
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        $sql = "DELETE FROM system_logs WHERE created_at < ?";
        
        return $db->execute($sql, [$cutoff_date]);
    }
    
    public function getLogStats() {
        $db = Database::getInstance();
        
        $stats = [];
        
        // Total logs by job type
        $result = $db->query("SELECT job_type, COUNT(*) as count FROM system_logs GROUP BY job_type");
        $stats['by_job_type'] = array_column($result, 'count', 'job_type');
        
        // Total logs by status
        $result = $db->query("SELECT status, COUNT(*) as count FROM system_logs GROUP BY status");
        $stats['by_status'] = array_column($result, 'count', 'status');
        
        // Logs in last 24 hours
        $result = $db->query("SELECT COUNT(*) as count FROM system_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stats['last_24h'] = $result[0]['count'] ?? 0;
        
        return $stats;
    }
}
