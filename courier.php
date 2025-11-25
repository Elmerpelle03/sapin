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

	<title>Courier</title>

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
			background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
			color: white;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 8px 24px rgba(14, 165, 233, 0.2);
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
		
		.stats-card.total { border-left-color: #0ea5e9; }
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
		
		.card { 
			border: none; 
			border-radius: 14px; 
			box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
		}
	</style>
</head>

<body>
	<div class="wrapper">
		<?php $active = 'courier'; ?>
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
									<i class="bi bi-bicycle me-2"></i>Courier Management
								</h1>
								<p class="mb-0 mt-2" style="opacity: 0.9;">Manage delivery personnel and courier accounts</p>
							</div>
							<button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addStaffModal">
								<i class="bi bi-plus-circle me-2"></i>Add Courier
							</button>
						</div>
					</div>

					<?php
					// Fetch courier statistics
					$total_couriers = $pdo->query("SELECT COUNT(*) FROM users WHERE usertype_id = 4")->fetchColumn();
					$verified_couriers = $pdo->query("SELECT COUNT(*) FROM users WHERE usertype_id = 4 AND is_verified = 1")->fetchColumn();
					$unverified_couriers = $pdo->query("SELECT COUNT(*) FROM users WHERE usertype_id = 4 AND is_verified = 0")->fetchColumn();
					?>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card total h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-person-badge me-2" style="font-size: 1.5rem; color: #0ea5e9;"></i>
									<p class="stats-label mb-0">Total Couriers</p>
								</div>
								<h2 class="stats-number" style="color: #0ea5e9;"><?= $total_couriers ?></h2>
								<small class="text-muted">Delivery personnel</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #10b981;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-patch-check-fill text-success me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Verified</p>
								</div>
								<h2 class="stats-number text-success"><?= $verified_couriers ?></h2>
								<small class="text-muted">Email verified</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card h-100" style="border-left-color: #f59e0b;">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-exclamation-circle-fill text-warning me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Unverified</p>
								</div>
								<h2 class="stats-number text-warning"><?= $unverified_couriers ?></h2>
								<small class="text-muted">Pending verification</small>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title mb-0">Courier List</h5>
								</div>
								<div class="card-body">
									<div class="table-responsive" style="width:100%">
										<table id="usersTable" class="display table">
											<thead>
												<tr>
													<th>ID</th>
													<th>Username</th>
													<th>Full Name</th>
													<th>Email</th>
													<th>Verified</th>
													<th>Status</th>
                                                    <th>Action</th>
												</tr>
											</thead>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>
			</main>

			<div class="modal fade" id="viewuserModal" tabindex="-1" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">User Details</h5>
							<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
						</div>
						<div class="modal-body">
							<div class="row mb-2">
								<div class="col-5 fw-bold">Username:</div>
								<div class="col-7" id="modalUsername"></div>
							</div>
							<div class="row mb-2">
								<div class="col-5 fw-bold">Email:</div>
								<div class="col-7" id="modalEmail"></div>
							</div>
							<div class="row mb-2">
								<div class="col-5 fw-bold">Full Name:</div>
								<div class="col-7" id="modalFullname"></div>
							</div>
							<div class="row mb-2">
								<div class="col-5 fw-bold">Contact Number:</div>
								<div class="col-7" id="modalContact"></div>
							</div>
							<div class="row mb-2">
								<div class="col-5 fw-bold">Address:</div>
								<div class="col-7" id="modalAddress"></div>
							</div>
							<div class="row mb-2">
								<div class="col-5 fw-bold">Verified:</div>
								<div class="col-7" id="modalVerified"></div>
							</div>
							<div class="row">
								<div class="col-5 fw-bold">Account Status:</div>
								<div class="col-7" id="modalStatus"></div>
							</div>
						</div>
						<div class="modal-footer">
							<form id="toggleUserStatusForm" method="POST" action="backend/toggle_user_status.php">
								<input type="hidden" name="user_id" id="modalUserId" value="">
								<input type="hidden" name="current_status" id="modalUserCurrentStatus" value="">
								<button type="submit" id="toggleUserStatusBtn" class="btn btn-danger">Disable User</button>
							</form>
							<button type="button" id="resetPasswordBtn" class="btn btn-primary">Reset Password</button>
						</div>
					</div>
				</div>
			</div>
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
	<?php $isPageUsers = false; ?>
	<?php require 'modals/addstaff.php'; ?>
	
	<script>
	$(document).ready(function() {
		$('#usersTable').DataTable({
			"processing": true,
			"serverSide": true,
			"responsive": true,
			"ajax": "backend/fetch_couriers.php",
			"columns": [
				{ "data": "user_id" },
				{ "data": "username" },
				{ "data": "fullname" },
				{ "data": "email" },
				{ "data": "verified" },
				{ "data": "status" },
				{ "data": "action", className: "all" }
			]
		});
		$('#usersTable').on('click', '.view-user-btn', function () {
			const user = $(this).data('user');

			$('#modalUserId').val(user.user_id);
    		$('#modalUserCurrentStatus').val(user.status_raw);
			$('#modalUsername').text(user.username);
			$('#modalEmail').text(user.email);
			$('#modalFullname').text(user.fullname);
			$('#modalContact').text(user.contact);
			$('#modalAddress').text(user.address);
			$('#modalVerified').html(user.verified_html);
			$('#modalStatus').html(user.status_html);

			if (user.usertype_id !== 1) {
				if (user.status_raw === 'Active') {
					$('#toggleUserStatusBtn').text('Disable User').removeClass('btn-success').addClass('btn-danger').show();
				} else {
					$('#toggleUserStatusBtn').text('Enable User').removeClass('btn-danger').addClass('btn-success').show();
				}
			} else {
				$('#toggleUserStatusBtn').hide();
			}

			const userModal = new bootstrap.Modal(document.getElementById('viewuserModal'));
			userModal.show();
		});
	});
	document.getElementById('toggleUserStatusForm').addEventListener('submit', function(e) {
		e.preventDefault();

		Swal.fire({
			title: 'Are you sure?',
			text: "Do you want to change the user's status?",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Yes, proceed',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				this.submit();
			}
		});
	});

	document.getElementById('resetPasswordBtn').addEventListener('click', function() {
		// Get the email from the modal span
		const email = document.getElementById('modalEmail').textContent.trim();

		if (!email) {
			Swal.fire('Error', 'Email not available.', 'error');
			return;
		}

		Swal.fire({
			title: 'Send Reset Password Link?',
			text: `Send reset password link to ${email}?`,
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Yes, send it',
			cancelButtonText: 'Cancel'
		}).then((result) => {
			if (result.isConfirmed) {
				Swal.fire({
					title: 'Sending...',
					html: 
					'<div class="custom-spinner"></div><p>Please wait...</p>'
					,
					showConfirmButton: false,
					allowOutsideClick: false,
					allowEscapeKey: false
				});
				fetch('../auth/send_reset_link.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ email: email })
				})
				.then(res => res.json())
				.then(data => {
					if (data.status === 'success') {
						Swal.fire('Sent!', data.message, 'success');
					} else {
						Swal.fire('Failed', data.message, 'error');
					}
				})
				.catch(() => {
					Swal.fire('Error', 'Failed to send request.', 'error');
				});
			}
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