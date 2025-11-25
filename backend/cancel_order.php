<?php
require '../config/db.php';
require '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // assuming the user is logged in

    // Optional: allow capturing a reason in the future
    $cancelled_at = date('Y-m-d H:i:s');
    $cancel_reason = 'Cancelled by customer at '.$cancelled_at.'.';


    // Validate input
    if (empty($order_id)) {
        $_SESSION['error_message'] = "Invalid order ID.";
        header('Location: ../orders.php');
        exit;
    }

    // Check if the order exists and belongs to the user and is still pending
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id AND status = 'Pending'");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error_message'] = "You can only cancel pending orders you own.";
        header('Location: ../orders.php');
        exit;
    }

    // Update the order status to Cancelled
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Cancelled', cancel_reason = :cancel_reason WHERE order_id = :order_id");
    $stmt->execute([
        ':cancel_reason' => $cancel_reason,
        ':order_id' => $order_id
    ]);

    $_SESSION['success_message'] = "Order #{$order_id} has been cancelled.";
    header('Location: ../orders.php');
    exit;
}
?>
