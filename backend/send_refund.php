<?php
session_start();
require '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype_id'], [1, 5])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$return_id = $_POST['return_id'] ?? null;
$refund_reference = trim($_POST['refund_reference'] ?? '');
$pickup_datetime = trim($_POST['pickup_datetime'] ?? '');
$pickup_location = trim($_POST['pickup_location'] ?? '');

if (!$return_id) {
    echo json_encode(['success' => false, 'message' => 'Return ID is required']);
    exit;
}

try {
    // Fetch return request details
    $stmt = $pdo->prepare("
        SELECT rr.*, o.user_id 
        FROM return_requests rr
        JOIN orders o ON rr.order_id = o.order_id
        WHERE rr.return_id = :return_id
    ");
    $stmt->execute([':return_id' => $return_id]);
    $return_request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$return_request) {
        echo json_encode(['success' => false, 'message' => 'Return request not found']);
        exit;
    }

    // Validate based on refund method
    if (strtolower($return_request['customer_refund_method']) === 'cash') {
        if (empty($pickup_datetime)) {
            echo json_encode(['success' => false, 'message' => 'Pickup date and time is required for cash refunds']);
            exit;
        }
    } else {
        if (empty($refund_reference)) {
            echo json_encode(['success' => false, 'message' => 'Reference number is required for digital refunds']);
            exit;
        }
    }

    if ($return_request['return_status'] !== 'Approved') {
        echo json_encode(['success' => false, 'message' => 'Return request must be approved first']);
        exit;
    }

    if ($return_request['refunded_at']) {
        echo json_encode(['success' => false, 'message' => 'Refund has already been sent']);
        exit;
    }

    // Handle proof of payment upload (only for digital refunds)
    $proof_filename = null;
    if (strtolower($return_request['customer_refund_method']) !== 'cash') {
        if (isset($_FILES['refund_proof']) && $_FILES['refund_proof']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/refunds/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
            $file_type = $_FILES['refund_proof']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $ext = pathinfo($_FILES['refund_proof']['name'], PATHINFO_EXTENSION);
                $proof_filename = uniqid('refund_proof_', true) . '.' . $ext;
                $filepath = $upload_dir . $proof_filename;
                
                if (!move_uploaded_file($_FILES['refund_proof']['tmp_name'], $filepath)) {
                    $proof_filename = null;
                }
            }
        }
    }

    // Prepare refund details based on method
    if (strtolower($return_request['customer_refund_method']) === 'cash') {
        $refund_details = json_encode([
            'pickup_datetime' => $pickup_datetime,
            'pickup_location' => $pickup_location ?: 'Main Store - 140 Rose St., Brgy. Paciano Rizal, Bay, Laguna'
        ]);
        $refund_reference = $pickup_datetime; // Use datetime as reference
    } else {
        $refund_details = null;
    }

    // Update return request with refund details
    $update_stmt = $pdo->prepare("
        UPDATE return_requests 
        SET refund_method = :method,
            refund_reference = :reference,
            refund_proof = :proof,
            refunded_at = NOW(),
            return_status = 'Completed',
            refund_details = :details
        WHERE return_id = :return_id
    ");
    $update_stmt->execute([
        ':method' => $return_request['customer_refund_method'],
        ':reference' => $refund_reference,
        ':proof' => $proof_filename,
        ':details' => $refund_details,
        ':return_id' => $return_id
    ]);

    // Create notification for customer
    $notif_title = "Refund Payment Sent";
    if (strtolower($return_request['customer_refund_method']) === 'cash') {
        $pickup_date = date('F j, Y - g:i A', strtotime($pickup_datetime));
        $notif_message = "Your cash refund of ₱" . number_format($return_request['refund_amount'], 2) . 
                         " for Order #" . $return_request['order_id'] . 
                         " is ready for pickup on " . $pickup_date . 
                         " at: " . ($pickup_location ?: 'Main Store - 140 Rose St., Brgy. Paciano Rizal, Bay, Laguna') . 
                         ". Please present this return/refund confirmation to SAPIN staff upon pickup.";
    } else {
        $notif_message = "Your refund of ₱" . number_format($return_request['refund_amount'], 2) . 
                         " for Order #" . $return_request['order_id'] . 
                         " has been sent via " . $return_request['customer_refund_method'] . 
                         ". Reference: " . $refund_reference;
    }
    
    $notif_stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, order_id, title, message, type, is_read) 
        VALUES (:user_id, :order_id, :title, :message, 'success', 0)
    ");
    $notif_stmt->execute([
        ':user_id' => $return_request['user_id'],
        ':order_id' => $return_request['order_id'],
        ':title' => $notif_title,
        ':message' => $notif_message
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Refund payment recorded successfully. Customer has been notified.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
