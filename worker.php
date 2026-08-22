<?php
/**
 * Temporal Memory Grid — Background Worker & Automation Daemon
 *
 * Usage:
 *   php worker.php                 # Runs continuously as daemon (default: 10s interval)
 *   php worker.php --once          # Runs one execution cycle (for cron)
 *   php worker.php --interval=5    # Custom loop interval in seconds
 *   php worker.php --simulate      # Generate simulated events when external API is silent
 */

namespace Temporal;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database_pdo.php';
require_once __DIR__ . '/data_puller.php';
require_once __DIR__ . '/aggregation_engine.php';
require_once __DIR__ . '/derive_rollups.php';
require_once __DIR__ . '/system_logs.php';
require_once __DIR__ . '/cache.php';

// Only allow CLI execution
if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== 'tmg_cron_trigger')) {
    header('HTTP/1.1 403 Forbidden');
    echo "Worker can only be executed from CLI or authorized cron endpoint.\n";
    exit(1);
}

class Worker {
    private $db;
    private $logger;
    private $interval = 10;
    private $runOnce = false;
    private $simulate = false;
    private $lastCleanup = 0;
    private $isRunning = true;

    public function __construct($options = []) {
        $this->db = Database::getInstance();
        $this->logger = SystemLogs::getInstance();
        $this->runOnce = !empty($options['once']);
        $this->interval = max(3, (int)($options['interval'] ?? 10));
        $this->simulate = !empty($options['simulate']);

        // Register shutdown handler
        register_shutdown_function([$this, 'shutdown']);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }

    public function handleSignal($signal) {
        echo "\n[Worker] Signal {$signal} received. Gracefully shutting down...\n";
        $this->isRunning = false;
    }

    public function shutdown() {
        try {
            $this->db->execute("UPDATE settings SET worker_is_running = 0, worker_last_status = 'Stopped' WHERE id = 1");
        } catch (\Exception $e) {}
    }

    public function start() {
        echo "========================================================\n";
        echo "  TEMPORAL MEMORY GRID — BACKGROUND WORKER DAEMON\n";
        echo "========================================================\n";
        echo "  Mode      : " . ($this->runOnce ? "Single Run (Cron Mode)" : "Continuous Daemon") . "\n";
        echo "  Interval  : {$this->interval}s\n";
        echo "  Database  : " . DB_TYPE . "\n";
        echo "  External  : " . (EVENT_GRID_API_URL ?: 'Configured in Settings') . "\n";
        echo "========================================================\n\n";

        $this->db->execute("UPDATE settings SET worker_is_running = 1, worker_interval = ?, worker_last_status = 'Running' WHERE id = 1", [$this->interval]);

        do {
            $iterationStart = microtime(true);
            $this->updateHeartbeat('running');

            try {
                $this->executeCycle();
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] ❌ Error in cycle: " . $e->getMessage() . "\n";
                $this->logger->log('worker', 'failed', $e->getMessage());
                $this->updateHeartbeat('error: ' . substr($e->getMessage(), 0, 50));
            }

            if ($this->runOnce || !$this->isRunning) {
                break;
            }

            $elapsed = microtime(true) - $iterationStart;
            $sleepTime = max(1, (int)($this->interval - $elapsed));
            echo "[" . date('Y-m-d H:i:s') . "] ⏳ Waiting {$sleepTime}s until next cycle...\n";
            sleep($sleepTime);

        } while ($this->isRunning);

