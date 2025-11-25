<?php
require('config/db.php');
require('config/session.php');
require ('config/details_checker.php');


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = "User not found.";
    header("Location: edit_profile.php");
    exit();
}

if(isset($_SESSION['error_message'])){
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = $_POST['new_email'];
    $confirm_email = $_POST['confirm_email'];

    // Check if both fields are filled
    if (empty($new_email) || empty($confirm_email)) {
        $_SESSION['error_message'] = "Both email fields are required.";
    }
    // Check if emails match
    elseif ($new_email !== $confirm_email) {
        $_SESSION['error_message'] = "Email addresses do not match.";
    }
    // Validate email format
    elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
    }
    // Check if the new email is the same as the current one
    elseif ($new_email === $user['email']) {
        $_SESSION['error_message'] = "The new email is the same as your current email.";
    }
    else {
        // Update email
        $update = $pdo->prepare("UPDATE users SET email = :email, is_verified = 0 WHERE user_id = :user_id");
        $update->execute([
            'email' => $new_email,
            'user_id' => $user_id
        ]);
        $_SESSION['success_message'] = "Email changed successfully. Please verify your new email address through your profile.";
        header("Location: index.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Change Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Desktop: Keep the form contained */
        .container {
            max-width: 500px;
        }
        
        .card, .alert {
            border-radius: 10px;
        }
        .bg-primary {
            background-color: var(--bs-primary) !important;
        }
        .btn-outline-primary {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }

        /* Tablet Responsiveness */
        @media (max-width: 768px) {
            .container {
                max-width: 90% !important;
                padding: 20px !important;
                margin: 0 auto !important;
            }
            
            .card {
                margin: 0 !important;
                width: 100% !important;
                border-radius: 15px !important;
            }
            
            .card-body {
                padding: 1.5rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px !important;
                min-height: 45px !important;
                padding: 12px 15px !important;
            }
            
            .form-label {
                font-size: 14px !important;
                font-weight: 600 !important;
                margin-bottom: 8px !important;
            }
            
            .btn {
                min-height: 45px !important;
                font-size: 16px !important;
                font-weight: 500 !important;
            }
        }

        /* Mobile Phones */
        @media (max-width: 576px) {
            .container {
                max-width: 95% !important;
                width: 95% !important;
                padding: 15px !important;
                margin: 0 auto !important;
            }
            
            .min-vh-100 {
                padding: 15px 0 !important;
            }
            
            .card {
                margin: 0 !important;
                width: 100% !important;
                border-radius: 12px !important;
            }
            
            .card-body {
                padding: 1.5rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px !important;
                min-height: 50px !important;
                padding: 15px !important;
                border-radius: 8px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            .form-label {
                font-size: 15px !important;
                font-weight: 600 !important;
                margin-bottom: 10px !important;
            }
            
            .btn {
                width: 100% !important;
                margin-bottom: 10px !important;
                min-height: 50px !important;
                font-size: 16px !important;
                border-radius: 8px !important;
            }
            
            .btn:last-child {
                margin-bottom: 0 !important;
            }
            
            .card-header {
                padding: 1rem 1.5rem !important;
            }
            
            .card-header h5 {
                font-size: 1.2rem !important;
                margin-bottom: 0 !important;
            }
            
            .mb-3 {
                margin-bottom: 1.5rem !important;
            }
        }

        /* Extra small phones */
        @media (max-width: 375px) {
            .container {
                max-width: 98% !important;
                width: 98% !important;
                padding: 10px !important;
                margin: 0 auto !important;
            }
            
            .card-body {
                padding: 1.25rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px !important;
                min-height: 52px !important;
                padding: 16px 12px !important;
            }
            
            .btn {
                min-height: 52px !important;
                padding: 16px 12px !important;
            }
        }
    </style>
</head>
<body>
<div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg w-100">
        <div class="card-header bg-primary text-white text-center">
            <h5 class="mb-0">Change Email</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Current Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Email</label>
                    <input type="email" name="new_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Email</label>
                    <input type="email" name="confirm_email" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Update Email</button>
                </div>
                <div class="text-center mt-3">
                    <a href="edit_profile.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to Edit Profile</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS + SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['error_message'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $_SESSION['error_message']; ?>'
    }).then(() => {
        <?php unset($_SESSION['error_message']); ?>
    });
</script>
<?php endif; ?>
</body>
</html>
