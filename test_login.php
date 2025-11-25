<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Login Page Components</h1>";

echo "<h2>1. Testing config/db.php</h2>";
try {
    require('config/db.php');
    echo "<p style='color: green;'>✓ Database loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>2. Testing session</h2>";
try {
    session_start();
    echo "<p style='color: green;'>✓ Session started</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>3. Testing auth/login.php include</h2>";
try {
    // Don't actually include it, just check if file exists
    if (file_exists('auth/login.php')) {
        echo "<p style='color: green;'>✓ auth/login.php exists</p>";
    } else {
        echo "<p style='color: red;'>✗ auth/login.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>4. Testing login.php</h2>";
try {
    if (file_exists('login.php')) {
        echo "<p style='color: green;'>✓ login.php exists</p>";
        echo "<p><a href='login.php'>Click here to try login.php</a></p>";
    } else {
        echo "<p style='color: red;'>✗ login.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Direct Links to Test:</h2>";
echo "<ul>";
echo "<li><a href='login.php'>login.php</a></li>";
echo "<li><a href='index.php'>index.php</a></li>";
echo "<li><a href='admin/index.php'>admin/index.php</a></li>";
echo "</ul>";
?>
