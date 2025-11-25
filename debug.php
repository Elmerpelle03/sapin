<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug Information</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo phpversion() . "<br><br>";

// Check if config files exist
echo "<h2>File Checks</h2>";
$files = [
    '../config/db.php',
    '../config/db.production.php',
    '../config/session_admin.php',
    '../includes/sidebar_admin.php',
    '../includes/navbar_admin.php'
];

foreach ($files as $file) {
    echo $file . ": " . (file_exists($file) ? "✓ EXISTS" : "✗ NOT FOUND") . "<br>";
}

echo "<br>";

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    $host = 'localhost';
    $dbname = 'u119634533_sapinbedsheets';
    $username = 'u119634533_sapinbedsheets';
    $password = 'AicellDEC_ROBLES200325';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Database connection successful!<br>";
    
    // Check if tables exist
    echo "<h3>Table Checks</h3>";
    $tables = ['users', 'usertype', 'orders', 'materials', 'products', 'visitors'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "$table: ✓ EXISTS<br>";
        } catch (PDOException $e) {
            echo "$table: ✗ NOT FOUND - " . $e->getMessage() . "<br>";
        }
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<br>";

// Check session
echo "<h2>Session Information</h2>";
session_start();
echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "✓ YES" : "✗ NO") . "<br>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "NOT SET") . "<br>";
echo "Usertype ID: " . (isset($_SESSION['usertype_id']) ? $_SESSION['usertype_id'] : "NOT SET") . "<br>";

echo "<br>";

// Check server info
echo "<h2>Server Information</h2>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Current Directory: " . __DIR__ . "<br>";

?>
