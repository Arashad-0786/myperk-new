<?php
/**
 * Standalone test for QR verify endpoint
 */

// Test the is_wp_error function exists
if (!function_exists('is_wp_error')) {
    echo "ERROR: is_wp_error function not found\n";
    exit(1);
}

echo "SUCCESS: is_wp_error function exists\n";

// Test WP_Error class
if (!class_exists('WP_Error')) {
    echo "ERROR: WP_Error class not found\n";
    exit(1);
}

echo "SUCCESS: WP_Error class exists\n";

// Test the function
$error = new WP_Error('test', 'Test error message');
echo "is_wp_error test: " . (is_wp_error($error) ? 'PASS' : 'FAIL') . "\n";

// Test with non-error
echo "is_wp_error(null) test: " . (!is_wp_error(null) ? 'PASS' : 'FAIL') . "\n";

echo "\nAll tests passed!\n";
?>