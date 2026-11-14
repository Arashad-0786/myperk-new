<?php
require_once 'standalone-minimal.php';

echo "Registered routes:\n";
foreach ($router->routes as $r) {
    echo "  " . $r['method'] . " " . $r['path'] . "\n";
}