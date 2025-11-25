<?php
require('config/db.php');
session_start();

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (!$token || !$email) {
    $_SESSION['error_message'] = "Invalid reset link.";
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT fp.user_id, fp.token_sent_at, u.username 
                       FROM forgot_password fp 
                       JOIN users u ON fp.user_id = u.user_id 
                       WHERE u.email = :email AND fp.fp_token = :token");
$stmt->execute(['email' => $email, 'token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error_message'] = "Invalid or expired reset link.";
    header("Location: index.php");
    exit();
}

// Check if the token is expired (1 hour)
$sent_time = strtotime($user['token_sent_at']);
if (time() - $sent_time > 3600) {
    $_SESSION['error_message'] = "Reset link expired.";
    header("Location: index.php");
    exit();
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $new_password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Check required fields
    if (!$new_password || !$confirm_password) {
        $_SESSION['error_message'] = "All fields are required.";
    }
    // Validate password match
    elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
    }
    // Validate password strength
    else {
        $uppercase = preg_match('@[A-Z]@', $new_password);
        $lowercase = preg_match('@[a-z]@', $new_password);
        $number    = preg_match('@[0-9]@', $new_password);
        $specialChars = preg_match('@[^\w]@', $new_password);

        if (strlen($new_password) < 8 || !$uppercase || !$lowercase || !$number || !$specialChars) {
            $_SESSION['error_message'] = "Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_id = :user_id");
            $stmt->execute([
                'password' => $hashed_password,
                'user_id' => $user['user_id']
            ]);

            // Invalidate the token
            $stmt = $pdo->prepare("DELETE FROM forgot_password WHERE user_id = :user_id");
            $stmt->execute(['user_id' => $user['user_id']]);

            $_SESSION['success_message'] = "Password has been reset successfully.";
            header("Location: login.php");
            exit();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .container { max-width: 500px; }
        .card { border-radius: 10px; }
        .alert { border-radius: 10px; }
        .bg-primary { background-color: var(--bs-primary) !important; }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .card {
                margin: 10px;
                width: calc(100% - 20px) !important;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .container {
                padding: 20px 10px;
            }
            
            .min-vh-100 {
                min-height: auto;
            }
        }

        @media (max-width: 576px) {
            .card {
                margin: 5px;
                width: calc(100% - 10px) !important;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg w-100">
        <div class="card-header bg-primary text-white text-center">
            <h5 class="mb-0">Reset Your Password</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6" placeholder="Enter new password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6" placeholder="Re-enter new password">
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['error_message'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $_SESSION['error_message']; ?>'
    });
</script>
<?php unset($_SESSION['error_message']); endif; ?>
</body>
</html>
