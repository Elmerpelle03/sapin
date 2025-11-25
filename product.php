<?php 
    require ('config/db.php');
    session_start();
    require ('config/details_checker.php');
    require('config/session_disallow_courier.php');

    
    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }

    if (!isset($_GET['id']) || empty($_GET['id'])) {
        header("Location: index.php");
        exit();
    }

    $id = $_GET['id'];
    
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

    // Get product data with variant stock information
    $stmt = $pdo->prepare("SELECT p.*, c.category_name, 
                          COALESCE(vs.total_stock, p.stock) AS display_stock,
                          vs.has_variants
                       FROM products p 
                       JOIN product_category c ON p.category_id = c.category_id 
                       LEFT JOIN (
                           SELECT product_id,
                                  SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock,
                                  COUNT(*) AS has_variants
                           FROM product_variants
                           GROUP BY product_id
                       ) vs ON vs.product_id = p.product_id
                       WHERE p.product_id = :product_id");
    $stmt->execute([':product_id' => $id]);
    $product_data = $stmt->fetch();

    $stmt_ratings = $pdo->prepare("
        SELECT ir.rating, ir.comment, CONCAT(ud.firstname, ' ', ud.lastname) as fullname, ir.created_at
        FROM item_ratings ir
        JOIN users u ON ir.user_id = u.user_id
        JOIN userdetails ud ON ir.user_id = ud.user_id
        WHERE ir.product_id = :product_id
        ORDER BY ir.created_at DESC
    ");
    $stmt_ratings->execute([':product_id' => $id]);
    $ratings_data = $stmt_ratings->fetchAll();

    // Compute average rating
    $stmt_avg = $pdo->prepare("SELECT AVG(rating) as average_rating FROM item_ratings WHERE product_id = :product_id");
    $stmt_avg->execute([':product_id' => $id]);
    $avg_result = $stmt_avg->fetchColumn();
    $average_rating = $avg_result ? round($avg_result, 1) : 0;
    
    // Get similar products (same category, excluding current product)
    $stmt_similar = $pdo->prepare("
        SELECT p.*, c.category_name, 
               IFNULL(AVG(ir.rating), 0) AS avg_rating,
               IFNULL(SUM(oi.quantity), 0) AS total_sold
        FROM products p
        JOIN product_category c ON p.category_id = c.category_id
        LEFT JOIN item_ratings ir ON ir.product_id = p.product_id
        LEFT JOIN order_items oi ON p.product_id = oi.product_id
        WHERE p.category_id = :category_id 
        AND p.product_id != :current_id
        AND p.stock > 0
        GROUP BY p.product_id
        ORDER BY RAND()
        LIMIT 4
    ");
    $stmt_similar->execute([
        ':category_id' => $product_data['category_id'],
        ':current_id' => $id
    ]);
    $similar_products = $stmt_similar->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product_data['product_name'];?> - Sapin Bedsheets</title>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sapin Bedsheets</title>
    
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
            --bs-primary: #2563eb;
        }
        
        body {
            background: linear-gradient(120deg, var(--light-bg) 0%, #ffffff 100%);
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
        
        .btn-danger:disabled {
            opacity: 0.8;
            cursor: not-allowed;
        }
        
        .input-group .btn:disabled,
        .input-group input:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f3f4f6;
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
            box-shadow: 0 4px 24px 0 rgba(80,80,150,0.1);
        }
    </style>
    <style>
        .product-detail-img {
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: box-shadow 0.3s ease;
        }
        
        .product-detail-img:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .product-info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        
        .product-info-card .section {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .product-info-card .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .product-info-card::-webkit-scrollbar {
            width: 6px;
        }
        
        .product-info-card::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }
        
        .product-info-card::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        .product-info-card::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .spec-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            text-align: center;
        }
        
        .spec-box .label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }
        
        .spec-box .value {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .price-display {
            background: #f8fafc;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .quantity-control {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .quantity-control button {
            border: none;
            background: #f8fafc;
            transition: background 0.2s ease;
        }
        
        .quantity-control button:hover {
            background: #e5e7eb;
        }
        
        .quantity-control input {
            border: none;
            border-left: 1px solid #e5e7eb;
            border-right: 1px solid #e5e7eb;
            font-weight: 500;
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
            .container {
                padding: 0 1rem;
            }
            .col-md-5, .col-md-7 {
                margin-bottom: 2rem;
            }
            .col-md-5 img {
                height: 300px !important;
            }
            .display-5 {
                font-size: 1.8rem !important;
            }
            .fs-3 {
                font-size: 1.5rem !important;
            }
            .btn-lg {
                padding: 0.8rem 2rem;
                font-size: 1rem;
            }
            .navbar-brand span {
                font-size: 1.1rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .col-md-5 img {
                height: 250px !important;
            }
            .display-5 {
                font-size: 1.5rem !important;
            }
            .fs-3 {
                font-size: 1.3rem !important;
            }
            .btn-lg {
                padding: 0.7rem 1.5rem;
                font-size: 0.9rem;
            }
            .badge {
                font-size: 0.7rem;
            }
            .navbar-brand span {
                font-size: 1rem !important;
            }
            .card-body {
                padding: 1rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    

    <?php $active = 'shop'; ?>
    <?php include 'includes/navbar_customer.php'; ?>

    <div class="container py-5">
        <a href="shop.php" class="btn btn-outline-primary mb-4" style="border-radius: 50px; padding: 0.6rem 1.5rem;"><i class="bi bi-arrow-left me-2"></i>Back to Shop</a>
        <div class="row g-3 g-md-4">
            <div class="col-md-5 d-flex align-items-center">
                <div class="position-relative w-100" style="height: 480px; background: #f8fafc; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <img src="uploads/products/<?php echo $product_data['image_url']?>"
                        class="product-detail-img"
                        alt="<?php echo $product_data['product_name'];?>"
                        style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    <?php 
                        // Use display_stock which includes variant stock
                        $stock_to_check = isset($product_data['display_stock']) ? $product_data['display_stock'] : $product_data['stock'];
                        
                        if($stock_to_check <= 0): 
                    ?>
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-danger">Out of Stock</span>
                        </div>
                    <?php elseif($stock_to_check <= $product_data['restock_alert']): ?>
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-warning text-dark">Low Stock</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-7">
                <div class="product-info-card">
                    <!-- Header Section -->
                    <div class="section">
                        <div class="mb-2">
                            <span class="badge bg-primary me-2"><?php echo $product_data['category_name'];?></span>
                            <span class="badge bg-secondary"><?php echo $product_data['material'];?></span>
                        </div>
                        <h1 class="h4 fw-bold mb-0" style="color: #111827; line-height: 1.2;"><?php echo $product_data['product_name'];?></h1>
                    </div>
                    
                    <!-- Price Section (dynamic by variant) -->
                    <div class="price-display">
                        <?php if ($is_bulk_buyer && $user_discount > 0): ?>
                            <span class="badge bg-success mb-2">
                                <i class="bi bi-star-fill"></i> WHOLESALER PRICE
                            </span>
                            <div class="d-flex align-items-baseline mb-1">
                                <span id="origPrice" class="text-muted text-decoration-line-through me-2">₱<?php echo number_format($product_data['price'], 2);?></span>
                                <span class="badge bg-warning text-dark">-<?php echo $user_discount; ?>%</span>
                            </div>
                            <div class="d-flex align-items-baseline">
                                <h2 class="h3 fw-bold text-success mb-0 me-2">₱<span id="finalPrice"><?php echo number_format($product_data['price'] * (1 - ($user_discount / 100)), 2);?></span></h2>
                                <span class="text-muted small">per piece</span>
                            </div>
                            <small class="text-success">You save ₱<span id="youSave"><?php echo number_format($product_data['price'] * ($user_discount / 100), 2); ?></span> per piece!</small>
                        <?php else: ?>
                            <div class="d-flex align-items-baseline">
                                <h2 class="h3 fw-bold text-primary mb-0 me-2">₱<span id="finalPrice"><?php echo number_format($product_data['price'], 2);?></span></h2>
                                <span class="text-muted small">per piece</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Available Sizes Section (moved here) -->
                    <div class="section">
                        <h6 class="text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 1px; color: #6b7280;">Available Sizes</h6>
                        <div id="variantBadgesTop"></div>
                    </div>
                    
                    <!-- Description Section -->
                    <div class="section">
                        <h6 class="text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 1px; color: #6b7280;">Description</h6>
                        <p class="text-muted mb-0" style="line-height: 1.6; font-size: 0.9rem;"><?php echo nl2br(htmlspecialchars($product_data['description']));?></p>
                    </div>
                    
                    <!-- Purchase Section -->
                    <div>
                        <div class="mb-3">
                            <label class="text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 1px; color: #6b7280;">Size</label>
                            <select id="variantSelect" class="form-select" style="max-width: 260px;">
                                <option>Loading sizes...</option>
                            </select>
                            <div class="mt-2">
                                <a class="small mt-1 d-inline-block" data-bs-toggle="collapse" href="#allSizesCollapse" role="button" aria-expanded="false" aria-controls="allSizesCollapse" id="viewAllSizesLink" style="text-decoration: none;">
                                    View all sizes
                                </a>
                                <div class="collapse mt-2" id="allSizesCollapse">
                                    <div class="card card-body p-2">
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0" id="allSizesTable">
                                                <thead>
                                                    <tr><th>Size</th><th class="text-end">Stock</th><th>Status</th></tr>
                                                </thead>
                                                <tbody></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 1px; color: #6b7280;">Quantity</label>
                            <div class="input-group" style="width: 220px;">
                                <button class="btn btn-outline-secondary" type="button" id="qtyDec">−</button>
                                <input type="number" class="form-control text-center fw-bold text-dark" id="quantity" 
                                       value="<?php echo $is_bulk_buyer ? '20' : '1'; ?>" 
                                       min="<?php echo $is_bulk_buyer ? '20' : '1'; ?>" 
                                       max="1000" 
                                       <?php echo $is_bulk_buyer ? 'data-is-wholesale="true"' : ''; ?> 
                                       style="background-color: #fff;">
                                <button class="btn btn-outline-secondary" type="button" id="qtyInc">+</button>
                                <?php if ($is_bulk_buyer): ?>
                                <span class="input-group-text bg-light border-0" style="font-size: 0.8rem; white-space: nowrap;">Min: 20</span>
                                <?php endif; ?>
                            </div>
                        </div>
                <script>
                    const isBulkBuyer = <?php echo $is_bulk_buyer ? 'true' : 'false'; ?>;
                    const discountRate = <?php echo (float)$user_discount; ?>;
                    const productId = <?php echo (int)$id; ?>;
                    const minQuantity = isBulkBuyer ? 20 : 1;
                    let variants = [];
                    let selectedVariant = null;

                    function money(n){ return Number(n).toFixed(2); }

                    function applyQty(step){
                        const input = document.getElementById('quantity');
                        let v = parseInt(input.value||'0',10);
                        const min = parseInt(input.min||'0',10);
                        const max = parseInt(input.max||'0',10);
                        
                        // For wholesalers, ensure we're incrementing by 20 when increasing
                        if (isBulkBuyer && step > 0) {
                            v = Math.max(min, Math.min(max, v + minQuantity));
                        } else {
                            v = Math.max(min, Math.min(max, v + step));
                        }
                        
                        // Ensure wholesalers can't go below minimum quantity
                        if (isBulkBuyer) {
                            v = Math.max(minQuantity, v);
                        }
                        
                        input.value = v;
                    }
                    
                    // Add real-time validation for quantity input
                    document.addEventListener('DOMContentLoaded', function() {
                        const quantityInput = document.getElementById('quantity');
                        if (quantityInput) {
                            quantityInput.addEventListener('input', function() {
                                const value = parseInt(this.value, 10);
                                const min = parseInt(this.min, 10);
                                
                                // For wholesalers, validate minimum quantity in real-time
                                if (isBulkBuyer && value < minQuantity) {
                                    // Show warning but don't prevent input
                                    this.style.borderColor = '#dc3545';
                                    this.style.backgroundColor = '#f8d7da';
                                    
                                    // Show tooltip or warning message
                                    if (!this.dataset.warningShown) {
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Minimum Quantity Required',
                                            text: 'Wholesale orders require a minimum of 20 items per product.',
                                            timer: 2000,
                                            showConfirmButton: false,
                                            position: 'top',
                                            toast: true
                                        });
                                        this.dataset.warningShown = 'true';
                                    }
                                } else {
                                    // Reset styling if valid
                                    this.style.borderColor = '';
                                    this.style.backgroundColor = '';
                                    delete this.dataset.warningShown;
                                }
                            });
                            
                            // Reset warning state when user focuses away
                            quantityInput.addEventListener('blur', function() {
                                const value = parseInt(this.value, 10);
                                if (isBulkBuyer && value < minQuantity) {
                                    // Auto-correct to minimum quantity
                                    this.value = minQuantity;
                                    this.style.borderColor = '';
                                    this.style.backgroundColor = '';
                                    delete this.dataset.warningShown;
                                }
                            });
                        }
                    });

                    function updateFromVariant(){
                        const q = document.getElementById('quantity');
                        const finalPriceEl = document.getElementById('finalPrice');
                        const origEl = document.getElementById('origPrice');
                        const saveEl = document.getElementById('youSave');

                        if (!selectedVariant){
                            // Keep the input enabled but show a message
                            q.min = isBulkBuyer ? 20 : 1;
                            q.value = isBulkBuyer ? 20 : 1;
                            q.max = 0;
                            document.getElementById('addToCartBtn').disabled = true;
                            return;
                        }
                        const stock = parseInt(selectedVariant.stock||'0',10);
                        const base = parseFloat(selectedVariant.price||'0');
                        
                        // Set minimum quantity based on user type
                        const minQty = isBulkBuyer ? minQuantity : 1;
                        q.min = minQty;
                        
                        // If current value is less than minimum, set to minimum
                        if (parseInt(q.value) < minQty) {
                            q.value = minQty;
                        }
                        
                        // Disable add to cart if not enough stock for minimum quantity
                        document.getElementById('addToCartBtn').disabled = stock < minQty;
                        
                        console.log('Selected variant:', selectedVariant.size, 'Price:', base, 'Stock:', stock, 'Min Qty:', minQty);
                        let final = base;
                        if (isBulkBuyer && discountRate>0){ final = Math.round(base * (1 - (discountRate/100)) * 100)/100; }

                        // Update UI (size tile removed)
                        // Availability line removed (as requested)
                        q.disabled = stock <= 0; 
                        q.min = isBulkBuyer ? 20 : 1;
                        q.max = stock; 
                        if (stock > 0) {
                            q.value = isBulkBuyer ? 20 : 1;
                        } else {
                            q.value = 0;
                        }
                        if (finalPriceEl){ finalPriceEl.textContent = money(final); }
                        if (origEl){ origEl.textContent = '₱'+money(base); }
                        if (saveEl){ saveEl.textContent = money(base - final); }

                        // Toggle add button state
                        const addBtn = document.getElementById('addToCartBtn');
                        if (addBtn){ addBtn.disabled = stock<=0; }
                    }

                    function initVariantSelect(){
                        const sel = document.getElementById('variantSelect');
                        sel.innerHTML = '';
                        variants.forEach(v=>{
                            const opt = document.createElement('option');
                            opt.value = v.variant_id;
                            opt.textContent = `${v.size} ${v.stock<=0 ? '(Out of stock)' : ''}`;
                            opt.disabled = v.stock<=0;
                            sel.appendChild(opt);
                        });
                        // select first in-stock, else first
                        let def = variants.find(v=>parseInt(v.stock,10)>0) || variants[0] || null;
                        if (def){ sel.value = def.variant_id; selectedVariant = def; }
                        updateFromVariant();
                        renderVariantBadges();
                        sel.addEventListener('change', ()=>{
                            const val = parseInt(sel.value,10);
                            selectedVariant = variants.find(v=>parseInt(v.variant_id,10)===val) || null;
                            updateFromVariant();
                            highlightSelectedBadge();
                        });
                    }

                    function renderVariantBadges(){
                        const wrap = document.getElementById('variantBadgesTop');
                        if(!wrap) return;
                        wrap.innerHTML='';
                        // Show up to 3 inline badges
                        const maxInline = 3;
                        const total = variants.length;
                        const inline = variants.slice(0, maxInline);
                        inline.forEach(v=>{
                            const span = document.createElement('span');
                            const out = parseInt(v.stock,10)<=0;
                            span.className = `badge ${out?'bg-danger':'bg-success'} me-1 mb-1`;
                            span.dataset.vid = v.variant_id;
                            span.style.cursor = out? 'not-allowed' : 'pointer';
                            span.textContent = `${v.size} (${v.stock})`;
                            if(!out){
                                span.onclick = ()=>{
                                    const sel = document.getElementById('variantSelect');
                                    sel.value = v.variant_id;
                                    selectedVariant = v;
                                    updateFromVariant();
                                    highlightSelectedBadge();
                                };
                            }
                            wrap.appendChild(span);
                        });
                        // '+N more' indicator
                        const more = total - inline.length;
                        const link = document.getElementById('viewAllSizesLink');
                        if (more>0 && link){
                            link.style.display = 'inline-block';
                            link.textContent = `View all sizes (+${more})`;
                        } else if (link) {
                            link.style.display = 'none';
                        }
                        buildAllSizesTable();
                        highlightSelectedBadge();
                    }

                    function highlightSelectedBadge(){
                        const wrap = document.getElementById('variantBadgesTop');
                        if(!wrap) return;
                        [...wrap.children].forEach(ch=>{
                            ch.style.outline = 'none';
                            ch.style.boxShadow = 'none';
                        });
                        if(selectedVariant){
                            const el = [...wrap.children].find(x=>parseInt(x.dataset.vid,10)===parseInt(selectedVariant.variant_id,10));
                            if(el){
                                el.style.outline = '2px solid #0d6efd33';
                                el.style.boxShadow = '0 0 0 2px #0d6efd22 inset';
                            }
                        }
                    }

                    function buildAllSizesTable(){
                        const tbody = document.querySelector('#allSizesTable tbody');
                        if (!tbody) return;
                        tbody.innerHTML = '';
                        variants.forEach(v=>{
                            const tr = document.createElement('tr');
                            const stock = parseInt(v.stock,10)||0;
                            const status = stock<=0? 'Out of stock' : (stock <= (<?php echo (int)$product_data['restock_alert'];?>) ? 'Low' : 'In stock');
                            const statusClass = stock<=0? 'text-danger' : (stock <= (<?php echo (int)$product_data['restock_alert'];?>) ? 'text-warning' : 'text-success');
                            tr.innerHTML = `<td>${v.size}</td><td class="text-end">${stock}</td><td class="${statusClass}">${status}</td>`;
                            tbody.appendChild(tr);
                        });
                    }

                    document.addEventListener('DOMContentLoaded', async function(){
                        // Qty buttons
                        document.getElementById('qtyDec').addEventListener('click', ()=>applyQty(-1));
                        document.getElementById('qtyInc').addEventListener('click', ()=>applyQty(1));
                        // Load variants
                        try{
                            const res = await fetch(`backend/get_variants.php?product_id=${productId}`);
                            const json = await res.json();
                            if (json.success){
                                variants = json.variants || [];
                                console.log('Loaded variants:', variants.map(v => ({size: v.size, price: v.price, stock: v.stock})));
                                if (variants.length>0){
                                    initVariantSelect();
                                } else {
                                    // No variants configured; fallback to product-level
                                    const sel = document.getElementById('variantSelect');
                                    sel.innerHTML = '<option>No sizes available</option>';
                                }
                            }
                        }catch(e){ console.warn('Failed loading variants', e); }
                    });
                </script>
                        <div class="d-grid gap-2">
                            <button id="addToCartBtn" onclick="addToCartWithQuantity(<?php echo $product_data['product_id']?>)" class="btn btn-primary btn-lg" disabled>
                                <i class="bi bi-cart-plus me-2"></i> Add to Cart
                            </button>
                            <a href="shop.php" class="btn btn-outline-secondary">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; overflow: hidden;">
                    <div class="card-header" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); padding: 1.25rem;">
                        <h5 class="text-white mb-0"><i class="bi bi-chat-left-quote me-2"></i>Customer Reviews</h5>
                    </div>
                    <div class="card-body p-3">
                        <?php if ($average_rating): ?>
                            <div class="alert alert-light border-0 mb-3" style="background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%); border-radius: 10px; padding: 1rem;">
                                <div class="d-flex align-items-center gap-3">
                                    <div>
                                        <h3 class="mb-0" style="font-size: 2rem; font-weight: 700; color: #d97706;"><?= $average_rating ?></h3>
                                    </div>
                                    <div>
                                        <div class="text-warning" style="font-size: 1.1rem;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= floor($average_rating)): ?>
                                                    <i class="bi bi-star-fill"></i>
                                                <?php elseif ($i - $average_rating <= 0.5): ?>
                                                    <i class="bi bi-star-half"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                        <p class="mb-0 text-muted small">Average Rating</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info border-0" style="border-radius: 12px;">
                                <i class="bi bi-info-circle me-2"></i>No ratings yet. Be the first to review!
                            </div>
                        <?php endif; ?>

                        <?php if ($ratings_data): ?>
                            <div class="reviews-list">
                                <?php foreach ($ratings_data as $r): ?>
                                    <div class="review-item p-3 mb-2" style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 12px;">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar" style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">
                                                    <?php echo strtoupper(substr($r['fullname'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong style="color: #1e293b; font-size: 0.9rem;"><?php echo htmlspecialchars($r['fullname']); ?></strong>
                                                    <div class="text-warning" style="font-size: 0.85rem;">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <?php if ($i <= $r['rating']): ?>
                                                                <i class="bi bi-star-fill"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-star"></i>
                                                            <?php endif; ?>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-calendar3 me-1"></i><?php echo date('M j, Y', strtotime($r['created_at'])); ?></small>
                                        </div>
                                        <?php if (!empty(trim($r['comment']))): ?>
                                            <p class="mt-2 mb-0 text-muted small" style="line-height: 1.6;"><?php echo htmlspecialchars($r['comment']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

function addToCartWithQuantity(productId) {
    const quantity = document.getElementById('quantity').value;
    const sel = document.getElementById('variantSelect');
    const variantId = sel && sel.value ? parseInt(sel.value,10) : 0;

    fetch('backend/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_to_cart&product_id=' + encodeURIComponent(productId) + '&variant_id=' + encodeURIComponent(variantId) + '&quantity=' + encodeURIComponent(quantity),
    })
    .then(response => response.text())
    .then(response => {
        const trimmedResponse = response.trim();
        
        // Handle old string responses (backward compatibility)
        if (trimmedResponse === 'not_logged_in') {
            Swal.fire({
                icon: 'warning',
                title: 'Please log in',
                text: 'You need to log in to add items to your cart.',
            }).then(() => {
                window.location.href = 'login.php?destination=product.php&des_id=<?php echo $id?>';
            });
            return;
        }
        
        if (trimmedResponse === 'not_verified') {
            Swal.fire({
                icon: 'warning',
                title: 'Email Verification Required',
                text: 'Please verify your email address first to add items to your cart.',
                showCancelButton: true,
                confirmButtonText: 'Verify Email',
                cancelButtonText: 'Go Back'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'verify_email.php';
                } else {
                    window.history.replaceState(null, '', 'index.php');
                    window.location.href = 'index.php';
                }
            });
            return;
        }
        
        if (trimmedResponse === 'out_of_stock') {
            Swal.fire({
                icon: 'error',
                title: 'Out of Stock',
                text: 'Sorry, the product is out of stock.',
            });
            return;
        }
        
        if (trimmedResponse === 'minimum_quantity_not_met') {
            Swal.fire({
                icon: 'warning',
                title: 'Minimum Quantity Required',
                text: 'Wholesale orders require a minimum of 20 items per product. Please increase the quantity to 20 or more.',
                footer: 'Bulk buyers must order at least 20 units of each product.'
            });
            return;
        }
        
        if (trimmedResponse === 'insufficient_stock_for_wholesale') {
            Swal.fire({
                icon: 'warning',
                title: 'Insufficient Stock for Wholesale',
                text: 'This product cannot be added as it does not meet the minimum quantity requirement of 20 items for wholesale checkout.',
                footer: 'Wholesale orders require a minimum of 20 items per product.'
            });
            return;
        }
        
        if (trimmedResponse === 'cart_limit_reached') {
            Swal.fire({
                icon: 'warning',
                title: 'Cart Limit Reached',
                text: 'You cannot have more than 50 products in your cart. Please remove some items before adding new ones.',
            });
            return;
        }
        
        // Try to parse JSON response
        try {
            const data = JSON.parse(trimmedResponse);
            
            if (data.status === 'success') {
                // Update cart count immediately
                updateCartCount();
                
                // Check if user is a wholesaler for custom message
                const isWholesaler = <?php echo $is_bulk_buyer ? 'true' : 'false'; ?>;
                const successMessage = isWholesaler 
                    ? 'Product added to cart! Remember: Minimum 20 items required for wholesale checkout.' 
                    : data.message || 'Product added to cart!';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart!',
                    text: successMessage,
                    timer: 3000,
                    showConfirmButton: false
                });
            } else if (data.status === 'exceeds_stock') {
                // Show helpful message about existing cart quantity
                const sizeInfo = data.variant_size ? `<p style="margin-bottom: 1rem;"><i class="bi bi-rulers"></i> <strong>Size: ${data.variant_size}</strong></p>` : '';
                const isWholesaler = <?php echo $is_bulk_buyer ? 'true' : 'false'; ?>;
                
                if (isWholesaler && data.stock < 20) {
                    // Special message for wholesalers when stock is less than minimum
                    Swal.fire({
                        icon: 'warning',
                        title: 'Insufficient Stock for Wholesale',
                        html: `<div style="text-align:left;">
                            ${sizeInfo}
                            <p><strong>This product cannot be added as it does not meet the minimum quantity requirement of 20 items for wholesale checkout.</strong></p>
                            <hr>
                            <p><i class="bi bi-box-seam"></i> Total stock available: <strong>${data.stock}</strong></p>
                            <p><i class="bi bi-info-circle"></i> Wholesale orders require a minimum of 20 items per product.</p>
                        </div>`,
                        confirmButtonText: 'OK'
                    });
                } else {
                    // Regular stock limit message
                    Swal.fire({
                        icon: 'info',
                        title: 'Cannot Add More',
                        html: `<div style="text-align: left;">
                            ${sizeInfo}
                            <p><strong>${data.message}</strong></p>
                            <hr>
                            <p><i class="bi bi-cart-fill"></i> Current in cart: <strong>${data.current_cart_qty}</strong></p>
                            <p><i class="bi bi-box-seam"></i> Total stock: <strong>${data.stock}</strong></p>
                            <p><i class="bi bi-plus-circle"></i> Can still add: <strong>${data.remaining}</strong></p>
                        </div>`,
                        confirmButtonText: 'OK'
                    });
                }
            } else if (data.status === 'cart_full') {
                // Cart already has maximum available stock
                const sizeInfo = data.variant_size ? `<p style="margin-bottom: 1rem;"><i class="bi bi-rulers"></i> <strong>Size: ${data.variant_size}</strong></p>` : '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Cart Already Full',
                    html: `<div style="text-align: left;">
                        ${sizeInfo}
                        <p><strong>${data.message}</strong></p>
                        <hr>
                        <p><i class="bi bi-cart-fill"></i> Current in cart: <strong>${data.current_cart_qty}</strong></p>
                        <p><i class="bi bi-box-seam"></i> Total stock: <strong>${data.stock}</strong></p>
                    </div>`,
                    confirmButtonText: 'OK'
                });
            }
        } catch (e) {
            // If not JSON, check if it's old success message
            if (trimmedResponse === 'success' || trimmedResponse.includes('Product added')) {
                updateCartCount();
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart!',
                    text: 'Item has been added to your cart.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: 'Something went wrong. Please try again.',
                });
            }
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Unable to add to cart. Please check your connection.',
        });
    });
}
</script>

