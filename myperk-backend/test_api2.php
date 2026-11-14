<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start PHP built-in server
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
fwrite($pipes[0], ""); // just to make sure it's open
fclose($pipes[0]);
sleep(3);

// Use file_get_contents with custom stream context to make requests
function api_request($method, $url, $body = null) {
    $options = [
        'http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\n",
            'ignore_errors' => true,
        ],
    ];
    
    if ($body !== null) {
        $options['http']['content'] = json_encode($body);
        $options['http']['method'] = 'POST';
    }
    
    $context = stream_context_create($options);
    $result = file_get_contents("http://localhost:8080$url", false, $context);
    $http_code = -1;
    
    // Get HTTP code
    if (function_exists('apache_request_headers')) {
        // Can't easily get code from file_get_contents
    }
    
    return ['code' => $http_code, 'body' => $result];
}

// Make requests
echo "TEST 1: GET customer/cards?customer_id=3\n";
$result = api_request('GET', '/myperk/v1/customer/cards?customer_id=3');
echo "Status: " . $result['code'] . "\n";
echo "Body: " . $result['body'] . "\n\n";

echo "TEST 2: POST customer/stamp-request\n";
$result = api_request('POST', '/myperk/v1/customer/stamp-request', [
    'business_id' => 1,
    'branch_id' => 1,
    'phone' => '+1234567891',
    'qr_hash' => 'TC001'
]);
echo "Status: " . $result['code'] . "\n";
echo "Body: " . $result['body'] . "\n\n";

echo "TEST 3: GET staff/requests\n";
$result = api_request('GET', '/myperk/v1/staff/requests');
echo "Status: " . $result['code'] . "\n";
echo "Body: " . $result['body'] . "\n\n";

echo "TEST 4: GET business/1/dashboard\n";
$result = api_request('GET', '/myperk/v1/business/1/dashboard');
echo "Status: " . $result['code'] . "\n";
echo "Body: " . $result['body'] . "\n\n";

proc_close($server);
echo "\nDone.\n";