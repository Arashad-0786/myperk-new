<?php
/** 
 * Standalone MyPerk REST API Server 
 * Serves myperk/v1 endpoints without WordPress 
 * Uses MySQL 8.4 on port 3307 
 */

// Suppress deprecation warnings to prevent them from appearing in JSON responses
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '1');

// ==========================================\n// WordPress-like Constants & Functions\n// ==========================================
define('ABSPATH', __DIR__ . '/myperk-core/');
define('MYPERK_VERSION', '1.0.0');
define('MYPERK_PLUGIN_DIR', __DIR__ . '/myperk-core/');
define('MYPERK_PLUGIN_URL', 'http://localhost:8080/');

// WordPress $wpdb fetch mode constants
define('OBJECT', 'OBJECT');
define('ARRAY_A', 'ARRAY_A');
define('ARRAY_N', 'ARRAY_N');

// Session for options storage
session_start();

// Standalone getallheaders (PHP CLI doesn't have it)
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

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $wp_filter;
        $wp_filter[$hook][] = ['callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args];
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {
        global $wp_filter;
        $wp_filter[$hook][] = ['callback' => $callback, 'priority' => $priority, 'accepted_args' => $accepted_args];
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args) {
        global $wp_filter;
        if (isset($wp_filter[$hook])) {
            usort($wp_filter[$hook], fn($a, $b) => $a['priority'] - $b['priority']);
            foreach ($wp_filter[$hook] as $filter) {
                call_user_func_array($filter['callback'], $args);
            }
        }
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args) {
        global $wp_filter;
        if (isset($wp_filter[$hook])) {
            usort($wp_filter[$hook], fn($a, $b) => $a['priority'] - $b['priority']);
            foreach ($wp_filter[$hook] as $filter) {
                $value = call_user_func_array($filter['callback'], [$value, ...$args]);
            }
        }
        return $value;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        global $current_user;
        return $current_user && $current_user->role === 'administrator';
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        if (is_array($str)) {
            return array_map('sanitize_text_field', $str);
        }
        if ($str === null) {
            return '';
        }
        $str = (string) $str;
        $str = remove_accents($str);
        $str = preg_replace('/[<>]/', '', $str);
        $str = htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        return $str;
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim($str);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('current_time')) {
    function current_time($type = 'mysql') {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 1000000) {
        return random_int($min, $max);
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return $url;
    }
}

if (!function_exists('rest_url')) {
    function rest_url() {
        return 'http://localhost:8080/myperk/v1/';
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'standalone-nonce';
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action) {
        return true;
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return stripslashes($value);
    }
}

if (!function_exists('selected')) {
    function selected($selected, $current = true, $echo = true) {
        $html = ($selected == $current) ? 'selected="selected"' : '';
        if ($echo) {
            echo $html;
        }
        return $html;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        $_SESSION['myperk_options'][$option] = $value;
        return true;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $_SESSION['myperk_options'][$option] ?? $default;
    }
}

if (!function_exists('register_setting')) {
    function register_setting($group, $name) {}
}

if (!function_exists('settings_fields')) {
    function settings_fields($group) {}
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($group) {}
}

if (!function_exists('submit_button')) {
    function submit_button($text = 'Save', $type = 'primary', $name = 'submit', $wrap = true) {
        echo "<input type='submit' name='$name' class='button button-$type' value='$text'>";
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src, $deps = [], $ver = false) {}
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {}
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {}
}

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback) {}
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost:8080/';
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {}
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {}
}

if (!function_exists('add_menu_page')) {
    function add_menu_page($page_title, $menu_title, $capability, $menu_slug, $callback, $icon_url = '', $position = null) {}
}

if (!function_exists('add_submenu_page')) {
    function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback) {}
}

if (!function_exists('__return_true')) {
    function __return_true() { return true; }
}

if (!function_exists('__return_false')) {
    function __return_false() { return false; }
}

