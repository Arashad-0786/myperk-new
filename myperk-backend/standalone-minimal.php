<?php
/**
 * Minimal standalone MyPerk server
 * Only the essentials to serve myperk/v1 endpoints
 */

// Catch-all error handler - MUST be first before any other code
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    file_put_contents('D:\\MyPerk-New\\myperk-backend\\error_debug.log', "ERROR [$errno]: $errstr in $errfile on line $errline\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $errstr, 'file' => $errfile, 'line' => $errline]);
    exit(1);
});

// Register shutdown function to catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\error_debug.log', "FATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n", FILE_APPEND);
    }
});

// IMMEDIATE DEBUG - write to a known location (now after error handler)
$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$uri = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';
file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "REQUEST RECEIVED: $method $uri\n", FILE_APPEND);
error_log("INIT: standalone-minimal.php loading...");
file_put_contents('D:\\MyPerk-New\\myperk-backend\\init_debug.log', "INIT: standalone-minimal.php loaded\n", FILE_APPEND);
// Register auto_prepend_file handler to see all requests
ini_set('auto_prepend_file', '');

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

// WordPress function stubs
if (!function_exists('register_rest_route')) {
    function register_rest_route($namespace, $route, $args = []) {
        global $router;
        $path = '/' . ltrim($namespace . $route, '/');
        $method = $args['methods'] ?? 'GET';
        if (is_array($method)) {
            foreach ($method as $m) {
                $router->add_route($m, $path, $args['callback']);
            }
        } else {
            $router->add_route($method, $path, $args['callback']);
        }
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return is_string($str) ? trim(strip_tags($str)) : $str;
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return is_string($str) ? trim(strip_tags($str)) : $str;
    }
}

if (!function_exists('__return_true')) {
    function __return_true() { return true; }
}

if (!function_exists('__return_false')) {
    function __return_false() { return false; }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) { return json_encode($data); }
}

// WP_Error class
if (!class_exists('WP_Error')) {
    class WP_Error {
        private $errors = [];
        private $error_data = [];
        
        public function __construct($code = '', $message = '', $data = '') {
            if ($code) {
                $this->errors[$code][] = $message;
                if ($data) $this->error_data[$code] = $data;
            }
        }
        
        public function add($code, $message, $data = '') {
            $this->errors[$code][] = $message;
            if ($data) $this->error_data[$code] = $data;
        }
        
        public function get_error_code() {
            return !empty($this->errors) ? array_key_first($this->errors) : '';
        }
        
        public function get_error_message($code = '') {
            if (!$code) $code = $this->get_error_code();
            return $this->errors[$code][0] ?? '';
        }
        
        public function get_error_data($code = '') {
            if (!$code) $code = $this->get_error_code();
            return $this->error_data[$code] ?? null;
        }
    }
}

// is_wp_error function
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

// WordPress action/filter stubs
$wp_filter = [];
function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
    global $wp_filter;
    $wp_filter[$hook][] = ['callback' => $callback, 'priority' => $priority, 'args' => $accepted_args];
}

function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
    global $wp_filter;
    $wp_filter[$hook][] = ['callback' => $callback, 'priority' => $priority, 'args' => $accepted_args];
}

function do_action($hook, ...$args) {
    global $wp_filter;
    if (isset($wp_filter[$hook])) {
        foreach ($wp_filter[$hook] as $filter) {
            call_user_func_array($filter['callback'], $args);
        }
    }
}

function apply_filters($hook, $value, ...$args) {
    global $wp_filter;
    if (isset($wp_filter[$hook])) {
        foreach ($wp_filter[$hook] as $filter) {
            $value = call_user_func_array($filter['callback'], array_merge([$value], $args));
        }
    }
    return $value;
}

// Minimal router class
class MyPerk_Router {
    public $routes = [];
    
