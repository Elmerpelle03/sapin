<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $material_id = $_POST['material_id'] ?? null;
    $requested_quantity = $_POST['requested_quantity'] ?? null;
    $supplier_contact = $_POST['supplier_contact'] ?? null;
    $contact_type = $_POST['contact_type'] ?? null;
    $message = $_POST['message'] ?? null;
    $current_stock = $_POST['current_stock'] ?? 0;
    
    if (!$material_id || !$requested_quantity || !$supplier_contact || !$contact_type) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        $requested_by = $_SESSION['admin_username'] ?? 'Admin';
        
        $stmt = $pdo->prepare("
            INSERT INTO material_supplier_requests 
            (material_id, requested_quantity, current_stock, supplier_contact, contact_type, message, requested_by, status)
            VALUES (:material_id, :quantity, :current_stock, :supplier_contact, :contact_type, :message, :requested_by, 'pending')
        ");
        
        $stmt->execute([
            'material_id' => $material_id,
            'quantity' => $requested_quantity,
            'current_stock' => $current_stock,
            'supplier_contact' => $supplier_contact,
            'contact_type' => $contact_type,
            'message' => $message,
            'requested_by' => $requested_by
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Supplier request saved successfully',
            'request_id' => $pdo->lastInsertId()
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
