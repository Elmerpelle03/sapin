<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$request_id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // If marking as delivered, add quantity to material stock
        if ($status === 'delivered') {
            // Get request details
            $request_stmt = $pdo->prepare("
                SELECT material_id, requested_quantity 
                FROM material_supplier_requests 
                WHERE request_id = :request_id
            ");
            $request_stmt->execute(['request_id' => $request_id]);
            $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request) {
                // Update material stock
                $update_material = $pdo->prepare("
                    UPDATE materials 
                    SET stock = stock + :quantity 
                    WHERE material_id = :material_id
                ");
                $update_material->execute([
                    'quantity' => $request['requested_quantity'],
                    'material_id' => $request['material_id']
                ]);
                
                // Get updated stock with unit name
                $stock_stmt = $pdo->prepare("
                    SELECT m.material_name, m.stock, mu.materialunit_name as unit 
                    FROM materials m 
                    LEFT JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id
                    WHERE m.material_id = :material_id
                ");
                $stock_stmt->execute(['material_id' => $request['material_id']]);
                $material = $stock_stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        
        // Update request status
        $stmt = $pdo->prepare("
            UPDATE material_supplier_requests 
            SET status = :status 
            WHERE request_id = :request_id
        ");
        
        $stmt->execute([
            'status' => $status,
            'request_id' => $request_id
        ]);
        
        $pdo->commit();
        
        $response = [
            'success' => true,
            'message' => "Status updated to {$status}"
        ];
        
        // Add material info if delivered
        if ($status === 'delivered' && isset($material)) {
            $response['material_updated'] = true;
            $response['material_name'] = $material['material_name'];
            $response['new_stock'] = $material['stock'];
            $response['unit'] = $material['unit'];
            $response['quantity_added'] = $request['requested_quantity'];
        }
        
        echo json_encode($response);
        
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
