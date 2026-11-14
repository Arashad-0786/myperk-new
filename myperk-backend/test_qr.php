<?php
// Test script for QR verify endpoint
require_once __DIR__ . '/standalone-server.php';

// Test the is_wp_error function
echo "Testing is_wp_error function:\n";
$error = new WP_Error('test', 'Test error');
echo "is_wp_error(WP_Error instance): " . (is_wp_error($error) ? 'true' : 'false') . "\n";
echo "is_wp_error(null): " . (is_wp_error(null) ? 'true' : 'false') . "\n";

// Test the QR verify endpoint
echo "\nTesting QR verify endpoint:\n";

// Create a mock request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/myperk/v1/public/qr/verify';
$_SERVER['CONTENT_TYPE'] = 'application/json';
$input = file_get_contents('php://input');
// Override file_get_contents for this test
stream_wrapper_unregister("php");
stream_wrapper_register("php", "MockPhpStream");

// Test the API directly
$request = new WP_REST_Request(['qr_hash' => 'TC001'], [], '{"qr_hash": "TC001"}');
$api = MyPerk_API::get_instance();
$result = $api->verify_qr($request);
echo "Result type: " . gettype($result) . "\n";
if (is_wp_error($result)) {
    echo "Error: " . $result->get_error_message() . "\n";
} else if ($result instanceof WP_REST_Response) {
    echo "Success! Response data: " . json_encode($result->data) . "\n";
} else {
    echo "Response: " . json_encode($result) . "\n";
}

class MockPhpStream {
    public $stream;
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    public function stream_read($count) { return ''; }
    public function stream_eof() { return true; }
    public function stream_stat() { return []; }
}
?>