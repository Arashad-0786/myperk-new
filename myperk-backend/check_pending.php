<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3307;dbname=perk_db', 'root', '');
    $stmt = $pdo->query('SELECT id, status FROM loyalty_stamp_requests WHERE status = "pending" LIMIT 5');
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch(Exception $e) {
    echo $e->getMessage();
}