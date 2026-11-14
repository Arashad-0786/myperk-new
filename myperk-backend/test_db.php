<?php
// Test database connection and queries

try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=perk_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n\n";
    
    // Check table structure
    echo "Tables in database:\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'loyalty_%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "  - $t\n";
    }
    
    // Check stamp_requests
    echo "\nStamp requests count: " . $pdo->query("SELECT COUNT(*) FROM loyalty_stamp_requests")->fetchColumn() . "\n";
    echo "Pending stamp requests: " . $pdo->query("SELECT COUNT(*) FROM loyalty_stamp_requests WHERE status='pending'")->fetchColumn() . "\n";
    
    // Check the actual query used in get_pending_requests
    $sql = "SELECT sr.id, sr.customer_id, c.full_name, sr.requested_stamps, sr.status, sr.qr_hash, sr.created_at
            FROM loyalty_stamp_requests sr
            JOIN loyalty_customers c ON c.id = sr.customer_id
            WHERE sr.business_id = 1 AND sr.status = 'pending'";
            
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_OBJ);
    echo "\nQuery test: " . count($results) . " results\n";
    if (count($results) > 0) {
        print_r(array_slice($results, 0, 3));
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}