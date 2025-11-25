<?php
require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        echo "Invalid request.";
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM product_materials WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        echo "success";
        
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
}
?>
