<?php
require('../../config/session_admin.php');
require('../../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cancellation_id = $_POST['cancellation_id'] ?? null;
    $action = $_POST['action'] ?? null; // 'approve' or 'reject'
    $admin_response = trim($_POST['admin_response'] ?? '');
    $admin_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$cancellation_id || !$action || empty($admin_response)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required.'
        ]);
        exit();
    }

    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action.'
        ]);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Get cancellation request details
        $stmt = $pdo->prepare("
            SELECT cr.*, o.user_id, o.order_id 
            FROM cancellation_requests cr
            JOIN orders o ON cr.order_id = o.order_id
            WHERE cr.cancellation_id = :cancellation_id AND cr.status = 'pending'
        ");
        $stmt->execute([':cancellation_id' => $cancellation_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Cancellation request not found or already processed.'
            ]);
            exit();
        }

        $new_status = ($action === 'approve') ? 'approved' : 'rejected';

        // Update cancellation request
        $stmt = $pdo->prepare("
            UPDATE cancellation_requests 
            SET status = :status, 
                admin_response = :admin_response, 
                admin_id = :admin_id, 
                responded_at = NOW()
            WHERE cancellation_id = :cancellation_id
        ");
        
        $stmt->execute([
            ':status' => $new_status,
            ':admin_response' => $admin_response,
            ':admin_id' => $admin_id,
            ':cancellation_id' => $cancellation_id
        ]);

        // Update order status
        if ($action === 'approve') {
            // Approved - cancel the order
            $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = :order_id");
            $stmt->execute([':order_id' => $request['order_id']]);
            
            $notification_type = 'cancellation_approved';
            $notification_title = 'Cancellation Request Approved';
            $notification_message = "Your cancellation request for Order #{$request['order_id']} has been approved. Reason: {$admin_response}";
        } else {
            // Rejected - keep the original order status (don't change it)
            // Admin can continue updating the order status normally
            
            $notification_type = 'cancellation_rejected';
            $notification_title = 'Cancellation Request Rejected';
            $notification_message = "Your cancellation request for Order #{$request['order_id']} has been rejected. Reason: {$admin_response}";
        }

        // Create notification for customer
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, order_id, type, title, message, is_read, created_at) 
                VALUES (:user_id, :order_id, :type, :title, :message, 0, NOW())
            ");
            
            $notif_result = $stmt->execute([
                ':user_id' => $request['user_id'],
                ':order_id' => $request['order_id'],
                ':type' => $notification_type,
                ':title' => $notification_title,
                ':message' => $notification_message
            ]);
            
            error_log("Notification created for user_id: " . $request['user_id'] . ", order_id: " . $request['order_id']);
        } catch (PDOException $e) {
            error_log("Notification error: " . $e->getMessage());
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Cancellation request {$new_status} successfully."
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
