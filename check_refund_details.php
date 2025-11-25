<?php
require('config/db.php');

echo "<h2>Checking Refund Details Column</h2>";

try {
    // Check if refund_details column exists
    $sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'return_requests' AND COLUMN_NAME = 'refund_details'";
    $stmt = $pdo->query($sql);
    $column_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column_exists) {
        echo "<h3 style='color: orange;'>⚠ refund_details column not found. Adding it...</h3>";
        
        try {
            $alterSql = "ALTER TABLE return_requests ADD COLUMN refund_details TEXT NULL AFTER refund_proof";
            $pdo->exec($alterSql);
            echo "<p style='color: green;'>✅ Refund details column added successfully!</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Error adding refund_details column: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<h3 style='color: green;'>✅ Refund details column exists</h3>";
    }
    
    // Show current return_requests structure
    echo "<h3>Return Requests Table Structure:</h3>";
    $sql = "DESCRIBE return_requests";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin/returns.php'>Go to Returns</a></p>";
?>
