<?php
/**
 * Template Loader & Shortcode Engine for MyPerk Core.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPerk_Templates {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_shortcode( 'myperk_customer_dashboard', array( $this, 'render_customer_dashboard' ) );
        add_shortcode( 'myperk_staff_dashboard', array( $this, 'render_staff_dashboard' ) );
        add_shortcode( 'myperk_business_dashboard', array( $this, 'render_business_dashboard' ) );
        add_shortcode( 'myperk_admin_dashboard', array( $this, 'render_admin_dashboard' ) );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'myperk-design-system', MYPERK_PLUGIN_URL . 'assets/css/myperk-design-system.css', array(), MYPERK_VERSION );
        wp_enqueue_script( 'myperk-frontend', MYPERK_PLUGIN_URL . 'assets/js/myperk-frontend.js', array( 'jquery' ), MYPERK_VERSION, true );

        wp_localize_script( 'myperk-frontend', 'myperk_params', array(
            'rest_url' => esc_url_raw( rest_url() ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    public function render_customer_dashboard() {
        ob_start();
        include MYPERK_PLUGIN_DIR . 'templates/customer-dashboard.php';
        return ob_get_clean();
    }

    public function render_staff_dashboard() {
        ob_start();
        include MYPERK_PLUGIN_DIR . 'templates/staff-approval.php';
        return ob_get_clean();
    }

    public function render_business_dashboard() {
        ob_start();
        include MYPERK_PLUGIN_DIR . 'templates/business-dashboard.php';
        return ob_get_clean();
    }

    public function render_admin_dashboard() {
        ob_start();
        include MYPERK_PLUGIN_DIR . 'templates/admin-dashboard.php';
        return ob_get_clean();
    }
}

MyPerk_Templates::get_instance();
