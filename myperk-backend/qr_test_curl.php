<?php
/**
 * Test QR verify endpoint using curl functions
 */

// Test QR verify endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/myperk/v1/public/qr/verify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['qr_hash' => 'TC001']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Error: $error\n";
echo "Raw Response:\n";
var_dump($response);

$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
} else {
    echo "Decoded Response:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
}

// Test with API key (super_admin)
echo "\n\nTesting with super_admin API key:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8080/myperk/v1/public/qr/verify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['qr_hash' => 'TC001']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'X-MyPerk-API-Key: sk_myperk_super_admin_123'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Error: $error\n";
echo "Raw Response:\n";
var_dump($response);

$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
} else {
    echo "Decoded Response:\n";
    echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
}
?>