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

    <title>Supplier Request History</title>

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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        
        .card { border: none; border-radius: 14px; box-shadow: 0 6px 18px rgba(17, 24, 39, 0.06); }
        .card-header { background: #fff; border-bottom: 1px solid #eef2f7; border-top-left-radius: 14px; border-top-right-radius: 14px; }
        .card-title { font-weight: 600; color: #0f172a; }

        .status-badge {
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-delivered { background-color: #d1fae5; color: #065f46; }

        /* DataTable styling */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 0.5rem;
        }

        table.dataTable thead th {
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }

        table.dataTable tbody tr:hover {
            background-color: #f9fafb;
        }

        .btn-sm {
            border-radius: 8px;
            font-size: 0.813rem;
            padding: 0.375rem 0.75rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php $active = 'supplier_requests'; ?>
        <?php require ('../includes/sidebar_admin.php');?>

        <div class="main">
            <?php require ('../includes/navbar_admin.php');?>

            <main class="content">
                <div class="container-fluid p-0">

                    <!-- Page Header -->
                    <div class="page-header">
                        <h1><i class="bi bi-clock-history me-2"></i>Supplier Request History</h1>
                        <p class="mb-0 mt-2 opacity-90">Track and manage all material restock requests sent to suppliers</p>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Request History</h5>
                                </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="requestsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Material</th>
                                        <th>Quantity</th>
                                        <th>Current Stock</th>
                                        <th>Supplier Contact</th>
                                        <th>Via</th>
                                        <th>Requested By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
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

        </div>
    </div>

    <script src="js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.2.9/js/dataTables.responsive.min.js"></script>

    <script>
        $(document).ready(function() {
            const table = $('#requestsTable').DataTable({
                ajax: {
                    url: 'backend/get_supplier_requests.php',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'requested_date' },
                    { data: 'material_name' },
                    { data: 'requested_quantity' },
                    { data: 'current_stock' },
                    { data: 'supplier_contact' },
                    { data: null, render: function(data, type, row) {
                        return row.contact_type === 'mobile' ? 
                            '<i class="bi bi-phone"></i> Mobile' : 
                            '<i class="bi bi-envelope"></i> Email';
                    }},
                    { data: 'requested_by' },
                    { data: null, render: function(data, type, row) {
                        const label = (row.status === 'delivered') ? 'RECEIVED' : row.status.toUpperCase();
                        return `<span class="status-badge status-${row.status}">${label}</span>`;
                    }},
                    { data: null, render: function(data, type, row) {
                        let buttons = '';
                        if (row.status === 'pending') {
                            buttons += `<button class="btn btn-sm btn-success mark-sent-btn" data-id="${row.request_id}">Mark Sent</button> `;
                        } else if (row.status === 'sent') {
                            buttons += `<button class="btn btn-sm btn-primary mark-received-btn" data-id="${row.request_id}">Mark Received</button> `;
                        }
                        buttons += `<button class="btn btn-sm btn-info view-btn" data-id="${row.request_id}" data-message="${row.message || 'No message'}">View</button>`;
                        return buttons;
                    }}
                ],
                order: [[0, 'desc']]
            });

            // Mark as sent
            $(document).on('click', '.mark-sent-btn', function() {
                const requestId = $(this).data('id');
                updateStatus(requestId, 'sent');
            });

            // Mark as received with confirmation (internally uses status 'delivered')
            $(document).on('click', '.mark-received-btn', function() {
                const requestId = $(this).data('id');
                
                // Show confirmation dialog
                Swal.fire({
                    title: 'Confirm Receipt',
                    html: `
                        <div class="text-start">
                            <p class="mb-3">Are you sure the materials have been received?</p>
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle me-2"></i>
                                <small>The requested quantity will be automatically added to your Material Inventory.</small>
                            </div>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Materials Received',
                    confirmButtonColor: '#10b981',
                    cancelButtonText: 'Cancel',
                    cancelButtonColor: '#6b7280'
                }).then((result) => {
                    if (result.isConfirmed) {
                        updateStatus(requestId, 'delivered');
                    }
                });
            });

            // View details
            $(document).on('click', '.view-btn', function() {
                const message = $(this).data('message');
                Swal.fire({
                    title: 'Request Details',
                    text: message,
                    icon: 'info'
                });
            });

            function updateStatus(requestId, status) {
                $.ajax({
                    url: 'backend/update_supplier_request_status.php',
                    type: 'POST',
                    data: { request_id: requestId, status: status },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let message = `Status changed to ${status}`;
                            let html = message;
                            
                            // If material was updated (delivered/received status)
                            if (response.material_updated) {
                                html = `
                                    <div class="text-start">
                                        <p class="mb-2"><strong>✅ Material Received!</strong></p>
                                        <hr>
                                        <p class="mb-1"><strong>Material:</strong> ${response.material_name}</p>
                                        <p class="mb-1"><strong>Quantity Added:</strong> ${response.quantity_added} ${response.unit}</p>
                                        <p class="mb-1"><strong>New Stock:</strong> ${response.new_stock} ${response.unit}</p>
                                        <hr>
                                        <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i>Stock automatically updated in Material Inventory</p>
                                    </div>
                                `;
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                html: html,
                                timer: response.material_updated ? 4000 : 2000,
                                showConfirmButton: response.material_updated
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            }
        });
    </script>

</body>
</html>
