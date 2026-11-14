<?php
// Clear stamp requests older than 24 hours
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Delete ALL requests
$stmt = $pdo->prepare("DELETE FROM loyalty_stamp_requests");
$stmt->execute();
echo "Deleted all requests: " . $stmt->rowCount() . "\n";

// Check remaining
$stmt = $pdo->query("SELECT COUNT(*) FROM loyalty_stamp_requests");
echo "Remaining requests: " . $stmt->fetchColumn() . "\n";

// Show all requests
$stmt = $pdo->query("SELECT * FROM loyalty_stamp_requests");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "All requests:\n";
foreach ($rows as $r) {
    echo "  id=" . $r['id'] . " customer_id=" . $r['customer_id'] . " business_id=" . $r['business_id'] . " status=" . $r['status'] . " created_at=" . $r['created_at'] . "\n";
}

$pdo = null;