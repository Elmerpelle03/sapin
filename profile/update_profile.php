<?php
require ('../config/db.php');
require ('../config/session.php');

$user_id = $_SESSION['user_id'];

// Fetch current user data
$stmt = $pdo->prepare("SELECT a.username, b.firstname, b.lastname, b.region_id, b.province_id, b.municipality_id, b.barangay_id, b.house, b.contact_number, a.email 
    FROM users a 
    LEFT JOIN userdetails b ON a.user_id = b.user_id 
    WHERE a.user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user_data = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $region_id = $_POST['region_id'];
    $province_id = $_POST['province_id'];
    $municipality_id = $_POST['municipality_id'];
    $barangay_id = $_POST['barangay_id'];
    $house = $_POST['house'];
    $contact_number = $_POST['contact_number'];

    // Validate contact number (must be exactly 11 digits, numeric only)
    if (!preg_match('/^\d{11}$/', $contact_number)) {
        $_SESSION['error_message'] = "Invalid contact number. Please enter exactly 11 digits.";
        header('Location: ../edit_profile.php');
        exit();
    } else {
        // Check if userdetails already exists
        $stmt = $pdo->prepare("SELECT userdetails_id FROM userdetails WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        $existing = $stmt->fetch();
    
        if ($existing) {
            // Update if exists
            $stmt = $pdo->prepare("UPDATE userdetails 
                                   SET firstname = :firstname, lastname = :lastname, region_id = :region_id, province_id = :province_id, 
                                   municipality_id = :municipality_id, barangay_id = :barangay_id, house = :house, contact_number = :contact_number 
                                   WHERE user_id = :user_id");
        } else {
            // Insert if not exists
            $stmt = $pdo->prepare("INSERT INTO userdetails (user_id, firstname, lastname, region_id, province_id, municipality_id, barangay_id, house, contact_number)
                                   VALUES (:user_id, :firstname, :lastname, :region_id, :province_id, :municipality_id, :barangay_id, :house, :contact_number)");
        }
    
        // Execute with common values
        $stmt->execute([
            'user_id' => $user_id,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'region_id' => $region_id,
            'province_id' => $province_id,
            'municipality_id' => $municipality_id,
            'barangay_id' => $barangay_id,
            'house' => $house,
            'contact_number' => $contact_number
        ]);
    
        $_SESSION['success_message'] = "Profile saved successfully.";
        header('Location: ../index.php');
        exit();
    }
}
?>
