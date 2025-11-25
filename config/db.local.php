<?php
    $host = 'localhost';
    $dbname = 'sapinbedsheets';
    $username = 'root';
    $password = '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        // Set MySQL timezone to Philippines (UTC+8)
        $pdo->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        // Log the error
        error_log("Database Connection Error (Local): " . $e->getMessage());
        
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
                'message' => 'Database connection failed. Please try again later.',
                'debug' => $e->getMessage() // Include debug info on localhost
            ]));
        } else {
            // Display error message for regular requests
            die("Connection failed: " . $e->getMessage());
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