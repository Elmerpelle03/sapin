<?php
require '../config/db.php';
require '../config/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $purpose = trim($_POST['purpose']);
    $id_type = $_POST['id_type'];
    $other_id_type = isset($_POST['other_id_type']) ? trim($_POST['other_id_type']) : '';
    $image = $_FILES['image'];
    $image_name = '';
    $submitted_at = date('Y-m-d H:i:s');
    
    // If "Other" is selected, combine with the specified ID type
    if ($id_type === 'Other' && !empty($other_id_type)) {
        $id_type = 'Other: ' . $other_id_type;
    }

    // Guard: prevent duplicate or invalid applications
    try {
        // If already a wholesaler, do not allow applying again
        $u = $pdo->prepare("SELECT usertype_id FROM users WHERE user_id = :uid");
        $u->execute([':uid' => $user_id]);
        $usertypeId = (int)$u->fetchColumn();
        if ($usertypeId === 3) {
            $_SESSION['warning_message'] = "You are already a Wholesaler.";
            header('Location: ../index.php');
            exit;
        }

        // Check latest application status
        $chk = $pdo->prepare("SELECT status FROM bulk_buyer_applications WHERE user_id = :uid ORDER BY submitted_at DESC LIMIT 1");
        $chk->execute([':uid' => $user_id]);
        $latestStatus = $chk->fetchColumn();
        if ($latestStatus === 'Pending') {
            $_SESSION['warning_message'] = "You already have a pending application. Please wait for admin review.";
            header('Location: ../index.php');
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Unexpected error. Please try again.";
        header('Location: ../apply_bulkbuyer.php');
        exit;
    }

    // Validation
    if (empty($user_id) || empty($purpose) || empty($id_type)) {
        $_SESSION['error_message'] = "All fields are required!";
        header('Location: ../apply_bulkbuyer.php');
        exit;
    }
    
    // Additional validation for "Other" ID type
    if ($id_type === 'Other' && empty($other_id_type)) {
        $_SESSION['error_message'] = "Please specify the type of ID when selecting 'Other'.";
        header('Location: ../apply_bulkbuyer.php');
        exit;
    }

    // Handle image upload with validation
    if ($image['error'] === 0) {
        // Validate file size (max 5MB)
        $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($image['size'] > $max_file_size) {
            $_SESSION['error_message'] = "File size too large. Maximum 5MB allowed.";
            header('Location: ../apply_bulkbuyer.php');
            exit;
        }
        
        // Validate file extension
        $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed_extensions)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG and PNG are allowed.";
            header('Location: ../apply_bulkbuyer.php');
            exit;
        }
        
        // Validate file is actually an image
        $check = getimagesize($image['tmp_name']);
        if ($check === false) {
            $_SESSION['error_message'] = "File is not a valid image.";
            header('Location: ../apply_bulkbuyer.php');
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/ids/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $image_name = uniqid('id_', true) . '.' . $ext;
        $upload_path = $upload_dir . $image_name;

        if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
            $_SESSION['error_message'] = "ID upload failed. Please try again.";
            header('Location: ../apply_bulkbuyer.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Please upload a valid ID.";
        header('Location: ../apply_bulkbuyer.php');
        exit;
    }

    // Insert into wholesaler application table
    try {
        $stmt = $pdo->prepare("INSERT INTO bulk_buyer_applications (user_id, purpose, id_type, id_image, status, submitted_at) 
                                VALUES (:user_id, :purpose, :id_type, :id_image, 'Pending', :submitted_at)");

        $stmt->execute([
            ':user_id' => $user_id,
            ':purpose' => $purpose,
            ':id_type' => $id_type,
            ':id_image' => $image_name,
            ':submitted_at' => $submitted_at
        ]);

        $_SESSION['success_message'] = "Application submitted successfully.";
        header('Location: ../index.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Database error. Please try again.";
        header('Location: ../apply_bulkbuyer.php');
        exit;
    }
}
?>
