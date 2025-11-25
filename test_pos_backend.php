<?php
session_start();

// Simulate admin session for testing
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "Testing POS Backend..." . PHP_EOL;

// Test if pos_handler.php exists
if (file_exists('admin/backend/pos_handler.php')) {
    echo "✓ pos_handler.php exists" . PHP_EOL;
} else {
    echo "✗ pos_handler.php NOT found" . PHP_EOL;
    exit;
}

// Test database connection
require_once 'config/db.php';
if ($pdo) {
    echo "✓ Database connection successful" . PHP_EOL;
} else {
    echo "✗ Database connection failed" . PHP_EOL;
    exit;
}

// Test products table
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0");
    $count = $stmt->fetchColumn();
    echo "✓ Found {$count} products with stock" . PHP_EOL;
} catch (Exception $e) {
    echo "✗ Error querying products: " . $e->getMessage() . PHP_EOL;
}

// Test pos_handler.php by simulating add_to_cart
$_POST['action'] = 'get_cart';
echo "Testing get_cart action..." . PHP_EOL;

// Capture output
ob_start();
include 'admin/backend/pos_handler.php';
$output = ob_get_clean();

echo "Backend response: " . $output . PHP_EOL;

echo "Test completed." . PHP_EOL;
?>
