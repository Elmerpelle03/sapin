<?php
// Test check_email.php database connection
header('Content-Type: application/json');

echo json_encode([
    'test' => 'Starting test...',
    'host' => $_SERVER['HTTP_HOST'],
    'script' => basename($_SERVER['SCRIPT_FILENAME'])
]);

// Try to include the database
try {
    require_once dirname(__DIR__) . '/config/db.php';
    
    if (isset($pdo)) {
        echo json_encode([
            'success' => true,
            'message' => 'Database connected successfully!',
            'pdo_exists' => true
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'PDO variable not set',
            'pdo_exists' => false
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
}
?>
