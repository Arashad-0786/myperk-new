<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_rewards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    stamp_cost INT UNSIGNED NOT NULL DEFAULT 1,
    reward_type ENUM('STAMPS', 'DISCOUNT', 'FREE_ITEM') NOT NULL DEFAULT 'STAMPS',
    reward_value VARCHAR(200),
    max_redemptions INT UNSIGNED,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_business (business_id),
    INDEX idx_active (is_active)
)");
echo "loyalty_rewards table created\n";
?>