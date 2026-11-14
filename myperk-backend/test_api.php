<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '0');

// Start PHP built-in server in background
$server = proc_open(
    ['php', '-S', 'localhost:8080', '-t', 'D:/MyPerk-New/myperk-backend'],
    [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes
);

// Wait for server to start
sleep(3);

// Helper function to make HTTP requests
function make_request($method, $url, $body = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080' . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER([
            'Content-Type: application/json',
        ]));
    }
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $http_code, 'body' => $result];
}

// Test 1: GET customer/cards?customer_id=3
echo "=== TEST 1: GET customer/cards?customer_id=3 ===\n";
$result = make_request('GET', '/myperk/v1/customer/cards?customer_id=3');
echo "Status: " . $result['code'];
echo "Body: " . $result['body'] . "\n\n";

// Test 2: POST customer/stamp-request
echo "=== TEST 2: POST customer/stamp-request ===\n";
$result = make_request('POST', '/myperk/v1/customer/stamp-request', [
    'business_id' => 1,
    'branch_id' => 1,
    'phone' => '+1234567891',
    'qr_hash' => 'TC001'
]);
echo "Status: " . $result['code'];
echo "Body: " . $result['body'] . "\n\n";

// Test 3: GET staff/requests
echo "=== TEST 3: GET staff/requests ===\n";
$result = make_request('GET', '/myperk/v1/staff/requests');
echo "Status: " . $result['code'];
echo "Body: " . $result['body'] . "\n\n";

// Test 4: GET business/1/dashboard
echo "=== TEST 4: GET business/1/dashboard ===\n";
$result = make_request('GET', '/myperk/v1/business/1/dashboard');
echo "Status: " . $result['code'];
echo "Body: " . $result['body'] . "\n\n";

// Stop the server
proc_close($server);
?>