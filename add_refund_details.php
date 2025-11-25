<?php
require('config/db.php');

echo "<h2>Adding Refund Details Column</h2>";

try {
    // Add the refund_details column
    $alterSql = "ALTER TABLE return_requests ADD COLUMN refund_details TEXT NULL AFTER refund_proof";
    $pdo->exec($alterSql);
    echo "<p style='color: green;'>✅ Refund details column added successfully!</p>";
    
    // Verify the column was added
    $sql = "DESCRIBE return_requests";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Updated Return Requests Table Structure:</h3>";
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
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='admin/returns.php'>Go to Returns</a></p>";
?>
