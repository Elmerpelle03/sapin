<?php 
    require ('../config/session_admin.php');
    require ('../config/db.php');
    
    if(isset($_SESSION['success_message'])){
        $success_message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
    }
    elseif(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    
    // Get selected conversation
    $selected_user_id = $_GET['user_id'] ?? null;
    
    // Get all wholesalers with messages
    $buyers_stmt = $pdo->query("
        SELECT DISTINCT 
            u.user_id,
            u.username,
            CONCAT(ud.firstname, ' ', ud.lastname) as full_name,
            (SELECT COUNT(*) FROM bulk_buyer_messages 
             WHERE user_id = u.user_id AND sender_type = 'buyer' AND is_read = 0) as unread_count,
            (SELECT created_at FROM bulk_buyer_messages 
             WHERE user_id = u.user_id 
             ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM users u
        JOIN userdetails ud ON u.user_id = ud.user_id
        WHERE u.usertype_id = 3 
        AND EXISTS (SELECT 1 FROM bulk_buyer_messages WHERE user_id = u.user_id)
        ORDER BY last_message_time DESC
    ");
    $buyers = $buyers_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get messages for selected user
    $messages = [];
    if($selected_user_id){
        $msg_stmt = $pdo->prepare("
            SELECT * FROM bulk_buyer_messages 
            WHERE user_id = :user_id 
            ORDER BY created_at ASC
        ");
        $msg_stmt->execute([':user_id' => $selected_user_id]);
        $messages = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark messages as read
        $pdo->prepare("UPDATE bulk_buyer_messages SET is_read = 1 
                       WHERE user_id = :user_id AND sender_type = 'buyer'")
            ->execute([':user_id' => $selected_user_id]);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template based on Bootstrap 5">
    <meta name="author" content="AdminKit">
    <meta name="keywords" content="adminkit, bootstrap, bootstrap 5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">
    
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="shortcut icon" href="img/icons/icon-48x48.png" />
    
    <link rel="canonical" href="https://demo-basic.adminkit.io/pages-blank.html" />
    
    <title>Wholesaler Messages - Admin</title>
    
    <link href="css/app.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { background-color: #f7f9fc; }
        .page-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 2rem;
            border-radius: 14px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 24px rgba(245, 158, 11, 0.2);
        }
        
        .page-header h1 {
            font-weight: 700;
            margin: 0;
            font-size: 1.75rem;
        }
        
        .page-header p {
            opacity: 0.9;
        }
        
        .conversation-list {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .conversation-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .conversation-item:hover {
            background: #f3f4f6;
        }
        
        .conversation-item.active {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        
        .message-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .message-item {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 12px;
            max-width: 70%;
        }
        
        .message-buyer {
            background: white;
            margin-right: auto;
            border: 1px solid #e5e7eb;
        }
        
        .message-admin {
            background: #fef3c7;
            margin-left: auto;
            text-align: right;
            border: 1px solid #fbbf24;
        }
        
        .unread-badge {
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .card { 
            border: none; 
            border-radius: 14px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.06); 
        }
        
        .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            border-radius: 14px 14px 0 0 !important;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php $active = 'bulk_messages'; ?>
        <?php require ('../includes/sidebar_admin.php');?>
        
        <div class="main">
            <?php require ('../includes/navbar_admin.php');?>
            
            <main class="content">
                <div class="container-fluid p-0">
                    
                    <div class="page-header">
                        <h1>
                            <i class="bi bi-chat-dots-fill me-2"></i>Wholesaler Messages
                        </h1>
                        <p class="mb-0 mt-2" style="opacity: 0.9;">Communicate with your wholesalers</p>
                    </div>
                    
                    <?php if(isset($success_message)): ?>
                        <div class="alert alert-success alert-dismissible">
                            <?= $success_message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Conversations List -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Conversations</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="conversation-list">
                                        <?php if(empty($buyers)): ?>
                                            <div class="text-center text-muted p-4">
                                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                                <p class="mt-2">No messages yet</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach($buyers as $buyer): ?>
                                                <a href="?user_id=<?= $buyer['user_id'] ?>" 
                                                   class="conversation-item <?= $selected_user_id == $buyer['user_id'] ? 'active' : '' ?> text-decoration-none text-dark d-block">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($buyer['full_name']) ?></div>
                                                            <small class="text-muted">@<?= htmlspecialchars($buyer['username']) ?></small>
                                                        </div>
                                                        <?php if($buyer['unread_count'] > 0): ?>
                                                            <span class="unread-badge"><?= $buyer['unread_count'] ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= $buyer['last_message_time'] ? date('M d, h:i A', strtotime($buyer['last_message_time'])) : '' ?>
                                                    </small>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <?php if($selected_user_id): ?>
                                            <?php
                                            $selected_buyer = array_filter($buyers, fn($b) => $b['user_id'] == $selected_user_id);
                                            $selected_buyer = reset($selected_buyer);
                                            ?>
                                            Conversation with <?= htmlspecialchars($selected_buyer['full_name'] ?? 'Buyer') ?>
                                        <?php else: ?>
                                            Select a conversation
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if(!$selected_user_id): ?>
                                        <div class="text-center text-muted py-5">
                                            <i class="bi bi-chat-left-text" style="font-size: 4rem;"></i>
                                            <p class="mt-3">Select a conversation from the left to view messages</p>
                                        </div>
                                    <?php else: ?>
                                        <!-- Messages Container -->
                                        <div class="message-container mb-3" id="messageContainer">
                                            <?php $shown_unread_divider = false; ?>
                                            <?php foreach($messages as $msg): ?>
                                                <?php $is_unread = ($msg['sender_type'] == 'buyer' && (int)$msg['is_read'] === 0); ?>
                                                <?php if($is_unread && !$shown_unread_divider): $shown_unread_divider = true; ?>
                                                    <div class="d-flex align-items-center my-3">
                                                        <div class="flex-grow-1 border-top"></div>
                                                        <span class="mx-3 small text-danger fw-bold">Unread</span>
                                                        <div class="flex-grow-1 border-top"></div>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="message-item <?= $msg['sender_type'] == 'buyer' ? 'message-buyer' : 'message-admin' ?>">
                                                    <div class="fw-bold mb-1 d-flex align-items-center justify-content-between">
                                                        <span><?= $msg['sender_type'] == 'buyer' ? $selected_buyer['full_name'] : 'You (Admin)' ?></span>
                                                        <?php if($is_unread): ?>
                                                            <span class="badge bg-danger">Unread</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                                    <small class="text-muted d-block mt-2">
                                                        <?= date('M d, Y h:i A', strtotime($msg['created_at'])) ?>
                                                    </small>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <!-- Reply Form -->
                                        <form action="backend/reply_message.php" method="POST">
                                            <input type="hidden" name="user_id" value="<?= $selected_user_id ?>">
                                            <div class="mb-3">
                                                <textarea name="message" class="form-control" rows="3" 
                                                          placeholder="Type your reply..." required></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-send me-2"></i>Send Reply
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>
    
    <script src="js/app.js"></script>
    <script>
        // Auto-scroll to bottom
        const container = document.getElementById('messageContainer');
        if(container){
            container.scrollTop = container.scrollHeight;
        }
    </script>
</body>
</html>
