<?php 
    $user_data = null;
    $cart_count = 0;
    
    if(isset($_SESSION['user_id'])){
        try {
            $stmt = $pdo->prepare("SELECT a.user_id, a.usertype_id, a.username, a.email, a.is_verified, a.accountstatus_id, a.join_date,
            b.firstname, b.lastname, CONCAT(b.house, ' ', tb.barangay_name, ', ', tm.municipality_name, ', ', tp.province_name) AS address,
            tr.region_id, tb.barangay_id,
            tm.municipality_id, tp.province_id, b.house, b.contact_number,
            c.usertype_name
            FROM users a 
            LEFT JOIN userdetails b ON a.user_id = b.user_id
            LEFT JOIN usertype c ON a.usertype_id = c.usertype_id
            LEFT JOIN table_region tr ON b.region_id = tr.region_id
            LEFT JOIN table_province tp ON b.province_id = tp.province_id
            LEFT JOIN table_municipality tm ON b.municipality_id = tm.municipality_id
            LEFT JOIN table_barangay tb ON b.barangay_id = tb.barangay_id
            WHERE a.user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user_data = $stmt->fetch();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $cart_count = $stmt->fetchColumn();
            
            // Count new orders (pending status)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id AND status = 'pending'");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $new_orders_count = $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Navbar error: " . $e->getMessage());
        }
    }
?>

<nav class="navbar navbar-expand-lg navbar-light custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="assets/img/logo_forsapin.jpg" alt="SAPIN Logo" height="40" width="40" style="object-fit:cover;" class="me-2 rounded-circle d-none d-sm-block">
            <img src="assets/img/logo_forsapin.jpg" alt="SAPIN Logo" height="35" width="35" style="object-fit:cover;" class="me-1 rounded-circle d-sm-none">
            <span class="fw-bold d-none d-md-block" style="color:#2563eb;font-size:1.35rem;">SAPIN</span>
            <span class="fw-bold d-md-none" style="color:#2563eb;font-size:1rem;">SAPIN</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active === 'index') ? 'active' : ''; ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($active === 'shop') ? 'active' : ''; ?>" href="shop.php">Products</a>
                </li>
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active === 'orders') ? 'active' : ''; ?>" href="orders.php">
                            My Orders
                            <?php if (isset($new_orders_count) && $new_orders_count > 0): ?>
                                <span class="badge bg-danger rounded-pill ms-1"><?php echo $new_orders_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active === 'wishlist') ? 'active' : ''; ?>" href="wishlist.php">
                            <span class="d-none d-md-inline">Wishlist</span>
                            <i class="bi bi-heart d-md-none"></i>
                            <span class="badge bg-danger rounded-pill" id="wishlist-count" style="display: none;">0</span>
                        </a>
                    </li>
                    <?php if (isset($user_data['usertype_id']) && $user_data['usertype_id'] == 3): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active === 'messages') ? 'active' : ''; ?>" href="message_admin.php">
                            <span class="d-none d-md-inline">Message Admin</span>
                            <i class="bi bi-chat-dots d-md-none"></i>
                            <?php
                            // Get unread admin messages count
                            $unread_msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM bulk_buyer_messages 
                                                              WHERE user_id = :user_id 
                                                              AND sender_type = 'admin' 
                                                              AND is_read = 0");
                            $unread_msg_stmt->execute([':user_id' => $_SESSION['user_id']]);
                            $unread_messages = $unread_msg_stmt->fetchColumn();
                            if ($unread_messages > 0):
                            ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $unread_messages; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($active === 'cart') ? 'active' : ''; ?>" href="cart.php">
                            <span class="d-none d-md-inline">Cart</span>
                            <i class="bi bi-cart d-md-none"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-primary rounded-pill" id="cart-count"><?php echo $cart_count;?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
                
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <!-- User is logged in -->
                    
                    <!-- Notifications Dropdown -->
                    <?php include 'includes/notifications_dropdown.php'; ?>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($user_data['firstname'] ?? $user_data['username'] ?? 'User'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#userModal">View Profile</a>
                            </li>
                            <?php if ($user_data['usertype_id'] == 1 || $user_data['usertype_id'] == 5):?>
                                <li>
                                    <a class="dropdown-item" href="admin/index.php">Admin Panel</a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="#" id="navbar-logout-btn">Logout</a>
                            </li>
                        </ul>
                    </li>
                    <?php else: ?>
                        <!-- User is not logged in -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php" id="nav-login-btn">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php" id="nav-register-btn">Register</a>
                        </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php require 'modal_profile.php';?>

<script>
// Update wishlist count badge from database
function updateWishlistCount() {
    fetch('backend/wishlist_api.php?action=count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('wishlist-count');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
    })
    .catch(error => console.error('Error updating wishlist count:', error));
}

// Update on page load
document.addEventListener('DOMContentLoaded', updateWishlistCount);

// Logout confirmation for navbar dropdown
document.addEventListener('DOMContentLoaded', function() {
    const navbarLogoutBtn = document.getElementById('navbar-logout-btn');
    
    if (navbarLogoutBtn) {
        navbarLogoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you really want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Logging out...',
                        text: 'Please wait...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        willOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        window.location.href = 'auth/logout.php';
                    }, 1000);
                }
            });
        });
    }
});
</script>
