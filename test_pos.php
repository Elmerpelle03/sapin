<?php
session_start();
require_once '../config/db.php';

// Simple test for POS functionality
echo "Testing POS Backend...\n";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "Session role: " . ($_SESSION['role'] ?? 'Not set') . "\n";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0");
    $count = $stmt->fetchColumn();
    echo "Available products: $count\n";
    
    // Test if we have any cart in session
    if (isset($_SESSION['pos_cart'])) {
        echo "Cart items: " . count($_SESSION['pos_cart']) . "\n";
    } else {
        echo "No cart in session\n";
    }
    
    echo "Backend is working!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
