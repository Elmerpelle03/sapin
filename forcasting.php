<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');

    // Get selected year (default to current year)
    $selected_year = $_GET['year'] ?? date('Y');
    
    // Function to get monthly sales data
    function getMonthlySales($pdo, $year, $month) {
        // Get sales data by categories from both POS and online orders
        $sql = "
        SELECT 
            c.category_name as category,
            SUM(combined_sales.total_sales) as sales_amount,
            SUM(combined_sales.quantity) as total_quantity
        FROM (
            -- POS Sales (Primary source)
            SELECT 
                psi.product_id,
                SUM(psi.total_price) as total_sales,
                SUM(psi.quantity) as quantity
            FROM pos_sale_items psi
            INNER JOIN pos_sales ps ON psi.sale_id = ps.sale_id
            WHERE YEAR(ps.sale_date) = ? 
            AND MONTH(ps.sale_date) = ?
            AND ps.status = 'completed'
            GROUP BY psi.product_id
            
            UNION ALL
            
            -- Online Orders (calculated from current product prices)
            SELECT 
                oi.product_id,
                SUM(p.price * oi.quantity) as total_sales,
                SUM(oi.quantity) as quantity
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE YEAR(o.date) = ? 
            AND MONTH(o.date) = ?
            AND o.status IN ('Processing', 'Shipping', 'Delivered', 'Received')
            GROUP BY oi.product_id
        ) as combined_sales
        INNER JOIN products p ON combined_sales.product_id = p.product_id
        INNER JOIN product_category c ON p.category_id = c.category_id
        GROUP BY c.category_id, c.category_name
        ORDER BY sales_amount DESC
        LIMIT 7
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$year, $month, $year, $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get available years for dropdown
    $years_sql = "
        SELECT DISTINCT YEAR(sale_date) as year FROM pos_sales 
        WHERE sale_date IS NOT NULL
        UNION 
        SELECT DISTINCT YEAR(date) as year FROM orders 
        WHERE date IS NOT NULL
        ORDER BY year DESC
    ";
    
    try {
        $years_stmt = $pdo->query($years_sql);
        $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // If no years found, add current year as default
        if (empty($available_years)) {
            $available_years = [date('Y')];
        }
    } catch (Exception $e) {
        // Fallback to current year if query fails
        $available_years = [date('Y')];
    }
    
    // Month names
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Sales Leaderboards">
    <meta name="author" content="SAPIN">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

    <title>Sales Leaderboards - SAPIN</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
	
	<style>
		body { background-color: #f7f9fc; }
		
		.page-header {
			background: white;
			color: #0f172a;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
			border: 1px solid #e5e7eb;
		}
		
		.page-header h1 {
			font-weight: 700;
			margin: 0;
			font-size: 1.75rem;
			color: #0f172a;
		}
		
		.page-header p {
			color: #64748b;
		}
		
		.forecasting-container {
			background: transparent;
			min-height: 100vh;
			padding: 0;
		}
		
		.month-card {
			background: #ffffff;
			border-radius: 14px;
			box-shadow: 0 8px 24px rgba(2, 6, 23, 0.06);
			margin-bottom: 20px;
			overflow: hidden;
			border: 1px solid #e5e7eb; /* lighter border */
		}
		
		.month-header {
			background: #1e3a8a; /* dark navy for header */
			color: #ffffff;
			padding: 15px 20px;
			font-size: 18px;
			font-weight: 600;
		}
		
		.rank-table {
			width: 100%;
			border-collapse: collapse;
		}
		
		.rank-table th {
			background: #f1f5f9; /* light slate */
			padding: 12px 15px;
			text-align: left;
			font-weight: 600;
			color: #0f172a;
			border-bottom: 2px solid #e5e7eb;
		}
		
		.rank-table td {
			padding: 12px 15px;
			border-bottom: 1px solid #e9ecef;
		}
		
		.rank-table tbody tr:hover {
			background: #f1f5f9;
		}
		
		.rank-number {
			width: 50px;
			text-align: center;
			font-weight: bold;
			color: #1e3a8a; /* navy */
		}
		
		.product-name {
			font-weight: 500;
		}
		
		.sales-amount {
			text-align: right;
			font-weight: 600;
			color: #1e293b; /* dark slate for readability */
		}
		
		.total-row {
			background: #f8fafc !important;
			border-top: 2px solid #93c5fd; /* soft blue divider */
			font-weight: bold;
		}
		
		.total-row td {
			border-bottom: none;
			color: #1e293b;
		}
		
		.year-selector {
			background: #ffffff;
			border-radius: 12px;
			padding: 20px;
			margin-bottom: 30px;
			box-shadow: 0 4px 12px rgba(2, 6, 23, 0.06);
			border: 1px solid #e5e7eb;
		}
		
		.no-data {
			text-align: center;
			padding: 40px 20px;
			color: #6c757d;
		}
		
		.grid-container {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
			gap: 20px;
		}
		
		.print-btn {
			background: #1e3a8a; /* navy button to match theme */
			border: none;
			color: #ffffff;
			padding: 10px 20px;
			border-radius: 8px;
			font-weight: 600;
			transition: transform 0.15s ease, box-shadow 0.15s ease;
		}

		.print-btn:hover {
			transform: translateY(-1px);
			box-shadow: 0 8px 18px rgba(2, 6, 23, 0.10);
		}
		
		@media print {
			.no-print { display: none !important; }
			.month-card { break-inside: avoid; }
		}
		
		/* Print header (hidden on screen) */
        #print-header { display: none; }
        @media print {
            .no-print { display: none !important; }
            .month-card { break-inside: avoid; }
            #print-header { display: block; margin-bottom: 12px; }
            #print-header .ph { display: table; width: 100%; }
            #print-header .cell { display: table-cell; vertical-align: middle; }
            #print-header .brand { width: 20%; }
            #print-header .brand img { width: 42mm; height: auto; }
            #print-header .center { text-align: center; font-weight: 700; font-size: 16px; }
            #print-header .meta { text-align: right; font-size: 12px; color: #334155; }
        }
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'forecasting'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content forecasting-container">
				<div class="container-fluid p-0">
                    <!-- Print-only Header -->
                    <div id="print-header" class="no-print" style="display:none">
                        <div class="ph">
                            <div class="cell brand">
                                <img src="../assets/img/logo_forsapin.jpg" alt="SAPIN Logo">
                            </div>
                            <div class="cell center">SAPIN<br>Sales Leaderboards</div>
                            <div class="cell meta">Year: <?= $selected_year ?><br>Generated: <?= date('F d, Y') ?></div>
                        </div>
                    </div>
					<!-- Page Header -->
					<div class="page-header no-print">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h1>
									<i class="bi bi-trophy me-2"></i>Sales Leaderboards (<?= $selected_year ?>)
								</h1>
								<p class="mb-0 mt-2" style="opacity: 0.9;">Monthly ranking of top-selling categories and performance trends</p>
							</div>
						<div class="d-flex gap-2">
							<button class="btn print-btn" id="downloadPDF">
								<i class="bi bi-printer me-1"></i>Print Report
							</button>
						</div>
					</div>

					<!-- Year Selector -->
					<div class="year-selector no-print">
						<form method="GET" class="d-flex align-items-center gap-3">
							<label for="year" class="form-label mb-0 fw-semibold">Select Year:</label>
							<select name="year" id="year" class="form-select w-auto" onchange="this.form.submit()">
								<?php foreach ($available_years as $year): ?>
									<option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>>
										<?= $year ?>
									</option>
								<?php endforeach; ?>
							</select>
						</form>
					</div>

					<!-- Monthly Sales Grids -->
					<div class="grid-container">
						<?php for ($month = 1; $month <= 12; $month++): 
							$monthly_data = getMonthlySales($pdo, $selected_year, $month);
							$monthly_total = array_sum(array_column($monthly_data, 'sales_amount'));
						?>
							<div class="month-card">
								<div class="month-header">
									<span class="badge bg-light text-dark me-2" style="font-size: 0.9rem; padding: 0.4rem 0.6rem;"><?= str_pad($month, 2, '0', STR_PAD_LEFT) ?></span>
									<?= $months[$month] ?>
								</div>
								
								<table class="rank-table">
									<thead>
										<tr>
											<th>Rank</th>
											<th>Category</th>
											<th>Sales (₱)</th>
										</tr>
									</thead>
									<tbody>
										<?php if (empty($monthly_data)): ?>
											<tr>
												<td colspan="3" class="no-data">
													<i class="bi bi-inbox text-muted"></i><br>
													No sales data available
												</td>
											</tr>
										<?php else: ?>
											<?php $rank = 1; ?>
											<?php foreach ($monthly_data as $item): ?>
												<tr>
													<td class="rank-number"><?= $rank ?></td>
													<td class="product-name"><?= htmlspecialchars($item['category']) ?></td>
													<td class="sales-amount"><?= number_format($item['sales_amount'], 2) ?></td>
												</tr>
												<?php $rank++; ?>
											<?php endforeach; ?>
											
											<!-- Total Row -->
											<tr class="total-row">
												<td colspan="2" class="fw-bold">TOTAL</td>
												<td class="sales-amount fw-bold"><?= number_format($monthly_total, 2) ?></td>
											</tr>
										<?php endif; ?>
									</tbody>
								</table>
							</div>
						<?php endfor; ?>
					</div>

					<!-- Annual Summary -->
					<?php
					// Calculate annual totals
					$annual_sql = "
					SELECT 
						c.category_name as category,
						SUM(combined_sales.total_sales) as sales_amount,
						SUM(combined_sales.quantity) as total_quantity
					FROM (
						-- POS Sales (Primary source)
						SELECT 
							psi.product_id,
							SUM(psi.total_price) as total_sales,
							SUM(psi.quantity) as quantity
						FROM pos_sale_items psi
						INNER JOIN pos_sales ps ON psi.sale_id = ps.sale_id
						WHERE YEAR(ps.sale_date) = ? 
						AND ps.status = 'completed'
						GROUP BY psi.product_id
						
						UNION ALL
						
						-- Online Orders (calculated from current product prices)
						SELECT 
							oi.product_id,
							SUM(p.price * oi.quantity) as total_sales,
							SUM(oi.quantity) as quantity
						FROM order_items oi
						INNER JOIN orders o ON oi.order_id = o.order_id
						INNER JOIN products p ON oi.product_id = p.product_id
						WHERE YEAR(o.date) = ? 
						AND o.status IN ('Processing', 'Shipping', 'Delivered', 'Received')
						GROUP BY oi.product_id
					) as combined_sales
					INNER JOIN products p ON combined_sales.product_id = p.product_id
					INNER JOIN product_category c ON p.category_id = c.category_id
					GROUP BY c.category_id, c.category_name
					ORDER BY sales_amount DESC
					LIMIT 10
					";
					
					$annual_stmt = $pdo->prepare($annual_sql);
					$annual_stmt->execute([$selected_year, $selected_year]);
					$annual_data = $annual_stmt->fetchAll(PDO::FETCH_ASSOC);
					$annual_total = array_sum(array_column($annual_data, 'sales_amount'));
					?>

					<div class="month-card mt-4">
						<div class="month-header">
							<i class="bi bi-trophy me-2"></i>
							Annual Top Performers (<?= $selected_year ?>)
						</div>
						
						<table class="rank-table">
							<thead>
								<tr>
									<th>Rank</th>
									<th>Category</th>
									<th>Sales (₱)</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($annual_data)): ?>
									<tr>
										<td colspan="3" class="no-data">
											<i class="bi bi-inbox text-muted"></i><br>
											No sales data available for <?= $selected_year ?>
										</td>
									</tr>
								<?php else: ?>
									<?php $rank = 1; ?>
									<?php foreach ($annual_data as $item): ?>
										<tr>
											<td class="rank-number"><?= $rank ?></td>
											<td class="product-name"><?= htmlspecialchars($item['category']) ?></td>
											<td class="sales-amount"><?= number_format($item['sales_amount'], 2) ?></td>
										</tr>
										<?php $rank++; ?>
									<?php endforeach; ?>
									
									<!-- Total Row -->
									<tr class="total-row">
										<td colspan="2" class="fw-bold">TOTAL</td>
										<td class="sales-amount fw-bold"><?= number_format($annual_total, 2) ?></td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</main>

			<footer class="footer no-print">
				<div class="container-fluid">
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">
								&copy; 2025 <a class="text-muted" href="#"><strong>SAPIN</strong></a>
							</p>
						</div>
						<div class="col-6 text-end">
							<ul class="list-inline">
								<li class="list-inline-item">
									<span class="text-muted">Sales Leaderboards v1.0</span>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="js/app.js"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<script>
	// === Download PDF (Document Style) ===
	document.getElementById("downloadPDF").addEventListener("click", async function(){
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
		const margin = 12;
		let yPos = margin;

		// Load SAPIN logo as data URL
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
		
		// Header (more compact) + logo
		pdf.setFillColor(30, 58, 138); // Navy blue
		pdf.rect(0, 0, pageWidth, 30, 'F');
		pdf.addImage(logoDataUrl, 'JPEG', margin, 7, 16, 16);
		pdf.setTextColor(255, 255, 255);
		pdf.setFontSize(18);
		pdf.setFont(undefined, 'bold');
		pdf.text("SALES LEADERBOARDS REPORT", pageWidth / 2, 14, { align: 'center' });
		pdf.setFontSize(11);
		pdf.setFont(undefined, 'normal');
		pdf.text("SAPIN", pageWidth / 2, 20, { align: 'center' });
		pdf.setFontSize(9);
		pdf.text("Year: <?= $selected_year ?> | Generated: <?= date('F d, Y') ?>", pageWidth / 2, 26, { align: 'center' });
		
		yPos = 38;
		
		        // Monthly Sales Data (build summary per month)
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const monthlySummary = [
            <?php for ($month = 1; $month <= 12; $month++): 
                $monthly_data = getMonthlySales($pdo, $selected_year, $month);
                $monthly_total = array_sum(array_column($monthly_data, 'sales_amount'));
                $monthly_units = array_sum(array_column($monthly_data, 'total_quantity'));
                $top = null;
                if (!empty($monthly_data)) {
                    usort($monthly_data, function($a,$b){ return $b['sales_amount'] <=> $a['sales_amount']; });
                    $top = $monthly_data[0];
                }
            ?>
            {
                month: '<?= $months[$month] ?>',
                totalSales: <?= $monthly_total ?: 0 ?>,
                totalUnits: <?= $monthly_units ?: 0 ?>,
                topCategory: '<?= isset($top['category']) ? addslashes($top['category']) : '' ?>',
                topSales: <?= isset($top['sales_amount']) ? $top['sales_amount'] : 0 ?>
            },
            <?php endfor; ?>
        ];

        // Build one compact summary table
        const summaryBody = monthlySummary.map(m => [
            m.month,
            'P' + formatNumber(m.totalSales),
            m.totalUnits.toString(),
            m.topCategory || '-',
            m.topCategory ? ('P' + formatNumber(m.topSales)) : '-'
        ]);

        // Add annual totals row
        const annualSales = monthlySummary.reduce((s,m)=>s+m.totalSales,0);
        const annualUnits = monthlySummary.reduce((s,m)=>s+m.totalUnits,0);
        summaryBody.push(['TOTAL', 'P' + formatNumber(annualSales), annualUnits.toString(), '', '']);

        pdf.autoTable({
            startY: yPos,
            head: [['Month','Total Sales (P)','Units Sold','Top Category','Top Category Sales (P)']],
            body: summaryBody,
            theme: 'grid',
            styles: { font: 'helvetica', fontSize: 9 },
            headStyles: { fillColor: [30,58,138], fontSize: 10, fontStyle:'bold', font:'helvetica' },
            bodyStyles: { font: 'helvetica' },
            columnStyles: { 1: {halign:'right'}, 2:{halign:'center'}, 4:{halign:'right'} },
            margin: { left: margin, right: margin }
        });
        yPos = pdf.lastAutoTable.finalY + 8;
		
		// Footer on all pages
		const totalPages = pdf.internal.getNumberOfPages();
		for (let i = 1; i <= totalPages; i++) {
			pdf.setPage(i);
			pdf.setFontSize(8);
			pdf.setTextColor(128, 128, 128);
			pdf.text(`Page ${i} of ${totalPages}`, pageWidth / 2, pageHeight - 10, { align: 'center' });
			pdf.text("SAPIN - Confidential", margin, pageHeight - 10);
		}
		
		// Open print dialog instead of downloading
		pdf.autoPrint();
		window.open(pdf.output('bloburl'), '_blank');
	});
	</script>

</body>
</html>
