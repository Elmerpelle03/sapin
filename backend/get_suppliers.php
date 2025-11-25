<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            supplier_id,
            supplier_name,
            company_name,
            contact_type,
            notes,
            is_active
        FROM suppliers
        WHERE is_active = 1
        ORDER BY supplier_name
    ");
    
    $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Note: We don't decrypt or send the actual contact info
    // Owner will see it only when sending orders
    
    echo json_encode([
        'success' => true,
        'suppliers' => $suppliers
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
