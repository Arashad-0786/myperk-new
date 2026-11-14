<?php
// Manual test of the router and request handler

// Simulate a request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/myperk/v1/staff/requests?business_id=1';

// Include the necessary files
define('ABSPATH', __DIR__ . '/myperk-core/');
define('MYPERK_VERSION', '1.0.0');
define('OBJECT', 'OBJECT');

// Set up headers for the test
$_SERVER['HTTP_X_MYPERK_API_KEY'] = 'bk_myperk_business_1';

// Include auth
require_once __DIR__ . '/standalone-auth.php';

// Include router
require_once __DIR__ . '/myperk-core/includes/class-router.php';

// Create router and register routes
$router = new MyPerk_Router();

// Register some test routes
$router->add_route('GET', '/myperk/v1/test', function() {
    return (object)[
        'status' => 200,
        'data' => ['message' => 'test route works'],
        'headers' => ['Content-Type' => 'application/json']
    ];
});

// Dispatch
echo "Dispatching test...\n";
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = '/' . ltrim($path, '/');
$body = '';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $response = $router->dispatch($method, $path, $body, $_SERVER);
    http_response_code($response->status);
    foreach ($response->headers as $key => $value) {
        header("$key: $value");
    }
    echo json_encode($response->data);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}