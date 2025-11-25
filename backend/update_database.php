<?php
// Database update script to add variant columns to pos_sale_items table

require_once '../../config/db.php';

try {
    // Check if columns exist first
    $stmt = $pdo->prepare("SHOW COLUMNS FROM pos_sale_items LIKE 'variant_id'");
    $stmt->execute();
    $variantIdExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM pos_sale_items LIKE 'variant_size'");
    $stmt->execute();
    $variantSizeExists = $stmt->rowCount() > 0;
    
    // Add columns if they don't exist
    if (!$variantIdExists) {
        $pdo->exec("ALTER TABLE pos_sale_items ADD COLUMN variant_id INT NULL AFTER total_price");
        echo "Added variant_id column to pos_sale_items table.<br>";
    } else {
        echo "variant_id column already exists.<br>";
    }
    
    if (!$variantSizeExists) {
        $pdo->exec("ALTER TABLE pos_sale_items ADD COLUMN variant_size VARCHAR(50) NULL AFTER variant_id");
        echo "Added variant_size column to pos_sale_items table.<br>";
    } else {
        echo "variant_size column already exists.<br>";
    }
    
    echo "<br>Database update completed successfully!";
    
} catch (PDOException $e) {
    echo "Database update failed: " . $e->getMessage();
}
?>
