<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
    
    // Restrict to Super Admin only
    if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
        header('Location: index.php');
        exit;
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
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="Expenses Management">
	<meta name="author" content="Sapin Bedsheets">

	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link rel="shortcut icon" href="img/icons/icon-48x48.png" />

	<title>Expenses - Sapin Bedsheets</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="../assets/css/style.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css">
	
	<style>
		body { background-color: #f7f9fc; }
		
		.page-header {
			background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
			color: white;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 8px 24px rgba(239, 68, 68, 0.2);
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
		
		.stats-card.total { border-left-color: #ef4444; }
		.stats-card.monthly { border-left-color: #10b981; }
		.stats-card.avg { border-left-color: #f59e0b; }
		
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

		.expenses-toolbar { gap: 12px; flex-wrap: wrap; }
		.expenses-toolbar .form-control, .expenses-toolbar .form-select { border-radius: 10px; border: 1px solid #e5e7eb; }
		.expenses-toolbar .form-control:focus, .expenses-toolbar .form-select:focus { box-shadow: 0 0 0 .2rem rgba(37,99,235,.2); border-color: #93c5fd; }

		.table thead th { background: #f1f5f9; color: #0f172a; font-weight: 600; border-bottom: 0; }
		.table-hover tbody tr:hover { box-shadow: 0 6px 16px rgba(15,23,42,.05); }

		.badge-category { font-weight: 600; border-radius: 9999px; padding: .35rem .6rem; }
		.badge-materials { background: #8b5cf6; color: #fff; }
		.badge-utilities { background: #f59e0b; color: #fff; }
		.badge-salaries { background: #3b82f6; color: #fff; }
		.badge-rent { background: #ef4444; color: #fff; }
		.badge-transportation { background: #10b981; color: #fff; }
		.badge-marketing { background: #ec4899; color: #fff; }
		.badge-miscellaneous { background: #6b7280; color: #fff; }

		.btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; color: #0f172a; transition: all .2s ease; }
		.btn-icon:hover { box-shadow: 0 6px 14px rgba(2,6,23,.08); transform: translateY(-1px); }
		.btn-icon.edit { color: #0ea5e9; border-color: #bae6fd; background: #f0f9ff; }
		.btn-icon.edit:hover { background: #e0f2fe; }
		.btn-icon.delete { color: #ef4444; border-color: #fecaca; background: #fff1f2; }
		.btn-icon.delete:hover { background: #ffe4e6; }

		.dataTables_wrapper .dataTables_filter { display: none; }

		.summary-cards .card { border-left: 4px solid; }
		.summary-cards .card.total { border-left-color: #2563eb; }
		.summary-cards .card.monthly { border-left-color: #10b981; }
		.summary-cards .card.avg { border-left-color: #f59e0b; }
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'expenses'; ?>
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
									<i class="bi bi-wallet2 me-2"></i>Expenses Management
								</h1>
								<p class="mb-0 mt-2" style="opacity: 0.9;">Track and manage business expenses</p>
							</div>
							<button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
								<i class="bi bi-plus-circle me-2"></i>Add Expense
							</button>
						</div>
					</div>

					<?php
					// Fetch expense statistics
					$total_expenses = $pdo->query("SELECT SUM(amount) FROM expenses")->fetchColumn();
					$monthly_expenses = $pdo->query("SELECT SUM(amount) FROM expenses WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) AND YEAR(expense_date) = YEAR(CURRENT_DATE())")->fetchColumn();
					$expense_count = $pdo->query("SELECT COUNT(*) FROM expenses")->fetchColumn();
					$avg_expense = $expense_count > 0 ? $total_expenses / $expense_count : 0;
					?>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card total h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-cash-stack me-2" style="font-size: 1.5rem; color: #ef4444;"></i>
									<p class="stats-label mb-0">Total Expenses</p>
								</div>
								<h2 class="stats-number" style="color: #ef4444;">₱<?= number_format($total_expenses, 2) ?></h2>
								<small class="text-muted">All time</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card monthly h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-calendar-check text-success me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">This Month</p>
								</div>
								<h2 class="stats-number text-success">₱<?= number_format($monthly_expenses, 2) ?></h2>
								<small class="text-muted"><?= date('F Y') ?></small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card avg h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-graph-up text-warning me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Average</p>
								</div>
								<h2 class="stats-number text-warning">₱<?= number_format($avg_expense, 2) ?></h2>
								<small class="text-muted">Per expense</small>
							</div>
						</div>
					</div>


				<!-- Toolbar: search and filters -->
				<div class="card mb-3">
					<div class="card-body">
						<div class="d-flex expenses-toolbar">
							<div class="input-group" style="max-width: 340px;">
								<span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
								<input type="search" id="expensesSearch" class="form-control border-start-0" placeholder="Search expenses...">
							</div>
							<div style="min-width: 200px;">
								<select id="categoryFilter" class="form-select">
									<option value="" selected>All Categories</option>
									<option value="Materials">Materials</option>
									<option value="Utilities">Utilities</option>
									<option value="Salaries">Salaries</option>
									<option value="Rent">Rent</option>
									<option value="Transportation">Transportation</option>
									<option value="Marketing">Marketing</option>
									<option value="Miscellaneous">Miscellaneous</option>
								</select>
							</div>
							<div>
								<input type="date" id="dateFrom" class="form-control" placeholder="From">
							</div>
							<div>
								<input type="date" id="dateTo" class="form-control" placeholder="To">
							</div>
						</div>
					</div>
				</div>

					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Expense Records</h5>
								</div>
								<div class="card-body">
									<div class="table-responsive" style="width:100%">
										<table id="expensesTable" class="display table table-hover align-middle w-100">
											<thead>
												<tr>
													<th>ID</th>
													<th>Date</th>
													<th>Category</th>
													<th>Expense Name</th>
													<th>Amount</th>
													<th>Description</th>
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
					<div class="row text-muted">
						<div class="col-6 text-start">
							<p class="mb-0">
								&copy; 2025 <a class="text-muted" href="#"><strong>Sapin Bedsheets</strong></a>
							</p>
						</div>
					</div>
				</div>
			</footer>
		</div>
	</div>
	
	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>
	
	<?php require 'modals/addexpense.php'; ?>
	<?php require 'modals/editexpense.php'; ?>
	
	<script>
	$(document).ready(function(){
		function formatDate(str){
			if(!str) return '-';
			const d = new Date(str);
			if(isNaN(d)) return str;
			const opts = { year:'numeric', month:'short', day:'numeric' };
			return d.toLocaleString(undefined, opts);
		}

		function categoryBadge(category){
			const map = {
				'Materials': 'materials',
				'Utilities': 'utilities',
				'Salaries': 'salaries',
				'Rent': 'rent',
				'Transportation': 'transportation',
				'Marketing': 'marketing',
				'Miscellaneous': 'miscellaneous'
			};
			const cls = map[category] || 'miscellaneous';
			return `<span class="badge-category badge-${cls}">${category}</span>`;
		}

		const table = $('#expensesTable').DataTable({
			processing: true,
			serverSide: true,
			responsive: true,
			ajax: {
				url: 'backend/fetch_expenses.php',
				data: function(d){
					d.category = $('#categoryFilter').val();
					d.date_from = $('#dateFrom').val();
					d.date_to = $('#dateTo').val();
				}
			},
			order: [[1, 'desc']],
			dom: '<"top"rt><"bottom"ip><"clear">',
			columns: [
				{ data: 'expense_id' },
				{ data: 'expense_date', render: function(d){ return formatDate(d); } },
				{ data: 'expense_category', render: function(d){ return categoryBadge(d); } },
				{ data: 'expense_name' },
				{ data: 'amount', render: function(v){
					const num = parseFloat(v||0);
					return `<strong class="text-success">₱${num.toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2})}</strong>`;
				}},
				{ data: 'description', render: function(d){ return d || '-'; } },
				{ data: null, orderable: false, className: 'text-nowrap', render: function(d, t, row){
					return `
						<button type="button" class="btn btn-sm btn-icon edit edit-btn" title="Edit" data-expense-id="${row.expense_id}" data-bs-toggle="modal" data-bs-target="#editExpenseModal">✏️</button>
						<button type="button" class="btn btn-sm btn-icon delete delete-btn" title="Delete" data-expense-id="${row.expense_id}">🗑</button>
					`;
				}}
			]
		});

		// Custom search
		$('#expensesSearch').on('input', function(){ table.search(this.value).draw(); });
		// Filters
		$('#categoryFilter, #dateFrom, #dateTo').on('change input', function(){ table.draw(); });

		// Load summary statistics
		function loadSummary(){
			$.get('backend/expense_summary.php', function(data){
				$('#totalExpenses').text('₱' + parseFloat(data.total || 0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
				$('#monthlyExpenses').text('₱' + parseFloat(data.monthly || 0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
				$('#avgExpenses').text('₱' + parseFloat(data.average || 0).toLocaleString(undefined,{minimumFractionDigits:2, maximumFractionDigits:2}));
			}, 'json');
		}
		loadSummary();

		// Edit handler
		$('#expensesTable').on('click', '.edit-btn', function(){
			let rowEl = $(this).closest('tr');
			if (rowEl.hasClass('child')) {
				rowEl = rowEl.prev();
			}
			const data = table.row(rowEl).data();

			$('#editExpenseId').val(data.expense_id);
			$('#editExpenseCategory').val(data.expense_category);
			$('#editExpenseName').val(data.expense_name);
			$('#editAmount').val(data.amount);
			$('#editExpenseDate').val(data.expense_date);
			$('#editDescription').val(data.description);
		});

		// Delete handler
		$('#expensesTable').on('click', '.delete-btn', function(){
			const rowEl = $(this).closest('tr');
			const expense_id = $(this).data('expense-id');
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
						url: 'backend/delete_expense.php',
						method: 'POST',
						data: { expense_id },
						success: function(resp){
							if(resp === 'success'){
								Swal.fire('Deleted!', 'The expense has been deleted.', 'success');
								table.row(rowEl).remove().draw(false);
								loadSummary();
							}else{
								Swal.fire('Error!', 'There was a problem deleting the expense.', 'error');
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
