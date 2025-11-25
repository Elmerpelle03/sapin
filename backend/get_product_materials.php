<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

$product_id = $_GET['product_id'] ?? null;

if (!$product_id) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            pm.id,
            pm.product_id,
            pm.material_id,
            pm.quantity_needed,
            m.material_name,
            m.stock,
            mu.unit
        FROM product_materials pm
        JOIN materials m ON pm.material_id = m.material_id
        LEFT JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id
        WHERE pm.product_id = :product_id
        ORDER BY m.material_name
    ");
    
    $stmt->execute(['product_id' => $product_id]);
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($materials);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
