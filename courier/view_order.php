<?php 
require('../config/session_courier.php');
require('../config/db.php');

// Handle messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    $_SESSION['error_message'] = "Order ID does not exist.";
    header('Location: index.php');
    exit();
}

// Get order details
$stmt = $pdo->prepare("
    SELECT 
        o.order_id, o.date, o.fullname, o.contact_number, o.house,
        o.payment_method, o.amount, o.shipping_fee, o.status,
        o.rider_id, o.cancel_reason, o.proof_image, o.user_id,
        u.usertype_id, u.discount_rate,
        b.barangay_name, m.municipality_name, p.province_name, r.region_name
    FROM orders o
    LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
    LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
    LEFT JOIN table_province p ON o.province_id = p.province_id
    LEFT JOIN table_region r ON o.region_id = r.region_id
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = :order_id
");
$stmt->execute([':order_id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $_SESSION['error_message'] = "Order not found.";
    header('Location: index.php');
    exit();
}

// Get order items
$items_stmt = $pdo->prepare("
    SELECT oi.*, p.product_name, p.image_url, 
           oi.unit_price AS price,
           pv.size AS variant_size
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.product_id
    LEFT JOIN product_variants pv ON oi.variant_id = pv.variant_id
    WHERE oi.order_id = :order_id
");
$items_stmt->execute([':order_id' => $order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

$full_address = $order['house'] . ', ' . $order['barangay_name'] . ', ' . 
                $order['municipality_name'] . ', ' . $order['province_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #JT<?php echo $order_id; ?>PH - Courier</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateX(-5px);
        }
        
        .container-custom {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .order-header {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .order-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .order-subtitle {
            color: #6b7280;
        }
        
        .status-badge-large {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #e0e7ff; color: #3730a3; }
        .status-shipping { background: #dbeafe; color: #1e40af; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-received { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        
        .card-modern {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .sticky-sidebar {
            position: sticky;
            top: 2rem;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-row {
            display: flex;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            flex: 0 0 200px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .info-value {
            flex: 1;
            color: #1f2937;
            font-weight: 500;
        }
        
        .product-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-details {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        
        .product-price {
            color: #1e40af;
            font-weight: 600;
        }
        
        .btn-modern {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }
        
        .btn-primary-modern:hover {
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-success-modern {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }
        
        .btn-danger-modern {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
        }
        
        .btn-outline-modern {
            background: white;
            border: 2px solid #3b82f6;
            color: #1e40af;
        }
        
        .btn-outline-modern:hover {
            background: #3b82f6;
            color: white;
        }
        
        .form-control-modern {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.75rem;
            transition: all 0.3s;
        }
        
        .form-control-modern:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .alert-modern {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        
        /* Responsive */
        @media (max-width: 991px) {
            .sticky-sidebar {
                position: relative;
                top: 0;
            }
            
            body {
                padding: 1rem;
            }
            
            .order-header {
                padding: 1.5rem;
            }
            
            .card-modern {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .order-title {
                font-size: 1.5rem;
            }
            
            .status-badge-large {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
            
            .info-label {
                flex: 0 0 120px;
                font-size: 0.875rem;
            }
            
            .info-value {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <a href="index.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-modern">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-modern">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Order Header -->
        <div class="order-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="order-title">Order #JT<?php echo $order_id; ?>PH</div>
                    <div class="order-subtitle">
                        <i class="bi bi-calendar"></i> <?php echo date('F d, Y', strtotime($order['date'])); ?>
                    </div>
                </div>
                <span class="status-badge-large status-<?php echo strtolower($order['status']); ?>">
                    <?php echo $order['status']; ?>
                </span>
            </div>
        </div>
        
        <div class="row g-3">
            <!-- Left Column - Main Content -->
            <div class="col-lg-8">
                <!-- Customer Information -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="bi bi-person-circle"></i> Customer Information
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="bi bi-person"></i> Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['fullname']); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="bi bi-telephone"></i> Contact</div>
                        <div class="info-value">
                            <a href="tel:<?php echo htmlspecialchars($order['contact_number']); ?>" 
                               style="color: #1e40af; text-decoration: none; font-weight: 600;">
                                <?php echo htmlspecialchars($order['contact_number']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="bi bi-geo-alt"></i> Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($full_address); ?></div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="bi bi-box-seam"></i> Order Items
                    </div>
                    <?php foreach ($items as $item): ?>
                        <div class="product-item">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="Product" class="product-image">
                            <?php else: ?>
                                <div class="product-image bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-image text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="product-details">
                                <?php
                                    // unit_price already has wholesaler discount applied at checkout time
                                    $unitPrice = $item['price'] ?? 0;
                                ?>
                                <div class="product-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Unknown Product'); ?></div>
                                <?php if (!empty($item['variant_size'])): ?>
                                    <div class="text-muted small">Size: <?php echo htmlspecialchars($item['variant_size']); ?></div>
                                <?php endif; ?>
                                <div class="text-muted small">Quantity: <?php echo $item['quantity']; ?></div>
                                <div class="product-price">₱<?php echo number_format($unitPrice, 2); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="card-modern">
                    <div class="card-title">
                        <i class="bi bi-receipt"></i> Order Summary
                    </div>
                    <div class="info-row">
                        <div class="info-label">Subtotal</div>
                        <div class="info-value">₱<?php echo number_format($order['amount'], 2); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Shipping Fee</div>
                        <div class="info-value">₱<?php echo number_format($order['shipping_fee'], 2); ?></div>
                    </div>
                    <div class="info-row" style="border-top: 2px solid #3b82f6; padding-top: 1rem; margin-top: 0.5rem;">
                        <div class="info-label"><strong style="font-size: 1.1rem;">Total Amount</strong></div>
                        <div class="info-value"><strong style="color: #1e40af; font-size: 1.5rem;">₱<?php echo number_format($order['amount'] + $order['shipping_fee'], 2); ?></strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value">
                            <span class="badge" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 0.5rem 1rem; font-size: 0.875rem;">
                                <?php echo htmlspecialchars($order['payment_method']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Actions Sidebar -->
            <div class="col-lg-4">
                <div class="sticky-sidebar">
                    <!-- Quick Actions -->
                    <div class="card-modern">
                    <div class="card-title">
                        <i class="bi bi-lightning-charge"></i> Quick Actions
                    </div>
                    
                    <!-- Contact Customer -->
                    <a href="tel:<?php echo htmlspecialchars($order['contact_number']); ?>" 
                       class="btn-modern btn-primary-modern w-100 mb-2">
                        <i class="bi bi-telephone-fill"></i> Call Customer
                    </a>
                    
                    <a href="sms:<?php echo htmlspecialchars($order['contact_number']); ?>" 
                       class="btn-modern btn-outline-modern w-100 mb-2" 
                       style="border: 2px solid #3b82f6; color: #1e40af;">
                        <i class="bi bi-chat-dots-fill"></i> Send SMS
                    </a>
                    
                    <!-- Locate Address -->
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($full_address); ?>" 
                       target="_blank"
                       class="btn-modern btn-success-modern w-100">
                        <i class="bi bi-geo-alt-fill"></i> Locate Address
                    </a>
                </div>
                
                <!-- Mark as Delivered -->
                <?php if ($order['status'] != 'Delivered' && $order['status'] != 'Received' && $order['status'] != 'Cancelled'): ?>
                <div class="card-modern">
                    <div class="card-title">
                        <i class="bi bi-check-circle"></i> Mark as Delivered
                    </div>
                    <form action="backend/update_order_status.php" method="POST" enctype="multipart/form-data" id="deliveryForm">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <input type="hidden" name="status" value="Delivered">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Proof of Delivery <span class="text-danger">*</span></label>
                            <input type="file" name="proof_image" class="form-control-modern" accept="image/*" required>
                            <small class="text-muted">Upload a photo as proof of delivery</small>
                        </div>
                        
                        <button type="submit" class="btn-modern btn-success-modern w-100">
                            <i class="bi bi-check-circle-fill"></i> Mark as Delivered
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Cancel Order -->
                <?php if ($order['status'] != 'Delivered' && $order['status'] != 'Received' && $order['status'] != 'Cancelled'): ?>
                <div class="card-modern" style="border: 2px solid #fee2e2;">
                    <div class="card-title text-danger">
                        <i class="bi bi-exclamation-triangle-fill"></i> Cancel Delivery
                    </div>
                    <div class="alert alert-warning" style="background: #fef3c7; border: none; border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                        <small><i class="bi bi-info-circle"></i> Only cancel if delivery cannot be completed</small>
                    </div>
                    <form action="backend/update_order_status.php" method="POST" id="cancelForm">
                        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                        <input type="hidden" name="status" value="Cancelled">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Reason <span class="text-danger">*</span></label>
                            <select name="cancel_reason" class="form-control-modern" id="cancelReasonSelect" required>
                                <option value="">-- Choose a reason --</option>
                                <option value="Customer not available">Customer not available</option>
                                <option value="Wrong/Incomplete address">Wrong or incomplete address</option>
                                <option value="Customer refused delivery">Customer refused delivery</option>
                                <option value="Customer requested cancellation">Customer requested cancellation</option>
                                <option value="Unable to contact customer">Unable to contact customer</option>
                                <option value="Weather/Road conditions">Weather or road conditions</option>
                                <option value="Other">Other (specify below)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="otherReasonDiv" style="display: none;">
                            <label class="form-label fw-semibold">Specify Reason <span class="text-danger">*</span></label>
                            <textarea name="cancel_reason_other" class="form-control-modern" rows="2" 
                                      placeholder="Please provide details..."></textarea>
                        </div>
                        
                        <button type="button" class="btn-modern btn-danger-modern w-100" onclick="confirmCancel()">
                            <i class="bi bi-x-circle-fill"></i> Cancel Delivery
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                </div> <!-- End sticky-sidebar -->
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Show/hide "Other" reason textarea
        const cancelReasonSelect = document.getElementById('cancelReasonSelect');
        const otherReasonDiv = document.getElementById('otherReasonDiv');
        
        if (cancelReasonSelect) {
            cancelReasonSelect.addEventListener('change', function() {
                if (this.value === 'Other') {
                    otherReasonDiv.style.display = 'block';
                    otherReasonDiv.querySelector('textarea').required = true;
                } else {
                    otherReasonDiv.style.display = 'none';
                    otherReasonDiv.querySelector('textarea').required = false;
                }
            });
        }
        
        // Confirm cancellation with SweetAlert
        function confirmCancel() {
            const form = document.getElementById('cancelForm');
            const reasonSelect = form.querySelector('[name="cancel_reason"]');
            const reasonOther = form.querySelector('[name="cancel_reason_other"]');
            
            let reason = reasonSelect.value.trim();
            
            if (!reason) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Reason Required',
                    text: 'Please select a reason for cancellation.',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            
            // If "Other" is selected, use the textarea value
            if (reason === 'Other') {
                const otherText = reasonOther.value.trim();
                if (!otherText) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Details Required',
                        text: 'Please specify the reason for cancellation.',
                        confirmButtonColor: '#3b82f6'
                    });
                    return;
                }
                reason = 'Other: ' + otherText;
                // Update the hidden field to include the full reason
                reasonSelect.value = reason;
            }
            
            Swal.fire({
                title: 'Cancel Delivery?',
                html: `
                    <div style="text-align: left; padding: 1rem;">
                        <p><strong>Are you sure you want to cancel this delivery?</strong></p>
                        <div style="background: #f3f4f6; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                            <div style="margin-bottom: 0.5rem;"><strong>Order:</strong> #JT<?php echo $order_id; ?>PH</div>
                            <div style="margin-bottom: 0.5rem;"><strong>Customer:</strong> <?php echo htmlspecialchars($order['fullname']); ?></div>
                            <div><strong>Amount:</strong> ₱<?php echo number_format($order['amount'], 2); ?></div>
                        </div>
                        <div style="background: #fee2e2; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                            <strong style="color: #991b1b;">Cancellation Reason:</strong><br>
                            <span style="color: #7f1d1d;">${reason}</span>
                        </div>
                        <p class="text-danger mt-3" style="font-weight: 600;">
                            <i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone!
                        </p>
                    </div>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="bi bi-check-circle"></i> Yes, Cancel Delivery',
                cancelButtonText: '<i class="bi bi-x-circle"></i> No, Keep It',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'btn-modern',
                    cancelButton: 'btn-modern'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        }
        
        // Show success message if delivered
        const deliveryForm = document.getElementById('deliveryForm');
        if (deliveryForm) {
            deliveryForm.addEventListener('submit', function(e) {
                const fileInput = this.querySelector('[name="proof_image"]');
                if (!fileInput.files.length) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Proof Required',
                        text: 'Please upload a photo as proof of delivery.',
                        confirmButtonColor: '#3b82f6'
                    });
                    return;
                }
                
                // Check file size (max 5MB)
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Maximum file size is 5MB. Your file is ' + (file.size / 1024 / 1024).toFixed(2) + 'MB.',
                        confirmButtonColor: '#3b82f6'
                    });
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Only JPG, PNG, or WEBP images are allowed. Your file is ' + file.type + '.',
                        confirmButtonColor: '#3b82f6'
                    });
                    return;
                }
            });
        }
    </script>
</body>
</html>
