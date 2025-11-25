<?php 
require('config/db.php');
session_start();
require('config/session_disallow_courier.php');

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
} elseif (isset($_SESSION['info_message'])) {
    $info_message = $_SESSION['info_message'];
    unset($_SESSION['info_message']);
}

if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    if (empty($order_id)) {
        $_SESSION['error_message'] = "Invalid order ID.";
        header('Location: orders.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :order_id AND user_id = :user_id");
    $stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $user_id
    ]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error_message'] = "Order not found or you don't have permission to view it.";
        header('Location: orders.php');
        exit;
    }
} else {
    $_SESSION['error_message'] = "No order ID specified.";
    header('Location: orders.php');
    exit;
}

$stmt_items = $pdo->prepare("
    SELECT o.orderitems_id, o.quantity,
           p.product_id, p.product_name, p.image_url,
           ir.rating, ir.comment
    FROM order_items o
    JOIN products p ON o.product_id = p.product_id
    LEFT JOIN item_ratings ir ON ir.order_id = o.order_id AND ir.product_id = o.product_id AND ir.user_id = :user_id
    WHERE o.order_id = :order_id
");
$stmt_items->execute([':order_id' => $order_id, ':user_id' => $user_id]);
$order_items = $stmt_items->fetchAll();

// Check if all items are already rated
$all_rated = true;
foreach ($order_items as $item) {
    if (is_null($item['rating'])) {
        $all_rated = false;
        break;
    }
}
if ($all_rated) {
    $_SESSION['info_message'] = "You have already rated all items in this order.";
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Rate My Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        :root {
            --primary-color: #7b5dfa;
            --secondary-color: #4d3fa3;
            --light-bg: #f7f7fa;
            --border-color: #edeaff;
        }
        body {
            background: linear-gradient(120deg, var(--light-bg) 0%, #fff 100%);
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
        .card {
            border-radius: 1rem;
            border: 1.5px solid var(--border-color);
            box-shadow: 0 4px 24px rgba(80, 80, 150, 0.1);
        }
        .star-rating {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            display: inline-block;
        }
        .star-rating .bi-star-fill {
            color: #ffc107;
        }
        .star-rating i:hover,
        .star-rating i:hover ~ i {
            color: #ffc107;
        }
        .rating-item {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .rating-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(80, 80, 150, 0.15) !important;
        }
        .product-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(123, 90, 250, 0.4);
        }
        .btn-back {
            background: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            margin-right: 10px;
        }
        .btn-back:hover {
            background: #5a6268;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .card {
                margin: 10px;
                width: calc(100% - 20px) !important;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .container {
                padding: 20px 10px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            h5 {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            .card {
                margin: 5px;
                width: calc(100% - 10px) !important;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .star-rating {
                font-size: 1.2rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">

<?php $active = 'orders'; ?>
<?php include 'includes/navbar_customer.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4 shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h4 class="mb-0">Rate Your Items</h4>
                    <p class="mb-0">Help us improve by rating the products you purchased</p>
                </div>
                <div class="card-body">
                    <form action="backend/submit_ratings.php" method="POST">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">

                        <?php foreach ($order_items as $item): ?>
                            <div class="mb-4 p-3 border rounded rating-item" data-product-id="<?php echo $item['product_id']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center product-image">
                                                <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-9">
                                        <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                        <p class="text-muted mb-2">Quantity: <?php echo (int)$item['quantity']; ?></p>

                                        <label>Rating:</label>
                                        <div class="star-rating mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo ($item['rating'] >= $i) ? '-fill' : ''; ?>" data-value="<?php echo $i; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <!-- Only one hidden input per item -->
                                        <input type="hidden" class="rating-input" name="ratings[<?php echo $item['product_id']; ?>]" value="<?php echo $item['rating'] ?? 1; ?>">

                                        <label>Comment (optional):</label>
                                        <textarea class="form-control mb-2" name="comments[<?php echo $item['product_id']; ?>]" rows="2" placeholder="Share your thoughts about this product..."><?php echo htmlspecialchars($item['comment'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-4">
                            <a href="orders.php" class="btn btn-back">Back to Orders</a>
                            <button type="submit" class="btn btn-submit">Submit Ratings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('.star-rating i').on('click', function() {
        const selectedStar = $(this);
        const container = selectedStar.closest('.rating-item');
        const ratingValue = parseInt(selectedStar.data('value'));

        // Update stars
        selectedStar.parent().find('i').each(function() {
            const starVal = $(this).data('value');
            $(this).toggleClass('bi-star-fill', starVal <= ratingValue);
            $(this).toggleClass('bi-star', starVal > ratingValue);
        });

        // Set hidden input value
        container.find('input.rating-input').val(ratingValue);
    });

    // Hover effects for preview
    $('.star-rating i').on('mouseenter', function() {
        const hoveredStar = $(this);
        const ratingValue = parseInt(hoveredStar.data('value'));
        hoveredStar.parent().find('i').each(function() {
            const starVal = $(this).data('value');
            if (starVal <= ratingValue) {
                $(this).addClass('text-warning');
            }
        });
    }).on('mouseleave', function() {
        $('.star-rating i').removeClass('text-warning');
    });

    // Initialize ratings
    $('.rating-item').each(function() {
        const container = $(this);
        const currentRating = parseInt(container.find('input.rating-input').val());
        if (currentRating > 0) {
            container.find('.star-rating i').each(function() {
                const starVal = $(this).data('value');
                $(this).toggleClass('bi-star-fill', starVal <= currentRating);
                $(this).toggleClass('bi-star', starVal > currentRating);
            });
        }
    });
});
</script>

<?php if (isset($success_message)): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo $success_message; ?>'
    });
</script>
<?php elseif (isset($error_message)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: '<?php echo $error_message; ?>'
    });
</script>
<?php elseif (isset($info_message)): ?>
<script>
    Swal.fire({
        icon: 'info',
        title: 'Information',
        text: '<?php echo $info_message; ?>'
    });
</script>
<?php endif; ?>

</body>
</html>
