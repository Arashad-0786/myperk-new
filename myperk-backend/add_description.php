<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->exec("ALTER TABLE loyalty_businesses ADD description TEXT NULL AFTER brand_color");
echo "description column added\n";

// Verify
$stmt = $pdo->query("DESCRIBE loyalty_businesses");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row["Field"] . " (" . $row["Type"] . ")\n";
}