<?php
require('config/db.php');

echo "<h2>Checking Payment References in Orders</h2>";

try {
    // Check orders with metadata
    $sql = "SELECT order_id, payment_method, proof_metadata as metadata FROM orders WHERE proof_metadata IS NOT NULL AND proof_metadata != '' ORDER BY order_id DESC LIMIT 10";
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Orders with Metadata:</h3>";
    if ($orders) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Order ID</th><th>Payment Method</th><th>Metadata</th><th>Reference Number</th></tr>";
        
        foreach ($orders as $order) {
            $reference = 'N/A';
            if (!empty($order['metadata'])) {
                $metadata = json_decode($order['metadata'], true);
                if (isset($metadata['payment_reference'])) {
                    $reference = $metadata['payment_reference'];
                }
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['payment_method']) . "</td>";
            echo "<td><small>" . htmlspecialchars($order['metadata']) . "</small></td>";
            echo "<td><strong>" . htmlspecialchars($reference) . "</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No orders with metadata found.</p>";
    }
    
    // Check all payment methods
    echo "<h3>All Orders by Payment Method:</h3>";
    $sql = "SELECT payment_method, COUNT(*) as count FROM orders GROUP BY payment_method";
    $stmt = $pdo->query($sql);
    $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Payment Method</th><th>Count</th></tr>";
    foreach ($methods as $method) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($method['payment_method']) . "</td>";
        echo "<td>" . $method['count'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check recent orders with digital payment methods
    echo "<h3>Recent Digital Payment Orders:</h3>";
    $sql = "SELECT order_id, payment_method, date FROM orders WHERE payment_method IN ('GCash1', 'GCash2', 'BPI', 'BDO') ORDER BY order_id DESC LIMIT 5";
    $stmt = $pdo->query($sql);
    $digital_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($digital_orders) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Order ID</th><th>Payment Method</th><th>Date</th><th>Has Reference</th></tr>";
        
        foreach ($digital_orders as $order) {
            $sql2 = "SELECT proof_metadata as metadata FROM orders WHERE order_id = :order_id";
            $stmt2 = $pdo->prepare($sql2);
            $stmt2->execute([':order_id' => $order['order_id']]);
            $order_data = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            $has_ref = 'No';
            if (!empty($order_data['metadata'])) {
                $metadata = json_decode($order_data['metadata'], true);
                if (isset($metadata['payment_reference'])) {
                    $has_ref = 'Yes';
                }
            }
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['order_id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['payment_method']) . "</td>";
            echo "<td>" . htmlspecialchars($order['date']) . "</td>";
            echo "<td>" . $has_ref . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No digital payment orders found.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin/orders.php'>Go to Orders</a></p>";
?>
