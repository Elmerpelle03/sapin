<?php
require '../config/db.php';
session_start();
ini_set('display_errors', 0);   // Don't show errors to users
ini_set('log_errors', 1);       // Log errors to PHP error log
error_reporting(E_ALL);         // Report all errors
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

// Check if the OTP is provided in the form
if (!isset($_POST['otp']) || empty($_POST['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'OTP is required']);
    exit;
}

// Validate OTP format (6 digits only)
$otpEntered = trim($_POST['otp']);
if (!preg_match('/^\d{6}$/', $otpEntered)) {
    echo json_encode(['status' => 'error', 'message' => 'OTP must be exactly 6 digits']);
    exit;
}

// Fetch the user and their OTP from the database
$stmt = $pdo->prepare("SELECT otp, otp_sent_at FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

// Check if OTP exists
if (empty($user['otp'])) {
    echo json_encode(['status' => 'error', 'message' => 'No OTP found. Please request a new one']);
    exit;
}

// Check if the OTP has expired (5 minutes expiry)
$otpTimestamp = strtotime($user['otp_sent_at']);
$expiryTime = 5 * 60; // 5 minutes
$currentTime = time();

if ($currentTime - $otpTimestamp > $expiryTime) {
    // Clear expired OTP
    $stmt = $pdo->prepare("UPDATE users SET otp = null, otp_sent_at = null WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    echo json_encode(['status' => 'error', 'message' => 'OTP has expired. Please request a new one']);
    exit;
}

// Check if the OTP matches
if ($user['otp'] !== $otpEntered) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid OTP. Please check and try again']);
    exit;
}

// OTP is valid - verify the user
$stmt = $pdo->prepare("UPDATE users SET otp = null, otp_sent_at = null, is_verified = true WHERE user_id = :user_id");
$stmt->execute([':user_id' => $_SESSION['user_id']]);

// Clear the just_registered session flag
unset($_SESSION['just_registered']);
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

echo json_encode(['status' => 'success', 'message' => 'Email verified successfully! Welcome to Sapin Bedsheets']);
?>
