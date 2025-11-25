# 🔄 Order Cancellation Request System - Implementation Guide

## 📋 Overview
This system allows customers to request order cancellation (even for "Preparing" status) with admin approval workflow.

---

## 🗄️ Database Setup

### Step 1: Run SQL Files (in order)
1. **`database/update_order_status_enum.sql`** - Adds 'cancellation_pending' status
2. **`database/create_cancellation_system.sql`** - Creates tables

---

## 📊 Database Schema

### `cancellation_requests` Table
```sql
- cancellation_id (PK)
- order_id (FK)
- user_id (FK)
- reason (TEXT) - Customer's cancellation reason
- status (ENUM: pending, approved, rejected)
- admin_response (TEXT) - Admin's reason for approval/rejection
- admin_id (FK) - Admin who handled the request
- requested_at (DATETIME)
- responded_at (DATETIME)
```

### `notifications` Table
```sql
- notification_id (PK)
- user_id (FK)
- type (ENUM: order, cancellation_approved, cancellation_rejected, general)
- title (VARCHAR)
- message (TEXT)
- link (VARCHAR) - Link to order_details.php
- is_read (BOOLEAN)
- created_at (TIMESTAMP)
```

### Order Status Flow
```
pending → preparing → cancellation_pending → cancelled (if approved)
                   ↓                       ↓
                   → shipped → completed   → preparing (if rejected)
```

---

## 🔧 Backend Files Created

### 1. **`backend/request_cancellation.php`**
- Customer submits cancellation request
- Validates order ownership
- Checks if order can be cancelled
- Creates cancellation request
- Updates order status to 'cancellation_pending'

### 2. **`admin/backend/handle_cancellation.php`**
- Admin approves or rejects request
- Updates cancellation request status
- Updates order status (cancelled or back to preparing)
- Creates notification for customer

### 3. **`admin/cancellation_requests.php`**
- Admin dashboard to view all requests
- Shows pending, approved, and rejected requests
- Approve/Reject buttons with reason input
- Links to view full order details

---

## 🎯 Workflow

### Customer Side:
```
1. Customer views order in order_details.php
2. Clicks "Request Cancellation" button
3. Enters cancellation reason
4. Submits request
5. Order status changes to "Cancellation Pending"
6. Waits for admin response
7. Receives notification when admin responds
8. Clicks notification → goes to order_details.php
9. Sees approval/rejection with admin's reason
```

### Admin Side:
```
1. Admin goes to cancellation_requests.php
2. Sees all pending cancellation requests
3. Reviews customer's reason
4. Clicks "Approve" or "Reject"
5. Enters reason for decision
6. Submits response
7. System updates order status:
   - Approved → status = 'cancelled'
   - Rejected → status = 'preparing'
8. Customer receives notification
```

---

## 📱 Customer Features to Implement

### Update `order_details.php`:

#### 1. Show Cancellation Request Button
```php
<?php if ($order['status'] === 'preparing' || $order['status'] === 'pending'): ?>
    <button class="btn btn-warning" onclick="requestCancellation(<?php echo $order['order_id']; ?>)">
        <i class="bi bi-x-circle"></i> Request Cancellation
    </button>
<?php endif; ?>
```

#### 2. Show Cancellation Status
```php
<?php
// Get cancellation request if exists
$stmt = $pdo->prepare("SELECT * FROM cancellation_requests WHERE order_id = :order_id ORDER BY requested_at DESC LIMIT 1");
$stmt->execute([':order_id' => $order_id]);
$cancellation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($cancellation):
?>
    <div class="alert alert-<?php 
        echo $cancellation['status'] === 'pending' ? 'warning' : 
            ($cancellation['status'] === 'approved' ? 'success' : 'danger'); 
    ?>">
        <h5>Cancellation Request: <?php echo ucfirst($cancellation['status']); ?></h5>
        <p><strong>Your Reason:</strong> <?php echo nl2br(htmlspecialchars($cancellation['reason'])); ?></p>
        
        <?php if ($cancellation['status'] !== 'pending'): ?>
            <hr>
            <p><strong>Admin Response:</strong> <?php echo nl2br(htmlspecialchars($cancellation['admin_response'])); ?></p>
            <small>Responded: <?php echo date('M d, Y h:i A', strtotime($cancellation['responded_at'])); ?></small>
        <?php endif; ?>
    </div>
<?php endif; ?>
```

