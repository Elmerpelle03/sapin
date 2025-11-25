<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: auth/logout.php");
    exit();
}

// Check if user is verified
require_once('db.php');
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch();

// Allow access to verification and OTP endpoints for unverified users
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$allowedUnverified = [
    'verify_email.php',
    'auth/verify_otp.php',
    'auth/send_otp.php'
];
if (!$user || $user['is_verified'] != 1) {
    if (!in_array($currentScript, $allowedUnverified)) {
        header("Location: /verify_email.php");
        exit();
    }
}


?>