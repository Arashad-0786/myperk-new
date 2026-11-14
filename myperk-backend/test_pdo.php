<?php
// Minimal test - just PDO connect and query
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=perk_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $sql = "SELECT b.id as branch_id, b.branch_name, b.business_id, biz.business_name, biz.slug, biz.status as business_status FROM loyalty_branches b JOIN loyalty_businesses biz ON biz.id = b.business_id WHERE b.branch_code = 'TC001' AND b.status = 'active' AND biz.status IN ('trialing', 'active', 'grace')";
    $stmt = $pdo->query($sql);
    $result = $stmt->fetch(PDO::FETCH_OBJ);
    echo 'Result: ' . var_export($result, true) . PHP_EOL;
    if ($result) {
        echo 'branch_id: ' . $result->branch_id . PHP_EOL;
        echo 'business_name: ' . $result->business_name . PHP_EOL;
    } else {
        echo 'No results found' . PHP_EOL;
    }
} catch (PDOException $e) {
    echo 'PDO Exception: ' . $e->getMessage() . PHP_EOL;
}