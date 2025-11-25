<?php 
require 'config/db.php'; 
$stmt = $pdo->query('DESCRIBE users'); 
while ($row = $stmt->fetch()) { 
    echo $row['Field'] . ' - ' . $row['Type'] . PHP_EOL; 
}
?>
