<?php
/**
 * Minimal Database Adapter for MyPerk Standalone Mode
 * 
 * Provides PDO-based database operations with WordPress-style placeholder conversion
 * (%s, %d, %f -> ?, ?, ?) for compatibility with existing API code.
 */

class MyPerk_DB_Standalone {
    private static $instance = null;
    public $pdo;
    public $prefix = '';
    public $insert_id = 0;
    
    private function __construct() {
        $this->pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function insert_id() {
        return $this->insert_id ?? 0;
    }
    
    public function get_row($sql, $param = null, $output = OBJECT) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "MYDB get_row ENTERED: " . substr($sql, 0, 40) . "...\n", FILE_APPEND);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "MYDB get_row ENTERED: " . substr($sql, 0, 40) . "...\n", FILE_APPEND);
        // Convert WordPress-style placeholders (%s, %d, %f) to PDO-style (?)
        $sql = preg_replace('/%s/', '?', $sql);
        $sql = preg_replace('/%d/', '?', $sql);
        $sql = preg_replace('/%f/', '?', $sql);
        // Check if SQL already has values substituted (no placeholders)
        if (strpos($sql, '?') === false) {
            // SQL already prepared, execute directly
            try {
                $stmt = $this->pdo->query($sql);
            } catch (Exception $e) {
                file_put_contents($logfile, "DB DEBUG get_row query exception: " . $e->getMessage() . "\n", FILE_APPEND);
                return null;
            }
        } else {
            // SQL has placeholders, prepare and bind
            $stmt = $this->pdo->prepare($sql);
            if ($param !== null) {
                if (!is_array($param)) {
                    $param = [$param];
                }
                foreach ($param as $i => $val) {
                    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $stmt->execute();
        }
        
        $result = $output === OBJECT ? $stmt->fetch(PDO::FETCH_OBJ) : $stmt->fetch(PDO::FETCH_ASSOC);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG get_row: result=" . json_encode($result) . "\n", FILE_APPEND);
        return $result;
    }
    
    public function get_var($sql, $param = null) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "DB DEBUG get_var called: sql=" . substr($sql, 0, 80) . "... param=" . var_export($param, true) . "\n", FILE_APPEND);
        // Check if SQL already has values substituted (no placeholders)
        if (strpos($sql, '%s') === false && strpos($sql, '%d') === false && strpos($sql, '%f') === false) {
            // SQL already prepared, execute directly
            try {
                $stmt = $this->pdo->query($sql);
            } catch (Exception $e) {
                file_put_contents($logfile, "DB DEBUG get_var query exception: " . $e->getMessage() . "\n", FILE_APPEND);
                return null;
            }
        } else {
            // SQL has placeholders, prepare and bind
            $stmt = $this->pdo->prepare($sql);
            if ($param !== null) {
                if (!is_array($param)) {
                    $param = [$param];
                }
                foreach ($param as $i => $val) {
                    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $stmt->execute();
        }
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result[0] ?? null;
    }
    
    public function get_results($sql, $param = null, $output = OBJECT) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "MYDB get_results ENTERED: " . substr($sql, 0, 40) . "...\n", FILE_APPEND);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "MYDB get_results ENTERED: " . substr($sql, 0, 40) . "...\n", FILE_APPEND);
        // Convert WordPress-style placeholders (%s, %d, %f) to PDO-style (?)
        $sql = preg_replace('/%s/', '?', $sql);
        $sql = preg_replace('/%d/', '?', $sql);
        $sql = preg_replace('/%f/', '?', $sql);
        // Check if SQL already has values substituted (no placeholders)
        if (strpos($sql, '?') === false) {
            // SQL already prepared, execute directly
            try {
                $stmt = $this->pdo->query($sql);
            } catch (Exception $e) {
                file_put_contents($logfile, "DB DEBUG get_results query exception: " . $e->getMessage() . "\n", FILE_APPEND);
                return null;
            }
        } else {
            // SQL has placeholders, prepare and bind
            $stmt = $this->pdo->prepare($sql);
            if ($param !== null) {
                if (!is_array($param)) {
                    $param = [$param];
                }
                foreach ($param as $i => $val) {
                    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $stmt->execute();
        }
        
        $result = $output === OBJECT ? $stmt->fetchAll(PDO::FETCH_OBJ) : $stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG get_results: result=" . json_encode($result) . "\n", FILE_APPEND);
        return $result;
    }
    
    public function insert($table, $data, $format = null) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "DB DEBUG insert ENTERED: table=$table, count=" . count($data) . "\n", FILE_APPEND);
        file_put_contents($logfile, "DB DEBUG pdo exists: " . (isset($this->pdo) ? 'yes' : 'no') . "\n", FILE_APPEND);
        if (isset($this->pdo)) {
            file_put_contents($logfile, "DB DEBUG pdo type: " . get_class($this->pdo) . "\n", FILE_APPEND);
        }
        try {
            file_put_contents($logfile, "DB DEBUG insert try ENTERED\n", FILE_APPEND);
            
            if ($format === null) {
                $format = [];
                foreach ($data as $key => $val) {
                    $format[] = is_int($val) ? '%d' : '%s';
                }
            }
            
            $columns = array_keys($data);
            $values = array_values($data);
            file_put_contents($logfile, "DB DEBUG columns: " . implode(', ', $columns) . "\n", FILE_APPEND);
            file_put_contents($logfile, "DB DEBUG format: " . implode(', ', $format) . "\n", FILE_APPEND);
            
            $placeholders = [];
            foreach ($format as $i => $fmt) {
                $placeholders[] = '?';
            }
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            file_put_contents($logfile, "DB DEBUG sql: $sql\n", FILE_APPEND);
            
            $stmt = $this->pdo->prepare($sql);
            file_put_contents($logfile, "DB DEBUG prepare succeeded\n", FILE_APPEND);
            
            foreach ($values as $i => $val) {
                $param = $val;
                $type = $format[$i] === '%d' ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($i + 1, $param, $type);
            }
            
            $stmt->execute();
            $this->insert_id = $this->pdo->lastInsertId();
            file_put_contents($logfile, "DB DEBUG execute succeeded, insert_id=$this->insert_id\n", FILE_APPEND);
            return $this->insert_id;
        } catch (Exception $e) {
            file_put_contents($logfile, "DB DEBUG insert exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return 0;
        }
    }
    
    private function prepare_placeholder($type) {
        return '?';
    }
    
    public function prepare($sql, ...$args) {
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG prepare: sql=$sql, args=" . json_encode($args) . "\n", FILE_APPEND);
        if (empty($args)) {
            return $sql;
        }
        $params = $args[0] ?? [];
        if (!is_array($params)) {
            $params = array_slice($args, 0);
        }
        
        $i = 0;
        $result = preg_replace_callback('/%s|%d|%f/', function($matches) use (&$i, &$params) {
            if (!isset($params[$i])) return '%s';
            $val = $params[$i++];
            if ($matches[0] === '%d') return intval($val);
            if ($matches[0] === '%f') return floatval($val);
            return "'" . addslashes($val) . "'";
        }, $sql);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG prepare result: $result\n", FILE_APPEND);
        return $result;
    }
    
    public function query($sql, $param = null) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "DB DEBUG query ENTERED: " . substr($sql, 0, 80) . "...\n", FILE_APPEND);
        
        // Convert WordPress-style placeholders to PDO-style
        $sql = preg_replace('/%s/', '?', $sql);
        $sql = preg_replace('/%d/', '?', $sql);
        $sql = preg_replace('/%f/', '?', $sql);
        
        // Check if SQL already has values substituted (no placeholders)
        if (strpos($sql, '?') === false) {
            try {
                $this->pdo->query($sql);
                return true;
            } catch (Exception $e) {
                file_put_contents($logfile, "DB DEBUG query exception: " . $e->getMessage() . "\n", FILE_APPEND);
                return false;
            }
        } else {
            $stmt = $this->pdo->prepare($sql);
            if ($param !== null) {
                if (!is_array($param)) {
                    $param = [$param];
                }
                foreach ($param as $i => $val) {
                    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            return $stmt->execute();
        }
    }
    
    public function update($table, $data, $where, $format = null, $where_format = null) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "DB DEBUG update ENTERED: table=$table\n", FILE_APPEND);
        
        try {
            $columns = array_keys($data);
            $values = array_values($data);
            $where_cols = array_keys($where);
            $where_vals = array_values($where);
            
            $set_parts = [];
            foreach ($columns as $col) {
                $set_parts[] = "$col = ?";
            }
            $where_parts = [];
            foreach ($where_cols as $col) {
                $where_parts[] = "$col = ?";
            }
            
            $sql = "UPDATE $table SET " . implode(', ', $set_parts) . " WHERE " . implode(' AND ', $where_parts);
            file_put_contents($logfile, "DB DEBUG update sql: $sql\n", FILE_APPEND);
            
            $stmt = $this->pdo->prepare($sql);
            
            $all_values = array_merge($values, $where_vals);
            foreach ($all_values as $i => $val) {
                $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            file_put_contents($logfile, "DB DEBUG update exception: " . $e->getMessage() . "\n", FILE_APPEND);
            return 0;
        }
    }
}