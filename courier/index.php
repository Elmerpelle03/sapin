<?php 
require('../config/session_courier.php');
require('../config/db.php');

$uid = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['fullname'] ?? 'Courier';

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM orders WHERE rider_id = $uid")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE rider_id = $uid AND status IN ('Delivered','Received')")->fetchColumn(),
    'shipping' => $pdo->query("SELECT COUNT(*) FROM orders WHERE rider_id = $uid AND status = 'Shipping'")->fetchColumn(),
    'cancelled' => $pdo->query("SELECT COUNT(*) FROM orders WHERE rider_id = $uid AND status = 'Cancelled'")->fetchColumn()
];

// Get active deliveries (Shipping, Processing, Pending only)
$deliveries = $pdo->query("
    SELECT o.order_id, o.fullname, o.status, o.contact_number, o.house, o.amount,
           b.barangay_name, m.municipality_name, p.province_name, o.date
    FROM orders o
    LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
    LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
    LEFT JOIN table_province p ON o.province_id = p.province_id
    WHERE o.rider_id = $uid AND o.status IN ('Shipping', 'Processing', 'Pending')
    ORDER BY 
        CASE 
            WHEN o.status = 'Shipping' THEN 1
            WHEN o.status = 'Processing' THEN 2
            WHEN o.status = 'Pending' THEN 3
        END,
        o.date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get all deliveries for the full list (including completed)
$all_deliveries = $pdo->query("
    SELECT o.order_id, o.fullname, o.status, o.contact_number, o.house, o.amount,
           b.barangay_name, m.municipality_name, p.province_name, o.date
    FROM orders o
    LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
    LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
    LEFT JOIN table_province p ON o.province_id = p.province_id
    WHERE o.rider_id = $uid
    ORDER BY o.date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Dashboard - Sapin Bedsheets</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Leaflet Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            min-height: 100vh;
        }
        
        /* Top Navigation */
        .top-nav {
            background: white;
            padding: 1.25rem 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .logo {
            font-size: 1.375rem;
            font-weight: 700;
            color: #1e40af;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo i {
            font-size: 1.75rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            position: relative;
        }
        
        .user-profile:hover {
            background: white;
            border-color: #cbd5e1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #1f2937;
            line-height: 1.2;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-logout {
            padding: 0.625rem 1.25rem;
            background: white;
            color: #dc2626;
            border: 1.5px solid #fecaca;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9375rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-logout:hover {
            background: #fef2f2;
            border-color: #dc2626;
            color: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
        }
        
        
        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--card-color), var(--card-color-light));
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card.blue { --card-color: #1e40af; --card-color-light: #3b82f6; }
        .stat-card.green { --card-color: #10b981; --card-color-light: #34d399; }
        .stat-card.orange { --card-color: #f59e0b; --card-color-light: #fbbf24; }
        .stat-card.red { --card-color: #ef4444; --card-color-light: #f87171; }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 0.75rem;
        }
        
        .stat-card.blue .stat-icon { background: linear-gradient(135deg, #1e40af20, #3b82f620); color: #1e40af; }
        .stat-card.green .stat-icon { background: linear-gradient(135deg, #10b98120, #34d39920); color: #10b981; }
        .stat-card.orange .stat-icon { background: linear-gradient(135deg, #f59e0b20, #fbbf2420); color: #f59e0b; }
        .stat-card.red .stat-icon { background: linear-gradient(135deg, #ef444420, #f8717120); color: #ef4444; }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.8125rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0.25rem 0;
            line-height: 1;
        }
        
        /* Main Content */
        .content-wrapper {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .card-modern {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
        }
        
        .card-header-modern {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-title-modern {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }
        
        .card-title-modern i {
            color: #3b82f6;
            font-size: 1.25rem;
        }
        
        /* Map */
        #deliveryMap {
            height: 480px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
            padding: 0.375rem;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .filter-tab {
            padding: 0.625rem 1.25rem;
            border: none;
            background: transparent;
            color: #64748b;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-tab:hover {
            background: white;
            color: #1e40af;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(30, 64, 175, 0.25);
        }
        
        .filter-tab i {
            font-size: 1rem;
        }
        
        /* Delivery List */
        .delivery-list {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }
        
        .delivery-list::-webkit-scrollbar {
            width: 6px;
        }
        
        .delivery-list::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 10px;
        }
        
        .delivery-list::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            border-radius: 10px;
        }
        
        .delivery-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.125rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            border-left: 3px solid transparent;
        }
        
        .delivery-item:hover {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateX(3px);
            border-color: #cbd5e1;
        }
        
        .delivery-item.pending { border-left-color: #f59e0b; }
        .delivery-item.shipping { border-left-color: #3b82f6; }
        .delivery-item.delivered { border-left-color: #10b981; }
        .delivery-item.cancelled { border-left-color: #ef4444; }
        
        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 0.75rem;
        }
        
        .delivery-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 1rem;
        }
        
        .delivery-id {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .status-badge {
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-shipping { background: #dbeafe; color: #1e40af; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .status-processing { background: #e0e7ff; color: #3730a3; }
        .status-received { background: #dcfce7; color: #166534; }
        
        .delivery-info {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.6;
        }
        
        .delivery-info i {
            width: 16px;
            margin-right: 0.5rem;
        }
        
        /* Buttons */
        .btn-modern {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }
        
        .btn-primary-modern:hover {
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
            transform: translateY(-2px);
        }
        
        .btn-outline-modern {
            background: white;
            color: #1e40af;
            border: 2px solid #3b82f6;
        }
        
        .btn-outline-modern:hover {
            background: #3b82f6;
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #9ca3af;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .content-wrapper {
                padding: 0 1rem 1rem;
            }
            
            .top-nav {
                padding: 1rem;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
        
        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .loading {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="d-flex justify-content-between align-items-center">
            <div class="logo">
                <i class="bi bi-truck-front-fill"></i>
                <span>Courier Dashboard</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Profile Link -->
                <a href="profile.php" class="user-profile text-decoration-none">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-role">Courier</div>
                    </div>
                </a>
                
                <!-- Logout Button -->
                <a href="../auth/logout.php" class="btn-logout text-decoration-none">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card blue">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-label">Total Deliveries</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="text-muted" style="font-size: 0.875rem;">All time</div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="text-muted" style="font-size: 0.875rem;">Successfully delivered</div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon">
                    <i class="bi bi-truck"></i>
                </div>
                <div class="stat-label">Shipping</div>
                <div class="stat-value"><?php echo $stats['shipping']; ?></div>
                <div class="text-muted" style="font-size: 0.875rem;">Out for delivery</div>
            </div>
            
            <div class="stat-card red">
                <div class="stat-icon">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div class="stat-label">Cancelled</div>
                <div class="stat-value"><?php echo $stats['cancelled']; ?></div>
                <div class="text-muted" style="font-size: 0.875rem;">Failed deliveries</div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Map Section -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <div class="card-title-modern">
                        <i class="bi bi-map-fill"></i>
                        Delivery Map
                    </div>
                    <button class="btn-modern btn-outline-modern btn-sm" onclick="refreshMap()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
                <div id="deliveryMap"></div>
                <div id="mapStatus" class="mt-3 text-center text-muted small"></div>
            </div>

            <!-- Deliveries List -->
            <div class="card-modern">
                <div class="card-header-modern">
                    <div class="card-title-modern">
                        <i class="bi bi-list-check"></i>
                        My Deliveries
                    </div>
                    <span class="badge rounded-pill" style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 0.5rem 1rem;">
                        <?php echo count($all_deliveries); ?> orders
                    </span>
                </div>
                
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="filterDeliveries('all')">
                        <i class="bi bi-grid-fill"></i> All
                    </button>
                    <button class="filter-tab" onclick="filterDeliveries('shipping')">
                        <i class="bi bi-truck"></i> Shipping
                    </button>
                    <button class="filter-tab" onclick="filterDeliveries('delivered')">
                        <i class="bi bi-check-circle"></i> Delivered
                    </button>
                    <button class="filter-tab" onclick="filterDeliveries('cancelled')">
                        <i class="bi bi-x-circle"></i> Cancelled
                    </button>
                </div>
                
                <!-- Deliveries List -->
                <div class="delivery-list">
                    <?php if (empty($all_deliveries)): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h5>No Deliveries</h5>
                            <p>You don't have any assigned deliveries yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($all_deliveries as $d): ?>
                            <?php
                            $address = htmlspecialchars($d['house'] . ', ' . $d['barangay_name'] . ', ' . $d['municipality_name'] . ', ' . $d['province_name']);
                            $statusClass = strtolower($d['status']);
                            ?>
                            <div class="delivery-item <?php echo $statusClass; ?>" 
                                 data-status="<?php echo $statusClass; ?>"
                                 data-id="<?php echo $d['order_id']; ?>"
                                 onclick="viewDelivery(<?php echo $d['order_id']; ?>)">
                                <div class="delivery-header">
                                    <div>
                                        <div class="delivery-name"><?php echo htmlspecialchars($d['fullname']); ?></div>
                                        <div class="delivery-id">
                                            <i class="bi bi-hash"></i> JT<?php echo $d['order_id']; ?>PH
                                        </div>
                                    </div>
                                    <span class="status-badge status-<?php echo $statusClass; ?>">
                                        <?php echo $d['status']; ?>
                                    </span>
                                </div>
                                <div class="delivery-info">
                                    <div><i class="bi bi-geo-alt-fill"></i> <?php echo $address; ?></div>
                                    <div><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($d['contact_number']); ?></div>
                                    <div><i class="bi bi-cash"></i> ₱<?php echo number_format($d['amount'], 2); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        console.log('🚀 Courier Dashboard Loaded');
        
        // Delivery locations from PHP
        const deliveryLocations = <?php echo json_encode(array_map(function($d) {
            return [
                'id' => $d['order_id'],
                'name' => $d['fullname'],
                'address' => $d['house'] . ', ' . $d['barangay_name'] . ', ' . $d['municipality_name'] . ', ' . $d['province_name'],
                'status' => $d['status']
            ];
        }, $deliveries)); ?>;
        
        console.log('📍 Delivery locations:', deliveryLocations.length);
        
        let map = null;
        let markers = [];
        
        // Initialize Map
        function initMap() {
            const mapDiv = document.getElementById('deliveryMap');
            const statusDiv = document.getElementById('mapStatus');
            
            try {
                statusDiv.innerHTML = '<i class="bi bi-hourglass-split loading"></i> Loading map...';
                
                // Create map - centered on Philippines
                map = L.map('deliveryMap').setView([14.5995, 120.9842], 11);
                
                // Add tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(map);
                
                // Show delivery locations on map
                if (deliveryLocations.length > 0) {
                    statusDiv.innerHTML = `<i class="bi bi-hourglass-split loading"></i> Loading ${deliveryLocations.length} delivery location(s)...`;
                    
                    let loadedCount = 0;
                    
                    // For each delivery, try to geocode with barangay
                    deliveryLocations.forEach((delivery, index) => {
                        // Extract address parts: House, Barangay, Municipality, Province
                        const addressParts = delivery.address.split(',').map(p => p.trim());
                        const barangay = addressParts[1] || '';  // 2nd part
                        const municipality = addressParts[2] || '';  // 3rd part
                        const province = addressParts[3] || '';  // 4th part
                        
                        console.log(`📍 Looking up: ${barangay}, ${municipality}, ${province}`);
                        
                        // Geocode using barangay + municipality + province for better accuracy
                        const searchQuery = encodeURIComponent(`${barangay}, ${municipality}, ${province}, Philippines`);
                        
                        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${searchQuery}&limit=1`)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    const lat = parseFloat(data[0].lat);
                                    const lon = parseFloat(data[0].lon);
                                    
                                    console.log(`✅ Found: ${municipality} at`, lat, lon);
                                    
                                    // Add marker
                                    const marker = L.marker([lat, lon])
                                        .addTo(map)
                                        .bindPopup(`
                                            <div style="min-width: 220px;">
                                                <strong>${delivery.name}</strong><br>
                                                <small class="text-muted">#JT${delivery.id}PH</small><br>
                                                <div class="mt-2">
                                                    <i class="bi bi-geo-alt-fill text-danger"></i> ${delivery.address}
                                                </div>
                                                <span class="badge bg-primary mt-2">${delivery.status}</span><br>
                                                <a href="view_order.php?order_id=${delivery.id}" class="btn btn-sm btn-primary mt-2 w-100">
                                                    <i class="bi bi-eye"></i> View Order
                                                </a>
                                            </div>
                                        `);
                                    
                                    markers.push(marker);
                                    loadedCount++;
                                    
                                    // Center map on first marker
                                    if (index === 0) {
                                        map.setView([lat, lon], 13);
                                    }
                                    
                                    statusDiv.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> Showing ${loadedCount} delivery location(s) on map`;
                                } else {
                                    console.warn(`⚠️ Could not find: ${municipality}`);
                                    loadedCount++;
                                    statusDiv.innerHTML = `<i class="bi bi-exclamation-triangle text-warning"></i> Location not found. Address: ${delivery.address}`;
                                }
                            })
                            .catch(error => {
                                console.error(`❌ Error:`, error);
                            });
                    });
                    
                } else {
                    statusDiv.innerHTML = '<i class="bi bi-info-circle text-muted"></i> No active deliveries';
                }
                
                console.log('✅ Map ready');
                
                console.log('✅ Map initialized');
                
            } catch (error) {
                console.error('❌ Map error:', error);
                statusDiv.innerHTML = '<i class="bi bi-exclamation-triangle text-danger"></i> Map unavailable';
                mapDiv.innerHTML = '<div class="empty-state"><i class="bi bi-map"></i><p>Map temporarily unavailable</p></div>';
            }
        }
        
        // Refresh Map
        function refreshMap() {
            console.log('🔄 Refreshing map...');
            if (map) {
                map.invalidateSize();
                document.getElementById('mapStatus').innerHTML = '<i class="bi bi-check-circle-fill text-success"></i> Map refreshed';
            } else {
                initMap();
            }
        }
        
        // Filter Deliveries
        function filterDeliveries(status) {
            console.log('🔍 Filtering:', status);
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filter items
            const items = document.querySelectorAll('.delivery-item');
            let visibleCount = 0;
            
            items.forEach(item => {
                const itemStatus = item.getAttribute('data-status');
                
                if (status === 'all' || itemStatus === status) {
                    item.style.display = '';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            console.log(`✅ Showing ${visibleCount} deliveries`);
        }
        
        // View Delivery
        function viewDelivery(orderId) {
            console.log('📦 Opening order:', orderId);
            window.location.href = `view_order.php?order_id=${orderId}`;
        }
        
        // Initialize on load
        window.addEventListener('load', function() {
            console.log('✅ Page loaded');
            setTimeout(initMap, 500);
        });
        
        console.log('✅ All scripts loaded');
    </script>
</body>
</html>
