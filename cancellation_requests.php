<?php
require('../config/session_admin.php');
require('../config/db.php');

$active = 'cancellations';

// Get all cancellation requests
$stmt = $pdo->query("
    SELECT 
        cr.*,
        o.amount as total_amount,
        o.status as order_status,
        o.fullname,
        o.contact_number as email
    FROM cancellation_requests cr
    JOIN orders o ON cr.order_id = o.order_id
    ORDER BY 
        CASE cr.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        cr.requested_at DESC
");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    <title>Cancellation Requests</title>

    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background-color: #f7f9fc; }
        
        .main {
            min-height: 100vh !important;
            height: auto !important;
        }
        
        .content {
            min-height: auto !important;
            height: auto !important;
            padding: 1.5rem !important;
        }
        
        .container-fluid {
            padding-bottom: 2rem;
            max-width: 100%;
        }
        
        .wrapper {
            min-height: 100vh;
        }
        
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
        
        .cancel-card {
            background: white;
            border: none;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .cancel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }
        
        .cancel-card-header {
            background: #f9fafb;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .cancel-card .card-body {
            padding: 1.5rem;
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
        
        .info-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fde68a;
        }

        .btn-action {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.5rem 1rem;
            transition: all 0.2s;
        }
        
        .btn-success { background: #10b981; border-color: #10b981; }
        .btn-success:hover { background: #059669; border-color: #059669; }
        
        .btn-danger { background: #ef4444; border-color: #ef4444; }
        .btn-danger:hover { background: #dc2626; border-color: #dc2626; }
        
        .nav-tabs .nav-link {
            border: none;
            color: #6b7280;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            transition: all 0.2s;
        }
        
        .nav-tabs .nav-link.active {
            background: white;
            color: #f59e0b;
            border-bottom: 3px solid #f59e0b;
        }
        
        /* Highlight card when linked to */
        :target .cancel-card {
            animation: highlight 2s ease;
        }
        
        @keyframes highlight {
            0% { box-shadow: 0 0 0 4px #fbbf24; }
            100% { box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        }
        
        @media (max-width: 768px) {
            .cancel-card-header {
                padding: 1rem;
            }
            .cancel-card .card-body {
                padding: 1rem;
            }
            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php require('../includes/sidebar_admin.php'); ?>
        
        <div class="main">
            <?php require('../includes/navbar_admin.php'); ?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1>
                            <i class="bi bi-x-circle me-2"></i>Cancellation Request Management
                        </h1>
                        <p class="mb-0 mt-2" style="opacity: 0.9;">Review and process order cancellation requests</p>
                    </div>

                    <?php
                    // Calculate statistics
                    $pending_count = count(array_filter($requests, fn($r) => strtolower($r['status']) === 'pending'));
                    $approved_count = count(array_filter($requests, fn($r) => strtolower($r['status']) === 'approved'));
                    $rejected_count = count(array_filter($requests, fn($r) => strtolower($r['status']) === 'rejected'));
                    ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-card pending">
                                <p class="stats-label">Pending Requests</p>
                                <h2 class="stats-number text-warning"><?= $pending_count ?></h2>
                                <small class="text-muted">Awaiting review</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-card approved">
                                <p class="stats-label">Approved</p>
                                <h2 class="stats-number text-success"><?= $approved_count ?></h2>
                                <small class="text-muted">Cancellation approved</small>
                            </div>
                        </div>
                        <div class="col-md-4">
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

                    <?php
                    // Organize requests by status
                    $statuses = ['all' => $requests, 'pending' => [], 'approved' => [], 'rejected' => []];
                    foreach ($requests as $request) {
                        $status_key = strtolower(trim($request['status']));
                        if (isset($statuses[$status_key])) {
                            $statuses[$status_key][] = $request;
                        }
                    }
                    ?>

                    <div class="tab-content" id="statusTabsContent">
                        <?php foreach (['all', 'pending', 'approved', 'rejected'] as $status):
                            $active = $status === 'all' ? 'show active' : '';
                        ?>
                            <div class="tab-pane fade <?= $active ?>" id="<?= $status ?>" role="tabpanel">
                                <?php if (empty($statuses[$status])): ?>
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>No <?= $status === 'all' ? '' : $status ?> cancellation requests found.
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($statuses[$status] as $request): ?>
                                        <div class="col-md-12" id="order-<?php echo $request['order_id']; ?>">
                                            <div class="cancel-card">
                                                <div class="cancel-card-header">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h5 class="mb-1 fw-bold">
                                                                <i class="bi bi-box-seam text-warning me-2"></i>Order #<?php echo $request['order_id']; ?>
                                                            </h5>
                                                            <p class="text-muted mb-0 small">
                                                                <i class="bi bi-person-fill me-1"></i><?php echo htmlspecialchars($request['fullname']); ?>
                                                                <span class="mx-2">•</span>
                                                                <i class="bi bi-envelope-fill me-1"></i><?php echo htmlspecialchars($request['email']); ?>
                                                            </p>
                                                        </div>
                                                        <span class="status-badge status-<?php echo strtolower($request['status']); ?>">
                                                            <i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i><?php echo ucfirst($request['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row g-4">
                                                        <div class="col-lg-8 col-md-12">
                                        <!-- Order Info -->
                                        <div class="row g-2 mb-4">
                                            <div class="col-md-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block"><i class="bi bi-calendar-event me-1"></i>Request Date</small>
                                                    <strong class="text-dark"><?php echo date('M j, Y', strtotime($request['requested_at'])); ?></strong>
                                                    <small class="text-muted ms-1"><?php echo date('g:i A', strtotime($request['requested_at'])); ?></small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="p-2 bg-light rounded">
                                                    <small class="text-muted d-block"><i class="bi bi-cash-coin me-1"></i>Order Amount</small>
                                                    <strong class="text-dark" style="font-size:1.1rem;">₱<?php echo number_format($request['total_amount'], 2); ?></strong>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cancellation Reason -->
                                        <div class="info-box">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="bi bi-exclamation-triangle-fill text-warning me-2" style="font-size:1.2rem;"></i>
                                                <strong>Cancellation Reason</strong>
                                            </div>
                                            <p class="mb-0 text-dark"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                                        </div>
                                        
                                        <?php if ($request['status'] !== 'pending'): ?>
                                            <!-- Admin Response -->
                                            <div class="p-3 border-start border-4 <?php echo $request['status'] === 'approved' ? 'border-success bg-light' : 'border-danger bg-light'; ?> rounded">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-<?php echo $request['status'] === 'approved' ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?> me-2" style="font-size:1.2rem;"></i>
                                                    <strong>Admin Response</strong>
                                                </div>
                                                <p class="mb-2 text-dark"><?php echo nl2br(htmlspecialchars($request['admin_response'])); ?></p>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>Responded: <?php echo date('M j, Y g:i A', strtotime($request['responded_at'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-lg-4 col-md-12">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <div class="d-flex flex-column gap-2">
                                                <button class="btn btn-success btn-action w-100" onclick="handleCancellation(<?php echo $request['cancellation_id']; ?>, 'approve')">
                                                    <i class="bi bi-check-circle-fill me-2"></i>Approve Cancellation
                                                </button>
                                                <button class="btn btn-danger btn-action w-100" onclick="handleCancellation(<?php echo $request['cancellation_id']; ?>, 'reject')">
                                                    <i class="bi bi-x-circle-fill me-2"></i>Reject Cancellation
                                                </button>
                                                <a href="view_order.php?order_id=<?php echo $request['order_id']; ?>" class="btn btn-outline-primary btn-action w-100">
                                                    <i class="bi bi-eye me-2"></i>View Order Details
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-light border">
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-<?php echo $request['status'] === 'approved' ? 'check-circle-fill text-success' : 'x-circle-fill text-danger'; ?> me-2" style="font-size:1.5rem;"></i>
                                                    <div>
                                                        <strong class="d-block"><?php echo ucfirst($request['status']); ?></strong>
                                                        <small class="text-muted"><?php echo date('M j, Y', strtotime($request['responded_at'])); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                                        <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function handleCancellation(cancellationId, action) {
            const actionText = action === 'approve' ? 'approve' : 'reject';
            const actionGerund = action === 'approve' ? 'approving' : 'rejecting';
            const actionTitle = action === 'approve' ? 'Approve Cancellation' : 'Reject Cancellation';
            
            Swal.fire({
                title: actionTitle,
                html: `
                    <p>Please provide a reason for ${actionGerund} this cancellation request:</p>
                    <textarea id="admin-response" class="form-control" rows="4" placeholder="Enter your reason here..."></textarea>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: actionText.charAt(0).toUpperCase() + actionText.slice(1),
                confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const response = document.getElementById('admin-response').value.trim();
                    if (!response) {
                        Swal.showValidationMessage('Please provide a reason');
                        return false;
                    }
                    return response;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('cancellation_id', cancellationId);
                    formData.append('action', action);
                    formData.append('admin_response', result.value);

                    fetch('backend/handle_cancellation.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message,
                                timer: 2000
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
                            text: 'Failed to process request'
                        });
                    });
                }
            });
        }
    </script>
    
    <script src="js/app.js"></script>
</body>
</html>
