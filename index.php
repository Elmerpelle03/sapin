<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
	$date = date("Y-m-d");
	
	// Fetch user type name
	$user_role = 'Admin'; // Default
	if (isset($_SESSION['usertype_id'])) {
		$stmt = $pdo->prepare("SELECT usertype_name FROM usertype WHERE usertype_id = :usertype_id");
		$stmt->execute([':usertype_id' => $_SESSION['usertype_id']]);
		$result = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($result) {
			$user_role = $result['usertype_name'];
		}
	}
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

	<link rel="canonical" href="https://demo-basic.adminkit.io/" />

	<title>Dashboard - Sapin Bedsheets</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<style>
		/* Global theme with gradient background */
		body { 
			background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
			min-height: 100vh;
		}
		
		/* Enhanced page header */
		.page-header {
			background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
			margin: -24px -24px 24px -24px;
			padding: 32px 24px;
			border-radius: 0 0 24px 24px;
			box-shadow: 0 8px 32px rgba(102, 126, 234, 0.2);
			color: white;
		}
		
		.page-header h1 {
			font-weight: 800;
			font-size: 2rem;
			margin: 0;
			text-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		
		.page-header p {
			margin: 8px 0 0 0;
			opacity: 0.9;
			font-size: 0.95rem;
		}
		
		.card { 
			border: none; 
			border-radius: 20px; 
			box-shadow: 0 4px 20px rgba(17,24,39,.06); 
			transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
			overflow: hidden;
			background: white;
			backdrop-filter: blur(10px);
		}
		
		.card:hover { 
			transform: translateY(-8px) scale(1.01); 
			box-shadow: 0 20px 40px rgba(17,24,39,.12); 
		}
		
		.card-header { 
			background: linear-gradient(to bottom, #ffffff 0%, #fafbfc 100%); 
			border-bottom: 2px solid #f1f5f9; 
			border-top-left-radius: 20px; 
			border-top-right-radius: 20px;
			padding: 20px 24px;
		}
		
		.card-title { 
			font-weight: 700; 
			color: #0f172a;
			font-size: 1.125rem;
		}

		/* Financial Overview Header */
		.financial-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			border: none;
			border-radius: 16px;
			padding: 20px 24px;
			box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
			animation: slideDown 0.5s ease-out;
		}
		
		@keyframes slideDown {
			from { opacity: 0; transform: translateY(-20px); }
			to { opacity: 1; transform: translateY(0); }
		}

		/* Financial Metric Cards */
		.financial-card {
			position: relative;
			overflow: hidden;
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		}
		
		.financial-card::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: linear-gradient(135deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.1) 100%);
			opacity: 0;
			transition: opacity 0.3s ease;
		}
		
		.financial-card:hover::before {
			opacity: 1;
		}
		
		.financial-card:hover {
			transform: translateY(-8px) scale(1.02);
			box-shadow: 0 16px 32px rgba(17,24,39,.2);
		}
		
		.financial-card .card-body {
			padding: 24px;
		}
		
		.financial-card h3 {
			font-size: 2rem;
			font-weight: 800;
			margin: 8px 0;
			letter-spacing: -0.5px;
		}
		
		.financial-card .metric-label {
			font-size: 0.75rem;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 4px;
		}
		
		.financial-card .metric-subtitle {
			font-size: 0.813rem;
			opacity: 0.7;
		}

		/* Icon circles with pulse animation */
		.icon-circle { 
			width: 56px; 
			height: 56px; 
			display: flex; 
			align-items: center; 
			justify-content: center; 
			border-radius: 16px;
			transition: all 0.3s ease;
			position: relative;
		}
		
		.financial-card:hover .icon-circle {
			transform: rotate(10deg) scale(1.1);
		}
		
		.icon-circle::after {
			content: '';
			position: absolute;
			width: 100%;
			height: 100%;
			border-radius: 16px;
			animation: pulse 2s infinite;
			opacity: 0;
		}
		
		@keyframes pulse {
			0% { transform: scale(1); opacity: 0.5; }
			50% { transform: scale(1.1); opacity: 0; }
			100% { transform: scale(1); opacity: 0; }
		}
		
		.icon-blue { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #2563eb; }
		.icon-green { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #059669; }
		.icon-yellow { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #d97706; }
		.icon-red { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #dc2626; }
		
		.icon-blue::after { background: #2563eb; }
		.icon-green::after { background: #059669; }
		.icon-yellow::after { background: #d97706; }
		.icon-red::after { background: #dc2626; }

		/* Stat cards - Enhanced */
		.stat-card {
			position: relative;
			overflow: hidden;
		}
		
		.stat-card::after {
			content: '';
			position: absolute;
			top: -50%;
			right: -50%;
			width: 200%;
			height: 200%;
			background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
			opacity: 0;
			transition: opacity 0.3s ease;
		}
		
		.stat-card:hover::after {
			opacity: 1;
		}
		
		.stat-card .card-title {
			font-size: 0.875rem;
			font-weight: 600;
			color: #64748b;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 12px;
		}
		
		.stat-card h1 {
			font-size: 2.5rem;
			font-weight: 800;
			color: #0f172a;
			margin: 16px 0;
			letter-spacing: -1px;
		}
		
		.stat-card .stat {
			width: 48px;
			height: 48px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 12px;
			background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
			transition: all 0.3s ease;
		}
		
		.stat-card:hover .stat {
			transform: rotate(-10deg) scale(1.1);
		}
		
		.change-badge { 
			padding: 4px 12px; 
			border-radius: 20px; 
			font-size: .75rem; 
			font-weight: 700;
			display: inline-flex;
			align-items: center;
			gap: 4px;
		}
		.change-up { background: #ecfdf5; color: #059669; }
		.change-down { background: #fef2f2; color: #dc2626; }
		.change-neutral { background: #f1f5f9; color: #475569; }

		/* Chart container - Enhanced */
		.chart-sm { 
			height: 320px; 
			padding: 16px;
		}
		
		.chart-card {
			background: linear-gradient(to bottom, #ffffff 0%, #f9fafb 100%);
		}
		
		.chart-card .card-header {
			background: transparent;
			border-bottom: 2px solid #f1f5f9;
			padding: 20px 24px;
		}
		
		.chart-card .card-title {
			font-size: 1.125rem;
			font-weight: 700;
			color: #0f172a;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		/* Recent Orders table */
		.table thead th { 
			background: #f1f5f9; 
			color: #0f172a; 
			font-weight: 700; 
			border-bottom: 0;
			padding: 1rem;
		}
		.table tbody tr { 
			transition: all 0.2s ease;
		}
		.table tbody tr:hover { 
			background: #f8fafc;
			box-shadow: 0 2px 8px rgba(15,23,42,.04);
		}
		.table tbody td {
			padding: 1rem;
			vertical-align: middle;
		}
		.badge-status { font-weight: 700; }
		.badge-status.pending { background: #f59e0b; }
		.badge-status.processing { background: #3b82f6; }
		.badge-status.shipping { background: #14b8a6; }
		.badge-status.delivered,.badge-status.received { background: #22c55e; }
		.badge-status.cancelled { background: #ef4444; }

		/* Low stock alert */
		.alert-low { 
			border-radius: 10px; 
			padding: .8rem 1rem; 
			background: #fffbeb; 
			color: #92400e; 
			border: 1px solid #fde68a;
			transition: all 0.2s ease;
			cursor: pointer;
			text-decoration: none;
			display: block;
		}
		.alert-low:hover { 
			background: #fef3c7; 
			border-color: #fbbf24;
			transform: translateX(4px);
			box-shadow: 0 2px 8px rgba(251, 191, 36, 0.2);
		}
		.alert-low .restock-btn {
			font-size: 0.75rem;
			padding: 0.25rem 0.5rem;
			border-radius: 6px;
			background: #f59e0b;
			color: white;
			border: none;
			font-weight: 600;
		}
		.alert-low .restock-btn:hover {
			background: #d97706;
		}
		.alert-ok { border-radius: 10px; padding: .6rem .8rem; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
		
		/* Fade in animation for cards */
		.fade-in-up {
			animation: fadeInUp 0.6s ease-out forwards;
			opacity: 0;
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
		
		.fade-in-up:nth-child(1) { animation-delay: 0.1s; }
		.fade-in-up:nth-child(2) { animation-delay: 0.2s; }
		.fade-in-up:nth-child(3) { animation-delay: 0.3s; }
		.fade-in-up:nth-child(4) { animation-delay: 0.4s; }

		/* Pending Orders: sticky header + optional scroll wrapper */
		.pending-orders .table-wrap { max-height: 420px; overflow: auto; }
		.pending-orders .table { margin-bottom: 0; }
		.pending-orders thead th { position: sticky; top: 0; z-index: 1; background: #f1f5f9; }
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'index'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">

					<!-- Enhanced Page Header -->
					<div class="page-header">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h1><i class="bi bi-speedometer2 me-3"></i>Analytics Dashboard</h1>
								<p class="mb-0">
									<i class="bi bi-calendar3 me-2"></i><?= date('l, F j, Y') ?> 
									<span class="mx-2">•</span>
									<i class="bi bi-clock me-2"></i><?= date('g:i A') ?>
								</p>
							</div>
							<div class="text-end">
								<div class="badge bg-white bg-opacity-25 text-white px-3 py-2" style="font-size: 0.9rem;">
									<i class="bi bi-person-circle me-2"></i>
									Welcome, <?= htmlspecialchars($user_role) ?>
								</div>
							</div>
						</div>
					</div>

					<?php if (isset($_SESSION['usertype_id']) && $_SESSION['usertype_id'] == 5): ?>
					<?php
						// Financial metrics for Super Admin only
						$current_month_start = date('Y-m-01');
						$current_month_end = date('Y-m-t');
						
						// Get this month's revenue (POS + Online Orders)
						$revenue_stmt = $pdo->prepare("
							SELECT COALESCE(SUM(total_amount), 0) as revenue
							FROM pos_sales
							WHERE DATE(sale_date) BETWEEN :from AND :to
							AND status = 'completed'
						");
						$revenue_stmt->execute([':from' => $current_month_start, ':to' => $current_month_end]);
						$pos_revenue = $revenue_stmt->fetchColumn();
						
						$orders_revenue_stmt = $pdo->prepare("
							SELECT COALESCE(SUM(amount), 0) as revenue
							FROM orders
							WHERE DATE(date) BETWEEN :from AND :to
							AND status IN ('Delivered', 'Received')
						");
						$orders_revenue_stmt->execute([':from' => $current_month_start, ':to' => $current_month_end]);
						$online_revenue = $orders_revenue_stmt->fetchColumn();
						
						$total_revenue = $pos_revenue + $online_revenue;
						
						// Get this month's expenses
						$expenses_stmt = $pdo->prepare("
							SELECT COALESCE(SUM(amount), 0) as total
							FROM expenses
							WHERE expense_date BETWEEN :from AND :to
						");
						$expenses_stmt->execute([':from' => $current_month_start, ':to' => $current_month_end]);
						$total_expenses = $expenses_stmt->fetchColumn();
						
						// Calculate profit
						$net_profit = $total_revenue - $total_expenses;
						$profit_margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0;
					?>
					<!-- Financial Overview Section (Super Admin Only) -->
					<div class="row mb-3">
						<div class="col-12">
							<div class="alert financial-header">
								<div class="d-flex align-items-center justify-content-between">
									<div>
										<h4 class="mb-1"><i class="bi bi-graph-up-arrow me-2"></i>Financial Overview</h4>
										<p class="mb-0 opacity-75"><?= date('F Y') ?> Performance Metrics</p>
									</div>
									<div>
										<i class="bi bi-currency-exchange" style="font-size: 2.5rem; opacity: 0.3;"></i>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row mb-4">
						<div class="col-xl-3 col-md-6 mb-3 fade-in-up">
							<a href="profitloss.php" style="text-decoration: none;">
								<div class="card financial-card" style="border-left: 4px solid #10b981;">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-start">
											<div class="flex-grow-1">
												<p class="metric-label text-muted">TOTAL REVENUE</p>
												<h3 class="text-success">₱<?= number_format($total_revenue, 2) ?></h3>
												<small class="metric-subtitle text-muted">
													<i class="bi bi-shop me-1"></i>POS + Online Orders
												</small>
											</div>
											<div class="icon-circle icon-green">
												<i class="bi bi-cash-stack" style="font-size: 1.75rem;"></i>
											</div>
										</div>
									</div>
								</div>
							</a>
						</div>
						<div class="col-xl-3 col-md-6 mb-3 fade-in-up">
							<a href="expenses.php" style="text-decoration: none;">
								<div class="card financial-card" style="border-left: 4px solid #ef4444;">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-start">
											<div class="flex-grow-1">
												<p class="metric-label text-muted">TOTAL EXPENSES</p>
												<h3 class="text-danger">₱<?= number_format($total_expenses, 2) ?></h3>
												<small class="metric-subtitle text-muted">
													<i class="bi bi-receipt me-1"></i>All Categories
												</small>
											</div>
											<div class="icon-circle icon-red">
												<i class="bi bi-wallet2" style="font-size: 1.75rem;"></i>
											</div>
										</div>
									</div>
								</div>
							</a>
						</div>
						<div class="col-xl-3 col-md-6 mb-3 fade-in-up">
							<a href="profitloss.php" style="text-decoration: none;">
								<div class="card financial-card" style="border-left: 4px solid #3b82f6;">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-start">
											<div class="flex-grow-1">
												<p class="metric-label text-muted">NET PROFIT</p>
												<h3 class="<?= $net_profit >= 0 ? 'text-primary' : 'text-danger' ?>">
													₱<?= number_format($net_profit, 2) ?>
												</h3>
												<small class="metric-subtitle text-muted">
													<i class="bi bi-calculator me-1"></i>Revenue - Expenses
												</small>
											</div>
											<div class="icon-circle icon-blue">
												<i class="bi bi-graph-up" style="font-size: 1.75rem;"></i>
											</div>
										</div>
									</div>
								</div>
							</a>
						</div>
						<div class="col-xl-3 col-md-6 mb-3 fade-in-up">
							<a href="profitloss.php" style="text-decoration: none;">
								<div class="card financial-card" style="border-left: 4px solid #f59e0b;">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-start">
											<div class="flex-grow-1">
												<p class="metric-label text-muted">PROFIT MARGIN</p>
												<h3 class="text-warning"><?= number_format($profit_margin, 1) ?>%</h3>
												<small class="metric-subtitle text-muted">
													<i class="bi bi-pie-chart me-1"></i>Profitability Rate
												</small>
											</div>
											<div class="icon-circle icon-yellow">
												<i class="bi bi-percent" style="font-size: 1.75rem;"></i>
											</div>
										</div>
									</div>
								</div>
							</a>
						</div>
					</div>
					<?php endif; ?>

					<div class="row">
						<div class="col-xl-6 col-xxl-5 d-flex">
							<div class="w-100">
								<div class="row">
									<div class="col-sm-6 mb-3">
										<?php 
											$stmt = $pdo->prepare("SELECT COUNT(*) FROM materials");
											$stmt->execute();
											$material_count = $stmt->fetchColumn();
										?>
										<a href="materialinventory.php" style="text-decoration: none;">
											<div class="card stat-card">
												<div class="card-body">
													<div class="d-flex justify-content-between align-items-start">
														<div>
															<h5 class="card-title"><i class="bi bi-box-seam me-2"></i>Materials</h5>
														</div>
														<div class="stat text-primary">
															<i class="align-middle" data-feather="package"></i>
														</div>
													</div>
													<h1><?php echo $material_count; ?></h1>
													<div class="d-flex align-items-center gap-2">
														<span class="badge bg-primary bg-opacity-10 text-primary">
															<i class="bi bi-arrow-right me-1"></i>View Inventory
														</span>
													</div>
												</div>
											</div>
										</a>
										<?php 
											// Today's date
											$today = date('Y-m-d');

											// Date 7 days ago
											$last_week = date('Y-m-d', strtotime('-7 days'));

											// Get today's visitors
											$stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE DATE(visit_time) = :date");
											$stmt->execute([':date' => $today]);
											$visitor_today = $stmt->fetchColumn() ?? 0;

											// Get visitors last week
											$stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE DATE(visit_time) = :last_week_date");
											$stmt->execute([':last_week_date' => $last_week]);
											$visitor_last_week = $stmt->fetchColumn() ?? 0;

											// Calculate % difference
											if ($visitor_last_week > 0) {
												$change = (($visitor_today - $visitor_last_week) / $visitor_last_week) * 100;
											} else {
												$change = 0; // Avoid division by zero
											}

											// Format and classify change
											$change_formatted = number_format(abs($change), 2);

											if ($change > 0) {
												$change_icon = 'mdi-arrow-top-right';
												$change_color = 'text-success';
											} elseif ($change < 0) {
												$change_icon = 'mdi-arrow-bottom-right';
												$change_color = 'text-danger';
											} else {
												$change_icon = 'mdi-arrow-right';
												$change_color = 'text-secondary';
											}
										?>
										<a href="reports.php" style="text-decoration: none;">
											<div class="card stat-card">
												<div class="card-body">
													<div class="d-flex justify-content-between align-items-start">
														<div>
															<h5 class="card-title"><i class="bi bi-people me-2"></i>Visitors Today</h5>
														</div>
														<div class="stat text-primary">
															<i class="align-middle" data-feather="users"></i>
														</div>
													</div>
													<h1><?php echo $visitor_today; ?></h1>
													<div class="d-flex align-items-center gap-2">
														<span class="change-badge <?php echo $change > 0 ? 'change-up' : ($change < 0 ? 'change-down' : 'change-neutral'); ?>">
															<i class="bi bi-<?php echo $change > 0 ? 'arrow-up' : ($change < 0 ? 'arrow-down' : 'dash'); ?>"></i>
															<?php echo $change_formatted; ?>%
														</span>
														<span class="text-muted small">vs last week</span>
													</div>
												</div>
											</div>
										</a>
									</div>
									<div class="col-sm-6 mb-3">
										<?php 
											// Dates
											$today = date('Y-m-d');
											$last_week = date('Y-m-d', strtotime('-7 days'));
											$start = $today . ' 00:00:00';
											$end = $today . ' 23:59:59';

											
											$stmt = $pdo->prepare("SELECT SUM(amount) FROM orders WHERE (status = 'Delivered' OR status = 'Received') AND date BETWEEN :start AND :end");
											$stmt->execute([':start' => $start, ':end' => $end]);
											$sales_today = $stmt->fetchColumn() ?? 0;

											// Sales same day last week
											$stmt = $pdo->prepare("SELECT SUM(amount) FROM orders WHERE (status = 'Delivered' OR status = 'Received') AND DATE(date) = :last_week");
											$stmt->execute([':last_week' => $last_week]);
											$sales_last_week = $stmt->fetchColumn() ?? 0;

											// Calculate % change
											if ($sales_last_week > 0) {
												$sales_change = (($sales_today - $sales_last_week) / $sales_last_week) * 100;
											} else {
												$sales_change = 0;
											}

											// Format
											$sales_change_formatted = number_format(abs($sales_change), 2);
										?>
										<a href="orders.php" style="text-decoration: none;">
											<div class="card stat-card">
												<div class="card-body">
													<div class="d-flex justify-content-between align-items-start">
														<div>
															<h5 class="card-title"><i class="bi bi-currency-exchange me-2"></i>Sales Today</h5>
														</div>
														<div class="stat text-primary">
															<i class="align-middle" data-feather="shopping-bag"></i>
														</div>
													</div>
													<h1>₱<?php echo number_format($sales_today, 2); ?></h1>
													<div class="d-flex align-items-center gap-2">
														<span class="change-badge <?php echo $sales_change > 0 ? 'change-up' : ($sales_change < 0 ? 'change-down' : 'change-neutral'); ?>">
															<i class="bi bi-<?php echo $sales_change > 0 ? 'arrow-up' : ($sales_change < 0 ? 'arrow-down' : 'dash'); ?>"></i>
															<?php echo $sales_change_formatted; ?>%
														</span>
														<span class="text-muted small">vs last week</span>
													</div>
												</div>
											</div>
										</a>
										<?php 
											// Today's date
											$today = date('Y-m-d');

											// Date 7 days ago
											$last_week = date('Y-m-d', strtotime('-7 days'));

											// Get today's orders
											$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(date) = :date");
											$stmt->execute([':date' => $today]);
											$orders_today = $stmt->fetchColumn() ?? 0;

											// Get last week's orders
											$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(date) = :last_week_date");
											$stmt->execute([':last_week_date' => $last_week]);
											$orders_last_week = $stmt->fetchColumn() ?? 0;

											// Calculate % difference
											if ($orders_last_week > 0) {
												$change = (($orders_today - $orders_last_week) / $orders_last_week) * 100;
											} else {
												$change = 0; // Avoid division by zero
											}

											// Format and classify change
											$change_formatted = number_format(abs($change), 2);

											if ($change > 0) {
												$change_icon = 'mdi-arrow-top-right';
												$change_color = 'text-success';
											} elseif ($change < 0) {
												$change_icon = 'mdi-arrow-bottom-right';
												$change_color = 'text-danger';
											} else {
												$change_icon = 'mdi-arrow-right';
												$change_color = 'text-secondary';
											}
										?>
										<a href="orders.php" style="text-decoration: none;">
											<div class="card stat-card">
												<div class="card-body">
													<div class="d-flex justify-content-between align-items-start">
														<div>
															<h5 class="card-title"><i class="bi bi-cart-check me-2"></i>Orders Today</h5>
														</div>
														<div class="stat text-primary">
															<i class="align-middle" data-feather="shopping-cart"></i>
														</div>
													</div>
													<h1><?php echo $orders_today; ?></h1>
													<div class="d-flex align-items-center gap-2">
														<span class="change-badge <?php echo $change > 0 ? 'change-up' : ($change < 0 ? 'change-down' : 'change-neutral'); ?>">
															<i class="bi bi-<?php echo $change > 0 ? 'arrow-up' : ($change < 0 ? 'arrow-down' : 'dash'); ?>"></i>
															<?php echo $change_formatted; ?>%
														</span>
														<span class="text-muted small">vs last week</span>
													</div>
												</div>
											</div>
										</a>
									</div>
								</div>
							</div>
						</div>

						<div class="col-xl-6 col-xxl-7">
							<div class="card chart-card flex-fill w-100">
								<div class="card-header">
									<h5 class="card-title mb-0">
										<i class="bi bi-graph-up me-2"></i>Monthly Sales Performance
									</h5>
									<p class="text-muted small mb-0 mt-1">Revenue trends for the current year</p>
								</div>
								<div class="card-body py-3">
									<div class="chart chart-sm">
										<canvas id="chartjs-dashboard-line"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<?php 
						$per_page = 3;
						$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
						$offset = ($page - 1) * $per_page;

						$stmt = $pdo->prepare("SELECT order_id, fullname, date, amount, status FROM orders WHERE status = 'Pending' ORDER BY date DESC LIMIT :limit OFFSET :offset");
						$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
						$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
						$stmt->execute();
						$pending_orders = $stmt->fetchAll();

						$stmt = $pdo->prepare("SELECT COUNT(order_id) FROM orders WHERE status = 'Pending'");
						$stmt->execute();
						$pending_count = $stmt->fetchColumn();
						$total_pages = max(1, (int)ceil(($pending_count ?? 0) / $per_page));
					?>
						<div class="col-12 col-lg-8 col-xxl-9 d-flex">
							<div class="card flex-fill pending-orders" id="pending-orders">
								<div class="card-header sticky-top">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h5 class="card-title mb-0">
												<i class="bi bi-clock-history me-2"></i>Pending Orders
											</h5>
											<p class="text-muted small mb-0 mt-1">Orders awaiting processing</p>
										</div>
										<span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 8px 16px;">
											<?php echo $pending_count ?? 0; ?> Pending
										</span>
									</div>
								</div>
								<div class="table-wrap">
							<table class="table table-hover my-0">
									<thead>
										<tr>
											<th>ID</th>
											<th>Full Name</th>
											<th class="d-none d-xl-table-cell">Date</th>
											<th class="d-none d-xl-table-cell">Status</th>
											<th>Amount</th>
											<th class="d-none d-md-table-cell">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php if (empty($pending_orders)): ?>
											<tr>
												<td colspan="6" class="text-center py-4">
													<i class="bi bi-inbox" style="font-size: 2rem; color: #94a3b8;"></i>
													<p class="text-muted mb-0 mt-2">No pending orders</p>
												</td>
											</tr>
										<?php else: ?>
											<?php foreach($pending_orders as $row): ?>
												<tr style="cursor: pointer;" onclick="window.location.href='view_order.php?order_id=<?php echo $row['order_id']; ?>'">
													<td>
														<strong class="text-primary">#<?php echo $row['order_id'] ?></strong>
													</td>
													<td>
														<div class="d-flex align-items-center">
															<i class="bi bi-person-circle me-2 text-muted"></i>
															<strong><?php echo htmlspecialchars($row['fullname']) ?></strong>
														</div>
													</td>
													<td class="d-none d-xl-table-cell">
														<small class="text-muted">
															<i class="bi bi-calendar3 me-1"></i><?php echo date("M j, Y", strtotime($row['date'])); ?>
															<br>
															<i class="bi bi-clock me-1"></i><?php echo date("g:i A", strtotime($row['date'])); ?>
														</small>
													</td>
													<td class="d-none d-xl-table-cell">
														<span class="badge bg-warning text-dark" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;">
															<i class="bi bi-clock-history me-1"></i>Pending
														</span>
													</td>
													<td>
														<strong class="text-success" style="font-size: 1.05rem;">₱<?php echo number_format($row['amount'], 2); ?></strong>
													</td>
													<td class="d-none d-md-table-cell">
														<a href="view_order.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary" onclick="event.stopPropagation();">
															<i class="bi bi-eye me-1"></i>View
														</a>
													</td>
												</tr>
											<?php endforeach; ?>
										<?php endif; ?>
									</tbody>
								</table>
								</div>
								<div class="card-footer d-flex justify-content-between align-items-center">
									<?php $prev = max(1, $page - 1); $next = min($total_pages, $page + 1); ?>
									<a class="btn btn-sm btn-outline-secondary <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="index.php?p=<?php echo $prev; ?>#pending-orders">Prev</a>
									<div class="small text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></div>
									<a class="btn btn-sm btn-outline-primary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" href="index.php?p=<?php echo $next; ?>#pending-orders">Next</a>
								</div>
							</div>
						</div>
						<div class="col-12 col-lg-4 col-xxl-3 d-flex">
                            <?php 
                                // Pagination for Low Stock accordion
                                $lp_per_page = 5; // products per page
                                $lm_per_page = 5; // materials per page
                                $lp_page = isset($_GET['lp']) ? max(1, (int)$_GET['lp']) : 1;
                                $lm_page = isset($_GET['lm']) ? max(1, (int)$_GET['lm']) : 1;
                                $lp_offset = ($lp_page - 1) * $lp_per_page;
                                $lm_offset = ($lm_page - 1) * $lm_per_page;

                                // Totals
                                $stmt = $pdo->prepare("SELECT COUNT(m.material_id) FROM materials m WHERE m.stock <= m.reorder_point");
                                $stmt->execute();
                                $low_stock_materials_total = (int)$stmt->fetchColumn();

                                // Count products with low stock (including variants)
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(DISTINCT p.product_id) 
                                    FROM products p
                                    LEFT JOIN (
                                        SELECT 
                                            product_id, 
                                            SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock
                                        FROM product_variants
                                        GROUP BY product_id
                                    ) vs ON vs.product_id = p.product_id
                                    WHERE COALESCE(vs.total_stock, p.stock) <= p.restock_alert
                                    AND COALESCE(vs.total_stock, p.stock) > 0
                                ");
                                $stmt->execute();
                                $low_stock_products_total = (int)$stmt->fetchColumn();

                                $lm_total_pages = max(1, (int)ceil($low_stock_materials_total / $lm_per_page));
                                $lp_total_pages = max(1, (int)ceil($low_stock_products_total / $lp_per_page));

                                // Paginated lists
                                $stmt = $pdo->prepare("\n                                    SELECT m.material_id, m.material_name, m.stock, m.reorder_point, mu.materialunit_name\n                                    FROM materials m\n                                    JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id\n                                    WHERE m.stock <= m.reorder_point\n                                    ORDER BY m.stock ASC\n                                    LIMIT :limit OFFSET :offset\n                                ");
                                $stmt->bindValue(':limit', $lm_per_page, PDO::PARAM_INT);
                                $stmt->bindValue(':offset', $lm_offset, PDO::PARAM_INT);
                                $stmt->execute();
                                $low_stock_materials = $stmt->fetchAll();

                                $stmt = $pdo->prepare("\n                                    SELECT \n                                        p.product_id, \n                                        p.product_name, \n                                        p.price, \n                                        p.bundle_price, \n                                        p.description, \n                                        COALESCE(vs.total_stock, p.stock) AS stock, \n                                        p.category_id, \n                                        p.pieces_per_bundle, \n                                        p.material, \n                                        p.size, \n                                        p.restock_alert, \n                                        p.image_url\n                                    FROM products p\n                                    LEFT JOIN (\n                                        SELECT \n                                            product_id, \n                                            SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock\n                                        FROM product_variants\n                                        GROUP BY product_id\n                                    ) vs ON vs.product_id = p.product_id\n                                    WHERE COALESCE(vs.total_stock, p.stock) <= p.restock_alert\n                                    AND COALESCE(vs.total_stock, p.stock) > 0\n                                    ORDER BY COALESCE(vs.total_stock, p.stock) ASC\n                                    LIMIT :limit OFFSET :offset\n                                ");
                                $stmt->bindValue(':limit', $lp_per_page, PDO::PARAM_INT);
                                $stmt->bindValue(':offset', $lp_offset, PDO::PARAM_INT);
                                $stmt->execute();
                                $low_stock_products = $stmt->fetchAll();

                                // Which accordion is open (default: none)
                                $products_open = isset($_GET['lp']);
                                $materials_open = isset($_GET['lm']);
                            ?>
                            <div class="card flex-fill w-100 low-stock-panel">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Low Stock</h5>
                                </div>
                                <div class="card-body w-100 p-2">
                                    <div class="accordion" id="lowStockAccordion">
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingProducts">
                                                <button class="accordion-button <?php echo $products_open ? '' : 'collapsed'; ?> py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProducts" aria-expanded="<?php echo $products_open ? 'true' : 'false'; ?>" aria-controls="collapseProducts">
                                                    <i class="bi bi-box-seam me-2"></i>Products
                                                    <span class="badge bg-danger-subtle text-danger fw-bold ms-auto"><?php echo $low_stock_products_total; ?></span>
                                                </button>
                                            </h2>
                                            <div id="collapseProducts" class="accordion-collapse collapse <?php echo $products_open ? 'show' : ''; ?>" aria-labelledby="headingProducts" data-bs-parent="#lowStockAccordion">
                                                <div class="accordion-body p-2" style="max-height: 300px; overflow-y: auto;">
                                                    <?php if ($low_stock_products): ?>
                                                        <?php foreach ($low_stock_products as $product): ?>
                                                            <a href="products.php#product-<?php echo $product['product_id']; ?>" class="alert-low mb-2">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                        <strong class="name clamp-2"><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                                                        <div class="small mt-1">
                                                                            <span class="badge bg-danger"><?php echo htmlspecialchars($product['stock']); ?> units left</span>
                                                                        </div>
                                                                    </div>
                                                                    <button class="restock-btn" onclick="event.preventDefault(); window.location.href='products.php#product-<?php echo $product['product_id']; ?>'">
                                                                        <i class="bi bi-arrow-clockwise me-1"></i>Restock
                                                                    </button>
                                                                </div>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="alert-ok mb-0"><i class="bi bi-check-circle-fill me-1"></i>All products are well stocked!</div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                        <?php $lp_prev = max(1, $lp_page - 1); $lp_next = min($lp_total_pages, $lp_page + 1); ?>
                                                        <a class="btn btn-sm btn-outline-secondary <?php echo $lp_page <= 1 ? 'disabled' : ''; ?>" href="index.php?lp=<?php echo $lp_prev; ?>#collapseProducts">Prev</a>
                                                        <div class="small text-muted">Page <?php echo $lp_page; ?> of <?php echo $lp_total_pages; ?></div>
                                                        <a class="btn btn-sm btn-outline-primary <?php echo $lp_page >= $lp_total_pages ? 'disabled' : ''; ?>" href="index.php?lp=<?php echo $lp_next; ?>#collapseProducts">Next</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingMaterials">
                                                <button class="accordion-button <?php echo $materials_open ? '' : 'collapsed'; ?> py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaterials" aria-expanded="<?php echo $materials_open ? 'true' : 'false'; ?>" aria-controls="collapseMaterials">
                                                    <i class="bi bi-boxes me-2"></i>Materials
                                                    <span class="badge bg-danger-subtle text-danger fw-bold ms-auto"><?php echo $low_stock_materials_total; ?></span>
                                                </button>
                                            </h2>
                                            <div id="collapseMaterials" class="accordion-collapse collapse <?php echo $materials_open ? 'show' : ''; ?>" aria-labelledby="headingMaterials" data-bs-parent="#lowStockAccordion">
                                                <div class="accordion-body p-2" style="max-height: 300px; overflow-y: auto;">
                                                    <?php if ($low_stock_materials): ?>
                                                        <?php foreach ($low_stock_materials as $material): ?>
                                                            <a href="materialinventory.php#material-<?php echo $material['material_id']; ?>" class="alert-low mb-2">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                        <strong class="name clamp-2"><?php echo htmlspecialchars($material['material_name']); ?></strong>
                                                                        <div class="small mt-1">
                                                                            <span class="badge bg-danger"><?php echo htmlspecialchars($material['stock']); ?> <?php echo htmlspecialchars($material['materialunit_name']); ?> left</span>
                                                                        </div>
                                                                    </div>
                                                                    <button class="restock-btn" onclick="event.preventDefault(); window.location.href='materialinventory.php#material-<?php echo $material['material_id']; ?>'">
                                                                        <i class="bi bi-arrow-clockwise me-1"></i>Reorder
                                                                    </button>
                                                                </div>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="alert-ok mb-0"><i class="bi bi-check-circle-fill me-1"></i>All materials are well-stocked.</div>
                                                    <?php endif; ?>
                                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                                        <?php $lm_prev = max(1, $lm_page - 1); $lm_next = min($lm_total_pages, $lm_page + 1); ?>
                                                        <a class="btn btn-sm btn-outline-secondary <?php echo $lm_page <= 1 ? 'disabled' : ''; ?>" href="index.php?lm=<?php echo $lm_prev; ?>#collapseMaterials">Prev</a>
                                                        <div class="small text-muted">Page <?php echo $lm_page; ?> of <?php echo $lm_total_pages; ?></div>
                                                        <a class="btn btn-sm btn-outline-primary <?php echo $lm_page >= $lm_total_pages ? 'disabled' : ''; ?>" href="index.php?lm=<?php echo $lm_next; ?>#collapseMaterials">Next</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <a href="products.php" class="btn btn-sm btn-outline-primary">View all low stock products</a>
                                        <a href="materialinventory.php" class="btn btn-sm btn-outline-primary">View all low stock materials</a>
                                    </div>
                                </div>
                            </div>
                        </div>
						</div>
					</div>

					<footer class="footer">
						<div class="container-fluid">
							
						</div>
					</footer>
				</div>
			</div>

			<script src="js/app.js"></script>
			<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

			<script>
document.addEventListener("DOMContentLoaded", function() {
  fetch('backend/fetch_dashboard_data.php')
    .then(response => response.json())
    .then(chartData => {
      var ctx = document.getElementById("chartjs-dashboard-line").getContext("2d");
      
      // Create beautiful blue gradient
      var gradient = ctx.createLinearGradient(0, 0, 0, 300);
      gradient.addColorStop(0, "rgba(37, 99, 235, 0.8)");
      gradient.addColorStop(0.5, "rgba(59, 130, 246, 0.4)");
      gradient.addColorStop(1, "rgba(96, 165, 250, 0.05)");

      new Chart(ctx, {
        type: "line",
        data: {
          labels: chartData.labels,
          datasets: [{
            label: "Monthly Revenue",
            fill: true,
            backgroundColor: gradient,
            borderColor: "#2563eb",
            borderWidth: 3,
            pointBackgroundColor: "#2563eb",
            pointBorderColor: "#fff",
            pointBorderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointHoverBackgroundColor: "#1e40af",
            pointHoverBorderColor: "#fff",
            pointHoverBorderWidth: 3,
            data: chartData.data,
            tension: 0.4
          }]
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          plugins: {
            legend: {
              display: true,
              position: 'top',
              labels: {
                font: {
                  size: 13,
                  weight: '600',
                  family: "'Inter', sans-serif"
                },
                color: '#475569',
                padding: 15,
                usePointStyle: true,
                pointStyle: 'circle'
              }
            },
            tooltip: {
              enabled: true,
              backgroundColor: 'rgba(15, 23, 42, 0.95)',
              titleColor: '#fff',
              bodyColor: '#fff',
              titleFont: {
                size: 14,
                weight: '600'
              },
              bodyFont: {
                size: 13
              },
              padding: 12,
              cornerRadius: 8,
              displayColors: false,
              callbacks: {
                label: function(context) {
                  return 'Revenue: ₱' + context.parsed.y.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                font: {
                  size: 12,
                  weight: '500'
                },
                color: '#64748b'
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(148, 163, 184, 0.1)',
                drawBorder: false
              },
              ticks: {
                font: {
                  size: 12,
                  weight: '500'
                },
                color: '#64748b',
                callback: function(value) {
                  return '₱' + value.toLocaleString('en-PH');
                }
              }
            }
          },
          interaction: {
            intersect: false,
            mode: 'index'
          }
        }
      });
    })
    .catch(err => console.error('Error loading chart data:', err));
});
</script>


</body>

</html>