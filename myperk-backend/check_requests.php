<?php
require 'myperk-core/includes/class-db.php';
$db = MyPerk_DB_Standalone::get_instance();

echo "=== Stamp requests 13-22 status ===\n";
$stmt = $db->pdo->query("SELECT id, status FROM loyalty_stamp_requests WHERE id IN (13,14,15,16,17,18,19,20,21,22) ORDER BY id");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  ID {$row['id']}: status={$row['status']}\n";
}

echo "\n=== All stamp requests ===\n";
$stmt = $db->pdo->query("SELECT id, business_id, customer_id, status, requested_stamps, approved_stamps FROM loyalty_stamp_requests ORDER BY id DESC LIMIT 15");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  ID {$row['id']}: biz={$row['business_id']}, cust={$row['customer_id']}, status={$row['status']}, req={$row['requested_stamps']}, appr={$row['approved_stamps']}\n";
}