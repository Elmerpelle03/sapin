<?php 
    require ('config/db.php');
    require ('config/session.php');
    require ('config/details_checker.php');
    require('config/session_disallow_courier.php');
    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cart_id = $_POST['cart_item_id'];
    
        if ($_POST['action'] === 'delete') {
            delete_cart_item($cart_id);
            $_SESSION['success_message'] = 'Item removed from cart.';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
    
        if ($_POST['action'] === 'update') {
            $current_quantity = get_quantity_from_db($cart_id);
            $change = isset($_POST['quantity_change']) ? intval($_POST['quantity_change']) : 0;
            $new_quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : $current_quantity;
            
            // Check if user is a wholesaler (usertype_id = 3)
            $is_wholesaler = isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 3;
            $min_quantity = $is_wholesaler ? 20 : 1;
            
            // Get current stock for this cart item
            $available_stock = get_stock_from_cart($cart_id);
        
            if ($change !== 0) {
                $new_quantity = max($min_quantity, $current_quantity + $change);
            } else {
                $new_quantity = max($min_quantity, $new_quantity);
            }
            
            // For wholesalers, enforce minimum quantity
            if ($is_wholesaler && $new_quantity < 20) {
                $new_quantity = 20;
                $_SESSION['error_message'] = "Minimum quantity for wholesale orders is 20 items.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
            
            // Handle stock validation and auto-adjustment
            if ($new_quantity > $available_stock) {
                // Auto-adjust to maximum available stock
                $new_quantity = $available_stock;
                update_cart_quantity($cart_id, $new_quantity);
                $_SESSION['error_message'] = "Only {$available_stock} items available. Quantity adjusted to maximum available.";
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit;
            }
        
            if ($new_quantity === 0) {
                delete_cart_item($cart_id);
                $_SESSION['success_message'] = 'Item removed from cart.';
            } else {
                // Only update if quantity actually changed
                if ($new_quantity !== $current_quantity) {
                    update_cart_quantity($cart_id, $new_quantity);
                }
                // No success message for normal quantity updates
            }
        
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit;
        }
        
    }
    
    // FUNCTIONS
    
    function get_quantity_from_db($cart_id) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $cart_id]);
        return $stmt->fetchColumn();
    }
    
    function get_stock_from_cart($cart_id) {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT COALESCE(pv.stock, p.stock) AS stock
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            LEFT JOIN product_variants pv ON c.variant_id = pv.variant_id AND pv.is_active = 1
            WHERE c.cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $cart_id]);
        return $stmt->fetchColumn();
    }
    
    function update_cart_quantity($cart_id, $new_quantity) {
        global $pdo;
        $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE cart_id = :cart_id");
        $stmt->execute([':quantity' => $new_quantity, ':cart_id' => $cart_id]);
    }
    
    function delete_cart_item($cart_id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $cart_id]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'clear') {
        clear_cart($_SESSION['user_id']); // or whatever identifies the user
        $_SESSION['success_message'] = 'Cart cleared successfully.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'remove_selected') {
        $selected_cart_ids = $_POST['selected_cart_ids'] ?? [];
        foreach ($selected_cart_ids as $cart_id) {
            delete_cart_item($cart_id);
        }
        $_SESSION['success_message'] = count($selected_cart_ids) . ' item(s) removed from cart.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
    
    function clear_cart($user_id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $user_id]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'remove') {
        $cart_id = $_POST['cart_item_id'];
        remove_cart_item($cart_id); // Remove item from the cart
        $_SESSION['success_message'] = 'Item removed successfully.';
        header("Location: " . $_SERVER['REQUEST_URI']); // Redirect after removal
        exit;
    }
    
    function remove_cart_item($cart_id) {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM cart WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $cart_id]);
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Sapin Bedsheets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
        }
        
        /* Animated Cart Header */
        .cart-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%) !important;
            background-size: 400% 400% !important;
            animation: gradientShift 15s ease infinite !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --light-bg: #f9fafb;
            --border-color: #e5e7eb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
        }
        
        body {
            background-color: var(--light-bg);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        /* Enhanced Header */
        .cart-header {
            background: linear-gradient(135deg, #2563eb 0%, #f59e0b 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .cart-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.3;
        }

        .cart-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .cart-header .lead {
            font-size: 1.1rem;
            opacity: 0.95;
            position: relative;
            z-index: 1;
        }

        /* Cart Stats Bar - Enhanced */
        .cart-stats {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            padding: 2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            justify-content: space-around;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .stat-item {
            text-align: center;
            position: relative;
            flex: 1;
            padding: 1.25rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .stat-item:nth-child(1) {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        }
        
        .stat-item:nth-child(2) {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
        
        .stat-item:nth-child(3) {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        
        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: block;
            opacity: 0.8;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1e293b;
            display: block;
            font-family: 'Segoe UI', 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            margin-top: 0.25rem;
            font-family: 'Segoe UI', 'SF Pro Text', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .stat-icon-minimal {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            display: block;
            opacity: 0.7;
        }

        @media (max-width: 768px) {
            .stat-item:not(:last-child)::after {
                display: none;
            }
            
            .cart-stats {
                gap: 0.5rem;
                padding: 0.75rem 0.5rem;
                flex-direction: row;
                justify-content: space-around;
            }
            
            .stat-item {
                padding: 0.5rem;
            }
            
            .stat-icon-minimal {
                font-size: 1.2rem !important;
            }
            
            .stat-value {
                font-size: 1rem !important;
                font-size: 1.25rem;
            }
            
            .stat-label {
                font-size: 0.65rem;
            }
            
            .stat-icon-minimal {
                font-size: 1rem;
                margin-bottom: 0.25rem;
            }
        }

        /* Enhanced Card Styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .card-title {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1.25rem;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }

        /* Cart Item Styling */
        .cart-item {
            transition: all 0.2s ease;
        }

        .cart-item:hover {
            background-color: #f9fafb;
        }
        
        /* Compact row spacing */
        .cart-item-card .row.g-0 {
            align-items: stretch;
        }

        .quantity-input {
            width: 70px;
        }

        /* Summary Card */
        .summary-card {
            position: sticky;
            top: 2rem;
            background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
        }

        .summary-card .card-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Ensure Order Summary stays on the right on desktop/tablet */
        @media (min-width: 768px) {
            .summary-card {
                position: sticky !important;
                top: 2rem !important;
            }
        }

        /* Enhanced Buttons */
        .btn-checkout {
            background: linear-gradient(135deg, #2563eb 0%, #f59e0b 100%);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }

        .btn-checkout:active {
            transform: translateY(0);
        }

        .btn-action {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        /* Empty Cart State */
        .empty-cart-container {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-cart-icon {
            font-size: 5rem;
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .empty-cart-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
        }

        .empty-cart-text {
            color: var(--text-muted);
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* Truncated description styles - REMOVED */
        .cart-item-card {
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin-bottom: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: visible;
            max-width: 100%;
            position: relative;
        }

        .cart-item-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-1px);
            border-color: #cbd5e1;
        }
        
        /* Out of Stock Item Styling */
        .cart-item-card.out-of-stock-item {
            opacity: 0.7;
            background: linear-gradient(135deg, #fff 0%, #fef2f2 100%);
            border: 2px solid #fecaca;
            position: relative;
        }
        
        .cart-item-card.out-of-stock-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(239, 68, 68, 0.03) 10px,
                rgba(239, 68, 68, 0.03) 20px
            );
            pointer-events: none;
        }
        
        .cart-item-card.out-of-stock-item .cart-product-image {
            filter: grayscale(50%);
            opacity: 0.6;
        }
        
        .cart-item-card.out-of-stock-item .product-title {
            color: #6b7280;
        }
        
        .quantity-controls-disabled {
            padding: 1rem;
            background: #fef2f2;
            border-radius: 8px;
            border: 1px dashed #fca5a5;
        }

        .product-image-container {
            position: relative;
            overflow: hidden;
            background: #f8fafc;
            width: 85px;
            height: 85px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .cart-product-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .cart-product-image:hover {
            transform: scale(1.08);
        }
        
        /* Larger checkbox beside image */
        .item-checkbox-large {
            width: 20px;
            height: 20px;
            cursor: pointer;
            flex-shrink: 0;
            border: 2px solid #cbd5e1;
            border-radius: 4px;
        }
        
        .item-checkbox-large:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .item-checkbox-large:hover {
            border-color: #2563eb;
        }
        
        .item-checkbox-large:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background-color: #f3f4f6;
            border-color: #d1d5db;
        }
        
        /* Better column alignment */
        .cart-item-card .col-lg-4 {
            padding-right: 0.75rem;
        }
        
        /* Product details section with image */
        .cart-item-card .col-lg-4 .d-flex {
            gap: 0.75rem;
        }
        
        .cart-item-card .col-lg-2,
        .cart-item-card .col-lg-3 {
            display: flex;
            align-items: center;
        }
        
        /* Add subtle dividers between sections */
        @media (min-width: 992px) {
            .cart-item-card .col-lg-2:not(:first-child),
            .cart-item-card .col-lg-3:not(:first-child) {
                border-left: 1px solid #f1f5f9;
                padding-left: 1rem;
            }
        }

        .product-info {
            padding: 0.75rem 0.875rem;
        }
        
        .product-info .row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .product-title {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .product-attributes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-bottom: 0.4rem;
        }

        .attribute-tag {
            background: #f1f5f9;
            color: #475569;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .stock-info {
            background: #ecfdf5;
            color: #047857;
            padding: 0.2rem 0.5rem;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: 500;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .stock-info .badge {
            white-space: nowrap;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }

        .price-display {
            text-align: center;
        }
        
        .price-label {
            font-size: 0.65rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            display: block;
            margin-bottom: 0.2rem;
        }
        
        .price-amount {
            font-size: 1.05rem;
            font-weight: 700;
            color: #2563eb;
            display: block;
        }

        .quantity-controls {
            background: transparent;
            border-radius: 6px;
            padding: 0.35rem;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            border: 1px solid #cbd5e1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .quantity-btn:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background-color: #e5e7eb;
            border-color: #d1d5db;
        }
        
        .quantity-btn:disabled:hover {
            background: #e5e7eb;
            color: #6b7280;
            border-color: #d1d5db;
            transform: none;
        }
        
        .quantity-btn:not(:disabled):hover {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .quantity-input-modern {
            width: 55px;
            height: 30px;
            text-align: center;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            font-weight: 700;
            font-size: 1rem;
            background: #ffffff !important;
            color: #1e293b !important;
            padding: 0;
            line-height: 30px;
        }
        
        .quantity-input-modern:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: #ffffff !important;
        }
        
        /* Remove spinner arrows for cleaner look */
        .quantity-input-modern::-webkit-outer-spin-button,
        .quantity-input-modern::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .quantity-input-modern[type=number] {
            -moz-appearance: textfield;
        }

        .subtotal-display {
            text-align: right;
            padding-right: 0.5rem;
        }
        
        .subtotal-label {
            font-size: 0.65rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
            display: block;
            margin-bottom: 0.2rem;
        }
        
        .subtotal-amount {
            font-size: 1.15rem;
            font-weight: 700;
            color: #059669;
            display: block;
            white-space: nowrap;
        }

        .remove-btn {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            border: 1px solid #ef4444;
            background: #fef2f2;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            flex-shrink: 0;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .remove-btn:hover {
            background: #ef4444;
            color: white;
        }
        
        /* Delete button at top-right corner of card */
        .remove-btn-top-corner {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #ef4444;
            background: #ffffff;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            cursor: pointer;
            font-size: 0.9rem;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        .remove-btn-top-corner:hover {
            background: #ef4444;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
        }
        
        /* Desktop-specific layout fixes */
        @media (min-width: 992px) {
            .col-lg-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }
            
            .col-lg-1 {
                flex: 0 0 8.333333%;
                max-width: 8.333333%;
            }
            
            .col-lg-2 {
                flex: 0 0 16.666667%;
                max-width: 16.666667%;
            }
            
            .col-lg-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
            
            /* Adjust product details column */
            .cart-item-card .col-lg-4 {
                min-width: 0;
            }
            
            /* Ensure proper alignment on desktop */
            .product-info .row {
                margin-left: 0;
                margin-right: 0;
            }
            
            .product-info .row > [class*='col-'] {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            
            .product-info .row > .col-lg-4:first-child {
                padding-left: 0.875rem;
            }
            
            /* Ensure image stays visible on desktop */
            .product-image-container {
                width: 85px;
                height: 85px;
            }
            
            /* Reduce divider padding */
            .cart-item-card .col-lg-2:not(:first-child),
            .cart-item-card .col-lg-3:not(:first-child) {
                padding-left: 0.75rem !important;
            }
            
            /* Remove button column */
            .cart-item-card .col-lg-1 {
                border-left: 1px solid #f1f5f9;
                padding-left: 0.5rem !important;
            }
        }

        .btn-continue {
            border: 2px solid #2563eb;
            color: #2563eb;
        }

        .btn-continue:hover {
            background: #2563eb;
            color: white;
        }

        .cart-empty {
            text-align: center;
            padding: 3rem;
        }

        .cart-empty i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
    </style>
    <style>
        .custom-navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .custom-navbar .nav-link {
            color: #4a4a4a !important;
            font-weight: 500;
            padding: 0.8rem 1.2rem !important;
            transition: color 0.3s ease;
        }

        .custom-navbar .nav-link:hover,
        .custom-navbar .nav-link.active {
            color: #2563eb !important;
        }

        .custom-navbar .navbar-brand {
            font-weight: 700;
            color: #2563eb !important;
        }

        .badge.cart-badge {
            background-color: #2563eb !important;
            transition: transform 0.2s ease;
        }

        .badge.cart-badge:hover {
            transform: scale(1.1);
        }
        
        /* Tablet/Medium screens - prevent overlapping */
        @media (max-width: 991px) and (min-width: 768px) {
            .stock-info {
                font-size: 0.65rem;
                gap: 0.4rem;
            }
            
            .stock-info .badge {
                font-size: 0.6rem;
                padding: 0.2rem 0.4rem;
            }
            
            .price-amount {
                font-size: 0.95rem;
            }
            
            .subtotal-amount {
                font-size: 1rem;
            }
        }
        
        /* Cart Header Controls - Responsive */
        .cart-header-controls {
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .cart-items-title {
            flex: 1 1 100%;
        }
        
        .cart-action-buttons {
            flex-wrap: wrap;
        }
        
        /* Mobile Responsiveness - Phase 2: Enhanced but Compact */
        @media (max-width: 767px) {
            .cart-header {
                padding: 2rem 0 !important;
            }
            .cart-header h1 {
                font-size: 2rem !important;
            }
            .summary-card {
                position: static !important;
                margin-top: 1.5rem;
            }
            
            /* Mobile cart controls */
            .cart-header-controls {
                flex-direction: column;
                align-items: stretch !important;
            }
            
            .cart-items-title {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }
            
            .cart-title-text {
                font-size: 0.9rem;
            }
            
            .cart-action-buttons {
                width: 100%;
                justify-content: space-between;
            }
            
            .cart-action-buttons .btn {
                flex: 1;
                font-size: 0.7rem;
                padding: 0.45rem 0.35rem;
                min-width: 0;
                white-space: nowrap;
            }
            
            .cart-action-buttons .btn i {
                font-size: 0.85rem;
                margin-right: 0.25rem;
            }
            
            /* More compact mobile cart layout */
            .cart-item-card {
                border-radius: 12px;
                margin-bottom: 1rem;
                overflow: hidden;
                box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            }
            
            .cart-item-card .row {
                flex-direction: column;
            }
            
            /* Keep thumbnail style on mobile */
            .product-image-container {
                width: 90px;
                height: 90px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                margin-bottom: 0.5rem;
            }
            
            .cart-product-image {
                height: 100%;
                width: 100%;
                object-fit: cover;
            }
            
            /* Center image and product info on mobile */
            .cart-item-card .col-lg-4 .d-flex {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            /* Compact mobile product info layout */
            .product-info {
                padding: 0.875rem;
                background: white;
            }
            
            .product-info .row {
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }
            
            .product-title {
                font-size: 1rem;
                margin-bottom: 0.4rem;
                color: #1e293b;
                font-weight: 600;
            }
            
            .product-attributes {
                justify-content: center;
                margin-bottom: 0.5rem;
            }
            
            .attribute-tag {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
            
            .stock-info {
                justify-content: center;
                margin-bottom: 0.75rem;
                font-size: 0.75rem;
                gap: 0.35rem;
            }
            
            .stock-info .badge {
                font-size: 0.65rem;
                padding: 0.2rem 0.4rem;
            }
            
            /* Compact mobile controls - inline layout */
            .mobile-controls-section {
                background: #f8fafc;
                border-radius: 0;
                padding: 1rem;
                margin: 0;
                border: none;
                box-shadow: none;
                border-top: 1px solid #e2e8f0;
            }
            
            .mobile-total-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
            }
            
            .mobile-total-row .subtotal-display {
                margin: 0;
                padding: 0.6rem 1rem;
                background: #f8fafc;
                border-radius: 8px;
                border: 1px solid #cbd5e1;
                flex: 1;
                text-align: left;
            }
            
            .mobile-total-row .subtotal-label {
                font-size: 0.65rem;
                margin-bottom: 0.2rem;
                font-weight: 500;
            }
            
            .mobile-total-row .subtotal-amount {
                font-size: 1.2rem;
            }
            
            /* Mobile delete button - same style as desktop */
            .mobile-total-row .remove-btn {
                width: 36px;
                height: 36px;
                border-radius: 8px;
                border: 1px solid #ef4444;
                background: #fef2f2;
                color: #ef4444;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }
            
            .mobile-total-row .remove-btn:hover {
                background: #ef4444;
                color: white;
            }
            
            .remove-btn {
                width: 36px;
                height: 36px;
                font-size: 1rem;
                margin: 0;
                position: static;
                display: inline-flex;
            }
            
            /* Make main quantity controls visible on mobile */
            .col-lg-3 {
                flex: 0 0 100%;
                max-width: 100%;
                justify-content: center !important;
                margin-bottom: 1rem;
            }
            
            /* Hide desktop subtotal on mobile (we have mobile total instead) */
            .col-lg-3:last-child .subtotal-display {
                display: none !important;
            }
            
            /* Hide desktop remove button on mobile (we have mobile remove button instead) */
            .col-lg-3:last-child .remove-btn {
                display: none !important;
            }
            
            .quantity-controls {
                margin: 0;
                padding: 0.5rem;
                background: white;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                display: inline-flex;
                justify-content: center;
            }
            
            .quantity-input-modern {
                width: 60px;
                height: 36px;
                font-size: 1rem;
                border-radius: 6px;
                font-weight: 600;
            }
            
            .quantity-btn {
                width: 36px;
                height: 36px;
                border-radius: 6px;
                font-size: 1rem;
            }
            
            .subtotal-display {
                font-size: 1.2rem;
                margin: 0;
                color: #059669;
                font-weight: 700;
            }
        }

        @media (max-width: 576px) {
            /* Extra small phones - even more compact buttons */
            .cart-action-buttons .btn {
                font-size: 0.65rem;
                padding: 0.4rem 0.25rem;
            }
            
            .cart-action-buttons .btn i {
                font-size: 0.8rem;
            }
            
            .cart-item-card {
                margin-bottom: 1.25rem;
                border-radius: 14px;
            }
            
            .product-image-container {
                width: 100px;
                height: 100px;
            }
            
            .cart-product-image {
                height: 100%;
            }
            
            .product-info {
                padding: 1.25rem;
            }
            
            .product-title {
                font-size: 1.2rem;
                margin-bottom: 0.75rem;
            }
            
            .mobile-controls-section {
                padding: 1.25rem;
            }
            
            .mobile-total-row .subtotal-display {
                padding: 0.75rem 1.25rem;
            }
            
            .mobile-total-row .subtotal-amount {
                font-size: 1.3rem;
            }
            
            /* Keep desktop-style delete button on small screens too */
            .mobile-total-row .remove-btn {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .quantity-input-modern {
                width: 70px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .quantity-btn {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .remove-btn {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .subtotal-display {
                font-size: 1.3rem;
            }
            
            /* Keep enhanced touch feedback */
            .quantity-btn:active,
            .remove-btn:active {
                transform: scale(0.95);
                transition: transform 0.1s ease;
            }
        }

        /* Extra small devices - keep compact */
        @media (max-width: 375px) {
            .cart-item-card {
                margin: 0 0.25rem 1rem;
                border-radius: 12px;
            }
            
            .product-info {
                padding: 1rem;
            }
            
            .mobile-controls-section {
                padding: 1rem;
            }
            
            .quantity-input-modern {
                width: 65px;
                height: 38px;
            }
            
            .quantity-btn {
                width: 38px;
                height: 38px;
            }
            
            .remove-btn {
                width: 38px;
                height: 38px;
            }
        }
        
        @media (max-width: 576px) {
            .cart-header {
                padding: 1.5rem 0 !important;
            }
            .cart-header h1 {
                font-size: 1.5rem !important;
            }
            .container {
                padding: 0 1rem;
            }
            .cart-item {
                padding: 0.8rem;
                flex-direction: column;
                text-align: center;
            }
            .cart-item img {
                width: 100px !important;
                height: 100px !important;
                margin-bottom: 1rem;
            }
            .cart-item .col-lg-4 {
                margin-bottom: 1rem;
            }
            .btn-checkout {
                font-size: 0.9rem;
                padding: 0.7rem;
            }
            .btn-continue {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
            .navbar-brand span {
                font-size: 1rem !important;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    

    <?php $active = 'cart'; ?>
    <?php include 'includes/navbar_customer.php'; ?>

    <div class="cart-header">
        <!-- Floating Shapes -->
        <div style="position: absolute; top: 20%; left: 8%; width: 70px; height: 70px; background: rgba(251,191,36,0.3); border-radius: 50%; animation: float 6s ease-in-out infinite;"></div>
        <div style="position: absolute; top: 50%; right: 12%; width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 8s ease-in-out infinite 1s;"></div>
        <div style="position: absolute; bottom: 25%; left: 10%; width: 40px; height: 40px; background: rgba(251,191,36,0.4); transform: rotate(45deg); animation: float 7s ease-in-out infinite 2s;"></div>
        <div style="position: absolute; top: 30%; right: 15%; width: 80px; height: 80px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; animation: rotate 20s linear infinite;"></div>
        
        <div class="container" style="position: relative; z-index: 10;">
            <h1 style="animation: fadeInUp 1s ease-out;"><i class="bi bi-cart3 me-2"></i>Shopping Cart</h1>
            <p class="lead mb-0" style="animation: fadeInUp 1s ease-out 0.2s; animation-fill-mode: both;">Review and manage your selected items before checkout</p>
        </div>
    </div>

    <div class="container mb-5">
        
        <?php 
            // Get user discount rate for wholesalers
            $user_stmt = $pdo->prepare("SELECT usertype_id, discount_rate FROM users WHERE user_id = :user_id");
            $user_stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
            $is_bulk_buyer = ($user_info['usertype_id'] == 3);
            $discount_rate = $is_bulk_buyer ? $user_info['discount_rate'] : 0;
            
            $stmt = $pdo->prepare("
                SELECT 
                    cart.cart_id,
                    cart.variant_id,
                    products.product_name,
                    COALESCE(pv.stock, products.stock) AS stock,
                    products.restock_alert,
                    products.description,
                    COALESCE(pv.price, products.price) AS price,
                    products.image_url,
                    cart.quantity,
                    pv.size AS variant_size,
                    (COALESCE(pv.price, products.price) * cart.quantity) AS total_price
                FROM cart
                JOIN products ON cart.product_id = products.product_id
                LEFT JOIN product_variants pv ON cart.variant_id = pv.variant_id AND pv.is_active = 1
                WHERE cart.user_id = :user_id
            ");
        
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $cart_items = $stmt->fetchAll();
            $subtotal = 0;
            $total_items_count = 0;
            $has_stock_issues = false;
            $stock_issue_items = [];
            foreach ($cart_items as &$item) {
                // Apply wholesaler discount
                if($is_bulk_buyer && $discount_rate > 0){
                    $item['original_price'] = $item['price'];
                    $item['price'] = $item['price'] * (1 - ($discount_rate / 100));
                    $item['total_price'] = $item['price'] * $item['quantity'];
                }
                $subtotal += $item['total_price'];
                $total_items_count += $item['quantity'];
                
                // Check for stock issues
                if ($item['quantity'] > $item['stock'] || $item['stock'] <= 0) {
                    $has_stock_issues = true;
                    $stock_issue_items[] = [
                        'name' => $item['product_name'],
                        'cart_qty' => $item['quantity'],
                        'stock' => $item['stock'],
                        'excess' => $item['quantity'] - $item['stock'],
                        'out_of_stock' => $item['stock'] <= 0
                    ];
                }
            }

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $cart_count = $stmt->fetchColumn();
            $cart_count = $cart_count ?: 0;
        ?>

        <!-- Stock Warning Banner -->
        <?php if ($has_stock_issues): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-left: 4px solid #dc2626;">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1">
                    <h5 class="alert-heading mb-2">
                        <strong>⚠️ Stock Availability Issue</strong>
                    </h5>
                    <?php if ($is_bulk_buyer): ?>
                        <p class="mb-2">Some items in your cart have insufficient stock for bulk orders. These items cannot be checked out:</p>
                        <ul class="mb-2">
                            <?php foreach ($stock_issue_items as $issue): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($issue['name']); ?></strong>: 
                                <?php if ($issue['out_of_stock']): ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>OUT OF STOCK</span> - 
                                    This item is no longer available. Please remove it from your cart.
                                <?php else: ?>
                                    Only <span class="badge bg-danger"><?php echo $issue['stock']; ?></span> available 
                                    (minimum 20 required for bulk orders) - 
                                    Please remove this item and choose other products
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mb-0"><i class="bi bi-info-circle me-1"></i><small>This may happen if other customers purchased these items while they were in your cart. Please remove these items and browse other products for bulk orders.</small></p>
                    <?php else: ?>
                        <p class="mb-2">Some items in your cart exceed available stock. These items cannot be checked out until quantities are adjusted:</p>
                        <ul class="mb-2">
                            <?php foreach ($stock_issue_items as $issue): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($issue['name']); ?></strong>: 
                                <?php if ($issue['out_of_stock']): ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i>OUT OF STOCK</span> - 
                                    This item is no longer available. Please remove it from your cart.
                                <?php else: ?>
                                    You have <span class="badge bg-danger"><?php echo $issue['cart_qty']; ?></span> in cart, 
                                    but only <span class="badge bg-success"><?php echo $issue['stock']; ?></span> available 
                                    (Reduce by <?php echo $issue['excess']; ?>)
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mb-0"><i class="bi bi-info-circle me-1"></i><small>This may happen if other customers purchased these items while they were in your cart. Please adjust quantities before proceeding to checkout.</small></p>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- Cart Statistics Bar -->
        <?php if ($cart_count > 0): ?>
        <div class="cart-stats">
            <div class="stat-item">
                <i class="bi bi-box-seam stat-icon-minimal" style="color: #2563eb;"></i>
                <span class="stat-value"><?php echo count($cart_items); ?></span>
                <span class="stat-label">Products</span>
            </div>
            <div class="stat-item">
                <i class="bi bi-stack stat-icon-minimal" style="color: #10b981;"></i>
                <span class="stat-value"><?php echo $total_items_count; ?></span>
                <span class="stat-label">Total Items</span>
            </div>
            <div class="stat-item">
                <i class="bi bi-cash-coin stat-icon-minimal" style="color: #f59e0b;"></i>
                <span class="stat-value">₱<?php echo number_format($subtotal, 2); ?></span>
                <span class="stat-label">Subtotal</span>
            </div>
        </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 cart-header-controls">
                            <h5 class="card-title mb-0 cart-items-title">
                                <i class="bi bi-bag-check me-2 text-primary"></i><span class="cart-title-text">Cart Items (<?php echo $cart_count ?>)</span>
                            </h5>
                            <?php if ($cart_count > 0): ?>
                            <div class="d-flex gap-2 cart-action-buttons">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmClearCart()" title="Clear Cart">
                                    <i class="bi bi-trash"></i> <span class="btn-text">Clear Cart</span>
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="removeSelected()" title="Remove Selected">
                                    <i class="bi bi-trash"></i> <span class="btn-text">Remove Selected</span>
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" onclick="checkoutSelected()" title="Checkout Selected">
                                    <i class="bi bi-credit-card"></i> <span class="btn-text">Checkout</span>
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Hidden form to submit via JS -->
                            <form id="clearCartForm" method="POST" style="display: none;">
                                <input type="hidden" name="action" value="clear">
                            </form>
                            <form id="removeSelectedForm" method="POST" style="display: none;">
                                <input type="hidden" name="action" value="remove_selected">
                            </form>
                            <form id="checkoutSelectedForm" method="POST" action="checkout.php" style="display: none;">
                                <input type="hidden" name="selected_checkout" value="1">
                            </form>
                        </div>

                        <?php if ($cart_count > 0): ?>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll">
                                <label class="form-check-label" for="selectAll">
                                    Select All Items
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <script>
                            function confirmClearCart() {
                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: "This will remove all items from your cart.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, clear it!'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        document.getElementById('clearCartForm').submit();
                                    }
                                });
                            }
                        </script>
                        
                        <?php if (empty($cart_items)): ?>
                            <div class="empty-cart-container">
                                <div class="empty-cart-icon">
                                    <i class="bi bi-cart-x"></i>
                                </div>
                                <h2 class="empty-cart-title">Your Cart is Empty</h2>
                                <p class="empty-cart-text">Looks like you haven't added any items to your cart yet.<br>Start shopping to fill it up!</p>
                                <a href="shop.php" class="btn btn-primary btn-lg btn-action">
                                    <i class="bi bi-shop me-2"></i>Browse Products
                                </a>
                            </div>
                        <?php else: ?>
                        <?php foreach ($cart_items as $row): ?>
                            <div class="cart-item-card <?php echo ($row['stock'] <= 0) ? 'out-of-stock-item' : ''; ?>" data-cart-id="<?php echo $row['cart_id']; ?>" data-price="<?php echo $row['price']; ?>" data-quantity="<?php echo $row['quantity']; ?>">
                                <!-- Delete Button - Top Right Corner -->
                                <button type="button" class="remove-btn-top-corner" 
                                    onclick="confirmRemoveItem('<?php echo $row['cart_id']; ?>')"
                                    title="Remove item">
                                    <i class="bi bi-trash"></i>
                                </button>
                                
                                <div class="row g-0">
                                    <!-- Product Information -->
                                    <div class="col-md-12 col-lg-12">
                                        <div class="product-info">
                                            <div class="row align-items-center">
                                <!-- Product Details with Image and Checkbox -->
                                <div class="col-lg-4">
                                    <div class="d-flex align-items-center gap-2">
                                        <!-- Checkbox - Disabled if out of stock -->
                                        <input class="form-check-input item-checkbox-large" type="checkbox" name="selected_items[]" value="<?php echo $row['cart_id']; ?>" <?php echo ($row['stock'] <= 0) ? 'disabled title="Item out of stock"' : ''; ?>>
                                        
                                        <!-- Product Image Thumbnail -->
                                        <div class="product-image-container">
                                            <img src="uploads/products/<?php echo $row['image_url']; ?>"
                                                 alt="<?php echo htmlspecialchars($row['product_name']); ?>"
                                                 class="cart-product-image">
                                        </div>
                                        
                                        <!-- Product Name & Info -->
                                        <div class="flex-grow-1">
                                            <h3 class="product-title">
                                                <?php echo htmlspecialchars($row['product_name']); ?>
                                                <?php if (!empty($row['variant_size'])): ?>
                                                    <span class="badge bg-primary ms-2" style="font-size: 0.75rem; font-weight: normal;">
                                                        <i class="bi bi-rulers me-1"></i><?php echo htmlspecialchars($row['variant_size']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </h3>
                                            
                                            <!-- Product Attributes -->
                                            <div class="product-attributes">
                                                <span class="attribute-tag">
                                                    <i class="bi bi-tag-fill me-1"></i>Bedsheet
                                                </span>
                                                <span class="attribute-tag">
                                                    <i class="bi bi-palette-fill me-1"></i>Premium Quality
                                                </span>
                                            </div>
                                            
                                            <!-- Stock Information with Warning -->
                                            <div class="stock-info">
                                                <?php if ($row['stock'] <= 0): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="bi bi-x-circle-fill me-1"></i>
                                                        OUT OF STOCK
                                                    </span>
                                                <?php else: ?>
                                                    <span style="white-space: nowrap;">
                                                        <i class="bi bi-box-seam me-1"></i>
                                                        <?php echo $row['stock']; ?> in stock
                                                    </span>
                                                    <?php if ($is_bulk_buyer && $row['stock'] < 20): ?>
                                                        <span class="badge bg-warning ms-2" title="Bulk buyers require minimum 20 items">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            Below Minimum
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($row['quantity'] > $row['stock']): ?>
                                                        <span class="badge bg-danger ms-2">
                                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                                            Exceeds by <?php echo ($row['quantity'] - $row['stock']); ?>
                                                        </span>
                                                    <?php elseif ($row['stock'] <= $row['restock_alert']): ?>
                                                        <span class="badge bg-warning text-dark ms-2">
                                                            <i class="bi bi-exclamation-circle me-1"></i>
                                                            LOW STOCK
                                                        </span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price -->
                                <div class="col-lg-2 d-flex align-items-center justify-content-center">
                                    <div class="price-display">
                                        <span class="price-label">Unit Price</span>
                                        <?php if($is_bulk_buyer && isset($row['original_price'])): ?>
                                            <small class="text-muted text-decoration-line-through d-block">₱<?php echo number_format($row['original_price'], 2); ?></small>
                                            <span class="price-amount text-success">₱<?php echo number_format($row['price'], 2); ?></span>
                                            <small class="badge bg-success">Bulk Discount</small>
                                        <?php else: ?>
                                            <span class="price-amount">₱<?php echo number_format($row['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="col-lg-2 d-flex align-items-center justify-content-center">
                                    <?php if ($row['stock'] <= 0): ?>
                                        <!-- Out of Stock - Show disabled controls -->
                                        <div class="quantity-controls-disabled text-center">
                                            <div class="text-danger fw-bold mb-2">
                                                <i class="bi bi-x-octagon"></i> Unavailable
                                            </div>
                                            <small class="text-muted">Remove from cart</small>
                                        </div>
                                    <?php else: ?>
                                        <form method="POST" id="form-<?php echo $row['cart_id']; ?>" onsubmit="return handleFormSubmit(event, '<?php echo $row['cart_id']; ?>', <?php echo $row['stock']; ?>);">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="cart_item_id" value="<?php echo $row['cart_id']; ?>">
                                            <input type="hidden" name="quantity_change" id="quantity_change-<?php echo $row['cart_id']; ?>" value="0">
                                            
                                            <div class="quantity-controls">
                                                <!-- Minus Button -->
                                                <button type="button" class="quantity-btn" 
                                                    id="minus-btn-<?php echo $row['cart_id']; ?>"
                                                    onclick="handleMinus('<?php echo $row['cart_id']; ?>', <?php echo $row['quantity']; ?>)"
                                                    <?php if ($is_bulk_buyer && $row['quantity'] <= 20): ?>disabled<?php endif; ?>>
                                                    <i class="bi bi-dash"></i>
                                                </button>

                                                <!-- Quantity Input -->
                                                <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" 
                                                    min="<?php echo $is_bulk_buyer ? '20' : '1'; ?>" max="<?php echo $row['stock']; ?>"
                                                    data-min-quantity="<?php echo $is_bulk_buyer ? '20' : '1'; ?>"
                                                    data-is-wholesale="<?php echo $is_bulk_buyer ? 'true' : 'false'; ?>"
                                                    data-cart-id="<?php echo $row['cart_id']; ?>"
                                                    class="quantity-input-modern"
                                                    oninput="validateQuantityInput(this, <?php echo $row['stock']; ?>)"
                                                    onblur="submitQuantityChange('<?php echo $row['cart_id']; ?>', <?php echo $row['stock']; ?>)"
                                                    onkeydown="if(event.key === 'Enter') { event.preventDefault(); this.blur(); }">

                                                <!-- Plus Button -->
                                                <button type="button" class="quantity-btn" onclick="handlePlus('<?php echo $row['cart_id']; ?>', <?php echo $row['quantity']; ?>, <?php echo $row['stock']; ?>)">
                                                    <i class="bi bi-plus"></i>
                                                </button>
                                            </div>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <!-- Subtotal - Far Right -->
                                <div class="col-lg-3 d-flex align-items-center justify-content-end">
                                    <div class="subtotal-display">
                                        <span class="subtotal-label">Subtotal</span>
                                        <span class="subtotal-amount">₱<?php echo number_format(($row['quantity'] * $row['price']), 2); ?></span>
                                    </div>
                                </div>
                                            </div>

                                            <!-- Mobile-specific controls section (hidden on desktop) -->
                                            <div class="mobile-controls-section d-md-none">
                                                <!-- Mobile Total Display with Delete Button beside it -->
                                                <div class="mobile-total-row d-flex justify-content-between align-items-center">
                                                    <div class="subtotal-display">
                                                        <span class="subtotal-label">Item Total</span>
                                                        <span class="subtotal-amount">₱<?php echo number_format(($row['quantity'] * $row['price']), 2); ?></span>
                                                    </div>
                                                    
                                                    <!-- Remove Button beside Total (same design as desktop) -->
                                                    <button type="button" class="remove-btn" 
                                                        onclick="confirmRemoveItem('<?php echo $row['cart_id']; ?>')"
                                                        title="Remove item">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                    <script>
                                        // This script block is now empty - functions moved outside the loop
                                    </script>
                        <?php endforeach; ?>
                        
                        <!-- Global validation script for all cart items -->
                        <script>
                            // Initialize validation for all quantity inputs
                            document.addEventListener('DOMContentLoaded', function() {
                                document.querySelectorAll('.quantity-input-modern').forEach(input => {
                                    const isWholesale = input.dataset.isWholesale === 'true';
                                    const cartId = input.dataset.cartId;
                                    const value = parseInt(input.value);
                                    
                                    // For wholesalers, ensure minimum quantity on page load
                                    if (isWholesale && value < 20) {
                                        input.value = 20;
                                    }
                                    
                                    // Set initial minus button state
                                    if (isWholesale && cartId) {
                                        const minusBtn = document.getElementById('minus-btn-' + cartId);
                                        if (minusBtn) {
                                            minusBtn.disabled = value <= 20;
                                        }
                                    }
                                });
                            });
                            
                            function validateQuantityInput(input, maxStock) {
                                const value = parseInt(input.value);
                                const minQuantity = parseInt(input.dataset.minQuantity) || 1;
                                const isWholesale = input.dataset.isWholesale === 'true';
                                const cartId = input.dataset.cartId;
                                
                                // Check minimum quantity for wholesalers
                                if (isWholesale && value < 20) {
                                    // Immediately correct the value
                                    input.value = 20;
                                    
                                    // Show popup notification
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'warning',
                                        title: 'Minimum quantity for wholesale is 20',
                                        text: 'Quantity automatically adjusted to minimum'
                                    });
                                    
                                    // Update the minus button state
                                    const minusBtn = document.getElementById('minus-btn-' + cartId);
                                    if (minusBtn) {
                                        minusBtn.disabled = true;
                                    }
                                    
                                    return;
                                }
                                
                                // Update minus button state for wholesalers
                                if (isWholesale && cartId) {
                                    const minusBtn = document.getElementById('minus-btn-' + cartId);
                                    if (minusBtn) {
                                        minusBtn.disabled = value <= 20;
                                    }
                                }
                                
                                if (value > maxStock) {
                                    input.value = maxStock;
                                    // Show a subtle warning without interrupting the user
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'warning',
                                        title: `Maximum ${maxStock} items available`
                                    });
                                }
                            }
                            
                            // Track original quantity values to detect changes
                            const originalQuantities = {};
                            document.addEventListener('DOMContentLoaded', function() {
                                document.querySelectorAll('.quantity-input-modern').forEach(input => {
                                    const form = input.closest('form');
                                    const cartId = form.id.replace('form-', '');
                                    originalQuantities[cartId] = parseInt(input.value);
                                });
                            });
                            
                            function submitQuantityChange(cartId, maxStock) {
                                const form = document.getElementById('form-' + cartId);
                                const input = form.querySelector('input[name="quantity"]');
                                const newValue = parseInt(input.value) || 1;
                                const originalValue = originalQuantities[cartId];
                                const minQuantity = parseInt(input.dataset.minQuantity) || 1;
                                const isWholesale = input.dataset.isWholesale === 'true';
                                
                                // Check minimum quantity for wholesalers
                                if (isWholesale && newValue < 20) {
                                    input.value = 20;
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'warning',
                                        title: 'Minimum quantity for wholesale is 20'
                                    });
                                    return;
                                }
                                
                                // Only submit if value actually changed
                                if (newValue !== originalValue) {
                                    if (newValue > maxStock) {
                                        input.value = maxStock;
                                    }
                                    
                                    if (newValue >= minQuantity) {
                                        // Save cart item ID to highlight after reload
                                        sessionStorage.setItem('changedCartItem', cartId);
                                        // Save current scroll position
                                        sessionStorage.setItem('cartScrollY', window.scrollY);
                                        form.submit();
                                    } else {
                                        input.value = originalValue; // Reset to original if invalid
                                    }
                                }
                            }
                            
                            function handleFormSubmit(event, cartId, maxStock) {
                                event.preventDefault();
                                const form = document.getElementById('form-' + cartId);
                                const input = form.querySelector('input[name="quantity"]');
                                const value = parseInt(input.value);
                                const minQuantity = parseInt(input.dataset.minQuantity) || 1;
                                const isWholesale = input.dataset.isWholesale === 'true';
                                
                                // Check minimum quantity for wholesalers
                                if (isWholesale && value < 20) {
                                    input.value = 20;
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'warning',
                                        title: 'Minimum quantity for wholesale is 20'
                                    });
                                    return;
                                }
                                
                                if (value > maxStock) {
                                    input.value = maxStock;
                                }
                                
                                if (value >= minQuantity) {
                                    // Save cart item ID to highlight after reload
                                    sessionStorage.setItem('changedCartItem', cartId);
                                    // Save current scroll position
                                    sessionStorage.setItem('cartScrollY', window.scrollY);
                                    form.submit();
                                }
                            }
                            
                            function handlePlus(cartId, currentQuantity, maxStock) {
                                const form = document.getElementById('form-' + cartId);
                                const quantityInput = form.querySelector('input[name="quantity"]');
                                const newQuantity = currentQuantity + 1;
                                
                                if (newQuantity > maxStock) {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'warning',
                                        title: `Maximum ${maxStock} items available`
                                    });
                                    return;
                                }
                                
                                // Set the quantity_change value to 1
                                const quantityChangeInput = document.getElementById('quantity_change-' + cartId);
                                quantityChangeInput.value = 1;
                                // Save cart item ID to highlight after reload
                                sessionStorage.setItem('changedCartItem', cartId);
                                // Save current scroll position
                                sessionStorage.setItem('cartScrollY', window.scrollY);
                                form.submit();
                            }

                            function handleMinus(cartId, quantity) {
                                const form = document.getElementById('form-' + cartId);
                                const input = form.querySelector('input[name="quantity"]');
                                const minQuantity = parseInt(input.dataset.minQuantity) || 1;
                                const isWholesale = input.dataset.isWholesale === 'true';
                                
                                // For wholesalers, don't allow going below 20
                                if (isWholesale && quantity <= 20) {
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });
                                    Toast.fire({
                                        icon: 'warning',
                                        title: 'Minimum quantity for wholesale is 20'
                                    });
                                    return;
                                }
                                
                                if (quantity <= 1) {
                                    Swal.fire({
                                        title: 'Remove from cart?',
                                        text: 'Are you sure you want to remove this item from your cart?',
                                        icon: 'warning',
                                        showCancelButton: true,
                                        confirmButtonText: 'Yes, remove it',
                                        cancelButtonText: 'Cancel'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            // Submit to delete logic
                                            const actionInput = form.querySelector('input[name="action"]');
                                            actionInput.value = 'delete';
                                            form.submit();
                                        }
                                    });
                                } else {
                                    // Set the quantity_change value to -1
                                    const quantityChangeInput = document.getElementById('quantity_change-' + cartId);
                                    quantityChangeInput.value = -1;
                                    // Save cart item ID to highlight after reload
                                    sessionStorage.setItem('changedCartItem', cartId);
                                    // Save current scroll position
                                    sessionStorage.setItem('cartScrollY', window.scrollY);
                                    form.submit();
                                }
                            }
                        </script>
                        
                        <script>
                            function confirmRemoveItem(cart_id) {
                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: "This will remove the item from your cart.",
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, remove it!',
                                    // Enhanced mobile styling
                                    customClass: {
                                        popup: 'mobile-swal-popup',
                                        confirmButton: 'mobile-swal-confirm',
                                        cancelButton: 'mobile-swal-cancel'
                                    },
                                    // Better mobile button sizes
                                    buttonsStyling: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Add loading state for better UX
                                        Swal.fire({
                                            title: 'Removing item...',
                                            allowOutsideClick: false,
                                            showConfirmButton: false,
                                            willOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });
                                        
                                        // Create a hidden form to submit
                                        var form = document.createElement('form');
                                        form.method = 'POST';
                                        form.style.display = 'none';
                                        
                                        // Add hidden inputs to the form
                                        var actionInput = document.createElement('input');
                                        actionInput.type = 'hidden';
                                        actionInput.name = 'action';
                                        actionInput.value = 'remove';
                                        form.appendChild(actionInput);

                                        var cartItemIdInput = document.createElement('input');
                                        cartItemIdInput.type = 'hidden';
                                        cartItemIdInput.name = 'cart_item_id';
                                        cartItemIdInput.value = cart_id;
                                        form.appendChild(cartItemIdInput);

                                        // Append the form and submit it
                                        document.body.appendChild(form);
                                        form.submit();
                                    }
                                });
                            }

                            // Enhanced mobile quantity validation with haptic feedback simulation
                            function validateQuantityInput(input, maxStock) {
                                const value = parseInt(input.value);
                                if (value > maxStock) {
                                    input.value = maxStock;
                                    
                                    // Enhanced mobile toast with better styling
                                    const Toast = Swal.mixin({
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000,
                                        timerProgressBar: true,
                                        customClass: {
                                            popup: 'mobile-toast-popup'
                                        },
                                        didOpen: (toast) => {
                                            // Simulate haptic feedback on mobile
                                            if (navigator.vibrate) {
                                                navigator.vibrate(50);
                                            }
                                        }
                                    });
                                    
                                    Toast.fire({
                                        icon: 'warning',
                                        title: `Maximum ${maxStock} items available`,
                                        background: '#fef3c7',
                                        color: '#92400e'
                                    });
                                }
                            }

                            // Enhanced mobile form submission with loading states
                            function validateAndSubmit(input, maxStock, cartId) {
                                const value = parseInt(input.value);
                                if (value > maxStock) {
                                    input.value = maxStock;
                                    
                                    // Show loading state for mobile
                                    showMobileLoadingState(cartId);
                                    input.form.submit();
                                } else if (value >= 1) {
                                    showMobileLoadingState(cartId);
                                    input.form.submit();
                                }
                            }

                            function handleMinus(cartId, quantity) {
                                const form = document.getElementById('form-' + cartId) || 
                                           document.getElementById('mobile-form-' + cartId);
                                
                                if (quantity <= 1) {
                                    confirmRemoveItem(cartId);
                                } else {
                                    showMobileLoadingState(cartId);
                                    
                                    // Submit minus
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'quantity_change';
                                    input.value = -1;
                                    form.appendChild(input);
                                    form.submit();
                                }
                            }

                            // Mobile loading state function
                            function showMobileLoadingState(cartId) {
                                const cartItem = document.querySelector(`#form-${cartId}`).closest('.cart-item-card') ||
                                               document.querySelector(`#mobile-form-${cartId}`).closest('.cart-item-card');
                                
                                if (cartItem) {
                                    cartItem.style.opacity = '0.6';
                                    cartItem.style.pointerEvents = 'none';
                                    
                                    // Add loading spinner for mobile
                                    const loadingDiv = document.createElement('div');
                                    loadingDiv.className = 'mobile-loading-overlay';
                                    loadingDiv.innerHTML = `
                                        <div class="mobile-spinner">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </div>
                                    `;
                                    cartItem.style.position = 'relative';
                                    cartItem.appendChild(loadingDiv);
                                }
                            }
                        </script>

                        <!-- Mobile-specific CSS for enhanced interactions -->
                        <style>
                            .mobile-swal-popup {
                                font-size: 1.1rem !important;
                                border-radius: 16px !important;
                            }
                            
                            .mobile-swal-confirm,
                            .mobile-swal-cancel {
                                min-height: 50px !important;
                                font-size: 1.1rem !important;
                                border-radius: 12px !important;
                                padding: 12px 24px !important;
                                margin: 0 8px !important;
                            }
                            
                            .mobile-swal-confirm {
                                background-color: #ef4444 !important;
                                color: white !important;
                            }
                            
                            .mobile-swal-cancel {
                                background-color: #6b7280 !important;
                                color: white !important;
                            }
                            
                            .mobile-toast-popup {
                                font-size: 1rem !important;
                                border-radius: 12px !important;
                                padding: 16px !important;
                            }
                            
                            .mobile-loading-overlay {
                                position: absolute;
                                top: 0;
                                left: 0;
                                right: 0;
                                bottom: 0;
                                background: rgba(255, 255, 255, 0.8);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                border-radius: 16px;
                                z-index: 10;
                            }
                            
                            .mobile-spinner i {
                                font-size: 2rem;
                                color: #2563eb;
                                animation: spin 1s linear infinite;
                            }
                            
                            @keyframes spin {
                                from { transform: rotate(0deg); }
                                to { transform: rotate(360deg); }
                            }

                            /* Enhanced touch feedback for mobile */
                            @media (max-width: 768px) {
                                .quantity-btn:active,
                                .remove-btn:active {
                                    transform: scale(0.95);
                                    transition: transform 0.1s ease;
                                }
                                
                                .cart-item-card:active {
                                    transform: scale(0.99);
                                    transition: transform 0.1s ease;
                                }
                            }
                        </style>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <?php if (!empty($cart_items)): ?>
                <div class="card shadow-sm summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Selected Subtotal</span>
                            <span id="selected-subtotal">₱0.00</span>
                        </div>
                        <!-- Shipping Fee removed -->
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span>Selected Total</span>
                            <strong class="fs-4" id="order-total">₱0.00</strong>
                        </div>

                        <div class="d-grid gap-2">
                            <button onclick="validateCartBeforeCheckout()" class="btn btn-primary btn-lg">
                                <i class="bi bi-credit-card me-2"></i>Proceed to Checkout
                            </button>
                            <a href="shop.php" class="btn btn-continue">
                                <i class="bi bi-arrow-left me-2"></i>Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    

    <style>
        /* Highlight animation for changed cart item */
        @keyframes highlightPulse {
            0% {
                background-color: #fff3cd;
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                background-color: #fff9e6;
                transform: scale(1.01);
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
            100% {
                background-color: white;
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            }
        }
        
        .cart-item-highlight {
            animation: highlightPulse 1.5s ease-in-out;
            border: 2px solid #ffc107 !important;
        }
    </style>

    <script>
        // IMMEDIATELY restore scroll position to prevent any scrolling
        (function() {
            // Disable browser scroll restoration
            if ('scrollRestoration' in history) {
                history.scrollRestoration = 'manual';
            }
            
            const savedScrollY = sessionStorage.getItem('cartScrollY');
            if (savedScrollY !== null) {
                // Restore scroll position IMMEDIATELY
                window.scrollTo(0, parseInt(savedScrollY));
                
                // Also restore on DOMContentLoaded in case browser tries to scroll again
                document.addEventListener('DOMContentLoaded', function() {
                    window.scrollTo(0, parseInt(savedScrollY));
                });
                
                // And one more time after full load
                window.addEventListener('load', function() {
                    window.scrollTo(0, parseInt(savedScrollY));
                    sessionStorage.removeItem('cartScrollY');
                });
            }
        })();
        
        // Highlight the changed cart item after page reload
        window.addEventListener('DOMContentLoaded', function() {
            const changedCartId = sessionStorage.getItem('changedCartItem');
            if (changedCartId) {
                // Find the cart item card
                const cartCard = document.querySelector(`[data-cart-id="${changedCartId}"]`);
                if (cartCard) {
                    // Add highlight class
                    cartCard.classList.add('cart-item-highlight');
                    
                    // Remove highlight after animation completes
                    setTimeout(function() {
                        cartCard.classList.remove('cart-item-highlight');
                    }, 1500);
                }
                
                // Clear the stored cart ID
                sessionStorage.removeItem('changedCartItem');
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>

        function validateCartBeforeCheckout() {
            <?php if (empty($cart_items)): ?>
            Swal.fire({
                icon: 'warning',
                title: 'Empty Cart',
                text: 'Your cart is empty. Add some items before checkout.',
                confirmButtonColor: '#2563eb'
            });
            return;
            <?php else: ?>
            
            // Check if user is a bulk buyer
            const isBulkBuyer = <?php echo $is_bulk_buyer ? 'true' : 'false'; ?>;
            
            // JavaScript array of ALL cart items for validation
            const cartValidationData = <?php echo json_encode(array_map(function($item) {
                return [
                    'cartId' => (string)$item['cart_id'],
                    'productName' => $item['product_name'],
                    'quantity' => (int)$item['quantity'],
                    'stock' => (int)$item['stock']
                ];
            }, $cart_items)); ?>;

            // Get selected cart IDs
            const selectedCheckboxes = document.querySelectorAll('.item-checkbox-large:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                Swal.fire('No items selected', 'Please select items to checkout.', 'warning');
                return;
            }

            // Filter to selected items only
            const selectedItems = cartValidationData.filter(item => selectedIds.includes(item.cartId));

            let hasStockIssues = false;
            let stockIssueMessages = [];
            let hasBulkStockIssues = false;
            let bulkStockIssueMessages = [];

            // Validate only selected items
            selectedItems.forEach(item => {
                if (item.quantity > item.stock) {
                    hasStockIssues = true;
                    stockIssueMessages.push(`${item.productName}: Requested ${item.quantity}, but only ${item.stock} available`);
                }
                
                // Additional validation for bulk buyers
                if (isBulkBuyer && item.stock < 20) {
                    hasBulkStockIssues = true;
                    bulkStockIssueMessages.push(`${item.productName}: Only ${item.stock} in stock (minimum 20 required for bulk orders)`);
                }
            });

            // Check bulk buyer stock issues first (priority for bulk buyers)
            if (isBulkBuyer && hasBulkStockIssues) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cannot Checkout - Stock Below Minimum',
                    html: '<strong>The stock is less than the required minimum for bulk orders:</strong><br><br>' +
                          bulkStockIssueMessages.join('<br>') +
                          '<br><br><em>Please remove these items from your cart and browse other products.</em>',
                    confirmButtonText: 'Browse Other Products',
                    showCancelButton: true,
                    cancelButtonText: 'Remove Items',
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Browse other products
                        window.location.href = 'shop.php';
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // User chose to remove items - show confirmation to remove problematic items
                        Swal.fire({
                            icon: 'warning',
                            title: 'Remove Problematic Items?',
                            html: '<strong>Remove the following items from your cart?</strong><br><br>' +
                                  bulkStockIssueMessages.join('<br>') +
                                  '<br><br><em>These items don\'t meet the minimum 20 quantity requirement for bulk orders.</em>',
                            confirmButtonText: 'Yes, Remove Items',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d'
                        }).then((removeResult) => {
                            if (removeResult.isConfirmed) {
                                // Automatically select and remove the problematic items
                                const problematicCartIds = selectedItems
                                    .filter(item => item.stock < 20)
                                    .map(item => item.cartId);
                                
                                // Check the checkboxes for problematic items
                                problematicCartIds.forEach(cartId => {
                                    const checkbox = document.querySelector(`input[value="${cartId}"]`);
                                    if (checkbox) checkbox.checked = true;
                                });
                                
                                // Submit remove selected form
                                const form = document.getElementById('removeSelectedForm');
                                // Clear any previous inputs
                                const existingInputs = form.querySelectorAll('input[name="selected_cart_ids[]"]');
                                existingInputs.forEach(el => el.remove());
                                
                                // Add problematic items to form
                                problematicCartIds.forEach(id => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'selected_cart_ids[]';
                                    input.value = id;
                                    form.appendChild(input);
                                });
                                
                                form.submit();
                            }
                        });
                    }
                });
                return;
            }

            if (hasStockIssues) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Validation Failed',
                    html: '<strong>The following items exceed available stock:</strong><br><br>' +
                          stockIssueMessages.join('<br>') +
                          '<br><br><em>Please update quantities before checkout.</em>',
                    confirmButtonText: 'Update Cart',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            // Submit only selected items to checkout
            const form = document.getElementById('checkoutSelectedForm');
            // Clear any previous inputs added (avoid duplicates on repeated attempts)
            const existingInputs = form.querySelectorAll('input[name="selected_cart_ids[]"]');
            existingInputs.forEach(el => el.remove());
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_cart_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            form.submit();
            <?php endif; ?>
        }
    </script>

    <?php if(isset($success_message)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?php echo $success_message; ?>'
            });
        </script>
    <?php elseif(isset($error_message)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error_message; ?>'
            });
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select all functionality (skip disabled/out-of-stock items)
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.item-checkbox-large:not(:disabled)');
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateTotal();
            });

            // Individual checkbox change
            const checkboxes = document.querySelectorAll('.item-checkbox-large');
            checkboxes.forEach(cb => cb.addEventListener('change', function() {
                updateTotal();
                updateSelectAllState();
            }));

            // Initial total update
            updateTotal();
        });

        function updateSelectAllState() {
            const allCheckboxes = document.querySelectorAll('.item-checkbox-large:not(:disabled)');
            const checkedCheckboxes = document.querySelectorAll('.item-checkbox-large:checked:not(:disabled)');
            const selectAll = document.getElementById('selectAll');
            selectAll.checked = allCheckboxes.length === checkedCheckboxes.length && allCheckboxes.length > 0;
            selectAll.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
        }

        function updateTotal() {
            const selectedItems = document.querySelectorAll('.item-checkbox-large:checked:not(:disabled)');
            let total = 0;
            selectedItems.forEach(cb => {
                const card = cb.closest('.cart-item-card');
                // Skip out-of-stock items
                if (card.classList.contains('out-of-stock-item')) {
                    return;
                }
                const price = parseFloat(card.dataset.price);
                const quantity = parseInt(card.dataset.quantity);
                total += price * quantity;
            });
            document.getElementById('selected-subtotal').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('order-total').textContent = '₱' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function removeSelected() {
            const selected = document.querySelectorAll('.item-checkbox-large:checked');
            if (selected.length === 0) {
                Swal.fire('No items selected', 'Please select items to remove.', 'warning');
                return;
            }
            Swal.fire({
                title: 'Remove selected items?',
                text: `Remove ${selected.length} item(s) from cart?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove'
            }).then(result => {
                if (result.isConfirmed) {
                    const form = document.getElementById('removeSelectedForm');
                    selected.forEach(cb => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'selected_cart_ids[]';
                        input.value = cb.value;
                        form.appendChild(input);
                    });
                    form.submit();
                }
            });
        }

        function checkoutSelected() {
            const selected = document.querySelectorAll('.item-checkbox-large:checked');
            if (selected.length === 0) {
                Swal.fire('No items selected', 'Please select items to checkout.', 'warning');
                return;
            }

            // Check if user is a bulk buyer
            const isBulkBuyer = <?php echo $is_bulk_buyer ? 'true' : 'false'; ?>;
            
            // JavaScript array of ALL cart items for validation
            const cartValidationData = <?php echo json_encode(array_map(function($item) {
                return [
                    'cartId' => (string)$item['cart_id'],
                    'productName' => $item['product_name'],
                    'quantity' => (int)$item['quantity'],
                    'stock' => (int)$item['stock']
                ];
            }, $cart_items)); ?>;

            // Get selected cart IDs
            const selectedIds = Array.from(selected).map(cb => cb.value);

            // Filter to selected items only
            const selectedItems = cartValidationData.filter(item => selectedIds.includes(item.cartId));

            let hasStockIssues = false;
            let stockIssueMessages = [];
            let hasBulkStockIssues = false;
            let bulkStockIssueMessages = [];

            // Validate only selected items
            selectedItems.forEach(item => {
                if (item.quantity > item.stock) {
                    hasStockIssues = true;
                    stockIssueMessages.push(`${item.productName}: Requested ${item.quantity}, but only ${item.stock} available`);
                }
                
                // Additional validation for bulk buyers
                if (isBulkBuyer && item.stock < 20) {
                    hasBulkStockIssues = true;
                    bulkStockIssueMessages.push(`${item.productName}: Only ${item.stock} in stock (minimum 20 required for bulk orders)`);
                }
            });

            // Check bulk buyer stock issues first (priority for bulk buyers)
            if (isBulkBuyer && hasBulkStockIssues) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cannot Checkout - Stock Below Minimum',
                    html: '<strong>The stock is less than the required minimum for bulk orders:</strong><br><br>' +
                          bulkStockIssueMessages.join('<br>') +
                          '<br><br><em>Please remove these items from your cart and browse other products.</em>',
                    confirmButtonText: 'Browse Other Products',
                    showCancelButton: true,
                    cancelButtonText: 'Remove Items',
                    confirmButtonColor: '#2563eb',
                    cancelButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Browse other products
                        window.location.href = 'shop.php';
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // User chose to remove items - show confirmation to remove problematic items
                        Swal.fire({
                            icon: 'warning',
                            title: 'Remove Problematic Items?',
                            html: '<strong>Remove the following items from your cart?</strong><br><br>' +
                                  bulkStockIssueMessages.join('<br>') +
                                  '<br><br><em>These items don\'t meet the minimum 20 quantity requirement for bulk orders.</em>',
                            confirmButtonText: 'Yes, Remove Items',
                            cancelButtonText: 'Cancel',
                            confirmButtonColor: '#dc3545',
                            cancelButtonColor: '#6c757d'
                        }).then((removeResult) => {
                            if (removeResult.isConfirmed) {
                                // Automatically select and remove the problematic items
                                const problematicCartIds = selectedItems
                                    .filter(item => item.stock < 20)
                                    .map(item => item.cartId);
                                
                                // Check the checkboxes for problematic items
                                problematicCartIds.forEach(cartId => {
                                    const checkbox = document.querySelector(`input[value="${cartId}"]`);
                                    if (checkbox) checkbox.checked = true;
                                });
                                
                                // Submit remove selected form
                                const form = document.getElementById('removeSelectedForm');
                                // Clear any previous inputs
                                const existingInputs = form.querySelectorAll('input[name="selected_cart_ids[]"]');
                                existingInputs.forEach(el => el.remove());
                                
                                // Add problematic items to form
                                problematicCartIds.forEach(id => {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = 'selected_cart_ids[]';
                                    input.value = id;
                                    form.appendChild(input);
                                });
                                
                                form.submit();
                            }
                        });
                    }
                });
                return;
            }

            if (hasStockIssues) {
                Swal.fire({
                    icon: 'error',
                    title: 'Stock Validation Failed',
                    html: '<strong>The following items exceed available stock:</strong><br><br>' +
                          stockIssueMessages.join('<br>') +
                          '<br><br><em>Please update quantities before checkout.</em>',
                    confirmButtonText: 'Update Cart',
                    confirmButtonColor: '#2563eb'
                });
                return;
            }

            // If all validations pass, proceed to checkout
            const form = document.getElementById('checkoutSelectedForm');
            // Clear any previous inputs added (avoid duplicates on repeated attempts)
            const existingInputs = form.querySelectorAll('input[name="selected_cart_ids[]"]');
            existingInputs.forEach(el => el.remove());
            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_cart_ids[]';
                input.value = id;
                form.appendChild(input);
            });
            form.submit();
        }
    </script>
</body>

</html>
