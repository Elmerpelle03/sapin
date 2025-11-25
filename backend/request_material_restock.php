<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_id = $_POST['material_id'] ?? null;
    $requested_quantity = $_POST['requested_quantity'] ?? null;
    $reason = $_POST['reason'] ?? 'Low stock';
    
    if (!$material_id || !$requested_quantity) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    try {
        // Get material info
        $stmt = $pdo->prepare("SELECT material_name, stock, unit FROM materials WHERE material_id = :material_id");
        $stmt->execute(['material_id' => $material_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$material) {
            echo json_encode(['success' => false, 'message' => 'Material not found']);
            exit;
        }
        
        // Get admin username
        $requested_by = $_SESSION['admin_username'] ?? 'Admin';
        
        // Insert restock request
        $insert_stmt = $pdo->prepare("
            INSERT INTO material_restock_requests 
            (material_id, requested_quantity, current_stock, reason, requested_by, status)
            VALUES (:material_id, :requested_quantity, :current_stock, :reason, :requested_by, 'pending')
        ");
        
        $insert_stmt->execute([
            'material_id' => $material_id,
            'requested_quantity' => $requested_quantity,
            'current_stock' => $material['stock'],
            'reason' => $reason,
            'requested_by' => $requested_by
        ]);
        
        $request_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => "Restock request submitted for {$material['material_name']}",
            'request_id' => $request_id,
            'material_name' => $material['material_name'],
            'requested_quantity' => $requested_quantity,
            'unit' => $material['unit']
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
