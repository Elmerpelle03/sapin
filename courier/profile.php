<?php
require('../config/session_courier.php');
require('../config/db.php');

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['fullname'] ?? 'Courier';
$user_email = $_SESSION['email'] ?? 'N/A';
$user_role = $_SESSION['role'] ?? 'courier';

// Get delivery statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_deliveries,
        SUM(CASE WHEN status IN ('Delivered', 'Received') THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'Shipping' THEN 1 ELSE 0 END) as in_progress
    FROM orders 
    WHERE rider_id = :rider_id
");
$stats_stmt->execute(['rider_id' => $user_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// Use session data for user info
$user_data = [
    'fullname' => $username,
    'username' => $_SESSION['username'] ?? 'N/A',
    'email' => $user_email,
    'contact_number' => $_SESSION['contact_number'] ?? 'N/A',
    'address' => $_SESSION['address'] ?? 'N/A',
    'created_at' => date('Y-m-d')
];

// Handle messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Courier</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .container-custom {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            transform: translateX(-5px);
        }
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }
        
        .profile-header {
            text-align: center;
            padding: 2rem 0;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 20px;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
        
        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            color: #6b7280;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .stat-box {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .info-section {
            margin-top: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .section-title i {
            color: #3b82f6;
        }
        
        .info-row {
            display: flex;
            padding: 1rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            flex: 0 0 180px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .info-value {
            flex: 1;
            color: #1f2937;
            font-weight: 500;
        }
        
        .btn-modern {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary-modern {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        }
        
        .btn-primary-modern:hover {
            box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
            transform: translateY(-2px);
            color: white;
        }
        
        .alert-modern {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <a href="index.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-modern">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-modern">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user_data['fullname'] ?? 'C', 0, 1)); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user_data['fullname'] ?? 'Courier'); ?></div>
                <div class="profile-role">Courier</div>
                
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $stats['total_deliveries']; ?></div>
                        <div class="stat-label">Total Deliveries</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="info-section">
                <div class="section-title">
                    <i class="bi bi-person-circle"></i>
                    Account Information
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="bi bi-person"></i> Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['fullname'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="bi bi-at"></i> Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="bi bi-envelope"></i> Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['email'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="bi bi-telephone"></i> Contact Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['contact_number'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="bi bi-geo-alt"></i> Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($user_data['address'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-4 text-center">
                <a href="edit_profile.php" class="btn-modern btn-primary-modern">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
