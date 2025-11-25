<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');

    // Get filter parameters
    $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $_GET['date_to'] ?? date('Y-m-d');
    $payment_method = $_GET['payment_method'] ?? '';
    $cashier_id = $_GET['cashier_id'] ?? '';
    $search = $_GET['search'] ?? '';

    // Pagination
    $page = (int)($_GET['page'] ?? 1);
    $records_per_page = 20;
    $offset = ($page - 1) * $records_per_page;

    // Build query
    $where_conditions = ["s.status != 'voided'"];
    $params = [];

    if ($date_from) {
        $where_conditions[] = "DATE(s.sale_date) >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where_conditions[] = "DATE(s.sale_date) <= ?";
        $params[] = $date_to;
    }

    if ($payment_method) {
        $where_conditions[] = "s.payment_method = ?";
        $params[] = $payment_method;
    }

    if ($cashier_id) {
        $where_conditions[] = "s.cashier_id = ?";
        $params[] = $cashier_id;
    }

    if ($search) {
        $where_conditions[] = "s.sale_number LIKE ?";
        $params[] = "%$search%";
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total
        FROM pos_sales s
        LEFT JOIN users u ON s.cashier_id = u.user_id
        WHERE $where_clause
    ";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);

    // Get sales data
    $sql = "
        SELECT 
            s.sale_id,
            s.sale_number,
            s.sale_date,
            s.subtotal,
            s.tax_amount,
            s.total_amount,
            s.payment_method,
            s.amount_payment,
            s.change_amount,
            s.status,
            u.username as cashier_name
        FROM pos_sales s
        LEFT JOIN users u ON s.cashier_id = u.user_id
        WHERE $where_clause
        ORDER BY s.sale_date DESC
        LIMIT $records_per_page OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all cashiers for filter
    $cashiers_stmt = $pdo->query("SELECT user_id, username FROM users WHERE usertype_id IN (1, 5) ORDER BY username");
    $cashiers = $cashiers_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Sales History">
	<meta name="author" content="Sapin Bedsheets">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<title>Sales History - Sapin Bedsheets</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
	
	<style>
		.filter-card {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			border-radius: 10px;
			padding: 20px;
			margin-bottom: 20px;
			color: white;
		}

		.sales-table {
			background: white;
			border-radius: 10px;
			box-shadow: 0 10px 30px rgba(0,0,0,0.1);
			overflow: hidden;
		}

		.table th {
			background-color: #f8f9fa;
			border-top: none;
			font-weight: 600;
		}

		.receipt-btn {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			border: none;
			color: white;
			padding: 5px 10px;
			border-radius: 5px;
			font-size: 12px;
			transition: all 0.3s ease;
		}

		.receipt-btn:hover {
			transform: translateY(-1px);
			box-shadow: 0 5px 15px rgba(0,0,0,0.2);
			color: white;
		}

		.pagination {
			justify-content: center;
		}

		.page-link {
			color: #667eea;
		}

		.page-item.active .page-link {
			background-color: #667eea;
			border-color: #667eea;
		}

		.filter-input {
			background: rgba(255,255,255,0.1);
			border: 1px solid rgba(255,255,255,0.3);
			color: white;
		}

		.filter-input:focus {
			background: rgba(255,255,255,0.15);
			border-color: rgba(255,255,255,0.5);
			color: white;
			box-shadow: none;
		}

		.filter-input::placeholder {
			color: rgba(255,255,255,0.7);
		}

		/* Ensure black text for specific dropdowns (Payment Method and Cashier) */
		select.filter-input[name="payment_method"],
		select.filter-input[name="cashier_id"] {
			color: #000000;
			background: #ffffff;
		}

		select.filter-input[name="payment_method"] option,
		select.filter-input[name="cashier_id"] option {
			color: #000000;
		}
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'sales_history'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">
					<!-- Header -->
					<div class="d-flex justify-content-between align-items-center mb-3">
						<h1 class="h3 mb-0"><i class="bi bi-clock-history me-2"></i>Sales History</h1>
						<div class="d-flex gap-2">
							<a href="pos.php" class="btn btn-success">
								<i class="bi bi-credit-card me-1"></i>Back to POS
							</a>
							<button class="btn btn-primary" onclick="printReport()">
								<i class="bi bi-printer me-1"></i>Print Report
							</button>
						</div>
					</div>

					<!-- Filters Card -->
					<div class="filter-card">
						<h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
						<form method="GET" action="sales_history.php">
							<div class="row">
								<div class="col-md-3 mb-3">
									<label class="form-label">Date From</label>
									<input type="date" class="form-control filter-input" name="date_from" value="<?= $date_from ?>">
								</div>
								<div class="col-md-3 mb-3">
									<label class="form-label">Date To</label>
									<input type="date" class="form-control filter-input" name="date_to" value="<?= $date_to ?>">
								</div>
								<div class="col-md-2 mb-3">
									<label class="form-label">Payment Method</label>
									<select class="form-control filter-input" name="payment_method">
										<option value="">All Methods</option>
										<option value="cash" <?= $payment_method === 'cash' ? 'selected' : '' ?>>Cash</option>
										<option value="card" <?= $payment_method === 'card' ? 'selected' : '' ?>>Card</option>
										<option value="gcash" <?= $payment_method === 'gcash' ? 'selected' : '' ?>>GCash</option>
										<option value="bank_transfer" <?= $payment_method === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
									</select>
								</div>
								<div class="col-md-2 mb-3">
									<label class="form-label">Cashier</label>
									<select class="form-control filter-input" name="cashier_id">
										<option value="">All Cashiers</option>
										<?php foreach ($cashiers as $cashier): ?>
											<option value="<?= $cashier['user_id'] ?>" <?= $cashier_id == $cashier['user_id'] ? 'selected' : '' ?>>
												<?= htmlspecialchars($cashier['username']) ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
								<div class="col-md-2 mb-3">
									<label class="form-label">Search Sale #</label>
									<input type="text" class="form-control filter-input" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Sale number...">
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<button type="submit" class="btn btn-light me-2">
										<i class="bi bi-search me-1"></i>Filter
									</button>
									<a href="sales_history.php" class="btn btn-outline-light">
										<i class="bi bi-arrow-clockwise me-1"></i>Reset
									</a>
								</div>
							</div>
						</form>
					</div>

					<!-- Summary Cards -->
					<?php
					// Calculate summary statistics
					$summary_sql = "
						SELECT 
							COUNT(*) as total_transactions,
							SUM(total_amount) as total_sales,
							AVG(total_amount) as avg_transaction
						FROM pos_sales s
						WHERE $where_clause
					";
					$summary_stmt = $pdo->prepare($summary_sql);
					$summary_stmt->execute($params);
					$summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
					?>
					<div class="row mb-4">
						<div class="col-md-4">
							<div class="card bg-primary text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="card-title mb-0">Total Transactions</h6>
											<h3 class="mb-0"><?= number_format($summary['total_transactions']) ?></h3>
										</div>
										<i class="bi bi-receipt-cutoff fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="card bg-success text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="card-title mb-0">Total Sales</h6>
											<h3 class="mb-0">₱<?= number_format($summary['total_sales'] ?? 0, 2) ?></h3>
										</div>
										<i class="bi bi-currency-dollar fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="card bg-info text-white">
								<div class="card-body">
									<div class="d-flex justify-content-between align-items-center">
										<div>
											<h6 class="card-title mb-0">Average Transaction</h6>
											<h3 class="mb-0">₱<?= number_format($summary['avg_transaction'] ?? 0, 2) ?></h3>
										</div>
										<i class="bi bi-graph-up fa-2x"></i>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Sales Table -->
					<div class="sales-table">
						<div class="table-responsive">
							<table class="table table-striped table-hover mb-0">
								<thead>
									<tr>
										<th>Sale #</th>
										<th>Date & Time</th>
										<th>Cashier</th>
										<th>Subtotal</th>
										<th>Tax</th>
										<th>Total</th>
										<th>Payment</th>
										<th>Method</th>
										<th>Change</th>
										<th>Status</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($sales)): ?>
										<tr>
											<td colspan="11" class="text-center py-4">
												<i class="bi bi-inbox display-4 text-muted"></i>
												<h5 class="text-muted mt-2">No sales found</h5>
												<p class="text-muted">Try adjusting your filter criteria</p>
											</td>
										</tr>
									<?php else: ?>
										<?php foreach ($sales as $sale): ?>
											<tr>
												<td><strong><?= htmlspecialchars($sale['sale_number']) ?></strong></td>
												<td><?= date('M d, Y h:i A', strtotime($sale['sale_date'])) ?></td>
												<td><?= htmlspecialchars($sale['cashier_name'] ?? 'Unknown') ?></td>
												<td>₱<?= number_format($sale['subtotal'], 2) ?></td>
												<td>₱<?= number_format($sale['tax_amount'], 2) ?></td>
												<td><strong>₱<?= number_format($sale['total_amount'], 2) ?></strong></td>
												<td>₱<?= number_format($sale['amount_payment'] ?? 0, 2) ?></td>
												<td><span class="badge bg-<?= $sale['payment_method'] === 'cash' ? 'success' : 'primary' ?>"><?= ucfirst($sale['payment_method']) ?></span></td>
												<td>₱<?= number_format($sale['change_amount'], 2) ?></td>
												<td><span class="badge bg-<?= $sale['status'] === 'completed' ? 'success' : 'warning' ?>"><?= ucfirst($sale['status']) ?></span></td>
												<td>
													<button class="btn receipt-btn" onclick="showReceipt(<?= $sale['sale_id'] ?>)">
														<i class="bi bi-receipt me-1"></i>Receipt
													</button>
												</td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>

						<!-- Pagination -->
						<?php if ($total_pages > 1): ?>
							<div class="p-3">
								<nav>
									<ul class="pagination">
										<!-- Previous Button -->
										<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
											<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
												<i class="bi bi-chevron-left"></i> Previous
											</a>
										</li>

										<!-- Page Numbers -->
										<?php
										$start_page = max(1, $page - 2);
										$end_page = min($total_pages, $page + 2);
										
										if ($start_page > 1): ?>
											<li class="page-item">
												<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
											</li>
											<?php if ($start_page > 2): ?>
												<li class="page-item disabled">
													<span class="page-link">...</span>
												</li>
											<?php endif; ?>
										<?php endif; ?>

										<?php for ($i = $start_page; $i <= $end_page; $i++): ?>
											<li class="page-item <?= $i === $page ? 'active' : '' ?>">
												<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
											</li>
										<?php endfor; ?>

										<?php if ($end_page < $total_pages): ?>
											<?php if ($end_page < $total_pages - 1): ?>
												<li class="page-item disabled">
													<span class="page-link">...</span>
												</li>
											<?php endif; ?>
											<li class="page-item">
												<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
											</li>
										<?php endif; ?>

										<!-- Next Button -->
										<li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
											<a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
												Next <i class="bi bi-chevron-right"></i>
											</a>
										</li>
									</ul>
								</nav>
								
								<div class="text-center mt-2">
									<small class="text-muted">
										Showing <?= min(($page - 1) * $records_per_page + 1, $total_records) ?> to 
										<?= min($page * $records_per_page, $total_records) ?> of 
										<?= number_format($total_records) ?> results
									</small>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</main>

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
									<span class="text-muted">Sales History v1.0</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>

	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<script>
		// Show receipt function
		function showReceipt(saleId) {
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
					showEReceipt(data.receipt_data);
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

		// Show E-Receipt (same format as POS)
		function showEReceipt(receiptData) {
			// Handle different data structures between direct checkout and sales history
			const sale = receiptData.sale || receiptData;
			const items = receiptData.items || [];

			const receiptHTML = `
				<div style="font-family: 'Courier New', monospace; max-width: 400px; margin: 0 auto; padding: 20px; background: white; color: black;">
					<div style="text-align: center; margin-bottom: 20px;">
						<h3 style="margin: 0; font-size: 16px; font-weight: bold;">SAPIN</h3>
						<div style="font-size: 12px; margin: 5px 0;">140 Rose Street</div>
						<div style="font-size: 12px; margin: 5px 0;">Brgy. Paciano Rizal, Bay, Laguna</div>
					
					</div>
					
					<div style="border-bottom: 1px dashed #000; margin-bottom: 10px; padding-bottom: 10px;">
    <div style="display: flex; justify-content: space-between; font-size: 12px;">
        <span>Check: ${sale.sale_number}</span>
    </div>
    <div style="font-size: 12px;">Date: ${new Date(sale.sale_date).toLocaleDateString()}</div>
    <div style="font-size: 12px;">Cashier: ${sale.cashier_name}</div>
</div>

					
					<div style="margin-bottom: 15px;">
						${items.map(item => `
							<div style="font-size: 12px; margin: 3px 0;">
								<div style="display: flex; justify-content: space-between;">
									<span>${item.quantity} ${item.product_name}</span>
									<span>${parseFloat(item.total_price).toFixed(2)}</span>
								</div>
							</div>
						`).join('')}
					</div>
					
					<div style="border-top: 1px dashed #000; padding-top: 10px;">
						<div style="display: flex; justify-content: space-between; font-size: 12px; margin: 2px 0;"><span>Sub Total:</span><span>${parseFloat(sale.subtotal).toFixed(2)}</span></div>
						<div style="display: flex; justify-content: space-between; font-size: 12px; margin: 2px 0;"><span>Sales Tax:</span><span>${parseFloat(sale.tax_amount).toFixed(2)}</span></div>
						<div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin: 5px 0;"><span>Check Total:</span><span>${parseFloat(sale.total_amount).toFixed(2)}</span></div>
					</div>
					
					<div style="text-align: center; margin-top: 20px; font-size: 11px;">
						<div>Thank you for your business!</div>
						<div>Please come again</div>
					</div>
				</div>
			`;
			
			Swal.fire({
				title: '<i class="bi bi-receipt"></i> Transaction Receipt',
				html: receiptHTML,
				width: '500px',
				showCancelButton: true,
				confirmButtonText: '<i class="bi bi-printer"></i> Print',
				cancelButtonText: '<i class="bi bi-x-circle"></i> Close',
				customClass: {
					confirmButton: 'btn btn-primary',
					cancelButton: 'btn btn-secondary'
				}
			}).then((result) => {
				if (result.isConfirmed) {
					window.print();
				}
			});
		}

		// Print report function - Generate Document-Style PDF
		function printReport() {
			const { jsPDF } = window.jspdf;
			const pdf = new jsPDF({
				orientation: "p",
				unit: "mm",
				format: "a4",
				putOnlyUsedFonts: true,
				floatPrecision: 16
			});
			
			// Page settings
			const pageWidth = pdf.internal.pageSize.getWidth();
			const pageHeight = pdf.internal.pageSize.getHeight();
			const margin = 15;
			let yPos = margin;
			
			// Helper function to format numbers for PDF (avoid Unicode issues)
			function formatNumber(num) {
				const formatted = Number(num).toFixed(2);
				const parts = formatted.split('.');
				parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
				return parts.join('.');
			}
			
			// Helper function to add new page if needed
			function checkPageBreak(requiredSpace) {
				if (yPos + requiredSpace > pageHeight - margin) {
					pdf.addPage();
					yPos = margin;
					return true;
				}
				return false;
			}
			
			// Header
			pdf.setFillColor(102, 126, 234); // Purple gradient color
			pdf.rect(0, 0, pageWidth, 45, 'F');
			pdf.setTextColor(255, 255, 255);
			pdf.setFontSize(24);
			pdf.setFont(undefined, 'bold');
			pdf.text("SALES HISTORY REPORT", pageWidth / 2, 18, { align: 'center' });
			pdf.setFontSize(12);
			pdf.setFont(undefined, 'normal');
			pdf.text("Sapin Bedsheets", pageWidth / 2, 28, { align: 'center' });
			pdf.setFontSize(10);
			pdf.text("Generated: <?= date('F d, Y h:i A') ?>", pageWidth / 2, 36, { align: 'center' });
			
			yPos = 55;
			
			// Filter Information Section
			pdf.setTextColor(0, 0, 0);
			pdf.setFontSize(14);
			pdf.setFont(undefined, 'bold');
			pdf.text("Report Filters", margin, yPos);
			yPos += 8;
			
			const filterData = [
				['Date Range', '<?= date('M d, Y', strtotime($date_from)) ?> to <?= date('M d, Y', strtotime($date_to)) ?>'],
				['Payment Method', '<?= $payment_method ? ucfirst($payment_method) : 'All Methods' ?>'],
				['Cashier', '<?= $cashier_id ? htmlspecialchars($cashiers[array_search($cashier_id, array_column($cashiers, 'user_id'))]['username'] ?? 'Unknown') : 'All Cashiers' ?>'],
				['Search Term', '<?= $search ? htmlspecialchars($search) : 'None' ?>']
			];
			
			pdf.autoTable({
				startY: yPos,
				body: filterData,
				theme: 'plain',
				styles: { 
					font: 'helvetica',
					fontSize: 9
				},
				columnStyles: {
					0: { cellWidth: 40, fontStyle: 'bold' },
					1: { cellWidth: 140 }
				},
				margin: { left: margin, right: margin }
			});
			
			yPos = pdf.lastAutoTable.finalY + 12;
			
			// Summary Statistics Section
			pdf.setFontSize(14);
			pdf.setFont(undefined, 'bold');
			pdf.text("Summary Statistics", margin, yPos);
			yPos += 8;
			
			const summaryData = [
				['Total Transactions', '<?= number_format($summary['total_transactions']) ?>'],
				['Total Sales', 'P<?= number_format($summary['total_sales'] ?? 0, 2, '.', ',') ?>'],
				['Average Transaction', 'P<?= number_format($summary['avg_transaction'] ?? 0, 2, '.', ',') ?>']
			];
			
			pdf.autoTable({
				startY: yPos,
				body: summaryData,
				theme: 'grid',
				styles: { 
					font: 'helvetica',
					fontSize: 10
				},
				headStyles: {
					fillColor: [102, 126, 234],
					font: 'helvetica'
				},
				columnStyles: {
					0: { cellWidth: 80, fontStyle: 'bold', fillColor: [248, 250, 252] },
					1: { cellWidth: 100, halign: 'right' }
				},
				margin: { left: margin, right: margin }
			});
			
			yPos = pdf.lastAutoTable.finalY + 15;
			
			// Sales Transactions Section
			checkPageBreak(60);
			
			pdf.setFontSize(14);
			pdf.setFont(undefined, 'bold');
			pdf.text("Sales Transactions", margin, yPos);
			yPos += 10;
			
			const salesData = [
				<?php foreach ($sales as $sale): ?>
				[
					'<?= htmlspecialchars($sale['sale_number']) ?>',
					'<?= date('M d, Y h:i A', strtotime($sale['sale_date'])) ?>',
					'<?= htmlspecialchars($sale['cashier_name'] ?? 'Unknown') ?>',
					'P<?= number_format($sale['subtotal'], 2, '.', ',') ?>',
					'P<?= number_format($sale['tax_amount'], 2, '.', ',') ?>',
					'P<?= number_format($sale['total_amount'], 2, '.', ',') ?>',
					'<?= ucfirst($sale['payment_method']) ?>',
					'<?= ucfirst($sale['status']) ?>'
				],
				<?php endforeach; ?>
			];
			
			if (salesData.length === 0) {
				pdf.setFontSize(10);
				pdf.setFont(undefined, 'italic');
				pdf.setTextColor(128, 128, 128);
				pdf.text("No sales transactions found for the selected filters.", margin, yPos);
			} else {
				pdf.autoTable({
					startY: yPos,
					head: [['Sale #', 'Date & Time', 'Cashier', 'Subtotal', 'Tax', 'Total', 'Method', 'Status']],
					body: salesData,
					theme: 'striped',
					styles: { 
						font: 'helvetica',
						fontSize: 8
					},
					headStyles: { 
						fillColor: [102, 126, 234],
						fontSize: 9,
						fontStyle: 'bold',
						font: 'helvetica'
					},
					bodyStyles: { 
						font: 'helvetica'
					},
					columnStyles: {
						0: { cellWidth: 25 },
						1: { cellWidth: 35 },
						2: { cellWidth: 25 },
						3: { cellWidth: 22, halign: 'right' },
						4: { cellWidth: 18, halign: 'right' },
						5: { cellWidth: 22, halign: 'right', fontStyle: 'bold' },
						6: { cellWidth: 20 },
						7: { cellWidth: 18 }
					},
					margin: { left: margin, right: margin }
				});
			}
			
			// Footer on all pages
			const totalPages = pdf.internal.getNumberOfPages();
			for (let i = 1; i <= totalPages; i++) {
				pdf.setPage(i);
				pdf.setFontSize(8);
				pdf.setTextColor(128, 128, 128);
				pdf.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
				pdf.text("Sapin Bedsheets - Confidential", margin, pageHeight - 10);
			}
			
			// Open print dialog instead of downloading
			pdf.autoPrint();
			window.open(pdf.output('bloburl'), '_blank');
		}
	</script>
</body>
</html>
