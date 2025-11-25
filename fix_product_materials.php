<?php
require '../config/db.php';
require '../config/session_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Product Materials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-tools me-2"></i>Fix Product-Material Links</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>What this does:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Finds all products without material links</li>
                                <li>Automatically links them based on the product's material field</li>
                                <li>Sets default quantity needed to 1.68 per unit</li>
                            </ul>
                        </div>

                        <div id="status" class="mb-3"></div>

                        <button id="checkBtn" class="btn btn-info me-2">
                            <i class="bi bi-search me-2"></i>Check Status
                        </button>
                        <button id="fixBtn" class="btn btn-success">
                            <i class="bi bi-wrench me-2"></i>Auto-Fix Links
                        </button>
                        <a href="products.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Products
                        </a>

                        <div id="results" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Check status
            $('#checkBtn').click(function() {
                $.ajax({
                    url: 'backend/check_product_materials_status.php',
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            let html = `
                                <div class="alert alert-primary">
                                    <h5>Current Status:</h5>
                                    <ul>
                                        <li><strong>Total Products:</strong> ${response.total_products}</li>
                                        <li><strong>Products with Materials:</strong> ${response.products_with_materials}</li>
                                        <li><strong>Products Missing Materials:</strong> ${response.products_missing_materials}</li>
                                    </ul>
                                </div>
                            `;
                            
                            if (response.products_missing_materials > 0) {
                                html += `
                                    <div class="alert alert-warning">
                                        <h6>Products Missing Material Links:</h6>
                                        <ul class="mb-0">
                                `;
                                response.missing_products.forEach(function(product) {
                                    html += `<li>${product.product_name} (Material: ${product.material})</li>`;
                                });
                                html += `</ul></div>`;
                            }
                            
                            $('#results').html(html);
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Could not check status', 'error');
                    }
                });
            });

            // Auto-fix
            $('#fixBtn').click(function() {
                Swal.fire({
                    title: 'Auto-Fix Product Materials?',
                    text: 'This will automatically link products to materials based on their material field.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Fix It!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Linking products to materials',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        $.ajax({
                            url: 'backend/auto_link_product_materials.php',
                            type: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                Swal.close();
                                
                                if (response.success) {
                                    let html = `
                                        <div class="alert alert-success">
                                            <h5><i class="bi bi-check-circle me-2"></i>${response.message}</h5>
                                            <ul>
                                                <li><strong>Successfully Linked:</strong> ${response.linked} products</li>
                                                <li><strong>Failed:</strong> ${response.failed} products</li>
                                                <li><strong>Total Processed:</strong> ${response.total_processed} products</li>
                                            </ul>
                                        </div>
                                    `;
                                    
                                    if (response.failed > 0) {
                                        html += `
                                            <div class="alert alert-warning">
                                                <h6>Failed Products (No Matching Material):</h6>
                                                <ul class="mb-0">
                                        `;
                                        response.failed_products.forEach(function(product) {
                                            html += `<li>${product.product_name} - Material: "${product.material}" (${product.reason})</li>`;
                                        });
                                        html += `</ul></div>`;
                                    }
                                    
                                    $('#results').html(html);
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: `Linked ${response.linked} products successfully!`,
                                        timer: 3000
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function() {
                                Swal.close();
                                Swal.fire('Error', 'Could not complete auto-fix', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
