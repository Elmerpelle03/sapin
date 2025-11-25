<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $material_id = $_POST['material_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    
    if (!$request_id || !$material_id || !$quantity) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update material stock
        $stmt = $pdo->prepare("
            UPDATE materials 
            SET stock = stock + :quantity,
                last_restock_date = NOW(),
                last_restock_by = :admin,
                last_restock_amount = :quantity
            WHERE material_id = :material_id
        ");
        
        $admin = $_SESSION['admin_username'] ?? 'Owner';
        
        $stmt->execute([
            'quantity' => $quantity,
            'admin' => $admin,
            'material_id' => $material_id
        ]);
        
        // Update request status
        $update_stmt = $pdo->prepare("
            UPDATE material_restock_requests 
            SET status = 'received',
                actual_delivery_date = CURDATE()
            WHERE request_id = :request_id
        ");
        
        $update_stmt->execute(['request_id' => $request_id]);
        
        // Get updated stock
        $stock_stmt = $pdo->prepare("SELECT material_name, stock, unit FROM materials WHERE material_id = :material_id");
        $stock_stmt->execute(['material_id' => $material_id]);
        $material = $stock_stmt->fetch(PDO::FETCH_ASSOC);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Material restocked successfully',
            'material_name' => $material['material_name'],
            'new_stock' => $material['stock'] . ' ' . $material['unit']
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
