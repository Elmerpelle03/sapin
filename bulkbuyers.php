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

	<title>Wholesaler Applications</title>

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
			background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
			color: white;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);
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
		
		.stats-card.total { border-left-color: #f59e0b; }
		.stats-card.pending { border-left-color: #f59e0b; }
		.stats-card.approved { border-left-color: #10b981; }
		.stats-card.rejected { border-left-color: #ef4444; }
		
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
		<?php $active = 'bulk'; ?>
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">
					<!-- Page Header -->
					<div class="page-header">
						<h1>
							<i class="bi bi-briefcase me-2"></i>Wholesaler Applications
						</h1>
						<p class="mb-0 mt-2" style="opacity: 0.9;">Review and manage wholesale customer applications</p>
					</div>

					<?php
					// Fetch bulk buyer statistics - using actual status values
					$total_applications = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_applications")->fetchColumn();
					$pending_applications = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_applications WHERE status = 'Pending'")->fetchColumn();
					$approved_applications = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_applications WHERE status = 'Approved'")->fetchColumn();
					$declined_applications = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_applications WHERE status = 'Declined'")->fetchColumn();
					?>

					<!-- Statistics Cards -->
					<div class="row g-3 mb-4">
						<div class="col">
							<div class="stats-card total h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-file-earmark-text me-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
									<p class="stats-label mb-0">Total Applications</p>
								</div>
								<h2 class="stats-number" style="color: #f59e0b;"><?= $total_applications ?></h2>
								<small class="text-muted">All submissions</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card pending h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-clock-history text-warning me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Pending</p>
								</div>
								<h2 class="stats-number text-warning"><?= $pending_applications ?></h2>
								<small class="text-muted">Awaiting review</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card approved h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-check-circle-fill text-success me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Approved</p>
								</div>
								<h2 class="stats-number text-success"><?= $approved_applications ?></h2>
								<small class="text-muted">Accepted</small>
							</div>
						</div>
						<div class="col">
							<div class="stats-card rejected h-100">
								<div class="d-flex align-items-center mb-2">
									<i class="bi bi-x-circle-fill text-danger me-2" style="font-size: 1.5rem;"></i>
									<p class="stats-label mb-0">Declined</p>
								</div>
								<h2 class="stats-number text-danger"><?= $declined_applications ?></h2>
								<small class="text-muted">Rejected</small>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header d-flex justify-content-between align-items-center">
									<h5 class="card-title mb-0">Applications</h5>
									<div class="d-flex gap-2 flex-wrap">
										<button class="btn btn-light border app-filter active" data-status="all"><i class="bi bi-grid"></i> All</button>
										<button class="btn btn-light border app-filter" data-status="Pending"><i class="bi bi-hourglass-split"></i> Pending</button>
										<button class="btn btn-light border app-filter" data-status="Approved"><i class="bi bi-check-circle"></i> Approved</button>
										<button class="btn btn-light border app-filter" data-status="Declined"><i class="bi bi-x-circle"></i> Declined</button>
									</div>
								</div>
								<div class="card-body">
									<div class="table-responsive" style="width:100%">
										<table id="usersTable" class="display table">
											<thead>
												<tr>
													<th>ID</th>
													<th>Username</th>
													<th>Full Name</th>
													<th>Address</th>
													<th>Purpose</th>
													<th>ID Type</th>
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
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <div class="col-5 fw-bold">Purpose:</div>
                                <div class="col-7" id="modalPurpose"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">Submitted At:</div>
                                <div class="col-7" id="modalSubmittedAt"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">Status:</div>
                                <div class="col-7" id="modalStatus"></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 fw-bold">ID Type:</div>
                                <div class="col-7" id="modalIdType"></div>
                            </div>
                            <div class="row">
                                <div class="col-5 fw-bold">ID Picture:</div>
                                <div class="col-7">
                                    <img id="modalIdPicture" src="" alt="ID Picture" class="img-fluid rounded border" style="max-height: 200px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="modal-footer">
                            <form id="toggleUserStatusForm" method="POST" action="backend/toggle_application_status.php">
                                <input type="hidden" name="application_id" id="modalApplicationId">
                                <input type="hidden" name="current_status" id="modalApplicationCurrentStatus">
                                <input type="hidden" name="action" id="actionType">
                                <input type="hidden" name="user_id" id="modalUserId" value="">

                                <button type="button" id="toggleUserStatusBtn" class="btn btn-success">Approve</button>
                                <button type="button" id="declineUserBtn" class="btn btn-danger">Decline</button>
                                <button type="button" id="closeModalBtn" class="btn btn-secondary" data-bs-dismiss="modal" style="display: none;">Close</button>
                            </form>
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
	
	<script>
	    $(document).ready(function () {
        let statusFilter = 'all';
        const table = $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "backend/fetch_applications.php",
                data: function(d){ d.status = statusFilter; }
            },
            columns: [
                { data: "application_id" },
                { data: "username" },
                { data: "fullname" },
                { data: "address" },
                { data: "purpose" },
                { data: "id_type" },
                { data: "status" },
                { data: "action", className: "all", orderable: false, searchable: false }
            ]
        });

        // Filter buttons
        $(document).on('click', '.app-filter', function(){
            $('.app-filter').removeClass('active');
            $(this).addClass('active');
            statusFilter = $(this).data('status');
            table.ajax.reload();
        });

        $('#usersTable').on('click', '.view-user-btn', function () {
            const user = $(this).data('user');

            // Fill modal content
            $('#modalApplicationId').val(user.application_id);
            $('#modalApplicationCurrentStatus').val(user.status);
            $('#modalUsername').text(user.username);
            $('#modalEmail').text(user.email);
            $('#modalFullname').text(user.fullname);
            $('#modalContact').text(user.contact);
            $('#modalAddress').text(user.address);
            $('#modalSubmittedAt').text(user.submitted_at);
            $('#modalStatus').text(user.status);
            $('#modalIdType').text(user.id_type);
            $('#modalIdPicture').attr('src', user.id_image);
            $('#modalUserId').val(user.user_id);
			$('#modalPurpose').text(user.purpose);

            // Show/hide buttons
            if (user.status === 'Pending') {
                $('#toggleUserStatusBtn')
                    .text('Approve')
                    .removeClass('btn-danger')
                    .addClass('btn-success')
                    .show();
                $('#declineUserBtn').show();
                $('#closeModalBtn').hide();
            } else {
                $('#toggleUserStatusBtn').hide();
                $('#declineUserBtn').hide();
                $('#closeModalBtn').show();
            }

            const userModal = new bootstrap.Modal(document.getElementById('viewuserModal'));
            userModal.show();
        });

        // Approve button
        $('#toggleUserStatusBtn').on('click', function () {
            $('#actionType').val('approve');
            $('#toggleUserStatusForm').submit();
        });

        // Decline button
        $('#declineUserBtn').on('click', function () {
            $('#actionType').val('decline');
            $('#toggleUserStatusForm').submit();
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