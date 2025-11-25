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
    
    // Fetch all wholesalers
    $stmt = $pdo->query("
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.discount_rate,
            CONCAT(ud.firstname, ' ', ud.lastname) as full_name,
            ud.contact_number,
            (SELECT COUNT(*) FROM orders WHERE user_id = u.user_id) as total_orders,
            (SELECT SUM(amount + shipping_fee) FROM orders WHERE user_id = u.user_id AND status IN ('Delivered', 'Received')) as total_spent
        FROM users u
        JOIN userdetails ud ON u.user_id = ud.user_id
        WHERE u.usertype_id = 3
        ORDER BY ud.firstname ASC
    ");
    $bulk_buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    <title>Manage Wholesalers - Admin</title>
    
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f7f9fc; }
        
        .page-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 2rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 1.75rem;
        }
        
        .buyer-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .buyer-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .buyer-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .buyer-info {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .discount-input {
            width: 100px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .stats-badge {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-block;
        }
        
        .stats-badge strong {
            color: #2563eb;
        }
        
        .card { 
            border: none; 
            border-radius: 14px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php $active = 'manage_bulk_buyers'; ?>
        <?php require ('../includes/sidebar_admin.php');?>
        
        <div class="main">
            <?php require ('../includes/navbar_admin.php');?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <div class="page-header">
                        <h1>
                            <i class="bi bi-people-fill me-2"></i>Manage Wholesalers
                        </h1>
                        <p class="mb-0 mt-2" style="opacity: 0.9;">Adjust discount rates and manage wholesaler accounts</p>
                    </div>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible">
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <?= $error_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <?php if(empty($bulk_buyers)): ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 4rem; color: #cbd5e1;"></i>
                                        <h4 class="mt-3 text-muted">No Wholesalers Yet</h4>
                                        <p class="text-muted">Approved wholesalers will appear here</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach($bulk_buyers as $buyer): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="buyer-card">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <div class="buyer-name">
                                                    <i class="bi bi-person-circle me-2"></i>
                                                    <?= htmlspecialchars($buyer['full_name']) ?>
                                                </div>
                                                <div class="buyer-info">
                                                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($buyer['email']) ?>
                                                </div>
                                                <div class="buyer-info">
                                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($buyer['contact_number']) ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-success">
                                                <i class="bi bi-star-fill"></i> Wholesaler
                                            </span>
                                        </div>
                                        
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <div class="stats-badge w-100 text-center">
                                                    <small class="d-block text-muted">Total Orders</small>
                                                    <strong><?= $buyer['total_orders'] ?></strong>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stats-badge w-100 text-center">
                                                    <small class="d-block text-muted">Total Spent</small>
                                                    <strong>₱<?= number_format($buyer['total_spent'] ?? 0, 2) ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <form action="backend/update_bulk_discount.php" method="POST" class="discount-form">
                                            <input type="hidden" name="user_id" value="<?= $buyer['user_id'] ?>">
                                            <div class="d-flex align-items-center gap-2">
                                                <label class="mb-0 fw-bold">Discount:</label>
                                                <div class="input-group" style="max-width: 150px;">
                                                    <input type="number" 
                                                           name="discount_rate" 
                                                           class="form-control discount-input" 
                                                           value="<?= $buyer['discount_rate'] ?>" 
                                                           min="0" 
                                                           max="100" 
                                                           step="0.5" 
                                                           required>
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="bi bi-check-lg"></i> Save
                                                </button>
                                            </div>
                                        </form>
                                        
                                        <div class="mt-3 d-flex gap-2">
                                            <a href="bulk_messages.php?user_id=<?= $buyer['user_id'] ?>" 
                                               class="btn btn-outline-success btn-sm flex-fill">
                                                <i class="bi bi-chat-dots"></i> Message
                                            </a>
                                            <a href="orders.php?customer=<?= urlencode($buyer['full_name']) ?>" 
                                               class="btn btn-outline-primary btn-sm flex-fill">
                                                <i class="bi bi-box"></i> Orders
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>
    
    <script src="js/app.js"></script>
    <script>
        // Form submission with SweetAlert
        document.querySelectorAll('.discount-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const discountRate = formData.get('discount_rate');
                
                fetch('backend/update_bulk_discount.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: 'Discount rate updated to ' + discountRate + '%',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update discount'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong!'
                    });
                });
            });
        });
    </script>
</body>
</html>
