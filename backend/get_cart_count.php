<?php
require('../config/db.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?? 0;
    
    echo json_encode(['success' => true, 'count' => $cart_count]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
?>
