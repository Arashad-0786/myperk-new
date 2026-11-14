<?php
/**
 * Security and Permission management for MyPerk Core.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPerk_Security {

    /**
     * Check if current user has permission for a specific business.
     */
    public static function user_can_access_business( $user_id, $business_id ) {
        if ( current_user_can( 'administrator' ) ) {
            return true;
        }

        global $wpdb;
        // Check if user is owner or staff associated with this business
        // Implementation links WP user ID to business owners/staff roles.
        $is_owner = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_businesses WHERE id = %d AND contact_email = (SELECT user_email FROM {$wpdb->users} WHERE ID = %d)",
            $business_id,
            $user_id
        ) );

        if ( $is_owner > 0 ) {
            return true;
        }

        return apply_filters( 'myperk_user_can_access_business', false, $user_id, $business_id );
    }

    /**
     * Audit log helper.
     */
    public static function log_audit( $business_id, $actor_id, $actor_type, $action, $target_type = '', $target_id = null, $meta = array() ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'loyalty_audit_log',
            array(
                'business_id' => $business_id,
                'actor_id'    => $actor_id,
                'actor_type'  => $actor_type,
                'action'      => $action,
                'target_type' => $target_type,
                'target_id'   => $target_id,
                'meta_json'   => wp_json_encode( $meta ),
                'ip_address'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
        );
    }
}
