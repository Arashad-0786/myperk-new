<?php
header('Content-Type: application/json');
require_once 'standalone-minimal.php';

$router = new MyPerk_Router();

// Test get public branches
$router->handle_request('GET', '/myperk/v1/public/business/1/branches', []);