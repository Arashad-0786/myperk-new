<?php
/**
 * Plugin Name: MyPerk Core
 * Plugin URI: https://myperk.local
 * Description: Core backend plugin for MyPerk multi-business loyalty and rewards SaaS platform.
 * Version: 1.0.0
 * Author: MyPerk Expert Architect
 * License: GPL v2 or later
 * Text Domain: myperk-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'MYPERK_VERSION', '1.0.0' );
define( 'MYPERK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MYPERK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main MyPerk Core Class.
 */
final class MyPerk_Core {

    /**
     * Instance of this class.
     *
     * @var MyPerk_Core
     */
    private static $instance = null;

    /**
     * Get instance.
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required core files.
     */
    private function includes() {
        require_once MYPERK_PLUGIN_DIR . 'includes/class-db.php';
        require_once MYPERK_PLUGIN_DIR . 'includes/class-security.php';
        require_once MYPERK_PLUGIN_DIR . 'includes/class-loyalty.php';
        require_once MYPERK_PLUGIN_DIR . 'includes/class-billing.php';
        require_once MYPERK_PLUGIN_DIR . 'includes/class-api.php';
        require_once MYPERK_PLUGIN_DIR . 'includes/class-templates.php';
        require_once MYPERK_PLUGIN_DIR . 'includes/class-admin.php';
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( 'MyPerk_DB', 'activate' ) );
        register_deactivation_hook( __FILE__, array( 'MyPerk_DB', 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Initialize plugin functionality.
     */
    public function init() {
        // Initialize subsystems
        MyPerk_API::get_instance();
        MyPerk_Loyalty::get_instance();
        MyPerk_Billing::get_instance();
        MyPerk_Admin::get_instance();
        
        do_action( 'myperk_core_loaded' );
    }
}

// Kick off the plugin.
function run_myperk_core() {
    return MyPerk_Core::get_instance();
}
run_myperk_core();
