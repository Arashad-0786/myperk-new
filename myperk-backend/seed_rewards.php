<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->exec("INSERT INTO loyalty_rewards (business_id, name, description, stamp_cost, reward_type, reward_value, max_redemptions, is_active) VALUES
(1, 'Free Drink', 'A free drink on us', 5, 'STAMPS', 'Free Drink', 1, 1),
(1, '10% Off', '10% discount on total', 10, 'DISCOUNT', '10% Off', NULL, 1),
(1, 'Free Appetizer', 'A free appetizer', 8, 'FREE_ITEM', 'Free Appetizer', 1, 1);");
echo "Rewards seeded\n";
?>