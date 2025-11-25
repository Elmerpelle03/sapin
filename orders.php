<?php
    require ('config/db.php');
    session_start();
    require('config/session_disallow_courier.php');

    // Mark notification as read if coming from notification
    $highlight_order_id = $_GET['highlight'] ?? null;
    $notif_id = $_GET['notif_id'] ?? null;

    if ($notif_id && isset($_SESSION['user_id'])) {
        $mark_read_stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE notification_id = :notif_id AND user_id = :user_id
        ");
        $mark_read_stmt->execute([
            ':notif_id' => $notif_id,
            ':user_id' => $_SESSION['user_id']
        ]);
    }

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
    <title>My Orders</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
        }
        
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            min-height: 100vh;
        }
        
        /* Animated Header */
        .orders-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            color: white;
            text-align: center;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
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

        .order-card {
            background: white;
            border: none;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: flex-start;
            gap: 20px;
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        
        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .order-card:hover::before {
            transform: scaleY(1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .order-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 16px 48px rgba(37, 99, 235, 0.2);
        }
        
        /* Order Card Image */
        .order-card img {
            border-radius: 12px;
            transition: transform 0.3s ease;
        }
        
        .order-card:hover img {
            transform: scale(1.05);
        }
        
        /* Status Badge Enhancement - Only for order status badges */
        .order-card .badge,
        .badge-delivered,
        .badge-processing,
        .badge-pending,
        .badge-received,
        .badge-cancelled {
            padding: 0.5rem 1rem !important;
            border-radius: 50px !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
        }
        
        /* Keep navbar badges small */
        .navbar .badge {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.75rem !important;
            min-width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* Highlight order from notification */
        .order-card.highlight-order {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
            animation: pulse 2s ease-in-out 3;
        }

        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 8px 24px rgba(59, 130, 246, 0.25);
            }
            50% {
                box-shadow: 0 8px 32px rgba(59, 130, 246, 0.45);
            }
        }

        .order-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .order-checkbox {
            border-radius: 4px;
            margin-right: 10px;
        }

        .sticky-controls {
            position: sticky;
            top: 56px; /* Assuming navbar height */
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e0e0e0;
            z-index: 1020;
            padding: 10px 0;
        }

        .order-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .order-middle {
            flex: 1;
        }

        .order-right {
            display: flex;
            align-items: center;
        }

        .order-status {
            margin: 4px 0;
        }

        .order-items {
            align-items: flex-end;
            gap: 8px;
        }

        .order-header {
            font-weight: 600;
            font-size: 16px;
            color: #333;
            margin-bottom: 8px;
        }

        .order-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .order-total {
            font-weight: bold;
            color: #F44336;
        }

        .btn-view-details {
            background-color: #ee4d2d;
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
            transform: scale(1);
        }

        .btn-view-details:hover {
            background-color: #d03c2a;
            color: white;
            text-decoration: none;
            transform: scale(1.05);
        }

        /* Status badge colors */
        .badge-delivered {
            background-color: #4CAF50 !important;
            color: white;
        }

        .badge-processing {
            background-color: #3498db !important;
            color: white;
        }

        .badge-pending {
            background-color: #FF9800 !important;
            color: white;
        }

        .badge-received {
            background-color: #9b59b6 !important;
            color: white;
        }

        .badge-cancelled {
            background-color: #F44336 !important;
            color: white;
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
            color: #6c63ff !important;
        }

        .custom-navbar .navbar-brand {
            font-weight: 700;
            color: #6c63ff !important;
        }

        .badge.cart-badge {
            background-color: #6c63ff !important;
            transition: transform 0.2s ease;
        }

        .badge.cart-badge:hover {
            transform: scale(1.1);
        }

        /* Filters Container */
        .filters-container {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                padding: 0 0.75rem;
            }
            
            h4.mb-4 {
                font-size: 1.25rem;
                margin-bottom: 1rem !important;
            }
            
            /* Stack filters vertically on mobile */
            .d-flex.flex-wrap.align-items-center.gap-3 {
                gap: 0.75rem !important;
            }
            
            .d-flex.align-items-center.gap-2 {
                flex: 1 1 100%;
                min-width: 100%;
            }
            
            .d-flex.align-items-center.gap-2 label {
                min-width: 60px;
                font-size: 0.9rem;
                white-space: nowrap;
            }
            
            .form-select-sm,
            .form-control-sm {
                font-size: 0.9rem !important;
                padding: 0.5rem 0.75rem !important;
                flex: 1;
            }
            
            .flex-grow-1 {
                flex: 1 1 100%;
                min-width: 100%;
            }
            
            .order-card {
                flex-direction: column;
                gap: 12px;
                padding: 15px;
            }
            
            .order-left {
                align-self: flex-start;
                width: 100%;
                justify-content: center;
            }
            
            .order-middle {
                text-align: left;
                width: 100%;
            }
            
            .order-right {
                align-items: center;
                flex-direction: row;
                justify-content: center;
                width: 100%;
            }
            
            .order-image {
                width: 100%;
                max-width: 200px;
                height: auto;
                aspect-ratio: 1;
            }
            
            .order-header {
                font-size: 1rem;
            }
            
            .btn-view-details {
                width: 100%;
                padding: 10px 20px;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                padding: 0 0.5rem;
            }
            
            h4.mb-4 {
                font-size: 1.1rem;
            }
            
            .d-flex.align-items-center.gap-2 {
                flex: 1 1 100%;
                min-width: 100%;
            }
            
            .d-flex.align-items-center.gap-2 label {
                min-width: 50px;
                font-size: 0.85rem;
            }
            
            .form-select-sm,
            .form-control-sm {
                font-size: 0.85rem !important;
                padding: 0.45rem 0.65rem !important;
            }
            
            .order-card {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .order-header {
                font-size: 0.95rem;
            }
            
            .order-status-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .order-total {
                font-size: 1.1rem;
            }
            
            .btn-view-details {
                padding: 9px 18px;
                font-size: 0.9rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <?php $active = 'orders'; ?>
    <?php include 'includes/navbar_customer.php'; ?>

    <!-- Animated Header -->
    <header class="orders-header">
        <!-- Floating Shapes -->
        <div style="position: absolute; top: 20%; left: 10%; width: 70px; height: 70px; background: rgba(251,191,36,0.3); border-radius: 50%; animation: float 6s ease-in-out infinite;"></div>
        <div style="position: absolute; top: 50%; right: 15%; width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; animation: float 8s ease-in-out infinite 1s;"></div>
        <div style="position: absolute; bottom: 30%; left: 15%; width: 40px; height: 40px; background: rgba(251,191,36,0.4); transform: rotate(45deg); animation: float 7s ease-in-out infinite 2s;"></div>
        <div style="position: absolute; top: 30%; right: 20%; width: 80px; height: 80px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; animation: rotate 20s linear infinite;"></div>
        
        <div class="container" style="position: relative; z-index: 10;">
            <h1 class="display-4 fw-bold mb-2" style="animation: fadeInUp 1s ease-out;">
                <i class="bi bi-receipt me-3"></i>My Orders
            </h1>
            <p class="lead" style="animation: fadeInUp 1s ease-out 0.2s; animation-fill-mode: both;">Track and manage your orders</p>
        </div>
    </header>

    <div class="container py-5">

        <!-- Filters and Sorting -->
        <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
            <div class="d-flex align-items-center gap-2">
                <label for="filterStatus" class="form-label mb-0 fw-semibold">Status:</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="all" selected>All</option>
                    <option value="pending">Pending</option>
                    <option value="processing">Processing</option>
                    <option value="shipping">Shipping</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="filterDateFrom" class="form-label mb-0 fw-semibold">From:</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm" />
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="filterDateTo" class="form-label mb-0 fw-semibold">To:</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm" />
            </div>
            <div class="flex-grow-1">
                <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Search order ID or product name" />
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="sortOrders" class="form-label mb-0 fw-semibold">Sort by:</label>
                <select id="sortOrders" class="form-select form-select-sm">
                    <option value="newest" selected>Newest → Oldest</option>
                    <option value="oldest">Oldest → Newest</option>
                    <option value="highest">Highest Price → Lowest Price</option>
                    <option value="lowest">Lowest Price → Highest Price</option>
                </select>
            </div>
        </div>

        <div id="ordersContainer" class="d-flex flex-column gap-3">
            <!-- Order cards will be rendered here -->
        </div>
        
        <!-- Pagination Controls -->
        <div id="paginationContainer" class="d-flex justify-content-center align-items-center gap-2 mt-4">
            <!-- Pagination buttons will be rendered here -->
        </div>

        <div id="noOrdersMessage" class="text-center text-muted py-3" style="display: none;">
            No orders yet — start shopping now!
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            fetchOrders();

            // Attach event listeners for filters
            $('#filterStatus, #sortOrders').on('change', applyFiltersAndSort);
            $('#filterDateFrom, #filterDateTo').on('change', applyFiltersAndSort);
            
            // Search input - debounced for better performance
            let searchTimeout;
            $('#filterSearch').on('input keyup', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() {
                    applyFiltersAndSort();
                }, 300); // Wait 300ms after user stops typing
            });
        });

        let allOrders = [];
        let currentPage = 1;
        const ordersPerPage = 5;

        function fetchOrders() {
            $.ajax({
                url: "backend/fetch_orders.php",
                type: "GET",
                data: {
                    draw: 1,
                    start: 0,
                    length: 1000 // Fetch all orders for card display
                },
                success: function(response) {
                    if (response && response.data && Array.isArray(response.data)) {
                        allOrders = response.data; // Store all orders globally
                        applyFiltersAndSort();
                    } else {
                        $('#ordersContainer').html('<p class="text-center text-danger">No orders data found.</p>');
                    }
                },
                error: function() {
                    $('#ordersContainer').html('<p class="text-center text-danger">Error loading orders.</p>');
                }
            });
        }

        function renderOrderCard(order) {
            const defaultImage = 'default.jpg'; // Adjust if needed
            const highlightOrderId = <?= json_encode($highlight_order_id) ?>;
            const isHighlighted = highlightOrderId && order.order_id == highlightOrderId;
            
            return `
                <div class="order-card ${isHighlighted ? 'highlight-order' : ''}" id="order-${order.order_id}">
                    <div class="order-left">
                        <img src="uploads/products/${order.product_image || defaultImage}" alt="Product" class="order-image">
                    </div>
                    <div class="order-middle">
                        <div class="order-header">#${order.order_id} - ${order.product_name}</div>
                        <div class="order-status-row">
                            <span class="order-status">${order.status}</span>
                            <span class="order-total">${order.amount}</span>
                        </div>
                        <div class="order-items">
                            ${order.item_count} item(s) • ${order.date}
                        </div>
                    </div>
                    <div class="order-right">
                        <a href="order_details.php?order_id=${order.order_id}" class="btn-view-details">View Details</a>
                    </div>
                </div>
            `;
        }

        function applyFiltersAndSort() {
            let filtered = allOrders.filter(order => {
                const statusFilter = $('#filterStatus').val().toLowerCase();
                // Extract text from HTML badge (e.g., '<span class="badge badge-pending">Pending</span>' -> 'pending')
                const statusText = order.status.replace(/<[^>]*>/g, '').toLowerCase();
                const statusMatch = statusFilter === 'all' || statusText.includes(statusFilter);

                const dateFrom = $('#filterDateFrom').val();
                const dateTo = $('#filterDateTo').val();
                const orderDate = new Date(order.sort_date);
                const fromMatch = !dateFrom || orderDate >= new Date(dateFrom);
                const toMatch = !dateTo || orderDate <= new Date(dateTo + 'T23:59:59');

                const search = $('#filterSearch').val().toLowerCase();
                const productName = (order.product_name || '').toLowerCase();
                const searchMatch = !search || 
                    order.order_id.toString().includes(search) || 
                    productName.includes(search);

                return statusMatch && fromMatch && toMatch && searchMatch;
            });

            const sort = $('#sortOrders').val();
            if (sort === 'newest') {
                filtered.sort((a, b) => new Date(b.sort_date) - new Date(a.sort_date));
            } else if (sort === 'oldest') {
                filtered.sort((a, b) => new Date(a.sort_date) - new Date(b.sort_date));
            } else if (sort === 'highest') {
                filtered.sort((a, b) => parseFloat(b.amount.replace('₱', '').replace(',', '')) - parseFloat(a.amount.replace('₱', '').replace(',', '')));
            } else if (sort === 'lowest') {
                filtered.sort((a, b) => parseFloat(a.amount.replace('₱', '').replace(',', '')) - parseFloat(b.amount.replace('₱', '').replace(',', '')));
            }

            // Pagination logic
            const totalPages = Math.ceil(filtered.length / ordersPerPage);
            const startIndex = (currentPage - 1) * ordersPerPage;
            const endIndex = startIndex + ordersPerPage;
            const paginatedOrders = filtered.slice(startIndex, endIndex);
            
            const html = paginatedOrders.map(renderOrderCard).join('');
            $('#ordersContainer').html(html);
            $('#noOrdersMessage').toggle(filtered.length === 0);
            
            // Render pagination controls
            renderPagination(totalPages, filtered.length);

            // Auto-scroll to highlighted order
            const highlightOrderId = <?= json_encode($highlight_order_id) ?>;
            if (highlightOrderId) {
                setTimeout(() => {
                    const highlightedOrder = document.getElementById('order-' + highlightOrderId);
                    if (highlightedOrder) {
                        highlightedOrder.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }, 500);
            }
        }
        
        function renderPagination(totalPages, totalOrders) {
            if (totalPages <= 1) {
                $('#paginationContainer').html('');
                return;
            }
            
            let paginationHTML = `
                <button class="btn btn-sm btn-outline-primary" onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="bi bi-chevron-left"></i> Previous
                </button>
                <span class="mx-3">Page ${currentPage} of ${totalPages} (${totalOrders} orders)</span>
                <button class="btn btn-sm btn-outline-primary" onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            `;
            
            $('#paginationContainer').html(paginationHTML);
        }
        
        function changePage(page) {
            const totalPages = Math.ceil(allOrders.length / ordersPerPage);
            if (page < 1 || page > totalPages) return;
            
            currentPage = page;
            applyFiltersAndSort();
            window.scrollTo({ top: 0, behavior: 'smooth' });
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

    
</body>

</html>