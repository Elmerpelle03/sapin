<?php
require('../../config/session_admin.php');
require('../../config/db.php');

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $user_id = $_POST['user_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    
    if(!$user_id || empty($message)){
        $_SESSION['error_message'] = "Invalid data.";
        header('Location: ../bulk_messages.php');
        exit;
    }
    
    try {
        // Insert admin reply as UNREAD for the buyer so a badge appears in their navbar
        $stmt = $pdo->prepare("INSERT INTO bulk_buyer_messages (user_id, sender_type, message, is_read) 
                               VALUES (:user_id, 'admin', :message, 0)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':message' => $message
        ]);

        // Create a customer notification so they see it in their notifications dropdown
        try {
            $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at)
                                         VALUES (:user_id, 0, :title, :message, 'message', 0, NOW())");
            $notif_stmt->execute([
                ':user_id' => $user_id,
                ':title' => 'New message from Admin',
                ':message' => mb_substr($message, 0, 140)
            ]);
        } catch (PDOException $e2) {
            error_log('Failed to create admin message notification: ' . $e2->getMessage());
        }

        $_SESSION['success_message'] = "Reply sent successfully!";
        header('Location: ../bulk_messages.php?user_id=' . $user_id);
        exit;
        
    } catch(PDOException $e){
        error_log("Reply message error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to send reply.";
        header('Location: ../bulk_messages.php?user_id=' . $user_id);
        exit;
    }
}
?>
