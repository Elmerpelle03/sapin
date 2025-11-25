<?php 
    require ('config/db.php');
    session_start();
    require ('config/details_checker.php');
    require('config/session_disallow_courier.php');
    
    // Check if user is bulk buyer
    if(!isset($_SESSION['user_id'])){
        header('Location: login.php');
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT usertype_id FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $usertype = $stmt->fetchColumn();
    
    if($usertype != 3){
        $_SESSION['error_message'] = "This feature is only available for wholesalers.";
        header('Location: index.php');
        exit();
    }

    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Admin - Sapin Bedsheets</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f59e0b;
        }
        
        body {
            background: linear-gradient(120deg, #f8fafc 0%, #ffffff 100%);
            min-height: 100vh;
        }
        
        /* Navbar styling to match wishlist */
        .custom-navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
    
        .custom-navbar .nav-link {
            color: #4a4a4a !important;
            font-weight: 500;
            padding: 0.8rem 1.2rem !important;
            transition: color 0.3s ease;
        }
    
        .custom-navbar .nav-link:hover,
        .custom-navbar .nav-link.active {
            color: var(--primary-color) !important;
        }
    
        .custom-navbar .navbar-brand {
            font-weight: 700;
            color: #2563eb !important;
        }
        
        .badge.cart-badge {
            background-color: #2563eb !important;
            transition: transform 0.2s ease;
        }
        
        .badge.cart-badge:hover {
            transform: scale(1.1);
        }
        
        .message-header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #60a5fa 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .message-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1rem;
        }
        
        .message-item {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 12px;
            max-width: 70%;
        }
        
        .message-buyer {
            background: #e0f2fe;
            margin-left: auto;
            text-align: right;
        }
        
        .message-admin {
            background: white;
            margin-right: auto;
            border: 1px solid #e5e7eb;
        }
        
        .message-time {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        
        .send-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .navbar-brand span {
                font-size: 1.1rem !important;
            }
            .custom-navbar .nav-link {
                padding: 0.5rem 0.8rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .navbar-brand span {
                font-size: 1rem !important;
            }
        }
    </style>
</head>
<body>

<?php $active = 'messages'; ?>
<?php include 'includes/navbar_customer.php'; ?>

<div class="message-header">
    <div class="container">
        <h1 class="display-4 fw-bold"><i class="bi bi-chat-dots-fill me-3"></i>Message Admin</h1>
        <p class="lead">Communicate directly with our team</p>
    </div>
</div>

<div class="container mb-5">
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-chat-left-text me-2"></i>Conversation
                        </h5>
                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                    
                    <!-- Messages Container -->
                    <div class="message-container" id="messageContainer">
                        <?php
                        // Fetch messages first (so we can know which are unread before marking them read)
                        $stmt = $pdo->prepare("SELECT * FROM bulk_buyer_messages 
                                              WHERE user_id = :user_id 
                                              ORDER BY created_at ASC");
                        $stmt->execute([':user_id' => $_SESSION['user_id']]);
                        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        // Find if there are unread admin messages to place a divider before the first one
                        $shown_unread_divider = false;

                        if(empty($messages)):
                        ?>
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-chat" style="font-size: 3rem;"></i>
                                <p class="mt-3">No messages yet. Start a conversation!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($messages as $msg): ?>
                                <?php $is_unread_admin = ($msg['sender_type'] === 'admin' && (int)$msg['is_read'] === 0); ?>
                                <?php if($is_unread_admin && !$shown_unread_divider): $shown_unread_divider = true; ?>
                                    <div class="d-flex align-items-center my-3">
                                        <div class="flex-grow-1 border-top"></div>
                                        <span class="mx-3 small text-danger fw-bold">Unread</span>
                                        <div class="flex-grow-1 border-top"></div>
                                    </div>
                                <?php endif; ?>
                                <div class="message-item <?= $msg['sender_type'] == 'buyer' ? 'message-buyer' : 'message-admin' ?>">
                                    <div class="message-sender fw-bold mb-1 d-flex justify-content-between align-items-center">
                                        <span><?= $msg['sender_type'] == 'buyer' ? 'You' : 'Admin' ?></span>
                                        <?php if($is_unread_admin): ?>
                                            <span class="badge bg-danger">Unread</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-text">
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    </div>
                                    <div class="message-time">
                                        <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php
                    // Now that we've rendered, mark admin messages as read
                    $mark_read_stmt = $pdo->prepare("UPDATE bulk_buyer_messages 
                                                     SET is_read = 1 
                                                     WHERE user_id = :user_id 
                                                     AND sender_type = 'admin' 
                                                     AND is_read = 0");
                    $mark_read_stmt->execute([':user_id' => $_SESSION['user_id']]);
                    // Also mark related 'message' notifications as read for this user
                    try {
                        $mark_msg_notifs = $pdo->prepare("UPDATE notifications
                                                           SET is_read = 1
                                                           WHERE user_id = :user_id AND type = 'message' AND is_read = 0");
                        $mark_msg_notifs->execute([':user_id' => $_SESSION['user_id']]);
                    } catch (PDOException $e) {
                        error_log('Failed marking message notifications read: ' . $e->getMessage());
                    }
                    ?>
                    
                    <!-- Send Message Form -->
                    <div class="send-box">
                        <form action="backend/send_message.php" method="POST" id="messageForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Your Message</label>
                                <textarea name="message" class="form-control" rows="3" 
                                          placeholder="Type your message here..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Auto-scroll to bottom of messages
const messageContainer = document.getElementById('messageContainer');
if(messageContainer){
    messageContainer.scrollTop = messageContainer.scrollHeight;
}

// Removed auto-refresh to prevent erasing typed messages
// Users can manually refresh the page to see new messages
</script>

</body>
</html>
