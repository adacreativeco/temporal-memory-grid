<?php
namespace Temporal;
require_once __DIR__ . '/config.php';

class Cache {
    private static $instance = null;
    
    private function __construct() {}
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($key) {
        if (!\CACHE_ENABLED) {
            return false;
        }
        
        $filename = \CACHE_DIR . md5($key) . '.cache';
        
        if (!file_exists($filename)) {
            return false;
        }
        
        $data = json_decode(file_get_contents($filename), true);
        
        if ($data === null || !isset($data['timestamp']) || !isset($data['data'])) {
            return false;
        }
        
        if (time() - $data['timestamp'] > \CACHE_TTL) {
            unlink($filename);
            return false;
        }
        
        return $data['data'];
    }
    
    public function set($key, $data) {
        if (!\CACHE_ENABLED) {
            return false;
        }
        
        $filename = \CACHE_DIR . md5($key) . '.cache';
        
        $cache_data = [
            'timestamp' => time(),
            'data' => $data
        ];
        
        return file_put_contents($filename, json_encode($cache_data));
    }
    
    public function clear() {
        $files = glob(\CACHE_DIR . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    public function delete($key) {
        $filename = \CACHE_DIR . md5($key) . '.cache';
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }
}
