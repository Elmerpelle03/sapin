<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require ('config/db.php');
session_start();

echo "<h1>Shop Debug Test</h1>";

// Test 1: Database connection
echo "<h2>1. Database Connection</h2>";
if (isset($pdo)) {
    echo "✓ PDO connection exists<br>";
} else {
    echo "✗ PDO connection failed<br>";
    die();
}

// Test 2: Check if product_materials table exists
echo "<h2>2. Check Tables</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_materials'");
    if ($stmt->rowCount() > 0) {
        echo "✓ product_materials table exists<br>";
    } else {
        echo "✗ product_materials table does NOT exist<br>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'materials'");
    if ($stmt->rowCount() > 0) {
        echo "✓ materials table exists<br>";
    } else {
        echo "✗ materials table does NOT exist<br>";
    }
} catch (PDOException $e) {
    echo "✗ Error checking tables: " . $e->getMessage() . "<br>";
}

// Test 3: Run the shop query
echo "<h2>3. Test Shop Query</h2>";
try {
    $sql = "
        SELECT 
            p.*, 
            c.category_name, 
            IFNULL(AVG(ir.rating), 0) AS avg_rating,
            GROUP_CONCAT(DISTINCT m.material_name ORDER BY m.material_name SEPARATOR ', ') AS all_materials
        FROM products p
        JOIN product_category c ON p.category_id = c.category_id
        LEFT JOIN item_ratings ir ON ir.product_id = p.product_id
        LEFT JOIN product_materials pm ON p.product_id = pm.product_id
        LEFT JOIN materials m ON pm.material_id = m.material_id
        WHERE 1=1
        GROUP BY 
            p.product_id, p.product_name, p.price, p.bundle_price, 
            p.description, p.stock, p.category_id, 
            p.pieces_per_bundle, p.material, p.size, p.restock_alert, 
            p.image_url, c.category_name
        ORDER BY p.category_id ASC, p.product_name ASC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo "✓ Query executed successfully<br>";
    echo "Found " . count($products) . " products<br>";
    
    if (count($products) > 0) {
        echo "<h3>Sample Product:</h3>";
        echo "<pre>";
        print_r($products[0]);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "✗ Query failed: " . $e->getMessage() . "<br>";
    echo "<pre>SQL: " . $sql . "</pre>";
}

// Test 4: Check if navbar includes work
echo "<h2>4. Test Navbar Include</h2>";
try {
    ob_start();
    $active = 'shop';
    include 'includes/navbar_customer.php';
    $navbar_output = ob_get_clean();
    
    if (strlen($navbar_output) > 0) {
        echo "✓ Navbar loaded successfully (" . strlen($navbar_output) . " bytes)<br>";
    } else {
        echo "✗ Navbar is empty<br>";
    }
} catch (Exception $e) {
    echo "✗ Navbar error: " . $e->getMessage() . "<br>";
}

echo "<h2>5. PHP Info</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Error Reporting: " . error_reporting() . "<br>";

echo "<hr><p><a href='shop.php'>Go to Shop Page</a></p>";
?>
