<?php
require('../config/session.php');
require('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $reason = trim($_POST['reason'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$order_id || empty($reason)) {
        echo json_encode([
            'success' => false,
            'message' => 'Order ID and cancellation reason are required.'
        ]);
        exit();
    }

    try {
        // Check if order belongs to user
        $stmt = $pdo->prepare("SELECT order_id, status FROM orders WHERE order_id = :order_id AND user_id = :user_id");
        $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode([
                'success' => false,
                'message' => 'Order not found or does not belong to you.'
            ]);
            exit();
        }

        // Check if order can be cancelled (not shipped or completed)
        if (in_array($order['status'], ['shipped', 'completed', 'cancelled'])) {
            echo json_encode([
                'success' => false,
                'message' => 'This order cannot be cancelled as it has already been ' . $order['status'] . '.'
            ]);
            exit();
        }

        // Check if there's already a pending cancellation request
        $stmt = $pdo->prepare("SELECT cancellation_id, status FROM cancellation_requests WHERE order_id = :order_id AND status = 'pending'");
        $stmt->execute([':order_id' => $order_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            echo json_encode([
                'success' => false,
                'message' => 'You already have a pending cancellation request for this order.'
            ]);
            exit();
        }

        // Insert cancellation request
        $stmt = $pdo->prepare("
            INSERT INTO cancellation_requests (order_id, user_id, reason, requested_at) 
            VALUES (:order_id, :user_id, :reason, NOW())
        ");
        
        $stmt->execute([
            ':order_id' => $order_id,
            ':user_id' => $user_id,
            ':reason' => $reason
        ]);

        // Don't change the order status - keep it at current status (Processing, Pending, etc.)
        // The cancellation_requests table tracks the pending request

        echo json_encode([
            'success' => true,
            'message' => 'Cancellation request submitted successfully. Please wait for admin approval.'
        ]);

    } catch (PDOException $e) {
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
