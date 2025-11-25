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
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Point of Sale System">
	<meta name="author" content="Sapin Bedsheets">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<title>POS - Point of Sale</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	
	<!-- Load Bootstrap JS in HEAD like other pages -->
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	
	<style>
		/* Layout shell */
		.pos-main-container {
			background: #f8fafc; /* very light content background */
			border-radius: 14px;
			padding: 20px;
			margin-bottom: 20px;
			border: 1px solid #e5e7eb;
		}

		.pos-grid {
			background: #ffffff;
			border-radius: 14px;
			box-shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
			min-height: calc(100vh - 200px);
			overflow: hidden;
		}

		.products-section {
			padding: 20px 20px 24px 20px;
			border-right: 1px solid #e5e7eb; /* light divider */
			background: #ffffff;
		}

		/* Cart panel visually separated */
		.cart-section {
			background: #ffffff;
			color: #0f172a;
			padding: 0;
			border-radius: 0 14px 14px 0;
			box-shadow: inset 0 1px 0 rgba(2,6,23,0.02);
		}

		.product-grid {
			max-height: 450px;
			overflow-y: auto;
		}

		.product-grid::-webkit-scrollbar {
			width: 6px;
		}

		.product-grid::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 3px;
		}

		.product-card {
			background: #e8f0fe; /* softer light blue */
			color: #0f172a;
			border: 1px solid #dbeafe;
			border-radius: 12px;
			cursor: pointer;
			transition: transform 0.2s ease, box-shadow 0.2s ease;
			height: 180px; /* Increased height to fit more content */
			position: relative;
			overflow: hidden;
			box-shadow: 0 2px 6px rgba(2, 6, 23, 0.05);
		}

		.product-card:hover {
			transform: scale(1.02);
			box-shadow: 0 10px 20px rgba(2, 6, 23, 0.10);
		}

		.product-card img {
			width: 100%;
			height: 90px; /* Reduced height to make more room for text */
			object-fit: cover;
		}

		.product-info {
			padding: 8px 10px;
			text-align: center;
			height: 90px; /* Fixed height for the info section */
			display: flex;
			flex-direction: column;
			justify-content: space-between;
		}

		.product-name {
			font-size: 12px;
			font-weight: 600;
			margin-bottom: 3px;
			color: #0f172a;
			overflow: hidden;
			display: -webkit-box;
			-webkit-line-clamp: 2; /* Show 2 lines of text */
			-webkit-box-orient: vertical;
			line-height: 1.3;
			height: 32px; /* Fixed height for 2 lines */
		}

		.product-price {
			font-size: 14px;
			font-weight: 700;
			color: #facc15; /* soft gold */
		}

		.product-stock {
			font-size: 11px;
			color: #64748b; /* slate gray */
			margin-top: 2px;
			font-weight: 600;
		}

		.stock-badge {
			padding: 4px 8px;
			border-radius: 4px;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: 0.5px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
			position: absolute;
			top: 5px;
			right: 5px;
			z-index: 10;
		}

		.stock-badge-danger {
			background-color: #ef4444;
			color: white;
			animation: pulse-red 2s infinite;
		}

		.stock-badge-warning {
			background-color: #f59e0b;
			color: #1e293b;
		}

		.stock-badge-success {
			background-color: #10b981;
			color: white;
		}

		@keyframes pulse-red {
			0% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
			}
			70% {
				box-shadow: 0 0 0 6px rgba(239, 68, 68, 0);
			}
			100% {
				box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
			}
		}

		.product-card.out-of-stock {
			opacity: 0.7;
			filter: grayscale(50%);
		}

		.product-card.low-stock {
			border: 2px solid #f59e0b;
		}

		/* Variant styles */
		.variant-list {
			max-height: 300px;
			overflow-y: auto;
		}

		.variant-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 10px 15px;
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			margin-bottom: 8px;
			transition: all 0.2s ease;
		}

		.variant-item:hover {
			background-color: #f3f4f6;
			cursor: pointer;
		}

		.variant-item.disabled {
			opacity: 0.6;
			cursor: not-allowed;
			background-color: #f3f4f6;
		}

		.variant-size {
			font-weight: 600;
			font-size: 14px;
		}

		.variant-stock {
			font-size: 12px;
			color: #6b7280;
		}

		.variant-badge {
			padding: 3px 8px;
			border-radius: 4px;
			font-size: 10px;
			font-weight: 600;
			margin-left: 8px;
		}

		.variant-badge-danger {
			background-color: #ef4444;
			color: white;
		}

		.variant-badge-warning {
			background-color: #f59e0b;
			color: #1e293b;
		}

		.variant-badge-success {
			background-color: #10b981;
			color: white;
		}

		/* Direct variant popup */
		.variant-popup {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background-color: rgba(0, 0, 0, 0.5);
			z-index: 1000;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.variant-popup-content {
			background-color: white;
			border-radius: 8px;
			width: 90%;
			max-width: 400px;
			max-height: 90vh;
			display: flex;
			flex-direction: column;
			overflow: hidden;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
		}

		.variant-popup-header {
			padding: 16px;
			border-bottom: 1px solid #e5e7eb;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.variant-popup-header h5 {
			margin: 0;
			font-size: 18px;
			font-weight: 600;
		}

		.variant-popup-close {
			font-size: 24px;
			cursor: pointer;
			color: #6b7280;
		}

		.variant-popup-body {
			padding: 16px;
			overflow-y: auto;
			max-height: 60vh;
		}

		.variant-popup-footer {
			padding: 12px 16px;
			border-top: 1px solid #e5e7eb;
			display: flex;
			justify-content: flex-end;
		}

		/* Variant indicator */
		.variant-indicator {
			position: absolute;
			top: 5px;
			left: 5px;
			background-color: #6366f1;
			color: white;
			padding: 2px 6px;
			border-radius: 4px;
			font-size: 9px;
			font-weight: 600;
			z-index: 10;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.max-in-cart {
			position: relative;
			box-shadow: 0 0 0 2px #ef4444 !important;
			transition: all 0.2s ease;
		}

		.max-stock-badge {
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.cart-header {
			background: #1e3a8a; /* dark navy */
			color: #ffffff;
			padding: 20px;
			border-radius: 0 14px 0 0;
			border-bottom: 1px solid rgba(255,255,255,0.08);
		}

		.cart-items {
			height: 300px;
			overflow-y: auto;
			padding: 16px;
			background: #ffffff;
		}

		.cart-items::-webkit-scrollbar {
			width: 4px;
		}

		.cart-items::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 2px;
		}

		.cart-item {
			background: #f8fafc;
			border: 1px solid #e5e7eb;
			border-radius: 10px;
			padding: 12px;
			margin-bottom: 10px;
		}

		.cart-item-name {
			font-weight: 600;
			font-size: 14px;
			margin-bottom: 8px;
			color: #0f172a;
		}

		.cart-item-details {
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-size: 12px;
			color: #334155;
		}

		.quantity-controls {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.qty-btn {
			width: 28px;
			height: 28px;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			color: #1e3a8a;
			border-radius: 6px;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			transition: background 0.2s, box-shadow 0.2s;
		}

		.qty-btn:hover {
			background: #eff6ff;
			box-shadow: 0 2px 6px rgba(2, 6, 23, 0.06);
		}

		.cart-totals {
			background: #f8fafc;
			padding: 16px;
			border-top: 1px solid #e5e7eb;
		}

		.total-line {
			display: flex;
			justify-content: space-between;
			margin-bottom: 8px;
			font-size: 14px;
			color: #0f172a;
		}

		.total-line.grand-total {
			font-size: 18px;
			font-weight: 700;
			border-top: 1px solid #e5e7eb;
			padding-top: 10px;
			margin-top: 10px;
			color: #1e293b;
		}

		.payment-buttons {
			padding: 16px;
			background: #ffffff;
			border-top: 1px solid #e5e7eb;
			border-radius: 0 0 14px 0;
		}

		.payment-btn {
			width: 100%;
			padding: 12px;
			border: none;
			border-radius: 8px;
			font-weight: 600;
			cursor: pointer;
			transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease;
			margin-bottom: 8px;
		}

		.btn-clear { background: #ef4444; color: #ffffff; }
		.btn-hold { background: #fde68a; color: #1f2937; }
		.btn-recall { background: #bfdbfe; color: #1e3a8a; }
		.btn-checkout { 
			background: #4ade80; /* pastel green */
			color: #0f172a;
			font-size: 16px;
			padding: 15px;
		}

		.payment-btn:hover {
			transform: translateY(-1px);
			box-shadow: 0 6px 14px rgba(2, 6, 23, 0.10);
		}

		.search-bar {
			margin-bottom: 15px;
		}

		.search-input {
			width: 100%;
			padding: 10px 15px;
			border: 2px solid #e2e8f0;
			border-radius: 25px;
			outline: none;
			transition: border-color 0.2s, box-shadow 0.2s;
		}

		.search-input:focus {
			border-color: #93c5fd; /* soft blue */
			box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
		}

		.categories {
			display: flex;
			gap: 8px;
			margin-bottom: 15px;
			flex-wrap: wrap;
		}

		.category-btn {
			padding: 6px 12px;
			border: 2px solid #93c5fd;
			background: #ffffff;
			color: #1e3a8a;
			border-radius: 16px;
			cursor: pointer;
			transition: background 0.2s, color 0.2s, box-shadow 0.2s;
			font-size: 12px;
		}

		.category-btn.active {
			background: #dbeafe; /* light blue */
			color: #1e3a8a;
			box-shadow: inset 0 0 0 1px #93c5fd;
		}

		.empty-cart {
			text-align: center;
			color: #64748b;
			padding: 40px 20px;
		}

		.empty-cart i {
			font-size: 2.5rem;
			margin-bottom: 10px;
			opacity: 0.5;
		}

		/* Mobile Responsive Styles */
		@media (max-width: 992px) {
			.pos-grid {
				flex-direction: column;
			}
			
			.products-section {
				border-right: none;
				border-bottom: 1px solid #e5e7eb;
				padding: 12px;
			}
			
			.cart-section {
				border-radius: 0 0 14px 14px;
			}
			
			.cart-header {
				border-radius: 0;
				padding: 15px;
			}
			
			.product-grid {
				max-height: 350px;
			}
			
			.cart-items {
				height: 220px;
				padding: 12px;
			}
			
			.product-card {
				height: 140px;
			}
			
			.product-card img {
				height: 80px;
			}
			
			.payment-buttons {
				border-radius: 0 0 14px 14px;
				padding: 12px;
			}
			
			.cart-item {
				padding: 10px;
				margin-bottom: 8px;
			}
			
			.cart-totals {
				padding: 12px;
			}
		}
		
		@media (max-width: 576px) {
			.pos-main-container {
				padding: 8px;
			}
			
			.products-section {
				padding: 10px;
			}
			
			.product-grid {
				max-height: 280px;
			}
			
			.categories {
				gap: 4px;
				margin-bottom: 10px;
			}
			
			.category-btn {
				font-size: 10px;
				padding: 4px 8px;
			}
			
			.search-input {
				padding: 6px 10px;
				font-size: 13px;
			}
			
			.search-bar {
				margin-bottom: 10px;
			}
			
			.cart-header {
				padding: 12px;
			}
			
			.cart-header h4 {
				font-size: 1rem;
			}
			
			.cart-items {
				height: 180px;
				padding: 10px;
			}
			
			.cart-item {
				padding: 8px;
				margin-bottom: 6px;
			}
			
			.cart-item-name {
				font-size: 12px;
				margin-bottom: 6px;
			}
			
			.cart-item-details {
				font-size: 11px;
			}
			
			.qty-btn {
				width: 24px;
				height: 24px;
				font-size: 12px;
			}
			
			.cart-totals {
				padding: 10px;
			}
			
			.total-line {
				font-size: 12px;
				margin-bottom: 6px;
			}
			
			.total-line.grand-total {
				font-size: 16px;
			}
			
			.payment-buttons {
				padding: 10px;
			}
			
			.payment-btn {
				padding: 8px;
				font-size: 13px;
				margin-bottom: 6px;
			}
			
			.btn-checkout {
				padding: 10px;
				font-size: 14px;
			}
			
			.product-card {
				height: 120px;
			}
			
			.product-card img {
				height: 70px;
			}
			
			.product-info {
				padding: 6px;
			}
			
			.product-name {
				font-size: 10px;
				margin-bottom: 2px;
			}
			
			.product-price {
				font-size: 12px;
			}
			
			.product-stock {
				font-size: 9px;
				margin-top: 1px;
			}
		}
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'pos'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h1 class="h3 mb-0"><i class="bi bi-credit-card me-2"></i>Point of Sale</h1>
						<div class="d-flex gap-2">
							<button class="btn btn-outline-primary" onclick="showSalesHistory()">
								<i class="bi bi-clock-history me-1"></i>Sales History
							</button>
							<button class="btn btn-success" onclick="newTransaction()">
								<i class="bi bi-receipt me-1"></i>New Transaction
							</button>
						</div>
					</div>

					<div class="pos-main-container">
						<div class="row pos-grid g-0">
							<!-- Products Section -->
							<div class="col-lg-8 products-section">
								<div class="d-flex justify-content-between align-items-center mb-3">
									<h4 class="mb-0 text-dark">Products</h4>
									<small class="text-muted">Click products to add to cart</small>
								</div>

								<!-- Search Bar -->
								<div class="search-bar">
									<input type="text" class="search-input" placeholder="Search products..." id="productSearch">
								</div>

								<!-- Categories -->
								<div class="categories">
									<button class="category-btn active" data-category="">All</button>
									<?php
									$catStmt = $pdo->query("SELECT category_id, category_name FROM product_category");
									$categories = $catStmt->fetchAll();
									foreach ($categories as $cat):
									?>
									<button class="category-btn" data-category="<?= $cat['category_id']; ?>">
										<?= htmlspecialchars($cat['category_name']); ?>
									</button>
									<?php endforeach; ?>
								</div>

								<!-- Products Grid -->
								<div class="product-grid">
									<div class="row g-2" id="productsContainer">
										<?php
										$stmt = $pdo->query("SELECT p.*, c.category_name,
										COALESCE(vs.total_stock, p.stock) AS display_stock,
										vs.has_variants,
										vs.variant_details
										FROM products p 
										JOIN product_category c ON p.category_id = c.category_id 
										LEFT JOIN (
											SELECT 
												product_id,
												SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock,
												COUNT(*) AS has_variants,
												GROUP_CONCAT(
													CONCAT(variant_id, ':', size, ':', stock, ':', is_active)
												SEPARATOR '|') AS variant_details
											FROM product_variants
											GROUP BY product_id
										) vs ON vs.product_id = p.product_id
										-- Show all products regardless of stock status
										ORDER BY p.product_name");
										$products = $stmt->fetchAll();
										
										foreach ($products as $product):
										?>
										<?php 
										$displayStock = isset($product['display_stock']) ? $product['display_stock'] : $product['stock'];
										$stockClass = '';
										if ($displayStock <= 0) {
											$stockClass = 'out-of-stock';
										} elseif ($displayStock <= 5) {
											$stockClass = 'low-stock';
										}
										?>
										<div class="col-6 col-md-4 col-lg-3 product-item" data-category="<?= $product['category_id']; ?>" data-category-name="<?= strtolower($product['category_name']); ?>" data-name="<?= strtolower($product['product_name']); ?>" data-stock="<?= $displayStock; ?>">
											<?php 
											$hasVariants = isset($product['has_variants']) && $product['has_variants'] > 0;
											$variantDetails = isset($product['variant_details']) ? $product['variant_details'] : '';
											$escapedProductName = htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8');
											
											// Get variant data directly from the database for this product
											$variants = [];
											if ($hasVariants) {
												$variantStmt = $pdo->prepare("SELECT variant_id, size, stock, is_active FROM product_variants WHERE product_id = ? AND is_active = 1 ORDER BY size ASC");
												$variantStmt->execute([$product['product_id']]);
												$variants = $variantStmt->fetchAll();
											}
											
											$onClickFunction = $hasVariants ? 
												"showDirectVariants('variant-list-{$product['product_id']}')" : 
												"addToCart({$product['product_id']}, '{$escapedProductName}', {$product['price']}, {$displayStock})";
											?>
											<div class="card product-card <?= $stockClass ?>" onclick="<?= $onClickFunction ?>" style="position: relative;">
												<?php if ($hasVariants): ?>
													<span class="variant-indicator"><i class="bi bi-layers me-1"></i>Variants</span>
												<?php endif; ?>
												<img src="../uploads/products/<?= $product['image_url']; ?>" 
													 class="card-img-top" 
													 alt="<?= htmlspecialchars($product['product_name']); ?>"
													 onerror="this.src='../uploads/products/default.jpg'">
												<!-- Stock Badge -->
												<?php if ($displayStock <= 0): ?>
													<span class="stock-badge stock-badge-danger">OUT OF STOCK</span>
												<?php elseif ($displayStock <= 5): ?>
													<span class="stock-badge stock-badge-warning">LOW STOCK (<?= $displayStock; ?>)</span>
												<?php else: ?>
													<span class="stock-badge stock-badge-success">IN STOCK: <?= $displayStock; ?></span>
												<?php endif; ?>
												<div class="product-info">
													<div class="product-name">
														<?= htmlspecialchars($product['product_name']); ?>
													</div>
													<div class="product-price">
														₱<?= number_format($product['price'], 2); ?>
													</div>
													<div class="product-stock">
														Stock: <?= isset($product['display_stock']) ? $product['display_stock'] : $product['stock']; ?>
													</div>
												</div>
											</div>
										</div>
										
										<!-- Hidden variant list for this product -->
										<?php if ($hasVariants && count($variants) > 0): ?>
										<div id="variant-list-<?= $product['product_id'] ?>" class="variant-popup" style="display: none;">
											<div class="variant-popup-content">
												<div class="variant-popup-header">
													<h5><?= htmlspecialchars($product['product_name']) ?></h5>
													<span class="variant-popup-close" onclick="hideDirectVariants('variant-list-<?= $product['product_id'] ?>')">×</span>
												</div>
												<div class="variant-popup-body">
													<?php foreach ($variants as $variant): ?>
													<div class="variant-item <?= $variant['stock'] <= 0 ? 'disabled' : '' ?>" 
														 <?= $variant['stock'] > 0 ? "onclick=\"addToCartWithVariant({$product['product_id']}, '{$escapedProductName}', {$product['price']}, {$variant['variant_id']}, '{$variant['size']}', {$variant['stock']}); hideDirectVariants('variant-list-{$product['product_id']}')\"" : '' ?>>
														<div>
															<div class="variant-size"><?= htmlspecialchars($variant['size']) ?></div>
														</div>
														<div class="d-flex align-items-center">
															<div class="variant-stock">Stock: <?= $variant['stock'] ?></div>
															<?php if ($variant['stock'] <= 0): ?>
																<span class="variant-badge variant-badge-danger">OUT OF STOCK</span>
															<?php elseif ($variant['stock'] <= 5): ?>
																<span class="variant-badge variant-badge-warning">LOW STOCK</span>
															<?php else: ?>
																<span class="variant-badge variant-badge-success">IN STOCK</span>
															<?php endif; ?>
														</div>
													</div>
													<?php endforeach; ?>
												</div>
												<div class="variant-popup-footer">
													<button class="btn btn-secondary" onclick="hideDirectVariants('variant-list-<?= $product['product_id'] ?>')">Cancel</button>
												</div>
											</div>
										</div>
										<?php endif; ?>
										<?php endforeach; ?>
									</div>
								</div>
							</div>

							<!-- Cart Section -->
							<div class="col-lg-4 cart-section">
								<div class="cart-header">
									<h4 class="mb-1"><i class="bi bi-receipt me-2"></i>Current Sale</h4>
								</div>

								<!-- Cart Items -->
								<div class="cart-items">
									<div id="cartItems">
										<div class="empty-cart">
											<i class="bi bi-cart-x"></i>
											<h5>Empty Cart</h5>
											<p>Click products to add them</p>
										</div>
									</div>
								</div>

								<!-- Cart Totals -->
								<div class="cart-totals">
									<div class="total-line">
										<span>Subtotal:</span>
										<span id="subtotal">₱0.00</span>
									</div>
									<div class="total-line">
										<span>Tax (1%):</span>
										<span id="tax">₱0.00</span>
									</div>
									<div class="total-line grand-total">
										<span>TOTAL:</span>
										<span id="total" style="color: #facc15;">₱0.00</span>
									</div>
									<!-- Hidden element referenced by JS to avoid errors -->
									<div id="changeDisplay" style="display:none">₱0.00</div>
								</div>

								<!-- Payment Buttons -->
								<div class="payment-buttons">
    <button class="payment-btn btn-checkout" onclick="processPayment()">
        <i class="bi bi-credit-card me-2"></i>CHECKOUT
    </button>
    <button class="payment-btn btn-clear" onclick="clearCart()">
        <i class="bi bi-trash me-2"></i>Clear Cart
    </button>
</div>

							</div>
						</div>
					</div>
				</div>
			</main>
      </div>
	</div>

	<!-- ✅ Checkout Modal -->
	<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Checkout</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
	<div class="mb-3">
    <label class="form-label">Payment Method</label>
    <select id="paymentMethod" class="form-select">
        <option value="cash">Cash</option>
        <option value="bank">Bank Transfer</option>
        <option value="gcash">GCash</option>
        <option value="other">Card</option>
    </select>
</div>


	<div class="mb-3">
		<label class="form-label">Total</label>
		<input type="text" id="checkoutTotal" class="form-control" readonly>
	</div>
	<div class="mb-3">
		<label class="form-label">Cash</label>
		<input type="number" id="cashAmount" class="form-control" min="0" step="0.01">
	</div>
	<div class="mb-3">
		<label class="form-label">Change</label>
		<input type="text" id="checkoutChange" class="form-control" readonly>
	</div>
</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-success" onclick="completePayment()">Complete Payment</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Receipt Modal (Bootstrap) -->
	<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
	  <div class="modal-dialog modal-dialog-centered modal-sm">
		<div class="modal-content">
		  <div class="modal-header">
			<h5 class="modal-title" id="receiptModalLabel"><i class="bi bi-receipt"></i> Receipt</h5>
			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
		  </div>
		  <div class="modal-body">
			<div id="receiptContent" style="font-family: 'Courier New', monospace; font-size:12px;"></div>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
			<button type="button" class="btn btn-primary" onclick="printReceipt()">Print Receipt</button>
		  </div>
		</div>
	  </div>
	</div>

			<footer class="footer">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">
								&copy; 2025 <a class="text-muted" href="#"><strong>Sapin Bedsheets</strong></a>
							</p>
						</div>
						<div class="col-6 text-end">
							<ul class="list-inline">
								<li class="list-inline-item">
									<span class="text-muted">POS System v1.0</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- POS JavaScript -->
	<script>
		let cart = [];
		let transactionNumber = 1;

		// Initialize POS system
	document.addEventListener('DOMContentLoaded', function() {
		console.log('POS System initializing...');
		updateCartDisplay();
		loadCart();
		// Initialize product card states
		updateAllProductCards();
		// Refresh products every 10 seconds for more real-time stock updates
		setInterval(refreshProductStock, 10000);
	});

		// When the page is shown again (back/forward navigation), reload cart from server
		window.addEventListener('pageshow', function () {
			loadCart();
		});
		// Also handle visibility change
		document.addEventListener('visibilitychange', function() {
			if (document.visibilityState === 'visible') loadCart();
		});

		// Load existing cart from session
		function loadCart() {
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: 'action=get_cart'
			})
			.then(response => response.json())
			.then(data => {
				if (data.success && data.cart) {
					cart = data.cart;
					updateCartDisplay();
					// Update product card states based on loaded cart
					updateAllProductCards();
				} else {
					// If backend returns error, keep current cart but log it
					console.log('get_cart response:', data);
				}
			})
			.catch(error => {
				console.error('Error loading cart:', error);
			});
		}

		// Refresh product stock in real-time
		// If productId is provided, only refresh that specific product
		function refreshProductStock(productId = null) {
			const category = document.querySelector('.category-btn.active')?.getAttribute('data-category') || '';
			const search = document.getElementById('productSearch')?.value || '';
			
			// If a specific productId is provided, add it to the request
			let requestBody = `action=get_products&category=${encodeURIComponent(category)}&search=${encodeURIComponent(search)}`;
			if (productId) {
				requestBody += `&product_id=${productId}`;
			}
			
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: requestBody
			})
			.then(response => response.json())
			.then(data => {
				if (data.success && data.products) {
					// Update stock badges on product cards
					data.products.forEach(product => {
						const productItem = document.querySelector(`[data-category="${product.category_id}"][data-name="${product.product_name.toLowerCase()}"]`);
						if (productItem) {
							const stockBadge = productItem.querySelector('[style*="position: absolute"]');
							if (stockBadge) {
								if (product.stock <= 0) {
									stockBadge.innerHTML = '<span class="badge bg-danger">Out of Stock</span>';
								} else if (product.stock <= 5) {
									stockBadge.innerHTML = `<span class="badge bg-warning text-dark">Low Stock (${product.stock})</span>`;
								} else {
									stockBadge.innerHTML = `<span class="badge bg-success">Stock: ${product.stock}</span>`;
								}
							}
							// Update data attribute for stock validation
							productItem.setAttribute('data-stock', product.stock);
							
							// Update stock number in product-info section
							const stockInfo = productItem.querySelector('.product-stock');
							if (stockInfo) {
								stockInfo.textContent = `Stock: ${product.stock}`;
							}
							
							// Check if this product is at max stock in cart
							updateProductCardState(product.product_id, product.stock);
						}
					});
				}
			})
			.catch(error => {
				console.error('Error refreshing product stock:', error);
			});
		}
		
		// Update product card visual state based on cart quantity vs available stock
		function updateProductCardState(productId, availableStock) {
			// Find if this product is in cart
			const cartItem = cart.find(item => item.product_id == productId);
			const productCard = document.querySelector(`.product-card[onclick*="addToCart(${productId},"]`);
			
			if (productCard) {
				// Reset any previous styling
				productCard.classList.remove('max-in-cart');
				productCard.style.opacity = '1';
				productCard.style.cursor = 'pointer';
				
				// If product is in cart and at max stock
				if (cartItem && cartItem.quantity >= availableStock) {
					productCard.classList.add('max-in-cart');
					productCard.style.opacity = '0.7';
					productCard.style.cursor = 'not-allowed';
					
					// Add or update a badge showing max reached
					let maxBadge = productCard.querySelector('.max-stock-badge');
					if (!maxBadge) {
						maxBadge = document.createElement('div');
						maxBadge.className = 'max-stock-badge';
						maxBadge.style.position = 'absolute';
						maxBadge.style.bottom = '5px';
						maxBadge.style.left = '5px';
						maxBadge.style.zIndex = '10';
						maxBadge.style.backgroundColor = '#ef4444';
						maxBadge.style.color = 'white';
						maxBadge.style.padding = '2px 6px';
						maxBadge.style.borderRadius = '4px';
						maxBadge.style.fontSize = '10px';
						productCard.appendChild(maxBadge);
					}
					maxBadge.textContent = 'Max in Cart';
				} else {
					// Remove max badge if it exists
					const maxBadge = productCard.querySelector('.max-stock-badge');
					if (maxBadge) {
						maxBadge.remove();
					}
				}
			}
		}

		// Show direct variants popup
		function showDirectVariants(variantListId) {
			console.log('Showing direct variants:', variantListId);
			const variantPopup = document.getElementById(variantListId);
			if (variantPopup) {
				variantPopup.style.display = 'flex';
			}
		}

		// Hide direct variants popup
		function hideDirectVariants(variantListId) {
			console.log('Hiding direct variants:', variantListId);
			const variantPopup = document.getElementById(variantListId);
			if (variantPopup) {
				variantPopup.style.display = 'none';
			}
		}

		// Show variants in modal (legacy function, kept for compatibility)
		function showVariants(productId, variantDetailsStr, productName, basePrice) {
			console.log('Showing variants for:', productId, productName);
			console.log('Variant details string:', variantDetailsStr);
			
			// Set modal title and product info immediately
			document.getElementById('variantProductName').textContent = productName;
			document.getElementById('variantProductPrice').textContent = '₱' + basePrice.toFixed(2);
			
			// Show loading indicator in variant list
			const variantList = document.getElementById('variantList');
			variantList.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading variants...</p></div>';
			
			// Show the modal while we fetch fresh data
			const variantModal = new bootstrap.Modal(document.getElementById('variantModal'));
			variantModal.show();
			
			// Fetch fresh variant data from the server
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: `action=get_variants&product_id=${productId}`
			})
			.then(response => {
				console.log('Fetch response:', response);
				return response.json();
			})
			.then(data => {
				console.log('Fetch data:', data);
				if (data.success && data.variants) {
					// Use fresh variant data from server
					displayVariants(data.variants, productId, productName, basePrice);
				} else {
					// Fallback to cached data if server request fails
					console.warn('Failed to get fresh variant data, using cached data');
					
					// Parse variant details from cached string
					const variants = [];
					if (variantDetailsStr && variantDetailsStr !== '') {
						const variantItems = variantDetailsStr.split('|');
						variantItems.forEach(item => {
							const [variantId, size, stock, isActive] = item.split(':');
							variants.push({
								variantId: parseInt(variantId),
								size,
								stock: parseInt(stock),
								isActive: parseInt(isActive) === 1
							});
						});
					}
					displayVariants(variants, productId, productName, basePrice);
				}
			})
			.catch(error => {
				console.error('Error fetching variant data:', error);
				variantList.innerHTML = '<div class="alert alert-danger">Error loading variants. Please try again.</div>';
			});
		}
			
		// Display variants in the modal
		function displayVariants(variants, productId, productName, basePrice) {
			// Clear previous variants
			const variantList = document.getElementById('variantList');
			variantList.innerHTML = '';
			
			console.log('Displaying variants:', variants);
			
			// Check if there are any variants
			if (!variants || variants.length === 0) {
				variantList.innerHTML = '<div class="alert alert-info">No variants available for this product.</div>';
				return;
			}
			
			// Add variants to the list
			variants.forEach(variant => {
				if (!variant.isActive) return; // Skip inactive variants
				
				const variantItem = document.createElement('div');
				variantItem.className = 'variant-item' + (variant.stock <= 0 ? ' disabled' : '');
				
				// Only add click handler if stock > 0
				if (variant.stock > 0) {
					variantItem.onclick = function() {
						addToCartWithVariant(productId, productName, basePrice, variant.variantId, variant.size, variant.stock);
						bootstrap.Modal.getInstance(document.getElementById('variantModal')).hide();
					};
				}
				
				// Left side - size
				const leftDiv = document.createElement('div');
				const sizeDiv = document.createElement('div');
				sizeDiv.className = 'variant-size';
				sizeDiv.textContent = variant.size;
				leftDiv.appendChild(sizeDiv);
				
				// Right side - stock and badge
				const rightDiv = document.createElement('div');
				rightDiv.className = 'd-flex align-items-center';
				
				const stockDiv = document.createElement('div');
				stockDiv.className = 'variant-stock';
				stockDiv.textContent = 'Stock: ' + variant.stock;
				rightDiv.appendChild(stockDiv);
				
				// Add badge based on stock level
				const badgeSpan = document.createElement('span');
				badgeSpan.className = 'variant-badge';
				
				if (variant.stock <= 0) {
					badgeSpan.className += ' variant-badge-danger';
					badgeSpan.textContent = 'OUT OF STOCK';
				} else if (variant.stock <= 5) {
					badgeSpan.className += ' variant-badge-warning';
					badgeSpan.textContent = 'LOW STOCK';
				} else {
					badgeSpan.className += ' variant-badge-success';
					badgeSpan.textContent = 'IN STOCK';
				}
				
				rightDiv.appendChild(badgeSpan);
				
				// Add left and right divs to variant item
				variantItem.appendChild(leftDiv);
				variantItem.appendChild(rightDiv);
				
				// Add variant item to list
				variantList.appendChild(variantItem);
			});
		}
		
		// Add product with variant to cart
		function addToCartWithVariant(productId, productName, price, variantId, variantSize, availableStock) {
			console.log('Adding to cart with variant:', productId, productName, price, 'Variant:', variantId, variantSize, 'Stock:', availableStock);
			
			// Check if stock is available
			if (availableStock <= 0) {
				Swal.fire({
					icon: 'error',
					title: 'Out of Stock',
					html: `<div class="text-center">
						<i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
						<p class="mt-3"><strong>${productName} (${variantSize})</strong> is currently out of stock.</p>
						<p class="text-muted small">Please check back later or contact inventory management.</p>
					</div>`,
					confirmButtonColor: '#ef4444',
					showCancelButton: false,
					confirmButtonText: 'OK'
				});
				return;
			}
			
			// First, let's add to our local cart array immediately for instant feedback
			const existingItemIndex = cart.findIndex(item => item.product_id == productId && item.variant_id == variantId);
			let newQuantity = 1;
			let currentCartQuantity = 0;
			
			if (existingItemIndex > -1) {
				// Item exists, check if we can increase quantity
				currentCartQuantity = cart[existingItemIndex].quantity;
				newQuantity = currentCartQuantity + 1;
				
				// Check against available stock
				if (newQuantity > availableStock) {
					Swal.fire({
						icon: 'warning',
						title: 'Limited Stock Available',
						html: `<div class="text-center">
							<i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
							<h5 class="mt-3"><strong>${productName} (${variantSize})</strong></h5>
							<div class="d-flex justify-content-center gap-3 my-3">
								<div class="text-center">
									<span class="d-block fw-bold fs-4">${availableStock}</span>
									<small class="text-muted">Available</small>
								</div>
								<div class="text-center">
									<span class="d-block fw-bold fs-4">${currentCartQuantity}</span>
									<small class="text-muted">In Cart</small>
								</div>
								<div class="text-center">
									<span class="d-block fw-bold fs-4 text-danger">${newQuantity}</span>
									<small class="text-muted">Requested</small>
								</div>
							</div>
							<p class="alert alert-danger">Cannot exceed available stock!</p>
						</div>`,
						confirmButtonColor: '#f59e0b',
						confirmButtonText: 'OK'
					});
					return;
				}
				cart[existingItemIndex].quantity = newQuantity;
				cart[existingItemIndex].total_price = cart[existingItemIndex].quantity * cart[existingItemIndex].unit_price;
			} else {
				// New item, add to cart
				cart.push({
					product_id: productId,
					product_name: productName + ' (' + variantSize + ')',
					unit_price: price,
					quantity: 1,
					total_price: price,
					stock: availableStock,
					variant_id: variantId,
					variant_size: variantSize
				});
			}
			
			// Update cart display
			updateCartDisplay();
			
			// Send to server
			const formData = new FormData();
			formData.append('action', 'add_to_cart');
			formData.append('product_id', productId);
			formData.append('quantity', 1);
			formData.append('variant_id', variantId);
			
			fetch('backend/pos_handler.php', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (!data.success) {
					console.error('Server error:', data.message);
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: data.message
					});
				}
			})
			.catch(error => {
				console.error('Network error:', error);
			});
		}
		
		// Add product to cart with stock validation
		function addToCart(productId, productName, price, availableStock) {
			console.log('Adding to cart:', productId, productName, price, 'Stock:', availableStock);
			
			// Check if stock is available
			if (availableStock <= 0) {
				// Flash the product card with a red border
				const productCard = document.querySelector(`.product-item[data-stock="${availableStock}"] .product-card`);
				if (productCard) {
					productCard.style.boxShadow = '0 0 0 3px #ef4444';
					setTimeout(() => {
						productCard.style.boxShadow = '';
					}, 1500);
				}
				
				Swal.fire({
					icon: 'error',
					title: 'Out of Stock',
					html: `<div class="text-center">
						<i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
						<p class="mt-3"><strong>${productName}</strong> is currently out of stock.</p>
						<p class="text-muted small">Please check back later or contact inventory management.</p>
					</div>`,
					confirmButtonColor: '#ef4444',
					showCancelButton: false,
					confirmButtonText: 'OK'
				});
				return;
			}
			
			// First, let's add to our local cart array immediately for instant feedback
			const existingItemIndex = cart.findIndex(item => item.product_id == productId);
			let newQuantity = 1;
			let currentCartQuantity = 0;
			
			if (existingItemIndex > -1) {
				// Item exists, check if we can increase quantity
				currentCartQuantity = cart[existingItemIndex].quantity;
				newQuantity = currentCartQuantity + 1;
				
				// Check against available stock
				if (newQuantity > availableStock) {
					// Flash the product card with a warning border
					const productCard = document.querySelector(`.product-item[data-stock="${availableStock}"] .product-card`);
					if (productCard) {
						productCard.style.boxShadow = '0 0 0 3px #f59e0b';
						setTimeout(() => {
							productCard.style.boxShadow = '';
						}, 1500);
					}
					
					Swal.fire({
						icon: 'warning',
						title: 'Limited Stock Available',
						html: `<div class="text-center">
							<i class="bi bi-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
							<h5 class="mt-3"><strong>${productName}</strong></h5>
							<div class="d-flex justify-content-center gap-3 my-3">
								<div class="text-center">
									<span class="d-block fw-bold fs-4">${availableStock}</span>
									<small class="text-muted">Available</small>
								</div>
								<div class="text-center">
									<span class="d-block fw-bold fs-4">${currentCartQuantity}</span>
									<small class="text-muted">In Cart</small>
								</div>
								<div class="text-center">
									<span class="d-block fw-bold fs-4 text-danger">${newQuantity}</span>
									<small class="text-muted">Requested</small>
								</div>
							</div>
							<p class="alert alert-danger">Cannot exceed available stock!</p>
						</div>`,
						confirmButtonColor: '#f59e0b',
						confirmButtonText: 'OK'
					});
					return;
				}
				cart[existingItemIndex].quantity = newQuantity;
				cart[existingItemIndex].total_price = cart[existingItemIndex].quantity * cart[existingItemIndex].unit_price;
			} else {
				// New item, add to cart - check if even 1 unit exceeds stock
				if (1 > availableStock) {
					// Flash the product card with a red border
					const productCard = document.querySelector(`.product-item[data-stock="${availableStock}"] .product-card`);
					if (productCard) {
						productCard.style.boxShadow = '0 0 0 3px #ef4444';
						setTimeout(() => {
							productCard.style.boxShadow = '';
						}, 1500);
					}
					
					Swal.fire({
						icon: 'error',
						title: 'Out of Stock',
						html: `<div class="text-center">
							<i class="bi bi-x-circle text-danger" style="font-size: 3rem;"></i>
							<p class="mt-3"><strong>${productName}</strong> is out of stock.</p>
							<p class="text-muted small">Please check back later or contact inventory management.</p>
						</div>`,
						confirmButtonColor: '#ef4444',
						confirmButtonText: 'OK'
					});
					return;
				}
				cart.push({
					product_id: productId,
					product_name: productName,
					unit_price: price,
					quantity: 1,
					total_price: price,
					stock: availableStock
				});
			}
			
			// Update display immediately
			updateCartDisplay();
			
			// Update product card visual state
			updateProductCardState(productId, availableStock);
			
			// Show success message
			const Toast = Swal.mixin({
				toast: true,
				position: 'top-end',
				showConfirmButton: false,
				timer: 1000,
				timerProgressBar: true
			});
			
			Toast.fire({
				icon: 'success',
				title: 'Added to cart!'
			});

			// Then sync with backend
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: `action=add_to_cart&product_id=${productId}&quantity=1`
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					cart = data.cart; // Update with backend response
					updateCartDisplay();
					// Refresh product stock immediately to reflect changes
					refreshProductStock();
				} else {
					console.error('Backend error:', data.message);
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: data.message || 'Failed to add item to cart'
					});
				}
			})
			.catch(error => {
				console.error('Error syncing with backend:', error);
			});
		}

		// Update cart display
		function updateCartDisplay() {
			console.log('Updating cart display, items:', cart.length);
			const cartContainer = document.getElementById('cartItems');
			
			if (!cartContainer) {
				console.error('Cart container not found!');
				return;
			}
			
			if (!cart || cart.length === 0) {
				cartContainer.innerHTML = `
					<div class="empty-cart">
						<i class="bi bi-cart-x"></i>
						<h5>Empty Cart</h5>
						<p>Click products to add them</p>
					</div>
				`;
			} else {
				let cartHTML = '';
				cart.forEach((item) => {
					cartHTML += `
						<div class="cart-item">
							<div class="cart-item-name">${item.product_name}</div>
							<div style="font-size: 10px; color: #64748b; margin-bottom: 4px;">
								Available: ${item.stock} | In cart: ${item.quantity}
							</div>
							<div class="cart-item-details">
								<div class="quantity-controls">
									<button class="qty-btn" onclick="updateQuantity(${item.product_id}, ${item.quantity - 1}, ${item.variant_id || 'null'})">
										<i class="bi bi-dash"></i>
									</button>
									<span class="quantity">${item.quantity}</span>
									<button class="qty-btn" onclick="updateQuantity(${item.product_id}, ${item.quantity + 1}, ${item.variant_id || 'null'})">
										<i class="bi bi-plus"></i>
									</button>
								</div>
								<div style="text-align: right;">
									<div style="font-weight: 600; color: #facc15; margin-bottom: 4px;">₱${parseFloat(item.total_price).toFixed(2)}</div>
									<button class="qty-btn" onclick="removeFromCart(${item.product_id}, ${item.variant_id || 'null'})" style="color: #e53e3e;">
										<i class="bi bi-trash"></i>
									</button>
								</div>
							</div>
						</div>
					`;
				});
				cartContainer.innerHTML = cartHTML;
			}
			
			updateCartTotals();
			
			// Update all product cards
			updateAllProductCards();
		}
		
		// Update all product cards based on cart state
		function updateAllProductCards() {
			const productCards = document.querySelectorAll('.product-card');
			productCards.forEach(card => {
				const onclick = card.getAttribute('onclick');
				if (onclick) {
					// Extract product ID and stock from onclick attribute
					const match = onclick.match(/addToCart\((\d+),\s*'[^']*',\s*(\d+(?:\.\d+)?),\s*(\d+)\)/);
					if (match) {
						const productId = parseInt(match[1]);
						const availableStock = parseInt(match[3]);
						updateProductCardState(productId, availableStock);
					}
				}
			});
		}

		// Update cart totals
		function updateCartTotals() {
			if (!cart || cart.length === 0) {
				document.getElementById('subtotal').textContent = '₱0.00';
				document.getElementById('tax').textContent = '₱0.00';
				document.getElementById('total').textContent = '₱0.00';
				const cd = document.getElementById('changeDisplay');
				if (cd) cd.textContent = '₱0.00';
				return;
			}

			const subtotal = cart.reduce((sum, item) => sum + parseFloat(item.total_price || 0), 0);
			const tax = subtotal * 0.01; // Changed from 12% to 1% VAT
			const total = subtotal + tax;

			document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
			document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
			document.getElementById('total').textContent = '₱' + total.toFixed(2);
			
			// Recalculate change whenever total changes
			calculateChange();
		}

		// Update quantity
		function updateQuantity(productId, newQuantity, variantId = null) {
			console.log('Updating quantity:', productId, newQuantity, 'Variant:', variantId);
			if (newQuantity <= 0) {
				removeFromCart(productId, variantId);
				return;
			}

			// Update local cart - find the item with matching product ID and variant ID if provided
			let itemIndex;
			if (variantId) {
				itemIndex = cart.findIndex(item => item.product_id == productId && item.variant_id == variantId);
			} else {
				itemIndex = cart.findIndex(item => item.product_id == productId && !item.variant_id);
			}
			
			if (itemIndex > -1) {
				// Check if new quantity exceeds available stock
				const availableStock = cart[itemIndex].stock;
				if (newQuantity > availableStock) {
					Swal.fire({
						icon: 'warning',
						title: 'Insufficient Stock',
						html: `<p><strong>${cart[itemIndex].product_name}</strong></p><p>Available Stock: ${availableStock}</p><p>Requested quantity: ${newQuantity}</p><p style="color: red;"><strong>Cannot exceed available stock!</strong></p>`,
						confirmButtonColor: '#f59e0b'
					});
					return;
				}
				cart[itemIndex].quantity = newQuantity;
				cart[itemIndex].total_price = cart[itemIndex].quantity * cart[itemIndex].unit_price;
				updateCartDisplay();
				
				// Update product card visual state
				updateProductCardState(productId, availableStock);
			}

			// Sync with backend
			const params = new URLSearchParams();
			params.append('action', 'update_cart_item');
			params.append('product_id', productId);
			params.append('quantity', newQuantity);
			if (variantId && variantId !== 'null') {
				params.append('variant_id', variantId);
			}
			
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: params.toString()
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					cart = data.cart;
					updateCartDisplay();
					// Refresh product stock immediately to reflect changes
					refreshProductStock();
				}
			})
			.catch(error => {
				console.error('Error updating quantity:', error);
			});
		}

		// Remove from cart
		function removeFromCart(productId, variantId = null) {
			console.log('Removing from cart:', productId, 'Variant:', variantId);
			// Find the product in cart to get its stock before removing
			let cartItem;
			if (variantId) {
				cartItem = cart.find(item => item.product_id == productId && item.variant_id == variantId);
			} else {
				cartItem = cart.find(item => item.product_id == productId && !item.variant_id);
			}
			
			let availableStock = 0;
			if (cartItem) {
				availableStock = cartItem.stock;
			} else {
				// Try to find the product item in the DOM
				const productCard = document.querySelector(`.product-card[onclick*="addToCart(${productId},"]`);
				if (productCard) {
					const onclick = productCard.getAttribute('onclick');
					if (onclick) {
						const match = onclick.match(/addToCart\(\d+,\s*'[^']*',\s*\d+(?:\.\d+)?,\s*(\d+)\)/);
						if (match) {
							availableStock = parseInt(match[1]);
						}
					}
				}
			}
			
			// Update local cart
			if (variantId && variantId !== 'null') {
				// Filter out specific variant
				cart = cart.filter(item => !(item.product_id == productId && item.variant_id == variantId));
			} else {
				// Filter out non-variant product
				cart = cart.filter(item => !(item.product_id == productId && !item.variant_id));
			}
			updateCartDisplay();
			
			// Update product card visual state
			updateProductCardState(productId, availableStock);

			// Send to server
			const params = new URLSearchParams();
			params.append('action', 'remove_from_cart');
			params.append('product_id', productId);
			if (variantId && variantId !== 'null') {
				params.append('variant_id', variantId);
			}
			
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: params.toString()
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					cart = data.cart;
					updateCartDisplay();
					// Refresh product stock immediately to reflect changes
					refreshProductStock();
				}
			})
			.catch(error => {
				console.error('Error removing item:', error);
			});
		}

		// Clear cart
		function clearCart() {
			if (!cart || cart.length === 0) {
				Swal.fire({
					icon: 'info',
					title: 'Cart is Empty!',
					text: 'No items to clear.'
				});
				return;
			}

			Swal.fire({
				title: 'Clear All Items?',
				text: 'This will remove all items from the cart.',
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#e53e3e',
				cancelButtonColor: '#6c757d',
				confirmButtonText: 'Yes, Clear All!',
				cancelButtonText: 'Cancel'
			}).then((result) => {
				if (result.isConfirmed) {
					cart = [];
					updateCartDisplay();
					
					// Clear cash amount field
					const cashInput = document.getElementById('cashAmount');
					if (cashInput) cashInput.value = 0;
					calculateChange();
					
					// Sync with backend
					fetch('backend/pos_handler.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: 'action=clear_cart'
					}).then(()=> {
						// Reload to confirm server cleared
						loadCart();
						// Refresh product stock immediately to reflect changes
						refreshProductStock();
					});

					const Toast = Swal.mixin({
						toast: true,
						position: 'top-end',
						showConfirmButton: false,
						timer: 1500
					});
					
					Toast.fire({
						icon: 'success',
						title: 'Cart cleared!'
					});
				}
			});
		}

		// Process payment
		function processPayment() {
			if (!cart || cart.length === 0) {
				Swal.fire({
					icon: 'warning',
					title: 'Cart Empty!',
					text: 'Please add items to cart before processing payment.'
				});
				return;
			}

			showPaymentModal();
		}

		// Show payment modal
		function showPaymentModal() {
            const total = parseFloat(document.getElementById('total').textContent.replace('₱', '').replace(',', '')) || 0;

            // Set total in the modal
            document.getElementById('checkoutTotal').value = `₱${total.toFixed(2)}`;

            // Clear cash + change inputs every time modal opens
            document.getElementById('cashAmount').value = '';
            document.getElementById('checkoutChange').value = '';

            // Show the Bootstrap modal
            const modal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            modal.show();

           // Add listener for cash input (calculate change automatically)
           const cashInput = document.getElementById('cashAmount');
           cashInput.oninput = function () {
              let cash = parseFloat(cashInput.value) || 0;
              let change = cash - total;
              document.getElementById('checkoutChange').value = change >= 0 ? `₱${change.toFixed(2)}` : '₱0.00';
           };
        }

        // Complete payment function
        function completePayment() {
			const total = parseFloat(document.getElementById('total').textContent.replace('₱', '').replace(',', '')) || 0;
			const cash = parseFloat(document.getElementById('cashAmount').value) || 0;

			if (cash < total) {
				Swal.fire({
					icon: 'error',
					title: 'Insufficient Cash',
					text: 'Cash provided is less than total amount.'
				});
				return;
			}

			Swal.fire({
				title: 'Confirm Payment',
				html: `
					<div class="text-center">
						<h4>Payment Summary</h4>
						<p>Total: ₱${total.toFixed(2)}</p>
						<p>Cash: ₱${cash.toFixed(2)}</p>
						<p>Change: ₱${(cash - total).toFixed(2)}</p>
					</div>
				`,
				showCancelButton: true,
				confirmButtonText: 'Complete Payment',
				cancelButtonText: 'Cancel'
			}).then((result) => {
				if (result.isConfirmed) {
					processTransaction({ paymentMethod: document.getElementById('paymentMethod').value || 'cash', amountPaid: cash });
				}
			});
		}

	// Process transaction (sends cart_items as JSON)
