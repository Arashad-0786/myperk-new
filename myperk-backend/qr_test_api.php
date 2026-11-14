<?php
/**
 * Test script for QR verify endpoint using socket
 */

$host = 'localhost';
$port = 8080;

// Create socket connection
$socket = fsockopen($host, $port, $errno, $errstr, 5);
if (!$socket) {
    echo "Failed to connect: $errstr ($errno)\n";
    exit(1);
}

// Prepare POST request
$request = "POST /myperk/v1/public/qr/verify HTTP/1.1\r\n";
$request .= "Host: localhost:8080\r\n";
$request .= "Content-Type: application/json\r\n";
$request .= "Content-Length: 17\r\n";
$request .= "Connection: close\r\n";
$request .= "\r\n";
$request .= '{"qr_hash":"TC001"}';

fwrite($socket, $request);

// Read response
$response = '';
while (!feof($socket)) {
    $response .= fread($socket, 4096);
}
fclose($socket);

// Parse response
$parts = explode("\r\n\r\n", $response, 2);
$body = $parts[1] ?? '';

echo "Response:\n";
echo "Status: " . ($parts[0] ?? '') . "\n";
echo "Body: " . $body . "\n";

$data = json_decode($body, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "\nJSON decode error: " . json_last_error_msg() . "\n";
} else {
    echo "\nParsed JSON:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}