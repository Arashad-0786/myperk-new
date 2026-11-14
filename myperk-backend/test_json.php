<?php
$body = '{"business_id": 1, "branch_id": 1, "qr_hash": "TC001", "phone": "+15551234567", "requested_stamps": 1}';
$result = json_decode($body, true);
echo "Result: ";
var_dump($result);
echo "Error: " . json_last_error_msg() . "\n";