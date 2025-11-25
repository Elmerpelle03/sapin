<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Restrict to Super Admin only
if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
    echo 'Unauthorized access';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_id = $_POST['expense_id'] ?? '';

    if (empty($expense_id)) {
        echo 'Invalid expense ID';
        exit;
    }

    try {
        // Get receipt path before deleting
        $stmt = $pdo->prepare("SELECT receipt_path FROM expenses WHERE expense_id = :id");
        $stmt->execute([':id' => $expense_id]);
        $expense = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete the expense
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE expense_id = :id");
        $stmt->execute([':id' => $expense_id]);

        // Delete receipt file if exists
        if ($expense && $expense['receipt_path'] && file_exists('../../' . $expense['receipt_path'])) {
            unlink('../../' . $expense['receipt_path']);
        }

        echo 'success';
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    }
} else {
    echo 'Invalid request method';
}
?>
