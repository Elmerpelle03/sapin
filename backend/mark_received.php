<?php
require '../config/db.php';
require '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['user_id']; // assuming the user is logged in

    // Validate input
    if (empty($order_id)) {
        $_SESSION['error_message'] = "Invalid order ID.";
        header('Location: ../orders.php');
        exit;
    }

    // Check if the order exists, belongs to the user, and is marked as Delivered
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id AND status = 'Delivered'");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error_message'] = "You can only mark your own delivered orders as received.";
        header('Location: ../orders.php');
        exit;
    }

    // Update the order status to Received
    $stmt = $pdo->prepare("UPDATE orders SET status = 'Received' WHERE order_id = :order_id");
    $stmt->execute([
        ':order_id' => $order_id
    ]);

    $_SESSION['success_message'] = "Order #{$order_id} has been marked as received.";
    header('Location: ../rate.php?id='.$order_id.'');
    exit;
}
?>
