<?php 
    require ('config/db.php');
    session_start();
    require ('config/details_checker.php');
    
    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }

    // Get selected cart IDs from POST (when coming from cart.php)
    $selected_ids = isset($_POST['selected_cart_ids']) ? $_POST['selected_cart_ids'] : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sapin Bedsheets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --border-color: #e5e7eb;
            --text-muted: #6b7280;
        }

        body {
            background-color: #f9fafb;
        }

        .checkout-header {
            background: linear-gradient(135deg, #2563eb 0%, #f59e0b 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .checkout-header h1 {
            font-weight: 700;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .checkout-header .lead {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Enhanced Card Styling */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .card-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.25rem;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }

        /* Form Styling */
        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.65rem 0.75rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Order Summary Styling */
        .order-summary {
            position: sticky;
            top: 2rem;
        }

        .order-summary .card {
            background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
        }

        .order-summary hr {
            border-top: 2px solid #e5e7eb;
            margin: 1rem 0;
        }

        /* Payment Method Styling */
        .payment-method {
            display: none;
        }

        .payment-method+label {
            padding: 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            background: white;
            display: block;
            position: relative;
            overflow: hidden;
        }

        .payment-method+label:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .payment-method:checked+label {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.05) 0%, rgba(245, 158, 11, 0.05) 100%);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .payment-method:checked+label::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--primary-color);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .payment-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
        }

        /* Button Styling */
        .btn-place-order {
            background: linear-gradient(135deg, #2563eb 0%, #f59e0b 100%);
            border: none;
            padding: 1rem 2rem;
            font-size: 1.15rem;
            font-weight: 600;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .btn-place-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }

        .btn-place-order:active {
            transform: translateY(0);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .section-header i {
            font-size: 1.1rem;
        }

        /* Order Item Styling */
        .order-item {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        /* Progress Indicator */
        .checkout-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .progress-step::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            z-index: -1;
        }

        .progress-step:first-child::before {
            display: none;
        }

        .progress-step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e5e7eb;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .progress-step.active .progress-step-circle {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .progress-step-label {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>
    <style>
        .reseller-sidebar {
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #343a40;
            padding-top: 1rem;
            overflow-x: hidden;
            overflow-y: auto;
            z-index: 1030;
        }
        .reseller-sidebar a {
            padding: 12px 20px;
            text-decoration: none;
            font-size: 1rem;
            color: #adb5bd;
            display: block;
            transition: background-color 0.3s, color 0.3s;
        }
        .reseller-sidebar a:hover,
        .reseller-sidebar a.active {
            background-color: #495057;
            color: #fff;
        }
        .reseller-sidebar .sidebar-header {
            font-size: 1.25rem;
            color: #fff;
            padding: 0 20px 1rem 20px;
            font-weight: 700;
            border-bottom: 1px solid #495057;
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
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .checkout-header {
                padding: 2rem 0 !important;
            }
            .checkout-header h1 {
                font-size: 2rem !important;
            }
            .order-summary {
                position: static !important;
                margin-top: 2rem;
            }
            .payment-method+label {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            .payment-icon {
                font-size: 1.5rem !important;
            }
            .btn-place-order {
                font-size: 1rem;
                padding: 0.8rem;
            }
            .navbar-brand span {
                font-size: 1.1rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .checkout-header {
                padding: 1.5rem 0 !important;
            }
            .checkout-header h1 {
                font-size: 1.5rem !important;
            }
            .container {
                padding: 0 1rem;
            }
            .payment-method+label {
                padding: 0.6rem;
                font-size: 0.8rem;
            }
            .payment-icon {
                font-size: 1.2rem !important;
            }
            .btn-place-order {
                font-size: 0.9rem;
                padding: 0.7rem;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
            }
            .navbar-brand span {
                font-size: 1rem !important;
            }
        }
    </style>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>

    <?php $active = 'cart'; ?>
    <?php include 'includes/navbar_customer.php'; ?>
    
    <!-- bawal mag checkout kapag hindi pa verified ang email -->
    <?php if(!$user_data['is_verified']):?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Email not verified!',
            text: 'Please verify your email first.',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'cart.php'; // Redirect to cart.php
            }
        });
    </script>
    <?php endif; ?>


    <div class="checkout-header">
        <div class="container">
            <h1><i class="bi bi-credit-card me-2"></i>Checkout</h1>
            <p class="lead mb-0">Review your order and complete your purchase</p>
        </div>
    </div>

    <div class="container mb-5">
        <!-- Progress Indicator -->
        <div class="checkout-progress mb-4">
            <div class="progress-step">
                <div class="progress-step-circle">
                    <i class="bi bi-check"></i>
                </div>
                <div class="progress-step-label">Cart</div>
            </div>
            <div class="progress-step active">
                <div class="progress-step-circle">2</div>
                <div class="progress-step-label">Checkout</div>
            </div>
            <div class="progress-step">
                <div class="progress-step-circle">3</div>
                <div class="progress-step-label">Confirmation</div>
            </div>
        </div>

        <form method="POST" action="backend/checkout.php" id="checkout-form" enctype="multipart/form-data">
            <?php if (!empty($selected_ids)): ?>
            <?php foreach ($selected_ids as $id): ?>
            <input type="hidden" name="selected_cart_ids[]" value="<?php echo htmlspecialchars($id); ?>">
            <?php endforeach; ?>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-8">
                    <!-- Shipping Information -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-truck me-2 text-primary"></i>Shipping Information
                            </h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="fullname"
                                        value="<?php echo $user_data['firstname']." ".$user_data['lastname'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" name="contact_number" id="contact_number" onchange="validateContactNumber()"
                                        value="<?php echo $user_data['contact_number'] ?>" required>
                                </div>
                                <script>
                                    function validateContactNumber() {
                                        const contactNumberInput = document.getElementById('contact_number');
                                        let contactNumber = contactNumberInput.value;
                                        contactNumber = contactNumber.replace(/\D/g, '');
                                        if (contactNumber.length > 11) {
                                            contactNumber = contactNumber.substring(0, 11);
                                        }
                                        contactNumberInput.value = contactNumber;
                                    }
                                    document.getElementById('contact_number').addEventListener('input', function(event) {
                                        let inputValue = event.target.value;
                                        inputValue = inputValue.replace(/\D/g, '').slice(0, 11);
                                        event.target.value = inputValue;
                                    });

                                </script>
                            </div>

                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <?php 
                                        $stmt = $pdo->prepare("SELECT * FROM table_region");
                                        $stmt->execute();
                                        $region = $stmt->fetchAll();
                                    ?>
                                    <label class="form-label">Region</label>
                                    <select name="region_id" id="region" class="form-select" required>
                                        <?php foreach($region as $row): ?>
                                            <option value="<?php echo $row['region_id']; ?>"
                                                <?php if ($row['region_id'] == $user_data['region_id']) echo 'selected'; ?>>
                                                <?php echo $row['region_name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Province</label>
                                    <select name="province_id" id="province" class="form-select" required>
                                        
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Municipality</label>
                                    <select name="municipality_id" id="municipality" class="form-select" required>
                                        
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Barangay</label>
                                    <select name="barangay_id" id="barangay" class="form-select" required>
                                        
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">House Number</label>
                                <textarea class="form-control" name="house" rows="1"><?php echo $user_data['house']?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Order Notes (Optional)</label>
                                <textarea class="form-control" name="notes" rows="2"
                                    placeholder="Special instructions for delivery"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="bi bi-credit-card-2-front me-2 text-primary"></i>Payment Method
                            </h5>

                            <!-- Cash Payment Section -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3" style="font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="bi bi-cash-stack me-2"></i>Cash Payment
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <input type="radio" name="payment_method" value="COD"
                                            class="payment-method" id="payment-cod" required checked>
                                        <label for="payment-cod" class="text-center">
                                            <i class="bi bi-cash payment-icon"></i>
                                            <div class="fw-semibold">Cash on Delivery</div>
                                            <small class="text-muted">Pay when you receive</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- E-Wallet Payment Section -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3" style="font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="bi bi-wallet2 me-2"></i>E-Wallet Payment
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="radio" name="payment_method" value="GCash1" data-gcash-qr="sapin_gcash.png"
                                            class="payment-method gcash-option" id="payment-gcash1" required>
                                        <label for="payment-gcash1" class="text-center">
                                            <i class="bi bi-wallet2 payment-icon text-primary"></i>
                                            <div class="fw-semibold">GCash Account 1</div>
                                            <small class="text-muted">Scan QR to pay</small>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" name="payment_method" value="GCash2" data-gcash-qr="owner_gcash.png"
                                            class="payment-method gcash-option" id="payment-gcash2" required>
                                        <label for="payment-gcash2" class="text-center">
                                            <i class="bi bi-wallet2 payment-icon text-primary"></i>
                                            <div class="fw-semibold">GCash Account 2</div>
                                            <small class="text-muted">Scan QR to pay</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Bank Transfer Section -->
                            <div class="mb-2">
                                <h6 class="text-muted mb-3" style="font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <i class="bi bi-bank me-2"></i>Bank Transfer
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="radio" name="payment_method" value="BPI" data-account-number="1459094756" data-account-name="Liezel S. Vallejo"
                                            class="payment-method bank-option" id="payment-bpi" required>
                                        <label for="payment-bpi" class="text-center">
                                            <i class="bi bi-bank payment-icon text-success"></i>
                                            <div class="fw-semibold">BPI</div>
                                            <small class="text-muted">Bank of the Philippine Islands</small>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" name="payment_method" value="BDO" data-account-number="005910593332" data-account-name="Liezel S. Vallejo"
                                            class="payment-method bank-option" id="payment-bdo" required>
                                        <label for="payment-bdo" class="text-center">
                                            <i class="bi bi-bank payment-icon text-success"></i>
                                            <div class="fw-semibold">BDO</div>
                                            <small class="text-muted">Banco de Oro</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Use the selected_ids from the top of the page
                if (!empty($selected_ids)) {
                    $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
                    $stmt = $pdo->prepare("SELECT c.cart_id, c.quantity, p.*, pc.category_name FROM cart c
                    JOIN products p ON c.product_id = p.product_id
                    JOIN product_category pc ON p.category_id = pc.category_id
                    WHERE c.user_id = ? AND c.cart_id IN ($placeholders)");
                    $stmt->execute(array_merge([$_SESSION['user_id']], $selected_ids));
                } else {
                    $stmt = $pdo->prepare("SELECT c.cart_id, c.quantity, p.*, pc.category_name FROM cart c
                    JOIN products p ON c.product_id = p.product_id
                    JOIN product_category pc ON p.category_id = pc.category_id
                    WHERE c.user_id = :user_id");
                    $stmt->execute([':user_id' => $_SESSION['user_id']]);
                }
                $cart_data = $stmt->fetchAll();
                ?>
                <?php if(!$cart_data): ?>
                    <script>
                        Swal.fire({
                            icon: 'warning',
                            title: 'Your cart is empty',
                            text: 'You will be redirected to your cart.',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'cart.php'; // Redirect to cart.php
                            }
                        });
                    </script>
                <?php endif; ?>
                <div class="col-lg-4">
                    <!-- Order Summary -->
                    <div class="card shadow-sm order-summary">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-receipt me-2 text-primary"></i>Order Summary
                            </h5>
                            <?php $subtotal = 0.00; ?>
                            <?php foreach($cart_data as $row): ?>
                            <?php if($row['quantity'] > $row['stock']):?>
                                <script>
                                    Swal.fire({
                                        icon: 'warning',
                                        title: 'Quantity of item: <?php echo $row['product_name']; ?> is invalid - out of stock.',
                                        text: 'You will be redirected to your cart.',
                                        confirmButtonText: 'OK'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = 'cart.php'; // Redirect to cart.php
                                        }
                                    });
                                </script>
                            <?php endif; ?>
                            <div class="order-item d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark"><?php echo $row['product_name'];?></div>
                                    <small class="text-muted d-block mt-1">
                                        <i class="bi bi-box-seam me-1"></i>Qty: <?php echo $row['quantity'];?> × ₱<?php echo number_format($row['price'], 2);?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-rulers me-1"></i>Size: <?php echo $row['size'];?>
                                    </small>
                                </div>
                                <?php $item_price = $row['price'] * $row['quantity']; $subtotal += $item_price;?>
                                <div class="fw-bold text-primary ms-3">₱<?php echo number_format($item_price, 2);?></div>
                            </div>
                            <?php endforeach; ?>
                            
                            <hr>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($subtotal, 2);?></span>
                            </div>
                            <input type="hidden" id="subtotal_value" value="<?php echo $subtotal; ?>">
                            <input type="hidden" name="shipping_fee" id="shipping_fee_hidden" value="150.00">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Shipping Fee</span>
                                <span id="shipping_fee_display">₱150.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong class="fs-5" id="total_display">₱<?php echo number_format($subtotal + 0, 2); ?></strong>
                            </div>

                            <button type="submit" class="btn btn-primary btn-place-order w-100">
                                Place Order <i class="bi bi-arrow-right"></i>
                            </button>

                            <div class="text-center mt-3">
                                <a href="cart.php" class="text-decoration-none">
                                    <i class="bi bi-arrow-left"></i> Back to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const formData = new FormData(form);
        const paymentMethodInput = form.querySelector('input[name="payment_method"]:checked');
        const paymentMethod = paymentMethodInput.value;

        if (paymentMethod === 'GCash1' || paymentMethod === 'GCash2') {
            // Get the QR code image from the data attribute
            const qrImage = paymentMethodInput.getAttribute('data-gcash-qr');
            const accountNumber = paymentMethod === 'GCash1' ? '1' : '2';
            
            // Show GCash QR and file input modal first
            Swal.fire({
                title: `Pay with GCash Account ${accountNumber}`,
                html: `
                    <p>Scan the QR code below to pay and upload the proof of payment below:</p>
                    <img src="assets/img/${qrImage}" alt="GCash QR" style="width:200px;">
                    <br><br>
                    <input type="file" id="proof-upload" class="swal2-file" accept="image/*">
                `,
                showCancelButton: true,
                confirmButtonText: 'Submit Proof',
                preConfirm: () => {
                    const fileInput = document.getElementById('proof-upload');
                    if (!fileInput.files.length) {
                        Swal.showValidationMessage('Please upload proof of payment.');
                        return false;
                    }
                    return fileInput.files[0];
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const proofFile = result.value;
                    formData.append('proof_of_payment', proofFile);

                    confirmAndSubmit(formData, form.action);
                }
            });
        } else if (paymentMethod === 'BPI') {
            // Get the BPI account details from the data attributes
            const accountNumber = paymentMethodInput.getAttribute('data-account-number');
            const accountName = paymentMethodInput.getAttribute('data-account-name');
            
            // Show BPI bank transfer details and file input modal
            Swal.fire({
                title: 'Pay with BPI Bank Transfer',
                html: `
                    <div style="text-align: left; padding: 10px;">
                        <p><strong>Transfer to BPI Account:</strong></p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                            <p style="margin: 5px 0;"><strong>Account Number:</strong></p>
                            <p style="font-size: 1.3rem; color: #2563eb; font-weight: bold; margin: 5px 0;">${accountNumber}</p>
                            <p style="margin: 5px 0;"><strong>Account Name:</strong></p>
                            <p style="font-size: 1.1rem; color: #333; font-weight: 600; margin: 5px 0;">${accountName}</p>
                            <p style="margin: 5px 0; font-size: 0.9rem; color: #666;">Bank of the Philippine Islands (BPI)</p>
                        </div>
                        <p style="margin-top: 15px;">After transferring, please upload your proof of payment:</p>
                    </div>
                    <input type="file" id="proof-upload" class="swal2-file" accept="image/*" style="margin-top: 10px;">
                `,
                showCancelButton: true,
                confirmButtonText: 'Submit Proof',
                width: '500px',
                preConfirm: () => {
                    const fileInput = document.getElementById('proof-upload');
                    if (!fileInput.files.length) {
                        Swal.showValidationMessage('Please upload proof of payment.');
                        return false;
                    }
                    return fileInput.files[0];
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const proofFile = result.value;
                    formData.append('proof_of_payment', proofFile);

                    confirmAndSubmit(formData, form.action);
                }
            });
        } else if (paymentMethod === 'BDO') {
            // Get the BDO account details from the data attributes
            const accountNumber = paymentMethodInput.getAttribute('data-account-number');
            const accountName = paymentMethodInput.getAttribute('data-account-name');
            
            // Show BDO bank transfer details and file input modal
            Swal.fire({
                title: 'Pay with BDO Bank Transfer',
                html: `
                    <div style="text-align: left; padding: 10px;">
                        <p><strong>Transfer to BDO Account:</strong></p>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                            <p style="margin: 5px 0;"><strong>Account Number:</strong></p>
                            <p style="font-size: 1.3rem; color: #2563eb; font-weight: bold; margin: 5px 0;">${accountNumber}</p>
                            <p style="margin: 5px 0;"><strong>Account Name:</strong></p>
                            <p style="font-size: 1.1rem; color: #333; font-weight: 600; margin: 5px 0;">${accountName}</p>
                            <p style="margin: 5px 0; font-size: 0.9rem; color: #666;">Banco de Oro (BDO)</p>
                        </div>
                        <p style="margin-top: 15px;">After transferring, please upload your proof of payment:</p>
                    </div>
                    <input type="file" id="proof-upload" class="swal2-file" accept="image/*" style="margin-top: 10px;">
                `,
                showCancelButton: true,
                confirmButtonText: 'Submit Proof',
                width: '500px',
                preConfirm: () => {
                    const fileInput = document.getElementById('proof-upload');
                    if (!fileInput.files.length) {
                        Swal.showValidationMessage('Please upload proof of payment.');
                        return false;
                    }
                    return fileInput.files[0];
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const proofFile = result.value;
                    formData.append('proof_of_payment', proofFile);

                    confirmAndSubmit(formData, form.action);
                }
            });
        } else {
            // For COD, show existing confirm dialog
            Swal.fire({
                title: 'Confirm Order',
                text: "Are you sure you want to place this order?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, place order'
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmAndSubmit(formData, form.action);
                }
            });
        }
    });

    function confirmAndSubmit(formData, action) {
        fetch(action, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: data.message
                }).then(() => {
                    window.location.href = 'cart.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: data.message
                });
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Unexpected Error',
                text: 'Something went wrong. Please try again later.'
            });
        });
    }
