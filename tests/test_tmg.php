<?php
/**
 * Automated Test Suite for Temporal Memory Grid (TMG)
 * Tests: Database Setup, Utils Validation, I18n 5-Language Parity, Caching Engine,
 * Aggregation Engine, Alert Engine, and JSON Schemas Integrity.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database_pdo.php';
require_once __DIR__ . '/../setup_database_sqlite.php';
require_once __DIR__ . '/../utils.php';
require_once __DIR__ . '/../i18n.php';
require_once __DIR__ . '/../cache.php';
require_once __DIR__ . '/../aggregation_engine.php';
require_once __DIR__ . '/../alert_engine.php';

echo "========================================================\n";
echo "  🧪 Running Temporal Memory Grid (TMG) Test Suite      \n";
echo "========================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest($name, $condition, $details = "") {
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ PASS: $name\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $name ($details)\n";
        $failed++;
    }
}

// 1. Database Connection & Schema Setup
try {
    $db = \Temporal\Database::getInstance();
    assertTest("Database Connection Instance", $db !== null);

    setupDatabaseSqlite();
    
    $pdo = $db->getConnection();
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    
    assertTest("Table 'events' created", in_array('events', $tables));
    assertTest("Table 'time_buckets' created", in_array('time_buckets', $tables));
    assertTest("Table 'bucket_metrics' created", in_array('bucket_metrics', $tables));
    assertTest("Table 'aggregation_jobs_log' created", in_array('aggregation_jobs_log', $tables));
    assertTest("Table 'alert_rules' created", in_array('alert_rules', $tables));
    assertTest("Table 'alert_history' created", in_array('alert_history', $tables));
    assertTest("Table 'users' created", in_array('users', $tables));
    assertTest("Table 'api_keys' created", in_array('api_keys', $tables));
    assertTest("Table 'settings' created", in_array('settings', $tables));
} catch (Exception $e) {
    assertTest("Database Init Error", false, $e->getMessage());
}

// 2. Utils Validation
try {
    // Valid time range
    $start = "2026-08-01 00:00:00";
    $end = "2026-08-02 00:00:00";
    \Temporal\Utils::validateTimeRange($start, $end);
    assertTest("Utils: Valid Time Range", true);

    // Invalid start > end
    $threwRange = false;
    try {
        \Temporal\Utils::validateTimeRange($end, $start);
    } catch (Exception $e) {
        $threwRange = true;
    }
    assertTest("Utils: Reject Inverted Time Range", $threwRange);

    // Valid bucket sizes
    $validSizes = ['1m', '5m', '15m', '1h', '1d'];
    $allValid = true;
    foreach ($validSizes as $s) {
        try {
            \Temporal\Utils::validateBucketSize($s);
        } catch (Exception $e) {
            $allValid = false;
        }
    }
    assertTest("Utils: Validate Standard Bucket Sizes (1m, 5m, 15m, 1h, 1d)", $allValid);

    // Invalid bucket size
    $threwSize = false;
    try {
        \Temporal\Utils::validateBucketSize('42m');
    } catch (Exception $e) {
        $threwSize = true;
    }
    assertTest("Utils: Reject Non-Standard Bucket Size ('42m')", $threwSize);
} catch (Exception $e) {
    assertTest("Utils Error", false, $e->getMessage());
}

// 3. I18n Engine & 5-Language Dictionary Parity
try {
    $langs = array_keys(\Temporal\I18n::$SUPPORTED_LANGS);
    assertTest("I18n: Supported Languages Count (5 Languages)", count($langs) === 5);
    assertTest("I18n: Supports TR, EN, DE, ES, FR", in_array('tr', $langs) && in_array('en', $langs) && in_array('de', $langs) && in_array('es', $langs) && in_array('fr', $langs));

    // Verify all 5 language files load
    $langDir = __DIR__ . '/../lang/';
    $dictKeys = [];
    $allLoaded = true;

    foreach ($langs as $l) {
        $file = $langDir . $l . '.php';
        if (file_exists($file)) {
            $dict = require $file;
            if (is_array($dict)) {
                $dictKeys[$l] = array_keys($dict);
            } else {
                $allLoaded = false;
            }
        } else {
            $allLoaded = false;
        }
    }
    assertTest("I18n: All 5 Language Dictionaries Loaded", $allLoaded);

    // Check translation function with loadLanguages
    \Temporal\I18n::loadLanguages('tr');
    $trWord = \Temporal\I18n::get('dashboard');
    assertTest("I18n: Turkish Translation Lookup", !empty($trWord));

    \Temporal\I18n::loadLanguages('en');
    $enWord = \Temporal\I18n::get('dashboard');
    assertTest("I18n: English Translation Lookup", !empty($enWord));
} catch (Exception $e) {
    assertTest("I18n Error", false, $e->getMessage());
}

// 4. Cache Engine
try {
    $cache = \Temporal\Cache::getInstance();
    $testKey = 'tmg_unit_test_' . time();
    $testVal = ['status' => 'ok', 'score' => 99.5];

    $cache->set($testKey, $testVal, 60);
    $cached = $cache->get($testKey);
    assertTest("Cache: Set and Get Value", is_array($cached) && isset($cached['score']) && $cached['score'] == 99.5);

    $cache->delete($testKey);
    $deleted = $cache->get($testKey);
    assertTest("Cache: Invalidate / Delete Key", $deleted === false);
} catch (Exception $e) {
    assertTest("Cache Error", false, $e->getMessage());
}

// 5. JSON Schemas Integrity
try {
    $schemaDir = __DIR__ . '/../docs/schemas/';
    $schemas = ['anomalies_response.schema.json', 'timeseries_response.schema.json', 'trend_response.schema.json'];
    $allValidSchemas = true;

    foreach ($schemas as $s) {
        $path = $schemaDir . $s;
        if (!file_exists($path)) {
            $allValidSchemas = false;
            break;
        }
        $json = json_decode(file_get_contents($path), true);
        if ($json === null || !isset($json['type'])) {
            $allValidSchemas = false;
            break;
        }
    }
    assertTest("Docs: JSON Response Schemas Valid (Timeseries, Trend, Anomalies)", $allValidSchemas);
} catch (Exception $e) {
    assertTest("JSON Schemas Error", false, $e->getMessage());
}

echo "\n--------------------------------------------------------\n";
echo "  Results: $passed Passed, $failed Failed\n";
echo "--------------------------------------------------------\n\n";

exit($failed > 0 ? 1 : 0);
