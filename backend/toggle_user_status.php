<?php
require '../../config/session_admin.php';
require '../../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $currentStatus = $_POST['current_status'] ?? null;

    if (!$userId || !$currentStatus) {
        $_SESSION['error_message'] = "Invalid data.";
        header("Location: ../users.php");
        exit;
    }
    if ($currentStatus === 'Active') {
        $newStatusId = 2;
    } else {
        $newStatusId = 1;
    }

    $stmt = $pdo->prepare("UPDATE users SET accountstatus_id = :newStatusId WHERE user_id = :user_id");
    $stmt->execute([
            ':newStatusId' => $newStatusId,
            ':user_id' => $userId
        ]);

    $_SESSION['success_message'] = "User status updated successfully.";
    header("Location: ../users.php");
    exit;
}
?>
