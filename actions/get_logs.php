<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

// Require login
$auth = \Temporal\Auth::getInstance();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $db = \Temporal\Database::getInstance();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(5, min(100, (int)($_GET['per_page'] ?? 20)));
    $jobType = trim($_GET['job_type'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $whereClauses = [];
    $params = [];

    if ($jobType !== '') {
        $whereClauses[] = "job_type = ?";
        $params[] = $jobType;
    }

    if ($status !== '') {
        $whereClauses[] = "status = ?";
        $params[] = $status;
    }

    if ($search !== '') {
        $whereClauses[] = "(message LIKE ? OR job_type LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // Count total records
    $countSql = "SELECT COUNT(*) as total FROM system_logs {$whereSql}";
    $countRes = $db->query($countSql, $params);
    $totalItems = (int)($countRes[0]['total'] ?? 0);
    $totalPages = max(1, ceil($totalItems / $perPage));

    $offset = ($page - 1) * $perPage;

    // Fetch records
    $dataSql = "
        SELECT id, created_at, job_type, status, message 
        FROM system_logs 
        {$whereSql} 
        ORDER BY id DESC 
        LIMIT ? OFFSET ?
    ";
    $queryParams = array_merge($params, [$perPage, $offset]);
    $logs = $db->query($dataSql, $queryParams);

    // Get distinct job types for filter dropdown
    $jobTypesRes = $db->query("SELECT DISTINCT job_type FROM system_logs WHERE job_type IS NOT NULL AND job_type != '' ORDER BY job_type");
    $distinctJobTypes = array_map(function($r) { return $r['job_type']; }, $jobTypesRes);

    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $logs,
            'distinct_job_types' => $distinctJobTypes,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
