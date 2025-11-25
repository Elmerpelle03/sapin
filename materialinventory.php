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
    
    // Check if we need to trigger restock modals for insufficient materials
    $trigger_restock_modal = false;
    $insufficient_materials = [];
    $context_info = [];
    
    if (isset($_SESSION['trigger_restock_modal']) && $_SESSION['trigger_restock_modal'] === true) {
        $trigger_restock_modal = true;
        $insufficient_materials = $_SESSION['insufficient_materials'] ?? [];
        $context_info = $_SESSION['attempted_product'] ?? $_SESSION['stock_update_context'] ?? $_SESSION['variant_update_context'] ?? [];
        
        // Clear the trigger so it doesn't happen again on refresh
        unset($_SESSION['trigger_restock_modal']);
        unset($_SESSION['insufficient_materials']);
        unset($_SESSION['attempted_product']);
        unset($_SESSION['stock_update_context']);
        unset($_SESSION['variant_update_context']);
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

	<title>Material Inventory</title>

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
		/* Page theming: white background with blue + gray accents */
		body { background-color: #f7f9fc; }
		
		.page-header {
			background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
			color: white;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 8px 24px rgba(139, 92, 246, 0.2);
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
		
		.stats-card.total { border-left-color: #8b5cf6; }
		.stats-card.low { border-left-color: #ef4444; }
		.stats-card.sufficient { border-left-color: #10b981; }
		
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
		
		.card { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(17, 24, 39, 0.06); }
		.card-header { background: #fff; border-bottom: 1px solid #eef2f7; border-top-left-radius: 14px; border-top-right-radius: 14px; }
		.card-title { font-weight: 600; color: #0f172a; }

		/* Toolbar */
		.inventory-toolbar { gap: 12px; flex-wrap: wrap; }
		.inventory-toolbar .form-control, .inventory-toolbar .form-select { border-radius: 10px; border: 1px solid #e5e7eb; }
		.inventory-toolbar .form-control:focus, .inventory-toolbar .form-select:focus { box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .2); border-color: #93c5fd; }

		/* Add Material button */
		.btn-primary { background: #2563eb; border-color: #2563eb; border-radius: 10px; box-shadow: 0 6px 14px rgba(37, 99, 235, .25); }
		.btn-primary:hover { background: #1d4ed8; border-color: #1d4ed8; transform: translateY(-1px); }
		.btn-primary:active { transform: translateY(0); }

		/* Table */
		.table { --bs-table-bg: #fff; }
		.table thead th { background: #f1f5f9; color: #0f172a; font-weight: 600; border-bottom: 0; }
		.table tbody tr { transition: box-shadow .2s ease, transform .05s ease; }
		.table-hover tbody tr:hover { box-shadow: 0 6px 16px rgba(15, 23, 42, .05); }

		/* Icon buttons */
		.btn-icon { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; color: #0f172a; transition: all .2s ease; }
		.btn-icon:hover { box-shadow: 0 6px 14px rgba(2, 6, 23, .08); transform: translateY(-1px); }
		.btn-icon.edit { color: #0ea5e9; border-color: #bae6fd; background: #f0f9ff; }
		.btn-icon.edit:hover { background: #e0f2fe; }
		.btn-icon.delete { color: #ef4444; border-color: #fecaca; background: #fff1f2; }
		.btn-icon.delete:hover { background: #ffe4e6; }

		/* Stock progress */
		.stock-wrap { min-width: 220px; }
		.stock-label { font-size: .85rem; color: #334155; }
		.progress { height: 10px; background-color: #e5e7eb; border-radius: 9999px; }
		.progress-bar.sufficient { background-color: #22c55e; }
		.progress-bar.near { background-color: #f59e0b; }
		.progress-bar.low { background-color: #ef4444; }

		/* DataTables: hide default filter since we have a custom search */
		.dataTables_wrapper .dataTables_filter { display: none; }
		
		/* Highlight targeted row */
		.highlight-row {
			animation: highlight-pulse 2s ease-in-out;
			background-color: #fef3c7 !important;
		}
		
		@keyframes highlight-pulse {
			0%, 100% { 
				background-color: transparent;
			}
			50% { 
				background-color: #fef3c7;
				box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.3);
			}
		}
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'materialinventory'; ?>
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
									<i class="bi bi-box-seam me-2"></i>Material Inventory Management
								</h1>
								<p class="mb-0 mt-2" style="opacity: 0.9;">Track and manage raw materials and supplies</p>
							</div>
							<button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
								<i class="bi bi-plus-circle me-2"></i>Add Material
							</button>
						</div>
					</div>

					<?php
					// Fetch material statistics
					$total_materials = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
					$low_stock = $pdo->query("SELECT COUNT(*) FROM materials WHERE stock < 50")->fetchColumn();
					$sufficient_stock = $pdo->query("SELECT COUNT(*) FROM materials WHERE stock >= 50")->fetchColumn();
					$total_stock = $pdo->query("SELECT SUM(stock) FROM materials")->fetchColumn();
					?>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card total h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-boxes text-purple me-2" style="font-size: 1.5rem; color: #8b5cf6;"></i>
									<p class="stats-label mb-0">Total Materials</p>
								</div>
								<h2 class="stats-number" style="color: #8b5cf6;"><?= $total_materials ?></h2>
								<small class="text-muted">Items in inventory</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card low h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-exclamation-triangle-fill text-danger me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Low Stock</p>
								</div>
								<h2 class="stats-number text-danger"><?= $low_stock ?></h2>
								<small class="text-muted">Need reordering</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card sufficient h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Sufficient</p>
								</div>
								<h2 class="stats-number text-success"><?= $sufficient_stock ?></h2>
								<small class="text-muted">Adequate stock</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #3b82f6;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-stack text-info me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Total Stock</p>
								</div>
								<h2 class="stats-number text-info"><?= number_format($total_stock, 2) ?></h2>
								<small class="text-muted">Total units</small>
							</div>
						</div>
					</div>

				<!-- Toolbar: search -->
				<div class="card mb-3">
					<div class="card-body">
						<div class="d-flex inventory-toolbar">
							<div class="input-group" style="max-width: 420px;">
								<span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
								<input type="search" id="materialSearch" class="form-control border-start-0" placeholder="Search materials...">
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-12">
						<div class="card">
							<div class="card-header">
								<h5 class="card-title mb-0">Materials</h5>
							</div>
							<div class="card-body">
								<div class="table-responsive" style="width:100%">
									<table id="usersTable" class="display table table-hover align-middle w-100">
										<thead>
											<tr>
												<th>ID</th>
												<th>Material Name</th>
												<th>Description</th>
												<th>Stock</th>
												<th>Reorder Point</th>
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
	<?php require 'modals/addmaterial.php'; ?>
	<?php require 'modals/editmaterial.php'; ?>
	
	<script>
	$(document).ready(function() {
		// Compute status based on stock and reorder point
		function computeStatus(stock, reorderPoint) {
			const stockNum = parseFloat(stock) || 0;
			const rop = parseFloat(reorderPoint) || 0;
			
			if (!rop || rop <= 0) {
				return stockNum > 0 ? 'sufficient' : 'low';
			}
			if (stockNum <= 0 || stockNum <= rop) return 'low';
			if (stockNum <= rop * 1.5) return 'near';
			return 'sufficient';
		}

		const table = $('#usersTable').DataTable({
			responsive: true,
			ajax: 'backend/fetch_materials.php',
			rowCallback: function(row, data) {
				// Add ID to row for anchor linking
				$(row).attr('id', 'material-' + data.material_id);
				
				// Highlight if this is the targeted row
				if (window.location.hash === '#material-' + data.material_id) {
					$(row).addClass('highlight-row');
				}
			},
			columns: [
				{ data: 'material_id' },
				{ data: 'material_name' },
				{ data: 'description' },
				{ data: 'stock_with_unit', render: function(data, type, row){
					const stockNum = parseFloat(row.stock) || 0;
					const rop = parseFloat(row.reorder_point) || 0;
					const status = computeStatus(stockNum, rop);
					const pctBase = Math.max(rop, 1);
					let pct = Math.round((stockNum / pctBase) * 100);
					pct = Math.max(0, Math.min(100, pct));
					const cls = status === 'sufficient' ? 'sufficient' : (status === 'near' ? 'near' : 'low');
					return `
						<div class="stock-wrap">
							<div class="progress mb-1">
								<div class="progress-bar ${cls}" role="progressbar" style="width: ${pct}%" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div>
							</div>
							<div class="d-flex justify-content-between stock-label">
								<span>${row.stock_with_unit || '-'}</span>
								<span class="text-muted">ROP: ${row.reorder_point ?? '-'}</span>
							</div>
						</div>`;
					}
				},
				{ data: 'reorder_point' },
				{ data: null, className: 'all text-nowrap', orderable: false, render: function(data, type, row){
					return `
						<button type="button" class="btn btn-sm btn-warning restock-btn" title="Request Restock" data-material-id="${row.material_id}" data-material-name="${row.material_name}" data-current-stock="${row.stock}" data-unit="${row.unit}">
							<i class="bi bi-box-seam"></i> Restock
						</button>
						<button type="button" class="btn btn-sm btn-icon edit edit-btn" title="Edit" data-material-id="${row.material_id}" data-bs-toggle="modal" data-bs-target="#editMaterialModal">✏️</button>
						<button type="button" class="btn btn-sm btn-icon delete delete-btn" title="Delete" data-material-id="${row.material_id}">🗑</button>
					`;
					}
				}
			],
			// Use compact layout without showing the default search box
			dom: '<"top"rt><"bottom"ip><"clear">'
		});

		// Custom search bar hookup
		$('#materialSearch').on('input', function(){
			table.search(this.value).draw();
		});

		// Edit handler (support responsive child rows)
		$('#usersTable').on('click', '.edit-btn', function () {
			let rowEl = $(this).closest('tr');
			if (rowEl.hasClass('child')) {
				rowEl = rowEl.prev();
			}
			const data = table.row(rowEl).data();

			$('#editMaterialId').val(data.material_id);
			$('#editMaterialName').val(data.material_name);
			$('#editDescription').val(data.description);
			$('#editMaterialUnit').val(data.materialunit_id);
			$('#editStock').val(data.stock);
			$('#editReorderPoint').val(data.reorder_point);
		});

		// Delete handler
		$('#usersTable').on('click', '.delete-btn', function () {
			const rowEl = $(this).closest('tr');
			const material_id = $(this).data('material-id');
			Swal.fire({
				title: 'Are you sure?',
				text: "You won't be able to revert this action!",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#d33',
				cancelButtonColor: '#3085d6',
				confirmButtonText: 'Yes, delete it!',
				cancelButtonText: 'No, cancel!'
			}).then((result) => {
				if (result.isConfirmed) {
					$.ajax({
						url: 'backend/deletematerial.php',
						method: 'POST',
						data: { material_id: material_id },
						success: function(response) {
							if (response === 'success') {
								Swal.fire('Deleted!', 'Your material has been deleted.', 'success');
								table.row(rowEl).remove().draw(false);
							} else {
								Swal.fire('Error!', 'There was a problem deleting the material.', 'error');
							}
						},
						error: function() {
							Swal.fire('Error!', 'An unexpected error occurred.', 'error');
						}
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

	<script>
		// Scroll to targeted material smoothly after table loads
		$(document).ready(function() {
			$('#usersTable').on('draw.dt', function() {
				if (window.location.hash) {
					const targetId = window.location.hash;
					const targetElement = document.querySelector(targetId);
					
					if (targetElement) {
						setTimeout(() => {
							targetElement.scrollIntoView({ 
								behavior: 'smooth', 
								block: 'center' 
							});
						}, 300);
					}
				}
			});
		});

		// Handle Restock Request button
		$(document).on('click', '.restock-btn', function() {
			const materialId = $(this).data('material-id');
			const materialName = $(this).data('material-name');
			const currentStock = $(this).data('current-stock');
			// Use stored unit if available (for auto-triggered modals), otherwise use button data
			const unit = window.currentRestockUnit || $(this).data('unit') || 'units';
			
			console.log('Restock modal unit:', unit);

			Swal.fire({
				title: 'Request Material Restock',
				html: `
					<div class="text-start">
						<p><strong>Material:</strong> ${materialName}</p>
						<p><strong>Current Stock:</strong> ${currentStock} ${unit}</p>
						<hr>
						<label class="form-label">Quantity to Request: *</label>
						<div class="input-group">
							<input type="number" id="restockQuantity" class="form-control" min="1" step="0.01" placeholder="Enter quantity">
							<select class="form-select" id="restockUnit" style="max-width: 150px;">
								<option value="yards" ${unit === 'yards' ? 'selected' : ''}>yards</option>
								<option value="meters" ${unit === 'meters' ? 'selected' : ''}>meters</option>
								<option value="grams" ${unit === 'grams' ? 'selected' : ''}>grams</option>
								<option value="kilograms" ${unit === 'kilograms' ? 'selected' : ''}>kilograms</option>
								<option value="pieces" ${unit === 'pieces' ? 'selected' : ''}>pieces</option>
								<option value="units" ${unit === 'units' ? 'selected' : ''}>units</option>
							</select>
						</div>
						
						<label class="form-label mt-3">Supplier Contact: *</label>
						<div class="input-group">
							<select class="form-select" id="contactType" style="max-width: 120px;">
								<option value="mobile">Mobile</option>
								<option value="email">Email</option>
							</select>
							<input type="text" id="supplierContact" class="form-control" placeholder="09XXXXXXXXX or email@example.com">
						</div>
						<small class="text-muted" id="contactHint">Enter supplier's mobile number</small>
						
						<br><br>
						
						<label class="form-label mt-3">Message:</label>
						<textarea id="restockMessage" class="form-control" rows="2" placeholder="e.g., Please deliver by Friday"></textarea>
						
						<div class="alert alert-info mt-3 mb-0">
							<small><i class="bi bi-info-circle"></i> This will prepare a message to send to your supplier.</small>
						</div>
					</div>
				`,
				showCancelButton: true,
				confirmButtonText: 'Prepare Request',
				confirmButtonColor: '#ffc107',
				width: '550px',
				didOpen: () => {
					// Add contact type change handler
					const contactTypeSelect = document.getElementById('contactType');
					const supplierContactInput = document.getElementById('supplierContact');
					const contactHint = document.getElementById('contactHint');
					
					// Function to restrict mobile input to numbers only
					function restrictToNumbers(e) {
						const char = String.fromCharCode(e.which);
						if (!/[0-9]/.test(char)) {
							e.preventDefault();
							
							// Show helpful message
							const hint = document.getElementById('contactHint');
							hint.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Mobile numbers can only contain digits. If you need to enter an email address, please select "Email" from the dropdown.</span>';
							hint.classList.add('fw-bold');
							
							// Reset hint after 3 seconds
							setTimeout(() => {
								hint.innerHTML = 'Enter supplier\'s mobile number';
								hint.classList.remove('fw-bold');
							}, 3000);
						}
					}
					
					// Function to clean mobile input (remove non-numbers)
					function cleanMobileInput() {
						if (contactTypeSelect.value === 'mobile') {
							this.value = this.value.replace(/[^0-9]/g, '');
						}
					}
					
					contactTypeSelect.addEventListener('change', function() {
						if (this.value === 'mobile') {
							supplierContactInput.placeholder = '09XXXXXXXXX';
							supplierContactInput.type = 'tel';
							contactHint.textContent = 'Enter supplier\'s mobile number';
							
							// Add input restrictions for mobile
							supplierContactInput.addEventListener('keypress', restrictToNumbers);
							supplierContactInput.addEventListener('input', cleanMobileInput);
						} else {
							supplierContactInput.placeholder = 'supplier@example.com';
							supplierContactInput.type = 'email';
							contactHint.textContent = 'Enter supplier\'s email address';
							
							// Remove input restrictions for email
							supplierContactInput.removeEventListener('keypress', restrictToNumbers);
							supplierContactInput.removeEventListener('input', cleanMobileInput);
						}
						supplierContactInput.value = '';
					});
					
					// Apply mobile restrictions by default (since mobile is default)
					supplierContactInput.addEventListener('keypress', restrictToNumbers);
					supplierContactInput.addEventListener('input', cleanMobileInput);
				},
				preConfirm: () => {
					const quantity = document.getElementById('restockQuantity').value;
					const contactType = document.getElementById('contactType').value;
					const supplierContact = document.getElementById('supplierContact').value;
					const message = document.getElementById('restockMessage').value;
					
					if (!quantity || quantity <= 0) {
						Swal.showValidationMessage('Please enter a valid quantity');
						return false;
					}
					
					if (!supplierContact) {
						Swal.showValidationMessage('Please enter supplier contact');
						return false;
					}
					
					// Validate mobile number (numbers only)
					if (contactType === 'mobile') {
						const mobilePattern = /^[0-9]+$/;
						if (!mobilePattern.test(supplierContact)) {
							Swal.showValidationMessage('Mobile number should contain numbers only');
							return false;
						}
					}
					
					// Validate email format
					if (contactType === 'email') {
						const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
						if (!emailPattern.test(supplierContact)) {
							Swal.showValidationMessage('Please enter a valid email address');
							return false;
						}
					}
					
					// Validate message (required)
					if (!message || message.trim().length === 0) {
						Swal.showValidationMessage('Please enter a message for the supplier');
						return false;
					}
					
					return { quantity, contactType, supplierContact, message };
				}
			}).then((result) => {
				if (result.isConfirmed) {
					// Get selected unit
					const selectedUnit = document.getElementById('restockUnit').value;
					
					// Prepare formal message for supplier
					let supplierMessage = `SAPIN\n`;
					supplierMessage += `140 Rose St., Brgy. Paciano Rizal, Bay, Laguna\n`;
					supplierMessage += `\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n`;
					supplierMessage += `MATERIAL RESTOCK REQUEST\n`;
					supplierMessage += `━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n`;
					supplierMessage += `Dear Supplier,\n\n`;
					supplierMessage += `We would like to request the following material:\n\n`;
					supplierMessage += `Material: ${materialName}\n`;
					supplierMessage += `Quantity Needed: ${result.value.quantity} ${selectedUnit}\n`;
					supplierMessage += `Current Stock: ${currentStock} ${unit}\n`;
					if (result.value.message) {
						supplierMessage += `\nAdditional Notes: ${result.value.message}\n`;
					}
					supplierMessage += `\nPlease confirm availability and delivery schedule at your earliest convenience.\n\n`;
					supplierMessage += `Thank you for your continued support.\n\n`;
					supplierMessage += `Best regards,\n`;
					supplierMessage += `SAPIN`;
					
					// Show message and send options. Save ONLY when the admin actually sends.
                    Swal.fire({
                        title: 'Request Message Ready',
                        html: `
                            <div class="text-start">
                                <div class="alert alert-info py-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Once you send this message, your request will be saved in <strong>Supplier Request History</strong>.
                                    After your order arrives, go there and click <strong>Received</strong> to add the requested quantity to your Material Inventory.
                                </div>
                                <p><strong>Send to:</strong> ${result.value.supplierContact}</p>
                                <p><strong>Via:</strong> ${result.value.contactType === 'mobile' ? 'SMS/WhatsApp' : 'Email'}</p>
                                <hr>
                                <textarea class="form-control" rows="10" id="copyMessage" readonly>${supplierMessage}</textarea>
                                
                                <div class="d-grid gap-2 mt-3">
                                    ${result.value.contactType === 'mobile' ? `
                                        <button id="sendSmsBtn" class="btn btn-success">
                                            <i class="bi bi-phone"></i> Send via SMS Now
                                        </button>
                                        <button id="sendWaBtn" class="btn btn-success">
                                            <i class="bi bi-whatsapp"></i> Send via WhatsApp Now
                                        </button>
                                    ` : `
                                        <button id="sendGmailBtn" class="btn btn-primary">
                                            <i class="bi bi-envelope"></i> Send via Gmail Now
                                        </button>
                                    `}
                                    <button id="copyBtn" class="btn btn-sm btn-secondary">
                                        <i class="bi bi-clipboard"></i> Or Copy Message
                                    </button>
                                </div>
                            </div>
                        `,
                        width: '600px',
                        showCancelButton: true,
                        confirmButtonText: 'Done',
                        cancelButtonText: 'Close',
                        didOpen: () => {
                            function saveThen(openFn) {
                                $.ajax({
                                    url: 'backend/save_supplier_request.php',
                                    type: 'POST',
                                    data: {
                                        material_id: materialId,
                                        requested_quantity: result.value.quantity,
                                        supplier_contact: result.value.supplierContact,
                                        contact_type: result.value.contactType,
                                        message: result.value.message,
                                        current_stock: currentStock
                                    },
                                    dataType: 'json'
                                }).always(function() {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Request saved',
                                        text: 'You can mark Received in Supplier Requests when it arrives.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                    if (typeof openFn === 'function') openFn();
                                });
                            }

                            const smsBtn = document.getElementById('sendSmsBtn');
                            if (smsBtn) smsBtn.addEventListener('click', function() {
                                const url = `sms:${result.value.supplierContact}?body=${encodeURIComponent(supplierMessage)}`;
                                saveThen(() => window.open(url, '_blank'));
                            });

                            const waBtn = document.getElementById('sendWaBtn');
                            if (waBtn) waBtn.addEventListener('click', function() {
                                const url = `https://wa.me/${result.value.supplierContact.replace(/[^0-9]/g, '')}?text=${encodeURIComponent(supplierMessage)}`;
                                saveThen(() => window.open(url, '_blank'));
                            });

                            const gmBtn = document.getElementById('sendGmailBtn');
                            if (gmBtn) gmBtn.addEventListener('click', function() {
                                const url = `https://mail.google.com/mail/?view=cm&fs=1&to=${result.value.supplierContact}&su=${encodeURIComponent('Material Restock Request - ' + materialName)}&body=${encodeURIComponent(supplierMessage)}`;
                                saveThen(() => window.open(url, '_blank'));
                            });

                            const copyBtn = document.getElementById('copyBtn');
                            if (copyBtn) copyBtn.addEventListener('click', function() {
                                document.getElementById('copyMessage').select();
                                document.execCommand('copy');
                                Swal.fire({icon:'success',title:'Copied!',timer:1000,toast:true,position:'top-end',showConfirmButton:false});
                            });
                        }
                    });
				}
			});
		});
		
		<?php if ($trigger_restock_modal && !empty($insufficient_materials)): ?>
		// Auto-trigger restock modals for insufficient materials
		$(document).ready(function() {
			const insufficientMaterials = <?php echo json_encode($insufficient_materials); ?>;
			const contextInfo = <?php echo json_encode($context_info); ?>;
			
			console.log('Insufficient materials:', insufficientMaterials);
			console.log('Context info:', contextInfo);
			
			// Show context message first
			let contextMessage = '';
			if (contextInfo.name) {
				contextMessage = `Cannot create product "${contextInfo.name}" - need to restock materials.`;
			} else if (contextInfo.product_name) {
				contextMessage = `Cannot update stock for "${contextInfo.product_name}" - need to restock materials.`;
			} else if (contextInfo.total_units_requested) {
				contextMessage = `Cannot update variant stock for ${contextInfo.total_units_requested} units - need to restock materials.`;
			}
			
			if (contextMessage) {
				Swal.fire({
					icon: 'info',
					title: 'Restock Required',
					text: contextMessage,
					timer: 2000,
					showConfirmButton: false,
					toast: true,
					position: 'top-end'
				});
			}
			
			// Trigger restock modal for each insufficient material
			setTimeout(() => {
				triggerRestockForMaterials(insufficientMaterials, 0);
			}, 2500);
		});
		
		function triggerRestockForMaterials(materials, index) {
			if (index >= materials.length) {
				// Show completion message
				Swal.fire({
					icon: 'success',
					title: 'Restock Forms Opened',
					text: `Restock forms opened for ${materials.length} material(s). Please complete the requests.`,
					timer: 2000,
					showConfirmButton: false,
					toast: true,
					position: 'top-end'
				});
				return;
			}
			
			const material = materials[index];
			const materialName = material.name || material; // Handle both new and old structure
			console.log('Looking for material:', materialName);
			
			// Try multiple selectors to find the restock button
			let restockBtn = $(`.restock-btn[data-material-name="${materialName}"]`).first();
			
			if (restockBtn.length === 0) {
				// Try partial match
				restockBtn = $(`.restock-btn[data-material-name*="${materialName}"]`).first();
			}
			
			if (restockBtn.length === 0) {
				// Try looking for material name in the table row
				restockBtn = $('tr').filter(function() {
					return $(this).text().includes(materialName);
				}).find('.restock-btn').first();
			}
			
			console.log('Found restock button:', restockBtn.length > 0 ? 'Yes' : 'No');
			
			if (restockBtn.length > 0) {
				console.log('Triggering restock for:', materialName);
				console.log('Unit from button:', restockBtn.data('unit'));
				
				// Store the material info for auto-filling
				window.currentRestockMaterial = material;
				window.currentRestockUnit = restockBtn.data('unit') || 'units';
				
				// Trigger the restock modal
				restockBtn.click();
				
				// Wait for modal to appear, then auto-fill quantity
				setTimeout(() => {
					const quantityField = document.getElementById('restockQuantity');
					if (quantityField && window.currentRestockMaterial) {
						let quantityToFill = 0;
						
						// Handle different material structures
						if (window.currentRestockMaterial.need_quantity) {
							// From addproduct.php
							quantityToFill = window.currentRestockMaterial.need_quantity;
						} else if (window.currentRestockMaterial.needed) {
							// From quick_stock_update.php
							quantityToFill = window.currentRestockMaterial.needed;
						} else if (window.currentRestockMaterial.shortage) {
							// Fallback to shortage amount
							quantityToFill = window.currentRestockMaterial.shortage;
						}
						
						if (quantityToFill > 0) {
							quantityField.value = quantityToFill;
							console.log('Auto-filled quantity with:', quantityToFill);
						}
					}
				}, 500);
				
				// Wait for user to close the modal, then trigger next one
				const checkModalClosed = setInterval(() => {
					if (!$('.swal2-container').is(':visible')) {
						clearInterval(checkModalClosed);
						console.log('Modal closed, triggering next material');
						// Trigger next material after a short delay
						setTimeout(() => {
							triggerRestockForMaterials(materials, index + 1);
						}, 500);
					}
				}, 1000);
			} else {
				// Material not found in table, skip to next
				console.warn(`Material "${materialName}" not found in table. Available materials:`);
				$('.restock-btn').each(function() {
					console.log('Available:', $(this).data('material-name'));
				});
				triggerRestockForMaterials(materials, index + 1);
			}
		}
		<?php endif; ?>
	</script>

</body>

</html>