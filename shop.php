<?php 
    require ('config/db.php');
    session_start();
    require ('config/details_checker.php');
    require('config/session_disallow_courier.php');
    // Get user discount rate for wholesalers discount
    $user_discount = 0;
    $is_wholesaler = false;
    if(isset($_SESSION['user_id'])){
        $stmt = $pdo->prepare("SELECT ud.userdetails_id, u.usertype_id, u.discount_rate 
                               FROM userdetails ud 
                               JOIN users u ON ud.user_id = u.user_id 
                               WHERE ud.user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$user_data){
            $_SESSION['error_message'] = "Please set your user details first.";
            header('Location: edit_profile.php');
            exit();
        }

        // Check if bulk buyer (usertype_id = 3)
        if($user_data['usertype_id'] == 3){
            $is_bulk_buyer = true;
            $user_discount = $user_data['discount_rate'];
        }
    }
    
    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    
    // Fetch all unique sizes from products
    $size_stmt = $pdo->query("SELECT DISTINCT size FROM products WHERE size IS NOT NULL AND size != '' ORDER BY size ASC");
    $available_sizes = $size_stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - SAPIN</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --accent-color: #ffffff;
        }
        
        body {
            background: linear-gradient(120deg, var(--light-bg) 0%, var(--accent-color) 100%);
            min-height: 100vh;
        }

        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--primary-color) 60%, var(--secondary-color) 100%);
            border: none;
        }

        .card {
            border-radius: 1rem;
            border: 1.5px solid var(--border-color);
            box-shadow: 0 4px 24px 0 rgba(80,80,150,0.1);
        }
    </style>
    <style>
        .filter-card {
            position: sticky;
            top: 1rem;
        }
        .product-card {
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
    </style>
    <style>
        .shop-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            background-position: center;
            color: white;
            text-align: center;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
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
        
        /* Enhanced Product Cards */
        .product-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            border-radius: 16px !important;
            overflow: hidden;
        }
        
        .product-card:hover {
            transform: translateY(-12px) !important;
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.25) !important;
        }
        
        .product-card img {
            transition: transform 0.6s ease !important;
        }
        
        .product-card:hover img {
            transform: scale(1.1) !important;
        }
        
        .shop-header::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }
        
        .shop-header-content {
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.8s ease-out;
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
        
        .shop-header h1 {
            text-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .filter-card {
            position: sticky;
            top: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: linear-gradient(to bottom, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.08);
            transition: all 0.3s ease;
        }
        
        .filter-card:hover {
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.15);
            transform: translateY(-2px);
        }
        
        .filter-card .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 16px 16px 0 0 !important;
            padding: 1.25rem;
            border: none;
        }
        
        .filter-card .form-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-card .form-control,
        .filter-card .form-select {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .filter-card .form-control:focus,
        .filter-card .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        /* Sticky Filter Bar */
        .sticky-filter-bar {
            position: fixed !important;
            top: 56px !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 999 !important;
            background: rgba(255, 255, 255, 0.98) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
            padding: 1rem !important;
            margin: 0 auto !important;
            max-width: 1140px !important;
            transform: translateY(0);
            opacity: 1;
            transition: transform 0.3s ease, opacity 0.3s ease !important;
        }
        
        .sticky-filter-bar.hidden {
            transform: translateY(-150%) !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }
        
        @media (min-width: 992px) {
            .sticky-filter-bar {
                top: 70px !important;
            }
        }
        
        /* Toggle Filter Button */
        .toggle-filter-btn {
            position: fixed;
            top: 130px;
            right: 20px;
            z-index: 1000;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            box-shadow: 0 4px 16px rgba(37, 99, 235, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .toggle-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(37, 99, 235, 0.4);
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
        }
        
        .toggle-filter-btn:active {
            transform: translateY(0);
        }
        
        .toggle-filter-btn i {
            font-size: 1.1rem;
        }
        
        .toggle-filter-btn .btn-text {
            display: inline;
        }
        
        @media (max-width: 768px) {
            .toggle-filter-btn {
                padding: 10px 16px;
                font-size: 0.85rem;
                right: 15px;
            }
            .toggle-filter-btn .btn-text {
                display: none;
            }
        }

        .product-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            background: white;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            position: relative;
        }
        
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .product-card:hover::before {
            transform: scaleX(1);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 32px rgba(37, 99, 235, 0.15);
            border-color: rgba(37, 99, 235, 0.3);
        }

        .product-card .card-img-top {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover .card-img-top {
            transform: scale(1.08);
        }

        .product-card .badge {
            font-size: 0.75rem;
            padding: 0.4em 0.8em;
            transition: all 0.3s ease;
            border-radius: 6px;
            font-weight: 600;
        }

        .product-card .badge:hover {
            transform: scale(1.1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .badge.bg-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        }
        
        .badge.bg-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
        }
        
        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        }
        
        .badge.bg-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }

        .product-card .card-body {
            padding: 1.5rem;
        }

        .product-card .price {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            color: white;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-add-cart:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        
        .btn-add-cart:active {
            transform: translateY(0);
        }

        #categoryFilter {
            min-width: 200px;
            border-color: #e0e0e0;
            border-radius: 20px;
            padding: 0.5rem 1rem;
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        #categoryFilter:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }
        
        /* Wishlist button styles */
        .wishlist-btn {
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover {
            transform: scale(1.1);
            background-color: #fee2e2;
        }
        
        .wishlist-btn.text-danger {
            background-color: #fecaca;
            border-color: #dc2626;
        }
        
        .wishlist-btn.text-danger:hover {
            background-color: #fca5a5;
        }
        
        .wishlist-btn i {
            transition: all 0.3s ease;
        }
        
        .wishlist-btn:hover i {
            transform: scale(1.2);
        }
    </style>
    <style>
        /* Page load animation */
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
        
        .product-item {
            animation: fadeIn 0.6s ease-out forwards;
            opacity: 0;
        }
        
        .product-item:nth-child(1) { animation-delay: 0.1s; }
        .product-item:nth-child(2) { animation-delay: 0.2s; }
        .product-item:nth-child(3) { animation-delay: 0.3s; }
        .product-item:nth-child(4) { animation-delay: 0.4s; }
        
        /* Enhanced sold count badge */
        .sold-count .badge {
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
            color: #047857 !important;
            border: 1px solid #a7f3d0 !important;
            font-weight: 600;
            padding: 0.4rem 0.75rem;
            transition: all 0.3s ease;
        }
        
        .sold-count .badge:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
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
        
        /* Collections Carousel */
        .collections-carousel {
            scroll-behavior: smooth;
            -ms-overflow-style: none;
            scrollbar-width: none;
            padding: 10px 0;
        }
        
        .collections-carousel::-webkit-scrollbar {
            display: none;
        }
        
        .collection-card {
            min-width: 220px;
            flex-shrink: 0;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }
        
        .collection-card:hover {
            transform: translateY(-8px);
        }
        
        .collection-image {
            width: 220px;
            height: 220px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            transition: all 0.4s ease;
            position: relative;
        }
        
        .collection-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, transparent 60%, rgba(0,0,0,0.4) 100%);
            z-index: 1;
            transition: opacity 0.3s ease;
        }
        
        .collection-card:hover .collection-image {
            box-shadow: 0 16px 48px rgba(37, 99, 235, 0.2);
        }
        
        .collection-card:hover .collection-image::before {
            opacity: 0.7;
        }
        
        .collection-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .collection-card:hover .collection-image img {
            transform: scale(1.1);
        }
        
        .collection-name {
            text-align: center;
            margin-top: 16px;
            font-weight: 600;
            color: #1e293b;
            font-size: 1.05rem;
            letter-spacing: 0.3px;
            transition: color 0.3s ease;
        }
        
        .collection-card:hover .collection-name {
            color: #2563eb;
        }
        
        /* Carousel Arrow Buttons */
        .carousel-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            color: #1e293b;
            font-size: 1.2rem;
        }
        
        .carousel-arrow:hover {
            background: #2563eb;
            color: white;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            transform: translateY(-50%) scale(1.1);
        }
        
        .carousel-arrow-left {
            left: -24px;
        }
        
        .carousel-arrow-right {
            right: -24px;
        }
        
        @media (max-width: 768px) {
            .carousel-arrow {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .carousel-arrow-left {
                left: 0;
            }
            
            .carousel-arrow-right {
                right: 0;
            }
        }
        
        /* Product Family Grouping Styles */
        .product-family-group {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 3rem;
            border: 2px solid #e5e7eb;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .product-family-group::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37, 99, 235, 0.08), transparent);
            transition: left 0.8s ease;
        }
        
        .product-family-group:hover::before {
            left: 100%;
        }
        
        .product-family-group:hover {
            box-shadow: 0 12px 40px rgba(37, 99, 235, 0.12);
            border-color: rgba(37, 99, 235, 0.4);
            transform: translateY(-4px);
        }
        
        .family-header {
            border-bottom: 3px solid;
            border-image: linear-gradient(90deg, var(--primary-color), var(--secondary-color)) 1;
            padding-bottom: 1.25rem;
            margin-bottom: 1.75rem;
            position: relative;
        }
        
        .family-title {
            color: #1e293b;
            font-weight: 700;
            font-size: 1.75rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .family-title::before {
            content: '✦';
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .family-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .product-card {
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            border-color: #2563eb;
        }
        
        .product-card .card-img-top {
            transition: transform 0.3s ease;
            border-radius: 12px 12px 0 0;
        }
        
        .product-card:hover .card-img-top {
            transform: scale(1.05);
        }
        
        .price-section {
            display: flex;
            flex-direction: column;
        }
        
        .price-section .price {
            font-size: 1.25rem;
            color: #2563eb;
        }
        
        /* Bulk Buyer Pricing Styles */
        .bulk-buyer-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #9ca3af;
            font-size: 0.9rem;
        }
        
        .bulk-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: #10b981;
        }
        
        .savings-badge {
            background-color: #fef3c7;
            color: #92400e;
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .action-buttons .btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            position: relative;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .action-buttons .btn:hover {
            transform: scale(1.15);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
        }
        
        .action-buttons .btn:active {
            transform: scale(0.95);
        }
        
        /* Make entire card clickable */
        .product-item > a {
            display: block;
            height: 100%;
            color: inherit;
        }
        
        .product-item > a:hover {
            color: inherit;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .shop-header {
                padding: 3rem 0 !important;
            }
            .shop-header h1 {
                font-size: 2rem !important;
            }
            .shop-header p {
                font-size: 1rem !important;
            }
            .filter-card {
                position: static !important;
                margin-bottom: 1rem;
            }
            .filter-card .card-body {
                padding: 1rem;
            }
            .filter-card .row {
                gap: 0.5rem;
            }
            .product-card {
                margin-bottom: 1.5rem;
            }
            .product-card img {
                height: 180px !important;
            }
            .product-card .card-body {
                padding: 0.75rem;
            }
            .product-card .card-title {
                font-size: 0.9rem;
            }
            .product-card .price {
                font-size: 1rem !important;
            }
            .navbar-brand span {
                font-size: 1.1rem !important;
            }
            #categoryFilter {
                min-width: 150px;
                font-size: 0.9rem;
            }
            .product-family-group {
                padding: 1rem;
            }
            .family-title {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 576px) {
            .shop-header {
                padding: 2rem 0 !important;
            }
            .shop-header h1 {
                font-size: 1.5rem !important;
            }
            .container {
                padding: 0 0.75rem;
            }
            .product-item {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .product-card img {
                height: 150px !important;
            }
            .product-card .card-body {
                padding: 0.5rem;
            }
            .product-card .card-title {
                font-size: 0.8rem;
                line-height: 1.2;
            }
            .product-card .price {
                font-size: 0.9rem !important;
            }
            .product-card .rating {
                font-size: 0.75rem;
            }
            .product-card .product-meta {
                font-size: 0.7rem;
            }
            .btn-add-cart, .product-card .btn {
                font-size: 0.75rem;
                padding: 0.4rem 0.6rem;
            }
            .navbar-brand span {
                font-size: 1rem !important;
            }
            #categoryFilter {
                min-width: 120px;
                font-size: 0.8rem;
            }
            .product-family-group {
                padding: 0.75rem;
                margin-bottom: 1.5rem;
            }
            .family-title {
                font-size: 1rem;
            }
            .family-meta .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.4rem;
            }
        }
    </style>
     <!-- swal -->
     <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
    

<body class="bg-light">
    

<?php $active = 'shop'; ?>
<?php include 'includes/navbar_customer.php'; ?>

    <!-- Shop Header -->
    <header class="shop-header">
        <!-- Animated Geometric Shapes -->
        <div style="position: absolute; top: 15%; left: 8%; width: 80px; height: 80px; background: rgba(251,191,36,0.3); border-radius: 50%; animation: float 6s ease-in-out infinite;"></div>
        <div style="position: absolute; top: 50%; right: 12%; width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 8s ease-in-out infinite 1s;"></div>
        <div style="position: absolute; bottom: 25%; left: 12%; width: 50px; height: 50px; background: rgba(251,191,36,0.4); transform: rotate(45deg); animation: float 7s ease-in-out infinite 2s;"></div>
        <div style="position: absolute; top: 25%; right: 15%; width: 100px; height: 100px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; animation: rotate 20s linear infinite;"></div>
        
        <div class="container shop-header-content" style="position: relative; z-index: 10;">
            <h1 class="display-3 fw-bold mb-3" style="animation: fadeInUp 1s ease-out;">Our Collections</h1>
            <p class="lead" style="font-size: 1.2rem; max-width: 600px; margin: 0 auto; animation: fadeInUp 1s ease-out 0.2s; animation-fill-mode: both;">Discover quality products for a cozier, more stylish space.</p>
            
            <!-- Search Bar -->
            <div class="mt-4" style="max-width: 600px; margin: 0 auto; animation: fadeInUp 1s ease-out 0.4s; animation-fill-mode: both;">
                <form method="GET" action="shop.php">
                    <div class="input-group input-group-lg">
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               placeholder="Search products..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                               style="border-radius: 50px 0 0 50px;">
                        <button class="btn btn-light" type="submit" style="border-radius: 0 50px 50px 0;">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </header>

    <?php
        // Capture filter values from GET
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $size = $_GET['size'] ?? '';
        $min_price = $_GET['min_price'] ?? '';
        $max_price = $_GET['max_price'] ?? '';
        $sort = $_GET['sort'] ?? 'name_asc';
        
        // Debug: Log filter values
        error_log("Size filter: " . $size);
        error_log("Min price: " . $min_price);
        error_log("Max price: " . $max_price);

        // Disable ONLY_FULL_GROUP_BY temporarily for this query
        $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
        
        // Base query with LEFT JOIN for average rating, materials, total sold, and variant sizes/prices
        $sql = "
            SELECT 
                p.*, 
                c.category_name, 
                IFNULL(AVG(ir.rating), 0) AS avg_rating,
                GROUP_CONCAT(DISTINCT m.material_name ORDER BY m.material_name SEPARATOR ', ') AS all_materials,
                IFNULL(SUM(oi.quantity), 0) AS total_sold,
                vs.sizes_pairs,
                vs.from_price,
                vs.total_variant_stock,
                vs.has_variants
            FROM products p
            JOIN product_category c ON p.category_id = c.category_id
            LEFT JOIN item_ratings ir ON ir.product_id = p.product_id
            LEFT JOIN product_materials pm ON p.product_id = pm.product_id
            LEFT JOIN materials m ON pm.material_id = m.material_id
            LEFT JOIN order_items oi ON p.product_id = oi.product_id
            LEFT JOIN (
              SELECT product_id,
                     MIN(CASE WHEN is_active = 1 AND stock > 0 THEN price END) AS from_price,
                     GROUP_CONCAT(CASE WHEN is_active = 1 AND stock > 0 THEN CONCAT(size,'::',stock) END SEPARATOR '||') AS sizes_pairs,
                     SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_variant_stock,
                     COUNT(*) AS has_variants
              FROM product_variants
              GROUP BY product_id
            ) vs ON vs.product_id = p.product_id
            LEFT JOIN (
              SELECT 1
            ) dummy ON 1=0
            WHERE 1=1
        ";

        // Filters
        if (!empty($search)) {
            $sql .= " AND p.product_name LIKE :search";
        }
        if (!empty($category)) {
            $sql .= " AND p.category_id = :category";
        }
        if (!empty($size)) {
            $sql .= " AND p.size LIKE :size";
        }
        if (!empty($min_price)) {
            $sql .= " AND p.price >= :min_price";
        }
        if (!empty($max_price)) {
            $sql .= " AND p.price <= :max_price";
        }

        // Group by all non-aggregated columns
        $sql .= " GROUP BY 
            p.product_id, p.product_name, p.price, p.bundle_price, 
            p.description, p.stock, p.category_id, 
            p.pieces_per_bundle, p.material, p.size, p.restock_alert, 
            p.image_url, c.category_name, vs.sizes_pairs, vs.from_price, vs.total_variant_stock, vs.has_variants
        ";

        // Sorting
        switch ($sort) {
            case 'name_desc':
                $sql .= " ORDER BY p.product_name DESC";
                break;
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name_asc':
            default:
                $sql .= " ORDER BY p.category_id ASC, p.product_name ASC";
                break;
        }

        // Prepare and execute
        try {
            $stmt = $pdo->prepare($sql);

            if (!empty($search)) {
                $stmt->bindValue(':search', '%' . $search . '%');
            }
            if (!empty($category)) {
                $stmt->bindValue(':category', $category);
            }
            if (!empty($size)) {
                $stmt->bindValue(':size', '%' . $size . '%');
            }
            if (!empty($min_price)) {
                $stmt->bindValue(':min_price', $min_price);
            }
            if (!empty($max_price)) {
                $stmt->bindValue(':max_price', $max_price);
            }

            $stmt->execute();
            $product_data = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            // Log error and show user-friendly message
            error_log("Shop query error: " . $e->getMessage());
            $product_data = [];
        }
        ?>


    <!-- Toggle Filter Button -->
    <button class="toggle-filter-btn" id="toggleFilterBtn" onclick="toggleFilterBar()">
        <i class="bi bi-funnel-fill"></i>
        <span class="btn-text">Hide Filters</span>
    </button>

    <div class="container py-5">
        <!-- Collections Carousel -->
        <div class="mb-5">
            <div class="text-center mb-4">
                <h2 class="fw-bold mb-2" style="color: #1e293b; font-size: 2rem;">Shop by Collection</h2>
                <p class="text-muted">Explore our curated collections</p>
            </div>
            <div class="position-relative">
                <!-- Left Arrow -->
                <button class="carousel-arrow carousel-arrow-left" id="scrollLeft" onclick="scrollCollections('left')">
                    <i class="bi bi-chevron-left"></i>
                </button>
                
                <!-- Right Arrow -->
                <button class="carousel-arrow carousel-arrow-right" id="scrollRight" onclick="scrollCollections('right')">
                    <i class="bi bi-chevron-right"></i>
                </button>
                
                <div class="collections-carousel d-flex gap-3 overflow-auto pb-3" id="collectionsCarousel">
                    <?php
                    // Get categories with a sample product image
                    $categories_stmt = $pdo->query("
                        SELECT c.category_id, c.category_name, 
                               (SELECT image_url FROM products WHERE category_id = c.category_id LIMIT 1) as sample_image
                        FROM product_category c
                        ORDER BY c.category_name
                    ");
                    $collections = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($collections as $collection):
                    ?>
                    <a href="shop.php?category=<?= $collection['category_id'] ?>" class="text-decoration-none">
                        <div class="collection-card">
                            <div class="collection-image">
                                <img src="uploads/products/<?= $collection['sample_image'] ?? 'default.jpg' ?>" 
                                     alt="<?= $collection['category_name'] ?>">
                            </div>
                            <div class="collection-name"><?= $collection['category_name'] ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="mb-0">Our Products</h1>
                </div>
                
                <!-- Simple Sort & Filter Bar -->
                <div class="p-3 bg-white rounded-3 shadow-sm sticky-filter-bar">
                    <form method="GET" class="d-flex gap-3 align-items-center flex-wrap justify-content-between">
                        <!-- Sort By -->
                        <div class="d-flex align-items-center gap-2">
                            <label class="text-muted mb-0" style="font-size: 0.95rem; font-weight: 500;">Sort:</label>
                            <select name="sort" class="form-select" style="width: auto; min-width: 180px; padding: 0.5rem 0.75rem; font-size: 0.95rem;">
                                <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                                <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <!-- Size Filter -->
                        <div class="d-flex align-items-center gap-2">
                            <label class="text-muted mb-0" style="font-size: 0.95rem; font-weight: 500;">Size:</label>
                            <select name="size" class="form-select" style="width: auto; min-width: 140px; max-width: 200px; padding: 0.5rem 0.75rem; font-size: 0.95rem;">
                                <option value="">All Sizes</option>
                                <?php foreach ($available_sizes as $size): ?>
                                    <?php 
                                        // Truncate long size names for display
                                        $display_size = strlen($size) > 20 ? substr($size, 0, 20) . '...' : $size;
                                    ?>
                                    <option value="<?= htmlspecialchars($size) ?>" 
                                            <?= ($_GET['size'] ?? '') === $size ? 'selected' : '' ?>
                                            title="<?= htmlspecialchars($size) ?>">
                                        <?= htmlspecialchars($display_size) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <label class="text-muted mb-0 text-nowrap" style="font-size: 0.95rem; font-weight: 500;">Price:</label>
                            <input type="number" name="min_price" class="form-control" style="max-width: 110px; padding: 0.5rem 0.75rem; font-size: 0.95rem;" placeholder="Min" value="<?= htmlspecialchars($min_price) ?>">
                            <span class="text-muted">-</span>
                            <input type="number" name="max_price" class="form-control" style="max-width: 110px; padding: 0.5rem 0.75rem; font-size: 0.95rem;" placeholder="Max" value="<?= htmlspecialchars($max_price) ?>">
                            <button type="submit" class="btn btn-primary text-nowrap" style="padding: 0.5rem 1rem; font-size: 0.95rem;">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                        </div>
                        
                        <!-- Clear Filters -->
                        <?php if (!empty($search) || !empty($category) || !empty($size) || !empty($min_price) || !empty($max_price)): ?>
                        <a href="shop.php" class="btn btn-outline-secondary text-nowrap" style="padding: 0.5rem 1rem; font-size: 0.95rem;">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                        <?php endif; ?>
                        
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                        <!-- Size is already in the dropdown, no need for hidden input -->
                    </form>
                    
                </div>
            </div>

            <!-- Products -->
            <div class="col-lg-12">
                <?php
                    function renderStars($rating) {
                        $fullStars = floor($rating);
                        $halfStar = ($rating - $fullStars >= 0.25 && $rating - $fullStars <= 0.75);
                        $result = '';

                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= $fullStars) {
                                $result .= '<i class="bi bi-star-fill"></i>';
                            } elseif ($halfStar && $i == $fullStars + 1) {
                                $result .= '<i class="bi bi-star-half"></i>';
                            } else {
                                $result .= '<i class="bi bi-star"></i>';
                            }
                        }

                        return $result;
                    }
                    
                    // Group products by category instead of product name
                    function groupProductsByCategory($products) {
                        $grouped = [];
                        
                        foreach ($products as $product) {
                            $categoryName = $product['category_name'];
                            $categoryId = $product['category_id'];
                            $materials = $product['all_materials'] ?? $product['material'] ?? '';
                            
                            if (!isset($grouped[$categoryId])) {
                                $grouped[$categoryId] = [
                                    'category_id' => $categoryId,
                                    'category_name' => $categoryName,
                                    'materials' => [],
                                    'products' => []
                                ];
                            }
                            
                            // Collect materials for this category
                            if (!empty($materials)) {
                                $materialList = array_map('trim', explode(',', $materials));
                                foreach ($materialList as $material) {
                                    if (!empty($material) && !in_array($material, $grouped[$categoryId]['materials'])) {
                                        $grouped[$categoryId]['materials'][] = $material;
                                    }
                                }
                            }
                            
                            $grouped[$categoryId]['products'][] = $product;
                        }
                        
                        return $grouped;
                    }
                    
                    $groupedProducts = groupProductsByCategory($product_data);
                ?>
                
                <?php if (!empty($product_data) && count($product_data) > 0): ?>
                    <?php foreach ($groupedProducts as $group): ?>
                        <!-- Product Category Group -->
                        <div class="product-family-group mb-5">
                            <div class="family-header mb-3">
                                <h4 class="family-title">
                                    <i class="bi bi-folder me-2"></i>
                                    <?php echo htmlspecialchars($group['category_name']); ?>
                                </h4>
                                <div class="family-meta">
                                    <span class="badge bg-primary me-2"><?php echo count($group['products']); ?> products</span>
                                    <?php if (!empty($group['materials'])): ?>
                                        <?php foreach ($group['materials'] as $material): ?>
                                            <span class="badge bg-info me-1"><?php echo htmlspecialchars($material); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <span class="text-muted ms-2">Browse all <?php echo strtolower($group['category_name']); ?> designs</span>
                                </div>
                            </div>
                            
                            
            
            <div class="row g-4">
                <?php foreach ($group['products'] as $row): ?>
                    <div class="col-6 col-md-4 col-lg-3 product-item">
                        <a href="product.php?id=<?php echo $row['product_id'] ?>" class="text-decoration-none">
                            <div class="card h-100 product-card shadow-sm">
                                <div class="position-relative">
                                    <img src="uploads/products/<?php echo $row['image_url'] ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo $row['product_name'] ?>" 
                                         style="height: 250px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0 p-2 d-flex flex-column gap-1">
                                    <?php 
                                    // Check if product is new (added in last 7 days)
                                    $created_date = strtotime($row['created_at'] ?? '');
                                    $is_new = (time() - $created_date) < (7 * 24 * 60 * 60);
                                    
                                    // Check if best seller (sold more than 10 units)
                                    $is_best_seller = ($row['total_sold'] ?? 0) >= 10;
                                    ?>
                                    
                                    <?php if ($is_new): ?>
                                        <span class="badge bg-success">NEW</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_best_seller): ?>
                                        <span class="badge bg-primary">BEST SELLER</span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // For products with variants, check total_variant_stock; otherwise check product stock
                                    $effective_stock = $row['has_variants'] > 0 ? ($row['total_variant_stock'] ?? 0) : $row['stock'];
                                    ?>
                                    <?php if ($effective_stock <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($effective_stock > 0 && $effective_stock <= $row['restock_alert']): ?>
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    <?php endif; ?>
                                </div>
                                <?php /* Removed single-size overlay to avoid confusion with variants */ ?>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title mb-2 text-dark">
                                    <?php echo $row['product_name'] ?>
                                </h6>

                                <?php if (!empty($row['sizes_pairs'])): ?>
                                    <div class="mb-2">
                                        <?php 
                                            $pairs = explode('||', $row['sizes_pairs']);
                                            $count = 0;
                                            foreach ($pairs as $pair) {
                                                if (!$pair) continue;
                                                list($sz,$st) = array_pad(explode('::', $pair), 2, '');
                                                $st = (int)$st;
                                                $badge = $st<=0 ? 'bg-danger' : ($st <= (int)$row['restock_alert'] ? 'bg-warning text-dark' : 'bg-success');
                                                echo '<span class="badge '.$badge.' me-1 mb-1">'.htmlspecialchars($sz).' ('.$st.')</span>';
                                                $count++;
                                                if ($count>=10) { echo '<span class="badge bg-secondary">+'.(count($pairs)-$count).' more</span>'; break; }
                                            }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                
                                
                                <div class="rating mb-2">
                                    <span class="text-warning small">
                                        <?php echo renderStars($row['avg_rating']); ?>
                                    </span>
                                    <small class="text-muted">(<?php echo number_format($row['avg_rating'], 1); ?>)</small>
                                </div>
                                
                                <!-- Sold Count Indicator -->
                                <div class="sold-count mb-2">
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        <i class="bi bi-bag-check-fill me-1"></i>
                                        <?php echo number_format($row['total_sold']); ?> sold
                                    </span>
                                </div>
                                
                                <!-- Stock Urgency Message -->
                                <?php if ($effective_stock > 0 && $effective_stock <= $row['restock_alert']): ?>
                                    <div class="mb-2">
                                        <small class="text-warning">
                                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                                            <strong>Only <?php echo $effective_stock; ?> left!</strong> Order soon
                                        </small>
                                    </div>
                                <?php elseif ($effective_stock > 0): ?>
                                    <div class="mb-2">
                                        <small class="text-success">
                                            <i class="bi bi-check-circle-fill me-1"></i>
                                            In Stock
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="price-section">
                                    <?php 
                                        $basePrice = isset($row['from_price']) && $row['from_price'] !== null ? (float)$row['from_price'] : (float)$row['price'];
                                    ?>
                                    <?php if ($is_bulk_buyer && $user_discount > 0): ?>
                                        <!-- Wholesaler Pricing -->
                                        <span class="bulk-buyer-badge">
                                            <i class="bi bi-star-fill"></i> WHOLESALER
                                        </span>
                                        <div class="original-price">₱<?php echo number_format($basePrice, 2) ?></div>
                                        <?php 
                                            $discounted_price = $basePrice * (1 - ($user_discount / 100));
                                            $savings = $basePrice - $discounted_price;
                                        ?>
                                        <div class="bulk-price">₱<?php echo number_format($discounted_price, 2) ?></div>
                                        <span class="savings-badge">Save ₱<?php echo number_format($savings, 2) ?></span>
                                    <?php else: ?>
                                        <!-- Regular Pricing -->
                                        <div class="price fw-bold text-primary">₱<?php echo number_format($basePrice, 2) ?></div>
                                        <?php if (isset($row['bundle_price']) && $row['bundle_price'] < $basePrice): ?>
                                            <small class="text-muted">Bundle: ₱<?php echo number_format($row['bundle_price'], 2) ?></small>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                    <div class="action-buttons d-flex gap-2">
                                        <!-- Wishlist Button -->
                                        <button onclick="toggleWishlist(<?= $row['product_id'] ?>, event)" 
                                                class="btn btn-outline-danger btn-sm wishlist-btn" 
                                                data-product-id="<?= $row['product_id'] ?>"
                                                title="Add to Wishlist">
                                        <i class="bi bi-heart"></i>
                                        </button>
                                        
                                        <!-- Add to Cart Button -->
                                        <?php if ($effective_stock > 0): ?>
                                            <button onclick="handleAddOrGo(<?= $row['product_id'] ?>, '<?= isset($row['sizes_pairs']) ? htmlspecialchars($row['sizes_pairs'], ENT_QUOTES) : '' ?>', event)" 
                                                    class="btn btn-primary btn-sm" 
                                                    title="Add to Cart">
                                            <i class="bi bi-cart-plus"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled title="Out of Stock">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-12">
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle me-2"></i>
            No products found matching your filters.
        </div>
    </div>
<?php endif; ?>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- <script src="assets/js/script.js"></script> -->

<?php 
    if(isset($_SESSION['user_id'])):
        $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = :user_id");
        $stmt->execute(["user_id" => $_SESSION['user_id']]);
        $is_verified = $stmt->fetchColumn();
        if(!$is_verified):
?>
    <script>
        Swal.fire({
            title: 'You are not verified yet',
            text: 'Please verify your email address.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Verify',
            cancelButtonText: 'Go Back'
            }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'verify_email.php';
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                window.history.back();
            }
        });
    </script>
