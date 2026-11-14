<?php
// Minimal test - just set header and exit
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode(['status' => 'ok', 'message' => 'test']);
exit;