<?php
// Check the actual data in stamp_requests

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=perk_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Sample stamp requests:\n";
    $stmt = $pdo->query("SELECT id, business_id, customer_id, branch_id, status, requested_stamps, created_at FROM loyalty_stamp_requests WHERE business_id = 1 AND status = 'pending' LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    print_r($results);
    
    echo "\n\nAll stamp requests in DB:\n";
    $stmt = $pdo->query("SELECT id, business_id, customer_id, branch_id, status, requested_stamps, created_at FROM loyalty_stamp_requests ORDER BY created_at DESC LIMIT 10");
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    print_r($results);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}