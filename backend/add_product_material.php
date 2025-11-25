<?php
require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $material_id = $_POST['material_id'] ?? null;
    $quantity_needed = $_POST['quantity_needed'] ?? null;
    
    if (!$product_id || !$material_id || !$quantity_needed) {
        echo "All fields are required.";
        exit;
    }
    
    if ($quantity_needed <= 0) {
        echo "Quantity must be greater than 0.";
        exit;
    }
    
    try {
        // Check if this combination already exists
        $check = $pdo->prepare("SELECT id FROM product_materials WHERE product_id = :product_id AND material_id = :material_id");
        $check->execute(['product_id' => $product_id, 'material_id' => $material_id]);
        
        if ($check->rowCount() > 0) {
            echo "This material is already added to this product.";
            exit;
        }
        
        // Insert new material requirement
        $stmt = $pdo->prepare("
            INSERT INTO product_materials (product_id, material_id, quantity_needed)
            VALUES (:product_id, :material_id, :quantity_needed)
        ");
        
        $stmt->execute([
            'product_id' => $product_id,
            'material_id' => $material_id,
            'quantity_needed' => $quantity_needed
        ]);
        
        echo "success";
        
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}
?>
