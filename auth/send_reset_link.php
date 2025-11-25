<?php
require '../config/db.php';
session_start();
require '../config/mailer.php';

header('Content-Type: application/json'); // Set response type to JSON

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit();
    }

    try {
        // Check if the user exists in the database
        $stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $token_sent_at = date("Y-m-d H:i:s");

            // Insert or update token in the database
            $stmtInsert = $pdo->prepare("
                INSERT INTO forgot_password (user_id, fp_token, token_sent_at)
                VALUES (:user_id, :fp_token, :token_sent_at)
                ON DUPLICATE KEY UPDATE fp_token = :token_update, token_sent_at = :sent_at_update
            ");
            $stmtInsert->execute([
                'user_id' => $user['user_id'],
                'fp_token' => $token,
                'token_sent_at' => $token_sent_at,
                'token_update' => $token,
                'sent_at_update' => $token_sent_at
            ]);

            // Dynamically determine base URL (no hardcoded folder)
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            // SCRIPT_NAME example: /sapinbedsheets-main/auth/send_reset_link.php
            // We want the project base: /sapinbedsheets-main
            $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            $baseURL = $protocol . '://' . $host . $scriptDir;
            // Remove trailing '/auth' if present (we need project root)
            if (substr($baseURL, -5) === '/auth') {
                $baseURL = substr($baseURL, 0, -5);
            }

            // Reset password link
            $resetLink = $baseURL . "/reset_password.php?token=" . $token . "&email=" . urlencode($email);

            $subject = "Reset Your Password";
            $body = "
                <p>Hi {$user['username']},</p>
                <p>Click the link below to reset your password (expires in 1 hour):</p>
                <p><a href='$resetLink'>Reset Password</a></p>
                <p>If you did not request this, just ignore this email.</p>
            ";

            // Send reset email
            if (sendVerificationEmail($email, $user['username'], $subject, $body)) {
                echo json_encode(['status' => 'success', 'message' => 'A reset link was sent to your email.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Email sending failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No account found with that email.']);
        }
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Try again later.']);
    }
} else {
    // Handle invalid request method
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>
