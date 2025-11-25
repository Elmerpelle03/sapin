<?php
require('../config/db.php');  // Include the database connection
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo 'not_logged_in';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $product_id = (int)$_POST['product_id'];
    $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : 0; // 0 means no variant provided
    $user_id = (int)$_SESSION['user_id'];
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;

    $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $is_verified = $stmt->fetchColumn();

    if (!$is_verified) {
        echo 'not_verified';
        exit;
    }

    // Check if user already has 50 unique items in cart (per product+variant)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $product_count = $stmt->fetchColumn();

    // Check if item (product+variant) already exists in cart
    if ($variant_id > 0) {
        $stmt = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id AND variant_id = :variant_id");
        $stmt->execute([':user_id' => $user_id, ':product_id' => $product_id, ':variant_id' => $variant_id]);
    } else {
        $stmt = $pdo->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id AND (variant_id IS NULL OR variant_id = 0)");
        $stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
    }
    $existing_product = $stmt->fetch();

    // If product doesn't exist and user already has 50 products, reject
    if (!$existing_product && $product_count >= 50) {
        echo 'cart_limit_reached';
        exit;
    }

    // Determine applicable stock and unit price
    $variant_size = null;
    if ($variant_id > 0) {
        // Variant path
        $stmt = $pdo->prepare("SELECT v.stock, v.price, v.size, p.product_name FROM product_variants v JOIN products p ON p.product_id = v.product_id WHERE v.variant_id = :vid AND v.product_id = :pid AND v.is_active = 1");
        $stmt->execute([':vid' => $variant_id, ':pid' => $product_id]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['stock'] <= 0) {
            echo "out_of_stock";
            exit;
        }
        $stock_available = (int)$row['stock'];
        $base_price = (float)$row['price'];
        $product_name = $row['product_name'];
        $variant_size = $row['size'];
    } else {
        // Legacy no-variant path
        $stmt = $pdo->prepare("SELECT stock, price, product_name FROM products WHERE product_id = :product_id");
        $stmt->execute([':product_id' => $product_id]);
        $row = $stmt->fetch();
        if (!$row || (int)$row['stock'] <= 0) {
            echo "out_of_stock";
            exit;
        }
        $stock_available = (int)$row['stock'];
        $base_price = (float)$row['price'];
        $product_name = $row['product_name'];
    }

    // Current cart qty for this product+variant
    if ($variant_id > 0) {
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id AND variant_id = :variant_id");
        $stmt->execute([':user_id' => $user_id, ':product_id' => $product_id, ':variant_id' => $variant_id]);
    } else {
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id AND (variant_id IS NULL OR variant_id = 0)");
        $stmt->execute([':user_id' => $user_id, ':product_id' => $product_id]);
    }
    $cart_item = $stmt->fetch();
    $current_cart_qty = $cart_item ? (int)$cart_item['quantity'] : 0;

    // Calculate total quantity after adding
    $total_quantity = $current_cart_qty + $quantity;

    // Check if total quantity exceeds stock
    if ($total_quantity > $stock_available) {
        $remaining = $stock_available - $current_cart_qty;
        $size_text = $variant_size ? " (Size: {$variant_size})" : "";
        if ($remaining <= 0) {
            echo json_encode([
                'status' => 'cart_full',
                'message' => "Cannot add more items! You have {$current_cart_qty} in your cart{$size_text} but only {$stock_available} available in stock.",
                'current_cart_qty' => $current_cart_qty,
                'stock' => $stock_available,
                'remaining' => 0,
                'variant_size' => $variant_size
            ]);
        } else {
            echo json_encode([
                'status' => 'exceeds_stock',
                'message' => "Limited stock for this size! You currently have {$current_cart_qty} in your cart{$size_text}. Only {$remaining} more can be added.",
                'current_cart_qty' => $current_cart_qty,
                'stock' => $stock_available,
                'remaining' => $remaining,
                'variant_size' => $variant_size
            ]);
        }
        exit;
    }

    // Compute unit price snapshot (apply bulk discount if buyer is bulk)
    $stmt = $pdo->prepare("SELECT usertype_id, discount_rate FROM users WHERE user_id = :uid");
    $stmt->execute([':uid' => $user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    $unit_price = $base_price;
    
    // Check if user is a wholesaler
    $is_wholesaler = $u && (int)$u['usertype_id'] === 3;
    
    // For wholesalers, check if quantity meets minimum requirement
    if ($is_wholesaler && $total_quantity < 20) {
        echo 'minimum_quantity_not_met';
        exit;
    }
    
    // For wholesalers, also check if stock is sufficient for the requested quantity
    if ($is_wholesaler && $stock_available < $total_quantity) {
        echo 'insufficient_stock_for_wholesale';
        exit;
    }
    
    if ($u && (int)$u['usertype_id'] === 3 && (float)$u['discount_rate'] > 0) {
        $unit_price = round($base_price * (1 - ((float)$u['discount_rate'] / 100)), 2);
    }

    // Function to add product to cart
    function add_to_cart($product_id, $variant_id, $user_id, $quantity, $unit_price) {
        global $pdo;

        // Check if the product+variant is already in the user's cart
        if ($variant_id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE product_id = :product_id AND user_id = :user_id AND variant_id = :variant_id");
            $stmt->execute([':product_id' => $product_id, ':user_id' => $user_id, ':variant_id' => $variant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE product_id = :product_id AND user_id = :user_id AND (variant_id IS NULL OR variant_id = 0)");
            $stmt->execute([':product_id' => $product_id, ':user_id' => $user_id]);
        }
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            // Update the quantity if the product is already in the cart
            $new_quantity = (int)$cart_item['quantity'] + $quantity;

            $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity, unit_price = :unit_price WHERE cart_id = :cart_id");
            $stmt->execute([':quantity' => $new_quantity, ':unit_price' => $unit_price, ':cart_id' => $cart_item['cart_id']]);
        } else {
            // If the product is not in the cart, insert a new entry
            $stmt = $pdo->prepare("INSERT INTO cart (product_id, variant_id, user_id, quantity, unit_price) VALUES (:product_id, :variant_id, :user_id, :quantity, :unit_price)");
            $stmt->execute([':product_id' => $product_id, ':variant_id' => ($variant_id>0?$variant_id:null), ':user_id' => $user_id, ':quantity' => $quantity, ':unit_price' => $unit_price]);
        }
    }

    // Add the product to the cart
    add_to_cart($product_id, $variant_id, $user_id, $quantity, $unit_price);
    echo json_encode([
        'status' => 'success',
        'message' => 'Product added to cart!'
    ]);
    exit;
}
?>
