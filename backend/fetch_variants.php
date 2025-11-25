<?php
require '../../config/db.php';
require '../../config/session_admin.php';
header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT variant_id, product_id, size, price, stock, size_multiplier, is_active
                           FROM product_variants
                           WHERE product_id = :pid
                           ORDER BY size ASC, variant_id ASC");
    $stmt->execute([':pid' => $product_id]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'variants' => $variants]);
} catch (PDOException $e) {
    error_log('fetch_variants error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
