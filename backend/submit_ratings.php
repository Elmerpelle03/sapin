<?php
require('../config/db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in to rate items.";
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $ratings = $_POST['ratings'] ?? [];
    $comments = $_POST['comments'] ?? [];

    if (empty($order_id) || empty($ratings)) {
        $_SESSION['error_message'] = "Please rate all items.";
        header('Location: ../rate.php?id='.$order_id);
        exit;
    }
    

    // Optional: verify the order belongs to the user
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error_message'] = "Invalid order or access denied.";
        header('Location: ../orders.php');
        exit;
    }

    // Insert or update each rating
    foreach ($ratings as $product_id => $rating) {
        $comment = trim($comments[$product_id] ?? '');

        $rating = max(1, min(5, (int)$rating));

        $insert = $pdo->prepare("
            INSERT INTO item_ratings (order_id, product_id, user_id, rating, comment, created_at)
            VALUES (:order_id, :product_id, :user_id, :rating, :comment, :created_at)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = VALUES(created_at)
        ");
        $insert->execute([
            ':order_id' => $order_id,
            ':product_id' => $product_id,
            ':user_id' => $user_id,
            ':rating' => $rating,
            ':comment' => $comment,
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }

    $_SESSION['success_message'] = "Ratings submitted successfully!";
    header('Location: ../orders.php');
    exit;
} else {
    $_SESSION['error_message'] = "Invalid request method.";
    header('Location: ../rate.php?id='.$order_id);
    exit;
}
