<?php
/**
 * WP Admin Management Panel for MyPerk Core.
 * Allows non-technical admin to manage plans, offers/rewards, settings, businesses, and notifications.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MyPerk_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register WP Admin menu and submenus.
     */
    public function register_admin_menus() {
        add_menu_page(
            'MyPerk Loyalty',
            'MyPerk',
            'manage_options',
            'myperk-dashboard',
            array( $this, 'render_dashboard_page' ),
            'dashicons-tickets-alt',
            30
        );

        add_submenu_page(
            'myperk-dashboard',
            'Platform Overview',
            'Dashboard',
            'manage_options',
            'myperk-dashboard',
            array( $this, 'render_dashboard_page' )
        );

        add_submenu_page(
            'myperk-dashboard',
            'Manage Businesses',
            'Businesses',
            'manage_options',
            'myperk-businesses',
            array( $this, 'render_businesses_page' )
        );

        add_submenu_page(
            'myperk-dashboard',
            'Plans & Pricing',
            'Plans & Pricing',
            'manage_options',
            'myperk-plans',
            array( $this, 'render_plans_page' )
        );

        add_submenu_page(
            'myperk-dashboard',
            'Rewards & Offers',
            'Rewards & Offers',
            'manage_options',
            'myperk-rewards',
            array( $this, 'render_rewards_page' )
        );

        add_submenu_page(
            'myperk-dashboard',
            'Notifications & Templates',
            'Notifications',
            'manage_options',
            'myperk-notifications',
            array( $this, 'render_notifications_page' )
        );

        add_submenu_page(
            'myperk-dashboard',
            'MyPerk Settings',
            'Settings',
            'manage_options',
            'myperk-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Register option fields for settings page.
     */
    public function register_settings() {
        register_setting( 'myperk_settings_group', 'myperk_razorpay_key_id' );
        register_setting( 'myperk_settings_group', 'myperk_razorpay_key_secret' );
        register_setting( 'myperk_settings_group', 'myperk_razorpay_webhook_secret' );
        register_setting( 'myperk_settings_group', 'myperk_billing_mode' ); // 'woocommerce' or 'direct_razorpay'
        register_setting( 'myperk_settings_group', 'myperk_otp_provider' ); // 'msg91' or 'twilio'
        register_setting( 'myperk_settings_group', 'myperk_default_currency' );
    }

    // ==========================================
    // RENDER ADMIN PAGES
    // ==========================================

    public function render_dashboard_page() {
        global $wpdb;
        $total_businesses = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_businesses" );
        $active_subs      = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}loyalty_subscriptions WHERE status = 'active'" );
        $total_stamps     = $wpdb->get_var( "SELECT COALESCE(SUM(delta),0) FROM {$wpdb->prefix}loyalty_ledger WHERE transaction_type = 'stamp_approved'" );
        ?>
        <div class="wrap">
            <h1>MyPerk Platform Overview</h1>
            <div style="display:flex; gap:1.5rem; margin-top:1.5rem;">
                <div style="background:#fff; border:1px solid #ccc; padding:1.5rem; border-radius:8px; flex:1;">
                    <h3 style="margin-top:0;">Total Businesses</h3>
                    <p style="font-size:2rem; font-weight:bold; color:#4F46E5; margin:0;"><?php echo esc_html( $total_businesses ); ?></p>
                </div>
                <div style="background:#fff; border:1px solid #ccc; padding:1.5rem; border-radius:8px; flex:1;">
                    <h3 style="margin-top:0;">Active Subscriptions</h3>
                    <p style="font-size:2rem; font-weight:bold; color:#10B981; margin:0;"><?php echo esc_html( $active_subs ); ?></p>
                </div>
                <div style="background:#fff; border:1px solid #ccc; padding:1.5rem; border-radius:8px; flex:1;">
                    <h3 style="margin-top:0;">Total Stamps Awarded</h3>
                    <p style="font-size:2rem; font-weight:bold; color:#F59E0B; margin:0;"><?php echo esc_html( number_format( $total_stamps ) ); ?></p>
                </div>
            </div>

            <div style="margin-top:2rem; background:#fff; padding:1.5rem; border:1px solid #ccc; border-radius:8px;">
                <h2>Quick Actions</h2>
                <p>Use the submenus on the left to manage your SaaS platform:</p>
                <ul>
                    <li><strong>Businesses:</strong> View registered businesses, edit details, and check subscription status.</li>
                    <li><strong>Plans & Pricing:</strong> Add or modify pricing tiers (Starter, Growth, Pro).</li>
                    <li><strong>Rewards & Offers:</strong> Create global promotional offers and stamp templates.</li>
                    <li><strong>Settings:</strong> Set your Razorpay keys and notification providers.</li>
                </ul>
            </div>
        </div>
        <?php
    }

    public function render_businesses_page() {
        global $wpdb;
        $businesses = $wpdb->get_results( "SELECT b.*, s.status as sub_status, s.plan_code FROM {$wpdb->prefix}loyalty_businesses b LEFT JOIN {$wpdb->prefix}loyalty_subscriptions s ON s.business_id = b.id ORDER BY b.created_at DESC" );
        ?>
        <div class="wrap">
            <h1>Manage Businesses</h1>
            <table class="wp-list-table widefat fixed striped" style="margin-top:1rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Business Name</th>
                        <th>Slug</th>
                        <th>Contact Email</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $businesses ) ) : ?>
                        <tr><td colspan="7">No businesses registered yet.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $businesses as $b ) : ?>
                            <tr>
                                <td><?php echo esc_html( $b->id ); ?></td>
                                <td><strong><?php echo esc_html( $b->business_name ); ?></strong></td>
                                <td><code><?php echo esc_html( $b->slug ); ?></code></td>
                                <td><?php echo esc_html( $b->contact_email ); ?></td>
                                <td><span class="badge"><?php echo esc_html( ucfirst( $b->status ) ); ?></span></td>
                                <td><?php echo esc_html( strtoupper( $b->plan_code ?: 'N/A' ) ); ?></td>
                                <td><?php echo esc_html( date( 'M j, Y', strtotime( $b->created_at ) ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_plans_page() {
        if ( isset( $_POST['myperk_save_plans'] ) && check_admin_referer( 'myperk_plans_nonce' ) ) {
            update_option( 'myperk_plans_config', wp_unslash( $_POST['plans'] ) );
            echo '<div class="updated"><p>Plans saved successfully!</p></div>';
        }

        $plans = get_option( 'myperk_plans_config', array(
            'starter' => array( 'name' => 'Starter', 'price_yearly' => '9,990', 'stamps_limit' => 500, 'features' => '1 Branch, Basic Analytics' ),
            'growth'  => array( 'name' => 'Growth', 'price_yearly' => '29,990', 'stamps_limit' => 5000, 'features' => '5 Branches, SMS Notifications, Custom Branding' ),
            'pro'     => array( 'name' => 'Enterprise', 'price_yearly' => '79,990', 'stamps_limit' => 50000, 'features' => 'Unlimited Branches, Priority Support, API Access' ),
        ) );
        ?>
        <div class="wrap">
            <h1>Subscription Plans & Pricing</h1>
            <p>Customize subscription plans offered to business owners. Any edits here instantly update your pricing page.</p>
            <form method="post" action="">
                <?php wp_nonce_field( 'myperk_plans_nonce' ); ?>
                <table class="form-table">
                    <?php foreach ( $plans as $key => $plan ) : ?>
                        <tr style="border-top:1px solid #eee;">
                            <th scope="row">Plan: <?php echo esc_html( ucfirst( $key ) ); ?></th>
                            <td>
                                <label>Name: <input type="text" name="plans[<?php echo esc_attr( $key ); ?>][name]" value="<?php echo esc_attr( $plan['name'] ); ?>" class="regular-text"></label><br><br>
                                <label>Yearly Price (INR): <input type="text" name="plans[<?php echo esc_attr( $key ); ?>][price_yearly]" value="<?php echo esc_attr( $plan['price_yearly'] ); ?>" class="regular-text"></label><br><br>
                                <label>Features: <textarea name="plans[<?php echo esc_attr( $key ); ?>][features]" class="large-text" rows="2"><?php echo esc_textarea( $plan['features'] ); ?></textarea></label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
                <p class="submit">
                    <input type="submit" name="myperk_save_plans" class="button button-primary" value="Save Plans">
                </p>
            </form>
        </div>
        <?php
    }

    public function render_rewards_page() {
        if ( isset( $_POST['myperk_save_offers'] ) && check_admin_referer( 'myperk_offers_nonce' ) ) {
            update_option( 'myperk_global_offers', wp_unslash( $_POST['offers'] ) );
            echo '<div class="updated"><p>Offers and Rewards saved!</p></div>';
        }

        $offers = get_option( 'myperk_global_offers', array(
            array( 'title' => '10 Stamps = Free Beverage', 'stamps_required' => 10, 'status' => 'active' ),
            array( 'title' => 'Diwali Special: 2x Stamps', 'stamps_required' => 5, 'status' => 'active' ),
        ) );
        ?>
        <div class="wrap">
            <h1>Rewards & Promotional Offers</h1>
            <p>Create global promotional campaign templates or default reward configurations.</p>
            <form method="post" action="">
                <?php wp_nonce_field( 'myperk_offers_nonce' ); ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Offer Title</th>
                            <th>Stamps Required</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $offers as $idx => $offer ) : ?>
                            <tr>
                                <td><input type="text" name="offers[<?php echo $idx; ?>][title]" value="<?php echo esc_attr( $offer['title'] ); ?>" class="widefat"></td>
                                <td><input type="number" name="offers[<?php echo $idx; ?>][stamps_required]" value="<?php echo esc_attr( $offer['stamps_required'] ); ?>" style="width:80px;"></td>
                                <td>
                                    <select name="offers[<?php echo $idx; ?>][status]">
                                        <option value="active" <?php selected( $offer['status'], 'active' ); ?>>Active</option>
                                        <option value="disabled" <?php selected( $offer['status'], 'disabled' ); ?>>Disabled</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="myperk_save_offers" class="button button-primary" value="Save Offers">
                </p>
            </form>
        </div>
        <?php
    }

    public function render_notifications_page() {
        ?>
        <div class="wrap">
            <h1>Notifications & Message Templates</h1>
            <p>Configure automated email and SMS notification templates sent to customers and business owners.</p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label>Stamp Approved Email</label></th>
                    <td>
                        <textarea class="large-text" rows="3" readonly>Hi {customer_name}, you received {stamps} stamp(s) from {business_name}! Your new balance is {balance} stamps.</textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Reward Unlocked Email</label></th>
                    <td>
                        <textarea class="large-text" rows="3" readonly>Congratulations {customer_name}! You unlocked a reward at {business_name}: {reward_title}!</textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label>Subscription Expiry Alert</label></th>
                    <td>
                        <textarea class="large-text" rows="3" readonly>Dear {business_owner}, your MyPerk subscription for {business_name} expires in {days_left} days. Renew now to avoid disruption.</textarea>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>MyPerk Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'myperk_settings_group' );
                do_settings_sections( 'myperk_settings_group' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="myperk_billing_mode">Billing Integration Path</label></th>
                        <td>
                            <select name="myperk_billing_mode" id="myperk_billing_mode">
                                <option value="woocommerce" <?php selected( get_option( 'myperk_billing_mode' ), 'woocommerce' ); ?>>WooCommerce + Official Razorpay (Preferred)</option>
                                <option value="direct_razorpay" <?php selected( get_option( 'myperk_billing_mode' ), 'direct_razorpay' ); ?>>Direct Razorpay API (Alternative)</option>
                            </select>
                            <p class="description">Select your preferred payment gateway integration path.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="myperk_razorpay_key_id">Razorpay Key ID</label></th>
                        <td>
                            <input type="text" name="myperk_razorpay_key_id" id="myperk_razorpay_key_id" value="<?php echo esc_attr( get_option( 'myperk_razorpay_key_id' ) ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="myperk_razorpay_key_secret">Razorpay Key Secret</label></th>
                        <td>
                            <input type="password" name="myperk_razorpay_key_secret" id="myperk_razorpay_key_secret" value="<?php echo esc_attr( get_option( 'myperk_razorpay_key_secret' ) ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="myperk_razorpay_webhook_secret">Razorpay Webhook Secret</label></th>
                        <td>
                            <input type="password" name="myperk_razorpay_webhook_secret" id="myperk_razorpay_webhook_secret" value="<?php echo esc_attr( get_option( 'myperk_razorpay_webhook_secret' ) ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="myperk_otp_provider">OTP Verification Provider</label></th>
                        <td>
                            <select name="myperk_otp_provider" id="myperk_otp_provider">
                                <option value="msg91" <?php selected( get_option( 'myperk_otp_provider' ), 'msg91' ); ?>>MSG91 (India DLT Compliant)</option>
                                <option value="twilio" <?php selected( get_option( 'myperk_otp_provider' ), 'twilio' ); ?>>Twilio Verify</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Settings' ); ?>
            </form>
        </div>
        <?php
    }
}