<?php endif; endif; ?>

<script>
    // Collections Carousel Scroll Function
    function scrollCollections(direction) {
        const carousel = document.getElementById('collectionsCarousel');
        const scrollAmount = 250; // Scroll by one card width + gap
        
        if (direction === 'left') {
            carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else {
            carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }
    
    // Function to update cart count in navbar
    function updateCartCount() {
        fetch('backend/get_cart_count.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const cartLink = document.querySelector('a[href="cart.php"]');
                if (!cartLink) return;
                const existing = cartLink.querySelector('.badge');
                if (existing) existing.remove();
                if (data.count > 0) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary rounded-pill';
                    badge.id = 'cart-count';
                    badge.textContent = data.count;
                    cartLink.appendChild(badge);
                }
            })
            .catch(()=>{});
    }

    // Add to cart (no variants)
    function addToCart(productId, event) {
        if (event) { event.preventDefault(); event.stopPropagation(); }
        
        // Check if user is a wholesaler (usertype_id = 3)
        const isWholesaler = <?php echo (isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 3) ? 'true' : 'false'; ?>;
        const minQuantity = isWholesaler ? 20 : 1;
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'backend/add_to_cart.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('action=add_to_cart&product_id=' + encodeURIComponent(productId) + '&quantity=' + minQuantity);
        xhr.onload = function () {
            if (xhr.status !== 200) {
                Swal.fire({ title:'Error!', text:'There was an issue adding the product to the cart.', icon:'error' });
                return;
            }
            const response = xhr.responseText.trim();
            if (response === 'not_logged_in') {
                Swal.fire({ title:'Please Log In', text:'You need to log in to add items to your cart.', icon:'warning', confirmButtonText:'Go to Login' })
                    .then(res=>{ if(res.isConfirmed) window.location.href = 'login.php?destination=shop.php'; });
                return;
            }
            if (response === 'not_verified') {
                Swal.fire({ title:'Email Not Verified', text:'You need to verify your email address before adding items to your cart.', icon:'warning', showCancelButton:true, confirmButtonText:'Verify', cancelButtonText:'Go Back' })
                    .then(res=>{ 
                        if(res.isConfirmed) {
                            window.location.href = 'verify_email.php';
                        } else {
                            window.history.replaceState(null, '', 'index.php');
                            window.location.href = 'index.php';
                        }
                    });
                return;
            }
            if (response === 'out_of_stock') { 
                const message = isWholesaler 
                    ? 'Sorry, the product does not have enough stock for the minimum wholesale quantity of 20.' 
                    : 'Sorry, the product is out of stock.';
                Swal.fire({ 
                    title: 'Out of Stock', 
                    text: message, 
                    icon: 'error',
                    footer: isWholesaler ? 'Please contact us for bulk orders with special requirements.' : ''
                }); 
                return; 
            }
            if (response === 'minimum_quantity_not_met') { 
                Swal.fire({ 
                    title: 'Minimum Quantity Required', 
                    text: 'Wholesale orders require a minimum of 20 items per product. Please increase the quantity to 20 or more.', 
                    icon: 'warning',
                    footer: 'Bulk buyers must order at least 20 units of each product.'
                }); 
                return; 
            }
            if (response === 'insufficient_stock_for_wholesale') { 
                Swal.fire({ 
                    title: 'Insufficient Stock for Wholesale', 
                    text: 'This product cannot be added as it does not meet the minimum quantity requirement of 20 items for wholesale checkout.', 
                    icon: 'warning',
                    footer: 'Wholesale orders require a minimum of 20 items per product.'
                }); 
                return; 
            }
            if (response === 'cart_limit_reached') { Swal.fire({ title:'Cart Limit Reached', text:'You cannot have more than 50 products in your cart. Please remove some items before adding new ones.', icon:'warning' }); return; }
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    updateCartCount();
                    const successMessage = isWholesaler 
                        ? 'Product added to cart! Remember: Minimum 20 items required for wholesale checkout.' 
                        : 'Product added to cart!';
                    Swal.fire({ title:'Product Added to Cart', text:successMessage, icon:'success', timer:3000, showConfirmButton:false });
                } else if (data.status === 'exceeds_stock') {
                    const sizeInfo = data.variant_size ? `<p style="margin-bottom: 1rem;"><i class="bi bi-rulers"></i> <strong>Size: ${data.variant_size}</strong></p>` : '';
                
                    if (isWholesaler && data.stock < 20) {
                        // Special message for wholesalers when stock is less than minimum
                        Swal.fire({ 
                            title: 'Insufficient Stock for Wholesale', 
                            html: `<div style="text-align:left;">${sizeInfo}<p><strong>This product cannot be added as it does not meet the minimum quantity requirement of 20 items for wholesale checkout.</strong></p><hr><p><i class="bi bi-box-seam"></i> Total stock available: <strong>${data.stock}</strong></p><p><i class="bi bi-info-circle"></i> Wholesale orders require a minimum of 20 items per product.</p></div>`, 
                            icon:'warning'
                        });
                    } else {
                        // Regular stock limit message
                        Swal.fire({ 
                            title: 'Cannot Add More', 
                            html:`<div style="text-align:left;">${sizeInfo}<p><strong>${data.message}</strong></p><hr><p><i class="bi bi-cart-fill"></i> Current in cart: <strong>${data.current_cart_qty}</strong></p><p><i class="bi bi-box-seam"></i> Total stock: <strong>${data.stock}</strong></p><p><i class="bi bi-plus-circle"></i> Can still add: <strong>${data.remaining}</strong></p></div>`, 
                            icon:'info' 
                        });
                    }
                } else if (data.status === 'cart_full') {
                    const sizeInfo = data.variant_size ? `<p style="margin-bottom: 1rem;"><i class="bi bi-rulers"></i> <strong>Size: ${data.variant_size}</strong></p>` : '';
                    Swal.fire({ title:'Cart Already Full', html:`<div style=\"text-align:left;\">${sizeInfo}<p><strong>${data.message}</strong></p><hr><p><i class=\"bi bi-cart-fill\"></i> Current in cart: <strong>${data.current_cart_qty}</strong></p><p><i class=\"bi bi-box-seam\"></i> Total stock: <strong>${data.stock}</strong></p></div>`, icon:'warning' });
                } else {
                    Swal.fire({ title:'Notice', text:response, icon:'info' });
                }
            } catch(e) {
                updateCartCount();
                Swal.fire({ title:'Product Added to Cart', text:response, icon:'success', timer:2000, showConfirmButton:false });
            }
        };
        xhr.onerror = function(){ Swal.fire({ title:'Network Error', text:'There was an issue with the request.', icon:'error' }); };
    }

    // Redirect to product page if variants are present
    function handleAddOrGo(productId, sizesPairs, event){
        if (event) { event.preventDefault(); event.stopPropagation(); }
        if (sizesPairs && sizesPairs.indexOf('::') !== -1){
            window.location = 'product.php?id=' + encodeURIComponent(productId);
        } else {
            addToCart(productId, event);
        }
    }
        
        // Wishlist functionality using database
        function toggleWishlist(productId, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('product_id', productId);
            
            fetch('backend/wishlist_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    if (data.message === 'Please log in') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Please Log In',
                            text: 'You need to log in to use wishlist',
                            confirmButtonText: 'Go to Login'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'login.php';
                            }
                        });
                    }
                    return;
                }
                
                if (data.action === 'added') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Added to Wishlist',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'info',
                        title: 'Removed from Wishlist',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
                
                updateWishlistIcons();
                
                // Update navbar count if function exists
                if (typeof updateWishlistCount === 'function') {
                    updateWishlistCount();
                }
            })
            .catch(error => {
                console.error('Wishlist error:', error);
            });
        }
        
        function updateWishlistIcons() {
            // Fetch wishlist product IDs from database
            fetch('backend/wishlist_api.php?action=get_ids')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const wishlist = data.product_ids;
                    document.querySelectorAll('.wishlist-btn').forEach(btn => {
                        const productId = parseInt(btn.getAttribute('data-product-id'));
                        const icon = btn.querySelector('i');
                        if (wishlist.includes(productId)) {
                            icon.classList.remove('bi-heart');
                            icon.classList.add('bi-heart-fill');
                            btn.classList.add('text-danger');
                        } else {
                            icon.classList.remove('bi-heart-fill');
                            icon.classList.add('bi-heart');
                            btn.classList.remove('text-danger');
                        }
                    });
                }
            })
            .catch(error => console.error('Error updating wishlist icons:', error));
        }
        
        // Update wishlist icons on page load
        document.addEventListener('DOMContentLoaded', updateWishlistIcons);
        
        // Toggle Filter Bar Function
        function toggleFilterBar() {
            const filterBar = document.querySelector('.sticky-filter-bar');
            const toggleBtn = document.getElementById('toggleFilterBtn');
            const icon = toggleBtn.querySelector('i');
            const text = toggleBtn.querySelector('.btn-text');
            
            filterBar.classList.toggle('hidden');
            
            // Change icon and text
            if(filterBar.classList.contains('hidden')) {
                icon.className = 'bi bi-funnel';
                text.textContent = 'Show Filters';
                toggleBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
            } else {
                icon.className = 'bi bi-funnel-fill';
                text.textContent = 'Hide Filters';
                toggleBtn.style.background = 'linear-gradient(135deg, #2563eb, #3b82f6)';
            }
        }
    </script>
</body>

</html>