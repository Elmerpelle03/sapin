<?php
// Temporary debug file to see what's causing the 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Database Connection</h1>";

try {
    require('config/db.php');
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    echo "<p>Connected to: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>Testing Session</h2>";
try {
    session_start();
    echo "<p style='color: green;'>✓ Session started successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Session error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>PHP Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

echo "<hr>";
echo "<p><strong>If you see this page, the basic PHP is working.</strong></p>";
echo "<p>Now try accessing your login page and see what happens.</p>";
?>
