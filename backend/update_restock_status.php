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
        $stmt = $pdo->prepare("
            UPDATE material_restock_requests 
            SET status = :status 
            WHERE request_id = :request_id
        ");
        
        $stmt->execute([
            'status' => $status,
            'request_id' => $request_id
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => "Request status updated to {$status}"
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
