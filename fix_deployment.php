<?php
/**
 * Deployment Fix Script
 * Run this script once after uploading to Hostinger to fix common issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Deployment Fix Script</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
        h1 { color: #667eea; margin-top: 0; }
        .success { color: #10b981; padding: 10px; background: #d1fae5; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; padding: 10px; background: #fee2e2; border-radius: 5px; margin: 10px 0; }
        .warning { color: #f59e0b; padding: 10px; background: #fef3c7; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; padding: 10px; background: #dbeafe; border-radius: 5px; margin: 10px 0; }
        .section { margin: 20px 0; padding: 20px; background: #f9fafb; border-radius: 10px; }
        .check-item { padding: 8px; margin: 5px 0; border-left: 3px solid #e5e7eb; padding-left: 15px; }
        .btn { display: inline-block; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px 0 0; }
        .btn:hover { background: #5568d3; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔧 Deployment Fix Script</h1>
    <p>This script will check and fix common deployment issues on Hostinger.</p>";

$fixes_applied = [];
$errors_found = [];

// 1. Check PHP Version
echo "<div class='section'>";
echo "<h2>1. PHP Version Check</h2>";
$php_version = phpversion();
echo "<div class='check-item'>";
echo "PHP Version: <strong>$php_version</strong><br>";
if (version_compare($php_version, '7.4.0', '>=')) {
    echo "<div class='success'>✓ PHP version is compatible</div>";
} else {
    echo "<div class='error'>✗ PHP version is too old. Please upgrade to PHP 7.4 or higher in cPanel.</div>";
    $errors_found[] = "PHP version too old";
}
echo "</div>";
echo "</div>";

// 2. Check Database Connection
echo "<div class='section'>";
echo "<h2>2. Database Connection</h2>";
try {
    $host = 'localhost';
    $dbname = 'u119634533_sapinbedsheets';
    $username = 'u119634533_sapinbedsheets';
    $password = 'AicellDEC_ROBLES200325';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>✓ Database connection successful!</div>";
    
    // Check required tables
    echo "<h3>Checking Required Tables:</h3>";
    $required_tables = [
        'users' => 'User accounts',
        'usertype' => 'User roles',
        'orders' => 'Customer orders',
        'products' => 'Product catalog',
        'materials' => 'Material inventory',
        'visitors' => 'Visitor tracking',
        'expenses' => 'Expense records',
        'pos_sales' => 'POS transactions'
    ];
    
    $missing_tables = [];
    foreach ($required_tables as $table => $description) {
        try {
            $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "<div class='check-item success'>✓ Table <code>$table</code> exists ($description)</div>";
        } catch (PDOException $e) {
            echo "<div class='check-item error'>✗ Table <code>$table</code> is missing ($description)</div>";
            $missing_tables[] = $table;
            $errors_found[] = "Missing table: $table";
        }
    }
    
    // Try to create visitors table if missing
    if (in_array('visitors', $missing_tables)) {
        echo "<h3>Attempting to create visitors table...</h3>";
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `visitors` (
                  `visitor_id` int(11) NOT NULL AUTO_INCREMENT,
                  `ip_address` varchar(45) NOT NULL,
                  `user_agent` text,
                  `visit_time` datetime NOT NULL,
                  PRIMARY KEY (`visitor_id`),
                  KEY `idx_ip_date` (`ip_address`, `visit_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            echo "<div class='success'>✓ Created visitors table successfully!</div>";
            $fixes_applied[] = "Created visitors table";
        } catch (PDOException $e) {
            echo "<div class='error'>✗ Failed to create visitors table: " . $e->getMessage() . "</div>";
        }
    }
    
    // Check if admin user exists
    echo "<h3>Checking Admin User:</h3>";
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE usertype_id IN (1, 5)");
        $admin_count = $stmt->fetchColumn();
        if ($admin_count > 0) {
            echo "<div class='success'>✓ Found $admin_count admin user(s)</div>";
        } else {
            echo "<div class='error'>✗ No admin users found. You may need to create one.</div>";
            $errors_found[] = "No admin users";
        }
    } catch (PDOException $e) {
        echo "<div class='error'>✗ Could not check admin users: " . $e->getMessage() . "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='warning'>
        <strong>Fix this by:</strong>
        <ol>
            <li>Verify database exists in Hostinger cPanel > MySQL Databases</li>
            <li>Check database credentials in <code>config/db.production.php</code></li>
            <li>Ensure database user has proper privileges</li>
            <li>Import your database SQL file via phpMyAdmin</li>
        </ol>
    </div>";
    $errors_found[] = "Database connection failed";
}
echo "</div>";

// 3. Check Required Files
echo "<div class='section'>";
echo "<h2>3. Required Files Check</h2>";
$required_files = [
    '../config/db.php' => 'Database config router',
    '../config/db.production.php' => 'Production database config',
    '../config/session_admin.php' => 'Admin session handler',
    '../includes/sidebar_admin.php' => 'Admin sidebar',
    '../includes/navbar_admin.php' => 'Admin navbar',
    'index.php' => 'Admin dashboard',
    'css/app.css' => 'Main stylesheet',
    '../.htaccess' => 'Server configuration'
];

foreach ($required_files as $file => $description) {
    echo "<div class='check-item'>";
    if (file_exists($file)) {
        echo "✓ <code>$file</code> - $description";
        echo "<div class='success'>EXISTS</div>";
    } else {
        echo "✗ <code>$file</code> - $description";
        echo "<div class='error'>MISSING - Please upload this file</div>";
        $errors_found[] = "Missing file: $file";
    }
    echo "</div>";
}
echo "</div>";

// 4. Check Directory Permissions
echo "<div class='section'>";
echo "<h2>4. Directory Permissions</h2>";
$directories = [
    '../logs' => 'Error logs',
    '../uploads' => 'File uploads',
    '../assets/images' => 'Product images'
];

foreach ($directories as $dir => $description) {
    echo "<div class='check-item'>";
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✓ <code>$dir</code> - $description";
            echo "<div class='success'>WRITABLE</div>";
        } else {
            echo "⚠ <code>$dir</code> - $description";
            echo "<div class='warning'>NOT WRITABLE - Set permissions to 777</div>";
            $errors_found[] = "Directory not writable: $dir";
        }
    } else {
        echo "✗ <code>$dir</code> - $description";
        echo "<div class='error'>DOES NOT EXIST</div>";
        
        // Try to create directory
        if (@mkdir($dir, 0777, true)) {
            echo "<div class='success'>✓ Created directory successfully!</div>";
            $fixes_applied[] = "Created directory: $dir";
        } else {
            echo "<div class='error'>Failed to create directory. Please create manually.</div>";
        }
    }
    echo "</div>";
}
echo "</div>";

// 5. Check PHP Extensions
echo "<div class='section'>";
echo "<h2>5. PHP Extensions</h2>";
$required_extensions = [
    'pdo' => 'PDO Database',
    'pdo_mysql' => 'MySQL PDO Driver',
    'mysqli' => 'MySQL Improved',
    'mbstring' => 'Multibyte String',
    'json' => 'JSON Support',
    'session' => 'Session Support',
    'gd' => 'Image Processing'
];

foreach ($required_extensions as $ext => $description) {
    echo "<div class='check-item'>";
    if (extension_loaded($ext)) {
        echo "✓ <code>$ext</code> - $description";
        echo "<div class='success'>ENABLED</div>";
    } else {
        echo "✗ <code>$ext</code> - $description";
        echo "<div class='error'>DISABLED - Enable in cPanel > Select PHP Version</div>";
        $errors_found[] = "Missing extension: $ext";
    }
    echo "</div>";
}
echo "</div>";

// 6. Test Session
echo "<div class='section'>";
echo "<h2>6. Session Test</h2>";
// Check if session can be started (but don't actually start it since headers are already sent)
if (function_exists('session_status')) {
    echo "<div class='success'>✓ Session functions are available</div>";
    echo "<div class='info'>Note: Session test skipped to avoid header conflicts in this diagnostic script</div>";
    echo "<div class='info'>Sessions will work normally in your application</div>";
} else {
    echo "<div class='error'>✗ Session functions not available</div>";
    $errors_found[] = "Session functions not available";
}
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📊 Summary</h2>";

if (empty($errors_found)) {
    echo "<div class='success'>";
    echo "<h3>✓ All Checks Passed!</h3>";
    echo "<p>Your application should be working correctly now.</p>";
    echo "</div>";
} else {
    echo "<div class='error'>";
    echo "<h3>⚠ Issues Found: " . count($errors_found) . "</h3>";
    echo "<ul>";
    foreach ($errors_found as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($fixes_applied)) {
    echo "<div class='success'>";
    echo "<h3>✓ Fixes Applied: " . count($fixes_applied) . "</h3>";
    echo "<ul>";
    foreach ($fixes_applied as $fix) {
        echo "<li>$fix</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<div class='info'>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If database connection failed, import your database SQL file via phpMyAdmin</li>";
echo "<li>Ensure you have an admin user account created</li>";
echo "<li>Try logging in at: <a href='../auth/login.php'>Login Page</a></li>";
echo "<li>Visit the admin dashboard: <a href='index.php'>Admin Dashboard</a></li>";
echo "<li>After everything works, delete this file for security</li>";
echo "</ol>";
echo "</div>";

echo "<a href='debug.php' class='btn'>Run Diagnostics</a>";
echo "<a href='index.php' class='btn'>Go to Dashboard</a>";
echo "<a href='../auth/login.php' class='btn'>Login Page</a>";

echo "</div>";
echo "</div>";
echo "</body></html>";
?>
