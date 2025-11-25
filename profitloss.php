<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
    
    // Restrict to Super Admin only
    if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
        header('Location: index.php');
        exit;
    }

    // Get selected period (default: current month)
    $period = $_GET['period'] ?? 'month';
    
    // Handle month_year input (from month picker)
    if (isset($_GET['month_year'])) {
        $month_year = explode('-', $_GET['month_year']);
        $year = $month_year[0] ?? date('Y');
        $month = $month_year[1] ?? date('m');
    } else {
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('m');
    }

    // Calculate date range based on period
    if ($period === 'month') {
        $date_from = "$year-$month-01";
        $date_to = date('Y-m-t', strtotime($date_from));
        $period_label = date('F Y', strtotime($date_from));
    } elseif ($period === 'year') {
        $date_from = "$year-01-01";
        $date_to = "$year-12-31";
        $period_label = "Year $year";
    } else { // custom
        $date_from = $_GET['date_from'] ?? date('Y-m-01');
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        $period_label = date('M d, Y', strtotime($date_from)) . ' - ' . date('M d, Y', strtotime($date_to));
    }

    // Get revenue from POS sales
    $revenue_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as revenue
        FROM pos_sales
        WHERE DATE(sale_date) BETWEEN :from AND :to
        AND status = 'completed'
    ");
    $revenue_stmt->execute([':from' => $date_from, ':to' => $date_to]);
    $revenue = $revenue_stmt->fetchColumn();

    // Get revenue from online orders
    $orders_revenue_stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as revenue
        FROM orders
        WHERE DATE(date) BETWEEN :from AND :to
        AND status IN ('Delivered', 'Received')
    ");
    $orders_revenue_stmt->execute([':from' => $date_from, ':to' => $date_to]);
    $orders_revenue = $orders_revenue_stmt->fetchColumn();

    $total_revenue = $revenue + $orders_revenue;

    // Get expenses by category
    $expenses_stmt = $pdo->prepare("
        SELECT 
            expense_category,
            SUM(amount) as total
        FROM expenses
        WHERE expense_date BETWEEN :from AND :to
        GROUP BY expense_category
        ORDER BY total DESC
    ");
    $expenses_stmt->execute([':from' => $date_from, ':to' => $date_to]);
    $expenses_by_category = $expenses_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total expenses
    $total_expenses = array_sum(array_column($expenses_by_category, 'total'));

    // Calculate profit
    $gross_profit = $total_revenue;
    $net_profit = $total_revenue - $total_expenses;
    $profit_margin = $total_revenue > 0 ? ($net_profit / $total_revenue) * 100 : 0;

    // Get capital and equity information
    try {
        // Get total capital injected
        $capital_stmt = $pdo->prepare("
            SELECT SUM(CASE 
                WHEN transaction_type IN ('initial_capital', 'additional_investment') 
                THEN amount 
                ELSE 0 
            END) as total_capital,
            SUM(CASE 
                WHEN transaction_type IN ('withdrawal', 'profit_distribution') 
                THEN amount 
                ELSE 0 
            END) as total_withdrawals,
            SUM(CASE 
                WHEN transaction_type = 'retained_earnings' 
                THEN amount 
                ELSE 0 
            END) as retained_earnings
            FROM capital_equity
        ");
        $capital_stmt->execute();
        $capital_data = $capital_stmt->fetch(PDO::FETCH_ASSOC);
        
        $total_capital = $capital_data['total_capital'] ?? 0;
        $total_withdrawals = $capital_data['total_withdrawals'] ?? 0;
        $retained_earnings = $capital_data['retained_earnings'] ?? 0;
        
        // Calculate current equity
        $current_equity = $total_capital - $total_withdrawals + $net_profit;
        
        // Get beginning retained earnings (cumulative profit before this period)
        $beginning_retained_stmt = $pdo->prepare("
            SELECT SUM(CASE 
                WHEN transaction_type = 'retained_earnings' 
                THEN amount 
                ELSE 0 
            END) as beginning_retained
            FROM capital_equity 
            WHERE transaction_date < :from_date
        ");
        $beginning_retained_stmt->execute([':from_date' => $date_from]);
        $beginning_retained_earnings = $beginning_retained_stmt->fetchColumn() ?? 0;
        
    } catch (Exception $e) {
        // If capital table doesn't exist, set defaults
        $total_capital = 1000000; // Default initial capital
        $total_withdrawals = 0;
        $retained_earnings = 0;
        $current_equity = $total_capital + $net_profit;
        $beginning_retained_earnings = 0;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Profit & Loss - SAPIN</title>

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />
	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<!-- jsPDF Libraries -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
	
	<style>
		body { background-color: #f7f9fc; }
		
		.page-header {
			background: linear-gradient(135deg, #10b981 0%, #059669 100%);
			color: white;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 8px 24px rgba(16, 185, 129, 0.2);
		}
		
		.page-header h1 {
			font-weight: 700;
			margin: 0;
			font-size: 1.75rem;
		}
		
		.stats-card {
			background: white;
			border-radius: 12px;
			padding: 1.5rem;
			box-shadow: 0 2px 8px rgba(0,0,0,0.06);
			margin-bottom: 1.5rem;
			border-left: 4px solid;
		}
		
		.stats-card.revenue { border-left-color: #10b981; }
		.stats-card.expenses { border-left-color: #ef4444; }
		.stats-card.profit { border-left-color: #3b82f6; }
		.stats-card.margin { border-left-color: #f59e0b; }
		
		.stats-number {
			font-size: 2rem;
			font-weight: 700;
			margin: 0;
		}
		
		.stats-label {
			color: #6b7280;
			font-size: 0.875rem;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		
		.card { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(17,24,39,.06); margin-bottom: 20px; }
		.card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 20px; }
		.card-title { font-weight: 600; margin: 0; }
		
		.metric-card { border-left: 4px solid; }
		.metric-card.revenue { border-left-color: #10b981; }
		.metric-card.expenses { border-left-color: #ef4444; }
		.metric-card.profit { border-left-color: #3b82f6; }
		.metric-card.margin { border-left-color: #f59e0b; }
		
		.metric-value { font-size: 2rem; font-weight: 700; margin: 10px 0; }
		.metric-label { color: #6b7280; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.05em; }
		
		.positive { color: #10b981; }
		.negative { color: #ef4444; }
		
		.period-selector { background: white; padding: 20px; border-radius: 14px; margin-bottom: 20px; }
		
		.btn-primary { background: #2563eb; border-color: #2563eb; border-radius: 10px; }
		.btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
		
		.table-financial { background: white; }
		.table-financial thead th { background: #f1f5f9; border-bottom: 2px solid #e5e7eb; }
		.table-financial .category-row { font-weight: 600; }
		.table-financial .total-row { background: #f9fafb; font-weight: 700; border-top: 2px solid #e5e7eb; }
		
		.chart-container { position: relative; height: 300px; }
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'profitloss'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">
					<!-- Page Header -->
					<div class="page-header">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h1>
									<i class="bi bi-bar-chart-line me-2"></i>Profit & Loss Statement
								</h1>
								<p class="mb-0 mt-2" style="opacity: 0.9;">Financial performance for <?= $period_label ?></p>
							</div>
							<button class="btn btn-light" id="printReportBtn">
								<i class="bi bi-printer me-1"></i>Print Report
							</button>
						</div>
					</div>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card revenue h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-arrow-up-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Total Revenue</p>
								</div>
								<h2 class="stats-number text-success">₱<?= number_format($total_revenue, 2) ?></h2>
								<small class="text-muted">Income</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card expenses h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-arrow-down-circle-fill text-danger me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Total Expenses</p>
								</div>
								<h2 class="stats-number text-danger">₱<?= number_format($total_expenses, 2) ?></h2>
								<small class="text-muted">Costs</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card profit h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-cash-coin me-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
									<p class="stats-label mb-0">Net Profit</p>
								</div>
								<h2 class="stats-number <?= $net_profit >= 0 ? 'text-primary' : 'text-danger' ?>">₱<?= number_format($net_profit, 2) ?></h2>
								<small class="text-muted">Bottom line</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card margin h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-percent text-warning me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Profit Margin</p>
								</div>
								<h2 class="stats-number text-warning"><?= number_format($profit_margin, 1) ?>%</h2>
								<small class="text-muted">Efficiency</small>
							</div>
						</div>
					</div>

					<!-- Capital & Equity Section -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #8b5cf6;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-bank me-2" style="font-size: 1.5rem; color: #8b5cf6;"></i>
									<p class="stats-label mb-0">Business Capital</p>
								</div>
								<h2 class="stats-number" style="color: #8b5cf6;">₱<?= number_format($total_capital, 2) ?></h2>
								<small class="text-muted">Total investment</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #06b6d4;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-wallet2 me-2" style="font-size: 1.5rem; color: #06b6d4;"></i>
									<p class="stats-label mb-0">Current Equity</p>
								</div>
								<h2 class="stats-number" style="color: #06b6d4;">₱<?= number_format($current_equity, 2) ?></h2>
								<small class="text-muted">Owner's equity</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #10b981;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-graph-up me-2" style="font-size: 1.5rem; color: #10b981;"></i>
									<p class="stats-label mb-0">Retained Earnings</p>
								</div>
								<h2 class="stats-number text-success">₱<?= number_format($retained_earnings + $net_profit, 2) ?></h2>
								<small class="text-muted">Cumulative profit</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #f59e0b;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-cash-stack me-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
									<p class="stats-label mb-0">Total Withdrawals</p>
								</div>
								<h2 class="stats-number text-warning">₱<?= number_format($total_withdrawals, 2) ?></h2>
								<small class="text-muted">Owner's withdrawals</small>
							</div>
						</div>
					</div>

				<!-- Period Selector -->
				<div class="period-selector">
					<form method="GET" action="profitloss.php" class="row g-3">
						<div class="col-md-3">
							<label class="form-label">Period Type</label>
							<select name="period" id="periodType" class="form-select" onchange="toggleDateInputs()">
								<option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Monthly</option>
								<option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Yearly</option>
								<option value="custom" <?= $period === 'custom' ? 'selected' : '' ?>>Custom Range</option>
							</select>
						</div>
						<div class="col-md-2" id="monthInput" style="display: <?= $period === 'month' ? 'block' : 'none' ?>;">
							<label class="form-label">Month</label>
							<input type="month" name="month_year" class="form-control" value="<?= "$year-$month" ?>">
						</div>
						<div class="col-md-2" id="yearInput" style="display: <?= $period === 'year' ? 'block' : 'none' ?>;">
							<label class="form-label">Year</label>
							<input type="number" name="year" class="form-control" value="<?= $year ?>" min="2020" max="2099">
						</div>
						<div class="col-md-2" id="dateFromInput" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
							<label class="form-label">From</label>
							<input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
						</div>
						<div class="col-md-2" id="dateToInput" style="display: <?= $period === 'custom' ? 'block' : 'none' ?>;">
							<label class="form-label">To</label>
							<input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
						</div>
						<div class="col-md-2 d-flex align-items-end">
							<button type="submit" class="btn btn-primary w-100">
								<i class="bi bi-search me-1"></i>Generate
							</button>
						</div>
					</form>
				</div>


				<!-- Charts Row -->
				<div class="row">
					<div class="col-md-6">
						<div class="card">
							<div class="card-header">
								<h5 class="card-title">Revenue vs Expenses</h5>
							</div>
							<div class="card-body">
								<div class="chart-container">
									<canvas id="revenueExpensesChart"></canvas>
								</div>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="card">
							<div class="card-header">
								<h5 class="card-title">Expenses Breakdown</h5>
							</div>
							<div class="card-body">
								<div class="chart-container">
									<canvas id="expensesBreakdownChart"></canvas>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Detailed Statement -->
				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<h5 class="card-title">Detailed Statement - <?= $period_label ?></h5>
							</div>
							<div class="card-body">
								<table class="table table-financial">
									<thead>
										<tr>
											<th>Category</th>
											<th class="text-end">Amount</th>
										</tr>
									</thead>
									<tbody>
										<tr class="category-row">
											<td colspan="2" class="bg-light"><strong>REVENUE</strong></td>
										</tr>
										<tr>
											<td class="ps-4">POS Sales</td>
											<td class="text-end">₱<?= number_format($revenue, 2) ?></td>
										</tr>
										<tr>
											<td class="ps-4">Online Orders</td>
											<td class="text-end">₱<?= number_format($orders_revenue, 2) ?></td>
										</tr>
										<tr class="total-row">
											<td><strong>Total Revenue</strong></td>
											<td class="text-end"><strong>₱<?= number_format($total_revenue, 2) ?></strong></td>
										</tr>
										<tr><td colspan="2">&nbsp;</td></tr>
										<tr class="category-row">
											<td colspan="2" class="bg-light"><strong>EXPENSES</strong></td>
										</tr>
										<?php foreach ($expenses_by_category as $expense): ?>
										<tr>
											<td class="ps-4"><?= htmlspecialchars($expense['expense_category']) ?></td>
											<td class="text-end">₱<?= number_format($expense['total'], 2) ?></td>
										</tr>
										<?php endforeach; ?>
										<?php if (empty($expenses_by_category)): ?>
										<tr>
											<td class="ps-4 text-muted">No expenses recorded</td>
											<td class="text-end">₱0.00</td>
										</tr>
										<?php endif; ?>
										<tr class="total-row">
											<td><strong>Total Expenses</strong></td>
											<td class="text-end"><strong>₱<?= number_format($total_expenses, 2) ?></strong></td>
										</tr>
										<tr><td colspan="2">&nbsp;</td></tr>
										<tr class="total-row bg-primary text-white">
											<td><strong>NET PROFIT</strong></td>
											<td class="text-end"><strong>₱<?= number_format($net_profit, 2) ?></strong></td>
										</tr>
										<tr><td colspan="2">&nbsp;</td></tr>
										<tr class="category-row">
											<td colspan="2" class="bg-light"><strong>BUSINESS CAPITAL</strong></td>
										</tr>
										<tr>
											<td class="ps-4">Initial Business Capital</td>
											<td class="text-end">₱<?= number_format($total_capital, 2) ?></td>
										</tr>
										<tr>
											<td class="ps-4">Add: Current Period Profit</td>
											<td class="text-end">₱<?= number_format($net_profit, 2) ?></td>
										</tr>
										<tr class="total-row bg-success text-white">
											<td><strong>TOTAL BUSINESS VALUE</strong></td>
											<td class="text-end"><strong>₱<?= number_format($total_capital + $net_profit, 2) ?></strong></td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>

				</div>
			</main>

			<footer class="footer">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">&copy; 2025 <strong>SAPIN</strong></p>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>
	
	<script src="js/app.js"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
	<script>
	function toggleDateInputs() {
		const period = document.getElementById('periodType').value;
		document.getElementById('monthInput').style.display = period === 'month' ? 'block' : 'none';
		document.getElementById('yearInput').style.display = period === 'year' ? 'block' : 'none';
		document.getElementById('dateFromInput').style.display = period === 'custom' ? 'block' : 'none';
		document.getElementById('dateToInput').style.display = period === 'custom' ? 'block' : 'none';
	}

	// Revenue vs Expenses Chart
	const ctx1 = document.getElementById('revenueExpensesChart').getContext('2d');
	new Chart(ctx1, {
		type: 'bar',
		data: {
			labels: ['Revenue', 'Expenses', 'Net Profit'],
			datasets: [{
				label: 'Amount (₱)',
				data: [<?= $total_revenue ?>, <?= $total_expenses ?>, <?= $net_profit ?>],
				backgroundColor: ['#10b981', '#ef4444', '#3b82f6'],
				borderRadius: 8
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { display: false }
			},
			scales: {
				y: { beginAtZero: true }
			}
		}
	});

	// Expenses Breakdown Chart
	const ctx2 = document.getElementById('expensesBreakdownChart').getContext('2d');
	new Chart(ctx2, {
		type: 'doughnut',
		data: {
			labels: [<?php echo implode(',', array_map(function($e) { return "'" . $e['expense_category'] . "'"; }, $expenses_by_category)); ?>],
			datasets: [{
				data: [<?php echo implode(',', array_column($expenses_by_category, 'total')); ?>],
				backgroundColor: ['#8b5cf6', '#f59e0b', '#3b82f6', '#ef4444', '#10b981', '#ec4899', '#6b7280']
			}]
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
			plugins: {
				legend: { position: 'right' }
			}
		}
	});

	// PDF Generation
	document.getElementById('printReportBtn').addEventListener('click', async function() {
		const { jsPDF } = window.jspdf;
		const pdf = new jsPDF({
			orientation: "p",
			unit: "mm",
			format: "a4"
		});

		// Helper function to format numbers
		function formatNumber(num) {
			const formatted = Number(num).toFixed(2);
			const parts = formatted.split('.');
			parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
			return parts.join('.');
		}

		const pageWidth = pdf.internal.pageSize.getWidth();
		const pageHeight = pdf.internal.pageSize.getHeight();
		const margin = 15;
		let yPos = margin;

		// Load SAPIN logo
		async function loadImageAsDataURL(url) {
			const res = await fetch(url);
			const blob = await res.blob();
			return await new Promise((resolve) => {
				const reader = new FileReader();
				reader.onload = () => resolve(reader.result);
				reader.readAsDataURL(blob);
			});
		}
		const logoDataUrl = await loadImageAsDataURL('../assets/img/logo_forsapin.jpg');

		// Header
		pdf.setFillColor(30, 58, 138);
		pdf.rect(0, 0, pageWidth, 45, 'F');
		if (logoDataUrl) {
			pdf.addImage(logoDataUrl, 'JPEG', margin, 12, 18, 18);
		}
		pdf.setTextColor(255, 255, 255);
		pdf.setFontSize(24);
		pdf.setFont(undefined, 'bold');
		pdf.text("PROFIT & LOSS STATEMENT", pageWidth / 2, 20, { align: 'center' });
		pdf.setFontSize(14);
		pdf.setFont(undefined, 'normal');
		pdf.text("SAPIN", pageWidth / 2, 28, { align: 'center' });
		pdf.setFontSize(11);
		pdf.text("Period: <?= $period_label ?>", pageWidth / 2, 36, { align: 'center' });

		yPos = 55;

		// Key Metrics Section
		pdf.setFontSize(14);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(30, 58, 138);
		pdf.text("Financial Summary", margin, yPos);
		yPos += 10;

		// Metrics boxes
		const boxWidth = (pageWidth - 2 * margin - 15) / 4;
		const boxHeight = 25;
		let xPos = margin;

		// Revenue Box
		pdf.setFillColor(16, 185, 129);
		pdf.roundedRect(xPos, yPos, boxWidth, boxHeight, 3, 3, 'F');
		pdf.setTextColor(255, 255, 255);
		pdf.setFontSize(8);
		pdf.text("TOTAL REVENUE", xPos + boxWidth/2, yPos + 8, { align: 'center' });
		pdf.setFontSize(12);
		pdf.setFont(undefined, 'bold');
		pdf.text("P" + formatNumber(<?= $total_revenue ?>), xPos + boxWidth/2, yPos + 18, { align: 'center' });

		xPos += boxWidth + 5;

		// Expenses Box
		pdf.setFillColor(239, 68, 68);
		pdf.roundedRect(xPos, yPos, boxWidth, boxHeight, 3, 3, 'F');
		pdf.setTextColor(255, 255, 255);
		pdf.setFontSize(8);
		pdf.text("TOTAL EXPENSES", xPos + boxWidth/2, yPos + 8, { align: 'center' });
		pdf.setFontSize(12);
		pdf.setFont(undefined, 'bold');
		pdf.text("P" + formatNumber(<?= $total_expenses ?>), xPos + boxWidth/2, yPos + 18, { align: 'center' });

		xPos += boxWidth + 5;

		// Net Profit Box
		pdf.setFillColor(59, 130, 246);
		pdf.roundedRect(xPos, yPos, boxWidth, boxHeight, 3, 3, 'F');
		pdf.setTextColor(255, 255, 255);
		pdf.setFontSize(8);
		pdf.text("NET PROFIT", xPos + boxWidth/2, yPos + 8, { align: 'center' });
		pdf.setFontSize(12);
		pdf.setFont(undefined, 'bold');
		pdf.text("P" + formatNumber(<?= $net_profit ?>), xPos + boxWidth/2, yPos + 18, { align: 'center' });

		xPos += boxWidth + 5;

		// Profit Margin Box
		pdf.setFillColor(245, 158, 11);
		pdf.roundedRect(xPos, yPos, boxWidth, boxHeight, 3, 3, 'F');
		pdf.setTextColor(255, 255, 255);
		pdf.setFontSize(8);
		pdf.text("PROFIT MARGIN", xPos + boxWidth/2, yPos + 8, { align: 'center' });
		pdf.setFontSize(12);
		pdf.setFont(undefined, 'bold');
		pdf.text("<?= number_format($profit_margin, 1) ?>%", xPos + boxWidth/2, yPos + 18, { align: 'center' });

		yPos += boxHeight + 15;

		// P&L Summary Table (compact)
		pdf.setFontSize(14);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(30, 58, 138);
		pdf.text("P&L Summary", margin, yPos);
		yPos += 6;
		const plSummary = [
			['Total Revenue', 'P' + formatNumber(<?= $total_revenue ?>)],
			['Total Expenses', 'P' + formatNumber(<?= $total_expenses ?>)],
			['Net Profit', 'P' + formatNumber(<?= $net_profit ?>)],
			['Profit Margin', '<?= number_format($profit_margin, 1) ?>%']
		];
		pdf.autoTable({
			startY: yPos,
			head: [['Metric', 'Amount']],
			body: plSummary,
			theme: 'grid',
			styles: { font: 'helvetica', fontSize: 10 },
			headStyles: { fillColor: [30,58,138], fontStyle: 'bold' },
			bodyStyles: { },
			columnStyles: { 0: { cellWidth: 100 }, 1: { cellWidth: 80, halign: 'right' } },
			margin: { left: margin, right: margin }
		});
		yPos = pdf.lastAutoTable.finalY + 10;

		// Expenses Breakdown (Top 5 + Others)
		pdf.setFontSize(12);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(30, 58, 138);
		pdf.text("Expenses Breakdown (Top 5)", margin, yPos);
		yPos += 6;
		const expData = [
			<?php 
			$sorted = $expenses_by_category; 
			usort($sorted, function($a,$b){ return ($b['total'] <=> $a['total']); });
			$top = array_slice($sorted, 0, 5);
			$others = max(0, $total_expenses - array_sum(array_column($top, 'total')));
			foreach ($top as $e): ?>
			['<?= addslashes($e['expense_category']) ?>','P' + formatNumber(<?= $e['total'] ?>)],
			<?php endforeach; ?>
			<?php if ($others > 0): ?>
			['Others','P' + formatNumber(<?= $others ?>)],
			<?php endif; ?>
			['Total','P' + formatNumber(<?= $total_expenses ?>)]
		];
		pdf.autoTable({
			startY: yPos,
			head: [['Category','Amount']],
			body: expData,
			theme: 'striped',
			styles: { font: 'helvetica', fontSize: 9 },
			headStyles: { fillColor: [99,102,241], fontStyle: 'bold' },
			columnStyles: { 0: { cellWidth: 110 }, 1: { cellWidth: 70, halign: 'right' } },
			margin: { left: margin, right: margin }
		});

		// Appendix – Detailed Statement (Page 2)
		pdf.addPage();
		yPos = margin;
		pdf.setFontSize(13);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(30,58,138);
		pdf.text('Appendix – Detailed Statement (<?= $period_label ?>)', margin, yPos);
		yPos += 8;

		// Revenue Details
		pdf.setFontSize(11);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(31,41,55);
		pdf.text('Revenue', margin, yPos);
		yPos += 4;
		const revDetails = [
			['POS Sales', 'P' + formatNumber(<?= $revenue ?>)],
			['Online Orders', 'P' + formatNumber(<?= $orders_revenue ?>)],
			['Total Revenue', 'P' + formatNumber(<?= $total_revenue ?>)]
		];
		pdf.autoTable({
			startY: yPos,
			head: [['Description','Amount']],
			body: revDetails,
			theme: 'grid',
			styles: { font: 'helvetica', fontSize: 9 },
			headStyles: { fillColor: [241,245,249], textColor: [31,41,55], fontStyle: 'bold' },
			columnStyles: { 0:{cellWidth: 110}, 1:{cellWidth: 70, halign: 'right'} },
			margin: { left: margin, right: margin }
		});
		yPos = pdf.lastAutoTable.finalY + 8;

		// Expenses Details
		pdf.setFontSize(11);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(31,41,55);
		pdf.text('Expenses', margin, yPos);
		yPos += 4;
		const expDetails = [
			<?php foreach ($expenses_by_category as $expense): ?>
			['<?= addslashes($expense['expense_category']) ?>','P' + formatNumber(<?= $expense['total'] ?>)],
			<?php endforeach; ?>
			<?php if (empty($expenses_by_category)): ?>
			['No expenses recorded','P0.00'],
			<?php endif; ?>
			['Total Expenses','P' + formatNumber(<?= $total_expenses ?>)]
		];
		pdf.autoTable({
			startY: yPos,
			head: [['Category','Amount']],
			body: expDetails,
			theme: 'striped',
			styles: { font: 'helvetica', fontSize: 9 },
			headStyles: { fillColor: [241,245,249], textColor: [31,41,55], fontStyle: 'bold' },
			columnStyles: { 0:{cellWidth: 110}, 1:{cellWidth: 70, halign: 'right'} },
			margin: { left: margin, right: margin }
		});
		yPos = pdf.lastAutoTable.finalY + 8;

		// Net Profit (highlight)
		pdf.autoTable({
			startY: yPos,
			head: [],
			body: [ ['','Net Profit','P' + formatNumber(<?= $net_profit ?>)] ],
			theme: 'plain',
			styles: { font: 'helvetica', fontSize: 10, cellPadding: 4, fillColor: [30,58,138], textColor: [255,255,255], fontStyle:'bold' },
			columnStyles: { 0:{cellWidth:10}, 1:{cellWidth: 110}, 2:{cellWidth: 70, halign:'right'} },
			margin: { left: margin, right: margin }
		});
		yPos = pdf.lastAutoTable.finalY + 8;

		// Capital & Equity Section
		pdf.setFontSize(11);
		pdf.setFont(undefined, 'bold');
		pdf.setTextColor(31,41,55);
		pdf.text('Capital & Equity', margin, yPos);
		yPos += 4;
		const capitalEquity = [
			['Business Capital', 'P' + formatNumber(<?= $total_capital ?>)],
			['Less: Owner\'s Withdrawals', '(' + formatNumber(<?= $total_withdrawals ?>) + ')'],
			['Add: Current Period Profit', 'P' + formatNumber(<?= $net_profit ?>)],
			['Add: Beginning Retained Earnings', 'P' + formatNumber(<?= $beginning_retained_earnings ?>)],
			['CURRENT OWNER\'S EQUITY', 'P' + formatNumber(<?= $current_equity ?>)],
			['TOTAL BUSINESS VALUE', 'P' + formatNumber(<?= $current_equity ?>)]
		];
		pdf.autoTable({
			startY: yPos,
			head: [['Description','Amount']],
			body: capitalEquity,
			theme: 'grid',
			styles: { font: 'helvetica', fontSize: 9 },
			headStyles: { fillColor: [16,185,129], textColor: [255,255,255], fontStyle: 'bold' },
			bodyStyles: { },
			columnStyles: { 0:{cellWidth: 110}, 1:{cellWidth: 70, halign:'right'} },
			margin: { left: margin, right: margin },
			didDrawRow: function(data) {
				// Highlight the equity rows
				if (data.row.index === 4 || data.row.index === 5) {
					pdf.setFillColor(16,185,129);
					pdf.setTextColor(255,255,255);
					pdf.setFont(undefined, 'bold');
					data.table.body.forEach((cell, colIndex) => {
						if (colIndex === 0 || colIndex === 1) {
							const cellPos = data.table.cells[data.row.index][colIndex];
							pdf.rect(cellPos.x, cellPos.y, cellPos.width, cellPos.height, 'F');
							pdf.text(cellPos.x + 2, cellPos.y + cellPos.height/2 + 2, cellPos.text || '', {align: 'left'});
						}
					});
				}
			}
		});
		// Footer on all pages with correct page numbering
		const totalPages = pdf.internal.getNumberOfPages();
		for (let i = 1; i <= totalPages; i++) {
			pdf.setPage(i);
			pdf.setFontSize(8);
			pdf.setTextColor(128, 128, 128);
			pdf.text("Generated: <?= date('F d, Y h:i A') ?>", margin, pageHeight - 10);
			pdf.text("SAPIN - Confidential", pageWidth / 2, pageHeight - 10, { align: 'center' });
			pdf.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
		}

		// Open print dialog
		pdf.autoPrint();
		window.open(pdf.output('bloburl'), '_blank');
	});
	</script>

</body>
</html>
