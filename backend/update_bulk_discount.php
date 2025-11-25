<?php
require('../../config/session_admin.php');
require('../../config/db.php');

header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $user_id = $_POST['user_id'] ?? null;
    $discount_rate = $_POST['discount_rate'] ?? null;
    
    if(!$user_id || $discount_rate === null){
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    // Validate discount rate
    if($discount_rate < 0 || $discount_rate > 100){
        echo json_encode(['success' => false, 'message' => 'Discount must be between 0% and 100%']);
        exit;
    }
    
    try {
        // Update discount rate
        $stmt = $pdo->prepare("UPDATE users SET discount_rate = :discount_rate WHERE user_id = :user_id AND usertype_id = 3");
        $stmt->execute([
            ':discount_rate' => $discount_rate,
            ':user_id' => $user_id
        ]);
        
        if($stmt->rowCount() > 0){
            echo json_encode(['success' => true, 'message' => 'Discount updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found or not a bulk buyer']);
        }
        
    } catch(PDOException $e){
        error_log("Update discount error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
