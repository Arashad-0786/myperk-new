<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$stmt = $pdo->query("DESCRIBE loyalty_rewards");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . ' (' . $row['Type'] . ')' . "\n";
}
echo "\n";
$stmt = $pdo->query("SELECT * FROM loyalty_rewards LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row) . "\n";
}
?>