</script>


</script>

<script>
  const subtotal = <?php echo $subtotal; ?>;
</script>

<script>
function loadLocation(parentId, type, targetSelectId, selectedValue = null) {
    fetch(`backend/get_location.php?type=${type}&parent_id=${parentId}`)
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById(targetSelectId);
        select.innerHTML = `<option selected disabled>Select ${type.charAt(0).toUpperCase() + type.slice(1)}</option>`;
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (selectedValue && item.id == selectedValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        // Clear dependent dropdowns
        if (type === 'province') {
            document.getElementById('municipality').innerHTML = '<option selected disabled>Select Municipality</option>';
            document.getElementById('barangay').innerHTML = '<option selected disabled>Select Barangay</option>';
        } else if (type === 'municipality') {
            document.getElementById('barangay').innerHTML = '<option selected disabled>Select Barangay</option>';
        }
    })
    .catch(err => console.error(err));
}
function fetchShippingFee() {
    const regionId = document.getElementById('region').value;
    const provinceId = document.getElementById('province').value;
    const municipalityId = document.getElementById('municipality').value;
    const barangayId = document.getElementById('barangay').value;

    const subtotal = parseFloat(document.getElementById('subtotal_value').value) || 0;

    fetch(`backend/get_shipping_fee.php?region_id=${regionId}&province_id=${provinceId}&municipality_id=${municipalityId}&barangay_id=${barangayId}`)
        .then(res => res.json())
        .then(data => {
            const fee = parseFloat(data.fee);

            // Update display
            document.getElementById('shipping_fee_display').textContent = `₱${fee.toFixed(2)}`;
            document.getElementById('total_display').textContent = `₱${(subtotal + fee).toFixed(2)}`;

            // Update hidden field
            document.getElementById('shipping_fee_hidden').value = fee.toFixed(2);
        })
        .catch(err => {
            console.error('Shipping fee fetch error:', err);
        });
}


