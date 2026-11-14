<?php
// Simple standalone test - no WordPress emulation
// Just test the router and database directly

// Minimal DB setup
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Test 1: QR verify query
$stmt = $pdo->prepare("SELECT b.id as branch_id, b.branch_name, b.business_id, biz.business_name, biz.slug, biz.status as business_status FROM loyalty_branches b JOIN loyalty_businesses biz ON biz.id = b.business_id WHERE b.branch_code = :code AND b.status = 'active' AND biz.status IN ('trialing', 'active', 'grace')");
$stmt->execute([':code' => 'TC001']);
$branch = $stmt->fetch(PDO::FETCH_OBJ);

if ($branch) {
    echo "QR VERIFY: valid=true, business_id=" . $branch->business_id . ", business_name=" . $branch->business_name . "\n";
} else {
    echo "QR VERIFY: valid=false, no branch found for TC001\n";
}

// Test 2: Check businesses
$stmt = $pdo->query("SELECT id, business_name, status FROM loyalty_businesses");
$businesses = $stmt->fetchAll(PDO::FETCH_OBJ);
echo "BUSINESSES:\n";
foreach ($businesses as $b) {
    echo "  ID=$b->id, name=$b->business_name, status=$b->status\n";
}

// Test 3: Check branches
$stmt = $pdo->query("SELECT id, branch_name, branch_code, business_id, status FROM loyalty_branches");
$branches = $stmt->fetchAll(PDO::FETCH_OBJ);
echo "BRANCHES:\n";
foreach ($branches as $b) {
    echo "  ID=$b->id, code=$b->branch_code, name=$b->branch_name, business_id=$b->business_id, status=$b->status\n";
}