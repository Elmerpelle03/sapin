<?php
require '../../config/db.php';
require '../../config/session_courier.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $status = $_POST['status'] ?? null;
    $rider_id = $_POST['rider_id'] ?? null;
    $cancel_reason = $_POST['cancel_reason'] ?? null;

    if (empty($order_id) || empty($status)) {
        $_SESSION['error_message'] = "Order ID and status are required!";
        header('Location: ../view_order.php?order_id=' . urlencode($order_id));
        exit;
    }

    $valid_statuses = ['Pending', 'Processing', 'Shipping', 'Delivered', 'Cancelled', 'Received'];
    if (!in_array($status, $valid_statuses)) {
        $_SESSION['error_message'] = "Invalid status selected!";
        header('Location: ../view_order.php?order_id=' . urlencode($order_id));
        exit;
    }

    // Initialize optional variables
    $proof_image_name = null;

    // Handle file if status is Delivered
    if ($status === 'Delivered') {
        if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
            $error_code = $_FILES['proof_image']['error'] ?? 'unknown';
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            ];
            $error_msg = $error_messages[$error_code] ?? "Upload error code: $error_code";
            $_SESSION['error_message'] = "Proof of delivery image is required. Error: $error_msg";
            header('Location: ../view_order.php?order_id=' . urlencode($order_id));
            exit;
        }

        $image = $_FILES['proof_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($image['type'], $allowedTypes)) {
            $_SESSION['error_message'] = "Only JPG, PNG, or WEBP images are allowed. File type: " . $image['type'];
            header('Location: ../view_order.php?order_id=' . urlencode($order_id));
            exit;
        }

        // Check file size (max 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ($image['size'] > $max_size) {
            $_SESSION['error_message'] = "File size too large. Maximum allowed size is 5MB. Your file: " . round($image['size'] / 1024 / 1024, 2) . "MB";
            header('Location: ../view_order.php?order_id=' . urlencode($order_id));
            exit;
        }

        // Create upload path
        $image_ext = pathinfo($image['name'], PATHINFO_EXTENSION);
        $proof_image_name = uniqid('proof_', true) . '.' . $image_ext;
        $upload_path = '../../uploads/deliveries/' . $proof_image_name;

        if (!is_dir('../../uploads/deliveries')) {
            if (!mkdir('../../uploads/deliveries', 0775, true)) {
                $_SESSION['error_message'] = "Failed to create upload directory. Please check folder permissions.";
                header('Location: ../view_order.php?order_id=' . urlencode($order_id));
                exit;
            }
        }

        if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
            $_SESSION['error_message'] = "Failed to upload proof image. Please check folder permissions and try again. Temp file: " . $image['tmp_name'] . ", Target: " . $upload_path;
            header('Location: ../view_order.php?order_id=' . urlencode($order_id));
            exit;
        }
    }

    // Build update query and handle materials decrement on Delivered
    try {
        // For Delivered, wrap in transaction and ensure idempotency
        if ($status === 'Delivered') {
            $pdo->beginTransaction();

            // Check current status to avoid double-decrement
            $checkStmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = :order_id FOR UPDATE");
            $checkStmt->execute([':order_id' => $order_id]);
            $current = $checkStmt->fetchColumn();

            // Build update
            $query = "UPDATE orders SET status = :status";
            $params = [
                ':status' => $status,
                ':order_id' => $order_id
            ];
            if (!empty($rider_id)) {
                $query .= ", rider_id = :rider_id";
                $params[':rider_id'] = $rider_id;
            }
            if ($proof_image_name) {
                $query .= ", proof_image = :proof_image";
                $params[':proof_image'] = $proof_image_name;
            }
            $query .= " WHERE order_id = :order_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            // Only decrement materials if not previously Delivered/Received
            if (!in_array(strtolower((string)$current), ['delivered','received'])) {
                // Sum quantities per material name from order items
                $sumStmt = $pdo->prepare("SELECT LOWER(TRIM(p.material)) AS mat_name, SUM(oi.quantity) AS total_qty
                    FROM order_items oi
                    JOIN products p ON p.product_id = oi.product_id
                    WHERE oi.order_id = :order_id
                    GROUP BY LOWER(TRIM(p.material))");
                $sumStmt->execute([':order_id' => $order_id]);
                $materialsToDeduct = $sumStmt->fetchAll(PDO::FETCH_ASSOC);

                if ($materialsToDeduct) {
                    foreach ($materialsToDeduct as $row) {
                        if (!$row['mat_name']) { continue; }
                        // Update materials table by matching material_name (case-insensitive)
                        $upd = $pdo->prepare("UPDATE materials SET stock = GREATEST(stock - :qty, 0) WHERE LOWER(TRIM(material_name)) = :mat_name");
                        $upd->execute([
                            ':qty' => (int)$row['total_qty'],
                            ':mat_name' => $row['mat_name']
                        ]);
                    }
                }
            }

            // Create notification for customer
            $order_info_stmt = $pdo->prepare("SELECT user_id, amount FROM orders WHERE order_id = :order_id");
            $order_info_stmt->execute([':order_id' => $order_id]);
            $order_info = $order_info_stmt->fetch(PDO::FETCH_ASSOC);

            if ($order_info) {
                $notification_title = "Order Delivered!";
                $notification_message = "Your order #" . $order_id . " has been successfully delivered. Total: ₱" . number_format($order_info['amount'], 2);
                
                $notif_stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, order_id, title, message, type, is_read) 
                    VALUES (:user_id, :order_id, :title, :message, 'success', 0)
                ");
                $notif_stmt->execute([
                    ':user_id' => $order_info['user_id'],
                    ':order_id' => $order_id,
                    ':title' => $notification_title,
                    ':message' => $notification_message
                ]);

                // Create notification for all admins (usertype_id 1 and 5)
                $admin_stmt = $pdo->prepare("SELECT user_id FROM users WHERE usertype_id IN (1, 5)");
                $admin_stmt->execute();
                $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);

                $admin_notif_title = "Order Delivered - Review Proof";
                $admin_notif_message = "Order #" . $order_id . " has been marked as delivered by courier. Please review the delivery proof.";
                
                foreach ($admins as $admin) {
                    $admin_notif_stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, order_id, title, message, type, is_read) 
                        VALUES (:user_id, :order_id, :title, :message, 'info', 0)
                    ");
                    $admin_notif_stmt->execute([
                        ':user_id' => $admin['user_id'],
                        ':order_id' => $order_id,
                        ':title' => $admin_notif_title,
                        ':message' => $admin_notif_message
                    ]);
                }
            }

            $pdo->commit();
        } else {
            // Non-delivered statuses: simple update
            $query = "UPDATE orders SET status = :status";
            $params = [
                ':status' => $status,
                ':order_id' => $order_id
            ];
            if ($status === 'Shipping' && !empty($rider_id)) {
                $query .= ", rider_id = :rider_id";
                $params[':rider_id'] = $rider_id;
            }
            if ($status === 'Cancelled') {
                $query .= ", cancel_reason = :cancel_reason";
                $params[':cancel_reason'] = $cancel_reason ?? '';
            }
            $query .= " WHERE order_id = :order_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        }

        $_SESSION['success_message'] = "Order status updated successfully.";
        header('Location: ../view_order.php?order_id=' . urlencode($order_id));
        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['error_message'] = "Unexpected error: " . $e->getMessage();
        header('Location: ../view_order.php?order_id=' . urlencode($order_id));
        exit;
    }
}
?>
