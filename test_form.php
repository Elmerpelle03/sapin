<?php
// Simple test to see if form data is being received
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>Form Fields Check:</h3>";
    $required_fields = ['username', 'email', 'password', 'confirm_password', 'firstname', 'lastname', 'region_id', 'province_id', 'municipality_id', 'barangay_id', 'house', 'contact_number'];
    
    foreach ($required_fields as $field) {
        echo $field . ": " . (isset($_POST[$field]) ? "✓ " . $_POST[$field] : "✗ MISSING") . "<br>";
    }
} else {
    echo "<h3>No POST data received</h3>";
    echo "Request method: " . $_SERVER['REQUEST_METHOD'];
}
?>