    public function add_route($method, $path, $callback) {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'callback' => $callback
        ];
    }
    
    public function dispatch($method, $path, $body = '', $headers = []) {
        $method = strtoupper($method);
        
        file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: method=$method, path=$path, routes count=" . count($this->routes) . "\n", FILE_APPEND);
        
        foreach ($this->routes as $route) {
            file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: checking route method={$route['method']} path={$route['path']}\n", FILE_APPEND);
            if ($route['method'] !== $method) continue;
            
            // Convert {param} placeholders to named regex groups, but leave existing regex patterns as-is
            $route_path = $route['path'];
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route_path);
            // If the route already contains regex patterns like (?P<id>\d+), use it as-is
            if (preg_match('/\(\?P<\w+>\)/', $route_path)) {
                $pattern = '#^' . $route_path . '$#';
            } else {
                $pattern = '#^' . $pattern . '$#';
            }
            
            file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: pattern=$pattern\n", FILE_APPEND);
            
            if (preg_match($pattern, $path, $matches)) {
                file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: MATCHED! matches=" . print_r($matches, true) . "\n", FILE_APPEND);
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }
                
                file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: raw_body=" . var_export($body, true) . "\n", FILE_APPEND);
                $json_params = json_decode($body, true);
                $je = json_last_error_msg();
                file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: json_decode result=" . var_export($json_params, true) . " json_last_error=" . ($je ?: 'No error') . "\n", FILE_APPEND);
                if (!is_array($json_params)) {
                    $json_params = [];
                }
                
                $all_params = array_merge($params, $_GET, $json_params);
                
                $request = new WP_REST_Request($all_params, $headers, $body);
                
                try {
                    file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: calling callback, callback=" . gettype($route['callback']) . " " . json_encode($route['callback']) . "\n", FILE_APPEND);
                    $result = call_user_func($route['callback'], $request);
                    file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: callback returned type=" . gettype($result) . " value=" . json_encode($result) . " is_wp_error=" . (is_wp_error($result) ? 'true' : 'false') . " is_wp_rest_response=" . ($result instanceof WP_REST_Response ? 'true' : 'false') . "\n", FILE_APPEND);
                    
                    if ($result instanceof WP_Error) {
                        return new WP_REST_Response(
                            ['success' => false, 'message' => $result->get_error_message()],
                            $result->get_error_data('status') ?? 400
                        );
                    }
                    
                    if ($result instanceof WP_REST_Response) {
                        file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: returning WP_REST_Response directly\n", FILE_APPEND);
                        return $result;
                    }
                    
                    file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: wrapping plain result in WP_REST_Response\n", FILE_APPEND);
                    return new WP_REST_Response($result, 200);
                } catch (Exception $e) {
                    file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER DEBUG: EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
                    http_response_code(500);
                    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    exit;
                }
            }
        }
        
        return new WP_REST_Response(['success' => false, 'message' => 'Route not found'], 404);
    }
}

// Minimal WP_REST_Request
class WP_REST_Request {
    public $params;
    public $headers;
    public $body;
    
    public function __construct($params = [], $headers = [], $body = '') {
        $this->params = $params;
        $this->headers = $headers;
        $this->body = $body;
    }
    
    public function get_param($key) {
        return $this->params[$key] ?? null;
    }
    
    public function get_params() {
        return $this->params;
    }
    
    public function get_json_params() {
        return json_decode($this->body, true) ?? [];
    }
}

// Minimal WP_REST_Response
class WP_REST_Response {
    public $data;
    public $status;
    public $headers = [];
    
    public function __construct($data = null, $status = 200, $headers = []) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }
}

// Minimal MyPerk_DB_Standalone
class MyPerk_DB_Standalone {
    private static $instance = null;
    public $pdo;
    public $prefix = '';
    public $insert_id = 0;
    
