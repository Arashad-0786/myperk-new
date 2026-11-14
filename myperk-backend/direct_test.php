<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', 'D:/MyPerk-New/myperk-backend/direct_test.log');

define('ABSPATH', 'D:/MyPerk-New/myperk-core/');
define('MYPERK_VERSION', '1.0.0');
define('OBJECT', 'OBJECT');

require_once 'standalone-minimal.php';

$db = MyPerk_DB_Standalone::get_instance();

// Test 1: Check if customer exists
$sql = "SELECT id FROM loyalty_customers WHERE phone = '+1234567890'";
$result = $db->get_var($sql);
echo "Test 1 - get_var result: " . var_export($result, true) . "\n";

// Test 2: Insert customer
$insert_sql = $db->prefix . 'loyalty_customers';
$columns = array('phone' => '+1234567890', 'full_name' => 'Test User', 'status' => 'active', 'created_at' => '2026-01-01 00:00:00');
$placeholders = array('%s', '%s', '%s', '%s');
$stmt = $db->pdo->prepare("INSERT INTO $insert_sql ($columns) VALUES (?)");  // simplified
// Actually let me use the insert method properly

// Let me just test the query
echo "Test done\n";