<?php
// Test loyalty engine directly
// Bypass ABSPATH check for standalone mode
define('ABSPATH', '');

// Include the standalone server which has all the classes
require_once 'standalone-minimal.php';

// Set up $wpdb - get the standalone DB instance
global $wpdb;
$wpdb = MyPerk_DB_Standalone::get_instance();
$wpdb->prefix = 'loyalty_';

// Test 1: Check if card exists
echo "Test 1: Check card for customer 4, business 1\n";
$card = $wpdb->get_row($wpdb->prepare(
    "SELECT id, stamp_count FROM loyalty_cards WHERE business_id = %d AND customer_id = %d AND status = 'active'",
    1, 4
));
echo "Card: " . json_encode($card) . "\n";

if ($card) {
    // Test 2: Check existing pending requests
    echo "Test 2: Check existing pending requests\n";
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM loyalty_stamp_requests WHERE business_id = %d AND customer_id = %d AND status = 'pending' AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
        1, 4
    ));
    echo "Existing: " . ($existing ? "found id=$existing" : "none") . "\n";
    
    // Test 3: Check daily limit
    echo "Test 3: Check daily limit\n";
    $daily_requests = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM loyalty_stamp_requests WHERE customer_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
        4
    ));
    echo "Daily requests: $daily_requests\n";
    
    // Test 4: Try insert
    echo "Test 4: Try insert stamp request\n";
    $result = $wpdb->insert(
        'loyalty_stamp_requests',
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
    
    // Test 5: Call loyalty engine create_stamp_request
    echo "Test 5: Call MyPerk_Loyalty::create_stamp_request\n";
    $result = MyPerk_Loyalty::create_stamp_request([
        "business_id" => 1,
        "branch_id" => 1,
        "customer_id" => 4,
        "qr_hash" => "TC001",
        "requested_stamps" => 1
    ]);
    echo "Result type: " . gettype($result) . "\n";
    echo "Result: " . json_encode($result) . "\n";
    echo "is_wp_error: " . (is_wp_error($result) ? 'true' : 'false') . "\n";
}