    private function __construct() {
        $this->pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function insert_id() {
        return $this->insert_id ?? 0;
    }
    
    public function get_row($sql, $param = null, $output = OBJECT) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "MYDB get_row ENTERED: " . substr($sql, 0, 40) . "...\n", FILE_APPEND);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "MYDB get_row ENTERED: " . substr($sql, 0, 40) . "...\n", FILE_APPEND);
        // Convert WordPress-style placeholders (%s, %d, %f) to PDO-style (?)
        $sql = preg_replace('/%s/', '?', $sql);
        $sql = preg_replace('/%d/', '?', $sql);
        $sql = preg_replace('/%f/', '?', $sql);
        // Check if SQL already has values substituted (no placeholders)
        if (strpos($sql, '?') === false) {
            // SQL already prepared, execute directly
            try {
                $stmt = $this->pdo->query($sql);
            } catch (Exception $e) {
                file_put_contents($logfile, "DB DEBUG get_row query exception: " . $e->getMessage() . "\n", FILE_APPEND);
                return null;
            }
        } else {
            // SQL has placeholders, prepare and bind
            $stmt = $this->pdo->prepare($sql);
            if ($param !== null) {
                if (!is_array($param)) {
                    $param = [$param];
                }
                foreach ($param as $i => $val) {
                    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $stmt->execute();
        }
        
        $result = $output === OBJECT ? $stmt->fetch(PDO::FETCH_OBJ) : $stmt->fetch(PDO::FETCH_ASSOC);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG get_row: result=" . json_encode($result) . "\n", FILE_APPEND);
        return $result;
    }
    
    public function get_var($sql, $param = null) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "DB DEBUG get_var called: sql=" . substr($sql, 0, 80) . "... param=" . var_export($param, true) . "\n", FILE_APPEND);
        // Check if SQL already has values substituted (no placeholders)
        if (strpos($sql, '%s') === false && strpos($sql, '%d') === false && strpos($sql, '%f') === false) {
            // SQL already prepared, execute directly
            try {
                $stmt = $this->pdo->query($sql);
            } catch (Exception $e) {
                file_put_contents($logfile, "DB DEBUG get_var query exception: " . $e->getMessage() . "\n", FILE_APPEND);
                return null;
            }
        } else {
            // SQL has placeholders, prepare and bind
            $stmt = $this->pdo->prepare($sql);
            if ($param !== null) {
                if (!is_array($param)) {
                    $param = [$param];
                }
                foreach ($param as $i => $val) {
                    $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
            }
            $stmt->execute();
        }
        $result = $stmt->fetch(PDO::FETCH_NUM);
        return $result[0] ?? null;
    }
    
    public function insert($table, $data, $format = null) {
        $logfile = 'D:\MyPerk-New\myperk-backend\debug.log';
        file_put_contents($logfile, "DB DEBUG insert ENTERED: table=$table, count=" . count($data) . "\n", FILE_APPEND);
        file_put_contents($logfile, "DB DEBUG pdo exists: " . (isset($this->pdo) ? 'yes' : 'no') . "\n", FILE_APPEND);
        if (isset($this->pdo)) {
            file_put_contents($logfile, "DB DEBUG pdo type: " . get_class($this->pdo) . "\n", FILE_APPEND);
        }
        try {
            file_put_contents($logfile, "DB DEBUG insert try ENTERED\n", FILE_APPEND);
            
            if ($format === null) {
                $format = [];
                foreach ($data as $key => $val) {
                    $format[] = is_int($val) ? '%d' : '%s';
                }
            }
            
            $columns = array_keys($data);
            $values = array_values($data);
            file_put_contents($logfile, "DB DEBUG columns: " . implode(', ', $columns) . "\n", FILE_APPEND);
            file_put_contents($logfile, "DB DEBUG format: " . implode(', ', $format) . "\n", FILE_APPEND);
            
            $placeholders = [];
            foreach ($format as $i => $fmt) {
                $placeholders[] = '?';
            }
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            file_put_contents($logfile, "DB DEBUG sql: $sql\n", FILE_APPEND);
            
            $stmt = $this->pdo->prepare($sql);
            file_put_contents($logfile, "DB DEBUG prepare succeeded\n", FILE_APPEND);
            
            foreach ($values as $i => $val) {
                $param = $val;
                $type = $format[$i] === '%d' ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($i + 1, $param, $type);
            }
            
            $stmt->execute();
            $this->insert_id = $this->pdo->lastInsertId();
            file_put_contents($logfile, "DB DEBUG execute succeeded, insert_id=$this->insert_id\n", FILE_APPEND);
            return $this->insert_id;
        } catch (Exception $e) {
            file_put_contents($logfile, "DB DEBUG insert exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return 0;
        }
    }
    
    public function update($table, $data, $where, $format = null) {
        $logfile = 'D:\\MyPerk-New\\myperk-backend\\debug.log';
        file_put_contents($logfile, "DB DEBUG update ENTERED: table=$table, data=" . json_encode($data) . ", where=" . json_encode($where) . "\n", FILE_APPEND);

        try {
            $set_parts = [];
            $values = [];
            $types = [];

            // Process data columns (SET part)
            $format_idx = 0;
            foreach ($data as $key => $val) {
                $set_parts[] = "$key = ?";
                $values[] = $val;
                if (is_array($format) && isset($format[$format_idx])) {
                    $types[] = ($format[$format_idx] === '%d') ? PDO::PARAM_INT : PDO::PARAM_STR;
                } else {
                    $types[] = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
                }
                $format_idx++;
            }

            // Process WHERE clause
            $where_parts = [];
            foreach ($where as $key => $val) {
                $where_parts[] = "$key = ?";
                $values[] = $val;
                if (is_array($format) && isset($format[$format_idx])) {
                    $types[] = ($format[$format_idx] === '%d') ? PDO::PARAM_INT : PDO::PARAM_STR;
                } else {
                    $types[] = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
                }
                $format_idx++;
            }

            $sql = "UPDATE $table SET " . implode(', ', $set_parts) . " WHERE " . implode(' AND ', $where_parts);

            file_put_contents($logfile, "DB DEBUG update SQL: $sql\n", FILE_APPEND);
            file_put_contents($logfile, "DB DEBUG update values: " . json_encode($values) . "\n", FILE_APPEND);

            $stmt = $this->pdo->prepare($sql);
            foreach ($values as $i => $val) {
                $stmt->bindValue($i + 1, $val, $types[$i]);
            }

            $result = $stmt->execute();
            $affected = $stmt->rowCount();
            file_put_contents($logfile, "DB DEBUG update result: " . ($result ? 'success' : 'failed') . ", affected=$affected\n", FILE_APPEND);
            return $result ? $affected : false;
        } catch (Exception $e) {
            file_put_contents($logfile, "DB DEBUG update exception: " . $e->getMessage() . "\n", FILE_APPEND);
            return false;
        }
    }
    
    private function prepare_placeholder($type) {
        return '?';
    }
    
    public function prepare($sql, ...$args) {
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG prepare: sql=$sql, args=" . json_encode($args) . "\n", FILE_APPEND);
        if (empty($args)) {
            return $sql;
        }
        $params = $args[0] ?? [];
        if (!is_array($params)) {
            $params = array_slice($args, 0);
        }
        
        $i = 0;
        $result = preg_replace_callback('/%s|%d|%f/', function($matches) use (&$i, &$params) {
            if (!isset($params[$i])) return '%s';
            $val = $params[$i++];
            if ($matches[0] === '%d') return intval($val);
            if ($matches[0] === '%f') return floatval($val);
            return "'" . addslashes($val) . "'";
        }, $sql);
        file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "DB DEBUG prepare result: $result\n", FILE_APPEND);
        return $result;
    }
    
    public function get_results($sql, $param = null, $output = OBJECT) {
        $logfile = 'D:\\MyPerk-New\\myperk-backend\\debug.log';
        file_put_contents($logfile, "DB DEBUG get_results ENTERED: " . substr($sql, 0, 120) . "...\n", FILE_APPEND);
        file_put_contents($logfile, "DB DEBUG get_results param: " . var_export($param, true) . "\n", FILE_APPEND);
        
        try {
            // WordPress style: prepare() is called separately to substitute values
            // So the SQL passed to get_results is already prepared
            // If there are still % placeholders, that's an error
            if (strpos($sql, '%') !== false) {
                file_put_contents($logfile, "DB DEBUG get_results: WARNING - SQL still has placeholder\n", FILE_APPEND);
            }
            
            // Execute the SQL directly (it's already prepared with values substituted)
            $stmt = $this->pdo->query($sql);
            
            $results = $stmt->fetchAll(PDO::FETCH_OBJ);
            file_put_contents($logfile, "DB DEBUG get_results: result_count=" . count($results) . "\n", FILE_APPEND);
            return $results;
        } catch (Exception $e) {
            file_put_contents($logfile, "DB DEBUG get_results EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
            return [];
        }
    }
}

