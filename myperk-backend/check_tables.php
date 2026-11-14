<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "Table: $table\n";
    $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "  - " . $row['Field'] . "\n";
    }
    echo "\n";
}
?>