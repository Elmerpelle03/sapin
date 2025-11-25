<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            m.material_id,
            m.material_name,
            m.description,
            m.stock,
            mu.unit
        FROM materials m
        LEFT JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id
        ORDER BY m.material_name
    ");
    
    $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($materials);
    
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
