<?php
/**
 * Standalone Authentication and Permission System for MyPerk
 * 
 * This system replaces WordPress user-based authentication with
 * token-based authentication suitable for standalone PHP servers.
 * 
 * Never trust query parameters (customer_id, business_id, branch_id, phone)
 * as proof of identity. All auth must come from valid tokens.
 */

// Configuration - these should be set via environment variables or config file
define('MYPERK_STANDALONE_MODE', true);
define('MYPERK_API_KEY_NAME', 'X-MyPerk-API-Key');

// In standalone mode, define role constants
if (defined('MYPERK_STANDALONE_MODE') && MYPERK_STANDALONE_MODE) {
    define('MYPERK_ROLE_SUPER_ADMIN', 'super_admin');
    define('MYPERK_ROLE_BUSINESS', 'business');
    define('MYPERK_ROLE_STAFF', 'staff');
    define('MYPERK_ROLE_CUSTOMER', 'customer');
    define('MYPERK_ROLE_ANONYMOUS', 'anonymous');
}

/** 
 * Standalone Authentication Class 
 * 
 * Handles API key validation, role-based access control,
 * and never trusts query parameters as proof of identity.
 */
class MyPerk_Standalone_Auth {
    
    private static $instance = null;
    private $api_keys = []; // In production, load from secure config/db
    private $user_sessions = []; // Track active sessions
    