if (!function_exists('wp_slash')) {
    function wp_slash($value) {
        return addslashes($value);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($data) {
        return $data;
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($string, $allowed_html) {
        return $string;
    }
}

if (!function_exists('remove_accents')) {
    function remove_accents($string) {
        return $string;
    }
}

// Auth functions
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        global $current_user;
        return $current_user !== null;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        global $current_user;
        return $current_user->ID ?? 0;
    }
}

// current_time function (WordPress compatibility)
if (!function_exists('current_time')) {
    function current_time($type = 'mysql', $gmt = 0) {
        if ($type === 'mysql' || $type === 'U') {
            return date('Y-m-d H:i:s');
        }
        if ($type === 'timestamp') {
            return time();
        }
        return date('Y-m-d H:i:s');
    }
}

// ==========================================
// WP_Error & WP_REST_Response classes
// ==========================================
class WP_Error {
    public $errors = [];
    public $error_data = [];
    
    public function __construct($code = '', $message = '', $data = '') {
        if ($code) {
            $this->errors[$code][] = $message;
            if ($data) {
                $this->error_data[$code] = $data;
            }
        }
    }
    
    public function get_error_code() {
        return empty($this->errors) ? '' : array_key_first($this->errors);
    }
    
    public function get_error_message($code = '') {
        if (!$code) {
            $code = $this->get_error_code();
        }
        return $this->errors[$code][0] ?? '';
    }
    
    public function get_error_data($code = '') {
        if (!$code) {
            $code = $this->get_error_code();
        }
        return $this->error_data[$code] ?? '';
    }
}

// is_wp_error function (WordPress compatibility) - after WP_Error class
if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

class WP_REST_Request {
    private $params = [];
    private $headers = [];
    private $body = '';
    
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
    
    public function get_header($key) {
        return $this->headers[strtolower($key)] ?? null;
    }
    
    public function get_body() {
        return $this->body;
    }
    
    public function get_json_params() {
        return json_decode($this->body, true) ?? [];
    }
}

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

// ==========================================
// Database Setup - MyPerk_DB Adapter
// ==========================================
// Include the class-db.php which has the full MyPerk_DB_Standalone implementation
require_once __DIR__ . '/myperk-core/includes/class-db.php';

// Set up global $wpdb for the API classes
global $wpdb;
$wpdb = MyPerk_DB_Standalone::get_instance();
$wpdb->prefix = ''; // Tables already have loyalty_ prefix without prefix

// ==========================================
// Include Standalone Auth System
// ==========================================
require_once __DIR__ . '/standalone-auth.php';

// ==========================================
// Include MyPerk Core Classes
// ==========================================
require_once __DIR__ . '/myperk-core/includes/class-security.php';
require_once __DIR__ . '/myperk-core/includes/class-loyalty.php';
require_once __DIR__ . '/myperk-core/includes/class-billing.php';
require_once __DIR__ . '/myperk-core/includes/class-api.php';
require_once __DIR__ . '/myperk-core/includes/class-admin.php';

// ==========================================
// Simple Router for REST API
// ==========================================
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
        
        // Parse path for params
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $path, $matches)) {
                // Extract named params
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_numeric($key)) {
                        $params[$key] = $value;
                    }
                }
                
                // Parse body params (JSON or form-urlencoded)
                // Note: php://input can only be read once per request
                $json_params = [];
                if (!empty($body)) {
                    $json_params = json_decode($body, true);
                    if (!is_array($json_params)) {
                        $json_params = [];
                    }
                }
                
                // Merge all params: route params, GET, POST, JSON body
                // JSON body params take precedence (array_merge gives later values precedence)
                $all_params = array_merge($params, $_GET, $_POST, $json_params);
                
                // Create request object
                $request = new WP_REST_Request($all_params, $headers, $body);
                
                try {
                    $result = call_user_func($route['callback'], $request);
                    
                    if ($result instanceof WP_Error) {
                        return new WP_REST_Response(
                            ['success' => false, 'message' => $result->get_error_message()],
                            $result->get_error_data('status') ?? 400
                        );
                    }
                    
                    if ($result instanceof WP_REST_Response) {
                        return $result;
                    }
                    
                    return new WP_REST_Response($result, 200);
                } catch (Exception $e) {
                    error_log("API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                    return new WP_REST_Response(
                        ['success' => false, 'message' => $e->getMessage()],
                        500
                    );
                }
            }
        }
        
        return new WP_REST_Response(['success' => false, 'message' => 'Route not found'], 404);
    }
}

