<?php
// Minimal test of MyPerk_DB_Standalone
require_once 'standalone-minimal.php';

define('OBJECT', 1);

$db = MyPerk_DB_Standalone::get_instance();
$sql = "SELECT b.id as branch_id, b.branch_name, b.business_id, biz.business_name, biz.slug, biz.status as business_status FROM loyalty_branches b JOIN loyalty_businesses biz ON biz.id = b.business_id WHERE b.branch_code = 'TC001' AND b.status = 'active' AND biz.status IN ('trialing', 'active', 'grace')";

echo "SQL: $sql\n";

$result = $db->get_row($sql);
echo "Result: " . var_export($result, true) . "\n";
if ($result) {
    echo "branch_id: " . $result->branch_id . "\n";
    echo "business_name: " . $result->business_name . "\n";
}