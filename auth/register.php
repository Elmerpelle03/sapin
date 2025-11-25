<?php
require ('../config/db.php');
require ('../config/mailer.php'); // Add mailer for automatic email sending
session_start();

function redirectWithError($message) {
    $_SESSION['error_message'] = $message;
    header("Location: ../register.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Quick debug - let's see what's happening
    file_put_contents('../debug_simple.log', "POST received at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents('../debug_simple.log', "Password: " . $_POST['password'] . "\n", FILE_APPEND);
    
    $usertype_id = 2;
    $join_date = date('Y-m-d H:i:s');

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $is_verified = 0; // Use 0 instead of false for integer field
    $accountstatus_id = 1;

    $firstname = $_POST['firstname'] ?? '';
    $lastname = $_POST['lastname'] ?? '';
    $region_id = $_POST['region_id'];
    $province_id = $_POST['province_id'];
    $municipality_id = $_POST['municipality_id'];
    $barangay_id = $_POST['barangay_id'];
    $house = $_POST['house'];
    $contact_number = $_POST['contact_number'];

    
    $_SESSION['reg_username'] = $username;
    $_SESSION['reg_email'] = $email;
    $_SESSION['reg_firstname'] = $firstname;
    $_SESSION['reg_lastname'] = $lastname;
    $_SESSION['reg_region_id'] = $region_id;
    $_SESSION['reg_province_id'] = $province_id;
    $_SESSION['reg_municipality_id'] = $municipality_id;
    $_SESSION['reg_barangay_id'] = $barangay_id;
    $_SESSION['reg_house'] = $house;
    $_SESSION['reg_contact_number'] = $contact_number;
    
    // Check required fields
    if (!$username || !$email || !$password || !$confirm_password || !$firstname || !$lastname || !$region_id || !$province_id || !$municipality_id || !$barangay_id || !$house || !$contact_number) {
        redirectWithError("All fields are required.");
    }

    // Trim whitespace from names
    $firstname = trim($firstname);
    $lastname = trim($lastname);
    
    // Validate first name (allows international characters, spaces, hyphens, and apostrophes)
    if (!preg_match("/^[\p{L} '\-]{2,50}$/u", $firstname)) {
        redirectWithError("Please enter a valid first name (2-50 characters, letters, spaces, hyphens, and apostrophes only)");
    }
    
    // Validate last name (allows international characters, spaces, hyphens, and apostrophes)
    if (!preg_match("/^[\p{L} '\-]{2,50}$/u", $lastname)) {
        redirectWithError("Please enter a valid last name (2-50 characters, letters, spaces, hyphens, and apostrophes only)");
    }
    
    // Check for invalid patterns like multiple spaces, hyphens, or apostrophes in a row
    if (preg_match("/ {2,}|'{2,}|-{2,}|' | '|^-|-$|'$|^'| -|-$/u", $firstname . $lastname)) {
        redirectWithError("Please enter a valid name without repeated or misplaced spaces, hyphens, or apostrophes.");
    }
    
    // Validate contact number format (accepts both with +63 or just the 10 digits)
    $contact_number = trim($contact_number);
    
    // Remove any non-digit characters except leading +
    $cleaned_number = preg_replace('/[^0-9+]/', '', $contact_number);
    
    // If it starts with 0, remove it
    if (strpos($cleaned_number, '0') === 0) {
        $cleaned_number = substr($cleaned_number, 1);
    }
    
    // If it's 10 digits without +63, add it
    if (preg_match('/^9\d{9}$/', $cleaned_number)) {
        $contact_number = '+63' . $cleaned_number;
    }
    // If it's 11 digits with 63, add the +
    elseif (preg_match('/^639\d{9}$/', $cleaned_number)) {
        $contact_number = '+' . $cleaned_number;
    }
    
    // Final validation
    if (!preg_match('/^\+639\d{9}$/', $contact_number)) {
        redirectWithError("Please enter a valid Philippine mobile number (10 digits starting with 9, e.g., 9123456789).");
    }
    
    // Update the session with the properly formatted number
    $_SESSION['reg_contact_number'] = $contact_number;

    // Validate username length
    if (strlen($username) < 3) {
        redirectWithError("Username must be at least 3 characters long.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithError("Invalid email format.");
    }

    // Validate password match
    if ($password !== $confirm_password) {
        redirectWithError("Passwords do not match.");
    }

    // Validate password strength
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    
    // Allow underscore as a special character
    $hasSpecialChar = (bool) preg_match('/[!@#$%^&*()_+\-=\[\]{};\'\"\\|,.<>\/?]+/', $password);
    $hasUnderscore = strpos($password, '_') !== false;
    $specialChars = $hasSpecialChar || $hasUnderscore;

    if (strlen($password) < 8 || !$uppercase || !$lowercase || !$number || !$specialChars) {
        file_put_contents('../debug_simple.log', "Password validation failed: len=" . strlen($password) . " up=$uppercase low=$lowercase num=$number spec=$specialChars\n", FILE_APPEND);
        redirectWithError("Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.");
    }

    file_put_contents('../debug_simple.log', "All validations passed, starting database insert\n", FILE_APPEND);

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

        // Phase 1: Generate OTP for automatic email verification
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

        $stmt = $pdo->prepare("INSERT INTO userdetails (user_id, firstname, lastname, region_id, province_id, municipality_id, barangay_id, house, contact_number)
                                VALUES (:user_id, :firstname, :lastname, :region_id, :province_id, :municipality_id, :barangay_id, :house, :contact_number)");
                                
        $stmt->execute([
            'user_id' => $lastInsertId,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'region_id' => $region_id,
            'province_id' => $province_id,
            'municipality_id' => $municipality_id,
            'barangay_id' => $barangay_id,
            'house' => $house,
            'contact_number' => $contact_number
        ]);
                
    // Do not set user_id or show email in success message
    $_SESSION['success_message'] = "Account created successfully! Please check your email for verification instructions.";
    header("Location: ../login.php");
    exit;

    } catch (PDOException $e) {
        redirectWithError("Registration failed: " . $e->getMessage());
    }
} else {
    redirectWithError("Invalid request.");
}
