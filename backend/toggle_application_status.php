<?php
require '../../config/db.php'; 
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $applicationId = $_POST['application_id'] ?? null;
    $currentStatus = $_POST['current_status'] ?? null;
    $action = $_POST['action'] ?? null;
    $userId = $_POST['user_id'] ?? null; // Must be passed from the modal

    if (!$applicationId || !$currentStatus || !$action || !$userId) {
        $_SESSION['error_message'] = "Invalid data.";
        header("Location: ../bulkbuyers.php");
        exit;
    }

    // Determine new status
    if ($action === 'approve') {
        $newStatus = 'Approved';
    } elseif ($action === 'decline') {
        $newStatus = 'Declined';
    } else {
        $_SESSION['error_message'] = "Invalid action.";
        header("Location: ../bulkbuyers.php");
        exit;
    }

    // Update the application status
    $stmt = $pdo->prepare("UPDATE bulk_buyer_applications SET status = :status WHERE application_id = :id");
    $stmt->execute([
        ':status' => $newStatus,
        ':id' => $applicationId
    ]);

    // If approved, update usertype_id in users table
    if ($newStatus === 'Approved') {
        $userUpdate = $pdo->prepare("UPDATE users SET usertype_id = 3 WHERE user_id = :user_id");
        $userUpdate->execute([':user_id' => $userId]);
    }

    $_SESSION['success_message'] = "Application has been $newStatus.";
    header("Location: ../bulkbuyers.php");
    exit;
}
?>
