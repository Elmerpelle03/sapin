<?php
require('../config/db.php');
session_start();
header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid product']);
  exit;
}

try {
  $stmt = $pdo->prepare("SELECT variant_id, size, price, stock, size_multiplier, is_active
                          FROM product_variants
                          WHERE product_id = :pid AND is_active = 1
                          ORDER BY size ASC");
  $stmt->execute([':pid' => $product_id]);
  $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['success' => true, 'variants' => $variants]);
} catch (PDOException $e) {
  error_log('get_variants error: '.$e->getMessage());
  echo json_encode(['success' => false, 'message' => 'Database error']);
}
