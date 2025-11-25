<?php
require('../config/db.php');
require('../config/session.php');

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['usertype_id'] != 1 && $_SESSION['usertype_id'] != 5)) {
    header('Location: ../login.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// Handle verification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $notes = $_POST['verification_notes'] ?? '';
    $admin_id = $_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE orders SET 
            requires_verification = 0, 
            verification_notes = :notes,
            verified_by = :admin_id,
            verified_at = :now,
            status = 'Confirmed'
            WHERE order_id = :order_id");
        
        $stmt->execute([
            ':notes' => $notes,
            ':admin_id' => $admin_id,
            ':now' => $now,
            ':order_id' => $order_id
        ]);
        
        $_SESSION['success_message'] = 'Order approved successfully.';
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE orders SET 
            requires_verification = 0, 
            verification_notes = :notes,
            verified_by = :admin_id,
            verified_at = :now,
            status = 'Cancelled',
            cancel_reason = 'Invalid proof of payment'
            WHERE order_id = :order_id");
        
        $stmt->execute([
            ':notes' => $notes,
            ':admin_id' => $admin_id,
            ':now' => $now,
            ':order_id' => $order_id
        ]);
        
        // Restore stock
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id");
        $stmt->execute([':order_id' => $order_id]);
        $items = $stmt->fetchAll();
        
        foreach ($items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + :quantity WHERE product_id = :product_id");
            $stmt->execute([
                ':quantity' => $item['quantity'],
                ':product_id' => $item['product_id']
            ]);
        }
        
        $_SESSION['success_message'] = 'Order rejected and stock restored.';
    }
    
    header('Location: orders.php');
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("SELECT o.*, u.username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.user_id 
    WHERE o.order_id = :order_id");
$stmt->execute([':order_id' => $order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Parse metadata if exists
$metadata = null;
if (!empty($order['proof_metadata'])) {
    $metadata = json_decode($order['proof_metadata'], true);
}

// Check for red flags
$red_flags = [];

if ($metadata) {
    // Check if EXIF data is missing (possible screenshot or edited image)
    if (empty($metadata['exif_datetime']) || empty($metadata['exif_make'])) {
        $red_flags[] = 'Missing EXIF data - possible screenshot or edited image';
    }
    
    // Check timestamp difference
    if (!empty($metadata['exif_datetime']) && !empty($metadata['upload_time'])) {
        $exif_time = strtotime($metadata['exif_datetime']);
        $upload_time = strtotime($metadata['upload_time']);
        $order_time = strtotime($order['date']);
        
        $diff_hours = abs($exif_time - $order_time) / 3600;
        if ($diff_hours > 24) {
            $red_flags[] = 'Receipt timestamp is ' . round($diff_hours) . ' hours different from order time';
        }
    }
    
    // Check file size (screenshots are usually smaller)
    if (!empty($metadata['file_size']) && $metadata['file_size'] < 50000) {
        $red_flags[] = 'Very small file size (' . round($metadata['file_size']/1024) . 'KB) - possible screenshot';
    }
}

// Check user history
$stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, 
    SUM(CASE WHEN status = 'Cancelled' AND cancel_reason LIKE '%proof%' THEN 1 ELSE 0 END) as rejected_proofs
    FROM orders WHERE user_id = :user_id");
$stmt->execute([':user_id' => $order['user_id']]);
$user_history = $stmt->fetch();

if ($user_history['rejected_proofs'] > 0) {
    $red_flags[] = 'User has ' . $user_history['rejected_proofs'] . ' previous rejected proof(s)';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Proof of Payment - Order #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .proof-image {
            max-width: 100%;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .red-flag {
            background: #fee;
            border-left: 4px solid #dc3545;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 4px;
        }
        .metadata-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .metadata-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .metadata-item:last-child {
            border-bottom: none;
        }
        .badge-warning-custom {
            background: #ffc107;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-shield-check"></i> Verify Proof of Payment</h2>
            <a href="orders.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Orders</a>
        </div>

        <div class="row">
            <!-- Left Column: Order Info & Red Flags -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Order Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Order ID:</strong> #<?php echo $order['order_id']; ?></p>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['fullname']); ?> (<?php echo htmlspecialchars($order['username']); ?>)</p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('M d, Y h:i A', strtotime($order['date'])); ?></p>
                        <p><strong>Amount:</strong> ₱<?php echo number_format($order['amount'], 2); ?></p>
                        <p><strong>Payment Method:</strong> <span class="badge bg-info"><?php echo $order['payment_method']; ?></span></p>
                        <p><strong>Status:</strong> <span class="badge bg-warning"><?php echo $order['status']; ?></span></p>
                    </div>
                </div>

                <!-- User History -->
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-badge"></i> Customer History</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Total Orders:</strong> <?php echo $user_history['total_orders']; ?></p>
                        <p><strong>Rejected Proofs:</strong> 
                            <span class="badge <?php echo $user_history['rejected_proofs'] > 0 ? 'bg-danger' : 'bg-success'; ?>">
                                <?php echo $user_history['rejected_proofs']; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Red Flags -->
                <?php if (!empty($red_flags)): ?>
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Red Flags (<?php echo count($red_flags); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($red_flags as $flag): ?>
                            <div class="red-flag">
                                <i class="bi bi-exclamation-circle text-danger"></i> <?php echo $flag; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> No red flags detected. Proof appears legitimate.
                </div>
                <?php endif; ?>

                <!-- Metadata -->
                <?php if ($metadata): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Image Metadata</h5>
                    </div>
                    <div class="card-body">
                        <div class="metadata-card">
                            <?php if (isset($metadata['upload_time'])): ?>
                            <div class="metadata-item">
                                <span><strong>Upload Time:</strong></span>
                                <span><?php echo $metadata['upload_time']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($metadata['file_size'])): ?>
                            <div class="metadata-item">
                                <span><strong>File Size:</strong></span>
                                <span><?php echo round($metadata['file_size']/1024, 2); ?> KB</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($metadata['dimensions'])): ?>
                            <div class="metadata-item">
                                <span><strong>Dimensions:</strong></span>
                                <span><?php echo $metadata['dimensions']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($metadata['mime_type'])): ?>
                            <div class="metadata-item">
                                <span><strong>File Type:</strong></span>
                                <span><?php echo $metadata['mime_type']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($metadata['exif_datetime'])): ?>
                            <div class="metadata-item">
                                <span><strong>Photo Taken:</strong></span>
                                <span><?php echo $metadata['exif_datetime']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($metadata['exif_make']) && isset($metadata['exif_model'])): ?>
                            <div class="metadata-item">
                                <span><strong>Device:</strong></span>
                                <span><?php echo $metadata['exif_make'] . ' ' . $metadata['exif_model']; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($metadata['image_hash'])): ?>
                            <div class="metadata-item">
                                <span><strong>Image Hash:</strong></span>
                                <span><code><?php echo substr($metadata['image_hash'], 0, 16); ?>...</code></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Proof Image & Actions -->
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-image"></i> Proof of Payment</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if (!empty($order['proof_of_payment']) && file_exists($order['proof_of_payment'])): ?>
                            <img src="../<?php echo $order['proof_of_payment']; ?>" alt="Proof of Payment" class="proof-image">
                            <div class="mt-3">
                                <a href="../<?php echo $order['proof_of_payment']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrows-fullscreen"></i> View Full Size
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> No proof of payment uploaded
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Verification Actions -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Verification Decision</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><strong>Verification Notes:</strong></label>
                                <textarea name="verification_notes" class="form-control" rows="4" placeholder="Add notes about your verification decision..." required></textarea>
                                <small class="text-muted">Document why you approved or rejected this proof</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="action" value="approve" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Approve Order
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg" 
                                    onclick="return confirm('Are you sure you want to reject this order? Stock will be restored.')">
                                    <i class="bi bi-x-circle"></i> Reject Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Verification Checklist -->
                <div class="card mt-4">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="bi bi-list-check"></i> Verification Checklist</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check1">
                            <label class="form-check-label" for="check1">
                                Amount matches order total
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check2">
                            <label class="form-check-label" for="check2">
                                Date/time is reasonable
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check3">
                            <label class="form-check-label" for="check3">
                                Payment method matches
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check4">
                            <label class="form-check-label" for="check4">
                                Image appears authentic
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="check5">
                            <label class="form-check-label" for="check5">
                                No signs of editing
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
