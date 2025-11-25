<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'mark_all') {
        // Mark all notifications as read for this user
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE user_id = :user_id AND is_read = 0
        ");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);

        // Also mark admin messages as read to sync navbar message badge
        $stmt2 = $pdo->prepare("
            UPDATE bulk_buyer_messages
            SET is_read = 1
            WHERE user_id = :user_id AND sender_type = 'admin' AND is_read = 0
        ");
        $stmt2->execute([':user_id' => $_SESSION['user_id']]);

        echo json_encode(['success' => true, 'message' => 'All notifications and messages marked as read']);
    } elseif ($action === 'mark_one' && isset($data['notification_id'])) {
        // Mark single notification as read
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = :notif_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':notif_id' => $data['notification_id'],
            ':user_id' => $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
