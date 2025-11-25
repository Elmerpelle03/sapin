<?php
// Email availability checker for registration
// Version: 2.0 - Fixed for production deployment

// Disable session for API endpoints (performance)
// session_start(); // Not needed for this endpoint

// Set content type to JSON
header('Content-Type: application/json');

// Disable caching for this endpoint
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Include database configuration
require_once dirname(__DIR__) . '/config/db.php';

// Check if email is provided
if (!isset($_GET['email']) || empty(trim($_GET['email']))) {
    echo json_encode(['available' => false, 'message' => 'Email is required']);
    exit;
}

$email = trim($_GET['email']);

// Basic email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['available' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Verify PDO connection exists
    if (!isset($pdo)) {
        throw new Exception('Database connection not established');
    }
    
    // Check if users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        throw new PDOException('Users table does not exist');
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['available' => false, 'message' => 'Email is already in use']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Email is available']);
    }
} catch (PDOException $e) {
    error_log("Database Error in check_email.php: " . $e->getMessage());
    http_response_code(500);
    
    echo json_encode([
        'available' => false, 
        'message' => 'Unable to verify email availability. Please try again.',
    ]);
} catch (Exception $e) {
    error_log("Error in check_email.php: " . $e->getMessage());
    http_response_code(500);
    
    echo json_encode([
        'available' => false, 
        'message' => 'Service temporarily unavailable. Please try again.',
    ]);
}
?>
