<?php
// Fetch unread notifications count
$unread_count = 0;
$notifications = [];
$admin_msg_notifications = [];

if (isset($_SESSION['user_id'])) {
    try {
        $notif_count_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM notifications 
            WHERE user_id = :user_id AND is_read = 0
        ");
        $notif_count_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $unread_count = (int)$notif_count_stmt->fetchColumn();

        // Count unread admin messages for this buyer and add to badge count
        $unread_msgs_stmt = $pdo->prepare("
            SELECT COUNT(*) FROM bulk_buyer_messages
            WHERE user_id = :user_id AND sender_type = 'admin' AND is_read = 0
        ");
        $unread_msgs_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $unread_count += (int)$unread_msgs_stmt->fetchColumn();

        // Fetch recent notifications (last 10)
        $notif_stmt = $pdo->prepare("
            SELECT * 
            FROM notifications
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $notif_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch recent admin messages and map them into notification-like records
        $msg_stmt = $pdo->prepare("
            SELECT message_id, message, is_read, created_at
            FROM bulk_buyer_messages
            WHERE user_id = :user_id AND sender_type = 'admin'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $msg_stmt->execute([':user_id' => $_SESSION['user_id']]);
        $admin_msgs = $msg_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($admin_msgs as $m) {
            $admin_msg_notifications[] = [
                'notification_id' => 'msg_' . $m['message_id'],
                'order_id' => 0,
                'title' => 'New message from Admin',
                'message' => mb_substr($m['message'] ?? '', 0, 140),
                'type' => 'message',
                'is_read' => (int)$m['is_read'],
                'created_at' => $m['created_at']
            ];
        }

        // Merge and sort by created_at desc, then slice top 10
        $merged = array_merge($notifications, $admin_msg_notifications);
        usort($merged, function($a, $b){
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });
        $notifications = array_slice($merged, 0, 10);
    } catch (PDOException $e) {
        error_log("Notifications error: " . $e->getMessage());
    }
}
?>

<!-- Notifications Dropdown -->
<li class="nav-item dropdown">
    <a class="nav-link position-relative d-flex align-items-center" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 0.5rem 0.75rem;">
        <i class="bi bi-bell fs-5"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute badge rounded-pill bg-danger" style="font-size: 0.65rem; top: 2px; left: 60%;">
                <?= $unread_count > 9 ? '9+' : $unread_count ?>
                <span class="visually-hidden">unread notifications</span>
            </span>
        <?php endif; ?>
    </a>
    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 500px; overflow-y: auto;">
        <li class="dropdown-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Notifications</span>
            <?php if ($unread_count > 0): ?>
                <a href="javascript:void(0)" onclick="markAllAsRead()" class="text-primary text-decoration-none small">Mark all as read</a>
            <?php endif; ?>
        </li>
        <li><hr class="dropdown-divider"></li>
        
        <?php if (empty($notifications)): ?>
            <li class="text-center py-4 text-muted">
                <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                <small>No notifications yet</small>
            </li>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <?php 
                    $isMessage = isset($notif['type']) && $notif['type'] === 'message';
                    $linkHref = $isMessage ? 'message_admin.php' : ('orders.php?highlight=' . urlencode($notif['order_id']) . '&notif_id=' . urlencode($notif['notification_id']));
                ?>
                <li>
                    <a class="dropdown-item notification-item <?= $notif['is_read'] ? '' : 'unread' ?>" 
                       href="<?= $linkHref ?>"
                       style="white-space: normal; padding: 12px 16px;">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <?php if ($isMessage): ?>
                                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-chat-dots-fill"></i>
                                    </div>
                                <?php elseif ($notif['type'] === 'success'): ?>
                                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-check-circle-fill"></i>
                                    </div>
                                <?php elseif ($notif['type'] === 'warning'): ?>
                                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-info-circle-fill"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1 <?= $notif['is_read'] ? 'text-muted' : 'fw-bold' ?>" style="font-size: 0.9rem;">
                                    <?= htmlspecialchars($notif['title']) ?>
                                </h6>
                                <p class="mb-1 text-muted small" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($notif['message']) ?>
                                </p>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    <i class="bi bi-clock me-1"></i><?= timeAgo($notif['created_at']) ?>
                                </small>
                            </div>
                        </div>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
            <?php endforeach; ?>
            <li class="text-center py-2">
                <a href="orders.php" class="text-primary text-decoration-none small fw-bold">View All Orders</a>
            </li>
        <?php endif; ?>
    </ul>
</li>

<style>
.notification-dropdown {
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    border: none;
    border-radius: 12px;
}

.notification-item {
    transition: background-color 0.2s ease;
    border-left: 3px solid transparent;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #f0f7ff;
    border-left-color: #2563eb;
}

.notification-item.unread:hover {
    background-color: #e6f2ff;
}

.dropdown-header {
    padding: 12px 16px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px 12px 0 0;
}

.dropdown-header a {
    color: white !important;
    opacity: 0.9;
}

.dropdown-header a:hover {
    opacity: 1;
}
</style>

<script>
function markAllAsRead() {
    fetch('backend/mark_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'mark_all' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<?php
// Helper function to display time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
?>