document.addEventListener('DOMContentLoaded', function () {
    const userRegionId = <?php echo json_encode($user_data['region_id']); ?>;
    const userProvinceId = <?php echo json_encode($user_data['province_id']); ?>;
    const userMunicipalityId = <?php echo json_encode($user_data['municipality_id']); ?>;
    const userBarangayId = <?php echo json_encode($user_data['barangay_id']); ?>;

    if (userRegionId) {
        loadLocation(userRegionId, 'province', 'province', userProvinceId);

        setTimeout(() => {
            if (userProvinceId) {
                loadLocation(userProvinceId, 'municipality', 'municipality', userMunicipalityId);
                setTimeout(() => {
                    fetchShippingFee();
                }, 900);
            }
        }, 300);

        setTimeout(() => {
            if (userMunicipalityId) {
                loadLocation(userMunicipalityId, 'barangay', 'barangay', userBarangayId);
                setTimeout(() => {
                    fetchShippingFee();
                }, 900);
            }
        }, 600);
    }
});

// Event listeners (keep them for when user changes dropdowns)
document.getElementById('region').addEventListener('change', function () {
    loadLocation(this.value, 'province', 'province');
    fetchShippingFee();
});

document.getElementById('province').addEventListener('change', function () {
    loadLocation(this.value, 'municipality', 'municipality');
    fetchShippingFee();
});

document.getElementById('municipality').addEventListener('change', function () {
    loadLocation(this.value, 'barangay', 'barangay');
    fetchShippingFee();
});

document.getElementById('barangay').addEventListener('change', function () {
    fetchShippingFee();
});
</script>

</body>

</html>