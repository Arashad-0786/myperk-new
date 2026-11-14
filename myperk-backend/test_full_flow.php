<?php
// Full flow test - simulate a request

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Mock $_SERVER
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/myperk/v1/staff/requests?business_id=1';

// Include the necessary files
define('ABSPATH', __DIR__ . '/myperk-core/');
define('MYPERK_VERSION', '1.0.0');
define('OBJECT', 'OBJECT');

// Set up header function stub
if (!function_exists('header')) {
    function header($header) {
        echo "[HEADER] $header\n";
    }
}

// Load the auth system
require_once __DIR__ . '/standalone-auth.php';

// Mock getallheaders
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
            }
        }
        return $headers;
    }
}

// Set up the API key
$_SERVER['HTTP_X_MYPERK_API_KEY'] = 'bk_myperk_business_1';

// Now try to get the API and test
require_once __DIR__ . '/myperk-core/includes/class-router.php';
require_once __DIR__ . '/myperk-core/includes/class-loyalty.php';
require_once __DIR__ . '/myperk-core/includes/class-api.php';

echo "Classes loaded successfully\n";

// Try to get the API instance
try {
    $api = MyPerk_API::get_instance();
    echo "API instance created\n";
    
    $api->register_routes();
    echo "Routes registered\n";
    
    // Get the router
    $router = new MyPerk_Router();
    echo "Router created\n";
    
    // List routes
    $routes = $router->get_routes('GET');
    echo "Routes: " . count($routes) . "\n";
    foreach ($routes as $route) {
        echo "  - $route[0]\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}