// Include API class
file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "BEFORE REQUIRE class-loyalty\n", FILE_APPEND);
require_once __DIR__ . '/myperk-core/includes/class-loyalty.php';
file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "BEFORE REQUIRE class-api\n", FILE_APPEND);
require_once __DIR__ . '/myperk-core/includes/class-api.php';
file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "AFTER REQUIRES\n", FILE_APPEND);

// Initialize
$router = new MyPerk_Router();

// Set up global $wpdb for the API classes
global $wpdb;
$wpdb = MyPerk_DB_Standalone::get_instance();
$wpdb->prefix = ''; // Tables already have loyalty_ prefix

// WordPress function stubs for audit log
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() { return 0; }
}

// MyPerk_Security with standalone auth integration
if (!class_exists('MyPerk_Security')) {
    class MyPerk_Security {
        public static function log_audit($business_id, $user_id, $user_role, $action, $target_type, $target_id, $metadata = []) {
            $target_str = is_array($target_type) ? json_encode($target_type) : $target_type;
            $target_id_str = is_array($target_id) ? json_encode($target_id) : $target_id;
            file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "AUDIT LOG: business=$business_id, action=$action, target=$target_str:$target_id_str\n", FILE_APPEND);
            return true;
        }
        
        // Standalone authentication integration
        public static function user_can_access_business($user_id, $business_id) {
            // In standalone mode, check if current auth has access
            $auth = MyPerk_Standalone_Auth::get_instance()->authenticate();
            if (!$auth['authenticated']) {
                return false;
            }
            
            $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
            $auth_business_id = $auth['data']['business_id'] ?? null;
            
            // Super admin can access all businesses
            if ($role === MYPERK_ROLE_SUPER_ADMIN) {
                return true;
            }
            
            // Business owner can access their own business
            if ($role === MYPERK_ROLE_BUSINESS) {
                return $auth_business_id === $business_id;
            }
            
            // Staff can access their assigned business
            if ($role === MYPERK_ROLE_STAFF) {
                return $auth_business_id === $business_id;
            }
            
            // Customer can access businesses they've joined
            if ($role === MYPERK_ROLE_CUSTOMER) {
                // In a real implementation, check if customer has a card for this business
                return $auth_business_id === $business_id;
            }
            
            return false;
        }
        
        public static function staff_can_process($staff_id, $business_id, $branch_id) {
            $auth = MyPerk_Standalone_Auth::get_instance()->authenticate();
            if (!$auth['authenticated']) {
                return false;
            }
            
            $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
            $auth_business_id = $auth['data']['business_id'] ?? null;
            
            return MyPerk_Standalone_Auth::get_instance()->staff_can_process($role, $auth_business_id, $business_id);
        }
    }
}

