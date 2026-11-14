<?php
require 'myperk-core/includes/class-db.php';
$db = MyPerk_DB_Standalone::get_instance();

echo "=== loyalty_ledger columns ===\n";
$stmt = $db->pdo->query('DESCRIBE loyalty_ledger');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  {$col['Field']}: {$col['Type']} ({$col['Null']})\n";
}

echo "\n=== loyalty_stamp_requests columns ===\n";
$stmt = $db->pdo->query('DESCRIBE loyalty_stamp_requests');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  {$col['Field']}: {$col['Type']} ({$col['Null']})\n";
}