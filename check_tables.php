<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$result = $pdo->query("SHOW TABLES");
echo "Tables in perk_db:\n";
foreach ($result as $row) {
    echo "  - " . $row['Tables_in_perk_db'] . "\n";
}