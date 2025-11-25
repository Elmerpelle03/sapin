<?php
require('../config/session_courier.php');
require('../config/db.php');

$user_id = $_SESSION['user_id'] ?? 0;
$username = $_SESSION['fullname'] ?? 'Courier';

// Fetch current user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    
    // Validate
    if (empty($fullname) || empty($email)) {
        $_SESSION['error_message'] = "Full name and email are required.";
    } else {
        // Update user data
        try {
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET fullname = :fullname, 
                    email = :email, 
                    contact_number = :contact_number, 
                    address = :address
                WHERE user_id = :user_id
            ");
            
            $update_stmt->execute([
                'fullname' => $fullname,
                'email' => $email,
                'contact_number' => $contact_number,
                'address' => $address,
                'user_id' => $user_id
            ]);
            
            // Update session
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['contact_number'] = $contact_number;
            $_SESSION['address'] = $address;
            
            $_SESSION['success_message'] = "Profile updated successfully!";
            header('Location: profile.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Error updating profile: " . $e->getMessage();
        }
    }
}

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
    <title>Edit Profile - Courier</title>
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
            max-width: 700px;
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
        
        .edit-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .card-header-custom {
            text-align: center;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 2rem;
        }
        
        .card-title-custom {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .card-subtitle {
            color: #6b7280;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-control-modern {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s;
            font-size: 0.9375rem;
        }
        
        .form-control-modern:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .btn-modern {
            padding: 0.875rem 2rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 1rem;
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
        
        .btn-secondary-modern {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .btn-secondary-modern:hover {
            background: #e5e7eb;
            color: #1f2937;
        }
        
        .alert-modern {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .required {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container-custom">
        <a href="profile.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Back to Profile
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
        
        <div class="edit-card">
            <div class="card-header-custom">
                <div class="card-title-custom">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </div>
                <div class="card-subtitle">Update your personal information</div>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">
                        Full Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           name="fullname" 
                           class="form-control form-control-modern" 
                           value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Email Address <span class="required">*</span>
                    </label>
                    <input type="email" 
                           name="email" 
                           class="form-control form-control-modern" 
                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Contact Number
                    </label>
                    <input type="text" 
                           name="contact_number" 
                           class="form-control form-control-modern" 
                           value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>" 
                           placeholder="e.g., 09123456789">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Address
                    </label>
                    <textarea name="address" 
                              class="form-control form-control-modern" 
                              rows="3" 
                              placeholder="Enter your complete address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="d-flex gap-3 justify-content-end mt-4">
                    <a href="profile.php" class="btn-modern btn-secondary-modern">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn-modern btn-primary-modern">
                        <i class="bi bi-check-circle"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
