<?php
require '../config/db.php';
require '../config/session_admin.php';
require '../config/encryption.php';

$supplier_id = $_GET['supplier_id'] ?? null;

if (!$supplier_id) {
    header('Location: suppliers.php');
    exit;
}

// Get supplier info
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_id = :supplier_id");
$stmt->execute(['supplier_id' => $supplier_id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    header('Location: suppliers.php');
    exit;
}

// Decrypt contact info (only owner sees this)
$mobile = $supplier['encrypted_mobile'] ? decryptContact($supplier['encrypted_mobile']) : null;
$email = $supplier['encrypted_email'] ? decryptContact($supplier['encrypted_email']) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Order to <?php echo htmlspecialchars($supplier['supplier_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-send me-2"></i>Send Order to <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Supplier Info -->
                        <div class="alert alert-info">
                            <h5>Supplier Information</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($supplier['supplier_name']); ?></p>
                            <?php if ($supplier['company_name']): ?>
                            <p class="mb-1"><strong>Company:</strong> <?php echo htmlspecialchars($supplier['company_name']); ?></p>
                            <?php endif; ?>
                            
                            <hr>
                            <p class="mb-1"><strong>Send via:</strong></p>
                            <?php if ($mobile && ($supplier['contact_type'] === 'mobile' || $supplier['contact_type'] === 'both')): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_method" id="sendViaMobile" value="mobile" checked>
                                <label class="form-check-label" for="sendViaMobile">
                                    <i class="bi bi-phone me-1"></i>Mobile: <?php echo htmlspecialchars($mobile); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($email && ($supplier['contact_type'] === 'email' || $supplier['contact_type'] === 'both')): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="send_method" id="sendViaEmail" value="email" <?php echo !$mobile ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sendViaEmail">
                                    <i class="bi bi-envelope me-1"></i>Email: <?php echo htmlspecialchars($email); ?>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Form -->
                        <form id="orderForm">
                            <input type="hidden" name="supplier_id" value="<?php echo $supplier_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Material to Order *</label>
                                <select class="form-select" name="material_id" id="materialSelect" required>
                                    <option value="">Select material...</option>
                                    <?php
                                    $materials = $pdo->query("SELECT material_id, material_name, stock, unit FROM materials ORDER BY material_name");
                                    while ($mat = $materials->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='{$mat['material_id']}' data-unit='{$mat['unit']}' data-stock='{$mat['stock']}'>";
                                        echo htmlspecialchars($mat['material_name']) . " (Current: {$mat['stock']} {$mat['unit']})";
                                        echo "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Quantity to Order *</label>
                                <input type="number" class="form-control" name="quantity" id="quantityInput" min="1" step="0.01" required>
                                <small class="text-muted" id="unitDisplay"></small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Message/Notes</label>
                                <textarea class="form-control" name="message" id="messageText" rows="4" placeholder="e.g., Please deliver by Friday..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Expected Delivery Date</label>
                                <input type="date" class="form-control" name="expected_delivery">
                            </div>

                            <div class="alert alert-warning">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Note:</strong> This will prepare the order message. You'll need to manually send it via SMS or email.
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-send me-2"></i>Prepare Order Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function() {
            // Update unit display when material selected
            $('#materialSelect').change(function() {
                const unit = $(this).find(':selected').data('unit');
                const stock = $(this).find(':selected').data('stock');
                $('#unitDisplay').text(`Unit: ${unit} | Current stock: ${stock} ${unit}`);
            });

            // Submit order form
            $('#orderForm').submit(function(e) {
                e.preventDefault();
                
                const formData = $(this).serializeArray();
                const sendMethod = $('input[name="send_method"]:checked').val();
                const materialName = $('#materialSelect option:selected').text();
                const quantity = $('#quantityInput').val();
                const message = $('#messageText').val();
                
                // Prepare order message
                let orderMessage = `Material Order Request\n\n`;
                orderMessage += `Material: ${materialName}\n`;
                orderMessage += `Quantity: ${quantity}\n`;
                if (message) {
                    orderMessage += `\nNotes: ${message}\n`;
                }
                orderMessage += `\nThank you!`;
                
                // Show the message to copy
                Swal.fire({
                    title: 'Order Message Ready',
                    html: `
                        <div class="text-start">
                            <p><strong>Send via:</strong> ${sendMethod === 'mobile' ? 'SMS' : 'Email'}</p>
                            <p><strong>To:</strong> ${sendMethod === 'mobile' ? '<?php echo $mobile; ?>' : '<?php echo $email; ?>'}</p>
                            <hr>
                            <p><strong>Message:</strong></p>
                            <textarea class="form-control" rows="8" id="copyMessage" readonly>${orderMessage}</textarea>
                            <button class="btn btn-sm btn-secondary mt-2" onclick="copyToClipboard()">
                                <i class="bi bi-clipboard"></i> Copy Message
                            </button>
                        </div>
                    `,
                    width: '600px',
                    showCancelButton: true,
                    confirmButtonText: 'Save Order Record',
                    cancelButtonText: 'Close'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Save order record
                        saveOrderRecord(formData, sendMethod);
                    }
                });
            });
        });

        function copyToClipboard() {
            const textarea = document.getElementById('copyMessage');
            textarea.select();
            document.execCommand('copy');
            
            Swal.fire({
                icon: 'success',
                title: 'Copied!',
                text: 'Message copied to clipboard',
                timer: 1500,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }

        function saveOrderRecord(formData, sendMethod) {
            formData.push({ name: 'send_method', value: sendMethod });
            
            $.ajax({
                url: 'backend/save_supplier_order.php',
                type: 'POST',
                data: $.param(formData),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Order Saved!',
                            text: 'Order record has been saved',
                            timer: 2000
                        }).then(() => {
                            window.location.href = 'suppliers.php';
                        });
                    }
                }
            });
        }
    </script>
</body>
</html>
