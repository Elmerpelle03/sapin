<?php
header('Content-Type: application/json');

require ('../config/db.php');
require ('../config/session.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_id = $_POST['cart_item_id'];
    $quantity = $_POST['quantity'];

    // Check if user is a wholesaler (usertype_id = 3)
    $is_wholesaler = isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 3;
    $min_quantity = $is_wholesaler ? 20 : 1;

    if (is_numeric($cart_id) && is_numeric($quantity) && $quantity >= $min_quantity) {
        // Get the available stock for this cart item
        $stmt_stock = $pdo->prepare("
            SELECT p.stock 
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.cart_id = :cart_id
        ");
        $stmt_stock->execute([':cart_id' => $cart_id]);
        $available_stock = $stmt_stock->fetchColumn();
        
        // Validate quantity against stock
        if ($quantity > $available_stock) {
            echo json_encode([
                'success' => false, 
                'message' => "Only {$available_stock} items available in stock."
            ]);
            exit;
        }
        
        // Update cart with validated quantity
        $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE cart_id = :cart_id");
        if ($stmt->execute([':quantity' => $quantity, ':cart_id' => $cart_id])) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
    
    // Check if the issue is minimum quantity
    if ($is_wholesaler && $quantity < 20) {
        echo json_encode([
            'success' => false, 
            'message' => 'Minimum quantity for wholesale orders is 20 items.'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity or cart item.']);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
