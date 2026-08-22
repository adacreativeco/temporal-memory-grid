<?php
require_once 'aggregation_engine.php';
require_once 'auth.php';
// Simple CLI runner: php run_aggregation.php 2025-01-01T00:00:00Z 2025-01-01T06:00:00Z 5m

if (php_sapi_name() !== 'cli') {
    echo "Run from CLI: php run_aggregation.php <start_iso> <end_iso> <bucket>\n";
    exit(0);
}

if ($argc < 4) {
    echo "Usage: php run_aggregation.php <start_iso> <end_iso> <bucket>\n";
    exit(1);
}

$start = $argv[1];
$end = $argv[2];
$bucket = $argv[3];

$engine = \Temporal\AggregationEngine::getInstance();
$result = $engine->aggregateEvents($start, $end, $bucket);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
