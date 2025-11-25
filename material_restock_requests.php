<?php
require '../config/db.php';
require '../config/session_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Restock Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-approved { background-color: #0dcaf0; color: #000; }
        .status-ordered { background-color: #0d6efd; color: #fff; }
        .status-received { background-color: #198754; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0"><i class="bi bi-box-seam me-2"></i>Material Restock Requests</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="requestsTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Request ID</th>
                                        <th>Material</th>
                                        <th>Requested Qty</th>
                                        <th>Current Stock</th>
                                        <th>Reason</th>
                                        <th>Requested By</th>
                                        <th>Date</th>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            const table = $('#requestsTable').DataTable({
                ajax: {
                    url: 'backend/get_restock_requests.php',
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'request_id' },
                    { data: 'material_name' },
                    { data: null, render: function(data, type, row) {
                        return `${row.requested_quantity} ${row.unit}`;
                    }},
                    { data: null, render: function(data, type, row) {
                        return `${row.current_stock} ${row.unit}`;
                    }},
                    { data: 'reason' },
                    { data: 'requested_by' },
                    { data: 'requested_date' },
                    { data: null, render: function(data, type, row) {
                        return `<span class="status-badge status-${row.status}">${row.status.toUpperCase()}</span>`;
                    }},
                    { data: null, render: function(data, type, row) {
                        let buttons = '';
                        if (row.status === 'pending') {
                            buttons += `<button class="btn btn-sm btn-success approve-btn" data-id="${row.request_id}">Approve</button> `;
                            buttons += `<button class="btn btn-sm btn-danger reject-btn" data-id="${row.request_id}">Reject</button>`;
                        } else if (row.status === 'approved') {
                            buttons += `<button class="btn btn-sm btn-primary ordered-btn" data-id="${row.request_id}">Mark Ordered</button>`;
                        } else if (row.status === 'ordered') {
                            buttons += `<button class="btn btn-sm btn-success received-btn" data-id="${row.request_id}" data-material-id="${row.material_id}" data-quantity="${row.requested_quantity}">Mark Received</button>`;
                        }
                        return buttons;
                    }}
                ],
                order: [[0, 'desc']]
            });

            // Update status handlers
            $(document).on('click', '.approve-btn, .reject-btn, .ordered-btn', function() {
                const requestId = $(this).data('id');
                const action = $(this).hasClass('approve-btn') ? 'approved' : 
                               $(this).hasClass('reject-btn') ? 'rejected' : 'ordered';
                
                updateRequestStatus(requestId, action);
            });

            $(document).on('click', '.received-btn', function() {
                const requestId = $(this).data('id');
                const materialId = $(this).data('material-id');
                const quantity = $(this).data('quantity');
                
                Swal.fire({
                    title: 'Mark as Received',
                    html: `
                        <p>This will add <strong>${quantity}</strong> to the material stock.</p>
                        <p>Confirm receipt?</p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Received',
                    confirmButtonColor: '#198754'
                }).then((result) => {
                    if (result.isConfirmed) {
                        markAsReceived(requestId, materialId, quantity);
                    }
                });
            });

            function updateRequestStatus(requestId, status) {
                $.ajax({
                    url: 'backend/update_restock_status.php',
                    type: 'POST',
                    data: { request_id: requestId, status: status },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: `Request ${status}`,
                                timer: 2000
                            });
                            table.ajax.reload();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            }

            function markAsReceived(requestId, materialId, quantity) {
                $.ajax({
                    url: 'backend/mark_restock_received.php',
                    type: 'POST',
                    data: { 
                        request_id: requestId,
                        material_id: materialId,
                        quantity: quantity
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Material Restocked!',
                                html: `
                                    <p>${response.material_name}</p>
                                    <p>Added: <strong>${quantity}</strong></p>
                                    <p>New Stock: <strong>${response.new_stock}</strong></p>
                                `,
                                timer: 3000
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
