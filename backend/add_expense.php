<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Restrict to Super Admin only
if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
    echo 'Unauthorized access';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_category = $_POST['expense_category'] ?? '';
    $expense_name = $_POST['expense_name'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $expense_date = $_POST['expense_date'] ?? '';
    $description = $_POST['description'] ?? '';
    $receipt_path = null;

    // Validate required fields
    if (empty($expense_category) || empty($expense_name) || empty($amount) || empty($expense_date)) {
        echo 'Please fill in all required fields';
        exit;
    }

    // Handle file upload
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/receipts/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf', 'gif'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            // Check file size (5MB max)
            if ($_FILES['receipt']['size'] <= 5242880) {
                $new_filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_path)) {
                    $receipt_path = 'uploads/receipts/' . $new_filename;
                }
            } else {
                echo 'File size exceeds 5MB limit';
                exit;
            }
        } else {
            echo 'Invalid file type. Only JPG, PNG, PDF allowed';
            exit;
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO expenses (expense_category, expense_name, amount, expense_date, description, receipt_path, created_by) 
                               VALUES (:category, :name, :amount, :date, :description, :receipt, :created_by)");
        
        $stmt->execute([
            ':category' => $expense_category,
            ':name' => $expense_name,
            ':amount' => $amount,
            ':date' => $expense_date,
            ':description' => $description,
            ':receipt' => $receipt_path,
            ':created_by' => $_SESSION['user_id']
        ]);

        echo 'success';
    } catch (PDOException $e) {
        echo 'Database error: ' . $e->getMessage();
    }
} else {
    echo 'Invalid request method';
}
?>
