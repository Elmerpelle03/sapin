<?php
require('../config/db.php');
session_start();

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $user_id = $_SESSION['user_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    
    if(!$user_id){
        $_SESSION['error_message'] = "Please log in.";
        header('Location: ../login.php');
        exit;
    }
    
    if(empty($message)){
        $_SESSION['error_message'] = "Message cannot be empty.";
        header('Location: ../message_admin.php');
        exit;
    }
    
    // Check if user is wholesaler
    $stmt = $pdo->prepare("SELECT usertype_id FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $usertype = $stmt->fetchColumn();
    
    if($usertype != 3){
        $_SESSION['error_message'] = "This feature is only available for wholesalers.";
        header('Location: ../index.php');
        exit;
    }
    
    try {
        // Insert message
        $stmt = $pdo->prepare("INSERT INTO bulk_buyer_messages (user_id, sender_type, message, is_read) 
                               VALUES (:user_id, 'buyer', :message, 0)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':message' => $message
        ]);
        
        $_SESSION['success_message'] = "Message sent successfully!";
        header('Location: ../message_admin.php');
        exit;
        
    } catch(PDOException $e){
        error_log("Send message error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to send message. Please try again.";
        header('Location: ../message_admin.php');
        exit;
    }
}
?>