        $this->updateHeartbeat('completed');
        echo "[" . date('Y-m-d H:i:s') . "] Worker finished.\n";
    }

    private function executeCycle() {
        $now = time();
        $startTimeIso = gmdate('Y-m-d\TH:i:s\Z', $now - 600); // last 10 minutes
        $endTimeIso = gmdate('Y-m-d\TH:i:s\Z', $now + 60);

        echo "[" . date('Y-m-d H:i:s') . "] 🔄 Checking external feed ({$startTimeIso} -> {$endTimeIso})...\n";

        // 1. Pull External Events
        $pulledCount = 0;
        $bucketsCount = 0;

        try {
            $result = DataPuller::run($startTimeIso, $endTimeIso, ['limit' => 100]);
            $pulledCount = $result['processed_events'] ?? 0;
            $bucketsCount = $result['processed_buckets'] ?? 0;
            echo "[" . date('Y-m-d H:i:s') . "] 📥 Ingestion: {$pulledCount} events fetched, {$bucketsCount} buckets updated.\n";
        } catch (\Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] ⚠️ External API unreachable: " . $e->getMessage() . "\n";
            
            // If simulate option is set or no events found, inject a realistic simulated event
            if ($this->simulate) {
                $this->generateSimulatedEvent();
            }
        }

        // 2. Catch up any unaggregated events
        $this->aggregatePendingEvents();

        // 3. Auto-derive larger rollups (5m, 15m, 1h)
        $this->deriveRollupBuckets();

        // 4. Daily Retention Cleanup Check
        if (($now - $this->lastCleanup) > 86400) {
            $this->runRetentionCleanup();
            $this->lastCleanup = $now;
        }

        // 5. Invalidate cache on new data
        if ($pulledCount > 0 || $bucketsCount > 0) {
            Cache::getInstance()->clear();
        }

        $this->updateHeartbeat("Active | Last events: {$pulledCount} | Buckets: {$bucketsCount}");
    }

    private function generateSimulatedEvent() {
        $eventTypes = ['vehicle_movement', 'sensor_alert', 'incident_report', 'zone_entry', 'status_ping'];
        $sources = ['rteg_tracker_01', 'rteg_camera_04', 'rteg_vehicle_34', 'rteg_sensor_09'];
        $regions = ['cell_41.01_28.97', 'cell_41.02_28.98', 'cell_40.99_29.02', 'cell_41.05_28.95'];

        $type = $eventTypes[array_rand($eventTypes)];
        $source = $sources[array_rand($sources)];
        $region = $regions[array_rand($regions)];
        $eventTime = gmdate('Y-m-d H:i:s');
        $extId = 'sim_' . time() . '_' . rand(1000, 9999);

        $payload = json_encode([
            'simulated' => true,
            'source' => 'worker_sim',
            'speed' => rand(20, 110),
            'severity' => rand(1, 4)
        ]);

        $sql = "INSERT INTO events (event_type, source_id, event_time, geo_region, event_data, external_event_id) VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [$type, $source, $eventTime, $region, $payload, $extId]);

        // Aggregate this 1-minute window
        $aggStart = gmdate('Y-m-d\TH:i:00\Z', strtotime($eventTime));
        $aggEnd = gmdate('Y-m-d\TH:i:59\Z', strtotime($eventTime));
        AggregationEngine::getInstance()->aggregateEvents($aggStart, $aggEnd, '1m');

        echo "[" . date('Y-m-d H:i:s') . "] 🤖 Generated 1 simulated event ({$type} from {$source}).\n";
    }

    private function aggregatePendingEvents() {
        // Aggregate the last 15 minutes to guarantee consistency
        $now = time();
        $start = gmdate('Y-m-d\TH:i:00\Z', $now - 900);
        $end = gmdate('Y-m-d\TH:i:59\Z', $now);

        try {
            AggregationEngine::getInstance()->aggregateEvents($start, $end, '1m');
        } catch (\Exception $e) {
            // Silently ignore minor aggregation range glitches
        }
    }

    private function deriveRollupBuckets() {
        $now = time();
        $startDb = gmdate('Y-m-d H:i:s', $now - 7200); // last 2 hours
        $endDb = gmdate('Y-m-d H:i:s', $now);

        try {
            Rollups::derive($startDb, $endDb, '1m', '5m');
            Rollups::derive($startDb, $endDb, '5m', '15m');
            Rollups::derive($startDb, $endDb, '15m', '1h');
        } catch (\Exception $e) {}
    }

    private function runRetentionCleanup() {
        echo "[" . date('Y-m-d H:i:s') . "] 🧹 Running daily retention cleanup...\n";
        try {
            $settings = $this->db->query("SELECT raw_retention_days, agg_retention_days FROM settings LIMIT 1");
            $rawDays = (int)($settings[0]['raw_retention_days'] ?? 60);
            $aggDays = (int)($settings[0]['agg_retention_days'] ?? 365);

            $rawCutoff = date('Y-m-d H:i:s', time() - ($rawDays * 86400));
            $aggCutoff = date('Y-m-d H:i:s', time() - ($aggDays * 86400));

            $this->db->execute("DELETE FROM events WHERE event_time < ?", [$rawCutoff]);
            $this->db->execute("DELETE FROM time_buckets WHERE bucket_end < ?", [$aggCutoff]);

            $this->logger->log('cleanup', 'success', "Retention cleanup: events < {$rawCutoff}, buckets < {$aggCutoff}");
            echo "[" . date('Y-m-d H:i:s') . "] ✓ Retention cleanup completed.\n";
        } catch (\Exception $e) {
            echo "[" . date('Y-m-d H:i:s') . "] ⚠️ Cleanup error: " . $e->getMessage() . "\n";
        }
    }

    private function updateHeartbeat($status) {
        try {
            $this->db->execute("UPDATE settings SET worker_last_beat = CURRENT_TIMESTAMP, worker_last_status = ? WHERE id = 1", [$status]);
        } catch (\Exception $e) {}
    }
}

// Parse CLI arguments
$options = [];
foreach ($argv ?? [] as $arg) {
    if ($arg === '--once') {
        $options['once'] = true;
    } elseif ($arg === '--simulate') {
        $options['simulate'] = true;
    } elseif (strpos($arg, '--interval=') === 0) {
        $options['interval'] = (int)substr($arg, 11);
    }
}

$worker = new Worker($options);
$worker->start();
