<?php
session_start();
require '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    if (isset($_POST['mark_all']) && $_POST['mark_all'] == 1) {
        // Mark all delivery and return notifications as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = :user_id 
            AND is_read = 0 
            AND (title LIKE '%Delivered%' OR title LIKE '%Return%')
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } elseif (isset($_POST['notification_id'])) {
        // Mark single notification as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = :notification_id 
            AND user_id = :user_id
        ");
        $stmt->execute([
            ':notification_id' => $_POST['notification_id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
