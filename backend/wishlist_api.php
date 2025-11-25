<?php
require('../config/db.php');
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $product_id = intval($_POST['product_id']);
            
            // Check if already in wishlist
            $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Already in wishlist']);
                exit;
            }
            
            // Add to wishlist
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Added to wishlist']);
            break;
            
        case 'remove':
            $product_id = intval($_POST['product_id']);
            
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist']);
            break;
            
        case 'toggle':
            $product_id = intval($_POST['product_id']);
            
            // Check if exists
            $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            if ($stmt->fetch()) {
                // Remove
                $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $product_id]);
                echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from wishlist']);
            } else {
                // Add
                $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $stmt->execute([$user_id, $product_id]);
                echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to wishlist']);
            }
            break;
            
        case 'get':
            // Get user discount rate for wholesalers
            $user_discount = 0;
            $stmt_user = $pdo->prepare("SELECT usertype_id, discount_rate FROM users WHERE user_id = ?");
            $stmt_user->execute([$user_id]);
            $user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            if($user_info && $user_info['usertype_id'] == 3){
                $user_discount = $user_info['discount_rate'];
            }
            
            // Get all wishlist items with product details and variant-aware pricing
            $stmt = $pdo->prepare("
                SELECT 
                    w.wishlist_id,
                    w.product_id,
                    w.created_at,
                    p.product_name,
                    COALESCE(vs.from_price, p.price) AS price,
                    p.stock,
                    p.image_url
                FROM wishlist w
                JOIN products p ON w.product_id = p.product_id
                LEFT JOIN (
                    SELECT product_id,
                           MIN(CASE WHEN is_active = 1 THEN price END) AS from_price
                    FROM product_variants
                    GROUP BY product_id
                ) vs ON vs.product_id = p.product_id
                WHERE w.user_id = ?
                ORDER BY w.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Apply wholesaler discount
            foreach ($items as &$item) {
                if($user_discount > 0){
                    $item['original_price'] = $item['price'];
                    $item['price'] = $item['price'] * (1 - ($user_discount / 100));
                }
            }
            
            echo json_encode(['success' => true, 'items' => $items]);
            break;
            
        case 'get_ids':
            // Get only product IDs (for checking if product is in wishlist)
            $stmt = $pdo->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode(['success' => true, 'product_ids' => $ids]);
            break;
            
        case 'count':
            // Get wishlist count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $count = $stmt->fetchColumn();
            
            echo json_encode(['success' => true, 'count' => $count]);
            break;
            
        case 'clear':
            // Clear all wishlist items
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Wishlist cleared']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    error_log("Wishlist API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
