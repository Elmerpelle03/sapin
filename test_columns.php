<?php
require('config/db.php');

echo "<h2>Checking orders table structure:</h2>";

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    $has_image_hash = false;
    $has_proof_metadata = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . $col['Default'] . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'image_hash') $has_image_hash = true;
        if ($col['Field'] === 'proof_metadata') $has_proof_metadata = true;
    }
    
    echo "</table>";
    
    echo "<h3>Status:</h3>";
    echo "<p>image_hash column exists: " . ($has_image_hash ? "✅ YES" : "❌ NO") . "</p>";
    echo "<p>proof_metadata column exists: " . ($has_proof_metadata ? "✅ YES" : "❌ NO") . "</p>";
    
    if ($has_image_hash) {
        echo "<h3>Testing duplicate detection:</h3>";
        $stmt = $pdo->query("SELECT order_id, image_hash FROM orders WHERE image_hash IS NOT NULL LIMIT 5");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($orders) > 0) {
            echo "<p>Found " . count($orders) . " orders with image hashes:</p>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Order ID</th><th>Image Hash</th></tr>";
            foreach ($orders as $order) {
                echo "<tr><td>" . $order['order_id'] . "</td><td>" . substr($order['image_hash'], 0, 16) . "...</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No orders with image hashes found yet.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
