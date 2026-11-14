<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Starting test...\n";

// Minimal stubs
if (!function_exists("current_time")) {
    function current_time($format = "", $gmt = false) { return date("Y-m-d H:i:s", time()); }
}
if (!function_exists("sanitize_text_field")) {
    function sanitize_text_field($str) { return $str; }
}

// Include DB class
include "myperk-core/includes/class-db.php";

echo "DB class included\n";

// Set up $wpdb
$wpdb = MyPerk_DB_Standalone::get_instance();
$wpdb->prefix = "";

echo "WPdb instance created\n";

// Test insert
$result = $wpdb->insert(
    $wpdb->prefix . 'loyalty_stamp_requests',
    array(
        'business_id' => 1,
        'branch_id' => 1,
        'customer_id' => 4,
        'requested_stamps' => 1,
        'status' => 'pending',
        'qr_hash' => 'TC001',
        'created_at' => date('Y-m-d H:i:s'),
    ),
    array('%d', '%d', '%d', '%d', '%s', '%s', '%s')
);

echo "Insert result: " . ($result ? "true" : "false") . "\n";
echo "Insert ID: " . ($wpdb->insert_id ? $wpdb->insert_id : "null") . "\n";