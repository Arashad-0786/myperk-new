<?php
/**
 * Core loyalty engine for MyPerk.
 * Handles QR verification, stamp requests, card management, and reward redemption.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPerk_Loyalty {

    /**
     * Verify a QR code hash and return branch information.
     *
     * @param string $qr_hash The QR hash to verify.
     * @return array|WP_Error {'valid': bool, 'business_id': int, 'business_name': string, 'business_slug': string, 'branch_id': int, 'branch_name': string, 'message': string}
     */
    public static function verify_qr( $qr_hash ) {
        global $wpdb;

        $qr_hash = sanitize_text_field( $qr_hash );

        // Look up the branch by QR hash
        $branch = $wpdb->get_row( $wpdb->prepare(
            "SELECT b.id as branch_id, b.branch_name, b.business_id, biz.business_name, biz.slug as business_slug, biz.status as business_status
            FROM {$wpdb->prefix}loyalty_branches b
            JOIN {$wpdb->prefix}loyalty_businesses biz ON b.business_id = biz.id
            WHERE b.branch_code = %s AND biz.status = 'active'",
            $qr_hash
        ) );

        if ( ! $branch ) {
            return new WP_Error( 'invalid_qr', 'Invalid or expired QR code.' );
        }

        // Audit log
        MyPerk_Security::log_audit(
            $branch->business_id,
            null,
            'qr_scan',
            'qr_verify',
            null,
            array( 'branch_id' => $branch->branch_id, 'qr_hash' => $qr_hash )
        );

        return array(
            'valid'        => true,
            'business_id'  => $branch->business_id,
            'business_name'=> $branch->business_name,
            'business_slug'=> $branch->business_slug,
            'branch_id'    => $branch->branch_id,
            'branch_name'  => $branch->branch_name,
            'message'      => 'QR verified successfully.',
        );
    }

    /**
     * Create a new stamp request.
     *
     * @param array $args {'business_id': int, 'branch_id': int, 'customer_id': int, 'qr_hash': string, 'requested_stamps': int}
     * @return array|WP_Error {'success': bool, 'request_id': int, 'message': string}
     */
    public static function create_stamp_request( $args ) {
        global $wpdb;

        $business_id    = intval( $args['business_id'] );
        $branch_id      = intval( $args['branch_id'] );
        $customer_id    = intval( $args['customer_id'] );
        $qr_hash        = sanitize_text_field( $args['qr_hash'] );
        $requested_stamps = intval( $args['requested_stamps'] ?: 1 );

        // Verify customer has a card for this business
        $card = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, stamp_count FROM {$wpdb->prefix}loyalty_cards
            WHERE business_id = %d AND customer_id = %d AND status = 'active'",
            $business_id, $customer_id
        ) );

        if ( ! $card ) {
            return new WP_Error( 'no_card', 'Customer has not joined this loyalty program.' );
        }

        // Check for duplicate pending request (anti-spam)
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}loyalty_stamp_requests
            WHERE business_id = %d AND customer_id = %d AND status = 'pending'
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            $business_id, $customer_id
        ) );

        if ( $existing ) {
            return new WP_Error( 'duplicate_request', 'A stamp request is already pending. Please wait for staff review.' );
        }

        // Check request limit per day
        $daily_requests = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_stamp_requests
            WHERE customer_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $customer_id
        ) );

        if ( $daily_requests > 10 ) {
            return new WP_Error( 'daily_limit', 'Daily request limit reached. Try again tomorrow.' );
        }

        // Insert request
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'loyalty_stamp_requests',
            array(
                'business_id'      => $business_id,
                'branch_id'        => $branch_id,
                'customer_id'      => $customer_id,
                'requested_stamps' => $requested_stamps,
                'status'           => 'pending',
                'qr_hash'          => $qr_hash,
                'created_at'       => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%d', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'insert_failed', 'Could not create stamp request.' );
        }

        $request_id = $wpdb->insert_id;

        // Audit log
        MyPerk_Security::log_audit(
            $business_id,
            $customer_id,
            'customer',
            'stamp_request_created',
            'stamp_request',
            $request_id,
            array(
                'branch_id'        => $branch_id,
                'requested_stamps' => $requested_stamps,
                'qr_hash'          => $qr_hash,
            )
        );

        return array(
            'success'      => true,
            'request_id'   => $request_id,
            'message'      => 'Stamp request submitted. Waiting for staff approval.',
        );
    }

    /**
     * Process (approve/reject) a stamp request.
     *
     * @param int $request_id The stamp request ID.
     * @param int $staff_id The staff member ID processing the request.
     * @param string $decision 'approve' or 'reject'.
     * @param int $approved_stamps Number of stamps to approve.
     * @return array|WP_Error
     */
    public static function process_stamp_request( $request_id, $staff_id, $decision, $approved_stamps ) {
        global $wpdb;

        // Validate decision
        if ( $decision !== 'approve' && $decision !== 'reject' ) {
            return new WP_Error( 'invalid_decision', 'Invalid decision. Must be \'approve\' or \'reject\'.' );
        }

        // Get the stamp request details
        $request = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, business_id, customer_id, branch_id, requested_stamps, status, qr_hash FROM {$wpdb->prefix}loyalty_stamp_requests WHERE id = %d",
            $request_id
        ) );

        if ( ! $request ) {
            return new WP_Error( 'not_found', 'Stamp request not found.' );
        }

        if ( $request->status !== 'pending' ) {
            return new WP_Error( 'invalid_status', 'Stamp request is not pending.' );
        }

        // Check if already processed today (using reference_id instead of request_id)
        $already_processed = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_ledger WHERE reference_id = %d AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            $request_id
        ) );

        if ( $already_processed > 0 && $decision === 'approve' ) {
            return new WP_Error( 'already_processed', 'This request has already been processed today.' );
        }

        // Get the customer's loyalty card for this business
        $card = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, stamp_count FROM {$wpdb->prefix}loyalty_cards WHERE customer_id = %d AND business_id = %d",
            $request->customer_id,
            $request->business_id
        ) );

        $current_stamps = $card ? $card->stamp_count : 0;
        $card_id = $card ? $card->id : null;

        // For approve, calculate new balance
        $new_stamp_count = $current_stamps;
        if ( $decision === 'approve' ) {
            $new_stamp_count = $current_stamps + $approved_stamps;
        }
        $delta = $decision === 'approve' ? $approved_stamps : -$approved_stamps;

        // Update the stamp request status
        $updated = $wpdb->update(
            $wpdb->prefix . 'loyalty_stamp_requests',
            array( 
                'status' => $decision === 'approve' ? 'approved' : 'rejected',
                'approved_stamps' => $decision === 'approve' ? $approved_stamps : 0,
                'processed_at' => current_time( 'mysql' ),
                'staff_id' => $staff_id,
            ),
            array( 'id' => $request_id ),
            array( '%s', '%d', '%s', '%d' )
        );

        if ( ! $updated ) {
            return new WP_Error( 'update_failed', 'Could not update stamp request status.' );
        }

        // Add ledger entry for stamps - match actual database schema
        $ledger_inserted = $wpdb->insert(
            $wpdb->prefix . 'loyalty_ledger',
            array(
                'business_id'      => $request->business_id,
                'customer_id'      => $request->customer_id,
                'card_id'          => $card_id ?? 0,
                'branch_id'        => $request->branch_id,
                'actor_id'         => $staff_id,
                'actor_type'       => 'staff',
                'delta'            => $delta,
                'balance_after'    => $new_stamp_count,
                'transaction_type' => $decision === 'approve' ? 'stamp_approved' : 'stamp_rejected',
                'reference_id'     => $request_id,
                'notes'            => $decision === 'approve' ? 'Stamp request approved' : 'Stamp request rejected',
            ),
            array( '%d', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%d', '%s' )
        );

        if ( ! $ledger_inserted ) {
            return new WP_Error( 'ledger_insert_failed', 'Could not create ledger entry.' );
        }

        // Update customer's stamp count in loyalty_cards
        if ( $decision === 'approve' && $card_id ) {
            $updated_card = $wpdb->update(
                $wpdb->prefix . 'loyalty_cards',
                array( 'stamp_count' => $new_stamp_count ),
                array( 'id' => $card_id ),
                array( '%d' )
            );
        }

        // Audit log
        MyPerk_Security::log_audit(
            $request->business_id,
            $request->customer_id,
            'customer',
            'stamp_request_processed',
            'stamp_request',
            $request_id,
            array(
                'decision'         => $decision,
                'approved_stamps'  => $approved_stamps,
                'request_id'       => $request_id,
            )
        );

        return array(
            'success'      => true,
            'request_id'   => $request_id,
            'decision'     => $decision,
            'approved_stamps' => $approved_stamps,
            'message'      => 'Stamp request ' . ($decision === 'approve' ? 'approved' : 'rejected') . '.',
        );
    }

    /**
     * Get pending stamp requests for a business.
     *
     * @param int $business_id The business ID.
     * @param int $branch_id The branch ID (optional).
     * @param int $limit Maximum number of requests to return.
     * @return array Pending stamp requests.
     */
    public static function get_pending_requests( $business_id, $branch_id = null, $limit = 50 ) {
        global $wpdb;

        $sql = "SELECT sr.id, sr.customer_id, c.full_name, sr.requested_stamps, sr.status, sr.qr_hash, sr.created_at
                FROM {$wpdb->prefix}loyalty_stamp_requests sr
                JOIN {$wpdb->prefix}loyalty_customers c ON c.id = sr.customer_id
                WHERE sr.business_id = %d AND sr.status = 'pending'";

        $params = [$business_id];

        if ( $branch_id !== null ) {
            $sql .= " AND sr.branch_id = %d";
            $params[] = $branch_id;
        }

        $sql .= " ORDER BY sr.created_at DESC LIMIT " . intval( $limit );

        $requests = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );

        $result = [];
        foreach ( $requests as $r ) {
            $result[] = array(
                'id'            => $r->id,
                'customer_id'   => $r->customer_id,
                'customer_name' => $r->full_name,
                'requested_stamps' => $r->requested_stamps,
                'status'        => $r->status,
                'qr_hash'       => $r->qr_hash,
                'created_at'    => $r->created_at,
            );
        }

        return $result;
    }

    /**
     * Get customer card details.
     *
     * @param int $business_id The business ID.
     * @param int $customer_id The customer ID.
     * @return array|WP_Error Customer card details including stamp count.
     */
    public static function get_customer_card( $business_id, $customer_id ) {
        global $wpdb;

        // Get the card
        $card = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, stamp_count, business_id, customer_id, status FROM {$wpdb->prefix}loyalty_cards
            WHERE business_id = %d AND customer_id = %d AND status = 'active'",
            $business_id, $customer_id
        ) );

        if ( ! $card ) {
            return new WP_Error( 'not_found', 'Loyalty card not found.' );
        }

        // Get customer details
        $customer = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, full_name, phone, status FROM {$wpdb->prefix}loyalty_customers WHERE id = %d",
            $customer_id
        ) );

        return array(
            'id'          => $card->id,
            'stamp_count' => $card->stamp_count,
            'business_id' => $card->business_id,
            'customer_id' => $card->customer_id,
            'status'      => $card->status,
            'customer'    => $customer,
        );
    }
}