#### 3. JavaScript for Request Cancellation
```javascript
function requestCancellation(orderId) {
    Swal.fire({
        title: 'Request Order Cancellation',
        html: `
            <p>Please provide a reason for cancelling this order:</p>
            <textarea id="cancellation-reason" class="form-control" rows="4" 
                placeholder="e.g., Changed my mind, Found a better deal, etc."></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Submit Request',
        confirmButtonColor: '#dc3545',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const reason = document.getElementById('cancellation-reason').value.trim();
            if (!reason) {
                Swal.showValidationMessage('Please provide a reason');
                return false;
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('reason', result.value);

            fetch('backend/request_cancellation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Request Submitted',
                        text: data.message,
                        timer: 2000
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            });
        }
    });
}
```

---

## 🔔 Notification System

### Create `notifications.php` (Customer Side):
```php
<?php
require('config/session.php');
require('config/db.php');

$user_id = $_SESSION['user_id'];

// Get unread notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC");
$stmt->execute([':user_id' => $user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notification count
$count = count($notifications);
?>

<!-- Notification Bell Icon -->
<div class="dropdown">
    <button class="btn btn-link position-relative" data-bs-toggle="dropdown">
        <i class="bi bi-bell fs-4"></i>
        <?php if ($count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $count; ?>
            </span>
        <?php endif; ?>
    </button>
    
    <ul class="dropdown-menu dropdown-menu-end" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <?php if (empty($notifications)): ?>
            <li class="dropdown-item text-muted">No new notifications</li>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <li>
                    <a class="dropdown-item" href="<?php echo $notif['link']; ?>" 
                       onclick="markAsRead(<?php echo $notif['notification_id']; ?>)">
                        <strong><?php echo htmlspecialchars($notif['title']); ?></strong>
                        <p class="mb-0 small"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <small class="text-muted"><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></small>
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

<script>
function markAsRead(notificationId) {
    fetch('backend/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'notification_id=' + notificationId
    });
}
</script>
```

### Create `backend/mark_notification_read.php`:
```php
<?php
require('../config/session.php');
require('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = $_POST['notification_id'] ?? null;
    $user_id = $_SESSION['user_id'];

    if ($notification_id) {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $notification_id, ':user_id' => $user_id]);
        echo json_encode(['success' => true]);
    }
}
?>
```

---

## 📦 Files to Upload to Hostinger

### Database:
- `database/update_order_status_enum.sql` (run in phpMyAdmin)
- `database/create_cancellation_system.sql` (run in phpMyAdmin)

### Backend:
- `backend/request_cancellation.php`
- `backend/mark_notification_read.php` (create this)

### Admin:
- `admin/cancellation_requests.php`
- `admin/backend/handle_cancellation.php`

### Customer Side (need to update):
- `order_details.php` (add cancellation UI)
- `navbar.php` or `header.php` (add notification bell)

---

## ✅ Testing Checklist

### Customer Side:
- [ ] Can request cancellation for "pending" orders
- [ ] Can request cancellation for "preparing" orders
- [ ] Cannot request cancellation for "shipped" orders
- [ ] Cannot request cancellation for "completed" orders
- [ ] Cannot submit empty cancellation reason
- [ ] Cannot submit duplicate cancellation requests
- [ ] Sees "Cancellation Pending" status after request
- [ ] Receives notification when admin responds
- [ ] Notification click goes to order_details.php
- [ ] Sees admin's approval/rejection reason

### Admin Side:
- [ ] Sees all pending cancellation requests
- [ ] Can approve with reason
- [ ] Can reject with reason
- [ ] Cannot submit empty admin response
- [ ] Order status updates correctly after approval (cancelled)
- [ ] Order status updates correctly after rejection (preparing)
- [ ] Customer receives notification after admin action
- [ ] Can view full order details from cancellation page

---

## 🎨 Status Badge Colors

```php
<?php
function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-secondary',
        'preparing' => 'bg-info',
        'cancellation_pending' => 'bg-warning',
        'shipped' => 'bg-primary',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger'
    ];
    return $badges[$status] ?? 'bg-secondary';
}
?>
```

---

## 🚀 Next Steps

1. **Run SQL migrations** in phpMyAdmin
2. **Test locally** with sample orders
3. **Update order_details.php** with cancellation UI
4. **Add notification bell** to navbar
5. **Test complete workflow**
6. **Upload to Hostinger**

---

## 📝 Notes

- Cancellation requests are **permanent records** (not deleted)
- Admin responses are **required** for transparency
- Notifications ensure customers are **always informed**
- System prevents **duplicate cancellation requests**
- Rejected orders can **continue normal flow**

---

## 🎉 Result

A complete cancellation request system with:
✅ Customer can request cancellation with reason
✅ Admin approval/rejection workflow
✅ Notification system
✅ Full audit trail
✅ Transparent communication