function processTransaction(paymentData) {
    if (!cart || cart.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cart Empty!',
            text: 'No items in cart to process.'
        });
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'process_sale');
    formData.append('payment_method', paymentData.paymentMethod);
    formData.append('amount_payment', paymentData.amountPaid);
    formData.append('cart_items', JSON.stringify(cart)); // send cart items

    fetch('backend/pos_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide checkout modal if open
            const checkoutModalEl = document.getElementById('checkoutModal');
            const checkoutModalInstance = bootstrap.Modal.getInstance(checkoutModalEl);
            if (checkoutModalInstance) checkoutModalInstance.hide();

            // Clear local cart and update UI
            cart = [];
            updateCartDisplay();
            document.getElementById('cashAmount').value = 0;
            calculateChange();

            // Refresh product stock immediately to reflect changes
            refreshProductStock();

            // Clear backend session cart
            fetch('backend/pos_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=clear_cart'
            }).then(() => {
                // Reload cart to ensure empty
                loadCart();
            });

            // Show receipt modal
            if (data.receipt_data) {
                showReceiptModal(data.receipt_data);
            } else {
                Swal.fire({
                    icon: 'success',
                    title: 'Payment Successful!',
                    text: data.message || 'Transaction completed successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Payment Failed!',
                text: data.message || 'Failed to process transaction'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: 'Payment processing failed'
        });
    });
}


		// Show receipt in the Bootstrap modal
		function showReceiptModal(receiptData) {
			// Build receipt HTML
			const itemsHTML = (receiptData.items || []).map(item => {
				const qty = item.quantity;
				const name = item.product_name || item.productName || item.name || '';
				const total = parseFloat(item.total_price || item.totalPrice || 0).toFixed(2);
				return `<div style="display:flex;justify-content:space-between;margin:3px 0;"><span>${qty} x ${name}</span><span>₱${total}</span></div>`;
			}).join('');

			const receiptHTML = `
				<div style="text-align:center;margin-bottom:8px;">
					<h4 style="margin:0;">SAPIN</h4>
					<div style="font-size:11px;">140 Rose Street</div>
					<div style="font-size:11px;">Brgy. Paciano Rizal, Bay, Laguna</div>
				</div>
				<div style="font-size:12px;border-bottom:1px dashed #000;padding-bottom:6px;">
					<div style="display:flex;justify-content:space-between;"><span>Check:</span><strong>${receiptData.sale_number || ''}</strong></div>
					<div>Date: ${new Date(receiptData.sale_date || Date.now()).toLocaleString()}</div>
					<div>Cashier: ${receiptData.cashier_name || ''}</div>
				</div>
				<div style="margin:8px 0;">
					${itemsHTML}
				</div>
				<div style="border-top:1px dashed #000;padding-top:6px;">
					<div style="display:flex;justify-content:space-between;"><span>Sub Total:</span><span>₱${parseFloat(receiptData.subtotal || receiptData.subTotal || 0).toFixed(2)}</span></div>
					<div style="display:flex;justify-content:space-between;"><span>Sales Tax:</span><span>₱${parseFloat(receiptData.tax_amount || 0).toFixed(2)}</span></div>
					<div style="display:flex;justify-content:space-between;font-weight:bold;"><span>Total:</span><span>₱${parseFloat(receiptData.total_amount || 0).toFixed(2)}</span></div>
				</div>
				<div style="text-align:center;margin-top:10px;font-size:11px;">
					Thank you for your purchase!
				</div>
			`;

			document.getElementById('receiptContent').innerHTML = receiptHTML;

			const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
			receiptModal.show();
		}

		// Print receipt content only (open print window)
		function printReceipt() {
			const content = document.getElementById('receiptContent').innerHTML;
			const printWindow = window.open('', '', 'width=400,height=600');
			printWindow.document.write('<html><head><title>Receipt</title>');
			printWindow.document.write('<style>body{font-family:Courier, monospace;font-size:12px;padding:10px;} .center{text-align:center;}</style>');
			printWindow.document.write('</head><body>');
			printWindow.document.write(content);
			printWindow.document.write('</body></html>');
			printWindow.document.close();
			printWindow.focus();
			printWindow.print();
			// optionally auto close after printing - do not force close in case user cancels print
			// setTimeout(() => printWindow.close(), 1000);
		}

		// Search functionality
		document.getElementById('productSearch').addEventListener('input', function() {
			const searchTerm = this.value.toLowerCase();
			const products = document.querySelectorAll('.product-item');
			
			products.forEach(product => {
				const productName = product.dataset.name;
				if (productName.includes(searchTerm)) {
					product.style.display = 'block';
				} else {
					product.style.display = 'none';
				}
			});
		});

		// Category filter
		document.querySelectorAll('.category-btn').forEach(btn => {
			btn.addEventListener('click', function() {
				// Remove active class from all buttons
				document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
				// Add active class to clicked button
				this.classList.add('active');
				
				const category = this.dataset.category;
				const categoryName = this.textContent.trim().toLowerCase();
				const products = document.querySelectorAll('.product-item');
				
				console.log('Filtering by category:', category, 'Category name:', categoryName);
				
				// Count products for debugging
				let visibleCount = 0;
				let totalCount = 0;
				
				products.forEach(product => {
					totalCount++;
					console.log('Product:', product.dataset.name, 
						'Category ID:', product.dataset.category, 
						'Category Name:', product.dataset.categoryName,
						'Stock:', product.dataset.stock);
					
					if (category === '' || product.dataset.category === category) {
						product.style.display = 'block';
						visibleCount++;
					} else {
						product.style.display = 'none';
					}
				});
				
				console.log(`Showing ${visibleCount} out of ${totalCount} products`);
				
				// Add a message if no products are found, but without the explanatory text
				const noProductsMsg = document.getElementById('noProductsMessage');
				if (visibleCount === 0) {
					if (!noProductsMsg) {
						const msg = document.createElement('div');
						msg.id = 'noProductsMessage';
						msg.className = 'alert alert-info mt-3 text-center';
						msg.innerHTML = `<i class="bi bi-info-circle me-2"></i>No products found in the "${this.textContent.trim()}" category.`;
						document.getElementById('productsContainer').appendChild(msg);
					}
				} else if (noProductsMsg) {
					noProductsMsg.remove();
				}
			});
		});

		// Cash amount calculation
		const cashAmountEl = document.getElementById('cashAmount');
		if (cashAmountEl) {
			cashAmountEl.addEventListener('input', function() {
				calculateChange();
			});
		}

		// Calculate change
		function calculateChange() {
			const total = parseFloat(document.getElementById('total').textContent.replace('₱', '') || 0);
			const cashAmount = parseFloat(document.getElementById('cashAmount').value || 0);
			const change = cashAmount - total;
			const checkoutChange = document.getElementById('checkoutChange');
			if (checkoutChange) checkoutChange.value = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');

			const changeElement = document.getElementById('changeDisplay');
			if (changeElement) {
				changeElement.textContent = '₱' + (change >= 0 ? change.toFixed(2) : '0.00');
				if (cashAmount >= total && total > 0) {
					changeElement.style.color = '#4ade80'; // Green
				} else if (total > 0) {
					changeElement.style.color = '#f87171'; // Red
				} else {
					changeElement.style.color = '#334155'; // Default dark slate
				}
			}
		}

		// Sales History function - redirect to sales_history.php
		function showSalesHistory() {
			window.location.href = 'sales_history.php';
		}

		// Show receipt from history
		function showHistoryReceipt(saleId) {
			fetch('backend/pos_handler.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: `action=get_sale_receipt&sale_id=${saleId}`
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					// The get_sale_receipt returns receipt_data
					showReceiptModal(data.receipt_data);
				} else {
					Swal.fire({
						icon: 'error',
						title: 'Error!',
						text: data.message || 'Failed to load receipt'
					});
				}
			})
			.catch(error => {
				console.error('Error:', error);
				Swal.fire({
					icon: 'error',
					title: 'Error!',
					text: 'Failed to load receipt'
				});
			});
		}

		// New Transaction function
		function newTransaction() {
			if (cart.length > 0) {
				Swal.fire({
					title: 'Current Transaction Exists',
					text: 'You have items in the current cart. What would you like to do?',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: 'Clear Cart & Start New',
					cancelButtonText: 'Continue Current'
				}).then((result) => {
					if (result.isConfirmed) {
						// Clear local cart and server-side cart
						cart = [];
						updateCartDisplay();
						fetch('backend/pos_handler.php', {
							method: 'POST',
							headers: {'Content-Type': 'application/x-www-form-urlencoded'},
							body: 'action=clear_cart'
						}).then(()=>loadCart());

						Swal.fire({
							icon: 'success',
							title: 'New Transaction Started!',
							text: 'Cart has been cleared',
							timer: 1500,
							showConfirmButton: false
						});
					}
				});
			} else {
				Swal.fire({
					icon: 'info',
					title: 'Ready for New Transaction',
					text: 'Cart is already empty. You can start adding products.',
					timer: 1500,
					showConfirmButton: false
				});
			}
		}
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

<!-- Variant Modal -->
<div class="modal fade" id="variantModal" tabindex="-1" aria-labelledby="variantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variantModalLabel">Select Variant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="product-info-header mb-3">
                    <h6 id="variantProductName" class="mb-1"></h6>
                    <div id="variantProductPrice" class="text-primary fw-bold"></div>
                </div>
                <div class="variant-list" id="variantList">
                    <!-- Variants will be populated here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
