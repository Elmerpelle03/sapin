<?php
/**
 * Diagnostic Script to Test Data Fetching
 * This file helps verify that database queries are working correctly
 * Upload this to your Hostinger server and access it via browser
 */

// Suppress visitor tracking for this test
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test Script';

require('../config/db.php');
session_start();

// Set a test user ID if not logged in (for testing purposes only)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Use a valid admin user ID from your database
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Fetch Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #007bff; }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Database Fetch Diagnostic Test</h1>
    <p class='info'>Testing database queries to identify data fetching issues...</p>
";

// Test 1: PDO Configuration
echo "<div class='test-section'>";
echo "<h2>Test 1: PDO Configuration</h2>";
try {
    $attrs = [
        'ATTR_ERRMODE' => $pdo->getAttribute(PDO::ATTR_ERRMODE),
        'ATTR_DEFAULT_FETCH_MODE' => $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE),
        'ATTR_EMULATE_PREPARES' => $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES),
    ];
    
    echo "<p class='success'>✓ PDO Connection Successful</p>";
    echo "<pre>";
    echo "Error Mode: " . ($attrs['ATTR_ERRMODE'] == PDO::ERRMODE_EXCEPTION ? 'EXCEPTION (Good)' : 'Other') . "\n";
    echo "Default Fetch Mode: " . ($attrs['ATTR_DEFAULT_FETCH_MODE'] == PDO::FETCH_ASSOC ? 'FETCH_ASSOC (Good)' : 'Other (May cause issues)') . "\n";
    echo "Emulate Prepares: " . ($attrs['ATTR_EMULATE_PREPARES'] ? 'Yes' : 'No (Good)') . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 2: Orders Count (Sidebar Query)
echo "<div class='test-section'>";
echo "<h2>Test 2: New Orders Count (Sidebar)</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE seen = 0");
    $new_order_count = $stmt->fetchColumn();
    echo "<p class='success'>✓ Query Successful</p>";
    echo "<p>New Orders (unseen): <strong>$new_order_count</strong></p>";
    
    // Get total orders for comparison
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
    $total_orders = $stmt->fetchColumn();
    echo "<p>Total Orders: <strong>$total_orders</strong></p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Return Requests Count (Sidebar Query)
echo "<div class='test-section'>";
echo "<h2>Test 3: Pending Returns Count (Sidebar)</h2>";
try {
    // First check if table exists
    $check = $pdo->query("SHOW TABLES LIKE 'return_requests'");
    if ($check->rowCount() > 0) {
        echo "<p class='success'>✓ Table 'return_requests' EXISTS</p>";
        
        $return_stmt = $pdo->query("SELECT COUNT(*) FROM return_requests WHERE return_status = 'Pending'");
        $pending_returns = $return_stmt->fetchColumn();
        echo "<p class='success'>✓ Query Successful</p>";
        echo "<p>Pending Returns: <strong>$pending_returns</strong></p>";
        
        // Get total returns for comparison
        $stmt = $pdo->query("SELECT COUNT(*) FROM return_requests");
        $total_returns = $stmt->fetchColumn();
        echo "<p>Total Return Requests: <strong>$total_returns</strong></p>";
    } else {
        echo "<p class='error'>✗ Table 'return_requests' does NOT exist</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 4: Notifications (Navbar Query)
echo "<div class='test-section'>";
echo "<h2>Test 4: Notifications (Navbar)</h2>";
try {
    // First check if table exists
    $check = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($check->rowCount() > 0) {
        echo "<p class='success'>✓ Table 'notifications' EXISTS</p>";
        
        $notif_count_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = :user_id 
            AND is_read = 0 
            AND (title LIKE '%Delivered%' OR title LIKE '%Return%')
        ");
        $notif_count_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $unread_count = $notif_count_stmt->fetchColumn();
        
        echo "<p class='success'>✓ Query Successful</p>";
        echo "<p>Unread Notifications: <strong>$unread_count</strong></p>";
        
        // Fetch recent notifications
        $notif_stmt = $pdo->prepare("
            SELECT notification_id, order_id, title, message, type, is_read, created_at 
            FROM notifications 
            WHERE user_id = :user_id 
            AND (title LIKE '%Delivered%' OR title LIKE '%Return%')
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $notif_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Recent Notifications (LIMIT 10): <strong>" . count($notifications) . " fetched</strong></p>";
        
        if (count($notifications) > 0) {
            echo "<pre>";
            foreach ($notifications as $i => $notif) {
                echo ($i + 1) . ". " . htmlspecialchars($notif['title']) . " - " . $notif['created_at'] . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='info'>No notifications found for this user.</p>";
        }
    } else {
        echo "<p class='error'>✗ Table 'notifications' does NOT exist</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 5: Sample Data Fetch with LIMIT
echo "<div class='test-section'>";
echo "<h2>Test 5: Sample Products (Testing LIMIT)</h2>";
try {
    $stmt = $pdo->query("SELECT product_id, product_name, price FROM products LIMIT 5");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✓ Query Successful</p>";
    echo "<p>Products Fetched (LIMIT 5): <strong>" . count($products) . "</strong></p>";
    
    if (count($products) > 0) {
        echo "<pre>";
        foreach ($products as $i => $product) {
            echo ($i + 1) . ". " . htmlspecialchars($product['product_name']) . " - ₱" . $product['price'] . "\n";
        }
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 6: Check Tables Exist
echo "<div class='test-section'>";
echo "<h2>Test 6: Database Tables Check</h2>";
try {
    $tables = ['orders', 'return_requests', 'notifications', 'products', 'users'];
    echo "<p class='success'>✓ Checking tables...</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "<li><strong>$table</strong>: $count rows</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 7: PHP Version and Environment
echo "<div class='test-section'>";
echo "<h2>Test 7: Server Environment</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Host: " . $_SERVER['HTTP_HOST'] . "\n";
echo "PDO Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
echo "PDO Client Version: " . $pdo->getAttribute(PDO::ATTR_CLIENT_VERSION) . "\n";
echo "PDO Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
echo "</pre>";
echo "</div>";

echo "
    <div class='test-section'>
        <h2>✅ Test Complete</h2>
        <p>If all tests show <span class='success'>✓</span>, your database configuration is correct.</p>
        <p>If you see errors or unexpected counts, please check:</p>
        <ul>
            <li>Database credentials in <code>config/db.production.php</code></li>
            <li>Table structures match between localhost and Hostinger</li>
            <li>Data has been properly migrated to Hostinger</li>
        </ul>
        <p class='error'><strong>⚠️ IMPORTANT:</strong> Delete this file after testing for security reasons!</p>
    </div>
</body>
</html>
";
?>
