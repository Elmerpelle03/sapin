<?php
session_start();
require 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? null;
$notif_id = $_GET['notif_id'] ?? null;

if (!$order_id) {
    header('Location: my_orders.php');
    exit;
}

// Mark notification as read if notif_id is provided
if ($notif_id) {
    $mark_read_stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE notification_id = :notif_id AND user_id = :user_id
    ");
    $mark_read_stmt->execute([
        ':notif_id' => $notif_id,
        ':user_id' => $_SESSION['user_id']
    ]);
}

// Fetch order details
$order_stmt = $pdo->prepare("
    SELECT o.*, 
           CONCAT(ud.house, ' ', tb.barangay_name, ', ', tm.municipality_name, ', ', tp.province_name) as full_address,
           ud.contact_number
    FROM orders o
    LEFT JOIN userdetails ud ON o.user_id = ud.user_id
    LEFT JOIN table_barangay tb ON ud.barangay_id = tb.barangay_id
    LEFT JOIN table_municipality tm ON ud.municipality_id = tm.municipality_id
    LEFT JOIN table_province tp ON ud.province_id = tp.province_id
    WHERE o.order_id = :order_id AND o.user_id = :user_id
");
$order_stmt->execute([
    ':order_id' => $order_id,
    ':user_id' => $_SESSION['user_id']
]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

// Try to fetch rider info if riders table exists and rider_id is set
$rider_info = null;
if ($order && !empty($order['rider_id'])) {
    try {
        $rider_stmt = $pdo->prepare("
            SELECT firstname, lastname, contact_number 
            FROM riders 
            WHERE rider_id = :rider_id
        ");
        $rider_stmt->execute([':rider_id' => $order['rider_id']]);
        $rider_info = $rider_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Riders table doesn't exist, skip rider info
        $rider_info = null;
    }
}

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header('Location: my_orders.php');
    exit;
}

// Fetch order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.product_name, p.image_url 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    WHERE oi.order_id = :order_id
");
$items_stmt->execute([':order_id' => $order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Sapin Bedsheets</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .order-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-shipping { background: #d1fae5; color: #065f46; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 30px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 8px;
            width: 2px;
            height: 100%;
            background: #e5e7eb;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-icon {
            position: absolute;
            left: -40px;
            top: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .timeline-icon.active {
            background: #22c55e;
            color: white;
        }
        
        .timeline-icon.inactive {
            background: #e5e7eb;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <?php $active = 'orders'; ?>
    <?php include 'includes/navbar_customer.php'; ?>
    
    <div class="order-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-2"><i class="bi bi-receipt me-2"></i>Order #<?= $order_id ?></h2>
                    <p class="mb-0">Placed on <?= date('F j, Y g:i A', strtotime($order['date'])) ?></p>
                </div>
                <div>
                    <span class="status-badge status-<?= strtolower($order['status']) ?>">
                        <?= $order['status'] ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container pb-5">
        <div class="row">
            <!-- Order Items -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Order Items</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order_items as $item): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <img src="uploads/products/<?= $item['image_url'] ?>" 
                                     alt="<?= $item['product_name'] ?>" 
                                     class="rounded"
                                     style="width: 80px; height: 80px; object-fit: cover;">
                                <div class="ms-3 flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h6>
                                    <p class="text-muted mb-0 small">
                                        Quantity: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <strong>₱<?= number_format($item['quantity'] * $item['price'], 2) ?></strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <h5 class="mb-0">Total Amount:</h5>
                            <h4 class="mb-0 text-primary">₱<?= number_format($order['amount'], 2) ?></h4>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Information -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Delivery Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Delivery Address:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($order['full_address']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>Contact Number:</strong></p>
                                <p class="text-muted"><?= htmlspecialchars($order['contact_number']) ?></p>
                            </div>
                        </div>
                        
                        <?php if ($rider_info): ?>
                            <hr>
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading"><i class="bi bi-person-badge me-2"></i>Assigned Rider</h6>
                                <p class="mb-1"><strong>Name:</strong> <?= htmlspecialchars($rider_info['firstname'] . ' ' . $rider_info['lastname']) ?></p>
                                <p class="mb-0"><strong>Contact:</strong> <?= htmlspecialchars($rider_info['contact_number']) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Status Timeline -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Order Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-icon active">
                                    <i class="bi bi-check"></i>
                                </div>
                                <h6>Order Placed</h6>
                                <small class="text-muted"><?= date('M j, Y g:i A', strtotime($order['date'])) ?></small>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-icon <?= in_array($order['status'], ['Processing', 'Shipping', 'Delivered']) ? 'active' : 'inactive' ?>">
                                    <i class="bi bi-<?= in_array($order['status'], ['Processing', 'Shipping', 'Delivered']) ? 'check' : 'circle' ?>"></i>
                                </div>
                                <h6>Processing</h6>
                                <small class="text-muted">Order is being prepared</small>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-icon <?= in_array($order['status'], ['Shipping', 'Delivered']) ? 'active' : 'inactive' ?>">
                                    <i class="bi bi-<?= in_array($order['status'], ['Shipping', 'Delivered']) ? 'check' : 'circle' ?>"></i>
                                </div>
                                <h6>Out for Delivery</h6>
                                <small class="text-muted">On the way to you</small>
                            </div>
                            
                            <div class="timeline-item">
                                <div class="timeline-icon <?= $order['status'] === 'Delivered' ? 'active' : 'inactive' ?>">
                                    <i class="bi bi-<?= $order['status'] === 'Delivered' ? 'check' : 'circle' ?>"></i>
                                </div>
                                <h6>Delivered</h6>
                                <small class="text-muted">
                                    <?= $order['status'] === 'Delivered' ? 'Order completed!' : 'Awaiting delivery' ?>
                                </small>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] === 'Cancelled'): ?>
                            <div class="alert alert-danger mt-3">
                                <h6 class="alert-heading"><i class="bi bi-x-circle me-2"></i>Order Cancelled</h6>
                                <?php if ($order['cancel_reason']): ?>
                                    <p class="mb-0"><strong>Reason:</strong> <?= htmlspecialchars($order['cancel_reason']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <a href="my_orders.php" class="btn btn-outline-primary w-100">
                    <i class="bi bi-arrow-left me-2"></i>Back to My Orders
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
