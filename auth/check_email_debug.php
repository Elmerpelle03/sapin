<?php
// Debug version of check_email.php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log
ini_set('log_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Debug info
$debugInfo = [
    'step' => 'Starting',
    'host' => $_SERVER['HTTP_HOST'],
    'script' => basename($_SERVER['SCRIPT_FILENAME']),
    'email_provided' => isset($_GET['email']) ? 'yes' : 'no'
];

try {
    $debugInfo['step'] = 'Including database config';
    require_once dirname(__DIR__) . '/config/db.php';
    
    $debugInfo['step'] = 'Database config included';
    
    if (!isset($pdo)) {
        throw new Exception('PDO object not created');
    }
    
    $debugInfo['step'] = 'PDO exists';
    $debugInfo['pdo_connected'] = true;
    
    // Check if email is provided
    if (!isset($_GET['email']) || empty(trim($_GET['email']))) {
        echo json_encode([
            'available' => false, 
            'message' => 'Email is required',
            'debug' => $debugInfo
        ]);
        exit;
    }
    
    $email = trim($_GET['email']);
    $debugInfo['email'] = $email;
    $debugInfo['step'] = 'Email validated';
    
    // Basic email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'available' => false, 
            'message' => 'Invalid email format',
            'debug' => $debugInfo
        ]);
        exit;
    }
    
    $debugInfo['step'] = 'Checking table exists';
    
    // Check if users table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        throw new PDOException('Users table does not exist');
    }
    
    $debugInfo['step'] = 'Table exists, querying email';
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    $debugInfo['step'] = 'Query executed';
    $debugInfo['rows_found'] = $stmt->rowCount();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'available' => false, 
            'message' => 'Email is already in use',
            'debug' => $debugInfo
        ]);
    } else {
        echo json_encode([
            'available' => true, 
            'message' => 'Email is available',
            'debug' => $debugInfo
        ]);
    }
    
} catch (PDOException $e) {
    $debugInfo['step'] = 'PDO Exception';
    $debugInfo['error'] = $e->getMessage();
    
    echo json_encode([
        'available' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => $debugInfo
    ]);
    
} catch (Exception $e) {
    $debugInfo['step'] = 'General Exception';
    $debugInfo['error'] = $e->getMessage();
    
    echo json_encode([
        'available' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => $debugInfo
    ]);
}
?>
