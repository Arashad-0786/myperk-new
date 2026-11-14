<?php
/**
 * REST API Controller for MyPerk Core.
 */

class MyPerk_API {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register MyPerk REST API endpoints.
     */
    public function register_routes() {
        $namespace = 'myperk/v1';

        // ==========================================
        // PUBLIC ENDPOINTS (No auth required)
        // ==========================================

        // QR Verification - POST as per Document 05/08/09 consistency
        register_rest_route( $namespace, '/public/qr/verify', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'verify_qr' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'qr_hash' => array(
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ) );

        // Customer joins loyalty program (after QR scan)
        register_rest_route( $namespace, '/public/customer/join', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'customer_join' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'business_id' => array( 'required' => true, 'type' => 'integer' ),
                'branch_id'   => array( 'required' => true, 'type' => 'integer' ),
                'phone'       => array( 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
                'name'        => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );

        // Get public business info
        register_rest_route( $namespace, '/public/business/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_public_business' ),
            'permission_callback' => '__return_true',
        ) );

        // Get public branches for a business
        register_rest_route( $namespace, '/public/business/(?P<id>\d+)/branches', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_public_branches' ),
            'permission_callback' => '__return_true',
        ) );

        // Get public rewards for a business
        register_rest_route( $namespace, '/public/rewards', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_public_rewards' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'business_id' => array( 'required' => true, 'type' => 'integer' ),
            ),
        ) );

        // ==========================================
        // CUSTOMER ENDPOINTS (Customer auth required)
        // ==========================================

        // Create stamp request
        register_rest_route( $namespace, '/customer/stamp-request', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_stamp_request' ),
            'permission_callback' => array( $this, 'check_customer_auth' ),
            'args'                => array(
                'business_id'    => array( 'required' => true, 'type' => 'integer' ),
                'branch_id'      => array( 'required' => true, 'type' => 'integer' ),
                'qr_hash'        => array( 'required' => true, 'type' => 'string' ),
                'requested_stamps' => array( 'type' => 'integer', 'default' => 1 ),
            ),
        ) );

        // Get customer's loyalty cards
        register_rest_route( $namespace, '/customer/cards', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_customer_cards' ),
            'permission_callback' => array( $this, 'check_customer_auth' ),
        ) );

        // Get specific card details
        register_rest_route( $namespace, '/customer/cards/(?P<business_id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_customer_card' ),
            'permission_callback' => array( $this, 'check_customer_auth' ),
        ) );

        // ==========================================
        // STAFF ENDPOINTS (Staff auth required)
        // ==========================================

        // Get pending requests for staff
        register_rest_route( $namespace, '/staff/requests', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_pending_requests' ),
            'permission_callback' => array( $this, 'check_staff_auth' ),
            'args'                => array(
                'business_id' => array( 'required' => true, 'type' => 'integer' ),
                'branch_id'   => array( 'type' => 'integer' ),
                'limit'       => array( 'type' => 'integer', 'default' => 50 ),
            ),
        ) );

        // Process (approve/reject) stamp request
        register_rest_route( $namespace, '/staff/requests/(?P<id>\d+)/process', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'process_stamp_request' ),
            'permission_callback' => array( $this, 'check_staff_auth' ),
            'args'                => array(
                'id'               => array( 'required' => true, 'type' => 'integer' ),
                'decision'         => array( 'required' => true, 'type' => 'string', 'enum' => array( 'approve', 'reject' ) ),
                'approved_stamps'  => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1, 'maximum' => 10 ),
            ),
        ) );

        // ==========================================
        // BUSINESS OWNER ENDPOINTS (Owner auth required)
        // ==========================================

        // Business dashboard data
        register_rest_route( $namespace, '/business/(?P<id>\d+)/dashboard', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_business_dashboard' ),
            'permission_callback' => array( $this, 'check_business_owner' ),
        ) );

        // Business settings
        register_rest_route( $namespace, '/business/(?P<id>\d+)/settings', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_business_settings' ),
            'permission_callback' => array( $this, 'check_business_owner' ),
        ) );

        register_rest_route( $namespace, '/business/(?P<id>\d+)/settings', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'update_business_settings' ),
            'permission_callback' => array( $this, 'check_business_owner' ),
        ) );

        // Branch management
        register_rest_route( $namespace, '/business/(?P<id>\d+)/branches', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_branches' ),
            'permission_callback' => array( $this, 'check_business_owner' ),
        ) );

        register_rest_route( $namespace, '/business/(?P<id>\d+)/branches', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'create_branch' ),
            'permission_callback' => array( $this, 'check_business_owner' ),
        ) );

        // ==========================================
        // ADMIN ENDPOINTS (Super admin only)
        // ==========================================

        // Platform dashboard
        register_rest_route( $namespace, '/admin/dashboard', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_admin_dashboard' ),
            'permission_callback' => array( $this, 'check_super_admin' ),
        ) );

        // Business listing
        register_rest_route( $namespace, '/admin/businesses', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_all_businesses' ),
            'permission_callback' => array( $this, 'check_super_admin' ),
        ) );

        // Subscription reconciliation
        register_rest_route( $namespace, '/admin/subscriptions/reconcile', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'reconcile_subscriptions' ),
            'permission_callback' => array( $this, 'check_super_admin' ),
        ) );
    }

    // ==========================================
    // PERMISSION CALLBACKS (Standalone Mode)
    // ==========================================
    
    // Helper: Get current authentication from API key header
    private function get_auth() {
        $auth = MyPerk_Standalone_Auth::get_instance()->authenticate();
        file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "DEBUG_GET_AUTH: auth=" . print_r($auth, true) . "\n", FILE_APPEND);
        return $auth;
    }

    public function check_customer_auth( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return false;
        }
        // Customer role or higher (business owner, staff, super_admin) can access customer endpoints
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        return in_array($role, [MYPERK_ROLE_CUSTOMER, MYPERK_ROLE_BUSINESS, MYPERK_ROLE_STAFF, MYPERK_ROLE_SUPER_ADMIN]);
    }

    public function check_staff_auth( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return false;
        }
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        // Staff, business owner, or super_admin can access staff endpoints
        return in_array($role, [MYPERK_ROLE_STAFF, MYPERK_ROLE_BUSINESS, MYPERK_ROLE_SUPER_ADMIN]);
    }

    public function check_business_owner( WP_REST_Request $request ) {
        $business_id = intval( $request->get_param( 'id' ) );
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return false;
        }
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        // Business owner or super_admin can access business owner endpoints
        if ($role === MYPERK_ROLE_SUPER_ADMIN) {
            return true;
        }
        if ($role === MYPERK_ROLE_BUSINESS) {
            $auth_business_id = $auth['data']['business_id'] ?? null;
            return $auth_business_id === $business_id;
        }
        return false;
    }

    public function check_super_admin( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return false;
        }
        return $auth['data']['role'] === MYPERK_ROLE_SUPER_ADMIN;
    }

    // ==========================================
    // PUBLIC ENDPOINT HANDLERS
    // ==========================================

    public function verify_qr( WP_REST_Request $request ) {
        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: verify_qr called\n", FILE_APPEND);
        try {
            $qr_hash = $request->get_param( 'qr_hash' );
            file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: qr_hash=$qr_hash\n", FILE_APPEND);
            $result  = MyPerk_Loyalty::verify_qr( $qr_hash );
            file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: result=" . json_encode($result) . "\n", FILE_APPEND);
            
            if (is_wp_error($result)) {
                return new WP_REST_Response(
                    ['valid' => false, 'error_code' => $result->get_error_code(), 'message' => $result->get_error_message()],
                    $result->get_error_data('status') ?? 400
                );
            }
            
            return new WP_REST_Response( $result, $result['valid'] ? 200 : 404 );
        } catch (Exception $e) {
            file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: verify_qr exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return new WP_REST_Response( ['valid' => false, 'error_code' => 'QR_VERIFY_ERROR', 'message' => 'QR verification failed: ' . $e->getMessage()], 500 );
        }
    }

    public function customer_join( WP_REST_Request $request ) {
        try {
            global $wpdb;

            $business_id = intval( $request->get_param( 'business_id' ) );
            $branch_id   = intval( $request->get_param( 'branch_id' ) );
            $phone       = sanitize_text_field( $request->get_param( 'phone' ) );
            $name        = sanitize_text_field( $request->get_param( 'name' ) );

            // Verify business is active
            $business = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, status FROM {$wpdb->prefix}loyalty_businesses WHERE id = %d",
                $business_id
            ) );

            if ( ! $business || ! in_array( $business->status, array( 'trialing', 'active', 'grace' ) ) ) {
                return new WP_REST_Response( ['success' => false, 'message' => 'Business not available.'], 400 );
            }

            // Find or create customer
            $customer_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", $phone
            ) );

            file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: customer_id from get_var=$customer_id\n", FILE_APPEND);

            if ( ! $customer_id ) {
                $wpdb->insert(
                    $wpdb->prefix . 'loyalty_customers',
                    array( 'phone' => $phone, 'full_name' => $name, 'status' => 'active', 'created_at' => current_time( 'mysql' ) ),
                    array( '%s', '%s', '%s', '%s' )
                );
                $customer_id = $wpdb->insert_id;
                file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: inserted customer, id=$customer_id\n", FILE_APPEND);
            } else {
                file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: customer already exists, id=$customer_id\n", FILE_APPEND);
            }

            // Create or get loyalty card
            $card_id = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}loyalty_cards WHERE business_id = %d AND customer_id = %d",
                $business_id, $customer_id
            ) );

            file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: card_id from get_var=$card_id\n", FILE_APPEND);

            if ( ! $card_id ) {
                $wpdb->insert(
                    $wpdb->prefix . 'loyalty_cards',
                    array(
                        'business_id'  => $business_id,
                        'customer_id'  => $customer_id,
                        'stamp_count'  => 0,
                        'status'       => 'active',
                        'joined_at'    => current_time( 'mysql' ),
                    ),
                    array( '%d', '%d', '%d', '%s', '%s' )
                );
                $card_id = $wpdb->insert_id;
                file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: inserted card, id=$card_id\n", FILE_APPEND);
            } else {
                file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API DEBUG: card already exists, id=$card_id\n", FILE_APPEND);
            }

            return new WP_REST_Response( array(
                'success'     => true,
                'customer_id' => $customer_id,
                'card_id'     => $card_id,
                'message'     => 'Welcome to the loyalty program!',
            ), 201 );
        } catch (Exception $e) {
            file_put_contents('D:/MyPerk-New/myperk-backend/debug.log', "API customer_join exception: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
            return new WP_REST_Response( ['success' => false, 'message' => 'Customer join failed: ' . $e->getMessage()], 500 );
        }
    }

    // ==========================================
    // PUBLIC ENDPOINT HANDLERS (Additional)
    // ==========================================

    public function get_public_business( WP_REST_Request $request ) {
        global $wpdb;
        $business_id = intval( $request->get_param( 'id' ) );

        $business = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, business_name, slug, contact_email, contact_phone, address, timezone, currency, logo_url, brand_color, description
            FROM {$wpdb->prefix}loyalty_businesses
            WHERE id = %d AND status IN ('trialing', 'active', 'grace')",
            $business_id
        ) );

        if ( ! $business ) {
            return new WP_REST_Response( ['success' => false, 'message' => 'Business not found.'], 404 );
        }

        return new WP_REST_Response( array( 'business' => $business ), 200 );
    }

    public function get_public_branches( WP_REST_Request $request ) {
        global $wpdb;
        $business_id = intval( $request->get_param( 'id' ) );

        // Verify business exists and is active
        $business = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_businesses WHERE id = %d AND status IN ('trialing', 'active', 'grace')",
            $business_id
        ) );

        if ( ! $business ) {
            return new WP_REST_Response( ['success' => false, 'message' => 'Business not found.'], 404 );
        }

        $branches = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, branch_name, branch_code, address, phone
            FROM {$wpdb->prefix}loyalty_branches
            WHERE business_id = %d AND status = 'active'
            ORDER BY branch_name ASC",
            $business_id
        ) );

        return new WP_REST_Response( array( 'branches' => $branches ), 200 );
    }

    public function get_public_rewards( WP_REST_Request $request ) {
        global $wpdb;
        $business_id = intval( $request->get_param( 'business_id' ) );

        // Verify business exists and is active
        $business = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_businesses WHERE id = %d AND status IN ('trialing', 'active', 'grace')",
            $business_id
        ) );

        if ( ! $business ) {
            return new WP_REST_Response( ['success' => false, 'message' => 'Business not found.'], 404 );
        }

        $rewards = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, business_id, name, description, stamp_cost, reward_type, reward_value, max_redemptions, is_active
            FROM {$wpdb->prefix}loyalty_rewards
            WHERE business_id = %d AND is_active = 1
            ORDER BY stamp_cost ASC",
            $business_id
        ) );

        return new WP_REST_Response( array( 'rewards' => $rewards ), 200 );
    }

    // ==========================================
    // CUSTOMER ENDPOINT HANDLERS
    // ==========================================

    public function create_stamp_request( WP_REST_Request $request ) {
        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: create_stamp_request called\n", FILE_APPEND);
        $auth = $this->get_auth();
        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: auth=" . json_encode($auth) . "\n", FILE_APPEND);
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        $args = array(
            'business_id'     => intval( $request->get_param( 'business_id' ) ),
            'branch_id'       => intval( $request->get_param( 'branch_id' ) ),
            'customer_id'     => null,
            'qr_hash'         => $request->get_param( 'qr_hash' ),
            'requested_stamps'=> intval( $request->get_param( 'requested_stamps' ) ),
        );

        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: args=" . json_encode($args) . "\n", FILE_APPEND);

        // Validate business_id matches authenticated user's business (if applicable)
        if ($role === MYPERK_ROLE_CUSTOMER || $role === MYPERK_ROLE_STAFF || $role === MYPERK_ROLE_BUSINESS) {
            if ($auth_business_id && $auth_business_id !== $args['business_id']) {
                return new WP_Error( 'forbidden', 'Cannot create stamp request for another business.', array( 'status' => 403 ) );
            }
        }

        // For customers: use their authenticated customer record
        // For staff/business: they must provide customer_id or phone via query params
        if ($role === MYPERK_ROLE_CUSTOMER) {
            // Customer must be authenticated and we should look up their customer_id by phone
            $phone = $auth['data']['phone'] ?? null;
            if ($phone) {
                global $wpdb;
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
                if ($customer_id) {
                    $args['customer_id'] = $customer_id;
                }
            }
        } else {
            // Staff/Business/SuperAdmin: use query params for customer identification
            $query = $request->get_params();
            file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: query for phone=" . json_encode($query) . "\n", FILE_APPEND);
            if (!empty($query['customer_id'])) {
                $args['customer_id'] = intval($query['customer_id']);
            } elseif (!empty($query['phone'])) {
                $phone = sanitize_text_field($query['phone']);
                global $wpdb;
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
                if ($customer_id) {
                    $args['customer_id'] = $customer_id;
                }
            }
        }

        if (is_null($args['customer_id'])) {
            return new WP_Error( 'missing_customer', 'Customer identification required. Provide customer_id or phone.', array( 'status' => 400 ) );
        }

        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: final args=" . json_encode($args) . "\n", FILE_APPEND);
        
        $result = MyPerk_Loyalty::create_stamp_request( $args );
        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: result=" . (is_scalar($result) ? json_encode($result) : gettype($result) . "(array keys: " . implode(',', array_keys($result)) . ")") . " is_wp_error=" . (is_wp_error($result) ? 'true' : 'false') . "\n", FILE_APPEND);

        file_put_contents('D:\MyPerk-New\myperk-backend\debug.log', "API DEBUG: about to return WP_REST_Response with data=" . (is_scalar($result) ? json_encode($result) : 'ARRAY') . "\n", FILE_APPEND);

        if ( is_wp_error( $result ) ) {
            return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
        }

        return new WP_REST_Response( $result, 201 );
    }

    public function get_customer_cards( WP_REST_Request $request ) {
        global $wpdb;
        
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        $customer_id = null;

        // For customer role: look up by their phone from auth
        if ($role === MYPERK_ROLE_CUSTOMER) {
            $phone = $auth['data']['phone'] ?? null;
            if ($phone) {
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
            }
        } else {
            // For staff/business/admin: use query params (customer_id or phone)
            $query = $request->get_params();
            if (!empty($query['customer_id'])) {
                $customer_id = intval($query['customer_id']);
            } elseif (!empty($query['phone'])) {
                $phone = sanitize_text_field($query['phone']);
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
            }
        }

        if (is_null($customer_id)) {
            return new WP_Error( 'missing_customer', 'Customer identification required.', array( 'status' => 400 ) );
        }
        
        // If business owner/staff/admin, verify they can access this customer's business
        if ($role !== MYPERK_ROLE_CUSTOMER && $role !== MYPERK_ROLE_SUPER_ADMIN) {
            $cards_check = $wpdb->get_results($wpdb->prepare(
                "SELECT c.business_id FROM {$wpdb->prefix}loyalty_cards c WHERE c.customer_id = %d AND c.status = 'active'",
                $customer_id
            ));
            if ($cards_check) {
                foreach ($cards_check as $card) {
                    if ($auth_business_id && $card->business_id !== $auth_business_id) {
                        return new WP_Error( 'forbidden', 'Cannot access cards for another business.', array( 'status' => 403 ) );
                    }
                }
            }
        }
        
        $cards = [];
        if (!is_null($customer_id)) {
            $cards = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, b.business_name, b.slug, b.logo_url, b.brand_color
                FROM {$wpdb->prefix}loyalty_cards c
                JOIN {$wpdb->prefix}loyalty_businesses b ON b.id = c.business_id
                WHERE c.customer_id = %d AND c.status = 'active' AND b.status IN ('trialing', 'active', 'grace')
                ORDER BY c.joined_at DESC",
                $customer_id
            ));
        }
        
        return new WP_REST_Response(array('cards' => $cards), 200);
    }

    public function get_customer_card( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        $business_id = intval( $request->get_param( 'business_id' ) );
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        // Verify business access
        if ($role !== MYPERK_ROLE_SUPER_ADMIN) {
            if ($auth_business_id && $auth_business_id !== $business_id) {
                return new WP_Error( 'forbidden', 'Cannot access cards for another business.', array( 'status' => 403 ) );
            }
        }

        $customer_id = null;
        if ($role === MYPERK_ROLE_CUSTOMER) {
            $phone = $auth['data']['phone'] ?? null;
            if ($phone) {
                global $wpdb;
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
            }
        } else {
            // For staff/business/admin: require customer_id or phone in query params
            $query = $request->get_params();
            if (!empty($query['customer_id'])) {
                $customer_id = intval($query['customer_id']);
            } elseif (!empty($query['phone'])) {
                $phone = sanitize_text_field($query['phone']);
                global $wpdb;
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
            }
        }

        if (is_null($customer_id)) {
            return new WP_Error( 'missing_customer', 'Customer identification required.', array( 'status' => 400 ) );
        }

        $card = MyPerk_Loyalty::get_customer_card( $business_id, $customer_id );

        if ( ! $card ) {
            return new WP_Error( 'not_found', 'Loyalty card not found.', array( 'status' => 404 ) );
        }

        return new WP_REST_Response( $card, 200 );
    }

    // ==========================================
    // REWARD REDEMPTION HANDLER
    // ==========================================

    public function redeem_reward( WP_REST_Request $request ) {
        global $wpdb;
        
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        $customer_id = null;
        
        // Look up customer by phone for customer role
        if ($role === MYPERK_ROLE_CUSTOMER) {
            $phone = $auth['data']['phone'] ?? null;
            if ($phone) {
                $customer_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE phone = %s", 
                    $phone
                ));
            }
        } else {
            // For staff/business/admin: require customer_id in query params
            $query = $request->get_params();
            if (!empty($query['customer_id'])) {
                $customer_id = intval($query['customer_id']);
            }
        }
        
        if (is_null($customer_id)) {
            return new WP_Error( 'missing_customer', 'Customer identification required.', array( 'status' => 400 ) );
        }
        
        // Get reward_id from request body or params
        $reward_id = intval( $request->get_param( 'reward_id' ) );
        if (is_null($reward_id)) {
            return new WP_Error( 'missing_reward', 'Reward ID required.', array( 'status' => 400 ) );
        }
        
        $business_id = intval( $request->get_param( 'business_id' ) );
        if (is_null($business_id)) {
            return new WP_Error( 'missing_business', 'Business ID required.', array( 'status' => 400 ) );
        }
        
        // Verify business access
        if ($role !== MYPERK_ROLE_SUPER_ADMIN && $auth_business_id !== $business_id) {
            return new WP_Error( 'forbidden', 'Cannot redeem reward for another business.', array( 'status' => 403 ) );
        }
        
        // Verify reward exists and is active
        $reward = $wpdb->get_row($wpdb->prepare(
            "SELECT id, business_id, name, description, stamp_cost, reward_type, reward_value, max_redemptions, is_active, current_redemptions
            FROM {$wpdb->prefix}loyalty_rewards
            WHERE id = %d AND business_id = %d AND is_active = 1",
            $reward_id, $business_id
        ));
        
        if (!$reward) {
            return new WP_Error( 'reward_not_found', 'Reward not found or not available.', array( 'status' => 404 ) );
        }
        
        // Check if customer has enough stamps
        $card = $wpdb->get_row($wpdb->prepare(
            "SELECT stamp_count FROM {$wpdb->prefix}loyalty_cards WHERE business_id = %d AND customer_id = %d AND status = 'active'",
            $business_id, $customer_id
        ));
        
        if (!$card) {
            return new WP_Error( 'card_not_found', 'Loyalty card not found.', array( 'status' => 404 ) );
        }
        
        if ($card->stamp_count < $reward->stamp_cost) {
            return new WP_Error( 'not_enough_stamps', 'Not enough stamps. Required: ' . $reward->stamp_cost . ', Available: ' . $card->stamp_count, array( 'status' => 400 ) );
        }
        
        // Check max redemptions
        if ($reward->max_redemptions && $reward->current_redemptions >= $reward->max_redemptions) {
            return new WP_Error( 'max_redemptions', 'Maximum redemptions reached for this reward.', array( 'status' => 400 ) );
        }
        
        // Deduct stamps and record redemption in a transaction
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}loyalty_cards SET stamp_count = stamp_count - %d WHERE id = %d AND business_id = %d AND customer_id = %d",
            $reward->stamp_cost, $card->id, $business_id, $customer_id
        ));
        
        // Increment current_redemptions
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}loyalty_rewards SET current_redemptions = current_redemptions + 1 WHERE id = %d AND business_id = %d",
            $reward_id, $business_id
        ));
        
        // Record the redemption in the ledger
        $wpdb->insert(
            $wpdb->prefix . 'loyalty_ledger',
            array(
                'business_id'    => $business_id,
                'customer_id'    => $customer_id,
                'actor_id'       => $auth_business_id ?? 0,
                'actor_type'     => 'owner',
                'delta'          => -$reward->stamp_cost,
                'balance_after'  => $card->stamp_count - $reward->stamp_cost,
                'transaction_type' => 'reward_redeemed',
                'reference_id'   => $reward_id,
                'notes'          => 'Reward redemption: ' . $reward->name,
                'created_at'     => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d' )
        );
        
        return new WP_REST_Response( array(
            'success'       => true,
            'reward_id'     => $reward_id,
            'reward_name'   => $reward->name,
            'stamps_deducted' => $reward->stamp_cost,
            'stamps_remaining' => $card->stamp_count - $reward->stamp_cost,
            'message'       => 'Reward redeemed successfully!',
        ), 200 );
    }

    // ==========================================
    // STAFF ENDPOINT HANDLERS
    // ==========================================

    public function get_pending_requests( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "DEBUG_GPA: auth authenticated=" . ($auth['authenticated'] ? 'true' : 'false') . "\n", FILE_APPEND);
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        $business_id = intval( $request->get_param( 'business_id' ) );
        file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "DEBUG_GPA: business_id from param=" . $business_id . "\n", FILE_APPEND);
        // Use auth business_id if not provided in query params
        if (is_null($business_id) || $business_id === 0) {
            $business_id = $auth['data']['business_id'] ?? 0;
            file_put_contents('D:\\MyPerk-New\\myperk-backend\\debug.log', "DEBUG_GPA: business_id from auth=" . $business_id . "\n", FILE_APPEND);
        }
        $auth_business_id = $auth['data']['business_id'] ?? 'null';
        $auth_role = $auth['data']['role'] ?? 'null';
        file_put_contents('D:\\\\MyPerk-New\\\\myperk-backend\\\\debug.log', "DEBUG_GPR: business_id from fix=$business_id, auth_business_id=$auth_business_id, role=$auth_role, branch_id=" . intval($request->get_param('branch_id')) . "\n", FILE_APPEND);
        $branch_id_param = $request->get_param( 'branch_id' );
        $branch_id = ($branch_id_param !== null && $branch_id_param !== '') ? intval($branch_id_param) : null;
        $limit       = intval( $request->get_param( 'limit' ) );
        
        // Default limit if not provided or zero
        if ($limit === 0) {
            $limit = 50;
        }
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        // Verify business access using the filter (which now uses standalone auth)
        $staff_can_process = apply_filters( 'myperk_staff_can_process', false, 0, $business_id, $branch_id );
        file_put_contents('D:\\\\MyPerk-New\\\\myperk-backend\\\\debug.log', "DEBUG_GPR: staff_can_process=" . ($staff_can_process ? 'true' : 'false') . "\n", FILE_APPEND);
        
        if ( ! $staff_can_process ) {
            // Additional check: if super_admin, allow access
            if ($role !== MYPERK_ROLE_SUPER_ADMIN) {
                return new WP_Error( 'unauthorized', 'Not authorized for this business.', array( 'status' => 403 ) );
            }
        }

        try {
            $requests = MyPerk_Loyalty::get_pending_requests( $business_id, $branch_id, $limit );
            file_put_contents('D:\\\\MyPerk-New\\\\myperk-backend\\\\debug.log', "DEBUG_GPA: MyPerk_Loyalty::get_pending_requests completed successfully, count=" . count($requests) . "\n", FILE_APPEND);
            file_put_contents('D:\\\\MyPerk-New\\\\myperk-backend\\\\debug.log', "DEBUG_GPA: requests=" . json_encode(array_slice($requests, 0, 3)) . "\n", FILE_APPEND);
        } catch (Exception $e) {
            file_put_contents('D:\\\\MyPerk-New\\\\myperk-backend\\\\debug.log', "DEBUG_GPA: EXCEPTION in get_pending_requests: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n", FILE_APPEND);
            return new WP_Error( 'db_error', $e->getMessage(), array( 'status' => 500 ) );
        }
        
        return new WP_REST_Response( array( 'requests' => $requests ), 200 );
    }

    public function process_stamp_request( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        $request_id     = intval( $request->get_param( 'id' ) );
        $decision       = $request->get_param( 'decision' );
        $approved_stamps = intval( $request->get_param( 'approved_stamps' ) );
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        // For standalone mode, use a staff identifier from the auth
        $staff_id = $auth['data']['user_id'] ?? 0;
        if ($staff_id === 0 && $role !== MYPERK_ROLE_SUPER_ADMIN) {
            // In standalone mode, we can use business_id as staff identifier
            $staff_id = $auth_business_id ?? 0;
        }
        
        $result = MyPerk_Loyalty::process_stamp_request( $request_id, $staff_id, $decision, $approved_stamps );

        if ( is_wp_error( $result ) ) {
            return new WP_Error( $result->get_error_code(), $result->get_error_message(), array( 'status' => 400 ) );
        }

        return new WP_REST_Response( $result, 200 );
    }

    // ==========================================
    // BUSINESS OWNER ENDPOINT HANDLERS
    // ==========================================

    public function get_business_dashboard( WP_REST_Request $request ) {
        global $wpdb;
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        $business_id = intval( $request->get_param( 'id' ) );
        
        // Check access
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        if ($role !== MYPERK_ROLE_SUPER_ADMIN && $auth_business_id !== $business_id) {
            return new WP_Error( 'forbidden', 'Cannot access another business dashboard.', array( 'status' => 403 ) );
        }

        // Key metrics
        $metrics = array(
            'total_members'       => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_cards WHERE business_id = %d AND status = 'active'", $business_id ) ),
            'stamps_30d'          => $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(delta),0) FROM {$wpdb->prefix}loyalty_ledger WHERE business_id = %d AND transaction_type = 'stamp_approved' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)", $business_id ) ),
            'rewards_redeemed_30d'=> $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_ledger WHERE business_id = %d AND transaction_type = 'reward_redeemed' AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)", $business_id ) ),
            'pending_requests'    => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_stamp_requests WHERE business_id = %d AND status = 'pending'", $business_id ) ),
        );

        // Recent activity
        $activity = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.*, c.full_name as customer_name, br.branch_name
            FROM {$wpdb->prefix}loyalty_ledger l
            JOIN {$wpdb->prefix}loyalty_customers c ON c.id = l.customer_id
            LEFT JOIN {$wpdb->prefix}loyalty_branches br ON br.id = l.branch_id
            WHERE l.business_id = %d
            ORDER BY l.created_at DESC LIMIT 20",
            $business_id
        ) );

        // Subscription status
        $subscription = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}loyalty_subscriptions WHERE business_id = %d",
            $business_id
        ) );

        return new WP_REST_Response( array(
            'metrics'      => $metrics,
            'activity'     => $activity,
            'subscription' => $subscription,
        ), 200 );
    }

    public function get_business_settings( WP_REST_Request $request ) {
        global $wpdb;
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        $business_id = intval( $request->get_param( 'id' ) );
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        if ($role !== MYPERK_ROLE_SUPER_ADMIN && $auth_business_id !== $business_id) {
            return new WP_Error( 'forbidden', 'Cannot access another business settings.', array( 'status' => 403 ) );
        }

        $business = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}loyalty_businesses WHERE id = %d", $business_id ) );
        $branches = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}loyalty_branches WHERE business_id = %d AND status = 'active'", $business_id ) );

        return new WP_REST_Response( array( 'business' => $business, 'branches' => $branches ), 200 );
    }

    public function update_business_settings( WP_REST_Request $request ) {
        global $wpdb;
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        $business_id = intval( $request->get_param( 'id' ) );
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        if ($role !== MYPERK_ROLE_SUPER_ADMIN && $auth_business_id !== $business_id) {
            return new WP_Error( 'forbidden', 'Cannot update another business settings.', array( 'status' => 403 ) );
        }
        
        $data        = $request->get_json_params();

        $allowed = array( 'business_name', 'contact_email', 'contact_phone', 'address', 'timezone', 'currency', 'brand_color', 'description' );
        $update  = array();

        foreach ( $allowed as $field ) {
            if ( isset( $data[ $field ] ) ) {
                $update[ $field ] = sanitize_text_field( $data[ $field ] );
            }
        }

        if ( $update ) {
            $update['updated_at'] = current_time( 'mysql' );
            $wpdb->update( $wpdb->prefix . 'loyalty_businesses', $update, array( 'id' => $business_id ) );

            MyPerk_Security::log_audit( $business_id, $auth['data']['user_id'] ?? 0, 'owner', 'updated_settings', 'business', $business_id, $update );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    public function get_branches( WP_REST_Request $request ) {
        global $wpdb;
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        $business_id = intval( $request->get_param( 'id' ) );
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        if ($role !== MYPERK_ROLE_SUPER_ADMIN && $auth_business_id !== $business_id) {
            return new WP_Error( 'forbidden', 'Cannot access another business branches.', array( 'status' => 403 ) );
        }

        $branches = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}loyalty_branches WHERE business_id = %d ORDER BY created_at DESC", $business_id ) );
        return new WP_REST_Response( array( 'branches' => $branches ), 200 );
    }

    public function create_branch( WP_REST_Request $request ) {
        global $wpdb;
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        $business_id = intval( $request->get_param( 'id' ) );
        
        $role = $auth['data']['role'] ?? MYPERK_ROLE_ANONYMOUS;
        $auth_business_id = $auth['data']['business_id'] ?? null;
        
        if ($role !== MYPERK_ROLE_SUPER_ADMIN && $auth_business_id !== $business_id) {
            return new WP_Error( 'forbidden', 'Cannot create branch for another business.', array( 'status' => 403 ) );
        }
        
        $data        = $request->get_json_params();

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'loyalty_branches',
            array(
                'business_id'  => $business_id,
                'branch_name'  => sanitize_text_field( $data['branch_name'] ),
                'branch_code'  => sanitize_text_field( $data['branch_code'] ),
                'address'      => sanitize_textarea_field( $data['address'] ?? '' ),
                'phone'        => sanitize_text_field( $data['phone'] ?? '' ),
                'status'       => 'active',
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'insert_failed', 'Could not create branch.', array( 'status' => 400 ) );
        }

        $branch_id = $wpdb->insert_id;
        MyPerk_Security::log_audit( $business_id, $auth['data']['user_id'] ?? 0, 'owner', 'created_branch', 'branch', $branch_id, array() );

        return new WP_REST_Response( array( 'success' => true, 'branch_id' => $branch_id ), 201 );
    }

    // ==========================================
    // ADMIN ENDPOINT HANDLERS
    // ==========================================

    public function get_admin_dashboard( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        global $wpdb;

        $metrics = array(
            'total_businesses'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_businesses" ),
            'active_subscriptions' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_subscriptions WHERE status = 'active'" ),
            'trialing_businesses'  => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_businesses WHERE status = 'trialing'" ),
            'total_stamps_issued'  => $wpdb->get_var( "SELECT COALESCE(SUM(delta),0) FROM {$wpdb->prefix}loyalty_ledger WHERE transaction_type = 'stamp_approved'" ),
        );

        return new WP_REST_Response( array( 'metrics' => $metrics ), 200 );
    }

    public function get_all_businesses( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        global $wpdb;
        $page = intval( $request->get_param( 'page' ) ) ?: 1;
        $per_page = 20;
        $offset = ( $page - 1 ) * $per_page;

        $businesses = $wpdb->get_results( $wpdb->prepare(
            "SELECT b.*, s.status as sub_status, s.plan_code,
            (SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_cards WHERE business_id = b.id AND status = 'active') as member_count
            FROM {$wpdb->prefix}loyalty_businesses b
            LEFT JOIN {$wpdb->prefix}loyalty_subscriptions s ON s.business_id = b.id
            ORDER BY b.created_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ) );

        return new WP_REST_Response( array( 'businesses' => $businesses, 'page' => $page ), 200 );
    }

    public function reconcile_subscriptions( WP_REST_Request $request ) {
        $auth = $this->get_auth();
        if (!$auth['authenticated']) {
            return new WP_Error( 'unauthorized', 'Authentication required.', array( 'status' => 401 ) );
        }
        
        // Trigger subscription sync with payment provider
        // This would call MyPerk_Billing::sync_all_subscriptions()
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Reconciliation queued.' ), 200 );
    }

    // ==========================================
    // HELPERS
    // ==========================================

    private function get_customer_id_for_user( $user_id ) {
        global $wpdb;
        // Link WP user to loyalty customer via email or meta
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_customers WHERE wp_user_id = %d",
            $user_id
        ) );
    }
}