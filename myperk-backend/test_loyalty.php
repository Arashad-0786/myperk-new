<?php
// Direct test of MyPerk_Loyalty::get_pending_requests

// Mock WordPress globals
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = ''; // Empty prefix - tables already have loyalty_ prefix

// Create PDO connection
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=perk_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!\n\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Create a stand-in $wpdb object with PDO
class TestDB {
    public $prefix = '';
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function prepare($sql, ...$args) {
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
        return $result;
    }
    
    public function get_results($sql) {
        echo "get_results SQL: " . substr($sql, 0, 120) . "...\n";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}

$wpdb = new TestDB($pdo);

// Now test the method
$sql = "SELECT sr.id, sr.customer_id, c.full_name, sr.requested_stamps, sr.status, sr.qr_hash, sr.created_at
        FROM {$wpdb->prefix}loyalty_stamp_requests sr
        JOIN {$wpdb->prefix}loyalty_customers c ON c.id = sr.customer_id
        WHERE sr.business_id = %d AND sr.status = 'pending'";

$sql .= " ORDER BY sr.created_at DESC LIMIT 10";

echo "Executing query...\n";
$prepared_sql = $wpdb->prepare($sql, 1);
echo "Prepared SQL: " . substr($prepared_sql, 0, 120) . "...\n";

$requests = $wpdb->get_results($prepared_sql);
echo "\nFound " . count($requests) . " requests\n";

if (count($requests) > 0) {
    echo "\nFirst request:\n";
    print_r($requests[0]);
}