// Register the staff authorization filter for standalone mode
add_filter('myperk_staff_can_process', function($default, $staff_id, $business_id, $branch_id) {
    return MyPerk_Security::staff_can_process($staff_id, $business_id, $branch_id);
}, 10, 4);

$api = MyPerk_API::get_instance();
$api->register_routes();

// Handle Request
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = '/' . ltrim($path, '/');
$body = file_get_contents('php://input');
$headers = getallheaders();

// Write debug to temp file
file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "BEFORE ROUTER: method=$method, path=$path, body=$body\n", FILE_APPEND);

// Register shutdown function to catch fatal errors
register_shutdown_function(function() use ($headers, $path) {
    $error = error_get_last();
    if ($error && $error['type'] === E_ERROR) {
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "FATAL ERROR: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'] . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode(['error' => $error['message'], 'fatal' => true, 'path' => $path]);
    }
});

try {
    file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "BEFORE DISPATCH: method=$method, path=$path\n", FILE_APPEND);
    $response = $router->dispatch($method, $path, $body, $headers);
    file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "AFTER DISPATCH: status=" . $response->status . "\n", FILE_APPEND);
} catch (Error $e) {
    @file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'headers' => json_encode($headers)]);
    exit;
} catch (Exception $e) {
    // Also try to write to debug log
    @file_put_contents('D:\\MyPerk-New\\myperk-backend\\first_debug.log', "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'headers' => json_encode($headers)]);
    exit;
}

$status = (int) $response->status;
http_response_code($status);
foreach ($response->headers as $key => $value) {
    header("$key: $value");
}
$output = json_encode($response->data);
file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "ROUTER RESPONSE: status=$status, output=$output\n", FILE_APPEND);
echo $output;