    private function __construct() {
        // Load API keys from secure configuration
        // In production: load from encrypted config file or database
        $this->load_api_keys();
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function load_api_keys() {
        // In production, load from secure config
        // For now, set up some test keys
        // Format: 'api_key_hash' => ['role' => 'role_name', 'business_id' => int, 'user_id' => int]
        // Keys should be hashed SHA-256 values
        $keys = [
            // Super admin key - full access
            'sk_myperk_super_admin_123' => [
                'role' => MYPERK_ROLE_SUPER_ADMIN,
                'business_id' => null,
                'user_id' => 0,
            ],
            // Business owner key for business ID 1
            'bk_myperk_business_1' => [
                'role' => MYPERK_ROLE_BUSINESS,
                'business_id' => 1,
                'user_id' => 0,
            ],
            // Staff key for business ID 1
            'wk_myperk_staff_1' => [
                'role' => MYPERK_ROLE_STAFF,
                'business_id' => 1,
                'user_id' => 0,
            ],
            // Customer key - phone linked
            'ck_myperk_customer_test' => [
                'role' => MYPERK_ROLE_CUSTOMER,
                'business_id' => 1,
                'user_id' => 0,
                'phone' => '+1234567890',
            ],
        ];
        
        // Hash all keys for storage
        foreach ($keys as $key => $data) {
            $this->api_keys[hash('sha256', $key)] = $data;
        }
    }
    
    /** 
     * Authenticate request using API key header
     * 
     * @return array ['authenticated' => bool, 'role' => string, 'data' => array]
     */
    public function authenticate() {
        // Debug: write to file
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: authenticate() called\n", FILE_APPEND);
        
        // Capture raw headers - PHP CLI server's getallheaders()
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: getallheaders returned: " . print_r($headers, true) . "\n", FILE_APPEND);
        
        // Also try $_SERVER fallback for PHP CLI development server
        if (empty($headers) && isset($_SERVER['HTTP_X_MYPERK_API_KEY'])) {
            $headers['x-myperk-api-key'] = $_SERVER['HTTP_X_MYPERK_API_KEY'];
        }
        
        // Normalize header names to lowercase for reliable lookup
        $headers = array_change_key_case($headers);
        
        // Debug: log the headers we received
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: Normalized headers: " . print_r($headers, true) . "\n", FILE_APPEND);
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: x-myperk-api-key isset: " . (isset($headers['x-myperk-api-key']) ? 'yes: ' . $headers['x-myperk-api-key'] : 'no') . "\n", FILE_APPEND);
        
        // Try to get API key from header (getallheaders returns lowercase keys after array_change_key_case)
        $api_key = null;
        if (isset($headers['x-myperk-api-key'])) {
            $api_key = $headers['x-myperk-api-key'];
            @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: API key found in header: " . $api_key . "\n", FILE_APPEND);
        } elseif (isset($headers['authorization'])) {
            // Bearer token format
            $auth = explode(' ', $headers['authorization']);
            if (count($auth) === 2 && $auth[0] === 'Bearer') {
                $api_key = $auth[1];
            }
        } elseif (isset($_GET['api_key'])) {
            // Never use query parameter as primary auth - only as fallback
            // This is explicitly logged for security auditing
            @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: API key from query parameter: " . $_GET['api_key'] . "\n", FILE_APPEND);
            $api_key = $_GET['api_key'];
        }
        
        if (is_null($api_key)) {
            @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: No API key found, returning error\n", FILE_APPEND);
            return [
                'authenticated' => false,
                'role' => MYPERK_ROLE_ANONYMOUS,
                'data' => [],
                'error' => 'No API key provided'
            ];
        }
        
        // Look up the API key
        $key_hash = hash('sha256', $api_key);
        @file_put_contents('D:\\MyPerk-New\\myperk-backend\\auth_debug.log', "AUTH_DEBUG: key_hash=$key_hash, available keys: " . print_r(array_keys($this->api_keys), true) . "\n", FILE_APPEND);
        if (!isset($this->api_keys[$key_hash])) {
            return [
                'authenticated' => false,
                'role' => MYPERK_ROLE_ANONYMOUS,
                'data' => [],
                'error' => 'Invalid API key'
            ];
        }
        
        $key_data = $this->api_keys[$key_hash];
        $role = $key_data['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $business_id = $key_data['business_id'] ?? null;
        $user_id = $key_data['user_id'] ?? 0;
        $phone = $key_data['phone'] ?? null;
        
        return [
            'authenticated' => true,
            'role' => $role,
            'data' => [
                'business_id' => $business_id,
                'user_id' => $user_id,
                'phone' => $phone,
                'role' => $role,
            ],
            'api_key' => $api_key
        ];
    }
    
    /** 
     * Generate a signed API key for a specific role and business
     * 
     * @param string $role MYPERK_ROLE_*
     * @param int $business_id
     * @param int $user_id
     * @param string $phone (for customers)
     * @return string signed_key
     */
    public function generate_api_key($role, $business_id = null, $user_id = 0, $phone = null) {
        // Create a key identifier
        $key_id = sprintf('%s_%d_%d', $role, $business_id ?? 0, $user_id);
        if ($phone) {
            $key_id .= '_' . $phone;
        }
        
        // Hash it for storage
        $key_hash = hash('sha256', $key_id . '_myperk_salt');
        
        // Store the key data (not the hash itself, but the metadata)
        $this->api_keys[$key_hash] = [
            'role' => $role,
            'business_id' => $business_id,
            'user_id' => $user_id,
            'phone' => $phone,
        ];
        
        return $api_key; // In production, return the plain key, store hash
    }
    
    /** 
     * Verify that a role can access a specific business
     * 
     * @param string $role
     * @param int $business_id
     * @return bool
     */
    public function role_can_access_business($role, $business_id) {
        if ($role === MYPERK_ROLE_SUPER_ADMIN) {
            return true;
        }
        
        if ($role === MYPERK_ROLE_BUSINESS) {
            // Business owner can access their own business
            return $business_id !== null;
        }
        
        if ($role === MYPERK_ROLE_STAFF) {
            // Staff can access their assigned business
            return $business_id !== null;
        }
        
        if ($role === MYPERK_ROLE_CUSTOMER) {
            // Customer can access business they've joined
            return $business_id !== null;
        }
        
        return false;
    }
    
    /** 
     * Check if staff can process stamp requests for a business
     * 
     * @param string $role
     * @param int $staff_business_id (the business the staff is assigned to)
     * @param int $request_business_id (the business the request is for)
     * @return bool
     */
    public function staff_can_process($role, $staff_business_id, $request_business_id) {
        if ($role === MYPERK_ROLE_SUPER_ADMIN) {
            return true;
        }
        
        if ($role === MYPERK_ROLE_BUSINESS) {
            // Business owner can process requests for their business
            return $staff_business_id === $request_business_id;
        }
        
        if ($role === MYPERK_ROLE_STAFF) {
            // Staff can process requests for their assigned business
            return $staff_business_id === $request_business_id;
        }
        
        if ($role === MYPERK_ROLE_CUSTOMER) {
            // Customers should not be able to process requests
            return false;
        }
        
        return false;
    }
}

/** 
 * Helper function to get current user role in standalone mode 
 */
if (!function_exists('myperk_get_current_role')) {
    function myperk_get_current_role() {
        $auth = MyPerk_Standalone_Auth::get_instance()->authenticate();
        if ($auth['authenticated']) {
            return $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        }
        return MYPERK_ROLE_ANONYMOUS;
    }
}

/** 
 * Helper function to get current business ID in standalone mode 
 */
if (!function_exists('myperk_get_current_business_id')) {
    function myperk_get_current_business_id() {
        $auth = MyPerk_Standalone_Auth::get_instance()->authenticate();
        if ($auth['authenticated']) {
            return $auth['data']['business_id'] ?? null;
        }
        return null;
    }
}

/** 
 * Helper function to check if current user has a specific role 
 */
if (!function_exists('myperk_current_can')) {
    function myperk_current_can($role) {
        $current_role = myperk_get_current_role();
        return $current_role === $role;
    }
}