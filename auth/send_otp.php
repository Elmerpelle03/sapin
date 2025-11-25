<?php
require '../config/db.php';
session_start();
ini_set('display_errors', 0);   // Don't show errors to users
ini_set('log_errors', 1);       // Log errors to PHP error log
error_reporting(E_ALL);         // Report all errors
require '../config/mailer.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Simple rate limiting: Check if last OTP was sent less than 60 seconds ago
$stmt = $pdo->prepare("SELECT username, email, otp_sent_at FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// Rate limiting check
if ($user['otp_sent_at']) {
    $last_sent = new DateTime($user['otp_sent_at']);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $last_sent->getTimestamp();
    
    if ($diff < 60) { // 60 seconds cooldown
        $remaining = 60 - $diff;
        echo json_encode(['status' => 'error', 'message' => "Please wait {$remaining} seconds before requesting another OTP"]);
        exit;
    }
}

// Generate secure OTP
$otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
$otp_sent_at = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("UPDATE users SET otp = :otp, otp_sent_at = :otp_sent_at WHERE user_id = :user_id");
$stmt->execute([':otp' => $otp, ':user_id' => $_SESSION['user_id'], ':otp_sent_at' => $otp_sent_at]);

$subject = "Email Verification - One-Time Password (OTP)";

$body = "
    <h2>Hello {$user['username']},</h2>
    <p>Thank you for registering with us. To complete your registration, please use the One-Time Password (OTP) provided below to verify your email address.</p>
    <h1 style='letter-spacing: 5px;'>$otp</h1>
    <p>This OTP is valid for 5 minutes. For your security, do not share this code with anyone.</p>
    <p>If you did not request this registration, you may safely ignore this message.</p>
    <br>
    <p>Warm regards,</p>
    <p><strong>Sapin Bedsheets</strong><br>
    Support Team</p>
";

// Send email
if (sendVerificationEmail($user['email'], $user['username'], $subject, $body)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email']);
}