<!-- Similar Products Section -->
<?php if (count($similar_products) > 0): ?>
<div class="container my-5">
    <h3 class="mb-4"><i class="bi bi-grid-3x3-gap me-2"></i>Similar Products You May Like</h3>
    <div class="row g-4">
        <?php foreach ($similar_products as $similar): ?>
        <div class="col-6 col-md-3">
            <a href="product.php?id=<?= $similar['product_id'] ?>" class="text-decoration-none">
                <div class="card h-100 shadow-sm" style="border-radius: 12px; transition: transform 0.3s;">
                    <img src="uploads/products/<?= $similar['image_url'] ?>" 
                         class="card-img-top" 
                         alt="<?= $similar['product_name'] ?>"
                         style="height: 200px; object-fit: cover; border-radius: 12px 12px 0 0;">
                    <div class="card-body">
                        <h6 class="card-title text-dark mb-2" style="min-height: 40px;">
                            <?= $similar['product_name'] ?>
                        </h6>
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-warning small">
                                <?php 
                                $rating = $similar['avg_rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </span>
                            <small class="text-muted ms-1">(<?= number_format($similar['avg_rating'], 1) ?>)</small>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($is_bulk_buyer && $user_discount > 0): ?>
                                <?php 
                                    $similar_discounted = $similar['price'] * (1 - ($user_discount / 100));
                                ?>
                                <div>
                                    <small class="text-muted text-decoration-line-through d-block">₱<?= number_format($similar['price'], 2) ?></small>
                                    <span class="text-success fw-bold">₱<?= number_format($similar_discounted, 2) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-primary fw-bold">₱<?= number_format($similar['price'], 2) ?></span>
                            <?php endif; ?>
                            <small class="text-muted"><?= $similar['total_sold'] ?> sold</small>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}
</style>
<?php endif; ?>

</body>
</html>