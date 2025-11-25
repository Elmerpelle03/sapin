<?php
require ('../../config/db.php');
session_start();

function redirectWithError($message) {
    $_SESSION['error_message'] = $message;
    header("Location: ../users.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usertype_id = $_POST['usertype'];
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_verified = false;
    $accountstatus_id = 1;
    $join_date = date('Y-m-d H:i:s');
    $email = $_POST['email'];
    $destination = $_POST['destination'];

    // Validate username length
    if (strlen($username) < 3) {
        redirectWithError("Username must be at least 3 characters long.");
    }

    // Validate password strength
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);

    if (strlen($password) < 8 || !$uppercase || !$lowercase || !$number || !$specialChars) {
        redirectWithError("Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.");
    }

    try {
        // Check username if exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->rowCount() > 0) {
            redirectWithError("Username already exists.");
        }

        // Check email if exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->rowCount() > 0) {
            redirectWithError("Email already exists.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (usertype_id, join_date, username, email, password, is_verified, accountstatus_id) 
                               VALUES (:usertype_id, :join_date, :username, :email, :password, :is_verified, :accountstatus_id)");
        $stmt->execute([
            'usertype_id' => $usertype_id,
            'join_date' => $join_date,
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'is_verified' => $is_verified,
            'accountstatus_id' => $accountstatus_id
        ]);

        $lastInsertId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO userdetails (user_id)
                                VALUES (:user_id)");
                                
        $stmt->execute([
            'user_id' => $lastInsertId
        ]);

        $_SESSION['success_message'] = "Staff account created!";
        header("Location: ../" . $destination);
        exit;

    } catch (PDOException $e) {
        redirectWithError("Registration failed: " . $e->getMessage());
    }
}
else {
    redirectWithError("Invalid request.");
}
