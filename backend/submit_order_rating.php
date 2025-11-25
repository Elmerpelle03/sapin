<?php
require('../config/db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to rate the order.";
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    if (empty($order_id) || empty($rating)) {
        $_SESSION['error_message'] = "Please provide a rating.";
        header('Location: ../order_details.php?order_id=' . $order_id);
        exit;
    }

    $rating = max(1, min(5, (int)$rating));

    // Verify the order belongs to the user and is received
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id AND status = 'Received'");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error_message'] = "Invalid order or order not received yet.";
        header('Location: ../orders.php');
        exit;
    }

    // Insert or update the rating
    $insert = $pdo->prepare("
        INSERT INTO order_ratings (order_id, user_id, rating, comment, created_at)
        VALUES (:order_id, :user_id, :rating, :comment, NOW())
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = VALUES(created_at)
    ");
    $insert->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id,
        ':rating' => $rating,
        ':comment' => $comment
    ]);

    $_SESSION['success_message'] = "Order rating submitted successfully!";
    header('Location: ../order_details.php?order_id=' . $order_id);
    exit;
} else {
    $_SESSION['error_message'] = "Invalid request method.";
    header('Location: ../orders.php');
    exit;
}
?>
