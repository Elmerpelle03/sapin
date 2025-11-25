<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
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
        $_SESSION['error_message'] = "Order ID does not exist.";
        header('Location: orders.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT 
            o.order_id,
            o.date,
            o.fullname,
            o.contact_number,
            o.house,
            o.payment_method,
            o.proof_of_payment,
            o.proof_metadata as metadata,
            o.amount,
            o.shipping_fee,
            o.status,
            o.proof_image,
            o.user_id,
            u.usertype_id,
            u.discount_rate,
            b.barangay_name,
            m.municipality_name,
            p.province_name,
            r.region_name,
            o.rider_id,
            o.cancel_reason
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

    // Parse metadata to get reference number
    $payment_reference = null;
    if (!empty($order['metadata'])) {
        $metadata = json_decode($order['metadata'], true);
        if (isset($metadata['payment_reference'])) {
            $payment_reference = $metadata['payment_reference'];
        }
    }

    $isFinal = in_array($order['status'], ['Delivered', 'Received', 'Cancelled']);
    
    // Define status progression order
    $statusOrder = ['Pending' => 0, 'Processing' => 1, 'Shipping' => 2, 'Delivered' => 3, 'Received' => 4];
    $currentStatusLevel = $statusOrder[$order['status']] ?? 0;

    if (!$order) {
        $_SESSION['error_message'] = "Order not found.";
        header('Location: orders.php');
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT oi.*, p.product_name, p.bundle_price, p.description, p.stock AS product_stock, p.category_id, 
               p.pieces_per_bundle, p.material, p.image_url,
               pv.size AS variant_size,
               pv.stock AS variant_stock,
               oi.unit_price AS price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
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
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
	<meta name="author" content="AdminKit">
	<meta name="keywords" content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<link rel="canonical" href="https://demo-basic.adminkit.io/pages-blank.html" />

	<title>Order #<?php echo $order['order_id'] ?></title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<!-- DataTables CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	<!-- Responsive extension CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
    <style>
        /* Page theme */
        body { background-color: #f7f9fc; font-size: 14px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .card-header { background: #fff; border-bottom: 1px solid #e5e7eb; border-top-left-radius: 12px; border-top-right-radius: 12px; padding: 1rem 1.25rem; }
        .card-title { font-weight: 600; color: #1f2937; font-size: 1.1rem; margin-bottom: 0; }
        .card-body { padding: 1.25rem; }

        /* Details card spacing */
        .details-list { 
            background: #f9fafb; 
            padding: 1.5rem; 
            border-radius: 8px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: space-evenly;
        }
        .detail-row { display: flex; padding: 0.85rem 0; border-bottom: 1px solid #e5e7eb; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { flex: 0 0 160px; font-weight: 600; color: #374151; font-size: 0.9rem; display: flex; align-items: center; }
        .detail-label i { margin-right: 0.5rem; color: #6b7280; font-size: 0.95rem; }
        .detail-value { flex: 1; color: #111827; font-size: 0.9rem; font-weight: 500; }
        
        /* Colorful icons in details */
        .detail-label i.bi-person-circle { color: #3b82f6; }
        .detail-label i.bi-telephone { color: #10b981; }
        .detail-label i.bi-calendar-event { color: #f59e0b; }
        .detail-label i.bi-cash-coin { color: #8b5cf6; }
        .detail-label i.bi-geo-alt { color: #ef4444; }
        .detail-label i.bi-sticky { color: #06b6d4; }

        /* Status cards */
        .status-radio { display: none; }
        .status-card { 
            position: relative; 
            border: 2px solid #e5e7eb; 
            border-radius: 10px; 
            padding: 16px 12px; 
            text-align: center; 
            cursor: pointer; 
            transition: all .2s ease; 
            background: #fff; 
            min-height: 95px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
        }
        .status-card .status-icon { font-size: 26px; margin-bottom: 6px; display: block; transition: transform .2s ease; }
        .status-card .status-text { font-weight: 600; color: #1f2937; font-size: 0.85rem; letter-spacing: 0.02em; }
        .status-card:not(.disabled):hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 16px rgba(0,0,0,.12); 
            border-color: #9ca3af;
        }
        .status-card:not(.disabled):hover .status-icon { transform: scale(1.1); }
        
        /* Status-specific colors */
        .status-card.pending { border-color: #fde68a; background: #fffef5; }
        .status-card.processing { border-color: #bfdbfe; background: #f8faff; }
        .status-card.shipping { border-color: #99f6e4; background: #f0fdfa; }
        .status-card.delivered { border-color: #bbf7d0; background: #f7fef9; }
        .status-card.received { border-color: #ddd6fe; background: #faf5ff; }
        .status-card.cancelled { border-color: #fecaca; background: #fff5f5; }
        
        /* Active/Checked state */
        .status-radio:checked + label.status-card { 
            box-shadow: 0 0 0 3px rgba(59,130,246,.2), 0 4px 12px rgba(0,0,0,.1);
            transform: scale(1.02);
        }
        .status-radio:checked + label.status-card.pending { background: #fef3c7; border-color: #f59e0b; border-width: 3px; }
        .status-radio:checked + label.status-card.processing { background: #dbeafe; border-color: #3b82f6; border-width: 3px; }
        .status-radio:checked + label.status-card.shipping { background: #ccfbf1; border-color: #14b8a6; border-width: 3px; }
        .status-radio:checked + label.status-card.delivered { background: #dcfce7; border-color: #22c55e; border-width: 3px; }
        .status-radio:checked + label.status-card.received { background: #ede9fe; border-color: #8b5cf6; border-width: 3px; }
        .status-radio:checked + label.status-card.cancelled { background: #fee2e2; border-color: #ef4444; border-width: 3px; }
        
        /* Disabled state */
        .status-card.disabled { 
            opacity: .5; 
            cursor: not-allowed; 
            background: #f9fafb;
            border-color: #e5e7eb;
        }
        .status-card.disabled:hover { transform: none; box-shadow: 0 1px 3px rgba(0,0,0,.05); }

        /* Controls */
        .form-select, .form-control { border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.9rem; padding: 0.5rem 0.75rem; }
        .form-select:focus, .form-control:focus { box-shadow: 0 0 0 3px rgba(59,130,246,.1); border-color: #3b82f6; outline: none; }
        .form-label { font-weight: 600; color: #374151; font-size: 0.875rem; margin-bottom: 0.5rem; }

        /* Buttons */
        .btn { font-size: 0.875rem; padding: 0.5rem 1rem; border-radius: 8px; font-weight: 600; transition: all .2s ease; }
        .btn-secondary { background: #6b7280; border-color: #6b7280; }
        .btn-secondary:hover { background: #4b5563; border-color: #4b5563; transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,.15); }
        .btn-outline-secondary { border-radius: 8px; font-size: 0.875rem; padding: 0.5rem 1rem; }
        .btn-save-status { 
            border-radius: 8px; 
            padding: 0.75rem 2rem; 
            font-weight: 700; 
            font-size: 0.95rem; 
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
            border: none; 
            color: #fff;
            box-shadow: 0 4px 12px rgba(37,99,235,.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-save-status:hover { 
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(37,99,235,.4); 
        }
        .btn-save-status:active { transform: translateY(0); }
        .btn-save-status i { font-size: 1.1rem; }
        /* Utility */
        .text-purple { color: #8b5cf6 !important; }
        .status-badge-corner { position: absolute; top: 14px; right: 14px; }

        /* Progress tracker */
        .order-progress { 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            gap: 12px; 
            padding: 2rem 1rem; 
            background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%);
            border-radius: 12px;
        }
        .order-step { 
            flex: 1; 
            text-align: center; 
            position: relative;
            transition: all .3s ease;
        }
        .order-step:not(:last-child)::after { 
            content: ""; 
            position: absolute; 
            top: 26px; 
            right: -50%; 
            width: 100%; 
            height: 4px; 
            background: #e5e7eb; 
            z-index: 1;
            border-radius: 2px;
        }
        .order-dot { 
            width: 52px; 
            height: 52px; 
            border-radius: 50%; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            background: #e5e7eb; 
            color: #9ca3af; 
            z-index: 2; 
            font-size: 1.3rem; 
            position: relative;
            transition: all .3s ease;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.1);
        }
        .order-label { 
            margin-top: 12px; 
            font-size: 0.9rem; 
            color: #9ca3af; 
            font-weight: 600;
            transition: all .3s ease;
        }
        .step-complete .order-dot { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
            color: #fff; 
            box-shadow: 0 0 0 4px #d1fae5, 0 4px 12px rgba(16,185,129,.3);
            transform: scale(1.05);
        }
        .step-complete .order-label { 
            color: #059669; 
            font-weight: 700;
        }
        .step-current .order-dot { 
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); 
            color: #fff; 
            box-shadow: 0 0 0 4px #dbeafe, 0 4px 12px rgba(59,130,246,.3);
            transform: scale(1.1);
            animation: pulse 2s infinite;
        }
        .step-current .order-label { 
            color: #2563eb; 
            font-weight: 700;
        }
        .step-complete:not(:last-child)::after { 
            background: linear-gradient(90deg, #10b981 0%, #86efac 100%);
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 4px #dbeafe, 0 4px 12px rgba(59,130,246,.3); }
            50% { box-shadow: 0 0 0 6px #dbeafe, 0 6px 16px rgba(59,130,246,.4); }
        }
        
        /* Cancelled Status */
        .cancelled-status {
            text-align: center;
            padding: 2rem 1rem;
        }
        .cancelled-icon {
            font-size: 5rem;
            color: #ef4444;
            margin-bottom: 1rem;
            animation: cancelPulse 2s ease-in-out infinite;
        }
        .cancelled-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #dc2626;
            margin-bottom: 0.75rem;
        }
        .cancelled-message {
            font-size: 1rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        .cancel-reason-box {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            text-align: left;
            max-width: 600px;
            margin: 0 auto;
            color: #991b1b;
            font-size: 0.95rem;
        }
        .cancel-reason-box strong {
            color: #7f1d1d;
        }
        
        @keyframes cancelPulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
        }

        /* Receipt totals */
        .receipt { 
            background: #fff; 
            border-radius: 10px; 
            box-shadow: 0 2px 8px rgba(0,0,0,.08); 
            padding: 1.25rem;
        }
        .receipt .row + .row { margin-top: 0.65rem; }
        .receipt .label { color: #6b7280; font-weight: 600; font-size: 0.95rem; }
        .receipt .value { font-weight: 600; color: #1f2937; font-size: 0.95rem; }
        .receipt .total { font-size: 1.2rem; font-weight: 700; color: #059669; }
        .receipt .negative { color: #dc2626; }
        
        /* Sidebar cards */
        h6.card-title { font-size: 0.95rem; font-weight: 600; }
        
        /* Vertical Progress Tracker */
        .progress-vertical {
            position: relative;
        }
        .progress-step {
            display: flex;
            gap: 1rem;
            padding: 0.75rem 0;
            position: relative;
        }
        .progress-step:not(:last-child)::after {
            content: "";
            position: absolute;
            left: 18px;
            top: 45px;
            width: 2px;
            height: calc(100% - 10px);
            background: #e5e7eb;
        }
        .progress-step.complete:not(:last-child)::after {
            background: #10b981;
        }
        .step-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e5e7eb;
            color: #9ca3af;
            font-size: 1rem;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
            transition: all .3s ease;
        }
        .progress-step.complete .step-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(16,185,129,.3);
        }
        .progress-step.current .step-icon {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 2px 8px rgba(59,130,246,.3);
            animation: pulse-small 2s infinite;
        }
        .step-content {
            flex: 1;
            padding-top: 0.35rem;
        }
        .step-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
            margin-bottom: 0.15rem;
        }
        .progress-step.complete .step-title {
            color: #059669;
        }
        .progress-step.current .step-title {
            color: #2563eb;
        }
        
        @keyframes pulse-small {
            0%, 100% { box-shadow: 0 2px 8px rgba(59,130,246,.3); }
            50% { box-shadow: 0 2px 12px rgba(59,130,246,.5); }
        }
        
        /* Section headers */
        h3, h4, h5 { font-weight: 600; color: #1f2937; margin-bottom: 1rem; }
        h3 { font-size: 1.25rem; }
        h4 { font-size: 1.1rem; }
        h5 { font-size: 1rem; }
        
        /* Badge improvements */
        .badge { font-size: 0.75rem; padding: 0.35rem 0.65rem; font-weight: 600; }
        
        /* View proof link */
        #view-proof-link { font-size: 0.85rem; font-weight: 600; text-decoration: none; padding: 0.25rem 0.6rem; background: #eff6ff; border-radius: 6px; transition: all .15s ease; display: inline-block; }
        #view-proof-link:hover { background: #dbeafe; text-decoration: none; }
        
        /* Helper text */
        .text-muted { color: #6b7280 !important; font-size: 0.85rem; }
        
        /* Section title enhancement */
        .section-title { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }
        .section-title i { color: #3b82f6; font-size: 1.2rem; }
        #print-header { display: none; }
        #print-receipt { display: none; }
        
        /* Order Items */
        .order-item-card {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            transition: all .2s ease;
        }
        .order-item-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,.12);
            transform: translateY(-2px);
        }
        .item-image-wrapper {
            height: 100%;
            min-height: 180px;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .item-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        .item-details {
            padding: 1.25rem;
        }
        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .item-total {
            font-size: 1.2rem;
            font-weight: 700;
            color: #059669;
        }
        .item-info {
            margin-top: 0.75rem;
        }
        .info-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 600;
            display: block;
            margin-bottom: 0.15rem;
        }
        .info-value {
            font-size: 0.9rem;
            color: #1f2937;
            font-weight: 500;
            display: block;
        }
        .item-description {
            font-size: 0.85rem;
            color: #6b7280;
            padding: 0.65rem;
            background: #f9fafb;
            border-radius: 6px;
            border-left: 3px solid #3b82f6;
        }
        .item-stock {
            font-size: 0.85rem;
            color: #059669;
            font-weight: 500;
        }
        
        /* Sticky Summary */
        .sticky-summary {
            position: sticky;
            top: 2rem;
        }
        
        /* Clean Summary Cards with Subtle Colors */
        .sticky-summary .card {
            border: 1px solid #e5e7eb;
        }
        
        /* Status Badge Card */
        .sticky-summary .card:nth-child(1) .card-body {
            background: linear-gradient(135deg, #ffffff 0%, #fef3c7 5%, #ffffff 100%);
        }
        
        /* Order Summary Card */
        .sticky-summary .card:nth-child(2) {
            border-left: 3px solid #10b981;
        }
        .sticky-summary .card:nth-child(2) .card-header {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
        }
        
        /* Progress Tracker Card */
        .sticky-summary .card:nth-child(3) {
            border-left: 3px solid #3b82f6;
        }
        .sticky-summary .card:nth-child(3) .card-header {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        }
        
        /* Simple Icon Circles */
        .icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        
        /* Scroll to top button */
        .scroll-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(59,130,246,.3);
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all .3s ease;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        .scroll-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(59,130,246,.4);
        }
        
        /* Ensure sidebar is visible on screen */
        @media screen {
            .sidebar {
                display: block !important;
                visibility: visible !important;
                width: 250px !important;
            }
        }
        
        /* Compact Professional Invoice Print Styles */
        @media print {
            /* Page setup */
            @page {
                margin: 10mm;
                size: A4 portrait;
            }
            
            /* Hide all UI elements */
            .sidebar, .navbar, .btn, .scroll-to-top, .alert, form,
            .status-card, #view-proof-link, #view-proof-btn,
            .section-title, .card-header, .progress-vertical, .footer { 
                display: none !important; 
                visibility: hidden !important;
            }
            
            /* Reset body */
            body {
                background: #fff !important;
                font-family: 'Arial', sans-serif;
                font-size: 8.5pt;
                color: #000;
                line-height: 1.2;
            }
            
            body::before, body::after {
                display: none !important;
            }
            
            .main, .container-fluid, .row, .col-lg-8, .col-lg-4 {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                display: block !important;
            }

            /* Stack columns for print with spacing */
            .row > [class^="col-"] {
                margin-bottom: 4mm !important;
            }
            /* Show only print header and receipt within container */
            .container-fluid > :not(#print-header):not(#print-receipt) {
                display: none !important;
            }
            /* Hide any duplicate header */
            #print-header-dup { display: none !important; }
            
            /* Print Header */
            #print-header {
                display: block !important;
                width: 100% !important;
                margin-bottom: 3mm;
            }
            #print-header .ph-row {
                display: table;
                width: 100%;
            }
            #print-header .ph-cell {
                display: table-cell;
                vertical-align: middle;
            }
            #print-header .brand {
                width: 25%;
                text-align: left;
            }
            #print-header .brand img {
                width: 26mm;
                height: auto;
            }
            #print-header .center {
                text-align: center;
                width: 50%;
                font-weight: bold;
                line-height: 1.2;
            }
            #print-header .meta {
                width: 25%;
                text-align: right;
                font-size: 8.5pt;
                line-height: 1.3;
            }
            /* Print Receipt block */
            #print-receipt {
                display: block !important;
                width: 100%;
                border: 1px solid #000;
                padding: 4mm;
                margin-bottom: 4mm;
            }
            #print-receipt h4 { margin: 0 0 2mm 0; font-size: 10pt; font-weight: bold; }
            #print-receipt .pr-row { display: table; width: 100%; margin-bottom: 2mm; }
            #print-receipt .pr-cell { display: table-cell; vertical-align: top; font-size: 8.5pt; }
            #print-receipt .left { width: 60%; }
            #print-receipt .right { width: 40%; text-align: right; }
            #print-receipt .muted { color: #555; }
            #print-receipt table { width: 100%; border-collapse: collapse; margin-top: 2mm; }
            #print-receipt th, #print-receipt td { border: 1px solid #000; padding: 1.5mm; font-size: 8.5pt; }
            #print-receipt th { background: #f3f4f6; text-align: left; }
            #print-receipt tfoot td { font-weight: bold; }
            #print-receipt .text-end { text-align: right; }
            #print-receipt .signature { margin-top: 10mm; display: table; width: 100%; }
            #print-receipt .signature .line { display: table-cell; width: 50%; text-align: center; }
            #print-receipt .signature .line span { display: inline-block; border-top: 1px solid #000; padding-top: 2mm; width: 60%; }
            
            /* Order number and date */
            h3 {
                position: relative;
                margin: 0 !important;
                padding: 1.5mm 0;
                border-bottom: 1px solid #000;
                font-size: 9pt;
                font-weight: bold;
            }
            
            h3::after {
                content: "Date: " attr(data-date);
                float: right;
                font-size: 8.5pt;
                font-weight: normal;
            }
            
            /* Cards as clean sections */
            .card {
                box-shadow: none !important;
                border: none !important;
                margin: 1.5mm 0 !important;
                page-break-inside: avoid;
            }
            
            .card-body {
                padding: 0 !important;
            }
            
            /* Order Details Table */
            .details-list {
                background: none !important;
                border: 1px solid #000;
                padding: 0 !important;
                margin: 1.5mm 0;
                display: table;
                width: 100%;
                border-collapse: collapse;
            }
            
            .details-list::before {
                content: "CUSTOMER INFORMATION";
                display: table-caption;
                background: #f3f4f6;
                padding: 1mm 2mm;
                font-weight: bold;
                font-size: 8.5pt;
                border: 1px solid #000;
                border-bottom: none;
                text-align: left;
            }
            
            .detail-row {
                display: table-row;
            }
            
            .detail-label, .detail-value {
                display: table-cell;
                padding: 1mm 2mm !important;
                border-bottom: 1px solid #ddd;
                vertical-align: middle;
                font-size: 8pt;
            }
            
            .detail-label {
                width: 35%;
                font-weight: bold;
                background: #f9fafb;
                text-align: left;
            }
            
            .detail-value {
                width: 65%;
                text-align: left;
            }
            
            .detail-label i {
                display: none;
            }
            
            .detail-row:last-child .detail-label,
            .detail-row:last-child .detail-value {
                border-bottom: none;
            }
            
            /* Order Items Section */
            .order-item-card {
                border: 1px solid #000 !important;
                border-top: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                background: none !important;
                page-break-inside: avoid;
            }
            
            .order-item-card:first-of-type {
                margin-top: 2mm !important;
            }
            
            .order-item-card:first-of-type::before {
                content: "ITEMS ORDERED";
                display: block;
                background: #f3f4f6;
                padding: 1mm 2mm;
                font-weight: bold;
                font-size: 8.5pt;
                border: 1px solid #000;
                border-bottom: none;
                text-align: left;
            }
            
            .order-item-card .row {
                display: block;
                margin: 0 !important;
            }
            
            .item-image-wrapper {
                display: none !important;
            }
            
            .col-md-3, .col-lg-2 {
                display: none !important;
            }
            
            .col-md-9, .col-lg-10 {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .item-details {
                padding: 1.5mm !important;
                border-bottom: 1px solid #ddd;
            }
            
            .order-item-card:last-of-type .item-details {
                border-bottom: none;
            }
            
            .item-name {
                font-size: 9pt;
                font-weight: bold;
                margin-bottom: 1mm !important;
                display: inline-block;
                width: 70%;
            }
            
            .item-total {
                float: right;
                font-size: 9pt;
                font-weight: bold;
                text-align: right;
                width: 25%;
            }
            
            .item-info {
                display: block !important;
                margin: 1mm 0 !important;
                clear: both;
            }
            
            .item-info > div {
                display: inline-block;
                margin-right: 4mm;
                font-size: 8pt;
            }
            
            .info-label {
                font-weight: bold;
            }
            
            .item-description, .item-stock {
                font-size: 8pt;
                margin-top: 0.5mm !important;
                display: block;
                clear: both;
            }
            
            /* Price Summary Table */
            .sticky-summary {
                margin-top: 2mm !important;
                border: 1px solid #000;
            }
            
            .sticky-summary::before {
                content: "PAYMENT SUMMARY";
                display: block;
                background: #f3f4f6;
                padding: 1mm 2mm;
                font-weight: bold;
                font-size: 8.5pt;
                border-bottom: 1px solid #000;
                text-align: left;
            }
            
            .sticky-summary .card {
                border: none !important;
                margin: 0 !important;
            }
            
            .sticky-summary .card-body {
                padding: 2mm !important;
            }
            
            .sticky-summary .card:nth-child(3) {
                display: none !important;
            }
            
            /* Align payment rows */
            .sticky-summary .d-flex {
                display: table-row !important;
            }
            
            .sticky-summary .d-flex > span,
            .sticky-summary .d-flex > strong {
                display: table-cell !important;
                padding: 1.5mm 0 !important;
            }
            
            .sticky-summary .d-flex > span:first-child {
                width: 60%;
                text-align: left;
            }
            
            .sticky-summary .d-flex > span:last-child,
            .sticky-summary .d-flex > strong:last-child {
                width: 40%;
                text-align: right;
            }
            
            .sticky-summary hr {
                margin: 2mm 0 !important;
                border-top: 1px solid #000 !important;
            }
            
            .sticky-summary .fs-5, .sticky-summary .fs-4 {
                font-size: 9pt !important;
            }
            
            .sticky-summary .text-success {
                color: #000 !important;
                font-weight: bold !important;
            }
            
            /* Compact Footer */
            .sticky-summary::after {
                content: "Thank you for your business! This is a computer-generated invoice.";
                display: block;
                text-align: center;
                margin-top: 2mm;
                padding-top: 2mm;
                border-top: 1px solid #000;
                font-size: 7.5pt;
                color: #666;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            /* General mobile adjustments */
            body { font-size: 13px; }
            .card { border-radius: 10px; margin-bottom: 1rem !important; }
            .card-body { padding: 1rem; }
            .card-header { padding: 0.875rem 1rem; }
            
            /* Header section - stack on mobile */
            .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-direction: column !important;
                gap: 1rem;
                align-items: stretch !important;
            }
            
            .d-flex.justify-content-between.align-items-center.mb-4 > div {
                width: 100%;
            }
            
            .d-flex.gap-2.align-items-center {
                flex-direction: column !important;
                gap: 0.75rem !important;
            }
            
            /* Order details - 2 column grid on mobile */
            .details-list {
                padding: 1rem;
                min-height: auto;
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 0.75rem;
            }
            
            .detail-row {
                display: flex;
                flex-direction: column;
                padding: 0.75rem;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #fff;
            }
            
            .detail-row:nth-child(n) {
                border-bottom: 1px solid #e5e7eb;
            }
            
            /* Full width for address and notes */
            .detail-row:nth-child(5),
            .detail-row:nth-child(6) {
                grid-column: 1 / -1;
            }
            
            .detail-label {
                flex: 0 0 auto;
                margin-bottom: 0.35rem;
                font-size: 0.75rem;
                font-weight: 700;
                color: #6b7280;
            }
            
            .detail-value {
                font-size: 0.85rem;
                font-weight: 600;
                color: #111827;
                word-break: break-word;
            }
            
            .detail-label i {
                font-size: 0.85rem;
            }
            
            /* Status cards - 2 columns on mobile */
            .status-card {
                min-height: 85px;
                padding: 12px 8px;
            }
            
            .status-card .status-icon {
                font-size: 22px;
                margin-bottom: 4px;
            }
            
            .status-card .status-text {
                font-size: 0.75rem;
            }
            
            /* Rider and cancel reason - stack on mobile */
            .row.mt-3.align-items-start.g-3 .col-md-6 {
                width: 100%;
            }
            
            /* Save button */
            .btn-save-status {
                width: 100%;
                justify-content: center;
                padding: 0.65rem 1.5rem;
            }
            
            .mt-4.d-flex {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            /* Order items - better mobile layout with 2 columns */
            .order-item-card {
                margin-bottom: 1rem !important;
            }
            
            .order-item-card .row {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 0;
            }
            
            /* Image column - left side */
            .order-item-card .col-md-3,
            .order-item-card .col-lg-2 {
                grid-column: 1;
            }
            
            /* Details column - right side */
            .order-item-card .col-md-9,
            .order-item-card .col-lg-10 {
                grid-column: 2;
            }
            
            .item-image-wrapper {
                min-height: 150px;
                max-height: 150px;
                padding: 0.5rem;
            }
            
            .item-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            
            .item-details {
                padding: 0.75rem;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .item-name {
                font-size: 0.9rem;
                line-height: 1.2;
                margin-bottom: 0.25rem !important;
            }
            
            .item-total {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }
            
            /* Item info - stack vertically in the right column */
            .item-info.row {
                display: flex !important;
                flex-direction: column !important;
                gap: 0.4rem !important;
                margin: 0 !important;
            }
            
            .item-info > div {
                display: flex;
                flex-direction: row;
                align-items: baseline;
                gap: 0.35rem;
                padding: 0 !important;
            }
            
            .info-label {
                font-size: 0.7rem;
                margin-bottom: 0;
                flex-shrink: 0;
            }
            
            .info-value {
                font-size: 0.75rem;
                font-weight: 600;
            }
            
            .item-description {
                font-size: 0.7rem;
                padding: 0.4rem;
                margin-top: 0.4rem !important;
            }
            
            .item-stock {
                font-size: 0.7rem;
                margin-top: 0.4rem !important;
            }
            
            /* Price breakdown - compact on mobile */
            .sticky-summary .card-body {
                padding: 0.875rem;
            }
            
            .sticky-summary .d-flex {
                font-size: 0.85rem;
            }
            
            .sticky-summary .total {
                font-size: 1.1rem !important;
            }
            
            /* Progress tracker - more compact */
            .progress-step {
                padding: 0.5rem 0;
            }
            
            .step-icon {
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
            
            .step-title {
                font-size: 0.85rem;
            }
            
            .progress-step:not(:last-child)::after {
                left: 15px;
                top: 38px;
            }
            
            /* Section titles */
            .section-title {
                font-size: 1rem;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
            }
            
            .section-title i {
                font-size: 1rem;
            }
            
            /* Scroll to top button */
            .scroll-to-top {
                bottom: 1rem;
                right: 1rem;
                width: 45px;
                height: 45px;
                font-size: 1.3rem;
            }
            
            /* Receipt totals */
            .receipt {
                padding: 1rem;
            }
            
            .receipt .row + .row {
                margin-top: 0.5rem;
            }
            
            /* Cancelled status */
            .cancelled-icon {
                font-size: 3.5rem;
            }
            
            .cancelled-title {
                font-size: 1.3rem;
            }
            
            /* Order progress horizontal - hide on mobile, use vertical */
            .order-progress {
                display: none !important;
            }
        }
        
        /* Extra small devices (phones in portrait, less than 576px) */
        @media (max-width: 576px) {
            body { font-size: 12px; }
            
            /* Status cards - still 2 columns but more compact */
            .status-card {
                min-height: 75px;
                padding: 10px 6px;
            }
            
            .status-card .status-icon {
                font-size: 20px;
            }
            
            .status-card .status-text {
                font-size: 0.7rem;
            }
            
            /* Details list - keep 2 columns but smaller */
            .details-list {
                padding: 0.75rem;
                gap: 0.5rem;
            }
            
            .detail-row {
                padding: 0.5rem;
            }
            
            .detail-label {
                font-size: 0.7rem;
            }
            
            .detail-value {
                font-size: 0.8rem;
            }
            
            /* Order items - even more compact for small phones */
            .order-item-card .row {
                grid-template-columns: 100px 1fr;
            }
            
            .item-image-wrapper {
                min-height: 120px;
                max-height: 120px;
                padding: 0.4rem;
            }
            
            .item-details {
                padding: 0.5rem;
            }
            
            .item-name {
                font-size: 0.85rem;
            }
            
            .item-total {
                font-size: 0.95rem;
            }
            
            /* Item info - more compact */
            .item-info.row {
                gap: 0.3rem !important;
            }
            
            .info-label {
                font-size: 0.65rem;
            }
            
            .info-value {
                font-size: 0.7rem;
            }
            
            .item-description {
                font-size: 0.65rem;
                padding: 0.3rem;
            }
            
            .item-stock {
                font-size: 0.65rem;
            }
        }
    </style>
	
</head>

<body>
	<div class="wrapper">
		<?php $active = 'orders'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">

				<!-- Success/Error Messages -->
                <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
				<div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i> Back to Orders
                        </a>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <h3 class="mb-0 text-dark fw-semibold me-3" data-date="<?= date('M d, Y h:i A', strtotime($order['date'])) ?>">Order #<?= $order['order_id'] ?></h3>
                        <button onclick="window.print()" class="btn btn-outline-primary">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Print header (brand + meta) -->
                <div id="print-header" class="mb-3">
                    <div class="ph-row">
                        <div class="ph-cell brand">
                            <img src="../assets/img/logo_forsapin.jpg" alt="SAPIN Logo">
                        </div>
                        <div class="ph-cell center">
                            <div>SAPIN</div>
                            <div>Official Receipt</div>
                        </div>
                        <div class="ph-cell meta">
                            <div>Order #: <?= htmlspecialchars($order['order_id']) ?></div>
                            <div>Date: <?= date('M d, Y h:i A', strtotime($order['date'])) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Print-only formal receipt -->
                <div id="print-receipt">
                    <div class="pr-row">
                        <div class="pr-cell left">
                            <h4>SAPIN</h4>
                            <div class="muted">Sapin Bedsheets</div>
                            <div class="muted">Philippines</div>
                        </div>
                        <div class="pr-cell right">
                            <div><strong>Order #:</strong> <?= htmlspecialchars($order['order_id']) ?></div>
                            <div><strong>Date:</strong> <?= date('M d, Y h:i A', strtotime($order['date'])) ?></div>
                        </div>
                    </div>

                    <div class="pr-row">
                        <div class="pr-cell left">
                            <h4>Bill To</h4>
                            <div><?= htmlspecialchars($order['fullname']) ?></div>
                            <div class="muted"><?= htmlspecialchars($order['contact_number']) ?></div>
                            <div class="muted">
                                <?= htmlspecialchars($order['house']) ?>, 
                                <?= htmlspecialchars($order['barangay_name']) ?>, 
                                <?= htmlspecialchars($order['municipality_name']) ?>, 
                                <?= htmlspecialchars($order['province_name']) ?>, 
                                <?= htmlspecialchars($order['region_name']) ?>
                            </div>
                        </div>
                        <div class="pr-cell right">
                            <h4>Payment</h4>
                            <div><strong>Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></div>
                            <?php if (!empty($payment_reference) && in_array($order['payment_method'], ['GCash1', 'GCash2', 'BPI', 'BDO'])): ?>
                            <div><strong>Reference No.:</strong> 
                                <span style="font-family: monospace; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;"><?= htmlspecialchars($payment_reference) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th style="width:48%">Item</th>
                                <th style="width:12%">Size</th>
                                <th style="width:10%">Qty</th>
                                <th style="width:15%">Unit Price</th>
                                <th style="width:15%" class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Compute unit prices consistent with checkout rules
                            $computedSubtotal = 0; 
                            foreach ($order_items as $it): 
                                $hasSavedUnit = isset($it['unit_price']) && $it['unit_price'] !== null && $it['unit_price'] !== '' && (float)$it['unit_price'] > 0;
                                $basePrice = $hasSavedUnit ? (float)$it['unit_price'] : ((isset($it['price']) && $it['price'] !== null) ? (float)$it['price'] : 0.0);
                                // If wholesaler and no saved unit price, apply discount to product price
                                if (!$hasSavedUnit && isset($order['usertype_id']) && (int)$order['usertype_id'] === 3 && isset($order['discount_rate']) && (float)$order['discount_rate'] > 0) {
                                    $unitPrice = round($basePrice * (1 - ((float)$order['discount_rate'] / 100)), 2);
                                } else {
                                    $unitPrice = $basePrice;
                                }
                                $qty = (int)$it['quantity'];
                                $lineTotal = $unitPrice * $qty;
                                $computedSubtotal += $lineTotal;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($it['product_name']) ?></td>
                                <td><?= htmlspecialchars($it['size'] ?? '') ?></td>
                                <td><?= $qty ?></td>
                                <td>₱<?= number_format($unitPrice, 2) ?></td>
                                <td class="text-end">₱<?= number_format($lineTotal, 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4">Subtotal</td>
                                <td class="text-end">₱<?= number_format((float)$order['amount'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4">Shipping</td>
                                <td class="text-end">₱<?= number_format((float)$order['shipping_fee'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="4">Total</td>
                                <td class="text-end">₱<?= number_format((float)$order['amount'] + (float)$order['shipping_fee'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>

                    
                </div>

					<div class="row">
						<!-- Left Column: Order Details -->
						<div class="col-lg-8">
							<div class="card mb-4">
								<div class="card-header">
									<h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>Order Details</h5>
								</div>
								<div class="card-body">
                                    <?php 
                                        $statusMapClass = [
                                            'Pending' => 'bg-warning',
                                            'Processing' => 'bg-primary',
                                            'Shipping' => 'bg-info',
                                            'Delivered' => 'bg-success',
                                            'Received' => 'bg-success',
                                            'Cancelled' => 'bg-danger'
                                        ];
                                        $badgeClass = $statusMapClass[$order['status']] ?? 'bg-secondary';
                                    ?>
                                    <div class="details-list">
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-person-circle"></i> Customer
                                                        </div>
                                                        <div class="detail-value"><?= htmlspecialchars($order['fullname']) ?></div>
                                                    </div>
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-telephone"></i> Contact Number
                                                        </div>
                                                        <div class="detail-value"><?= htmlspecialchars($order['contact_number']) ?></div>
                                                    </div>
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-calendar-event"></i> Order Date
                                                        </div>
                                                        <div class="detail-value"><?= date("F j, Y - g:i A", strtotime($order['date'])) ?></div>
                                                    </div>
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-cash-coin"></i> Payment Method
                                                        </div>
                                                        <div class="detail-value">
                                                            <?= htmlspecialchars($order['payment_method']) ?>
                                                            <?php if (in_array($order['payment_method'], ['GCash', 'GCash1', 'GCash2', 'BPI', 'BDO']) && !empty($order['proof_of_payment'])): ?>
                                                                <a href="#" id="view-proof-link" class="ms-2"><i class="bi bi-image"></i> View Proof</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($payment_reference) && in_array($order['payment_method'], ['GCash1', 'GCash2', 'BPI', 'BDO'])): ?>
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-receipt"></i> Reference Number
                                                        </div>
                                                        <div class="detail-value">
                                                            <span style="font-family: monospace; background: #f3f4f6; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?= htmlspecialchars($payment_reference) ?></span>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-geo-alt"></i> Shipping Address
                                                        </div>
                                                        <div class="detail-value">
                                                            <?= htmlspecialchars($order['house']) ?>, 
                                                            <?= htmlspecialchars($order['barangay_name']) ?>, 
                                                            <?= htmlspecialchars($order['municipality_name']) ?>, 
                                                            <?= htmlspecialchars($order['province_name']) ?>, 
                                                            <?= htmlspecialchars($order['region_name']) ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($order['notes'])): ?>
                                                    <div class="detail-row">
                                                        <div class="detail-label">
                                                            <i class="bi bi-sticky"></i> Notes
                                                        </div>
                                                        <div class="detail-value"><?= htmlspecialchars($order['notes']) ?></div>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
								</div>
							</div>
						</div>
						
						<!-- Right Column: Quick Summary -->
						<div class="col-lg-4">
							<!-- Status Badge -->
							<div class="card mb-4">
								<div class="card-body text-center">
									<span class="badge <?= $badgeClass ?> text-white" style="font-size: 1rem; padding: 0.5rem 1rem;">
										<?= htmlspecialchars($order['status']) ?>
									</span>
									<div class="mt-3">
										<small class="text-muted">Current Status</small>
									</div>
								</div>
							</div>
							
							<!-- Items Count -->
							<div class="card mb-3" style="border-left: 3px solid #3b82f6;">
								<div class="card-body" style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);">
									<div class="d-flex align-items-center justify-content-between mb-2">
										<span class="text-muted small"><i class="bi bi-box-seam me-1 text-primary"></i>Total Items:</span>
										<strong class="fs-5 text-primary"><?= count($order_items) ?></strong>
									</div>
									<div class="d-flex align-items-center justify-content-between">
										<span class="text-muted small"><i class="bi bi-cart-check me-1 text-success"></i>Total Quantity:</span>
										<strong class="fs-5 text-success"><?= array_sum(array_column($order_items, 'quantity')) ?></strong>
									</div>
								</div>
							</div>

							<!-- Price Breakdown -->
							<div class="card mb-3" style="border-left: 3px solid #10b981;">
								<div class="card-header" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);">
									<h6 class="card-title mb-0"><i class="bi bi-receipt me-2 text-success"></i>Price Breakdown</h6>
								</div>
								<div class="card-body">
									<div class="d-flex justify-content-between mb-2">
										<span class="text-muted">Subtotal:</span>
										<strong>₱<?= number_format($order['amount'], 2) ?></strong>
									</div>
									<div class="d-flex justify-content-between mb-2">
										<span class="text-muted">Shipping:</span>
										<strong>₱<?= number_format($order['shipping_fee'], 2) ?></strong>
									</div>
									<hr>
									<div class="d-flex justify-content-between">
										<span class="fw-bold">Total:</span>
										<span class="fw-bold text-success" style="font-size: 1.25rem;">
											₱<?= number_format($order['amount'] + $order['shipping_fee'], 2) ?>
										</span>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Update Order Status Section -->
					<div class="row mb-4">
						<div class="col-12">
							<h4 class="section-title">
								<i class="bi bi-gear-fill"></i> Update Order Status
							</h4>
						</div>
					</div>
					
					<div class="row">
						<!-- Left: Status Selection -->
						<div class="col-lg-8">
							<form id="order-status-form" action="backend/update_order_status.php" method="POST">
								<div class="card shadow-sm mb-4">
									<div class="card-body">
										<p class="text-muted mb-3">
											<i class="bi bi-lightbulb"></i> Select the current status. 
											<span class="text-info">You can only move forward or cancel.</span>
										</p>

                                                    <div class="row g-3">
                                                        <!-- Pending -->
                                                        <?php 
                                                            $pendingDisabled = $currentStatusLevel > 0 || $isFinal;
                                                        ?>
                                                        <div class="col-md-4 col-lg-2">
                                                            <input type="radio" name="status" value="Pending" id="status-pending" class="status-radio" <?= $order['status'] == 'Pending' ? 'checked' : '' ?> <?= $pendingDisabled ? 'disabled' : '' ?>>
                                                            <label for="status-pending" class="status-card pending <?= $pendingDisabled ? 'disabled' : '' ?>" <?= $pendingDisabled ? 'title="Cannot go back to previous status"' : '' ?>>
                                                                <i class="bi bi-hourglass status-icon text-warning"></i>
                                                                <span class="status-text">Pending</span>
                                                            </label>
                                                        </div>

                                                        <!-- Processing -->
                                                        <?php 
                                                            $processingDisabled = $currentStatusLevel > 1 || $isFinal;
                                                        ?>
                                                        <div class="col-md-4 col-lg-2">
                                                            <input type="radio" name="status" value="Processing" id="status-processing" class="status-radio" <?= $order['status'] == 'Processing' ? 'checked' : '' ?> <?= $processingDisabled ? 'disabled' : '' ?>>
                                                            <label for="status-processing" class="status-card processing <?= $processingDisabled ? 'disabled' : '' ?>" <?= $processingDisabled ? 'title="Cannot go back to previous status"' : '' ?>>
                                                                <i class="bi bi-gear status-icon text-primary"></i>
                                                                <span class="status-text">Processing</span>
                                                            </label>
                                                        </div>

                                                        <!-- Shipping -->
                                                        <?php 
                                                            $shippingDisabled = $currentStatusLevel > 2 || $isFinal;
                                                        ?>
                                                        <div class="col-md-4 col-lg-2">
                                                            <input type="radio" name="status" value="Shipping" id="status-shipping" class="status-radio" <?= $order['status'] == 'Shipping' ? 'checked' : '' ?> <?= $shippingDisabled ? 'disabled' : '' ?>>
                                                            <label for="status-shipping" class="status-card shipping <?= $shippingDisabled ? 'disabled' : '' ?>" <?= $shippingDisabled ? 'title="Cannot go back to previous status"' : '' ?>>
                                                                <i class="bi bi-truck status-icon text-info"></i>
                                                                <span class="status-text">Shipping</span>
                                                            </label>
                                                        </div>

                                                        <!-- Delivered (disabled) -->
                                                        <div class="col-md-4 col-lg-2">
                                                            <input type="radio" name="status" value="Delivered" id="status-delivered" class="status-radio" <?= $order['status'] == 'Delivered' ? 'checked' : '' ?> disabled>
                                                            <label for="status-delivered" class="status-card delivered disabled">
                                                                <i class="bi bi-check-circle status-icon text-success"></i>
                                                                <span class="status-text">Delivered</span>
                                                            </label>
                                                        </div>

                                                        <!-- Received (disabled) -->
                                                        <div class="col-md-4 col-lg-2">
                                                            <input type="radio" name="status" value="Received" id="status-received" class="status-radio" <?= $order['status'] == 'Received' ? 'checked' : '' ?> disabled>
                                                            <label for="status-received" class="status-card received disabled">
                                                                <i class="bi bi-house-check status-icon text-purple"></i>
                                                                <span class="status-text">Received</span>
                                                            </label>
                                                        </div>

                                                        <!-- Cancelled -->
                                                        <?php 
                                                            // Disable cancel if order is already in final status OR if it's Shipping or beyond
                                                            $cancelledDisabled = $isFinal || $currentStatusLevel >= 2;
                                                            $cancelTitle = $isFinal ? 'Order already in final status' : ($currentStatusLevel >= 2 ? 'Cannot cancel order once it is shipping' : '');
                                                        ?>
                                                        <div class="col-md-4 col-lg-2">
                                                            <input type="radio" name="status" value="Cancelled" id="status-cancelled" class="status-radio" <?= $order['status'] == 'Cancelled' ? 'checked' : '' ?> <?= $cancelledDisabled ? 'disabled' : '' ?>>
                                                            <label for="status-cancelled" class="status-card cancelled <?= $cancelledDisabled ? 'disabled' : '' ?>" <?= $cancelledDisabled ? 'title="' . $cancelTitle . '"' : '' ?>>
                                                                <i class="bi bi-x-circle status-icon text-danger"></i>
                                                                <span class="status-text">Cancelled</span>
                                                            </label>
                                                        </div>
                                                    </div>


                                                    <?php 
                                                        $stmt = $pdo->prepare("SELECT u.*, ud.firstname, ud.lastname FROM users u JOIN userdetails ud ON u.user_id = ud.user_id WHERE usertype_id = 4");
                                                        $stmt->execute();
                                                        $riders = $stmt->fetchAll();
                                                    ?>
                                                    <div class="row mt-3 align-items-start g-3">
                                                        <!-- Rider Select -->
                                                        <div class="col-md-6">
                                                            <label for="rider_id" class="form-label">Assign Delivery Rider</label>
                                                            <select name="rider_id" id="rider_id" class="form-select" <?= $order['status'] !== 'Shipping' ? 'disabled' : '' ?> <?= $isFinal ? 'disabled' : '' ?> required>
                                                                <option value="">Select a rider</option>
                                                                <?php foreach ($riders as $rider): ?>
                                                                    <option value="<?= $rider['user_id'] ?>" <?= $order['rider_id'] == $rider['user_id'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($rider['firstname'].' '.$rider['lastname']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <!-- Cancel Reason (dropdown) -->
                                                        <div class="col-md-6">
                                                            <label for="cancel_reason" class="form-label">Reason for Cancellation</label>
                                                            <?php $current_reason = trim($order['cancel_reason'] ?? ''); ?>
                                                            <select name="cancel_reason" id="cancel_reason" class="form-select" <?= $isFinal ? 'disabled' : '' ?> <?= $order['status'] !== 'Cancelled' ? 'disabled' : '' ?>>
                                                                <option value="">Select reason</option>
                                                                <?php 
                                                                    $reasons = ['Customer requested','Out of stock','Address issue','Payment issue','Damaged item','Other'];
                                                                    foreach ($reasons as $reason):
                                                                ?>
                                                                    <option value="<?= $reason ?>" <?= $current_reason === $reason ? 'selected' : '' ?>><?= $reason ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <input type="text" id="cancel_reason_other" class="form-control mt-2" placeholder="Specify other reason" style="display: none;" <?= $isFinal ? 'disabled' : '' ?> />
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($order['proof_image'])): ?>
                                                        <button type="button" class="btn btn-outline-secondary mt-3" id="view-proof-btn">
                                                            <i class="bi bi-image me-1"></i> View Proof of Delivery
                                                        </button>
                                                    <?php endif; ?>


										<input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
										<div class="mt-4 d-flex justify-content-between align-items-center">
											<small class="text-muted"><i class="bi bi-info-circle me-1"></i> Changes will be saved immediately</small>
											<button type="submit" class="btn btn-save-status" <?= $isFinal ? 'disabled' : '' ?>>
												<i class="bi bi-check-circle"></i> Save Status
											</button>
										</div>
									</div>
								</div>
							</form>
						</div>
						
						<!-- Right: Progress Tracker -->
						<div class="col-lg-4">
							<?php 
								$current = $order['status'];
								$isCancelled = ($current === 'Cancelled');
								
								if ($isCancelled) {
							?>
							<div class="card">
								<div class="card-header">
									<h6 class="card-title mb-0 text-danger"><i class="bi bi-x-octagon me-2"></i>Order Status</h6>
								</div>
								<div class="card-body text-center">
									<div class="cancelled-icon" style="font-size: 3rem; margin-bottom: 0.5rem;">
										<i class="bi bi-x-circle-fill"></i>
									</div>
									<h6 class="cancelled-title" style="font-size: 1.1rem; margin-bottom: 0.5rem;">Cancelled</h6>
									<p class="cancelled-message" style="font-size: 0.85rem; margin-bottom: 1rem;">This order has been cancelled.</p>
									<?php if (!empty($order['cancel_reason'])): ?>
									<div class="cancel-reason-box" style="text-align: left; font-size: 0.85rem;">
										<strong>Reason:</strong> <?= htmlspecialchars($order['cancel_reason']) ?>
									</div>
									<?php endif; ?>
								</div>
							</div>
							<?php 
								} else {
									$steps = ['Pending','Processing','Shipping','Delivered','Received'];
									$currentIndex = array_search($current, $steps);
									if ($currentIndex === false) { $currentIndex = 0; }
							?>
							<div class="card">
								<div class="card-header">
									<h6 class="card-title mb-0"><i class="bi bi-diagram-3 me-2"></i>Order Progress</h6>
								</div>
								<div class="card-body p-3">
									<div class="progress-vertical">
										<?php foreach ($steps as $i => $s): 
											$isComplete = $i < $currentIndex;
											$isCurrent = $i === $currentIndex;
											$icon = [
												'Pending' => 'hourglass',
												'Processing' => 'gear',
												'Shipping' => 'truck',
												'Delivered' => 'check-circle',
												'Received' => 'house-check'
											][$s];
										?>
										<div class="progress-step <?= $isComplete ? 'complete' : ($isCurrent ? 'current' : '') ?>">
											<div class="step-icon">
												<i class="bi bi-<?= $icon ?>"></i>
											</div>
											<div class="step-content">
												<div class="step-title"><?= $s ?></div>
												<?php if ($isCurrent): ?>
												<small class="text-primary">Current</small>
												<?php elseif ($isComplete): ?>
												<small class="text-success">✓ Done</small>
												<?php endif; ?>
											</div>
										</div>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>

					<!-- Order Items -->
					<div class="row">
						<div class="col-12">
                                    <div class="mb-4">
                                        <h4 class="section-title">
                                            <i class="bi bi-box-seam"></i> Order Items
                                        </h4>
                                        
                                        <?php foreach ($order_items as $item): ?>
                                            <div class="order-item-card mb-3">
                                                <div class="row g-0">
                                                    <div class="col-md-3 col-lg-2">
                                                        <div class="item-image-wrapper">
                                                            <img src="../uploads/products/<?= $item['image_url'] ?>" 
                                                                 class="item-image" 
                                                                 alt="<?= htmlspecialchars($item['product_name']) ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-9 col-lg-10">
                                                        <div class="item-details">
                                                            <?php
                                                                // unit_price already has wholesaler discount applied at checkout time
                                                                $unitPrice = $item['price'];
                                                            ?>
                                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                                <h5 class="item-name"><?= htmlspecialchars($item['product_name']) ?></h5>
                                                                <span class="item-total">₱<?= number_format($unitPrice * $item['quantity'], 2) ?></span>
                                                            </div>
                                                            <div class="row g-2 item-info">
                                                                <?php if (!empty($item['variant_size'])): ?>
                                                                <div class="col-md-6 col-lg-3">
                                                                    <span class="info-label">Size:</span>
                                                                    <span class="info-value"><?= htmlspecialchars($item['variant_size']) ?></span>
                                                                </div>
                                                                <?php endif; ?>
                                                                <div class="col-md-6 col-lg-3">
                                                                    <span class="info-label">Material:</span>
                                                                    <span class="info-value"><?= htmlspecialchars($item['material']) ?></span>
                                                                </div>
                                                                <div class="col-md-6 col-lg-3">
                                                                    <span class="info-label">Price:</span>
                                                                    <span class="info-value">₱<?= number_format($unitPrice, 2) ?></span>
                                                                </div>
                                                                <div class="col-md-6 col-lg-3">
                                                                    <span class="info-label">Quantity:</span>
                                                                    <span class="info-value">×<?= $item['quantity'] ?></span>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($item['description'])): ?>
                                                            <div class="item-description mt-2">
                                                                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($item['description']) ?>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div class="item-stock mt-2">
                                                                <i class="bi bi-box me-1"></i>Stock: 
                                                                <?php 
                                                                // Show variant stock if available, otherwise show product stock
                                                                $display_stock = !empty($item['variant_stock']) ? $item['variant_stock'] : $item['product_stock'];
                                                                echo $display_stock . ' available';
                                                                
                                                                // Show variant size if applicable
                                                                if (!empty($item['variant_size'])) {
                                                                    echo ' (Size: ' . htmlspecialchars($item['variant_size']) . ')';
                                                                }
                                                                ?>
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
			</main>

			<footer class="footer">
				<div class="container-fluid">
					
				</div>
			</footer>
		</div>
	</div>
	

	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<!-- Responsive extension JS -->
	<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
	
	
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
        // Populate the edit modal with the product data when Edit button is clicked
        function populateEditModal(productId) {
            // Make an AJAX call to fetch the product data based on productId
            fetch(`backend/fetch_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    // Populate the modal fieldss
                    document.getElementById('edit_product_name').value = data.product_name;
                    document.getElementById('edit_price').value = data.price;
                    document.getElementById('edit_bundle_price').value = data.bundle_price;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_stock').value = data.stock;
                    document.getElementById('edit_pieces_per_bundle').value = data.pieces_per_bundle;
                    document.getElementById('edit_category_id').value = data.category_id;
                    document.getElementById('edit_size').value = data.size;
                    document.getElementById('edit_material').value = data.material;
                    document.getElementById('edit_product_id').value = data.product_id;

                    // Show the existing image in the preview
                    document.getElementById('editImagePreview').src = `../uploads/products/${data.image_url}`;
                })
                .catch(error => console.error('Error fetching product data:', error));
        }
    </script>
    <script>
        function confirmDelete(productId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This product will be deleted permanently.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect to PHP delete handler
                    window.location.href = `backend/deleteproduct.php?id=${productId}`;
                }
            });
        }
    </script>

<script>
    // Direct inline script to handle order status changes
    document.addEventListener('DOMContentLoaded', function() {
        // Get all the elements we need
        const statusRadios = document.querySelectorAll('input[name="status"]');
        const riderSelect = document.getElementById('rider_id');
        const cancelReasonSelect = document.getElementById('cancel_reason');
        const cancelReasonOther = document.getElementById('cancel_reason_other');
        const orderForm = document.getElementById('order-status-form');
        
        // Function to enable/disable fields based on status
        function updateFields() {
            // Get the currently selected status
            let selectedStatus = '';
            for (const radio of statusRadios) {
                if (radio.checked) {
                    selectedStatus = radio.value;
                    break;
                }
            }
            
            // Always enable both fields first (important for Hostinger)
            riderSelect.disabled = false;
            cancelReasonSelect.disabled = false;
            
            // Then apply the correct state based on status
            if (selectedStatus === 'Shipping') {
                // For Shipping: Enable rider, disable reason
                riderSelect.disabled = false;
                cancelReasonSelect.disabled = true;
                cancelReasonSelect.value = '';
                cancelReasonOther.style.display = 'none';
                cancelReasonOther.value = '';
            } 
            else if (selectedStatus === 'Cancelled') {
                // For Cancelled: Disable rider, enable reason
                riderSelect.disabled = true;
                riderSelect.value = '';
                cancelReasonSelect.disabled = false;
                
                // Show/hide Other text field
                if (cancelReasonSelect.value === 'Other') {
                    cancelReasonOther.style.display = 'block';
                } else {
                    cancelReasonOther.style.display = 'none';
                }
            } 
            else {
                // For other statuses: Disable both
                riderSelect.disabled = true;
                riderSelect.value = '';
                cancelReasonSelect.disabled = true;
                cancelReasonSelect.value = '';
                cancelReasonOther.style.display = 'none';
                cancelReasonOther.value = '';
            }
        }
        
        // Initialize fields on page load
        updateFields();
        
        // Also run after a short delay (helps with some hosting environments)
        setTimeout(updateFields, 500);
        
        // Add event listeners to status radios
        for (const radio of statusRadios) {
            radio.addEventListener('change', updateFields);
            radio.addEventListener('click', updateFields);
        }
        
        // Add event listener to cancel reason select
        cancelReasonSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                cancelReasonOther.style.display = 'block';
            } else {
                cancelReasonOther.style.display = 'none';
                cancelReasonOther.value = '';
            }
        });
        
        // Form submission handler
        orderForm.addEventListener('submit', function(e) {
            // Prevent the default form submission
            e.preventDefault();
            
            // Get the selected status
            let selectedStatus = '';
            for (const radio of statusRadios) {
                if (radio.checked) {
                    selectedStatus = radio.value;
                    break;
                }
            }
            
            // Validate based on status
            if (selectedStatus === 'Cancelled') {
                if (!cancelReasonSelect.value) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Please select a reason for cancellation.'
                    });
                    return false;
                }
                
                if (cancelReasonSelect.value === 'Other' && !cancelReasonOther.value.trim()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Please enter a custom reason for cancellation.'
                    });
                    return false;
                }
                
                // If using custom reason, add it as hidden field
                if (cancelReasonSelect.value === 'Other') {
                    // Remove any existing hidden fields
                    const existingHidden = document.querySelector('input[name="cancel_reason"][type="hidden"]');
                    if (existingHidden) {
                        existingHidden.remove();
                    }
                    
                    // Create and add new hidden field
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'cancel_reason';
                    hiddenInput.value = cancelReasonOther.value.trim();
                    orderForm.appendChild(hiddenInput);
                    
                    // Remove name attribute from select
                    cancelReasonSelect.removeAttribute('name');
                }
            } 
            else if (selectedStatus === 'Shipping') {
                if (!riderSelect.value) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Please select a delivery rider.'
                    });
                    return false;
                }
            }
            
            // If we get here, form is valid - submit it
            orderForm.submit();
        });
    });
</script>

<script>
    // Proof of payment (GCash, BPI, BDO)
    $(document).ready(function() {
        $('#view-proof-link').on('click', function (e) {
            e.preventDefault();
            // Path in DB is '../uploads/proofs/file.jpg' (relative from backend)
            // We're in admin folder, so we need to go up one level
            let proofPath = '<?= htmlspecialchars($order['proof_of_payment']) ?>';
            // Remove '../' from the path since we're already in admin folder
            proofPath = proofPath.replace('../', '');
            
            Swal.fire({
                title: '<?= htmlspecialchars($order['payment_method']) ?> - Proof of Payment',
                imageUrl: '../' + proofPath,
                imageAlt: 'Proof of Payment',
                imageWidth: '90%',
                confirmButtonText: 'Close',
                showCloseButton: true,
                customClass: {
                    image: 'img-fluid'
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const viewProofBtn = document.getElementById('view-proof-btn');
        if (viewProofBtn) {
            viewProofBtn.addEventListener('click', function () {
                Swal.fire({
                    title: 'Proof of Delivery',
                    imageUrl: '../uploads/deliveries/<?= htmlspecialchars($order['proof_image']) ?>',
                    imageAlt: 'Proof of delivery image',
                    imageWidth: '100%',
                    confirmButtonText: 'Close'
                });
            });
        }
    });
</script> 

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="bi bi-arrow-up"></i>
</button>

<script>
// Scroll to top functionality
const scrollBtn = document.getElementById('scrollToTop');

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        scrollBtn.classList.add('show');
    } else {
        scrollBtn.classList.remove('show');
    }
});

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Auto-dismiss alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

</body>

</html>