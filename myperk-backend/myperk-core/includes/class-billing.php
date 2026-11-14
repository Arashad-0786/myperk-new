<?php
/**
 * Billing and Webhook Handler for MyPerk Core.
 * Supports: WooCommerce + Razorpay (Option A) and Direct Razorpay (Option B).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPerk_Billing {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_webhook_routes' ) );
        
        // WooCommerce order status hooks (Option A path)
        if ( class_exists( 'WooCommerce' ) ) {
            add_action( 'woocommerce_order_status_completed', array( $this, 'handle_wc_order_completed' ), 10, 2 );
            add_action( 'woocommerce_order_status_on-hold', array( $this, 'handle_wc_order_on_hold' ), 10, 2 );
            add_action( 'woocommerce_order_status_refunded', array( $this, 'handle_wc_order_refunded' ), 10, 2 );
        }
    }

    /**
     * Register REST API webhook endpoints.
     */
    public function register_webhook_routes() {
        $namespace = 'myperk/v1';

        // Direct Razorpay webhook (Option B)
        register_rest_route( $namespace, '/billing/webhook/razorpay', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_razorpay_webhook' ),
            'permission_callback' => '__return_true',
        ) );

        // Generic webhook endpoint (for future providers)
        register_rest_route( $namespace, '/billing/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'handle_generic_webhook' ),
            'permission_callback' => '__return_true',
        ) );
    }

    // ==========================================
    // WOocommerce Hooks (Option A: WooCommerce + Razorpay)
    // ==========================================

    /**
     * Handle WooCommerce order completion - payment verified by WC Razorpay plugin.
     */
    public function handle_wc_order_completed( $order_id, $order ) {
        $this->process_wc_subscription_order( $order_id, 'active' );
    }

    /**
     * Handle WooCommerce order on-hold - pending payment.
     */
    public function handle_wc_order_on_hold( $order_id, $order ) {
        $this->process_wc_subscription_order( $order_id, 'pending' );
    }

    /**
     * Handle WooCommerce order refunded.
     */
    public function handle_wc_order_refunded( $order_id, $order ) {
        $this->process_wc_subscription_order( $order_id, 'refunded' );
    }

    /**
     * Core processor for WooCommerce subscription orders.
     */
    private function process_wc_subscription_order( $order_id, $new_status ) {
        global $wpdb;

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        // Verify this is a MyPerk subscription order
        $business_id = $order->get_meta( '_myperk_business_id' );
        $plan_code   = $order->get_meta( '_myperk_plan_code' );

        if ( ! $business_id || ! $plan_code ) {
            return; // Not a MyPerk order
        }

        $business_id = intval( $business_id );

        // Determine subscription status from order status
        $status_map = array(
            'completed'   => 'active',
            'processing'  => 'active',
            'on-hold'     => 'pending',
            'pending'     => 'pending',
            'refunded'    => 'cancelled',
            'cancelled'   => 'cancelled',
            'failed'      => 'cancelled',
        );

        $subscription_status = $status_map[ $new_status ] ?? 'pending';

        // Update or create subscription record
        $this->update_subscription_record( $business_id, array(
            'plan_code'             => $plan_code,
            'status'                => $subscription_status,
            'provider_order_id'     => (string) $order_id,
            'provider_subscription_id' => $order->get_meta( '_razorpay_subscription_id' ) ?: '',
            'current_period_end'    => $this->calculate_period_end( $order ),
        ) );

        // If activated, create invoice
        if ( 'active' === $subscription_status ) {
            $this->create_invoice_for_order( $order, $business_id );
        }

        // Audit log
        MyPerk_Security::log_audit(
            $business_id,
            0,
            'system',
            'wc_order_status_change',
            'subscription',
            $business_id,
            array(
                'order_id'      => $order_id,
                'order_status'  => $new_status,
                'sub_status'    => $subscription_status,
            )
        );
    }

    // ==========================================
    // Direct Razorpay Webhook Handler (Option B)
    // ==========================================

    /**
     * Handle incoming Razorpay webhooks directly.
     */
    public function handle_razorpay_webhook( WP_REST_Request $request ) {
        // Verify signature
        $signature = $request->get_header( 'x-razorpay-signature' );
        $payload   = $request->get_body();

        if ( ! $this->verify_razorpay_signature( $payload, $signature ) ) {
            return new WP_Error( 'invalid_signature', 'Webhook signature verification failed.', array( 'status' => 401 ) );
        }

        $event = json_decode( $payload, true );
        if ( ! $event || empty( $event['event'] ) ) {
            return new WP_Error( 'invalid_payload', 'Invalid webhook payload.', array( 'status' => 400 ) );
        }

        // Record event idempotently
        $recorded = $this->record_webhook_event( 'razorpay', $event );
        if ( is_wp_error( $recorded ) ) {
            // Duplicate event - acknowledge but don't process
            return new WP_REST_Response( array( 'status' => 'duplicate' ), 200 );
        }

        // Process based on event type
        $result = $this->process_razorpay_event( $event );

        // Mark as processed
        $this->mark_webhook_processed( $recorded, $result );

        return new WP_REST_Response( array( 'status' => 'success' ), 200 );
    }

    /**
     * Generic webhook handler for other providers.
     */
    public function handle_generic_webhook( WP_REST_Request $request ) {
        return new WP_REST_Response( array( 'status' => 'not_implemented' ), 501 );
    }

    /**
     * Verify Razorpay webhook signature.
     */
    private function verify_razorpay_signature( $payload, $signature ) {
        $secret = get_option( 'myperk_razorpay_webhook_secret' );
        if ( ! $secret ) {
            return false;
        }

        $expected = hash_hmac( 'sha256', $payload, $secret );
        return hash_equals( $expected, $signature );
    }

    /**
     * Record webhook event idempotently.
     */
    private function record_webhook_event( $provider, $event ) {
        global $wpdb;

        $event_id = $event['event'] . '_' . ( $event['payload']['payment']['entity']['id'] ?? $event['payload']['subscription']['entity']['id'] ?? uniqid() );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'loyalty_webhook_events',
            array(
                'provider'           => $provider,
                'provider_event_id'  => $event_id,
                'event_type'         => $event['event'],
                'payload_json'       => wp_json_encode( $event ),
                'signature_valid'    => 1,
                'status'             => 'received',
                'created_at'         => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
        );

        if ( ! $inserted ) {
            // Check if duplicate
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}loyalty_webhook_events WHERE provider = %s AND provider_event_id = %s",
                $provider, $event_id
            ) );

            if ( $existing ) {
                return new WP_Error( 'duplicate', 'Event already processed.', array( 'id' => $existing ) );
            }
            return new WP_Error( 'insert_failed', 'Could not record webhook event.' );
        }

        return $wpdb->insert_id;
    }

    /**
     * Process Razorpay event types.
     */
    private function process_razorpay_event( $event ) {
        $event_type = $event['event'];
        $payload    = $event['payload'];

        switch ( $event_type ) {
            case 'payment.captured':
                return $this->handle_payment_captured( $payload );
            case 'payment.failed':
                return $this->handle_payment_failed( $payload );
            case 'subscription.charged':
                return $this->handle_subscription_charged( $payload );
            case 'subscription.completed':
            case 'subscription.cancelled':
            case 'subscription.paused':
                return $this->handle_subscription_status_change( $payload, $event_type );
            default:
                return array( 'status' => 'ignored', 'event' => $event_type );
        }
    }

    /**
     * Handle successful payment capture.
     */
    private function handle_payment_captured( $payload ) {
        $payment = $payload['payment']['entity'];
        $order_id = $payment['order_id'] ?? '';

        // Find business by order_id (stored in notes or metadata)
        $business_id = $this->get_business_id_from_razorpay_order( $order_id );
        if ( ! $business_id ) {
            return array( 'status' => 'no_business_found' );
        }

        // Activate subscription
        $this->update_subscription_record( $business_id, array(
            'status'                => 'active',
            'provider_payment_id'   => $payment['id'],
            'provider_order_id'     => $order_id,
            'current_period_end'    => $this->calculate_period_end_from_now( 'year' ),
        ) );

        // Create invoice
        $this->create_invoice_from_payment( $business_id, $payment );

        return array( 'status' => 'processed', 'action' => 'subscription_activated' );
    }

    /**
     * Handle failed payment.
     */
    private function handle_payment_failed( $payload ) {
        $payment = $payload['payment']['entity'];
        $order_id = $payment['order_id'] ?? '';

        $business_id = $this->get_business_id_from_razorpay_order( $order_id );
        if ( ! $business_id ) {
            return array( 'status' => 'no_business_found' );
        }

        // Mark subscription as past_due or trigger grace
        $this->update_subscription_record( $business_id, array(
            'status' => 'past_due',
        ) );

        // Schedule grace period reminders
        $this->schedule_grace_reminders( $business_id );

        return array( 'status' => 'processed', 'action' => 'payment_failed' );
    }

    /**
     * Handle subscription charged (recurring).
     */
    private function handle_subscription_charged( $payload ) {
        $subscription = $payload['subscription']['entity'];
        $payment_id   = $subscription['charge_id'] ?? '';

        $business_id = $this->get_business_id_from_razorpay_subscription( $subscription['id'] );
        if ( ! $business_id ) {
            return array( 'status' => 'no_business_found' );
        }

        // Extend subscription period
        $this->extend_subscription_period( $business_id );

        // Create invoice
        if ( $payment_id ) {
            $payment = array( 'id' => $payment_id, 'amount' => $subscription['charge_amount'] ?? 0 );
            $this->create_invoice_from_payment( $business_id, $payment );
        }

        return array( 'status' => 'processed', 'action' => 'subscription_renewed' );
    }

    /**
     * Handle subscription status changes.
     */
    private function handle_subscription_status_change( $payload, $event_type ) {
        $subscription = $payload['subscription']['entity'];
        $business_id  = $this->get_business_id_from_razorpay_subscription( $subscription['id'] );

        if ( ! $business_id ) {
            return array( 'status' => 'no_business_found' );
        }

        $status_map = array(
            'subscription.completed' => 'cancelled',
            'subscription.cancelled' => 'cancelled',
            'subscription.paused'    => 'paused',
        );

        $new_status = $status_map[ $event_type ] ?? 'cancelled';

        $this->update_subscription_record( $business_id, array( 'status' => $new_status ) );

        return array( 'status' => 'processed', 'action' => $new_status );
    }

    // ==========================================
    // SUBSCRIPTION MANAGEMENT HELPERS
    // ==========================================

    /**
     * Update subscription record in database.
     */
    private function update_subscription_record( $business_id, $data ) {
        global $wpdb;

        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_subscriptions WHERE business_id = %d",
            $business_id
        ) );

        $allowed = array( 'plan_code', 'status', 'billing_period', 'provider_subscription_id', 'provider_order_id', 'provider_payment_id', 'current_period_end' );
        $update = array();

        foreach ( $allowed as $field ) {
            if ( isset( $data[ $field ] ) ) {
                $update[ $field ] = $data[ $field ];
            }
        }

        if ( ! $update ) {
            return false;
        }

        $update['updated_at'] = current_time( 'mysql' );

        if ( $existing ) {
            $wpdb->update( $wpdb->prefix . 'loyalty_subscriptions', $update, array( 'business_id' => $business_id ) );
        } else {
            $update['business_id'] = $business_id;
            $update['created_at']  = current_time( 'mysql' );
            $wpdb->insert( $wpdb->prefix . 'loyalty_subscriptions', $update );
        }

        return true;
    }

    /**
     * Create invoice record from WooCommerce order.
     */
    private function create_invoice_for_order( $order, $business_id ) {
        global $wpdb;

        $subscription = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_subscriptions WHERE business_id = %d",
            $business_id
        ) );

        if ( ! $subscription ) {
            return;
        }

        $amount_paise = (int) ( $order->get_total() * 100 );
        $tax_paise    = (int) ( $order->get_total_tax() * 100 );

        $invoice_number = 'INV-' . $business_id . '-' . date( 'Ymd' ) . '-' . wp_rand( 1000, 9999 );

        $wpdb->insert(
            $wpdb->prefix . 'loyalty_invoices',
            array(
                'public_id'          => 'INV-' . uniqid(),
                'business_id'        => $business_id,
                'subscription_id'    => $subscription->id,
                'invoice_number'     => $invoice_number,
                'amount_paise'       => $amount_paise,
                'tax_paise'          => $tax_paise,
                'currency'           => $order->get_currency(),
                'status'             => 'paid',
                'issued_at'          => current_time( 'mysql' ),
                'paid_at'            => current_time( 'mysql' ),
                'provider_order_id'  => (string) $order->get_id(),
                'provider_payment_id'=> $order->get_transaction_id(),
            ),
            array( '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Create invoice from Razorpay payment.
     */
    private function create_invoice_from_payment( $business_id, $payment ) {
        global $wpdb;

        $subscription = $wpdb->get_row( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_subscriptions WHERE business_id = %d",
            $business_id
        ) );

        if ( ! $subscription ) {
            return;
        }

        $amount_paise = isset( $payment['amount'] ) ? intval( $payment['amount'] ) : 0;

        $invoice_number = 'INV-' . $business_id . '-' . date( 'Ymd' ) . '-' . wp_rand( 1000, 9999 );

        $wpdb->insert(
            $wpdb->prefix . 'loyalty_invoices',
            array(
                'public_id'          => 'INV-' . uniqid(),
                'business_id'        => $business_id,
                'subscription_id'    => $subscription->id,
                'invoice_number'     => $invoice_number,
                'amount_paise'       => $amount_paise,
                'tax_paise'          => 0,
                'currency'           => 'INR',
                'status'             => 'paid',
                'issued_at'          => current_time( 'mysql' ),
                'paid_at'            => current_time( 'mysql' ),
                'provider_payment_id'=> $payment['id'],
            ),
            array( '%s', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Extend subscription period by one billing cycle.
     */
    private function extend_subscription_period( $business_id ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}loyalty_subscriptions
            SET current_period_start = current_period_end,
                current_period_end = DATE_ADD(current_period_end, INTERVAL 1 YEAR),
                status = 'active',
                updated_at = CURRENT_TIMESTAMP
            WHERE business_id = %d",
            $business_id
        ) );
    }

    /**
     * Calculate period end from WooCommerce order.
     */
    private function calculate_period_end( $order ) {
        // Default to 1 year from now for annual plans
        return date( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
    }

    private function calculate_period_end_from_now( $period = 'year' ) {
        return date( 'Y-m-d H:i:s', strtotime( "+1 {$period}" ) );
    }

    /**
     * Find business ID from Razorpay order ID (stored in order notes/meta).
     */
    private function get_business_id_from_razorpay_order( $order_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT business_id FROM {$wpdb->prefix}loyalty_subscriptions WHERE provider_order_id = %s",
            $order_id
        ) );
    }

    private function get_business_id_from_razorpay_subscription( $subscription_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT business_id FROM {$wpdb->prefix}loyalty_subscriptions WHERE provider_subscription_id = %s",
            $subscription_id
        ) );
    }

    /**
     * Schedule grace period reminders.
     */
    private function schedule_grace_reminders( $business_id ) {
        // Could use wp_schedule_single_event for day 1, 3, 7 reminders
        // Implementation depends on cron setup
    }

    /**
     * Mark webhook event as processed.
     */
    private function mark_webhook_processed( $event_id, $result ) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'loyalty_webhook_events',
            array(
                'status'        => is_wp_error( $result ) ? 'failed' : 'processed',
                'processed_at'  => current_time( 'mysql' ),
                'error_message' => is_wp_error( $result ) ? $result->get_error_message() : '',
            ),
            array( 'id' => $event_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );
    }

    // ==========================================
    // PUBLIC METHODS FOR ADMIN/EXTERNAL USE
    // ==========================================

    /**
     * Check if business has active subscription (entitlement guard).
     */
    public static function business_has_active_subscription( $business_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_subscriptions
            WHERE business_id = %d AND status IN ('trialing', 'active', 'grace')
            AND (grace_period_end IS NULL OR grace_period_end > NOW())",
            $business_id
        ) ) > 0;
    }

    /**
     * Get subscription details for a business.
     */
    public static function get_subscription( $business_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}loyalty_subscriptions WHERE business_id = %d",
            $business_id
        ) );
    }

    /**
     * Create billing order for business (called from API).
     */
    public static function create_billing_order( $business_id, $plan_code, $billing_period = 'year' ) {
        global $wpdb;

        // Verify business exists
        $business = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}loyalty_businesses WHERE id = %d", $business_id ) );
        if ( ! $business ) {
            return new WP_Error( 'not_found', 'Business not found.' );
        }

        // Pricing logic (would be configurable)
        $pricing = array(
            'starter' => array( 'year' => 99900, 'month' => 9990 ),
            'growth'  => array( 'year' => 299900, 'month' => 29990 ),
            'pro'     => array( 'year' => 799900, 'month' => 79990 ),
        );

        $amount_paise = $pricing[ $plan_code ][ $billing_period ] ?? 0;

        // Create pending subscription record
        self::update_subscription_record( $business_id, array(
            'plan_code'       => $plan_code,
            'billing_period'  => $billing_period,
            'status'          => 'pending_payment',
        ) );

        // Return checkout URL - would integrate with WC or Razorpay directly
        $checkout_url = apply_filters( 'myperk_billing_checkout_url', '', $business_id, $plan_code, $amount_paise );

        return array(
            'success'      => true,
            'checkout_url' => $checkout_url,
            'amount_paise' => $amount_paise,
        );
    }
}

// Helper function for external use
function myperk_update_subscription_record( $business_id, $data ) {
    MyPerk_Billing::get_instance()->update_subscription_record( $business_id, $data );
}