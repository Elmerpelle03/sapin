<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    $total_rules = $pdo->query("SELECT COUNT(*) FROM shipping_rules")->fetchColumn();
    $avg_fee = $pdo->query("SELECT AVG(shipping_fee) FROM shipping_rules")->fetchColumn();
    $max_fee = $pdo->query("SELECT MAX(shipping_fee) FROM shipping_rules")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'total_rules' => $total_rules,
        'avg_fee' => number_format($avg_fee, 2),
        'max_fee' => number_format($max_fee, 2)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
