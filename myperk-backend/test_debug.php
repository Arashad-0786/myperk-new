<?php
// Minimal stubs
if (!function_exists("current_time")) {
    function current_time($format = "", $gmt = false) { return date("Y-m-d H:i:s", time()); }
}
if (!function_exists("sanitize_text_field")) {
    function sanitize_text_field($str) { return $str; }
}

// Include DB class
include "myperk-core/includes/class-db.php";

// Set up $wpdb
$wpdb = MyPerk_DB_Standalone::get_instance();
$wpdb->prefix = "";

// Include loyalty class
include "myperk-core/includes/class-loyalty.php";

// Test 1: Direct call
$result1 = MyPerk_Loyalty::create_stamp_request([
    "business_id" => 1,
    "branch_id" => 1,
    "customer_id" => 4,
    "qr_hash" => "TC001",
    "requested_stamps" => 1
]);

echo "Test 1 - Direct call:\n";
echo "Result type: " . gettype($result1) . "\n";
echo "Result: " . json_encode($result1) . "\n";
echo "is_wp_error: " . (is_wp_error($result1) ? 'true' : 'false') . "\n\n";

// Test 2: Check if $wpdb->insert works
$wpdb->insert(
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
echo "Test 2 - insert result: " . ($wpdb->insert_id ? "success (id=" . $wpdb->insert_id . ")" : "failed") . "\n";

// Test 3: Check existing requests
$existing = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM loyalty_stamp_requests WHERE business_id = %d AND customer_id = %d AND status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
    1, 4
));
echo "Test 3 - existing request: " . ($existing ? "found id=$existing" : "none") . "\n";

$daily_requests = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM loyalty_stamp_requests WHERE customer_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
    4
));
echo "Test 4 - daily requests: $daily_requests\n";