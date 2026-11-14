<?php
$pdo = new PDO("mysql:host=127.0.0.1;port=3307;dbname=perk_db", "root", "");
$branches = $pdo->query("SELECT id, branch_name, branch_code, address, phone FROM loyalty_branches WHERE business_id = 1 AND status = 'active' ORDER BY branch_name ASC");
foreach ($branches->fetchAll(PDO::FETCH_ASSOC) as $row) {
    print_r($row);
}