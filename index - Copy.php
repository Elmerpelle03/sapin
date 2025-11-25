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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Quality Beddings - Sapin Bedsheets</title>
    
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

        .btn-primary:hover {
            background: linear-gradient(90deg, var(--secondary-color) 60%, var(--primary-color) 100%);
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
        }
    </style>
    <style>
        .hero-section { 
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            background-size: cover;
            background-position: center;
            height: 80vh;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
        }
        
        .feature-card {
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s;
            border: none;
            background: var(--accent-color);
            box-shadow: 0 0.5rem 1rem rgba(37,99,235,0.1);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--bs-primary);
            margin-bottom: 1rem;
        }
        
        .product-card {
            border: none;
            transition: transform 0.3s;
            box-shadow: 0 0.5rem 1rem rgba(37,99,235,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
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
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 4rem 0;
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
    <section class="hero-section">
        <div class="container">
            <h1 class="display-3 fw-bold mb-4">Experience Luxurious Comfort</h1>
            <p class="lead mb-4">Premium quality bedsheets that transform your sleep experience</p>
            <a href="shop.php" class="btn btn-light btn-lg">Shop Now</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Sapin Bedsheets?</h2>
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
            COALESCE(AVG(r.rating), 0) AS avg_rating
        FROM 
            products p
        JOIN 
            product_category c ON p.category_id = c.category_id
        LEFT JOIN 
            item_ratings r ON p.product_id = r.product_id
        GROUP BY 
            p.product_id
        ORDER BY 
            p.category_id DESC
        LIMIT 4;");
        $stmt->execute();
        $featured = $stmt->fetchAll();
    ?>
    <!-- Featured Products -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Featured Collections</h2>
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
                            <img src="uploads/products/<?php echo htmlspecialchars($product['image_url']); ?>"
                                class="card-img-top"
                                alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <span class="badge bg-primary mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <span class="badge bg-info mb-2 ms-1"><?php echo htmlspecialchars($product['material']); ?></span>
                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                <p class="card-text text-muted small mb-2">
                                    Size: <?php echo htmlspecialchars($product['size']); ?><br>
                                    <?php echo htmlspecialchars($product['pieces_per_bundle']); ?> pcs per bundle
                                    <br>
                                    <span class="text-warning">
                                        <?php echo renderStars($product['avg_rating']); ?>
                                        <small class="text-muted">(<?php echo number_format($product['avg_rating'], 1); ?>)</small>
                                    </span>
                                </p>
                                <p class="card-text text-muted">
                                    <?php
                                        $desc = htmlspecialchars($product['description']);
                                        echo strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc;
                                    ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="price fw-bold">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <div class="d-flex gap-1">
                                        <button onclick="addToCart(<?php echo $product['product_id']; ?>, event)" class="btn btn-primary btn-sm">
                                            <i class="bi bi-cart-plus"></i>
                                        </button>
                                        <a href="product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <a href="shop.php" class="btn btn-primary btn-lg">View All Products</a>
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
        <div class="container">
            <h2 class="display-4 mb-4">Transform Your Sleep Experience</h2>
            <p class="lead mb-4">Join thousands of satisfied customers who have chosen Sapin Bedsheets</p>
            <a href="shop.php" class="btn btn-light btn-lg">Shop Now</a>
        </div>
    </section>

    <!-- <script src="assets/js/script.js"></script> -->
</body>
</html>

    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    <script>
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
                        const cartCountElement = document.getElementById('cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.count;
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
                            
                            Swal.fire({
                                title: 'Product Added to Cart',
                                text: xhr.responseText,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            });
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