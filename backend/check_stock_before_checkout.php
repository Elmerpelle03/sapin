<?php
require('../config/session.php');
require('../config/db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $selected_ids = isset($_POST['selected_cart_ids']) ? $_POST['selected_cart_ids'] : [];

    try {
        // Get cart items with variant-aware stock calculation
        if (!empty($selected_ids)) {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT c.*, p.product_name, 
                                  COALESCE(vs.total_stock, p.stock) AS display_stock, 
                                  p.stock 
                                FROM cart c 
                                JOIN products p ON c.product_id = p.product_id 
                                LEFT JOIN (
                                    SELECT product_id, 
                                           SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock
                                    FROM product_variants
                                    GROUP BY product_id
                                ) vs ON vs.product_id = p.product_id 
                                WHERE c.user_id = ? AND c.cart_id IN ($placeholders)");
            $stmt->execute(array_merge([$user_id], $selected_ids));
        } else {
            $stmt = $pdo->prepare("SELECT c.*, p.product_name, 
                                  COALESCE(vs.total_stock, p.stock) AS display_stock, 
                                  p.stock 
                                FROM cart c 
                                JOIN products p ON c.product_id = p.product_id 
                                LEFT JOIN (
                                    SELECT product_id, 
                                           SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock
                                    FROM product_variants
                                    GROUP BY product_id
                                ) vs ON vs.product_id = p.product_id 
                                WHERE c.user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
        }
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items)) {
            echo json_encode([
                'success' => false,
                'has_stock_issues' => false,
                'message' => 'Your cart is empty.'
            ]);
            exit();
        }

        // Check for stock issues using display_stock (variant-aware)
        $stock_issues = [];
        foreach ($cart_items as $item) {
            // Use display_stock which includes variant stock
            $effective_stock = isset($item['display_stock']) ? $item['display_stock'] : $item['stock'];
            
            if ($effective_stock <= 0) {
                $stock_issues[] = [
                    'product' => $item['product_name'],
                    'requested' => $item['quantity'],
                    'available' => 0,
                    'out_of_stock' => true
                ];
            } elseif ($item['quantity'] > $effective_stock) {
                $stock_issues[] = [
                    'product' => $item['product_name'],
                    'requested' => $item['quantity'],
                    'available' => $effective_stock,
                    'out_of_stock' => false
                ];
            }
        }

        if (!empty($stock_issues)) {
            echo json_encode([
                'success' => false,
                'has_stock_issues' => true,
                'stock_issues' => $stock_issues,
                'message' => 'Some items in your cart have stock availability issues.'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'has_stock_issues' => false,
                'message' => 'All items are available.'
            ]);
        }

    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'has_stock_issues' => false,
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
