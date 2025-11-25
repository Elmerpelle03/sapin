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
	$stmt = $pdo->prepare("UPDATE orders SET seen = 1 WHERE seen = 0");
    $stmt->execute();
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

	<title>Orders</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<!-- DataTables CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	<!-- Responsive extension CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
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
		
		.stats-card.total { border-left-color: #3b82f6; }
		.stats-card.pending { border-left-color: #f59e0b; }
		.stats-card.completed { border-left-color: #10b981; }
		.stats-card.cancelled { border-left-color: #ef4444; }
		
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
		
		.card { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(17,24,39,.06); }
		.card-header { background: #fff; border-bottom: 1px solid #eef2f7; border-top-left-radius: 14px; border-top-right-radius: 14px; }
		.card-title { font-weight: 600; color: #0f172a; }

		.btn-primary { background: #2563eb; border-color: #2563eb; border-radius: 10px; box-shadow: 0 6px 14px rgba(37,99,235,.25); }
		.btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; transform: translateY(-1px); }

		.orders-toolbar { gap: 12px; flex-wrap: wrap; }
		.orders-toolbar .form-control, .orders-toolbar .form-select { border-radius: 10px; border: 1px solid #e5e7eb; }
		.orders-toolbar .form-control:focus, .orders-toolbar .form-select:focus { box-shadow: 0 0 0 .2rem rgba(37,99,235,.2); border-color: #93c5fd; }
		
		/* Mobile responsive filters */
		@media (max-width: 768px) {
			.orders-toolbar { flex-direction: column; }
			.orders-toolbar > div { width: 100% !important; max-width: 100% !important; min-width: 100% !important; }
			.orders-toolbar .input-group { max-width: 100% !important; }
		}

		.table thead th { background: #f1f5f9; color: #0f172a; font-weight: 600; border-bottom: 0; }
		.table-hover tbody tr:hover { box-shadow: 0 6px 16px rgba(15,23,42,.05); }

		.badge-status { font-weight: 600; }
		.badge-status.pending { background-color: #f59e0b; }
		.badge-status.shipped { background-color: #3b82f6; }
		.badge-status.delivered { background-color: #22c55e; }
		.badge-status.cancelled { background-color: #ef4444; }

		.badge-pill { border-radius: 9999px; padding: .35rem .6rem; font-weight: 600; }
		.badge-cod { background: #6b7280; color: #fff; }
		.badge-gcash { background: #8b5cf6; color: #fff; }
		.badge-cc { background: #3b82f6; color: #fff; }

		.amount-strong { font-weight: 800; color: #16a34a; }

		.btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; color: #0f172a; transition: all .2s ease; }
		.btn-icon:hover { box-shadow: 0 6px 14px rgba(2,6,23,.08); transform: translateY(-1px); }
		.btn-icon.view { color: #2563eb; border-color: #bfdbfe; background: #eff6ff; }
		.btn-icon.view:hover { background: #dbeafe; }
		.btn-icon.delete { color: #ef4444; border-color: #fecaca; background: #fff1f2; }
		.btn-icon.delete:hover { background: #ffe4e6; }

		/* Hide default DataTables search box */
		.dataTables_wrapper .dataTables_filter { display: none; }
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'orders'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">
					<!-- Page Header -->
					<div class="page-header">
						<h1>
							<i class="bi bi-box-seam me-2"></i>Order Management
						</h1>
						<p class="mb-0 mt-2" style="opacity: 0.9;">Track and manage all customer orders</p>
					</div>

					<?php
					// Fetch order statistics - exact status match
					$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
					$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
					$processing_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Processing'")->fetchColumn();
					$completed_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Delivered', 'Received')")->fetchColumn();
					$cancelled_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Cancelled'")->fetchColumn();
					?>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card total h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-box-seam text-primary me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Total</p>
								</div>
								<h2 class="stats-number text-primary"><?= $total_orders ?></h2>
								<small class="text-muted">All orders</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card pending h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-clock-history text-warning me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Pending</p>
								</div>
								<h2 class="stats-number text-warning"><?= $pending_orders ?></h2>
								<small class="text-muted">Awaiting</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #3b82f6;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-truck text-info me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Processing</p>
								</div>
								<h2 class="stats-number text-info"><?= $processing_orders ?></h2>
								<small class="text-muted">In transit</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card completed h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Completed</p>
								</div>
								<h2 class="stats-number text-success"><?= $completed_orders ?></h2>
								<small class="text-muted">Delivered</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card cancelled h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-x-circle-fill text-danger me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Cancelled</p>
								</div>
								<h2 class="stats-number text-danger"><?= $cancelled_orders ?></h2>
								<small class="text-muted">Cancelled</small>
							</div>
						</div>
					</div>

				<!-- Toolbar: search and filters -->
				<div class="card mb-3">
					<div class="card-body">
						<div class="d-flex orders-toolbar">
							<div class="input-group" style="max-width: 340px;">
								<span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
								<input type="search" id="ordersSearch" class="form-control border-start-0" placeholder="Search orders...">
							</div>
							<div style="min-width: 200px;">
								<select id="statusFilter" class="form-select">
									<option value="all" selected>All Status</option>
									<option value="Pending">Pending</option>
									<option value="Processing">Processing</option>
									<option value="Shipping">Shipping</option>
									<option value="Delivered">Delivered</option>
									<option value="Received">Received</option>
									<option value="Cancelled">Cancelled</option>
								</select>
							</div>
							<div style="min-width: 200px;">
								<select id="customerTypeFilter" class="form-select">
									<option value="all" selected>All Customers</option>
									<option value="bulk">Wholesalers Only</option>
									<option value="regular">Regular Customers Only</option>
								</select>
							</div>
							<div>
								<input type="date" id="dateFrom" class="form-control" placeholder="From">
							</div>
							<div>
								<input type="date" id="dateTo" class="form-control" placeholder="To">
							</div>
							<div class="ms-auto" style="max-width: 260px;">
								<input type="text" id="customerFilter" class="form-control" placeholder="Filter by customer name">
							</div>
						</div>
					</div>
				</div>

					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Orders</h5>
								</div>
								<div class="card-body">
									<div class="table-responsive" style="width:100%">
										<table id="usersTable" class="display table table-hover align-middle w-100">
											<thead>
												<tr>
													<th>Order</th>
													<th>Order Date</th>
													<th>Full Name</th>
													<th>Contact Number</th>
													<th>Shipping Address</th>
													<th>Payment Method</th>
													<th>Reference</th>
													<th>Proof</th>
													<th>Amount</th>
													<th>Action</th>
												</tr>
											</thead>
											<tbody>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
			</main>

			<footer class="footer">
				<div class="container-fluid">
					
				</div>
			</footer>
		</div>
	</div>
	

	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<!-- Responsive extension JS -->
	<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
	<!-- No modals required here; view navigates to dedicated page -->
	
	<script>
	$(document).ready(function(){
		function formatDate(str){
			if(!str) return '-';
			const d = new Date(str.replace(' ', 'T'));
			if(isNaN(d)) return str;
			const opts = { year:'numeric', month:'short', day:'numeric', hour:'numeric', minute:'2-digit' };
			return d.toLocaleString(undefined, opts);
		}

		function statusBadge(status){
			const map = {
				'Pending': 'pending',
				'Shipping': 'shipped',
				'Processing': 'shipped',
				'Delivered': 'delivered',
				'Received': 'delivered',
				'Cancelled': 'cancelled'
			};
			const cls = map[status] || 'pending';
			return `<span class="badge badge-status ${cls} text-white">${status}</span>`;
		}

		function paymentPill(method){
			const m = (method||'').toLowerCase();
			if(m.includes('cod')) return '<span class="badge-pill badge-cod">COD</span>';
			if(m.includes('gcash')) return '<span class="badge-pill badge-gcash">GCash</span>';
			if(m.includes('bpi')) return '<span class="badge-pill badge-cc">BPI</span>';
			if(m.includes('bdo')) return '<span class="badge-pill badge-cc">BDO</span>';
			if(m.includes('credit')) return '<span class="badge-pill badge-cc">Credit Card</span>';
			return `<span class="badge-pill badge-cod">${method||'-'}</span>`;
		}

		function proofIcon(proof, payment_method){
			const onlineMethods = ['GCash', 'GCash1', 'GCash2', 'BPI', 'BDO'];
			const isOnline = onlineMethods.some(m => payment_method.includes(m));
			
			if (!isOnline) {
				return '<span class="text-muted small">N/A</span>';
			}
			
			if (proof && proof.trim() !== '') {
				return '<i class="bi bi-check-circle-fill text-success" title="Proof uploaded"></i>';
			} else {
				return '<i class="bi bi-exclamation-circle-fill text-warning" title="No proof"></i>';
			}
		}

		const table = $('#usersTable').DataTable({
			processing: true,
			serverSide: true,
			responsive: true,
			pageLength: 5,
			lengthMenu: [[5, 10, 25, 50, 100], [5, 10, 25, 50, 100]],
			ajax: {
				url: 'backend/fetch_orders.php',
				data: function(d){
					d.status = $('#statusFilter').val();
					d.customer_type = $('#customerTypeFilter').val();
					d.date_from = $('#dateFrom').val();
					d.date_to = $('#dateTo').val();
					d.customer = $('#customerFilter').val();
				}
			},
			order: [[1, 'desc']],
			lengthChange: true,
			dom: 'l<"toolbar">frtip',
			columns: [
				{ data: 'order_id', render: function(data, type, row){
					let html = `<div><span class="fw-semibold text-dark">#${data}</span></div><div class="mt-1">${statusBadge(row.status)}`;
					
					// Add cancellation badge if exists
					if (row.cancellation_status) {
						if (row.cancellation_status === 'pending') {
							html += ` <span class="badge bg-warning text-dark ms-1" style="font-size:0.7rem"><i class="bi bi-clock"></i> Cancel Pending</span>`;
						} else if (row.cancellation_status === 'approved') {
							html += ` <span class="badge bg-success ms-1" style="font-size:0.7rem"><i class="bi bi-check-circle"></i> Cancel Approved</span>`;
						} else if (row.cancellation_status === 'rejected') {
							html += ` <span class="badge bg-danger ms-1" style="font-size:0.7rem"><i class="bi bi-x-circle"></i> Cancel Rejected</span>`;
						}
					}
					
					html += `</div>`;
					return html;
				}},
				{ data: 'date', render: function(d){ return formatDate(d); } },
				{ data: 'fullname' },
				{ data: 'contact_number' },
				{ data: 'shipping_address' },
				{ data: 'payment_method', render: function(d){ return paymentPill(d); } },
				{ data: 'payment_reference', orderable: false, className: 'text-center', render: function(d, t, row){
					if (d && ['GCash1', 'GCash2', 'BPI', 'BDO'].includes(row.payment_method)) {
						return `<span style="font-family: monospace; font-size: 0.8rem; background: #f3f4f6; padding: 2px 6px; border-radius: 4px;" title="Reference Number">${d}</span>`;
					}
					return '';
				}},
				{ data: null, orderable: false, className: 'text-center', render: function(d, t, row){
					return proofIcon(row.proof_of_payment, row.payment_method);
				}},
				{ data: 'amount', render: function(v){
					const num = parseFloat(v||0);
					return `<span class="amount-strong">₱${num.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}</span>`;
				}},
				{ data: null, orderable: false, className: 'text-nowrap', render: function(d, t, row){
					let buttons = `<div class="d-flex gap-1 align-items-center">`;
					
					// View Order button
					buttons += `<a class="btn btn-sm btn-icon view" title="View Order" href="view_order.php?order_id=${row.order_id}">👁</a>`;
					
					// Add "View Cancellation Request" button if there's a pending cancellation
					if (row.cancellation_status === 'pending') {
						buttons += `
						<a class="btn btn-sm btn-warning text-white" title="View Cancellation Request" href="cancellation_requests.php#order-${row.order_id}" style="font-size:0.7rem; padding:0.3rem 0.6rem; white-space: nowrap;">
							<i class="bi bi-exclamation-circle-fill"></i> View Cancel
						</a>`;
					}
					
					// Delete button
					buttons += `<button type="button" class="btn btn-sm btn-icon delete delete-btn" title="Delete" data-order-id="${row.order_id}">🗑</button>`;
					
					buttons += `</div>`;
					return buttons;
				}}
			],
			columnDefs: [
				{ targets: [7, 9], orderable: false }
			]
		});

		// Custom search
		$('#ordersSearch').on('input', function(){ table.search(this.value).draw(); });
		// Filters
		$('#statusFilter, #customerTypeFilter, #dateFrom, #dateTo, #customerFilter').on('change input', function(){ table.draw(); });

		// Delete handler
		$('#usersTable').on('click', '.delete-btn', function(){
			const rowEl = $(this).closest('tr');
			const order_id = $(this).data('order-id');
			Swal.fire({
				title: 'Are you sure?',
				text: "You won't be able to revert this action!",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Yes, delete it!',
				cancelButtonText: 'No, cancel!'
			}).then((result)=>{
				if(result.isConfirmed){
					$.ajax({
						url: 'backend/delete_order.php',
						method: 'POST',
						data: { order_id },
						success: function(resp){
							if(resp === 'success'){
								Swal.fire('Deleted!', 'The order has been deleted.', 'success');
								$('#usersTable').DataTable().row(rowEl).remove().draw(false);
							}else{
								Swal.fire('Error!', 'There was a problem deleting the order.', 'error');
							}
						},
						error: function(){ Swal.fire('Error!', 'An unexpected error occurred.', 'error'); }
					});
				}
			});
		});
	});
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