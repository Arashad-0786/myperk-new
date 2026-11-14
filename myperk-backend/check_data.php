<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Check stamp_requests for qr_hash
$stmt = $pdo->query("SELECT * FROM loyalty_stamp_requests LIMIT 5");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "stamp_request: " . json_encode($row) . "\n";
}

// Check branches
echo "\n--- Branches ---\n";
$stmt = $pdo->query("SELECT * FROM loyalty_branches");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "branch: " . json_encode($row) . "\n";
}

// Check businesses
echo "\n--- Businesses ---\n";
$stmt = $pdo->query("SELECT * FROM loyalty_businesses");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "business: " . json_encode($row) . "\n";
}
?>