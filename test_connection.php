<?php
/**
 * Database Connection Diagnostic Tool
 * Use this to test the database connection on production server
 * DELETE THIS FILE after testing for security
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";
echo "<p><strong>Environment:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</p>";

// Production database credentials (update if needed)
$host = 'localhost';
$dbname = 'u119634533_sapinbedsheets';
$username = 'u119634533_sapinbedsheets';
$password = 'AicellDEC_ROBLES200325';

echo "<h3>Testing Connection...</h3>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Database connection successful!</p>";
    
    // Test users table
    echo "<h3>Testing 'users' table...</h3>";
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() > 0) {
        echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Users table exists.</p>";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table Structure:</strong></p>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['Field']} ({$col['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'><strong>✗ ERROR:</strong> Users table does not exist!</p>";
    }
    
    // Test email column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'><strong>✓ SUCCESS:</strong> Email column exists.</p>";
    } else {
        echo "<p style='color: red;'><strong>✗ ERROR:</strong> Email column does not exist!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>✗ CONNECTION FAILED:</strong></p>";
    echo "<p><strong>Error Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Error Code:</strong> " . $e->getCode() . "</p>";
    
    echo "<h3>Possible Issues:</h3>";
    echo "<ul>";
    echo "<li>Database credentials are incorrect</li>";
    echo "<li>Database does not exist on the server</li>";
    echo "<li>Database user does not have proper permissions</li>";
    echo "<li>MySQL server is not running</li>";
    echo "<li>Firewall blocking connection</li>";
    echo "</ul>";
    
    echo "<h3>Things to Verify:</h3>";
    echo "<ol>";
    echo "<li>Check Hostinger control panel for correct database name</li>";
    echo "<li>Verify database username (usually same as database name)</li>";
    echo "<li>Confirm database password</li>";
    echo "<li>Ensure database user has all privileges</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><strong>IMPORTANT:</strong> Delete this file after testing for security reasons!</p>";
?>
