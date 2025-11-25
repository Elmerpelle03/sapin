<?php 
require ('../config/session.php');
require ('../config/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname        = $_POST['fullname'];
    $contact_number  = $_POST['contact_number'];
    $region_id       = $_POST['region_id'];
    $province_id     = $_POST['province_id'];
    $municipality_id = $_POST['municipality_id'];
    $barangay_id     = $_POST['barangay_id'];
    $house           = $_POST['house'];
    $notes           = $_POST['notes'];
    $payment_method  = $_POST['payment_method'];
    $shipping_fee    = $_POST['shipping_fee'];
    $user_id         = $_SESSION['user_id'];
    $now             = date("Y-m-d H:i:s");

    $proof_file_path = null;
    $image_hash = null;
    $metadata = null;

    // ✅ MODERATE: Handle proof upload with duplicate detection
    if ($payment_method === 'GCash1' || $payment_method === 'GCash2' || $payment_method === 'BPI' || $payment_method === 'BDO') {
        
        if (!isset($_FILES['proof_of_payment']) || $_FILES['proof_of_payment']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Please upload proof of payment.']);
            exit();
        }

        $file = $_FILES['proof_of_payment'];
        
        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum 10MB.']);
            exit();
        }

        // Validate file type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images allowed.']);
            exit();
        }

        // ✅ SECURITY: Generate hash for duplicate detection
        $image_hash = md5_file($file['tmp_name']);
        
        // Check if this image was already used
        $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE image_hash = :hash");
        $stmt->execute([':hash' => $image_hash]);
        $duplicate = $stmt->fetch();
        
        if ($duplicate) {
            echo json_encode(['success' => false, 'message' => 'This proof of payment has already been used. Please upload a unique receipt.']);
            exit();
        }

        // ✅ SECURITY: Store metadata
        $metadata = json_encode([
            'upload_time' => $now,
            'file_size' => $file['size'],
            'original_name' => $file['name'],
            'user_id' => $user_id
        ]);

        // Create upload directory
        $uploads_dir = '../uploads/proofs';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }

        // Generate unique filename
        $filename = 'proof_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $uploads_dir . '/' . $filename;

        // Upload file
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
            exit();
        }

        $proof_file_path = $target_path;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Get cart items
        $selected_ids = isset($_POST['selected_cart_ids']) ? $_POST['selected_cart_ids'] : [];
        if (!empty($selected_ids)) {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
            $stmt->execute(array_merge([$user_id], $selected_ids));
        } else {
            $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
        }
        $cart_data = $stmt->fetchAll();

        if (!$cart_data) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
            exit();
        }

        $total_amount = 0;

        // Check stock and deduct
        foreach ($cart_data as $row) {
            $stmt = $pdo->prepare("SELECT price, stock FROM products WHERE product_id = :product_id");
            $stmt->execute([':product_id' => $row['product_id']]);
            $product = $stmt->fetch();

            if ($product['stock'] < $row['quantity']) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Insufficient stock for some products.']);
                exit();
            }

            $total_amount += $product['price'] * $row['quantity'];

            $stmt = $pdo->prepare("UPDATE products SET stock = stock - :quantity WHERE product_id = :product_id");
            $stmt->execute([
                ':quantity' => $row['quantity'],
                ':product_id' => $row['product_id']
            ]);
        }

        // Insert order with security columns
        $stmt = $pdo->prepare("INSERT INTO orders (
                user_id, fullname, contact_number, region_id, province_id, municipality_id, barangay_id,
                house, notes, payment_method, status, date, amount, shipping_fee, proof_of_payment, 
                rider_id, seen, cancel_reason, proof_image, image_hash, proof_metadata
            ) VALUES (
                :user_id, :fullname, :contact_number, :region_id, :province_id, :municipality_id, :barangay_id,
                :house, :notes, :payment_method, 'Pending', :date, :amount, :shipping_fee, :proof, 
                :rider_id, :seen, :cancel_reason, :proof_image, :image_hash, :metadata
            )");

        $stmt->execute([
            ':user_id'        => $user_id,
            ':fullname'       => $fullname,
            ':contact_number' => $contact_number,
            ':region_id'      => $region_id,
            ':province_id'    => $province_id,
            ':municipality_id'=> $municipality_id,
            ':barangay_id'    => $barangay_id,
            ':house'          => $house,
            ':notes'          => $notes,
            ':payment_method' => $payment_method,
            ':date'           => $now,
            ':amount'         => $total_amount,
            ':shipping_fee'   => $shipping_fee,
            ':proof'          => $proof_file_path,
            ':rider_id'       => 0,
            ':seen'           => 0,
            ':cancel_reason'  => '',
            ':proof_image'    => '',
            ':image_hash'     => $image_hash,
            ':metadata'       => $metadata
        ]);

        $order_id = $pdo->lastInsertId();

        // Insert order items
        $sql = "INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)";
        $stmt = $pdo->prepare($sql);
        foreach ($cart_data as $row) {
            $stmt->execute([
                ':order_id'   => $order_id,
                ':product_id' => $row['product_id'],
                ':quantity'   => $row['quantity']
            ]);
        }

        // Clear cart
        if (!empty($selected_ids)) {
            $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND cart_id IN ($placeholders)");
            $stmt->execute(array_merge([$user_id], $selected_ids));
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully!',
            'amount'  => number_format($total_amount, 2)
        ]);
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
}
?>
