<?php
// Redirect to timeseries aggregate with format
$params = $_GET;
if (!isset($params['format'])) {
    $params['format'] = 'csv';
}
$query = http_build_query($params);
header('Location: /api/v1/timeseries.php?' . $query, true, 302);
exit();
