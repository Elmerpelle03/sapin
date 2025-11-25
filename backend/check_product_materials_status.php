<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    // Get count of products with and without material links
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_products,
            SUM(CASE WHEN pm.product_id IS NOT NULL THEN 1 ELSE 0 END) AS products_with_materials,
            SUM(CASE WHEN pm.product_id IS NULL THEN 1 ELSE 0 END) AS products_missing_materials
        FROM products p
        LEFT JOIN product_materials pm ON p.product_id = pm.product_id
    ");
    
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get list of products missing materials
    $missing_stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.material
        FROM products p
        LEFT JOIN product_materials pm ON p.product_id = pm.product_id
        WHERE pm.product_id IS NULL
        LIMIT 20
    ");
    
    $missing_products = $missing_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_products' => (int)$counts['total_products'],
        'products_with_materials' => (int)$counts['products_with_materials'],
        'products_missing_materials' => (int)$counts['products_missing_materials'],
        'missing_products' => $missing_products
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
