<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = $_POST['supplier_id'] ?? null;
    $material_id = $_POST['material_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $message = $_POST['message'] ?? null;
    $send_method = $_POST['send_method'] ?? null;
    $expected_delivery = $_POST['expected_delivery'] ?? null;
    
    if (!$supplier_id || !$material_id || !$quantity || !$send_method) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO supplier_restock_orders 
            (supplier_id, material_id, requested_quantity, message, sent_via, expected_delivery, created_by, status)
            VALUES (:supplier_id, :material_id, :quantity, :message, :sent_via, :expected_delivery, :created_by, 'sent')
        ");
        
        $created_by = $_SESSION['admin_username'] ?? 'Owner';
        
        $stmt->execute([
            'supplier_id' => $supplier_id,
            'material_id' => $material_id,
            'quantity' => $quantity,
            'message' => $message,
            'sent_via' => $send_method,
            'expected_delivery' => $expected_delivery ?: null,
            'created_by' => $created_by
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Order record saved successfully',
            'order_id' => $pdo->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
