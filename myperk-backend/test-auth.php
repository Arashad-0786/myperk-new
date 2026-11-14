<?php
/**
 * Test auth header parsing
 */

// Basic WordPress-like constants
define('ABSPATH', __DIR__ . '/myperk-core/');
define('MYPERK_VERSION', '1.0.0');
define('OBJECT', 'OBJECT');

// Include standalone authentication system
require_once __DIR__ . '/standalone-auth.php';

// Minimal getallheaders function
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headers[strtolower(str_replace(' ', '-', substr($key, 5)))] = $value;
            }
        }
        return $headers;
    }
}

// WordPress function stubs
if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = false) {
        $format = $gmt ? 'Y-m-d H:i:s' : 'Y-m-d H:i:s';
        return gmdate($format, time());
    }
}

// Simple test endpoint
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = '/' . ltrim($path, '/');

if ($path === '/test/headers') {
    $headers = getallheaders();
    $headers_normalized = array_change_key_case($headers);
    
    header('Content-Type: application/json');
    echo json_encode([
        'method' => $method,
        'path' => $path,
        'raw_headers' => $headers,
        'normalized_headers' => $headers_normalized,
        'x_myperk_api_key' => $headers_normalized['x-myperk-api-key'] ?? 'NOT FOUND',
        'http_x_myperk_api_key' => $_SERVER['HTTP_X_MYPERK_API_KEY'] ?? 'NOT FOUND',
        'all_server' => array_filter($_SERVER, function($k) { return strpos($k, 'HTTP_') === 0; }, ARRAY_FILTER_USE_KEY)
    ]);
    exit;
}

// Test auth
if ($path === '/test/auth') {
    try {
        $auth = MyPerk_Standalone_Auth::get_instance()->authenticate();
        header('Content-Type: application/json');
        echo json_encode($auth);
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
    }
    exit;
}

http_response_code(404);
echo 'Not Found';