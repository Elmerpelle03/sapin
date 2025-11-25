<?php
require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $rider_id = $_POST['rider_id'] ?? null;
    $cancel_reason = $_POST['cancel_reason'] ?? null;

    if (empty($order_id) || empty($status)) {
        $_SESSION['error_message'] = "Order ID and status are required!";
        header('Location: ../view_order.php?order_id=' . $order_id);
        exit;
    }

    $valid_statuses = ['Pending', 'Processing', 'Shipping', 'Delivered', 'Cancelled', 'Received'];
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status selected!";
        header('Location: ../view_order.php?order_id=' . $order_id);
        exit;
    }

    try {
        // Get order details and user_id before updating
        $order_query = "SELECT user_id, amount, status FROM orders WHERE order_id = :order_id";
        $order_stmt = $pdo->prepare($order_query);
        $order_stmt->execute([':order_id' => $order_id]);
        $order_data = $order_stmt->fetch(PDO::FETCH_ASSOC);

        // Prevent cancellation if order is already Shipping, Delivered, or Received
        if ($status === 'Cancelled' && $order_data) {
            $statusOrder = ['Pending' => 0, 'Processing' => 1, 'Shipping' => 2, 'Delivered' => 3, 'Received' => 4];
            $currentStatusLevel = $statusOrder[$order_data['status']] ?? 0;
            
            if ($currentStatusLevel >= 2) {
                $_SESSION['error_message'] = "Cannot cancel order once it is shipping or has been delivered.";
                header('Location: ../view_order.php?order_id=' . $order_id);
                exit;
            }
        }

        // If cancelling, require a reason
        if ($status === 'Cancelled' && (empty($cancel_reason) || trim($cancel_reason) === '')) {
            $_SESSION['error_message'] = "Please select or provide a reason for cancellation before saving.";
            header('Location: ../view_order.php?order_id=' . $order_id);
            exit;
        }

        // Base query and parameters
        $query = "UPDATE orders SET status = :status";
        $params = [
            ':status' => $status,
            ':order_id' => $order_id
        ];

        // Append rider_id if Shipping
        if ($status === 'Shipping' && !empty($rider_id)) {
            $query .= ", rider_id = :rider_id";
            $params[':rider_id'] = $rider_id;
        }

        // Append cancel_reason if Cancelled
        if ($status === 'Cancelled' && !empty($cancel_reason)) {
            $query .= ", cancel_reason = :cancel_reason";
            $params[':cancel_reason'] = $cancel_reason;
        }

        $query .= " WHERE order_id = :order_id";

        // Prepare and execute
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        // Create notification if status is Delivered
        if ($status === 'Delivered' && $order_data) {
            $notification_title = "Order Delivered!";
            $notification_message = "Your order #" . $order_id . " has been successfully delivered. Total: ₱" . number_format($order_data['amount'], 2);
            
            $notif_stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, order_id, title, message, type, is_read) 
                VALUES (:user_id, :order_id, :title, :message, 'success', 0)
            ");
            $notif_stmt->execute([
                ':user_id' => $order_data['user_id'],
                ':order_id' => $order_id,
                ':title' => $notification_title,
                ':message' => $notification_message
            ]);
        }

        $_SESSION['success_message'] = "Order status updated successfully." . ($status === 'Delivered' ? " Customer has been notified." : "");
        header("Location: ../view_order.php?order_id=" . $order_id);
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['error_message'] = "Unexpected error: " . $e->getMessage();
        header('Location: ../view_order.php?order_id=' . $order_id);
        exit;
    }
}
?>
