<?php
require_once 'standalone-minimal.php';
echo "current_time exists: " . (function_exists('current_time') ? 'yes' : 'no') . "\n";
if (function_exists('current_time')) {
    $result = current_time('mysql');
    echo "current_time(mysql) = $result\n";
}