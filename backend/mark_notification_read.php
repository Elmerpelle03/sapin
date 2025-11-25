<?php
require('../config/session.php');
require('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = $_POST['notification_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($notification_id) {
        try {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $notification_id, ':user_id' => $user_id]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
