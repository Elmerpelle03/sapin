<?php 
    require ('config/db.php');
    session_start();
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
    $order_id = $_GET['order_id'] ?? null;
    
    if (!$order_id) {
        $_SESSION['error_message'] = "";
        header('Location: orders.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            CONCAT(
                o.house, ', ',
                b.barangay_name, ', ',
                m.municipality_name, ', ',
                p.province_name, ', ',
                r.region_name
            ) AS shipping_address
        FROM orders o
        LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
        LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
        LEFT JOIN table_province p ON o.province_id = p.province_id
        LEFT JOIN table_region r ON o.region_id = r.region_id
        WHERE o.order_id = :order_id
    ");

    $stmt->execute([':order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);


    if (!$order) {
        $_SESSION['error_message'] = "";
        header('Location: orders.php');
        exit();
    }

    // Check if there's a return request for this order
    $return_stmt = $pdo->prepare("SELECT * FROM return_requests WHERE order_id = :order_id");
    $return_stmt->execute([':order_id' => $order_id]);
    $return_request = $return_stmt->fetch(PDO::FETCH_ASSOC);

    // Check if there's a cancellation request for this order
    $cancel_stmt = $pdo->prepare("SELECT * FROM cancellation_requests WHERE order_id = :order_id ORDER BY requested_at DESC LIMIT 1");
    $cancel_stmt->execute([':order_id' => $order_id]);
    $cancellation_request = $cancel_stmt->fetch(PDO::FETCH_ASSOC);

    // Get user discount rate for wholesalers
    $user_discount = 0;
    $is_bulk_buyer = false;
    if(isset($order['user_id'])){
        $user_stmt = $pdo->prepare("SELECT usertype_id, discount_rate FROM users WHERE user_id = :user_id");
        $user_stmt->execute([':user_id' => $order['user_id']]);
        $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user_info && $user_info['usertype_id'] == 3){
            $is_bulk_buyer = true;
            $user_discount = $user_info['discount_rate'];
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name, p.bundle_price, p.description, p.stock, p.category_id, 
               p.pieces_per_bundle, p.material, p.image_url,
               pv.size AS variant_size,
               oi.unit_price AS price
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $status_options = ['pending', 'processing', 'shipping', 'delivered', 'cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order['order_id'];?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
        }
        
        /* Animated Header */
        .order-details-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: white;
            text-align: center;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
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
            --primary-color: #35a4ffff; /* Shopee-inspired orange */
            --secondary-color: #e6e6fa; /* lavender accent */
            --accent-color: #f7931e; /* yellow-orange */
            --light-bg: #fafafa; /* soft white */
            --border-color: #e0e0e0;
            --bs-primary: #ff6b35;
        }

        body {
            background: linear-gradient(120deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        /* Enhanced Card Styles */
        .card {
            border: none !important;
            border-radius: 20px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
            transition: all 0.4s ease !important;
            overflow: hidden !important;
        }
        
        .card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 12px 40px rgba(37, 99, 235, 0.15) !important;
        }
        
        .card-header {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%) !important;
            color: white !important;
            border: none !important;
            padding: 1.5rem !important;
            font-weight: 600 !important;
        }
        
        .card-body {
            padding: 2rem !important;
        }
        
        /* Product Item Cards */
        .product-item-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        
        .product-item-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.1);
            transform: translateX(5px);
        }
        
        .product-item-card img {
            border-radius: 12px;
            transition: transform 0.3s ease;
        }
        
        .product-item-card:hover img {
            transform: scale(1.05);
        }
        
        /* Status Badge Enhancement - Only for order status badges */
        .card .badge,
        .badge-delivered,
        .badge-processing,
        .badge-pending,
        .badge-received,
        .badge-cancelled,
        .badge-success,
        .badge-warning,
        .badge-danger,
        .badge-info {
            padding: 0.5rem 1rem !important;
            border-radius: 50px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
        }
        
        /* Keep navbar badges small */
        .navbar .badge {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        body {
            background: linear-gradient(120deg, var(--light-bg) 0%, #fff 100%);
            min-height: 100vh;
        }

        .navbar-brand {
            color: #6c63ff !important; /* darker lavender for text */
            font-weight: 700;
            font-size: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border: none;
            color: #333;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, var(--accent-color) 0%, var(--primary-color) 100%);
        }

        .text-primary {
            color: #6c63ff !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .card {
            border-radius: 1.5rem;
            border: 1.5px solid var(--border-color);
            box-shadow: 0 6px 30px 0 rgba(230,230,250,0.15);
        }

        /* Status highlighting */
        .status-pending {
            border-color: #d3d3d3 !important;
            background-color: #f5f5f5 !important;
            color: #6c757d;
        }

        .status-processing {
            border-color: #add8e6 !important;
            background-color: #e0f6ff !important;
            color: #0c4a6e;
        }

        .status-shipping {
            border-color: #87ceeb !important;
            background-color: #b0e0e6 !important;
            color: #0369a1;
        }

        .status-delivered {
            border-color: #90ee90 !important;
            background-color: #d4edda !important;
            color: #155724;
        }

        .status-received {
            border-color: #dda0dd !important;
            background-color: #f0e6ff !important;
            color: #6b21a8;
        }

        .status-cancelled {
            border-color: #ffb6c1 !important;
            background-color: #ffe6e6 !important;
            color: #721c24;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 20px 10px;
            }

            .card {
                margin: 10px 0;
            }

            .card-body {
                padding: 1rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .table-responsive {
                font-size: 0.9rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .timeline-container {
                flex-direction: column;
            }

            .timeline-step {
                margin-bottom: 20px;
            }

            .timeline-line {
                width: 2px;
                height: 40px;
                left: 25px;
                top: 50px;
            }

            .product-card {
                margin-bottom: 20px;
            }

            .product-card img {
                border-radius: 15px 15px 0 0;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 0.75rem;
            }

            .table-responsive {
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
            }

            .timeline-icon {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .product-card .card-body {
                padding: 1rem;
            }
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
            color: #6c63ff !important;
        }

        .custom-navbar .navbar-brand {
            font-weight: 700;
            color: #6c63ff !important;
        }

        .badge.cart-badge {
            background-color: #6c63ff !important;
            transition: transform 0.2s ease;
        }

        .badge.cart-badge:hover {
            transform: scale(1.1);
        }
    </style>
    <style>
        /* Status Radio Button Styles */
        .status-radio {
            display: none;
        }

        .status-radio + label {
            padding: 1rem;
            border: 2px solid #dee2e6;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            text-align: center;
        }

        .status-radio:checked + label {
            border-color: #764ba2;
            background-color: #f8f9fa;
        }

        .status-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .btn-place-order {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 1rem;
            font-size: 1.1rem;
        }

        .btn-place-order:hover {
            opacity: 0.9;
        }

        /* Timeline Styles */
        .order-timeline {
            margin: 20px 0;
        }

        /* Enhanced Timeline */
        .timeline-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);
            padding: 2rem;
            border-radius: 16px;
            margin: 2rem 0;
            position: relative;
        }

        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            flex: 1;
        }

        .timeline-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #9ca3af;
            z-index: 1;
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .timeline-step.active .timeline-icon {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.3);
            animation: pulse-icon 2s ease-in-out infinite;
        }
        
        @keyframes pulse-icon {
            0%, 100% { transform: scale(1.1); }
            50% { transform: scale(1.15); }
        }

        .timeline-label {
            margin-top: 10px;
            font-size: 0.9rem;
            text-align: center;
            color: #666;
        }

        .timeline-step.active .timeline-label {
            color: var(--primary-color);
            font-weight: bold;
        }

        .timeline-line {
            position: absolute;
            top: 25px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e0e0e0;
            z-index: -1;
        }

        .timeline-line.active {
            background: var(--primary-color);
        }

        /* Modernized Product Cards */
        .product-card {
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        }

        .product-card img {
            height: 200px;
            object-fit: cover;
            border-radius: 15px 0 0 15px;
        }

        .product-card .card-body {
            padding: 1.5rem;
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    

    <?php $active = 'orders'; ?>
    <?php include 'includes/navbar_customer.php'; ?>

    <!-- Animated Header -->
    <header class="order-details-header">
        <!-- Floating Shapes -->
        <div style="position: absolute; top: 20%; left: 10%; width: 70px; height: 70px; background: rgba(251,191,36,0.3); border-radius: 50%; animation: float 6s ease-in-out infinite;"></div>
        <div style="position: absolute; top: 50%; right: 15%; width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 8s ease-in-out infinite 1s;"></div>
        <div style="position: absolute; bottom: 30%; left: 15%; width: 40px; height: 40px; background: rgba(251,191,36,0.4); transform: rotate(45deg); animation: float 7s ease-in-out infinite 2s;"></div>
        <div style="position: absolute; top: 30%; right: 20%; width: 80px; height: 80px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; animation: rotate 20s linear infinite;"></div>
        
        <div class="container" style="position: relative; z-index: 10;">
            <h1 class="display-4 fw-bold mb-2" style="animation: fadeInUp 1s ease-out;">
                <i class="bi bi-receipt-cutoff me-3"></i>Order #<?php echo $order['order_id'];?>
            </h1>
            <p class="lead" style="animation: fadeInUp 1s ease-out 0.2s; animation-fill-mode: both;">View your order details and track delivery status</p>
        </div>
    </header>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <a href="orders.php" class="btn btn-secondary btn-sm mb-3">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-box-seam me-2"></i>Order #<?= $order['order_id'] ?></h5>
                        <?php if (in_array(strtolower($order['status']), ['pending', 'preparing', 'processing']) && !$cancellation_request): ?>
                            <button type="button" class="btn btn-sm btn-warning" onclick="requestCancellation(<?= $order['order_id'] ?>)">
                                <i class="bi bi-x-circle me-1"></i>Request Cancellation
                            </button>
                        <?php elseif ($order['status'] === 'Delivered'): ?>
                            <?php if ($return_request): ?>
                                <!-- Show return request status -->
                                <div class="alert alert-<?= $return_request['return_status'] === 'Approved' ? 'success' : ($return_request['return_status'] === 'Rejected' ? 'danger' : ($return_request['return_status'] === 'Completed' ? 'info' : 'warning')) ?> mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Return Request: <?= $return_request['return_status'] ?></strong>
                                    <?php if ($return_request['return_status'] === 'Approved'): ?>
                                        <br><small>Your refund of ₱<?= number_format($return_request['refund_amount'], 2) ?> is being processed.</small>
                                    <?php elseif ($return_request['return_status'] === 'Completed'): ?>
                                        <?php if (strtolower($return_request['customer_refund_method']) === 'cash'): ?>
                                            <br><small>Your cash refund of ₱<?= number_format($return_request['refund_amount'], 2) ?> is ready for pickup. Please present this confirmation to SAPIN staff.</small>
                                            <?php 
                                            // Parse pickup details from refund_details
                                            $pickup_details = json_decode($return_request['refund_details'] ?? '{}', true);
                                            if (!empty($pickup_details['pickup_datetime'])): 
                                                $pickup_date = date('F j, Y - g:i A', strtotime($pickup_details['pickup_datetime']));
                                                $pickup_location = $pickup_details['pickup_location'] ?? 'Main Store - 140 Rose St., Brgy. Paciano Rizal, Bay, Laguna';
                                            ?>
                                                <br><small class="text-info"><i class="bi bi-calendar-check me-1"></i>Pickup Date & Time: <?= $pickup_date ?></small>
                                                <br><small class="text-info"><i class="bi bi-geo-alt me-1"></i>Pickup Location: <?= htmlspecialchars($pickup_location) ?></small>
                                            <?php endif; ?>
                                            <?php if ($return_request['refunded_at']): ?>
                                                <br><small class="text-muted"><i class="bi bi-clock me-1"></i>Completed on: <?= date('F j, Y - g:i A', strtotime($return_request['refunded_at'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <br><small>Your refund of ₱<?= number_format($return_request['refund_amount'], 2) ?> has been sent via <?= htmlspecialchars($return_request['refund_method']) ?>.</small>
                                            <?php if ($return_request['refunded_at']): ?>
                                                <br><small class="text-muted"><i class="bi bi-clock me-1"></i>Completed on: <?= date('F j, Y - g:i A', strtotime($return_request['refunded_at'])) ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php elseif ($return_request['return_status'] === 'Rejected'): ?>
                                        <br><small><?= htmlspecialchars($return_request['admin_notes'] ?? 'Your return request was not approved.') ?></small>
                                    <?php else: ?>
                                        <br><small>Your return request is pending review by admin.</small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <!-- Show action buttons only if no return request -->
                                <div class="d-flex gap-2">
                                    <form id="received-form-<?= $order['order_id'] ?>" action="backend/mark_received.php" method="POST">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <button type="button" class="btn btn-sm btn-success" onclick="confirmReceived(<?= $order['order_id'] ?>)">Mark as Received</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#returnModal<?= $order['order_id'] ?>">
                                        <i class="bi bi-arrow-return-left me-1"></i>Request Return/Refund
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="mb-4">
                            <h4 class="mb-4">Customer Information</h4>
                            <div class="row">
                                <div class="col-6">
                                    <p><strong>Full Name:</strong> <?= $order['fullname'] ?></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Contact Number:</strong> <?= $order['contact_number'] ?></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Shipping Address:</strong> <?= $order['shipping_address'] ?></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Notes:</strong> <?= $order['notes'] ?></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Payment Method:</strong> <?= $order['payment_method'] ?></p>
                                </div>
                                <div class="col-6">
                                    <p><strong>Order Date:</strong> <?= date("F j, Y - g:i A", strtotime($order['date'])) ?></p>
                                </div>
                            </div>                            
                        </div>
                        
                        <!-- Cancellation Request Status -->
                        <?php if ($cancellation_request): ?>
                            <div class="mb-4">
                                <div class="alert alert-<?php 
                                    echo $cancellation_request['status'] === 'pending' ? 'warning' : 
                                        ($cancellation_request['status'] === 'approved' ? 'success' : 'danger'); 
                                ?> border-start border-5">
                                    <h5 class="alert-heading">
                                        <i class="bi bi-<?php 
                                            echo $cancellation_request['status'] === 'pending' ? 'clock-history' : 
                                                ($cancellation_request['status'] === 'approved' ? 'check-circle' : 'x-circle'); 
                                        ?> me-2"></i>
                                        Cancellation Request: <?php echo ucfirst($cancellation_request['status']); ?>
                                    </h5>
                                    <hr>
                                    <p class="mb-2"><strong>Your Reason:</strong></p>
                                    <p class="mb-3"><?php echo nl2br(htmlspecialchars($cancellation_request['reason'])); ?></p>
                                    
                                    <?php if ($cancellation_request['status'] !== 'pending'): ?>
                                        <hr>
                                        <p class="mb-2"><strong>Admin Response:</strong></p>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($cancellation_request['admin_response'])); ?></p>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            Responded: <?php echo date('F j, Y - g:i A', strtotime($cancellation_request['responded_at'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <p class="mb-0"><small class="text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            Requested: <?php echo date('F j, Y - g:i A', strtotime($cancellation_request['requested_at'])); ?>
                                        </small></p>
                                        <p class="mb-0"><small>Please wait for admin approval.</small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <h4>Order Status</h4>
                            <?php if ($order['status'] === 'Cancelled'): ?>
                                <div class="card shadow-sm mb-4 border-danger">
                                    <div class="card-body text-center">
                                        <i class="bi bi-x-circle status-icon text-danger"></i>
                                        <h5 class="text-danger">Order Cancelled</h5>
                                        <?php if (!empty($order['cancel_reason'])): ?>
                                            <p class="mb-0"><strong>Reason:</strong> <?= htmlspecialchars($order['cancel_reason']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="order-timeline">
                                    <?php
                                    $statuses = ['Pending', 'Processing', 'Shipping', 'Delivered', 'Received'];
                                    $current_index = array_search($order['status'], $statuses);
                                    $icons = ['bi-hourglass', 'bi-gear', 'bi-truck', 'bi-check-circle', 'bi-house-check'];
                                    ?>
                                    <div class="timeline-container">
                                        <?php foreach ($statuses as $index => $status): ?>
                                            <div class="timeline-step <?= $index <= $current_index ? 'active' : '' ?>">
                                                <div class="timeline-icon">
                                                    <i class="bi <?= $icons[$index] ?>"></i>
                                                </div>
                                                <div class="timeline-label"><?= $status ?></div>
                                                <?php if ($index < count($statuses) - 1): ?>
                                                    <div class="timeline-line <?= $index < $current_index ? 'active' : '' ?>"></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>




                        <!-- Order Items -->
                        <h4 class="fade-in">Total Amount: ₱<?php echo number_format($order['amount']+$order['shipping_fee'], 2); ?></h4>
                        <p class="fade-in">Subtotal: ₱<?php echo number_format( $order['amount'], 2); ?></p>
                        <p class="fade-in">Shipping Fee: ₱<?php echo number_format($order['shipping_fee'], 2); ?></p>
                        <div class="row fade-in">
                            <?php foreach ($order_items as $index => $item): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card product-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                                        <div class="row g-0">
                                            <div class="col-md-4">
                                                <img src="uploads/products/<?= $item['image_url'] ?>" class="img-fluid" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                            </div>
                                            <div class="col-md-8">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($item['product_name']) ?></h5>
                                                    <?php if (!empty($item['variant_size'])): ?>
                                                        <p class="card-text"><strong>Size:</strong> <?= htmlspecialchars($item['variant_size']) ?></p>
                                                    <?php endif; ?>
                                                    <p class="card-text"><strong>Material:</strong> <?= htmlspecialchars($item['material']) ?></p>
                                                    <p class="card-text"><strong>Price:</strong> ₱<?= number_format($item['price'], 2) ?></p>
                                                    <p class="card-text"><strong>Quantity:</strong> <?= $item['quantity'] ?></p>
                                                    <p class="card-text"><strong>Total:</strong> ₱<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
        function confirmCancel(orderId) {
            Swal.fire({
                title: 'Cancel this order?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, cancel it',
                cancelButtonText: 'No',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('cancel-form-' + orderId).submit();
                }
            });
        }

        function confirmReceived(orderId) {
            Swal.fire({
                title: 'Mark as Received?',
                text: 'Please confirm that you received your order.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, I received it',
                cancelButtonText: 'Not yet',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('received-form-' + orderId).submit();
                }
            });
        }

        function requestCancellation(orderId) {
            Swal.fire({
                title: 'Request Order Cancellation',
                html: `
                    <p class="text-start mb-3">Please provide a reason for cancelling this order:</p>
                    <textarea id="cancellation-reason" class="form-control" rows="4" 
                        placeholder="e.g., Changed my mind, Found a better deal, Ordered by mistake, etc."></textarea>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Submit Request',
                confirmButtonColor: '#dc3545',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const reason = document.getElementById('cancellation-reason').value.trim();
                    if (!reason) {
                        Swal.showValidationMessage('Please provide a reason for cancellation');
                        return false;
                    }
                    if (reason.length < 10) {
                        Swal.showValidationMessage('Please provide a more detailed reason (at least 10 characters)');
                        return false;
                    }
                    return reason;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('reason', result.value);

                    fetch('backend/request_cancellation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Request Submitted',
                                text: data.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to submit cancellation request. Please try again.'
                        });
                    });
                }
            });
        }
    </script>

    <!-- Return/Refund Modal -->
    <div class="modal fade" id="returnModal<?= $order['order_id'] ?>" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="returnModalLabel">
                        <i class="bi bi-arrow-return-left me-2"></i>Request Return/Refund
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="returnForm<?= $order['order_id'] ?>" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Order #<?= $order['order_id'] ?></strong><br>
                            Amount: ₱<?= number_format($order['amount'], 2) ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="returnReason<?= $order['order_id'] ?>" class="form-label">
                                <strong>Reason for Return/Refund <span class="text-danger">*</span></strong>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="returnReason<?= $order['order_id'] ?>" 
                                name="reason" 
                                rows="4" 
                                placeholder="Please provide a detailed reason for your return/refund request..."
                                required
                            ></textarea>
                            <small class="text-muted">Minimum 20 characters</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="returnImages<?= $order['order_id'] ?>" class="form-label">
                                <strong>Upload Images <span class="text-danger">*</span></strong>
                            </label>
                            <input 
                                type="file" 
                                class="form-control" 
                                id="returnImages<?= $order['order_id'] ?>" 
                                name="images[]" 
                                accept="image/*"
                                multiple
                                required
                            >
                            <small class="text-muted">Upload at least 1 image (max 5 images, JPG, PNG, WEBP)</small>
                        </div>
                        
                        <hr>
                        <h6 class="mb-3"><i class="bi bi-wallet2 me-2"></i>Refund Payment Details</h6>
                        
                        <div class="mb-3">
                            <label for="refundMethod<?= $order['order_id'] ?>" class="form-label">
                                <strong>Preferred Refund Method <span class="text-danger">*</span></strong>
                            </label>
                            <select 
                                class="form-select" 
                                id="refundMethod<?= $order['order_id'] ?>" 
                                name="customer_refund_method" 
                                required
                            >
                                <option value="">Select payment method...</option>
                                <option value="GCash">GCash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash (Pick-up)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="paymentDetailsContainer<?= $order['order_id'] ?>">
                            <label for="paymentDetails<?= $order['order_id'] ?>" class="form-label">
                                <strong>Payment Details <span class="text-danger">*</span></strong>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="paymentDetails<?= $order['order_id'] ?>" 
                                name="customer_payment_details" 
                                rows="3" 
                                placeholder="Enter your GCash number, bank account details, or other payment information..."
                                required
                            ></textarea>
                            <small class="text-muted" id="paymentDetailsHelp<?= $order['order_id'] ?>">
                                Examples:<br>
                                • GCash: 09XX XXX XXXX (Name)<br>
                                • Bank: Account Number, Bank Name, Account Name<br>
                                • Other: Enter your preferred payment method details
                            </small>
                        </div>
                        
                        <div class="mb-3 d-none" id="cashPickupInfo<?= $order['order_id'] ?>">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Cash Refund Information</strong><br>
                                <small>Wait for admin to set the pickup date, time, and location. You will receive a notification when your cash refund is ready for pickup.</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send me-1"></i>Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Handle refund method selection
        document.getElementById('refundMethod<?= $order['order_id'] ?>').addEventListener('change', function() {
            const method = this.value;
            const paymentDetailsContainer = document.getElementById('paymentDetailsContainer<?= $order['order_id'] ?>');
            const paymentDetailsField = document.getElementById('paymentDetails<?= $order['order_id'] ?>');
            const paymentDetailsHelp = document.getElementById('paymentDetailsHelp<?= $order['order_id'] ?>');
            const cashPickupInfo = document.getElementById('cashPickupInfo<?= $order['order_id'] ?>');
            
            if (method.toLowerCase() === 'cash') {
                // Hide payment details for cash refunds
                paymentDetailsContainer.classList.add('d-none');
                paymentDetailsField.removeAttribute('required');
                paymentDetailsField.value = '';
                cashPickupInfo.classList.remove('d-none');
            } else {
                // Show payment details for digital refunds
                paymentDetailsContainer.classList.remove('d-none');
                paymentDetailsField.setAttribute('required', 'required');
                cashPickupInfo.classList.add('d-none');
                
                // Update placeholder and help text based on method
                if (method.toLowerCase() === 'gcash') {
                    paymentDetailsField.placeholder = 'Enter your GCash number and name (e.g., 09XX XXX XXXX - Juan Dela Cruz)';
                    paymentDetailsHelp.innerHTML = 'Example: 09XX XXX XXXX - Juan Dela Cruz';
                } else if (method.toLowerCase() === 'bank transfer') {
                    paymentDetailsField.placeholder = 'Enter your bank account details (Account Number, Bank Name, Account Name)';
                    paymentDetailsHelp.innerHTML = 'Example: 1234567890 - BPI - Juan Dela Cruz';
                } else {
                    paymentDetailsField.placeholder = 'Enter your payment details...';
                    paymentDetailsHelp.innerHTML = 'Enter your preferred payment information';
                }
            }
        });
        
        // Handle return form submission
        document.getElementById('returnForm<?= $order['order_id'] ?>').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const reason = document.getElementById('returnReason<?= $order['order_id'] ?>').value.trim();
            const images = document.getElementById('returnImages<?= $order['order_id'] ?>').files;
            
            if (reason.length < 20) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reason Too Short',
                    text: 'Please provide a more detailed reason (at least 20 characters)'
                });
                return;
            }
            
            if (images.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Images Required',
                    text: 'Please upload at least 1 image to support your return request'
                });
                return;
            }
            
            const formData = new FormData(this);
            
            Swal.fire({
                title: 'Submitting...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('backend/submit_return_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Submitted!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred. Please try again.'
                });
                console.error('Error:', error);
            });
        });
    </script>
    
</body>

</html>