<?php
/**
 * Check Database Connection Details
 */

// Suppress visitor tracking
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test Script';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Connection Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; border-left: 4px solid #007bff; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection Diagnostic</h1>
";

// Check HTTP_HOST
echo "<div class='section'>";
echo "<h2>Step 1: Environment Detection</h2>";
echo "<pre>";
echo "HTTP_HOST: " . $_SERVER['HTTP_HOST'] . "\n";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Is Localhost: " . ($_SERVER['HTTP_HOST'] === 'localhost' ? 'YES' : 'NO') . "\n";
echo "\nWhich config will be loaded: ";
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    echo "<span class='warning'>db.local.php (LOCALHOST)</span>\n";
} else {
    echo "<span class='success'>db.production.php (HOSTINGER)</span>\n";
}
echo "</pre>";
echo "</div>";

// Now load the config
require('../config/db.php');

// Check actual connection
echo "<div class='section'>";
echo "<h2>Step 2: Actual Database Connection</h2>";
try {
    $stmt = $pdo->query("SELECT DATABASE() as dbname, USER() as user, VERSION() as version");
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    echo "✓ Connection Successful!\n\n";
    echo "Database Name: <span class='success'>" . $info['dbname'] . "</span>\n";
    echo "Database User: " . $info['user'] . "\n";
    echo "MySQL Version: " . $info['version'] . "\n";
    echo "</pre>";
    
    // Check if this matches what we expect
    echo "<div style='margin-top: 15px; padding: 15px; background: #fff3cd; border-left: 4px solid #ff9800;'>";
    echo "<strong>⚠️ IMPORTANT CHECK:</strong><br>";
    if ($_SERVER['HTTP_HOST'] === 'localhost') {
        if ($info['dbname'] === 'sapinbedsheets') {
            echo "<span class='success'>✓ Correct: Using localhost database</span>";
        } else {
            echo "<span class='error'>✗ Wrong database! Expected 'sapinbedsheets', got '" . $info['dbname'] . "'</span>";
        }
    } else {
        if ($info['dbname'] === 'u119634533_sapin_bedsheet') {
            echo "<span class='success'>✓ Correct: Using Hostinger database</span>";
        } else {
            echo "<span class='error'>✗ Wrong database! Expected 'u119634533_sapin_bedsheet', got '" . $info['dbname'] . "'</span>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Connection Failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Show all tables in connected database
echo "<div class='section'>";
echo "<h2>Step 3: Tables in Connected Database</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>Found <strong>" . count($tables) . "</strong> tables in database: <code>" . $info['dbname'] . "</code></p>";
    
    // Check for our specific tables
    $has_notifications = in_array('notifications', $tables);
    $has_returns = in_array('return_requests', $tables);
    
    echo "<div style='margin: 15px 0;'>";
    echo "<p>Looking for required tables:</p>";
    echo "<ul style='font-size: 16px;'>";
    echo "<li>" . ($has_notifications ? "<span class='success'>✓ notifications</span>" : "<span class='error'>✗ notifications (MISSING)</span>") . "</li>";
    echo "<li>" . ($has_returns ? "<span class='success'>✓ return_requests</span>" : "<span class='error'>✗ return_requests (MISSING)</span>") . "</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<details>";
    echo "<summary style='cursor: pointer; padding: 10px; background: #f8f9fa; border-radius: 4px;'>Click to see all tables</summary>";
    echo "<pre style='margin-top: 10px;'>";
    foreach ($tables as $i => $table) {
        echo ($i + 1) . ". " . $table . "\n";
    }
    echo "</pre>";
    echo "</details>";
    
} catch (Exception $e) {
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Check config file contents
echo "<div class='section'>";
echo "<h2>Step 4: Configuration File Check</h2>";
echo "<p>Let's verify what's in your <code>db.production.php</code> file:</p>";

$config_file = '../config/db.production.php';
if (file_exists($config_file)) {
    $config_content = file_get_contents($config_file);
    
    // Extract database name from config
    preg_match('/\$dbname\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches);
    $config_dbname = $matches[1] ?? 'NOT FOUND';
    
    preg_match('/\$host\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches);
    $config_host = $matches[1] ?? 'NOT FOUND';
    
    preg_match('/\$username\s*=\s*[\'"]([^\'"]+)[\'"]/', $config_content, $matches);
    $config_username = $matches[1] ?? 'NOT FOUND';
    
    echo "<pre>";
    echo "Host: " . $config_host . "\n";
    echo "Database: <span class='success'>" . $config_dbname . "</span>\n";
    echo "Username: " . $config_username . "\n";
    echo "</pre>";
    
    if ($config_dbname !== 'u119634533_sapin_bedsheet') {
        echo "<div style='padding: 15px; background: #f8d7da; border-left: 4px solid #dc3545; margin-top: 10px;'>";
        echo "<span class='error'>⚠️ WARNING: Database name in config doesn't match expected Hostinger database!</span>";
        echo "</div>";
    }
} else {
    echo "<p class='error'>Config file not found!</p>";
}
echo "</div>";

// Diagnosis
echo "<div class='section'>";
echo "<h2>🎯 Diagnosis</h2>";

if (!$has_notifications || !$has_returns) {
    echo "<div style='padding: 20px; background: #f8d7da; border-left: 4px solid #dc3545;'>";
    echo "<h3 style='margin-top: 0;'>❌ Problem Identified</h3>";
    echo "<p>The tables <strong>notifications</strong> and/or <strong>return_requests</strong> do NOT exist in the database you're currently connected to: <code>" . $info['dbname'] . "</code></p>";
    
    echo "<h4>Possible Causes:</h4>";
    echo "<ol>";
    echo "<li><strong>You created the tables in a different database</strong><br>Check if you have multiple databases in phpMyAdmin and created the tables in the wrong one.</li>";
    echo "<li><strong>The SQL commands failed silently</strong><br>There might have been an error you didn't notice.</li>";
    echo "<li><strong>Wrong database credentials</strong><br>Your config file might be pointing to the wrong database.</li>";
    echo "</ol>";
    
    echo "<h4>Solution:</h4>";
    echo "<p>1. Go to phpMyAdmin on Hostinger</p>";
    echo "<p>2. Make sure you select database: <code style='background: yellow; padding: 3px 8px;'>" . $info['dbname'] . "</code></p>";
    echo "<p>3. Run the CREATE TABLE commands again in that specific database</p>";
    echo "</div>";
} else {
    echo "<div style='padding: 20px; background: #d4edda; border-left: 4px solid #28a745;'>";
    echo "<h3 style='margin-top: 0;'>✅ Tables Found!</h3>";
    echo "<p>Both <strong>notifications</strong> and <strong>return_requests</strong> tables exist in the database.</p>";
    echo "<p>If you're still getting errors, it might be a caching issue. Try clearing your browser cache.</p>";
    echo "</div>";
}

echo "</div>";

echo "
    <div class='section'>
        <p class='error'><strong>⚠️ Delete this file after checking!</strong></p>
    </div>
</body>
</html>
";
?>
