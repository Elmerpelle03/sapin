<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$order_id = $_POST['order_id'] ?? null;
$reason = trim($_POST['reason'] ?? '');
$customer_refund_method = trim($_POST['customer_refund_method'] ?? '');
$customer_payment_details = trim($_POST['customer_payment_details'] ?? '');

if (!$order_id || empty($reason) || empty($customer_refund_method)) {
    echo json_encode(['success' => false, 'message' => 'Order ID, reason, and refund method are required']);
    exit;
}

// Require payment details only for non-cash refunds
if (strtolower($customer_refund_method) !== 'cash' && empty($customer_payment_details)) {
    echo json_encode(['success' => false, 'message' => 'Payment details are required for digital refunds']);
    exit;
}

try {
    // Verify order belongs to user and is delivered
    $verify_stmt = $pdo->prepare("
        SELECT order_id, amount, status 
        FROM orders 
        WHERE order_id = :order_id AND user_id = :user_id
    ");
    $verify_stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $_SESSION['user_id']
    ]);
    $order = $verify_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    if ($order['status'] !== 'Delivered') {
        echo json_encode(['success' => false, 'message' => 'Only delivered orders can be returned']);
        exit;
    }

    // Check if return request already exists
    $check_stmt = $pdo->prepare("
        SELECT return_id 
        FROM return_requests 
        WHERE order_id = :order_id
    ");
    $check_stmt->execute([':order_id' => $order_id]);
    
    if ($check_stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'A return request already exists for this order']);
        exit;
    }

    // Handle image uploads (required)
    $image_paths = [];
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'At least one image is required for return requests']);
        exit;
    }
    
    $upload_dir = '../uploads/returns/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0775, true);
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $max_files = 5;
    
    for ($i = 0; $i < min(count($_FILES['images']['name']), $max_files); $i++) {
        if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
            $file_type = $_FILES['images']['type'][$i];
            
            if (in_array($file_type, $allowed_types)) {
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $filename = uniqid('return_', true) . '.' . $ext;
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $filepath)) {
                    $image_paths[] = $filename;
                }
            }
        }
    }
    
    if (empty($image_paths)) {
        echo json_encode(['success' => false, 'message' => 'Please upload valid image files (JPG, PNG, WEBP)']);
        exit;
    }

    // Insert return request
    $insert_stmt = $pdo->prepare("
        INSERT INTO return_requests (order_id, user_id, reason, refund_amount, images, customer_refund_method, customer_payment_details) 
        VALUES (:order_id, :user_id, :reason, :refund_amount, :images, :customer_refund_method, :customer_payment_details)
    ");
    $insert_stmt->execute([
        ':order_id' => $order_id,
        ':user_id' => $_SESSION['user_id'],
        ':reason' => $reason,
        ':refund_amount' => $order['amount'],
        ':images' => !empty($image_paths) ? json_encode($image_paths) : null,
        ':customer_refund_method' => $customer_refund_method,
        ':customer_payment_details' => strtolower($customer_refund_method) === 'cash' ? 'Cash pickup - Admin to set details' : $customer_payment_details
    ]);

    $return_id = $pdo->lastInsertId();

    // Create notification for admins (usertype_id 1 and 5)
    $admin_stmt = $pdo->prepare("SELECT user_id FROM users WHERE usertype_id IN (1, 5)");
    $admin_stmt->execute();
    $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);

    $notif_title = "New Return Request";
    $notif_message = "Order #" . $order_id . " has a return/refund request. Amount: ₱" . number_format($order['amount'], 2);
    
    foreach ($admins as $admin) {
        $notif_insert = $pdo->prepare("
            INSERT INTO notifications (user_id, order_id, title, message, type, is_read) 
            VALUES (:user_id, :order_id, :title, :message, 'warning', 0)
        ");
        $notif_insert->execute([
            ':user_id' => $admin['user_id'],
            ':order_id' => $order_id,
            ':title' => $notif_title,
            ':message' => $notif_message
        ]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Return request submitted successfully. An admin will review your request.',
        'return_id' => $return_id
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
