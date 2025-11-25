<?php 
    require ('config/db.php');
    session_start();
    require('config/session_disallow_courier.php');
    
    // Get user discount rate for bulk buyers
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
    <title>My Wishlist - Sapin Bedsheets</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
        }
        
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #ffffff 100%);
            min-height: 100vh;
        }
        
        /* Navbar styling to match index.php */
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
        
        .wishlist-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .wishlist-item {
            transition: all 0.3s ease;
        }
        
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .btn-remove {
            transition: all 0.3s ease;
        }
        
        .btn-remove:hover {
            transform: scale(1.1);
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .navbar-brand span {
                font-size: 1.1rem !important;
            }
            .custom-navbar .nav-link {
                padding: 0.5rem 0.8rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand span {
                font-size: 1rem !important;
            }
        }
    </style>
</head>
<body>

<?php $active = 'wishlist'; ?>
<?php include 'includes/navbar_customer.php'; ?>

<div class="wishlist-header">
    <div class="container">
        <h1 class="display-4 fw-bold"><i class="bi bi-heart-fill me-3"></i>My Wishlist</h1>
        <p class="lead">Your favorite products saved for later</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 id="wishlist-count-text">Loading...</h4>
                <button class="btn btn-outline-danger" onclick="clearAllWishlist()">
                    <i class="bi bi-trash me-2"></i>Clear All
                </button>
            </div>
            
            <div id="wishlist-container" class="row g-4">
                <!-- Wishlist items will be loaded here -->
            </div>
            
            <div id="empty-wishlist" style="display: none;" class="text-center py-5">
                <i class="bi bi-heart" style="font-size: 5rem; color: #cbd5e1;"></i>
                <h3 class="mt-4 text-muted">Your wishlist is empty</h3>
                <p class="text-muted">Start adding products you love!</p>
                <a href="shop.php" class="btn btn-primary mt-3">
                    <i class="bi bi-shop me-2"></i>Browse Products
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Load wishlist items from database
function loadWishlist() {
    const container = document.getElementById('wishlist-container');
    const emptyMessage = document.getElementById('empty-wishlist');
    const countText = document.getElementById('wishlist-count-text');
    
    // Fetch wishlist from database
    fetch('backend/wishlist_api.php?action=get')
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to load wishlist');
            return;
        }
        
        const products = data.items;
        
        if (products.length === 0) {
            container.style.display = 'none';
            emptyMessage.style.display = 'block';
            countText.textContent = 'No items in wishlist';
            return;
        }
        
        container.style.display = 'flex';
        emptyMessage.style.display = 'none';
        countText.textContent = `${products.length} item${products.length > 1 ? 's' : ''} in wishlist`;
        container.innerHTML = products.map(product => `
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 wishlist-item">
                    <div class="position-relative">
                        <img src="uploads/products/${product.image_url}" class="card-img-top" alt="${product.product_name}" style="height: 250px; object-fit: cover;">
                        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2 btn-remove" onclick="removeFromWishlist(${product.product_id})">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">${product.product_name}</h5>
                        <p class="card-text">
                            <span class="text-primary fw-bold fs-5">₱${parseFloat(product.price).toFixed(2)}</span>
                        </p>
                        <div class="d-flex gap-2">
                            ${product.stock > 0 ? 
                                `<button class="btn btn-primary flex-grow-1" onclick="addToCartFromWishlist(${product.product_id})">
                                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                                </button>` :
                                `<button class="btn btn-secondary flex-grow-1" disabled>
                                    <i class="bi bi-x-circle me-2"></i>Out of Stock
                                </button>`
                            }
                            <a href="product.php?id=${product.product_id}" class="btn btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    })
    .catch(error => {
        console.error('Error loading wishlist:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load wishlist items'
        });
    });
}

// Remove item from wishlist
function removeFromWishlist(productId) {
    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);
    
    fetch('backend/wishlist_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Removed from Wishlist',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
            
            loadWishlist();
            if (typeof updateWishlistCount === 'function') {
                updateWishlistCount();
            }
        }
    });
}

// Clear all wishlist
function clearAllWishlist() {
    Swal.fire({
        title: 'Clear Wishlist?',
        text: 'Remove all items from your wishlist?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, clear all',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'clear');
            
            fetch('backend/wishlist_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadWishlist();
                    if (typeof updateWishlistCount === 'function') {
                        updateWishlistCount();
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'Wishlist Cleared',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        }
    });
}

// Add to cart from wishlist
function addToCartFromWishlist(productId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'backend/add_to_cart.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('action=add_to_cart&product_id=' + productId + '&quantity=1');

    xhr.onload = function () {
        if (xhr.status == 200) {
            const response = xhr.responseText.trim();
            
            if (response === 'not_logged_in') {
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
                return;
            }
            
            try {
                const data = JSON.parse(response);
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Added to Cart!',
                        text: data.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000
                    });
                    
                    // Update cart count if function exists
                    if (typeof updateCartCount === 'function') {
                        updateCartCount();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to add to cart'
                    });
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred'
                });
            }
        }
    };
}

// Load wishlist on page load
document.addEventListener('DOMContentLoaded', loadWishlist);
</script>

</body>
</html>
