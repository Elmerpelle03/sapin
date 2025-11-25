<?php
require '../../config/db.php';

// Test what materials are linked to a pillow product
// Change this to your actual pillow product_id
$test_product_id = 1; // ⚠️ CHANGE THIS to your pillow product_id

echo "<h2>Testing Material Deduction for Product ID: {$test_product_id}</h2>";

// Get product info
$stmt = $pdo->prepare("SELECT product_id, product_name, stock FROM products WHERE product_id = :id");
$stmt->execute(['id' => $test_product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Product Info:</h3>";
echo "<pre>";
print_r($product);
echo "</pre>";

// Get materials linked to this product
$material_stmt = $pdo->prepare("
    SELECT pm.material_id, pm.quantity_needed, m.stock, m.material_name
    FROM product_materials pm
    JOIN materials m ON pm.material_id = m.material_id
    WHERE pm.product_id = :product_id
");
$material_stmt->execute(['product_id' => $test_product_id]);
$materials = $material_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Materials Linked to This Product:</h3>";
echo "<pre>";
print_r($materials);
echo "</pre>";

echo "<h3>Count of Materials:</h3>";
echo "Total materials found: " . count($materials);

if (count($materials) == 0) {
    echo "<p style='color: red; font-weight: bold;'>❌ NO MATERIALS LINKED! This is the problem!</p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ Materials are linked!</p>";
    
    // Simulate adding 10 units
    $stock_to_add = 10;
    echo "<h3>Simulating adding {$stock_to_add} units:</h3>";
    
    foreach ($materials as $mat) {
        $total_needed = $mat['quantity_needed'] * $stock_to_add;
        echo "<p>";
        echo "Material: <strong>{$mat['material_name']}</strong><br>";
        echo "Quantity needed per unit: {$mat['quantity_needed']}<br>";
        echo "Total needed for {$stock_to_add} units: <strong>{$total_needed}</strong><br>";
        echo "Current stock: {$mat['stock']}<br>";
        echo "After deduction: " . ($mat['stock'] - $total_needed);
        echo "</p>";
    }
}
?>
