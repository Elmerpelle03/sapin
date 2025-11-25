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
$action = $_POST['action'] ?? null;
$admin_notes = trim($_POST['admin_notes'] ?? '');

if (!$return_id || !in_array($action, ['Approved', 'Rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Fetch return request details
    $stmt = $pdo->prepare("
        SELECT rr.*, o.user_id, o.amount 
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

    if ($return_request['return_status'] !== 'Pending') {
        echo json_encode(['success' => false, 'message' => 'This request has already been processed']);
        exit;
    }

    // Update return request
    $update_stmt = $pdo->prepare("
        UPDATE return_requests 
        SET return_status = :status,
            admin_notes = :notes,
            processed_by = :admin_id,
            processed_at = NOW()
        WHERE return_id = :return_id
    ");
    $update_stmt->execute([
        ':status' => $action,
        ':notes' => $admin_notes,
        ':admin_id' => $_SESSION['user_id'],
        ':return_id' => $return_id
    ]);

    // Create notification for customer
    $notif_title = $action === 'Approved' ? 
        "Return Request Approved" : 
        "Return Request Rejected";
    
    $notif_message = $action === 'Approved' ?
        "Your return request for Order #" . $return_request['order_id'] . " has been approved. Refund amount: ₱" . number_format($return_request['refund_amount'], 2) . 
        (strtolower($return_request['customer_refund_method']) === 'cash' ? 
            " Please present this return/refund confirmation to SAPIN staff when picking up your cash refund." : "") :
        "Your return request for Order #" . $return_request['order_id'] . " has been rejected." . ($admin_notes ? " Reason: " . $admin_notes : "");
    
    $notif_type = $action === 'Approved' ? 'success' : 'warning';
    
    $notif_stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, order_id, title, message, type, is_read) 
        VALUES (:user_id, :order_id, :title, :message, :type, 0)
    ");
    $notif_stmt->execute([
        ':user_id' => $return_request['user_id'],
        ':order_id' => $return_request['order_id'],
        ':title' => $notif_title,
        ':message' => $notif_message,
        ':type' => $notif_type
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Return request has been {$action}. Customer has been notified."
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
