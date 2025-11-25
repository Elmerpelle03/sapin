<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            r.request_id,
            r.material_id,
            m.material_name,
            m.unit,
            r.requested_quantity,
            r.current_stock,
            r.reason,
            r.requested_by,
            DATE_FORMAT(r.requested_date, '%Y-%m-%d %H:%i') as requested_date,
            r.status,
            r.owner_notes
        FROM material_restock_requests r
        JOIN materials m ON r.material_id = m.material_id
        ORDER BY r.requested_date DESC
    ");
    
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $requests]);
    
} catch (PDOException $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
?>
