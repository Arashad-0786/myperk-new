<?php
require 'myperk-core/includes/class-db.php';
$db = MyPerk_DB_Standalone::get_instance();

// Check if request_id column exists
$stmt = $db->pdo->query("SHOW COLUMNS FROM loyalty_ledger LIKE 'request_id'");
if ($stmt->rowCount() == 0) {
    echo "Adding request_id column to loyalty_ledger...\n";
    $db->pdo->exec("ALTER TABLE loyalty_ledger ADD COLUMN request_id BIGINT UNSIGNED DEFAULT NULL AFTER reference_id");
    echo "Column added successfully!\n";
} else {
    echo "Column request_id already exists.\n";
}

// Verify the column was added
$stmt = $db->pdo->query("DESCRIBE loyalty_ledger");
echo "\nUpdated loyalty_ledger columns:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "  {$col['Field']}: {$col['Type']} ({$col['Null']})\n";
}