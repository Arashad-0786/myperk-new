<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=perk_db', 'root', '');

echo "=== BUSINESSES ===\n";
$stmt = $pdo->query('SELECT id, business_name, slug, status FROM loyalty_businesses');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}

echo "\n=== BRANCHES ===\n";
$stmt = $pdo->query('SELECT id, branch_name, branch_code, business_id FROM loyalty_branches');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}

echo "\n=== QR STAMPS ===\n";
$stmt = $pdo->query('SELECT qr_hash, branch_id FROM loyalty_stamp_requests WHERE qr_hash = "TC001" LIMIT 5');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}

// Check if TC001 branch exists
echo "\n=== CHECKING TC001 BRANCH ===\n";
$stmt = $pdo->prepare('SELECT * FROM loyalty_branches WHERE branch_code = ?');
$stmt->execute(['TC001']);
$branch = $stmt->fetch(PDO::FETCH_ASSOC);
if ($branch) {
    echo "TC001 branch found:\n";
    print_r($branch);
} else {
    echo "TC001 branch NOT found!\n";
}