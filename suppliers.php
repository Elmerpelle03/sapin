<?php
require '../config/db.php';
require '../config/session_admin.php';
require '../config/encryption.php';

// Check if owner (you can add owner role check here)
// For now, any admin can access
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-people me-2"></i>Supplier Management</h4>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                            <i class="bi bi-plus-circle me-2"></i>Add Supplier
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-shield-lock me-2"></i>
                            <strong>Privacy Protected:</strong> Supplier contact information is encrypted and only visible to you.
                        </div>

                        <div id="suppliersList" class="row">
                            <!-- Suppliers will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal fade" id="addSupplierModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addSupplierForm">
                        <div class="mb-3">
                            <label class="form-label">Supplier Name *</label>
                            <input type="text" class="form-control" name="supplier_name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" class="form-control" name="company_name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Method *</label>
                            <select class="form-select" name="contact_type" id="contactType" required>
                                <option value="">Select...</option>
                                <option value="mobile">Mobile Number Only</option>
                                <option value="email">Email Only</option>
                                <option value="both">Both Mobile & Email</option>
                            </select>
                        </div>

                        <div class="mb-3" id="mobileField" style="display:none;">
                            <label class="form-label">Mobile Number</label>
                            <input type="tel" class="form-control" name="mobile" placeholder="09XXXXXXXXX">
                            <small class="text-muted">This will be encrypted and hidden from developers</small>
                        </div>

                        <div class="mb-3" id="emailField" style="display:none;">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" name="email" placeholder="supplier@example.com">
                            <small class="text-muted">This will be encrypted and hidden from developers</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveSupplierBtn">Save Supplier</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            loadSuppliers();

            // Show/hide contact fields based on selection
            $('#contactType').change(function() {
                const type = $(this).val();
                $('#mobileField, #emailField').hide();
                
                if (type === 'mobile' || type === 'both') {
                    $('#mobileField').show();
                    $('input[name="mobile"]').prop('required', true);
                } else {
                    $('input[name="mobile"]').prop('required', false);
                }
                
                if (type === 'email' || type === 'both') {
                    $('#emailField').show();
                    $('input[name="email"]').prop('required', true);
                } else {
                    $('input[name="email"]').prop('required', false);
                }
            });

            // Save supplier
            $('#saveSupplierBtn').click(function() {
                const formData = $('#addSupplierForm').serialize();
                
                $.ajax({
                    url: 'backend/add_supplier.php',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Supplier Added!',
                                text: response.message,
                                timer: 2000
                            });
                            $('#addSupplierModal').modal('hide');
                            $('#addSupplierForm')[0].reset();
                            loadSuppliers();
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                });
            });
        });

        function loadSuppliers() {
            $.ajax({
                url: 'backend/get_suppliers.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displaySuppliers(response.suppliers);
                    }
                }
            });
        }

        function displaySuppliers(suppliers) {
            let html = '';
            
            if (suppliers.length === 0) {
                html = '<div class="col-12"><p class="text-muted">No suppliers added yet.</p></div>';
            } else {
                suppliers.forEach(supplier => {
                    const contactBadge = supplier.contact_type === 'both' ? 
                        '<span class="badge bg-success">Mobile & Email</span>' :
                        supplier.contact_type === 'mobile' ?
                        '<span class="badge bg-info">Mobile Only</span>' :
                        '<span class="badge bg-warning">Email Only</span>';
                    
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">${supplier.supplier_name}</h5>
                                    ${supplier.company_name ? `<p class="text-muted mb-2">${supplier.company_name}</p>` : ''}
                                    ${contactBadge}
                                    <hr>
                                    <button class="btn btn-sm btn-primary send-order-btn" 
                                            data-supplier-id="${supplier.supplier_id}"
                                            data-supplier-name="${supplier.supplier_name}"
                                            data-contact-type="${supplier.contact_type}">
                                        <i class="bi bi-send me-1"></i>Send Order
                                    </button>
                                    <button class="btn btn-sm btn-secondary link-material-btn"
                                            data-supplier-id="${supplier.supplier_id}">
                                        <i class="bi bi-link me-1"></i>Link Materials
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }
            
            $('#suppliersList').html(html);
        }

        // Send order to supplier
        $(document).on('click', '.send-order-btn', function() {
            const supplierId = $(this).data('supplier-id');
            const supplierName = $(this).data('supplier-name');
            const contactType = $(this).data('contact-type');
            
            // Show material selection and order form
            window.location.href = `send_supplier_order.php?supplier_id=${supplierId}`;
        });
    </script>
</body>
</html>
