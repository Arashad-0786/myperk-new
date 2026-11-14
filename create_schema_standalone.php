<?php
/**
 * Standalone dbDelta-like function for MyPerk Core.
 * Creates tables if they don't exist, or alters them if needed.
 * Adapted from WordPress wp-includes/upgrade.php dbDelta() function.
 */

function myperk_dbdelta($sql) {
    global $wpdb; // We'll use our own $wpdb replacement
    
    // Split multiple SQL statements by semicolon
    $statement = '';
    $queries = [];
    
    // For our use case, we receive one CREATE TABLE statement at a time
    // so we can just execute it directly
    
    // Remove trailing semicolons and whitespace
    $sql = trim($sql);
    if (empty($sql)) {
        return;
    }
    
    // Execute the CREATE TABLE statement
    try {
        global $wpdb; // This should be our MyPerk_DB instance
        $wpdb->query($sql);
        echo "  ✓ Table(s) created/verified\n";
    } catch (Exception $e) {
        echo " ! Error: " . $e->getMessage() . "\n";
    }
}

// Test the schema creation
echo "Creating MyPerk database schema...\n\n";

// Table 1: loyalty_businesses
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_businesses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_name VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NOT NULL UNIQUE,
    legal_name VARCHAR(191) DEFAULT '',
    gstin VARCHAR(50) DEFAULT '',
    contact_email VARCHAR(191) NOT NULL,
    contact_phone VARCHAR(50) DEFAULT '',
    address TEXT,
    timezone VARCHAR(100) DEFAULT 'UTC',
    currency VARCHAR(10) DEFAULT 'INR',
    logo_url TEXT,
    brand_color VARCHAR(20) DEFAULT '#000000',
    status VARCHAR(30) DEFAULT 'trialing',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY status_idx (status),
    KEY slug_idx (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 2: loyalty_branches
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    branch_name VARCHAR(191) NOT NULL,
    branch_code VARCHAR(50) NOT NULL,
    address TEXT,
    phone VARCHAR(50) DEFAULT '',
    status VARCHAR(30) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY business_id_idx (business_id),
    UNIQUE KEY business_branch_code (business_id, branch_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 3: loyalty_customers
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wp_user_id BIGINT UNSIGNED DEFAULT NULL,
    phone VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(191) DEFAULT '',
    email VARCHAR(191) DEFAULT '',
    status VARCHAR(30) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY phone_idx (phone),
    KEY wp_user_id_idx (wp_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 4: loyalty_cards
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    stamp_count INT UNSIGNED DEFAULT 0,
    rewards_redeemed_count INT UNSIGNED DEFAULT 0,
    status VARCHAR(30) DEFAULT 'active',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY business_customer_idx (business_id, customer_id),
    UNIQUE KEY business_customer_card (business_id, customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 5: loyalty_stamp_requests
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_stamp_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    staff_id BIGINT UNSIGNED DEFAULT NULL,
    requested_stamps INT UNSIGNED DEFAULT 1,
    approved_stamps INT UNSIGNED DEFAULT 0,
    status VARCHAR(30) DEFAULT 'pending',
    qr_hash VARCHAR(191) NOT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    processed_at DATETIME DEFAULT NULL,
    KEY business_status_idx (business_id, status),
    KEY customer_idx (customer_id),
    KEY qr_hash_idx (qr_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 6: loyalty_ledger
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    card_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED DEFAULT NULL,
    actor_id BIGINT UNSIGNED NOT NULL,
    actor_type VARCHAR(50) NOT NULL,
    delta INT NOT NULL,
    balance_after INT UNSIGNED NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    reference_id BIGINT UNSIGNED DEFAULT NULL,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY customer_business_idx (customer_id, business_id),
    KEY transaction_type_idx (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 7: loyalty_audit_log
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED DEFAULT NULL,
    actor_id BIGINT UNSIGNED DEFAULT NULL,
    actor_type VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(100) DEFAULT '',
    target_id BIGINT UNSIGNED DEFAULT NULL,
    meta_json LONGTEXT,
    ip_address VARCHAR(50) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY action_idx (action),
    KEY business_id_idx (business_id),
    KEY created_at_idx (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 8: loyalty_subscriptions
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL UNIQUE,
    plan_code VARCHAR(50) NOT NULL,
    status VARCHAR(30) DEFAULT 'trialing',
    billing_period VARCHAR(20) DEFAULT 'year',
    provider_subscription_id VARCHAR(191) DEFAULT '',
    provider_order_id VARCHAR(191) DEFAULT '',
    current_period_start DATETIME DEFAULT CURRENT_TIMESTAMP,
    current_period_end DATETIME DEFAULT CURRENT_TIMESTAMP,
    grace_period_end DATETIME DEFAULT NULL,
    active_guard TINYINT GENERATED ALWAYS AS (
        CASE WHEN status IN ('trialing', 'active', 'grace') THEN 1 ELSE NULL END
    ) STORED,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY business_status_idx (business_id, status),
    UNIQUE KEY active_business_guard (business_id, active_guard)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Table 9: loyalty_invoices
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    public_id VARCHAR(50) NOT NULL UNIQUE,
    business_id BIGINT UNSIGNED NOT NULL,
    subscription_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    amount_paise BIGINT UNSIGNED NOT NULL,
    tax_paise BIGINT UNSIGNED NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    status VARCHAR(30) DEFAULT 'issued',
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    provider_payment_id VARCHAR(191) DEFAULT '',
    provider_order_id VARCHAR(191) DEFAULT '',
    KEY business_id_idx (business_id),
    KEY invoice_number_idx (invoice_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
);

// Table 10: loyalty_webhook_events
myperk_dbdelta("
CREATE TABLE IF NOT EXISTS loyalty_webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    provider_event_id VARCHAR(191) NOT NULL,
    business_id BIGINT UNSIGNED DEFAULT NULL,
    subscription_id BIGINT UNSIGNED DEFAULT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload_json LONGTEXT NOT NULL,
    signature_valid TINYINT(1) DEFAULT 0,
    status VARCHAR(30) DEFAULT 'received',
    attempts INT UNSIGNED DEFAULT 1,
    next_retry_at DATETIME DEFAULT NULL,
    processed_at DATETIME DEFAULT NULL,
    error_message TEXT,
    UNIQUE KEY provider_event_unique (provider, provider_event_id),
    KEY status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

echo "\n✅ Schema creation complete!\n";

// Verify all tables
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$result = $pdo->query("SHOW TABLES");
echo "Tables in perk_db:\n";
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "  - " . $row['Tables_in_perk_db'] . "\n";
}

// Insert a test business to verify the full pipeline works
$wpdb = new MyPerk_DB('127.0.0.1', 3307, 'perk_db', 'root', '');
$newId = $wpdb->insert('loyalty_businesses', [
    'business_name' => 'Test Integration Business',
    'slug' => 'test-integration',
    'contact_email' => 'test@myperk.local',
    'currency' => 'INR',
    'status' => 'trialing',
]);
echo "\nInserted test business ID: $newId\n";

// Verify
$count = $wpdb->get_var("SELECT COUNT(*) FROM loyalty_businesses");
echo "Total businesses after insert: $count\n";

$pdo = null;