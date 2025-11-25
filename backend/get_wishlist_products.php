<?php
require('../config/db.php');

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$product_ids = $input['product_ids'] ?? [];

if (empty($product_ids)) {
    echo json_encode([]);
    exit;
}

// Sanitize product IDs
$product_ids = array_map('intval', $product_ids);
$placeholders = str_repeat('?,', count($product_ids) - 1) . '?';

// Fetch product details
$sql = "SELECT 
    product_id,
    product_name,
    price,
    stock,
    image_url
FROM products 
WHERE product_id IN ($placeholders)
ORDER BY FIELD(product_id, " . implode(',', $product_ids) . ")";

$stmt = $pdo->prepare($sql);
$stmt->execute($product_ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);
