<?php
namespace Temporal;
require_once __DIR__ . '/database_pdo.php';
require_once __DIR__ . '/utils.php';

class Rollups {
    public static function derive($start_time, $end_time, $from_size, $to_size) {
        $db = Database::getInstance();
        $from = Utils::getBucketInterval($from_size);
        $to = Utils::getBucketInterval($to_size);
        if ($to % $from !== 0) { throw new \Exception('Invalid rollup'); }
        $factor = $to / $from;
        $rows = $db->query("SELECT bucket_start, bucket_end, total_events, id FROM time_buckets WHERE bucket_start >= ? AND bucket_end <= ? AND bucket_size = ? ORDER BY bucket_start", [$start_time, $end_time, $from_size]);
        $group = [];
        foreach ($rows as $r) { $group[] = $r; }
        $i = 0;
        while ($i < count($group)) {
            $chunk = array_slice($group, $i, $factor);
            if (count($chunk) < $factor) break;
            $start = $chunk[0]['bucket_start'];
            $end = $chunk[$factor-1]['bucket_end'];
            $sum = 0;
            foreach ($chunk as $c) { $sum += (int)$c['total_events']; }
            $existing = $db->query("SELECT id FROM time_buckets WHERE bucket_start = ? AND bucket_end = ? AND bucket_size = ?", [$start, $end, $to_size]);
            if (empty($existing)) {
                $db->execute("INSERT INTO time_buckets (bucket_start, bucket_end, bucket_size, total_events) VALUES (?, ?, ?, ?)", [$start, $end, $to_size, $sum]);
            } else {
                $db->execute("UPDATE time_buckets SET total_events = ? WHERE id = ?", [$sum, $existing[0]['id']]);
            }
            $i += $factor;
        }
        return true;
    }
}

if (php_sapi_name() === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    if ($argc < 5) { echo "Usage: php derive_rollups.php <start_iso> <end_iso> <from> <to>\n"; exit(1); }
    $ok = Rollups::derive($argv[1], $argv[2], $argv[3], $argv[4]);
    echo json_encode(['ok'=>$ok]) . "\n";
}

