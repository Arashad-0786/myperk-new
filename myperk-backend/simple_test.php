<?php
// Simulate a request to test the routing

// Bootstrap the router and API
require_once __DIR__ . '/standalone-minimal.php';

// Test the get_pending_requests function directly
echo "Testing MyPerk_Loyalty::get_pending_requests...\n";

try {
    $requests = MyPerk_Loyalty::get_pending_requests(1, null, 10);
    echo "Success! Found " . count($requests) . " requests\n";
    print_r($requests);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}