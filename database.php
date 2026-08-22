<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        if (DB_TYPE === 'mysql') {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($this->connection->connect_error) {
                die("Connection failed: " . $this->connection->connect_error);
            }
            $this->connection->set_charset(DB_CHARSET);
        } else {
            // SQLite fallback
            $this->connection = new SQLite3(__DIR__ . '/temporal_memory_grid.db');
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        if (DB_TYPE === 'mysql') {
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            // SQLite fallback
            if (!empty($params)) {
                $stmt = $this->connection->prepare($sql);
                foreach ($params as $i => $param) {
                    $stmt->bindValue($i + 1, $param);
                }
                $res = $stmt->execute();
                $result = [];
                if ($res) {
                    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                        $result[] = $row;
                    }
                }
                return $result;
            } else {
                $res = $this->connection->query($sql);
                $rows = [];
                if ($res) {
                    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                        $rows[] = $row;
                    }
                }
                return $rows;
            }
        }
    }
    
    public function execute($sql, $params = []) {
        if (DB_TYPE === 'mysql') {
            $stmt = $this->connection->prepare($sql);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
            }
            
            return $stmt->execute();
        } else {
            // SQLite fallback
            $stmt = $this->connection->prepare($sql);
            if (!empty($params)) {
                foreach ($params as $i => $param) {
                    $stmt->bindValue($i + 1, $param);
                }
            }
            return $stmt->execute();
        }
    }
    
    public function lastInsertId() {
        if (DB_TYPE === 'mysql') {
            return $this->connection->insert_id;
        } else {
            return $this->connection->lastInsertRowID();
        }
    }
}
