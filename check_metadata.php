<?php
require('config/db.php');

echo "<h2>Checking Orders Table Structure</h2>";

try {
    $sql = "DESCRIBE orders";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Orders Table Columns:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasMetadata = false;
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'metadata') {
            $hasMetadata = true;
        }
    }
    echo "</table>";
    
    if (!$hasMetadata) {
        echo "<h3 style='color: red;'>⚠ METADATA COLUMN NOT FOUND</h3>";
        echo "<p>The metadata column doesn't exist in the orders table. Let's create it...</p>";
        
        try {
            $alterSql = "ALTER TABLE orders ADD COLUMN metadata TEXT NULL AFTER proof_of_payment";
            $pdo->exec($alterSql);
            echo "<p style='color: green;'>✅ Metadata column added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error adding metadata column: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<h3 style='color: green;'>✅ Metadata column exists</h3>";
    }
    
    // Check if there are any orders with metadata
    $checkSql = "SELECT COUNT(*) as count FROM orders WHERE metadata IS NOT NULL AND metadata != ''";
    $stmt = $pdo->query($checkSql);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Orders with Metadata:</h3>";
    echo "<p>" . $result['count'] . " orders have metadata (reference numbers)</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin/orders.php'>Go back to Orders</a></p>";
?>
