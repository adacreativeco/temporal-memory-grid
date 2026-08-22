<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../i18n.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = \Temporal\Database::getInstance();

    $startTime = $_GET['start_time'] ?? null;
    $endTime = $_GET['end_time'] ?? null;
    $limit = (int)($_GET['limit'] ?? 8);

    if (!$startTime || !$endTime) {
        $endTime = gmdate('Y-m-d H:i:s');
        $startTime = gmdate('Y-m-d H:i:s', time() - 86400);
    } else {
        $startTime = gmdate('Y-m-d H:i:s', strtotime($startTime));
        $endTime = gmdate('Y-m-d H:i:s', strtotime($endTime));
    }

    // Query 1: from bucket_metrics if available
    $sqlMetrics = "
        SELECT 
            bm.metric_subtype AS region,
            SUM(bm.count) AS total_events
        FROM bucket_metrics bm
        JOIN time_buckets tb ON bm.bucket_id = tb.id
        WHERE bm.metric_type = 'events_by_geo_region'
          AND tb.bucket_end > ?
          AND tb.bucket_start < ?
          AND bm.metric_subtype IS NOT NULL
          AND bm.metric_subtype != ''
        GROUP BY bm.metric_subtype
        ORDER BY total_events DESC
        LIMIT ?
    ";

    $results = $db->query($sqlMetrics, [$startTime, $endTime, $limit]);

    // If bucket_metrics is empty or has no geo tags, fall back to raw events table
    if (empty($results)) {
        $sqlRaw = "
            SELECT 
                COALESCE(geo_region, 'Grid_' || ROUND(latitude, 2) || '_' || ROUND(longitude, 2)) AS region,
                COUNT(*) AS total_events
            FROM events
            WHERE event_time >= ? AND event_time < ?
              AND (geo_region IS NOT NULL OR (latitude IS NOT NULL AND longitude IS NOT NULL))
            GROUP BY region
            ORDER BY total_events DESC
            LIMIT ?
        ";
        $results = $db->query($sqlRaw, [$startTime, $endTime, $limit]);
    }

    // Calculate total for percentage distribution
    $grandTotal = 0;
    foreach ($results as $r) {
        $grandTotal += (int)$r['total_events'];
    }

    $regions = [];
    foreach ($results as $index => $r) {
        $cnt = (int)$r['total_events'];
        $pct = $grandTotal > 0 ? round(($cnt / $grandTotal) * 100, 1) : 0;
        
        $intensity = 'low';
        if ($pct >= 35 || $index === 0) {
            $intensity = 'high';
        } elseif ($pct >= 15) {
            $intensity = 'medium';
        }

        $regions[] = [
            'region' => $r['region'],
            'total_events' => $cnt,
            'percentage' => $pct,
            'intensity' => $intensity
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'total_regions' => count($regions),
            'grand_total' => $grandTotal,
            'regions' => $regions
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
