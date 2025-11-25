<?php
    // Enable error logging for debugging
    error_reporting(E_ALL);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    
    $host = 'localhost';
    $dbname = 'u119634533_sapinbedsheets';
    $username = 'u119634533_sapinbedsheets';
    $password = 'AicellDEC_ROBLES200325';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Set MySQL timezone to Philippines (UTC+8)
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        // Log the error
        error_log("Database Connection Error: " . $e->getMessage());
        
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        // Check if the script expects JSON (for API endpoints)
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        $jsonEndpoints = ['check_email.php', 'check_username.php', 'register.php'];
        $expectsJson = in_array($scriptName, $jsonEndpoints) || $isAjax;
        
        if ($expectsJson) {
            // Return JSON error for AJAX/API requests
            header('Content-Type: application/json');
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'available' => false,
                'message' => 'Database connection failed. Please try again later.'
            ]));
        } else {
            // Display user-friendly HTML error for regular page requests
            die("
            <!DOCTYPE html>
            <html>
            <head>
                <title>Database Connection Error</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 50px; background: #f5f5f5; }
                    .error-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
                    h1 { color: #dc3545; }
                    .details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px; font-family: monospace; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='error-box'>
                    <h1>⚠️ Database Connection Error</h1>
                    <p>Unable to connect to the database. Please check:</p>
                    <ul>
                        <li>Database credentials are correct</li>
                        <li>Database server is running</li>
                        <li>Database exists on the server</li>
                    </ul>
                    <div class='details'>Error: " . htmlspecialchars($e->getMessage()) . "</div>
                    <p style='margin-top: 20px;'><a href='debug.php'>Run Diagnostics</a></p>
                </div>
            </body>
            </html>
            ");
        }
    }
    date_default_timezone_set('Asia/Manila');

    // Skip visitor tracking for AJAX/API calls to avoid interference
    $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
    $skipTracking = in_array($scriptName, ['check_email.php', 'check_username.php', 'register.php']);
    
    if (!$skipTracking) {
        try{
            //track visitors
            $ip = $_SERVER['REMOTE_ADDR'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $today = date("Y-m-d");

            $stmt = $pdo->prepare("SELECT 1 FROM visitors WHERE ip_address = :ip AND DATE(visit_time) = :today");
            $stmt->execute([':ip' => $ip, ':today' => $today]);

            if ($stmt->rowCount() === 0) {
                $visitTime = date("Y-m-d H:i:s");
                $insert = $pdo->prepare("INSERT INTO visitors (ip_address, user_agent, visit_time) VALUES (:ip, :userAgent, :visitTime)");
                $insert->execute([
                    ':ip' => $ip,
                    ':userAgent' => $userAgent,
                    ':visitTime' => $visitTime
                ]);
            }
        }catch (PDOException $e) {
            // Silently fail if visitors table doesn't exist - don't break the site
            error_log("Visitor tracking error: " . $e->getMessage());
        }
    }
?>
