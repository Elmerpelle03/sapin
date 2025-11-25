<?php
require ('../config/db.php');
session_start();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userInput = trim($_POST['user']);
    $password = trim($_POST['password']);
    $destination = $_POST['destination'];
    $des_id = isset($_POST['des_id']) ? $_POST['des_id'] : 0;

    if (empty($userInput) || empty($password)) {
        $_SESSION['error_message'] = "Username/email and password are required!";
        header("Location: ../login.php");
        exit();
    }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email LIMIT 1");
    $stmt->execute([':username' => $userInput, ':email' => $userInput]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ($user['accountstatus_id'] == 2) {
            $_SESSION['error_message'] = "Your account is disabled.";
            header("Location: ../login.php?user=".$userInput);
            exit();
        }
        if (password_verify($password, $user['password'])) {
            if ($user['is_verified'] != 1) {
                // Generate OTP and send email
                require_once('../config/mailer.php');
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_sent_at = date('Y-m-d H:i:s');
                $stmtOtp = $pdo->prepare("UPDATE users SET otp = :otp, otp_sent_at = :otp_sent_at WHERE user_id = :user_id");
                $stmtOtp->execute([
                    ':otp' => $otp,
                    ':otp_sent_at' => $otp_sent_at,
                    ':user_id' => $user['user_id']
                ]);
                $subject = "Welcome to Sapin Bedsheets - Email Verification OTP";
                $body = "<h2>Hello {$user['username']},</h2>"
                    . "<p>Welcome to <strong>Sapin Bedsheets</strong>! We're excited to have you join our community.</p>"
                    . "<p>To complete your registration, please use the One-Time Password (OTP) below to verify your email address:</p>"
                    . "<h1 style='letter-spacing: 5px;'>$otp</h1>"
                    . "<p>This OTP is valid for 5 minutes. For your security, do not share this code with anyone.</p>"
                    . "<p>If you did not request this registration, you may safely ignore this message.</p>"
                    . "<br><p>Best regards,</p><p><strong>Sapin Bedsheets</strong><br>Customer Support Team</p>";
                sendVerificationEmail($user['email'], $user['username'], $subject, $body);
                $_SESSION['error_message'] = "Complete your email verification to start shopping.";
                $_SESSION['user_id'] = $user['user_id']; // So verify_email.php can use it
                $_SESSION['otp_sent'] = true; // Track OTP sent after login
                header("Location: ../verify_email.php");
                exit();
            }
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['usertype_id'] = $user['usertype_id'];
            if($user['usertype_id'] == 1 || $user['usertype_id'] == 5){
                header("Location: ../admin/index.php"); 
                exit();
            }
            else if($user['usertype_id'] == 4){
                header("Location: ../courier/index.php"); 
                exit();
            }
            else{
                header("Location: ../".$destination."?id=".$des_id); 
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Incorrect password!";
            header("Location: ../login.php?user=".$userInput);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Username or email not found!";
        header("Location: ../login.php?user=".$userInput);
        exit();
    }
}
?>
