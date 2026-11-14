<?php
ob_implicit_flush(1);
ob_start();

echo "=== MyPerk Database Setup ===\n\n";

// Connect directly via PDO
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3307", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Step 1: MySQL connection successful\n";
} catch (Exception $e) {
    echo "ERROR: Cannot connect to MySQL - " . $e->getMessage() . "\n";
    exit(1);
}

// Create database and use it
$pdo->exec("CREATE DATABASE IF NOT EXISTS perk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE perk_db");
echo "Step 2: Database 'perk_db' ready\n";

echo "\nStep 3: Creating tables...\n";

// Table: loyalty_businesses
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_businesses (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_businesses\n";

// Table: loyalty_branches
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_branches (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_branches\n";

// Table: loyalty_customers
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wp_user_id BIGINT UNSIGNED DEFAULT NULL,
    phone VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(191) DEFAULT '',
    email VARCHAR(191) DEFAULT '',
    status VARCHAR(30) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY phone_idx (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_customers\n";

// Table: loyalty_cards
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_cards (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_cards\n";

// Table: loyalty_stamp_requests
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_stamp_requests (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_stamp_requests\n";

// Table: loyalty_ledger
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_ledger (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_ledger\n";

// Table: loyalty_audit_log
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_audit_log (
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
    KEY business_id_idx (business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_audit_log\n";

// Table: loyalty_rewards
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    stamp_cost INT UNSIGNED NOT NULL,
    reward_type VARCHAR(50) DEFAULT 'discount',
    reward_value VARCHAR(191) DEFAULT '',
    max_redemptions INT UNSIGNED DEFAULT NULL,
    current_redemptions INT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY business_status_idx (business_id, is_active),
    KEY stamp_cost_idx (stamp_cost)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_rewards\n";

// Table: loyalty_subscriptions
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL UNIQUE,
    plan_code VARCHAR(50) NOT NULL,
    status VARCHAR(30) DEFAULT 'trialing',
    billing_period VARCHAR(20) DEFAULT 'year',
    provider_subscription_id VARCHAR(191) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "  ✓ loyalty_subscriptions\n";

echo "\n✅ All 8 tables created!\n";

echo "\nStep 4: Inserting test data...\n";

// Check if test business exists
$stmt = $pdo->prepare("SELECT id FROM loyalty_businesses WHERE slug = ?");
$stmt->execute(['test-shop']);
$existing = $stmt->fetchColumn();

if (!$existing) {
    $stmt = $pdo->prepare("INSERT INTO loyalty_businesses 
        (business_name, slug, contact_email, contact_phone, address, timezone, currency, status, brand_color)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        'Test Shop', 'test-shop', 'owner@testshop.local', '+1555123456',
        '123 Test Street, Test City', 'UTC', 'INR', 'active', '#FF5733'
    ]);
    $bizId = $pdo->lastInsertId();
    echo "  ✓ Business created (ID: $bizId)\n";
    
    $stmt = $pdo->prepare("INSERT INTO loyalty_branches 
        (business_id, branch_name, branch_code, address, phone, status)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$bizId, 'Main Branch', 'QR-TEST-001', '123 Test Street, Test City', '+1555123456', 'active']);
    $branchId = $pdo->lastInsertId();
    echo "  ✓ Branch created (ID: $branchId, QR Code: QR-TEST-001)\n";
    
    $stmt = $pdo->prepare("INSERT INTO loyalty_subscriptions 
        (business_id, plan_code, status) VALUES (?, ?, ?)");
    $stmt->execute([$bizId, 'starter', 'active']);
    echo "  ✓ Subscription created\n";
    
    $stmt = $pdo->prepare("INSERT INTO loyalty_rewards 
        (business_id, name, description, stamp_cost, reward_type, reward_value, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$bizId, 'Free Coffee', 'Get a free coffee after 5 stamps', 5, 'discount', '50%', 1]);
    echo "  ✓ Reward 'Free Coffee' created\n";
    
    $stmt->execute([$bizId, '10 Off', 'Get 10 off your next purchase', 10, 'discount', '10$', 1]);
    echo "  ✓ Reward '10 Off' created\n";
    
    $stmt = $pdo->prepare("INSERT INTO loyalty_customers 
        (phone, full_name, email, status) VALUES (?, ?, ?, ?)");
    $stmt->execute(['+1555999888', 'John Doe', 'john@testshop.local', 'active']);
    $customerId = $pdo->lastInsertId();
    echo "  ✓ Test customer created (ID: $customerId, Phone: +1555999888)\n";
} else {
    echo "  ℹ Test business already exists (ID: $existing)\n";
}

echo "\n✅ Database setup complete!\n";

echo "\nConnection string for PHP server: mysql:host=127.0.0.1;port=3307;dbname=perk_db\n";

ob_end_flush();