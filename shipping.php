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
	<meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
	<meta name="author" content="AdminKit">
	<meta name="keywords" content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<link rel="canonical" href="https://demo-basic.adminkit.io/pages-blank.html" />

	<title>Shipping Rules</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<!-- DataTables CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	<!-- Responsive extension CSS -->
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">

	<style>
        :root{
            --bg: #f7f9fc;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --danger: #ef4444;
            --danger-hover: #dc2626;
        }

        body { background: var(--bg); }
        
        .page-header {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            padding: 2rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(20, 184, 166, 0.2);
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
        
        .stats-card.total { border-left-color: #14b8a6; }
        .stats-card.active { border-left-color: #10b981; }
        .stats-card.inactive { border-left-color: #6b7280; }
        
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
        .card-title { font-weight: 600; color: var(--text); }

        /* Header Add button */
        .btn-add { border-radius: 9999px; padding: .55rem 1rem; background: var(--primary); border-color: var(--primary); box-shadow: 0 6px 14px rgba(37,99,235,.25); }
        .btn-add:hover { background: var(--primary-hover); border-color: var(--primary-hover); transform: translateY(-1px); }

        /* Table styling */
        .table-responsive { overflow-x: auto; }
        .table { border-collapse: separate; border-spacing: 0; }
        .table thead th { background: #f8fafc; color: #334155; font-weight: 600; border-bottom: 0; padding: 12px 14px; }
        .table tbody td { padding: 12px 14px; vertical-align: middle; }
        .table tbody tr:nth-child(even) { background: #fbfdff; }
        .table tbody tr:hover { background: #f1f5f9; }
        .table-rounded { border-radius: 14px; overflow: hidden; border: 1px solid var(--border); background: var(--card-bg); }

        /* Buttons in Action column coming from backend */
        .edit-btn { background: #eff6ff !important; color: var(--primary) !important; border: 1px solid #bfdbfe !important; border-radius: 9999px !important; padding: .35rem .7rem !important; transition: all .15s ease; }
        .edit-btn:hover { background: #dbeafe !important; }
        .delete-btn { background: #fff1f2 !important; color: var(--danger) !important; border: 1px solid #fecaca !important; border-radius: 9999px !important; padding: .35rem .7rem !important; transition: all .15s ease; }
        .delete-btn:hover { background: #ffe4e6 !important; }

        /* Sidebar alignment (non-invasive) */
        .sidebar .sidebar-item .sidebar-link { display: flex; align-items: center; gap: 10px; }
        .sidebar .sidebar-item .sidebar-link i { font-size: 1.1rem; }
    </style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'shipping'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid">
					<!-- Page Header -->
					<div class="page-header">
						<div class="d-flex justify-content-between align-items-center">
							<div>
								<h1>
									<i class="bi bi-truck me-2"></i>Shipping Rules Management
								</h1>
								<p class="mb-0 mt-2" style="opacity: 0.9;">Configure shipping fees and delivery zones</p>
							</div>
							<button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addRuleModal">
								<i class="bi bi-plus-circle me-2"></i>Add Rule
							</button>
						</div>
					</div>

					<?php
					// Fetch shipping statistics
					$total_rules = $pdo->query("SELECT COUNT(*) FROM shipping_rules")->fetchColumn();
					$avg_fee = $pdo->query("SELECT AVG(shipping_fee) FROM shipping_rules")->fetchColumn();
					$max_fee = $pdo->query("SELECT MAX(shipping_fee) FROM shipping_rules")->fetchColumn();
					?>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4" id="statsCards">
						<div class="col">
							<div class="stats-card total h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-geo-alt text-teal me-2" style="font-size: 1.5rem; color: #14b8a6;"></i>
									<p class="stats-label mb-0">Total Rules</p>
								</div>
								<h2 class="stats-number" style="color: #14b8a6;" id="totalRules"><?= $total_rules ?></h2>
								<small class="text-muted">Shipping zones</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #3b82f6;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-cash-coin text-info me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Average Fee</p>
								</div>
								<h2 class="stats-number text-info" id="avgFee">₱<?= number_format($avg_fee, 2) ?></h2>
								<small class="text-muted">Per delivery</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #f59e0b;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-arrow-up-circle-fill text-warning me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Highest Fee</p>
								</div>
								<h2 class="stats-number text-warning" id="maxFee">₱<?= number_format($max_fee, 2) ?></h2>
								<small class="text-muted">Maximum charge</small>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Shipping Rules</h5>
								</div>
								<div class="card-body">
									<div class="table-responsive table-rounded" style="width:100%">
										<table id="shippingTable" class="display table table-hover align-middle">
											<thead>
												<tr>
													<th>ID</th>
													<th>Rule Name</th>
													<th>Area</th>
													<th>Fee</th>
													<th>Action</th>
												</tr>
											</thead>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>

			</main>

			<style>
				.custom-spinner {
					width: 3rem;
					height: 3rem;
					border: 0.4rem solid #f3f3f3;
					border-top: 0.4rem solid var(--bs-primary);
					border-radius: 50%;
					animation: spin 1s linear infinite;
					margin: 1rem auto;
				}

				@keyframes spin {
					0% { transform: rotate(0deg); }
					100% { transform: rotate(360deg); }
				}
			</style>


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
	
    <?php include 'modals/addshipping.php' ?>
	<script>
	// Function to update statistics cards
	function updateStats() {
		$.ajax({
			url: 'backend/get_shipping_stats.php',
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				if (data.success) {
					$('#totalRules').text(data.total_rules);
					$('#avgFee').text('₱' + data.avg_fee);
					$('#maxFee').text('₱' + data.max_fee);
				}
			}
		});
	}

	$(document).ready(function () {
        $('#shippingTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: "backend/fetch_shipping.php",
            columns: [
                { data: "rule_id" },
                { data: "rule_name" },
                { data: "area" },
                { data: "shipping_fee" },
                { data: "action", className: "all", orderable: false, searchable: false }
            ]
        });
    });

	$(document).on('click', '.delete-btn', function () {
		const ruleId = $(this).data('id');

		Swal.fire({
			title: 'Are you sure?',
			text: "This action cannot be undone.",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d33',
			cancelButtonColor: '#6c757d',
			confirmButtonText: 'Yes, delete it!'
		}).then((result) => {
			if (result.isConfirmed) {
				$.ajax({
					url: 'backend/delete_shipping.php',
					type: 'POST',
					data: { rule_id: ruleId },
					success: function (response) {
						const res = response;
						$('#shippingTable').DataTable().ajax.reload();
						updateStats(); // Update statistics cards
						if (res.success) {
							Swal.fire(
								'Deleted!',
								'The shipping rule has been removed.',
								'success'
							);
						} else {
							Swal.fire(
								'Failed!',
								'An error occurred while deleting.',
								'error'
							);
						}
					},
					error: function () {
						Swal.fire(
							'Error!',
							'Could not connect to the server.',
							'error'
						);
					}
				});
			}
		});
	});

		
	</script>
	<?php if(isset($success_message)): ?>
        <script>
            $(document).ready(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo $success_message; ?>'
                });
                // Update statistics after adding/editing (table already reloaded via page refresh)
                updateStats();
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