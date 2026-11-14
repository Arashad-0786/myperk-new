<?php
require_once __DIR__ . '/myperk-backend/myperk-core/includes/class-db.php';
define('OBJECT', 'OBJECT');

$db = MyPerk_DB_Standalone::get_instance();
$businesses = $db->get_results("SELECT id, business_name, slug FROM loyalty_businesses");
echo "Businesses: " . json_encode($businesses, JSON_PRETTY_PRINT) . "\n";

$branches = $db->get_results("SELECT id, business_id, branch_name, branch_code FROM loyalty_branches");
echo "Branches: " . json_encode($branches, JSON_PRETTY_PRINT) . "\n";

$customers = $db->get_results("SELECT id, phone, full_name FROM loyalty_customers");
echo "Customers: " . json_encode($customers, JSON_PRETTY_PRINT) . "\n";