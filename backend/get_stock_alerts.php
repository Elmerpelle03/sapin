<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    // Get products with stock issues (out of stock or low stock < 10)
    $stmt = $pdo->prepare("
        SELECT 
            product_id,
            product_name,
            stock,
            CASE 
                WHEN stock = 0 THEN 'out_of_stock'
                WHEN stock < 10 THEN 'low_stock'
                ELSE 'normal'
            END as stock_status
        FROM products
        WHERE stock < 10
        ORDER BY stock ASC, product_name ASC
        LIMIT 10
    ");
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts
    $count_stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
            SUM(CASE WHEN stock > 0 AND stock < 10 THEN 1 ELSE 0 END) as low_stock
        FROM products
    ");
    $counts = $count_stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'products' => $products,
        'counts' => [
            'out_of_stock' => intval($counts['out_of_stock']),
            'low_stock' => intval($counts['low_stock'])
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
