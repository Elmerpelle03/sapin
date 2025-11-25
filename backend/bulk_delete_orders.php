<?php
require '../config/db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo 'error: not logged in';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_ids = $_POST['order_ids'] ?? [];

    if (empty($order_ids)) {
        echo 'error: no orders selected';
        exit();
    }

    // Convert to array if single value
    if (!is_array($order_ids)) {
        $order_ids = [$order_ids];
    }

    // Sanitize order_ids
    $order_ids = array_map('intval', $order_ids);

    // Only allow deleting past orders (delivered, received, cancelled)
    $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE order_id IN ($placeholders) AND user_id = ? AND status IN ('delivered', 'received', 'cancelled')");
    $params = array_merge($order_ids, [$user_id]);
    $stmt->execute($params);
    $valid_orders = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($valid_orders)) {
        echo 'error: no valid orders to delete';
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Delete order items first
        $placeholders = str_repeat('?,', count($valid_orders) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id IN ($placeholders)");
        $stmt->execute($valid_orders);

        // Delete orders
        $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id IN ($placeholders) AND user_id = ?");
        $params = array_merge($valid_orders, [$user_id]);
        $stmt->execute($params);

        $pdo->commit();

        // Check if request is AJAX
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => 'Selected orders deleted successfully.']);
        } else {
            $_SESSION['success_message'] = 'Selected orders deleted successfully.';
            header('Location: ../orders.php');
        }
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'Error deleting orders.']);
        } else {
            $_SESSION['error_message'] = 'Error deleting orders.';
            header('Location: ../orders.php');
        }
        exit();
    }
} else {
    header('Location: ../orders.php');
    exit();
}
?>
