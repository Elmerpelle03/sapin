<?php
require '../config/db.php';
require '../config/session_admin.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Blockout Links</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">🔧 Fix Blockout Material Links</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Problem:</strong> Blockout products are linked to US Katrina material instead of Blockout material.
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>This will:</strong>
                            <ul class="mb-0">
                                <li>Find all products with "Blockout" in material field</li>
                                <li>Change their link from US Katrina → Blockout</li>
                                <li>Fix the "Insufficient materials" error</li>
                            </ul>
                        </div>

                        <button id="fixBtn" class="btn btn-danger btn-lg w-100">
                            🔧 Fix Blockout Links Now
                        </button>
                        
                        <a href="products.php" class="btn btn-secondary w-100 mt-2">
                            ← Back to Products
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
            $('#fixBtn').click(function() {
                Swal.fire({
                    title: 'Fixing...',
                    text: 'Updating Blockout product links',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: 'fix_blockout_links.php',
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            let html = `
                                <div class="alert alert-success">
                                    <h5>✅ ${response.message}</h5>
                                    <p><strong>Blockout Material ID:</strong> ${response.blockout_id}</p>
                                    <p><strong>Material Name:</strong> ${response.blockout_material}</p>
                                    <p><strong>Products Fixed:</strong> ${response.fixed_count}</p>
                                </div>
                            `;
                            
                            if (response.fixed_products && response.fixed_products.length > 0) {
                                html += `
                                    <div class="alert alert-info">
                                        <h6>Fixed Products:</h6>
                                        <ul class="mb-0">
                                `;
                                response.fixed_products.forEach(function(name) {
                                    html += `<li>${name}</li>`;
                                });
                                html += `</ul></div>`;
                            }
                            
                            $('#results').html(html);
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Fixed!',
                                text: `${response.fixed_count} Blockout products updated!`,
                                timer: 3000
                            });
                        } else {
                            $('#results').html(`
                                <div class="alert alert-danger">
                                    <strong>Error:</strong> ${response.message}
                                </div>
                            `);
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not connect to server'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
