<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
    $active = 'returns';
    
    // Get highlight parameter from notification
    $highlight_order_id = $_GET['highlight'] ?? null;
    
    // Function to get color for refund method badges
    function getRefundMethodColor($method) {
        switch(strtolower($method)) {
            case 'gcash': return 'success';
            case 'bank transfer': return 'primary';
            case 'cash': return 'info';
            case 'other': return 'secondary';
            default: return 'dark';
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Return Requests - Sapin Bedsheets</title>

	<link href="css/app.css" rel="stylesheet">
	<link href="css/custom.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
	
	<style>
		body { background-color: #f7f9fc; }
		
		.page-header {
			background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			color: white;
			padding: 2rem;
			border-radius: 14px;
			margin-bottom: 2rem;
			box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
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
		
		.status-badge {
			padding: 6px 14px;
			border-radius: 20px;
			font-size: 0.8rem;
			font-weight: 600;
			letter-spacing: 0.3px;
		}
		
		.status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }
		.status-approved { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
		.status-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
		
		.return-card {
			background: white;
			border: none;
			border-radius: 14px;
			box-shadow: 0 2px 12px rgba(0,0,0,0.06);
			transition: all 0.3s ease;
			margin-bottom: 2rem;
			overflow: hidden;
		}
		
		.return-card:hover {
			transform: translateY(-2px);
			box-shadow: 0 8px 24px rgba(0,0,0,0.1);
		}
		
		.return-card-header {
			background: #f9fafb;
			padding: 1.5rem;
			border-bottom: 1px solid #e5e7eb;
		}
		
		.return-card .card-body {
			padding: 1.5rem;
		}
		
		@media (max-width: 768px) {
			.return-card-header {
				padding: 1rem;
			}
			.return-card .card-body {
				padding: 1rem;
			}
			.stats-card {
				margin-bottom: 1rem;
			}
		}
		
		.return-image {
			width: 100px;
			height: 100px;
			object-fit: cover;
			border-radius: 10px;
			margin-right: 10px;
			cursor: pointer;
			border: 2px solid #e5e7eb;
			transition: all 0.2s;
		}
		
		.return-image:hover {
			transform: scale(1.05);
			border-color: #667eea;
		}
		
		.info-box {
			background: #f0f9ff;
			border-left: 4px solid #667eea;
			padding: 1rem;
			border-radius: 8px;
			margin-bottom: 1rem;
			border: 1px solid #e0f2fe;
		}
		
		.status-completed {
			background: #dbeafe;
			color: #1e40af;
			border: 1px solid #93c5fd;
		}
		
		.nav-tabs .nav-link {
			border: none;
			color: #6b7280;
			font-weight: 600;
			padding: 0.75rem 1.5rem;
			transition: all 0.2s;
		}
		
		.nav-tabs .nav-link.active {
			background: white;
			color: #667eea;
			border-bottom: 3px solid #667eea;
		}
		
		.btn-action {
			border-radius: 8px;
			font-weight: 600;
			padding: 0.5rem 1rem;
			transition: all 0.2s;
		}
		
		.highlight-order {
			animation: highlightPulse 2s ease-in-out 3;
			border: 3px solid #fbbf24 !important;
			box-shadow: 0 0 20px rgba(251, 191, 36, 0.4) !important;
		}
		
		@keyframes highlightPulse {
			0%, 100% { box-shadow: 0 0 20px rgba(251, 191, 36, 0.4); }
			50% { box-shadow: 0 0 30px rgba(251, 191, 36, 0.6); }
		}
	</style>
</head>

<body>
	<div class="wrapper">
		<?php require ('../includes/sidebar_admin.php');?>

		<div class="main">
			<?php require ('../includes/navbar_admin.php');?>

			<main class="content">
				<div class="container-fluid p-0">
					<!-- Page Header -->
					<div class="page-header">
						<h1>
							<i class="bi bi-arrow-return-left me-2"></i>Return & Refund Management
						</h1>
						<p class="mb-0 mt-2" style="opacity: 0.9;">Manage customer return and refund requests efficiently</p>
					</div>

					<?php
					// Fetch all return requests first
					$stmt = $pdo->prepare("
						SELECT 
							rr.*,
							o.order_id,
							o.amount as order_amount,
							u.username,
							u.email,
							CONCAT(ud.firstname, ' ', ud.lastname) as customer_name
						FROM return_requests rr
						JOIN orders o ON rr.order_id = o.order_id
						JOIN users u ON rr.user_id = u.user_id
						LEFT JOIN userdetails ud ON u.user_id = ud.user_id
						ORDER BY rr.created_at DESC
					");
					$stmt->execute();
					$returns = $stmt->fetchAll(PDO::FETCH_ASSOC);
					
					// Calculate statistics (case-insensitive)
					$pending_count = 0;
					$approved_count = 0;
					$rejected_count = 0;
					$completed_count = 0;
					
					foreach ($returns as $return) {
						$status = strtolower(trim($return['return_status']));
						if ($status === 'pending') {
							$pending_count++;
						} elseif ($status === 'approved') {
							$approved_count++;
							$completed_count++;
						} elseif ($status === 'rejected') {
							$rejected_count++;
							$completed_count++;
						} elseif ($status === 'completed') {
							// Handle "Completed" status (treat as approved)
							$approved_count++;
							$completed_count++;
						}
					}
					?>

					<!-- Statistics Cards -->
					<div class="row mb-4">
						<div class="col-md-3">
							<div class="stats-card pending">
								<p class="stats-label">Pending Requests</p>
								<h2 class="stats-number text-warning"><?= $pending_count ?></h2>
								<small class="text-muted">Awaiting review</small>
							</div>
						</div>
						<div class="col-md-3">
							<div class="stats-card" style="border-left-color: #667eea;">
								<p class="stats-label">Completed</p>
								<h2 class="stats-number text-primary"><?= $completed_count ?></h2>
								<small class="text-muted">Processed requests</small>
							</div>
						</div>
						<div class="col-md-3">
							<div class="stats-card approved">
								<p class="stats-label">Approved</p>
								<h2 class="stats-number text-success"><?= $approved_count ?></h2>
								<small class="text-muted">Ready for refund</small>
							</div>
						</div>
						<div class="col-md-3">
							<div class="stats-card rejected">
								<p class="stats-label">Rejected</p>
								<h2 class="stats-number text-danger"><?= $rejected_count ?></h2>
								<small class="text-muted">Request denied</small>
							</div>
						</div>
					</div>

					<!-- Filter Tabs -->
					<ul class="nav nav-tabs mb-4" id="statusTabs" role="tablist">
						<li class="nav-item" role="presentation">
							<button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
								<i class="bi bi-list-ul me-1"></i>All Requests
							</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button">
								<i class="bi bi-clock-history me-1"></i>Pending
								<?php if ($pending_count > 0): ?>
									<span class="badge bg-warning text-dark ms-1"><?= $pending_count ?></span>
								<?php endif; ?>
							</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed" type="button">
								<i class="bi bi-check-circle me-1"></i>Completed
							</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="approved-tab" data-bs-toggle="tab" data-bs-target="#approved" type="button">
								<i class="bi bi-check-circle-fill me-1"></i>Approved
							</button>
						</li>
						<li class="nav-item" role="presentation">
							<button class="nav-link" id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected" type="button">
								<i class="bi bi-x-circle-fill me-1"></i>Rejected
							</button>
						</li>
					</ul>

					<div class="tab-content" id="statusTabsContent">
						<?php
						// Organize returns by status
						$statuses = ['all' => $returns, 'pending' => [], 'completed' => [], 'approved' => [], 'rejected' => []];
						foreach ($returns as $return) {
							$status_key = strtolower(trim($return['return_status']));
							
							// Handle "Completed" status as "Approved"
							if ($status_key === 'completed') {
								$statuses['approved'][] = $return;
								$statuses['completed'][] = $return;
							} elseif (isset($statuses[$status_key])) {
								$statuses[$status_key][] = $return;
								// Add to completed tab if approved or rejected
								if (in_array($status_key, ['approved', 'rejected'])) {
									$statuses['completed'][] = $return;
								}
							}
						}
						
						foreach (['all', 'pending', 'completed', 'approved', 'rejected'] as $status):
							$active = $status === 'all' ? 'show active' : '';
						?>
						<div class="tab-pane fade <?= $active ?>" id="<?= $status ?>" role="tabpanel">
							<?php if (empty($statuses[$status])): ?>
								<div class="alert alert-info">
									<i class="bi bi-info-circle me-2"></i>No <?= $status === 'all' ? '' : $status ?> return requests found.
								</div>
							<?php else: ?>
								<?php foreach ($statuses[$status] as $return): ?>
									<div class="return-card <?= ($highlight_order_id && $return['order_id'] == $highlight_order_id) ? 'highlight-order' : '' ?>" id="order-<?= $return['order_id'] ?>">
										<div class="return-card-header">
											<div class="d-flex justify-content-between align-items-center">
												<div>
													<h5 class="mb-1 fw-bold">
														<i class="bi bi-box-seam text-primary me-2"></i>Order #<?= $return['order_id'] ?>
													</h5>
													<p class="text-muted mb-0 small">
														<i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($return['customer_name'] ?? $return['username']) ?>
														<span class="mx-2">•</span>
														<i class="bi bi-envelope-fill me-1"></i><?= htmlspecialchars($return['email']) ?>
													</p>
												</div>
												<span class="status-badge status-<?= strtolower($return['return_status']) ?>">
													<i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i><?= $return['return_status'] ?>
												</span>
											</div>
										</div>
										<div class="card-body">
											<div class="row g-4">
												<div class="col-lg-8 col-md-12">
													<!-- Return Reason Section -->
													<div class="mb-4 p-3 border-start border-4 border-danger bg-light rounded">
														<div class="d-flex align-items-center mb-2">
															<i class="bi bi-exclamation-triangle-fill text-danger me-2" style="font-size:1.2rem;"></i>
															<strong>Return Reason</strong>
														</div>
														<p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($return['reason'])) ?></p>
													</div>
													
													<!-- Product Images Section -->
													<?php if ($return['images']): ?>
														<div class="mb-4">
															<div class="d-flex align-items-center mb-2">
																<i class="bi bi-images text-info me-2" style="font-size:1.2rem;"></i>
																<strong>Product Images</strong>
															</div>
															<div class="d-flex flex-wrap gap-2">
																<?php 
																	$images = json_decode($return['images'], true);
																	foreach ($images as $image):
																?>
																	<img src="../uploads/returns/<?= $image ?>" 
																		 class="return-image" 
																		 onclick="viewImage('../uploads/returns/<?= $image ?>')" 
																		 alt="Return image"
																		 title="Click to view full size">
																<?php endforeach; ?>
															</div>
														</div>
													<?php endif; ?>
													
													<div class="info-box">
														<div class="d-flex align-items-center mb-2">
															<i class="bi bi-wallet2-fill text-primary me-2" style="font-size:1.2rem;"></i>
															<strong>Refund Payment Details</strong>
														</div>
														<div class="mt-2">
															<span class="badge bg-primary mb-2"><?= htmlspecialchars($return['customer_refund_method'] ?? 'Not specified') ?></span>
															<p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($return['customer_payment_details'] ?? 'No details provided')) ?></p>
														</div>
													</div>
													
													<!-- Request Info -->
													<div class="row g-2">
														<div class="col-md-6">
															<div class="p-2 bg-light rounded">
																<small class="text-muted d-block"><i class="bi bi-calendar-event me-1"></i>Request Date</small>
																<strong class="text-dark"><?= date('M j, Y', strtotime($return['created_at'])) ?></strong>
																<small class="text-muted ms-1"><?= date('g:i A', strtotime($return['created_at'])) ?></small>
															</div>
														</div>
														<div class="col-md-6">
															<div class="p-2 bg-light rounded">
																<small class="text-muted d-block"><i class="bi bi-cash-coin me-1"></i>Refund Amount</small>
																<strong class="text-success" style="font-size:1.1rem;">₱<?= number_format($return['refund_amount'], 2) ?></strong>
															</div>
														</div>
													</div>
												</div>
												
												<div class="col-lg-4 col-md-12">
													<div class="d-flex flex-column gap-2">
														<?php if ($return['return_status'] === 'Pending'): ?>
															<button class="btn btn-success btn-action w-100" 
																	onclick="processReturn(<?= $return['return_id'] ?>, 'Approved')">
																<i class="bi bi-check-circle-fill me-2"></i>Approve Request
															</button>
															<button class="btn btn-danger btn-action w-100" 
																	onclick="processReturn(<?= $return['return_id'] ?>, 'Rejected')">
																<i class="bi bi-x-circle-fill me-2"></i>Reject Request
															</button>
														<?php elseif ($return['return_status'] === 'Approved' && !$return['refunded_at']): ?>
															<button class="btn btn-primary btn-action w-100" 
																	data-bs-toggle="modal" 
																	data-bs-target="#refundModal<?= $return['return_id'] ?>">
																<i class="bi bi-cash-coin me-2"></i>Process Refund
															</button>
														<?php else: ?>
															<div class="alert alert-light border mb-0">
																<div class="d-flex align-items-center">
																	<?php if ($return['refunded_at']): ?>
																		<i class="bi bi-check-circle-fill text-success me-2" style="font-size:1.5rem;"></i>
																		<div>
																			<strong class="d-block">Refunded</strong>
																			<small class="text-muted"><?= date('M j, Y', strtotime($return['refunded_at'])) ?></small>
																		</div>
																	<?php elseif ($return['processed_at']): ?>
																		<i class="bi bi-info-circle-fill text-info me-2" style="font-size:1.5rem;"></i>
																		<div>
																			<strong class="d-block">Processed</strong>
																			<small class="text-muted"><?= date('M j, Y', strtotime($return['processed_at'])) ?></small>
																		</div>
																	<?php endif; ?>
																</div>
															</div>
														<?php endif; ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
				</div>
			</main>
		</div>
	</div>

	<!-- Refund Payment Modals -->
	<?php foreach ($returns as $return): ?>
		<?php if ($return['return_status'] === 'Approved' && !$return['refunded_at']): ?>
		<div class="modal fade" id="refundModal<?= $return['return_id'] ?>" tabindex="-1" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header bg-primary text-white">
						<h5 class="modal-title">
							<i class="bi bi-cash-coin me-2"></i>Send Refund Payment
						</h5>
						<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
					</div>
					<form id="refundForm<?= $return['return_id'] ?>" enctype="multipart/form-data">
						<div class="modal-body">
							<input type="hidden" name="return_id" value="<?= $return['return_id'] ?>">
							
							<div class="alert alert-info">
								<strong>Order #<?= $return['order_id'] ?></strong><br>
								Customer: <?= htmlspecialchars($return['customer_name'] ?? $return['username']) ?><br>
								Refund Amount: <strong>₱<?= number_format($return['refund_amount'], 2) ?></strong>
							</div>
							
							<div class="alert alert-warning">
								<strong><i class="bi bi-wallet2 me-2"></i>Customer's Payment Details:</strong><br>
								<span class="badge bg-dark"><?= htmlspecialchars($return['customer_refund_method'] ?? 'Not specified') ?></span><br>
								<small class="mt-1 d-block"><?= nl2br(htmlspecialchars($return['customer_payment_details'] ?? 'No details provided')) ?></small>
							</div>
							
							<div class="mb-3">
								<label class="form-label"><strong>Refund Method</strong></label>
								<div class="form-control bg-light" style="border: 1px solid #dee2e6;">
									<span class="badge bg-<?= getRefundMethodColor($return['customer_refund_method'] ?? 'Not specified') ?> fs-6">
										<?= htmlspecialchars($return['customer_refund_method'] ?? 'Not specified') ?>
									</span>
								</div>
								<small class="text-muted">Customer's selected refund method (cannot be changed)</small>
							</div>
							
							<?php if (strtolower($return['customer_refund_method'] ?? '') === 'cash'): ?>
								<!-- Cash Refund Fields -->
								<div class="mb-3">
									<label class="form-label"><strong>Pickup Date & Time <span class="text-danger">*</span></strong></label>
									<input type="datetime-local" class="form-control" name="pickup_datetime" required>
									<small class="text-muted">Set when customer can pickup their cash refund</small>
								</div>
								
								<div class="mb-3">
									<label class="form-label"><strong>Pickup Location</strong></label>
									<textarea class="form-control" name="pickup_location" rows="2" placeholder="Store address or pickup location">Main Store - 140 Rose St., Brgy. Paciano Rizal, Bay, Laguna</textarea>
									<small class="text-muted">Where customer should pickup the refund</small>
								</div>
							<?php else: ?>
								<!-- Digital Refund Fields -->
								<div class="mb-3">
									<label class="form-label"><strong>Reference Number <span class="text-danger">*</span></strong></label>
									<input type="text" class="form-control" name="refund_reference" 
										   placeholder="Transaction/Reference number" required>
								</div>
								
								<div class="mb-3">
									<label class="form-label"><strong>Upload Proof of Payment</strong></label>
									<input type="file" class="form-control" name="refund_proof" accept="image/*">
									<small class="text-muted">Optional: Screenshot or photo of payment confirmation</small>
								</div>
							<?php endif; ?>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
							<button type="submit" class="btn btn-primary">
								<i class="bi bi-send me-1"></i>Confirm Refund Sent
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php endif; ?>
	<?php endforeach; ?>

	<script src="js/app.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	
	<script>
		function viewImage(src) {
			Swal.fire({
				imageUrl: src,
				imageAlt: 'Return image',
				showCloseButton: true,
				showConfirmButton: false,
				width: '80%'
			});
		}
		
		function processReturn(returnId, action) {
			Swal.fire({
				title: `${action} this return request?`,
				text: action === 'Approved' ? 'The customer will be notified of the approval.' : 'Please provide a reason for rejection.',
				icon: 'question',
				input: action === 'Rejected' ? 'textarea' : null,
				inputPlaceholder: action === 'Rejected' ? 'Enter rejection reason...' : null,
				showCancelButton: true,
				confirmButtonText: `Yes, ${action.toLowerCase()}`,
				cancelButtonText: 'Cancel',
				reverseButtons: true,
				preConfirm: (notes) => {
					if (action === 'Rejected' && !notes) {
						Swal.showValidationMessage('Please provide a reason for rejection');
					}
					return notes;
				}
			}).then((result) => {
				if (result.isConfirmed) {
					const formData = new FormData();
					formData.append('return_id', returnId);
					formData.append('action', action);
					if (result.value) {
						formData.append('admin_notes', result.value);
					}
					
					fetch('backend/process_return.php', {
						method: 'POST',
						body: formData
					})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							Swal.fire({
								icon: 'success',
								title: 'Success!',
								text: data.message
							}).then(() => {
								location.reload();
							});
						} else {
							Swal.fire({
								icon: 'error',
								title: 'Error',
								text: data.message
							});
						}
					})
					.catch(error => {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: 'An error occurred. Please try again.'
						});
						console.error('Error:', error);
					});
				}
			});
		}
		
		// Handle refund form submissions
		document.querySelectorAll('[id^="refundForm"]').forEach(form => {
			form.addEventListener('submit', function(e) {
				e.preventDefault();
				
				const formData = new FormData(this);
				const modalId = this.id.replace('refundForm', 'refundModal');
				
				Swal.fire({
					title: 'Sending Refund...',
					text: 'Please wait',
					allowOutsideClick: false,
					didOpen: () => {
						Swal.showLoading();
					}
				});
				
				fetch('backend/send_refund.php', {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						Swal.fire({
							icon: 'success',
							title: 'Refund Sent!',
							text: data.message
						}).then(() => {
							location.reload();
						});
					} else {
						Swal.fire({
							icon: 'error',
							title: 'Error',
							text: data.message
						});
					}
				})
				.catch(error => {
					Swal.fire({
						icon: 'error',
						title: 'Error',
						text: 'An error occurred. Please try again.'
					});
					console.error('Error:', error);
				});
			});
		});
		
		// Scroll to highlighted order from notification
		<?php if ($highlight_order_id): ?>
		document.addEventListener('DOMContentLoaded', function() {
			const highlightedOrder = document.getElementById('order-<?= $highlight_order_id ?>');
			if (highlightedOrder) {
				// Wait a bit for the page to fully load
				setTimeout(() => {
					highlightedOrder.scrollIntoView({ 
						behavior: 'smooth', 
						block: 'center' 
					});
				}, 500);
			}
		});
		<?php endif; ?>
	</script>
</body>
</html>