$router = new MyPerk_Router();

// We need to intercept the register_rest_route calls
function register_rest_route($namespace, $route, $args = []) {
    global $router;
    $methods = $args['methods'] ?? 'GET';
    $callback = $args['callback'] ?? null;
    $permission_callback = $args['permission_callback'] ?? '__return_true';
    
    // Ensure the path starts with /
    $fullPath = '/' . ltrim($namespace . $route, '/');
    
    if (is_array($methods)) {
        foreach ($methods as $method) {
            $router->add_route($method, $fullPath, function($request) use ($callback, $permission_callback) {
                // Check permission
                if ($permission_callback !== '__return_true') {
                    $perm_result = call_user_func($permission_callback, $request);
                    if (is_wp_error($perm_result) || $perm_result === false) {
                        return new WP_Error('forbidden', 'Permission denied', ['status' => 403]);
                    }
                }
                return call_user_func($callback, $request);
            });
        }
    } else {
        $router->add_route($methods, $fullPath, function($request) use ($callback, $permission_callback) {
            if ($permission_callback !== '__return_true') {
                $perm_result = call_user_func($permission_callback, $request);
                if (is_wp_error($perm_result) || $perm_result === false) {
                    return new WP_Error('forbidden', 'Permission denied', ['status' => 403]);
                }
            }
            return call_user_func($callback, $request);
        });
    }
}

// Initialize API to register routes
$api = MyPerk_API::get_instance();

// Manually call register_routes since rest_api_init action won't fire
$api->register_routes();

$billing = MyPerk_Billing::get_instance();

// Also register billing webhook routes
$billing->register_webhook_routes();

// ==========================================
// Handle Request
// ==========================================
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Debug: check router routes after API registration
global $router;
$api = MyPerk_API::get_instance();
$api->register_routes();
$billing = MyPerk_Billing::get_instance();
$billing->register_webhook_routes();

// Register staff authorization filter for standalone mode
add_filter('myperk_staff_can_process', function($default, $staff_id, $business_id, $branch_id) {
    $auth_instance = MyPerk_Standalone_Auth::get_instance();
    $auth = $auth_instance->authenticate();
    if (!$auth['authenticated']) {
        return false;
    }
    $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
    $auth_business_id = $auth['data']['business_id'] ?? null;
    
    // Super admin can process anywhere
    if ($role === MYPERK_ROLE_SUPER_ADMIN) {
        return true;
    }
    // Staff and business owner can process if they're authorized for that business
    if (in_array($role, [MYPERK_ROLE_STAFF, MYPERK_ROLE_BUSINESS])) {
        return $auth_business_id === $business_id;
    }
    return false;
}, 10, 4);

error_log("ROUTES COUNT: " . count($router->routes));
foreach ($router->routes as $i => $route) {
    error_log("ROUTE $i: method={$route['method']} path={$route['path']}");
}

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
$body = file_get_contents('php://input');
$headers = getallheaders();

// Normalize path to start with /
$path = '/' . ltrim($path, '/');

// Handle request
try {
    $response = $router->dispatch($method, $path, $body, $headers);
    // Ensure status is int
    $response->status = (int) $response->status;
} catch (Exception $e) {
    error_log("FATAL: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    $response = new WP_REST_Response(['success' => false, 'message' => 'Internal server error'], 500);
    $response->status = 500;
}

$status = (int) ($response->status ?? 200);
http_response_code($status);
foreach ($response->headers as $key => $value) {
    header("$key: $value");
}
echo json_encode($response->data);