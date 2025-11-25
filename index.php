<?php 
    require ('config/db.php');
    session_start();
    require('config/session_disallow_courier.php');

    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    
    // Get user discount rate for wholesalers
    $user_discount = 0;
    $is_bulk_buyer = false;
    if(isset($_SESSION['user_id'])){
        $user_stmt = $pdo->prepare("SELECT usertype_id, discount_rate FROM users WHERE user_id = :user_id");
        $user_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user_info = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user_info && $user_info['usertype_id'] == 3){
            $is_bulk_buyer = true;
            $user_discount = $user_info['discount_rate'];
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Quality Home Textiles - SAPIN</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
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
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, var(--secondary-color) 60%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-hero {
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
        }

        .text-primary {
            color: var(--primary-color) !important;
        }

        .bg-primary {
            background-color: var(--primary-color) !important;
        }

        .card {
            border-radius: 1rem;
            border: 1.5px solid var(--border-color);
            box-shadow: 0 4px 24px 0 rgba(37,99,235,0.1);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(37,99,235,0.2);
        }
        
        /* Enhanced Feature Cards */
        .feature-card {
            background: white;
            padding: 2.5rem 1.5rem;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e5e7eb;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 16px 48px rgba(37, 99, 235, 0.15);
            border-color: var(--primary-color);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        /* Section Titles */
        .section-title {
            position: relative;
            display: inline-block;
        }
        
        .section-divider {
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            margin: 1rem auto;
            border-radius: 2px;
            animation: expand 0.8s ease-out;
        }
        
        @keyframes expand {
            from { width: 0; }
            to { width: 100px; }
        }
        
        /* Product Cards Enhancement */
        .product-card-enhanced {
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .product-card-enhanced:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 60px rgba(37, 99, 235, 0.2);
        }
        
        .product-card-enhanced img {
            transition: transform 0.6s ease;
        }
        
        .product-card-enhanced:hover img {
            transform: scale(1.1);
        }
        
        /* Stats Section */
        .stats-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.15);
        }
        
        .stats-number {
            font-size: 3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
    <style>
        .hero-section { 
            background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 25%, #3b82f6 50%, #f59e0b 75%, #fbbf24 100%);
            background-size: 300% 300%;
            animation: gradientShift 12s ease infinite;
            background-position: center;
            color: white;
            text-align: center;
            padding: 8rem 0;
            min-height: 80vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .bg-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.25;
            background-image: 
                linear-gradient(45deg, rgba(30, 58, 138, 0.15) 25%, transparent 25%, transparent 50%, rgba(30, 58, 138, 0.15) 50%, rgba(30, 58, 138, 0.15) 75%, transparent 75%, transparent),
                linear-gradient(-45deg, rgba(30, 58, 138, 0.15) 25%, transparent 25%, transparent 50%, rgba(30, 58, 138, 0.15) 50%, rgba(30, 58, 138, 0.15) 75%, transparent 75%, transparent);
            background-size: 40px 40px;
            animation: patternMove 30s linear infinite;
        }
        
        .geometric-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            border-radius: 50%;
            filter: blur(20px);
            animation: float 15s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 350px;
            height: 350px;
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            top: -100px;
            right: -50px;
            opacity: 0.4;
            filter: blur(25px);
            animation: float 20s ease-in-out infinite;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            bottom: -50px;
            left: -50px;
            animation-delay: -5s;
        }
        
        .shape-3 {
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, #1d4ed8, #60a5fa);
            top: 40%;
            left: 20%;
            opacity: 0.3;
            filter: blur(20px);
            animation-delay: -8s;
            animation-duration: 25s;
        }
        
        .shape-4 {
            width: 150px;
            height: 150px;
            background: linear-gradient(45deg, #1e40af, #3b82f6);
            bottom: 10%;
            right: 15%;
            opacity: 0.35;
            filter: blur(25px);
            animation-delay: -5s;
            animation-duration: 20s;
        }
        
        /* Additional blue accent elements */
        .blue-accent {
            position: absolute;
            background: linear-gradient(90deg, rgba(30, 58, 138, 0.4), rgba(59, 130, 246, 0.4));
            border-radius: 50%;
            filter: blur(15px);
            z-index: 0;
        }
        
        .accent-1 {
            width: 400px;
            height: 400px;
            top: -200px;
            right: -100px;
            opacity: 0.3;
            animation: pulse 15s ease-in-out infinite alternate;
        }
        
        .accent-2 {
            width: 600px;
            height: 600px;
            bottom: -300px;
            left: -200px;
            opacity: 0.2;
            animation: pulse 20s ease-in-out infinite alternate-reverse;
        }
        
        .accent-3 {
            width: 500px;
            height: 500px;
            top: 30%;
            right: -150px;
            opacity: 0.25;
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.5), rgba(251, 191, 36, 0.5));
            filter: blur(40px);
            animation: pulse 25s ease-in-out infinite alternate;
            z-index: 0;
        }
        
        /* Additional floating elements */
        .floating-dots {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .dot {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            animation: floatDot var(--duration) ease-in-out infinite;
            animation-delay: var(--delay);
            opacity: 0.6;
        }
        
        .dot.blue {
            background: rgba(59, 130, 246, 0.4);
            box-shadow: 0 0 15px 5px rgba(59, 130, 246, 0.2);
        }
        
        .dot.yellow {
            background: rgba(245, 158, 11, 0.4);
            box-shadow: 0 0 15px 5px rgba(245, 158, 11, 0.2);
        }
        
        @keyframes floatDot {
            0%, 100% {
                transform: translate(0, 0) scale(1);
                opacity: 0.4;
            }
            50% {
                transform: translate(20px, -20px) scale(1.2);
                opacity: 0.8;
            }
        }
        
        
        @keyframes patternMove {
            0% { background-position: 0 0; }
            100% { background-position: 100px 100px; }
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
            }
            25% {
                transform: translate(20px, 20px) rotate(5deg);
            }
            50% {
                transform: translate(0, 40px) rotate(0deg);
            }
            75% {
                transform: translate(-20px, 20px) rotate(-5deg);
            }
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Floating Fabric/Bedsheet Elements */
        .fabric-wave {
            position: absolute;
            width: 100%;
            height: 200px;
            opacity: 0.1;
            will-change: transform;
        }
        
        .fabric-wave-1 {
            bottom: 0;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
            clip-path: ellipse(80% 50% at 50% 100%);
            animation: wave 8s ease-in-out infinite;
            opacity: 0.3;
        }
        
        .fabric-wave-2 {
            bottom: 50px;
            left: 0;
            background: linear-gradient(90deg, transparent, rgba(251,191,36,0.5), transparent);
            clip-path: ellipse(70% 40% at 50% 100%);
            animation: wave 10s ease-in-out infinite reverse;
            opacity: 0.3;
        }
        
        @keyframes wave {
            0%, 100% { transform: translateX(-20%) scaleY(1); }
            50% { transform: translateX(20%) scaleY(1.3); }
        }
        
        /* Scroll Reveal Animation */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(50px);
            transition: all 0.8s ease-out;
        }
        
        .scroll-reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Floating Stars/Sparkles (representing comfort/quality) */
        .sparkle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(251, 191, 36, 0.8);
            border-radius: 50%;
            animation: twinkle linear infinite;
            box-shadow: 0 0 10px rgba(251, 191, 36, 0.5);
        }
        
        .sparkle-1 {
            top: 20%;
            left: 15%;
            animation-duration: 3s;
        }
        
        .sparkle-2 {
            top: 40%;
            left: 85%;
            animation-duration: 4s;
            animation-delay: 1s;
        }
        
        .sparkle-3 {
            top: 60%;
            left: 25%;
            animation-duration: 5s;
            animation-delay: 2s;
        }
        
        .sparkle-4 {
            top: 30%;
            left: 70%;
            animation-duration: 3.5s;
            animation-delay: 0.5s;
        }
        
        @keyframes twinkle {
            0%, 100% { 
                opacity: 0;
                transform: scale(0);
            }
            50% { 
                opacity: 1;
                transform: scale(1.5);
            }
        }
        
        /* Decorative Circles (representing comfort bubbles) */
        .comfort-circle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float-up 20s linear infinite;
        }
        
        .circle-1 {
            width: 100px;
            height: 100px;
            bottom: -100px;
            left: 10%;
            animation-duration: 15s;
        }
        
        .circle-2 {
            width: 150px;
            height: 150px;
            bottom: -150px;
            left: 60%;
            animation-duration: 20s;
            animation-delay: 5s;
        }
        
        .circle-3 {
            width: 80px;
            height: 80px;
            bottom: -80px;
            left: 85%;
            animation-duration: 18s;
            animation-delay: 10s;
        }
        
        @keyframes float-up {
            0% {
                transform: translateY(0) scale(1);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-100vh) scale(1.5);
                opacity: 0;
            }
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }
        
        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to top, rgba(0,0,0,0.1), transparent);
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Pulsing Button Effect */
        .btn-hero {
            animation: pulse-btn 2s ease-in-out infinite;
        }
        
        @keyframes pulse-btn {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); }
            50% { transform: scale(1.05); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3); }
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            animation: fadeInUp 1s ease-out;
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
        
        .feature-card {
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid #e5e7eb;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.08);
            border-radius: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(37,99,235,0.08), transparent);
            transition: left 0.6s ease;
        }
        
        .feature-card::after {
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
        
        .feature-card:hover::before {
            left: 100%;
        }
        
        .feature-card:hover::after {
            transform: scaleX(1);
        }
        
        .feature-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 16px 32px rgba(37,99,235,0.15);
            border-color: rgba(37,99,235,0.3);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            color: var(--secondary-color);
        }
        
        .product-card {
            border: 2px solid #e5e7eb;
            border-radius: 1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 16px rgba(37,99,235,0.08);
            overflow: hidden;
            position: relative;
            background: white;
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
            transform: translateY(-10px);
            box-shadow: 0 16px 32px rgba(37,99,235,0.15);
            border-color: rgba(37,99,235,0.3);
        }
        
        .product-card img {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .product-card:hover img {
            transform: scale(1.08);
        }
        
        .testimonial-card {
            background: rgba(255,255,255,0.9);
            border-radius: 1rem;
            padding: 2rem;
            margin: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(37,99,235,0.1);
        }
        
        .swiper-container {
            padding: 2rem 0;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: pulse 5s ease-in-out infinite reverse;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.5; }
            50% { transform: scale(1.15) rotate(5deg); opacity: 0.3; }
        }
    </style>
     <style>
        .text-warning i {
            color: #ffc107;
            font-size: 1.1rem;
            vertical-align: middle;
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
            color: var(--primary-color) !important;
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
            .hero-section {
                height: 60vh;
                padding: 2rem 0;
            }
            .hero-section h1 {
                font-size: 2rem !important;
            }
            .hero-section p {
                font-size: 1rem !important;
            }
            .feature-card {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            .feature-icon {
                font-size: 2rem;
            }
            .navbar-brand span {
                font-size: 1.1rem !important;
            }
            .custom-navbar .nav-link {
                padding: 0.5rem 0.8rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .hero-section {
                height: 50vh;
                padding: 1rem 0;
            }
            .hero-section h1 {
                font-size: 1.5rem !important;
            }
            .hero-section p {
                font-size: 0.9rem !important;
            }
            .feature-card {
                padding: 1rem;
            }
            .feature-icon {
                font-size: 1.8rem;
            }
            .cta-section {
                padding: 2rem 0 !important;
            }
            .cta-section h2 {
                font-size: 1.5rem !important;
            }
            .container {
                padding: 0 1rem;
            }
            .navbar-brand span {
                font-size: 1rem !important;
            }
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">

    <!-- swal -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <?php $active = 'index'; ?>
    <?php include 'includes/navbar_customer.php'; ?>

    <!-- Hero Section -->
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
            --light: #eff6ff;
            --white: #ffffff;
        }
        
        .hero-section {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            text-align: center;
            overflow: hidden;
            padding: 4rem 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 20;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .hero-logo {
            width: 160px;
            height: 160px;
            margin: 0 auto 1.5rem;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #f59e0b;
            padding: 3px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 10px 30px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hero-logo:hover {
            transform: scale(1.05) translateY(-5px);
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25);
        }
        
        .hero-title {
            font-size: 5.5rem;
            font-weight: 800;
            margin-bottom: 2rem;
            line-height: 1.1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            letter-spacing: -1px;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(90deg, #ffffff, #f0f9ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.1rem;
            max-width: 800px;
            margin: 0 auto 2.5rem;
            line-height: 1.5;
            color: #ffffff;
            font-weight: 300;
            letter-spacing: 0.3px;
            text-shadow: 0 1px 8px rgba(0, 0, 0, 0.3);
            font-family: 'Montserrat', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            position: relative;
            padding: 0 2rem;
            text-align: center;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .hero-heading {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0 0 1rem 0;
            text-transform: none;
            letter-spacing: 0;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.2;
            position: relative;
            padding: 0;
            white-space: nowrap;
        }
        
        .hero-subtitle::before,
        .hero-subtitle::after {
            content: '';
            position: absolute;
            top: 50%;
            height: 2px;
            width: 50px;
            background: linear-gradient(90deg, rgba(255,255,255,0.5), transparent);
        }
        
        .hero-subtitle::before {
            left: 0;
        }
        
        .hero-subtitle::after {
            right: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5));
        }
        
        
        .hero-buttons {
            display: flex;
            gap: 1.25rem;
            justify-content: center;
            margin-top: 2.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            z-index: 1;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary) 0%, var(--primary-light) 50%, #f59e0b 100%);
            background-size: 200% auto;
            color: white;
            border: none;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #3b82f6 0%, #60a5fa 50%, #f59e0b 100%);
            background-size: 200% auto;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
            background-position: right center;
        }
        
        .btn-primary:active {
            transform: translateY(0) scale(0.98);
        }
        
        .btn-outline {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
            position: relative;
            overflow: hidden;
            z-index: 1;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .btn-outline::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.2));
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: -1;
        }
        
        .btn-outline:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
        }
        
        .btn-outline:hover::before {
            width: 100%;
        }
        
        .btn-outline:active {
            transform: translateY(0);
        }
        
        .btn i {
            margin-right: 8px;
            font-size: 1.1em;
        }
        
        .scroll-down {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            color: var(--white);
            text-decoration: none;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .scroll-down:hover {
            opacity: 1;
            transform: translateX(-50%) translateY(3px);
        }
        
        .scroll-down i {
            font-size: 1.5rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-8px); }
            60% { transform: translateY(-4px); }
        }
        
        /* Animated Floating Elements */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .floating-element {
            position: absolute;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.6);
            box-shadow: 0 0 25px 8px rgba(29, 78, 216, 0.4);
            animation: float var(--duration, 20s) ease-in-out infinite, pulse 3s ease-in-out infinite alternate;
            animation-delay: var(--delay, 0s);
            width: var(--size, 30px);
            height: var(--size, 30px);
            left: var(--x, 50%);
            top: var(--y, 50%);
            z-index: 1;
            will-change: transform, opacity;
            transform: translateZ(0);
        }
        
        .yellow-float {
            background: rgba(245, 158, 11, 0.7);
            box-shadow: 0 0 30px 10px rgba(245, 158, 11, 0.5);
            animation: float var(--duration, 20s) ease-in-out infinite, pulse 4s ease-in-out infinite alternate-reverse;
        }
        
        .accent-float {
            background: linear-gradient(45deg, rgba(29, 78, 216, 0.3), rgba(245, 158, 11, 0.3));
            box-shadow: 0 0 80px 40px rgba(29, 78, 216, 0.25);
            filter: blur(25px);
            animation: float var(--duration, 25s) ease-in-out infinite, glow 8s ease-in-out infinite alternate;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg) scale(1);
                opacity: 0.8;
            }
            25% {
                transform: translate(30px, 30px) rotate(8deg) scale(1.1);
                opacity: 0.9;
            }
            50% {
                transform: translate(0, 60px) rotate(0deg) scale(0.9);
                opacity: 0.7;
            }
            75% {
                transform: translate(-30px, 30px) rotate(-8deg) scale(1.05);
                opacity: 0.85;
            }
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.7;
            }
            50% {
                transform: scale(1.1);
                opacity: 0.9;
            }
            100% {
                transform: scale(1);
                opacity: 0.7;
            }
        }
        
        /* Decorative Elements */
        .hero-blur {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.15;
            z-index: 1;
            animation: pulse 8s ease-in-out infinite alternate;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.1; }
            100% { transform: scale(1.1); opacity: 0.2; }
        }
        
        .blur-1 {
            width: 400px;
            height: 400px;
            background: var(--accent);
            top: -100px;
            right: -100px;
            animation: float-accent 15s ease-in-out infinite alternate;
        }
        
        @keyframes float-accent {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(-50px, 50px);
            }
        }
        
        .blur-2 {
            width: 500px;
            height: 500px;
            background: var(--light);
            bottom: -200px;
            left: -200px;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
            }
            
            .hero-logo {
                width: 140px;
                height: 140px;
            }
        }
        
        @media (max-width: 768px) {
            .hero-section {
                min-height: 90vh;
                padding: 5rem 1rem;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
                margin: 1rem auto 2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
                padding: 0.9rem 2rem;
            }
            
            .hero-logo {
                width: 120px;
                height: 120px;
            }
        }
    </style>
    <section class="hero-section">
        <!-- Background Pattern -->
        <div class="bg-pattern"></div>
        
        <!-- Geometric Shapes -->
        <div class="geometric-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
            <div class="shape shape-4"></div>
            <div class="blue-accent accent-1"></div>
            <div class="blue-accent accent-2"></div>
            <div class="accent-3"></div>
            
            <!-- Floating Dots Background -->
            <div class="floating-dots" id="floatingDots">
                <!-- Dots will be added by JavaScript -->
            </div>
        </div>
        
        <!-- Decorative Blur Effects -->
        <div class="hero-blur blur-1"></div>
        <div class="hero-blur blur-2"></div>
        
        <!-- Animated Floating Elements -->
        <div class="floating-elements">
            <div class="floating-element" style="--i:1; --size: 40px; --x: 10%; --y: 20%; --duration: 15s; --delay: 0s;"></div>
            <div class="floating-element yellow-float" style="--i:2; --size: 25px; --x: 85%; --y: 30%; --duration: 18s; --delay: -2s;"></div>
            <div class="floating-element" style="--i:3; --size: 30px; --x: 30%; --y: 70%; --duration: 25s; --delay: -10s;"></div>
            <div class="floating-element yellow-float" style="--i:4; --size: 35px; --x: 70%; --y: 60%; --duration: 20s; --delay: -8s;"></div>
            <div class="floating-element" style="--i:5; --size: 25px; --x: 20%; --y: 50%; --duration: 22s; --delay: -3s;"></div>
            <div class="floating-element yellow-float" style="--size: 20px; --x: 90%; --y: 80%; --duration: 25s; --delay: -12s;"></div>
            <div class="floating-element yellow-float" style="--size: 30px; --x: 15%; --y: 25%; --duration: 22s; --delay: -5s;"></div>
            <div class="floating-element accent-float" style="--size: 120px; --x: 80%; --y: 70%; --duration: 25s; --delay: -5s;"></div>
        </div>
        
        <div class="hero-content">
            <img src="assets/img/logo_forsapin.jpg" alt="SAPIN" class="hero-logo">
            <h1 class="display-4 fw-bold mb-4" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.2); text-align: center; font-size: 3.5rem; text-transform: none;">Experience Luxurious Comfort</h1>
            <p class="hero-subtitle">
                Premium quality products designed to elevate your home with comfort and style
            </p>
            <div class="hero-buttons">
                <a href="shop.php" class="btn btn-primary">
                    <i class="bi bi-cart3"></i> Shop Now
                </a>
                <a href="#featured" class="btn btn-outline">
                    <i class="bi bi-grid"></i> View Collections
                </a>
            </div>
        </div>
        
        <a href="#features" class="scroll-down">
            <span>Scroll to Explore</span>
            <i class="bi bi-arrow-down"></i>
        </a>
    </section>
    
    <style>
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-8px); }
            60% { transform: translateY(-4px); }
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
    </style>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5 scroll-reveal">
                <h2 class="display-5 fw-bold mb-3">Why Choose SAPIN?</h2>
                <div style="width: 80px; height: 4px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); margin: 0 auto; border-radius: 2px;"></div>
            </div>
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="feature-card">
                        <i class="bi bi-shield-check feature-icon text-primary"></i>
                        <h4>Premium Quality</h4>
                        <p>Made with the finest materials for ultimate comfort</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <i class="bi bi-wind feature-icon text-primary"></i>
                        <h4>Breathable</h4>
                        <p>Stay cool and comfortable all night long</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <i class="bi bi-heart feature-icon text-primary"></i>
                        <h4>Hypoallergenic</h4>
                        <p>Safe for sensitive skin and allergies</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <i class="bi bi-stars feature-icon text-primary"></i>
                        <h4>Long-Lasting</h4>
                        <p>Durable materials that maintain quality</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php 
        $stmt = $pdo->prepare("SELECT 
            p.*, 
            c.category_name,
            AVG(r.rating) AS avg_rating,
            COUNT(r.rating_id) AS rating_count,
            vs.sizes_pairs,
            vs.from_price,
            vs.total_variant_stock,
            vs.has_variants
        FROM 
            products p
        JOIN 
            product_category c ON p.category_id = c.category_id
        INNER JOIN 
            item_ratings r ON p.product_id = r.product_id
        LEFT JOIN (
            SELECT product_id,
                   MIN(CASE WHEN is_active = 1 AND stock > 0 THEN price END) AS from_price,
                   GROUP_CONCAT(CASE WHEN is_active = 1 AND stock > 0 THEN CONCAT(size,'::',stock) END SEPARATOR '||') AS sizes_pairs,
                   SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_variant_stock,
                   COUNT(*) AS has_variants
            FROM product_variants
            GROUP BY product_id
        ) vs ON vs.product_id = p.product_id
        GROUP BY 
            p.product_id
        HAVING 
            rating_count > 0
        ORDER BY 
            avg_rating DESC, rating_count DESC
        LIMIT 4;");
        $stmt->execute();
        $featured = $stmt->fetchAll();
    ?>
    <!-- Featured Products -->
    <section id="featured" class="py-5" style="background: linear-gradient(135deg, #f8fafc 0%, #e5e7eb 100%);">
        <div class="container">
            <div class="text-center mb-5" style="animation: fadeInUp 0.8s ease-out;">
                <h2 class="display-4 fw-bold mb-3">Featured Collections</h2>
                <div style="width: 100px; height: 4px; background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); margin: 0 auto; border-radius: 2px;"></div>
                <p class="text-muted mt-3">Handpicked products with the best ratings</p>
            </div>
            <div class="row g-4">
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
                ?>
                <?php foreach ($featured as $product): ?>
                    <div class="col-md-3">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>"
                                    class="card-img-top"
                                    alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                    style="height: 220px; object-fit: cover;">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 0.5rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">
                                        <i class="bi bi-star-fill me-1"></i>Featured
                                    </span>
                                </div>
                                <?php 
                                // Calculate effective stock
                                $effective_stock = $product['has_variants'] > 0 ? ($product['total_variant_stock'] ?? 0) : $product['stock'];
                                ?>
                                <div class="position-absolute top-0 start-0 m-2">
                                    <?php if ($effective_stock <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($effective_stock > 0 && $effective_stock <= $product['restock_alert']): ?>
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">In Stock</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge mb-2" style="background: linear-gradient(135deg, var(--primary-color), #3b82f6); border-radius: 6px;"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                    <span class="badge mb-2 ms-1" style="background: linear-gradient(135deg, #10b981, #059669); border-radius: 6px;"><?php echo htmlspecialchars($product['material']); ?></span>
                                </div>
                                <h5 class="card-title fw-bold" style="color: #1e293b;"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                <div class="mb-2">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="text-warning" style="font-size: 1.1rem;">
                                            <?php echo renderStars($product['avg_rating']); ?>
                                        </span>
                                        <span class="badge bg-warning text-dark" style="border-radius: 6px;"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                    </div>
                                    <p class="card-text text-muted small mb-2">
                                        <?php if (!empty($product['sizes_pairs'])): ?>
                                            <?php
                                                // Parse variant sizes
                                                $sizes = array_filter(explode('||', $product['sizes_pairs']));
                                                $size_list = [];
                                                foreach ($sizes as $s) {
                                                    if (!empty($s)) {
                                                        list($size_name, $stock) = explode('::', $s);
                                                        $size_list[] = $size_name;
                                                    }
                                                }
                                                $size_display = implode(', ', $size_list);
                                            ?>
                                            <i class="bi bi-rulers text-primary me-1"></i>Available sizes: <?php echo htmlspecialchars($size_display); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <small class="text-muted d-block" style="font-size: 0.7rem;">PRICE</small>
                                            <?php 
                                                // Use variant price if available, otherwise product price
                                                $display_price = !empty($product['from_price']) ? $product['from_price'] : $product['price'];
                                                $has_variants = $product['has_variants'] > 0;
                                            ?>
                                            <?php if ($is_bulk_buyer && $user_discount > 0): ?>
                                                <?php 
                                                    $discounted_price = $display_price * (1 - ($user_discount / 100));
                                                ?>
                                                <small class="text-muted text-decoration-line-through d-block"><?php echo $has_variants ? 'From ' : ''; ?>₱<?php echo number_format($display_price, 2); ?></small>
                                                <span class="fw-bold" style="font-size: 1.5rem; background: linear-gradient(135deg, #10b981, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $has_variants ? 'From ' : ''; ?>₱<?php echo number_format($discounted_price, 2); ?></span>
                                                <small class="badge bg-success">-<?php echo $user_discount; ?>%</small>
                                            <?php else: ?>
                                                <span class="fw-bold" style="font-size: 1.5rem; background: linear-gradient(135deg, var(--primary-color), #3b82f6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $has_variants ? 'From ' : ''; ?>₱<?php echo number_format($display_price, 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <?php if ($effective_stock <= 0): ?>
                                            <button class="btn btn-secondary" disabled style="border-radius: 8px; padding: 0.6rem;">
                                                <i class="bi bi-x-circle me-2"></i>Out of Stock
                                            </button>
                                            <a href="product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary" style="border-radius: 8px; padding: 0.6rem;">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        <?php elseif ($has_variants): ?>
                                            <a href="product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-primary" style="border-radius: 8px; padding: 0.6rem;">
                                                <i class="bi bi-eye me-2"></i>View Details
                                            </a>
                                        <?php else: ?>
                                            <button onclick="addToCart(<?php echo $product['product_id']; ?>, event)" class="btn btn-primary" style="border-radius: 8px; padding: 0.6rem;">
                                                <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                            </button>
                                            <a href="product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary" style="border-radius: 8px; padding: 0.6rem;">View Details</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5">
                <a href="shop.php" class="btn btn-primary btn-lg" style="padding: 0.8rem 2.5rem; border-radius: 50px;">
                    <i class="bi bi-grid-3x3-gap me-2"></i>View All Products
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials 
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">What Our Customers Say</h2>
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="testimonial-card">
                            <p class="mb-3">No reviews available yet.</p>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section> -->


    <!-- CTA Section -->
    <section class="cta-section text-center">
        <div class="container" style="position: relative; z-index: 1;">
            <h2 class="display-3 fw-bold mb-4" style="text-shadow: 2px 2px 4px rgba(0,0,0,0.2);">Ready to Shop?</h2>
            <p class="lead mb-5" style="font-size: 1.2rem; max-width: 600px; margin: 0 auto;">Browse our collection of quality bedsheets, curtains, and comforters</p>
            <a href="shop.php" class="btn btn-light btn-hero">
                <i class="bi bi-cart-check me-2"></i>Start Shopping
            </a>
        </div>
    </section>

    <!-- <script src="assets/js/script.js"></script> -->
</body>
</html>

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
        // Create floating dots
        function createFloatingDots() {
            const container = document.getElementById('floatingDots');
            if (!container) return;
            
            // Clear existing dots
            container.innerHTML = '';
            
            // Create 20 floating dots
            for (let i = 0; i < 20; i++) {
                const dot = document.createElement('div');
                const size = Math.random() * 8 + 4; // 4px to 12px
                const isBlue = Math.random() > 0.5;
                const duration = 15 + Math.random() * 20; // 15-35s
                const delay = Math.random() * -20; // 0 to -20s
                const posX = Math.random() * 100; // 0% to 100%
                const posY = Math.random() * 100; // 0% to 100%
                
                dot.className = `dot ${isBlue ? 'blue' : 'yellow'}`;
                dot.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    left: ${posX}%;
                    top: ${posY}%;
                    --duration: ${duration}s;
                    --delay: ${delay}s;
                    animation-duration: ${duration}s;
                    animation-delay: ${delay}s;
                `;
                
                container.appendChild(dot);
            }
        }
        
        // Dynamic floating elements
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize floating dots
            createFloatingDots();
            const heroSection = document.querySelector('.hero-section');
            const floatingContainer = document.querySelector('.floating-elements');
            
            // Add more dynamic floating elements
            function createFloatingElement() {
                const element = document.createElement('div');
                const size = Math.random() * 30 + 10; // 10px to 40px
                const isYellow = Math.random() > 0.5;
                const isLarge = Math.random() > 0.9;
                
                element.className = 'floating-element' + (isYellow ? ' yellow-float' : '') + (isLarge ? ' accent-float' : '');
                
                const style = {
                    '--size': `${size}px`,
                    '--x': `${Math.random() * 100}%`,
                    '--y': `${Math.random() * 100}%`,
                    '--duration': `${15 + Math.random() * 20}s`,
                    '--delay': `-${Math.random() * 10}s`
                };
                
                Object.assign(element.style, style);
                floatingContainer.appendChild(element);
            }
            
            // Create initial floating elements
            for (let i = 0; i < 5; i++) {
                createFloatingElement();
            }
            
            // Add interactive effect on mousemove
            heroSection.addEventListener('mousemove', (e) => {
                const { clientX, clientY } = e;
                const { left, top, width, height } = heroSection.getBoundingClientRect();
                
                const x = ((clientX - left) / width) * 100;
                const y = ((clientY - top) / height) * 100;
                
                // Move the accent float towards cursor
                const accentFloat = document.querySelector('.accent-float');
                if (accentFloat) {
                    accentFloat.style.setProperty('--x', `${x}%`);
                    accentFloat.style.setProperty('--y', `${y}%`);
                }
            });
        });

        // Initialize Swiper
        new Swiper('.swiper-container', {
            slidesPerView: 1,
            spaceBetween: 20,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
            autoplay: {
                delay: 5000,
            },
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Parallax & Scroll Reveal Effects -->
    <script>
        // Parallax for fabric waves
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            
            const wave1 = document.querySelector('.fabric-wave-1');
            const wave2 = document.querySelector('.fabric-wave-2');
            
            if (wave1) {
                wave1.style.transform = `translateY(${scrolled * 0.2}px) translateX(-10%)`;
            }
            if (wave2) {
                wave2.style.transform = `translateY(${scrolled * 0.3}px) translateX(10%)`;
            }
            
            // Scroll Reveal Animation
            const reveals = document.querySelectorAll('.scroll-reveal');
            reveals.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.classList.add('active');
                }
            });
        });
        
        // Trigger on page load
        window.dispatchEvent(new Event('scroll'));
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .custom-spinner {
            width: 3rem;
            height: 3rem;
            border: 0.4rem solid #f3f3f3;
            border-top: 0.4rem solid var(--bs-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 1rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
        const customSwal = (title, text) => {
            Swal.fire({
            title: title,
            html: `
                <div class="custom-spinner mb-2"></div>
                <p>${text}</p>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false
            });
        };
        
        // Function to update cart count in navbar
        function updateCartCount() {
            fetch('backend/get_cart_count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const cartLink = document.querySelector('a[href="cart.php"]');
                        if (cartLink) {
                            // Remove existing badge if any
                            const existingBadge = cartLink.querySelector('.badge');
                            if (existingBadge) {
                                existingBadge.remove();
                            }
                            
                            // Add badge only if count > 0
                            if (data.count > 0) {
                                const badge = document.createElement('span');
                                badge.className = 'badge bg-primary rounded-pill';
                                badge.id = 'cart-count';
                                badge.textContent = data.count;
                                cartLink.appendChild(badge);
                            }
                        }
                    }
                })
                .catch(error => {
                    console.log('Error updating cart count:', error);
                });
        }
        
        function addToCart(productId, event) {
            // Prevent event bubbling to parent elements
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            customSwal('Please wait...', 'Adding product to cart...');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'backend/add_to_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.send('action=add_to_cart&product_id=' + productId + '&quantity=1');

            const startTime = Date.now(); // Record when the request starts

            xhr.onload = function () {
                const elapsed = Date.now() - startTime;
                const delay = Math.max(0, 1000 - elapsed); // Ensure at least 1s delay

                setTimeout(() => {
                    Swal.close(); // Close loading screen after delay

                    if (xhr.status == 200) {
                        if (xhr.responseText.trim() === 'not_logged_in') {
                            Swal.fire({
                                title: 'Please Log In',
                                text: 'You need to log in to add items to your cart.',
                                icon: 'warning',
                                confirmButtonText: 'Go to Login'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'login.php';
                                }
                            });
                        }
                        else if (xhr.responseText.trim() === 'not_verified') {
                            Swal.fire({
                                title: 'Email Not Verified',
                                text: 'You need to verify your email address before adding items to your cart.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Verify',
                                cancelButtonText: 'Go Back'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = 'verify_email.php';
                                } else {
                                    window.history.replaceState(null, '', 'index.php');
                                    window.location.href = 'index.php';
                                }
                            });
                        }
                        else if (xhr.responseText.trim() === 'out_of_stock') {
                            Swal.fire({
                                title: 'Out of Stock',
                                text: 'Sorry, the product is out of stock or the quantity you requested is unavailable.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                        else if (xhr.responseText.trim() === 'cart_limit_reached') {
                            Swal.fire({
                                title: 'Cart Limit Reached',
                                text: 'You cannot have more than 50 products in your cart. Please remove some items before adding new ones.',
                                icon: 'warning',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            // Update cart count immediately
                            updateCartCount();
                            
                            // Parse JSON response
                            try {
                                const response = JSON.parse(xhr.responseText);
                                Swal.fire({
                                    title: 'Product Added to Cart',
                                    text: response.message || 'Product added successfully!',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } catch (e) {
                                // Fallback if response is not JSON
                                Swal.fire({
                                    title: 'Product Added to Cart',
                                    text: 'Product added successfully!',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                            // No page reload - user stays on current page
                        }
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'There was an issue adding the product to the cart.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }, delay);
            };

            xhr.onerror = function () {
                const elapsed = Date.now() - startTime;
                const delay = Math.max(0, 1000 - elapsed);

                setTimeout(() => {
                    Swal.close();
                    Swal.fire({
                        title: 'Network Error',
                        text: 'There was an issue with the request.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }, delay);
            };
        }

    </script>
</body>

</html>