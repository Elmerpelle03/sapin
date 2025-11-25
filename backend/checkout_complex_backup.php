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

    // Debug: Log the posted data
    file_put_contents('../debug_checkout.log', "POST data received at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    file_put_contents('../debug_checkout.log', "Payment method: $payment_method\n", FILE_APPEND);
    file_put_contents('../debug_checkout.log', "User ID: $user_id\n", FILE_APPEND);

    $proof_file_path = null;

    // ✅ Handle proof upload if GCash, BPI, or BDO is chosen
    if ($payment_method === 'GCash1' || $payment_method === 'GCash2' || $payment_method === 'BPI' || $payment_method === 'BDO') {
        file_put_contents('../debug_checkout.log', "Processing proof upload for payment method: $payment_method\n", FILE_APPEND);
        
        try {
        
        if (!isset($_FILES['proof_of_payment']) || $_FILES['proof_of_payment']['error'] !== UPLOAD_ERR_OK) {
            $payment_label = 'online payment';
            if ($payment_method === 'BPI') {
                $payment_label = 'BPI Bank Transfer';
            } elseif ($payment_method === 'BDO') {
                $payment_label = 'BDO Bank Transfer';
            } elseif ($payment_method === 'GCash1' || $payment_method === 'GCash2') {
                $payment_label = 'GCash';
            }
            
            $error_code = isset($_FILES['proof_of_payment']) ? $_FILES['proof_of_payment']['error'] : 'not set';
            file_put_contents('../debug_checkout.log', "File upload error code: $error_code\n", FILE_APPEND);
            
            echo json_encode(['success' => false, 'message' => "Proof of payment is required for $payment_label."]);
            exit();
        }

        $file = $_FILES['proof_of_payment'];
        file_put_contents('../debug_checkout.log', "File received: " . $file['name'] . " Size: " . $file['size'] . "\n", FILE_APPEND);
        
        // ✅ SECURITY: Validate file size (max 5MB)
        $max_file_size = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $max_file_size) {
            echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
            exit();
        }

        // ✅ SECURITY: Validate file is actually an image
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF images are allowed.']);
            exit();
        }

        // ✅ SECURITY: Verify image integrity
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid or corrupted image file.']);
            exit();
        }

        // ✅ SECURITY: Generate image hash for duplicate detection
        $image_hash = md5_file($file['tmp_name']);
        
        // Check if this exact image was used before (only if column exists)
        try {
            $stmt = $pdo->prepare("SELECT order_id FROM orders WHERE image_hash = :hash");
            $stmt->execute([':hash' => $image_hash]);
            $duplicate = $stmt->fetch();
            
            if ($duplicate) {
                echo json_encode(['success' => false, 'message' => 'This proof of payment has already been used for another order. Please upload a unique receipt.']);
                exit();
            }
        } catch (PDOException $e) {
            // Column doesn't exist yet, skip duplicate check
            file_put_contents('../debug_checkout.log', "Duplicate check skipped - column not found\n", FILE_APPEND);
        }

        $uploads_dir = '../uploads/proofs';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }

        // ✅ SECURITY: Sanitize filename and add timestamp
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'proof_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
        $target_path = $uploads_dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload proof of payment.']);
            exit();
        }

        // ✅ SECURITY: Add watermark to prevent reuse
        try {
            $watermarked_path = addWatermarkToProof($target_path, $user_id, $now);
            if ($watermarked_path) {
                $target_path = $watermarked_path;
            }
        } catch (Exception $e) {
            // Continue even if watermarking fails
            file_put_contents('../debug_checkout.log', "Watermark error: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        // ✅ SECURITY: Extract EXIF metadata for verification
        try {
            $exif_data = @exif_read_data($target_path);
            $metadata = json_encode([
                'upload_time' => $now,
                'file_size' => $file['size'],
                'dimensions' => $image_info[0] . 'x' . $image_info[1],
                'mime_type' => $mime_type,
                'exif_datetime' => isset($exif_data['DateTime']) ? $exif_data['DateTime'] : null,
                'exif_make' => isset($exif_data['Make']) ? $exif_data['Make'] : null,
                'exif_model' => isset($exif_data['Model']) ? $exif_data['Model'] : null,
                'image_hash' => $image_hash
            ]);
        } catch (Exception $e) {
            // If EXIF extraction fails, continue without metadata
            $metadata = json_encode([
                'upload_time' => $now,
                'file_size' => $file['size'],
                'dimensions' => $image_info[0] . 'x' . $image_info[1],
                'mime_type' => $mime_type,
                'image_hash' => $image_hash
            ]);
            file_put_contents('../debug_checkout.log', "EXIF extraction error: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        $proof_file_path = $target_path;
        file_put_contents('../debug_checkout.log', "Proof uploaded successfully: $target_path\n", FILE_APPEND);
        
        } catch (Exception $e) {
            // Catch any unexpected errors during proof upload
            file_put_contents('../debug_checkout.log', "CRITICAL ERROR in proof upload: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents('../debug_checkout.log', "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            echo json_encode(['success' => false, 'message' => 'Error processing proof of payment: ' . $e->getMessage()]);
            exit();
        }
    }

// ✅ SECURITY: Function to add watermark to proof image
function addWatermarkToProof($image_path, $user_id, $timestamp) {
    $image_info = getimagesize($image_path);
    $mime_type = $image_info['mime'];
    
    // Load image based on type
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($image_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($image_path);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($image_path);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // Get image dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Create semi-transparent watermark
    $watermark_color = imagecolorallocatealpha($image, 255, 255, 255, 50);
    $bg_color = imagecolorallocatealpha($image, 0, 0, 0, 80);
    
    // Watermark text
    $watermark_text = "Order #" . time() . " | User: " . $user_id;
    $timestamp_text = date('Y-m-d H:i:s', strtotime($timestamp));
    
    // Position at bottom right
    $font_size = 3;
    $text_width = imagefontwidth($font_size) * strlen($watermark_text);
    $text_height = imagefontheight($font_size);
    
    $x = $width - $text_width - 10;
    $y = $height - ($text_height * 3) - 10;
    
    // Add background rectangle
    imagefilledrectangle($image, $x - 5, $y - 5, $width - 5, $height - 5, $bg_color);
    
    // Add text
    imagestring($image, $font_size, $x, $y, $watermark_text, $watermark_color);
    imagestring($image, $font_size, $x, $y + $text_height + 2, $timestamp_text, $watermark_color);
    
    // Save watermarked image
    $watermarked_path = str_replace('.', '_watermarked.', $image_path);
    
    switch ($mime_type) {
        case 'image/jpeg':
        case 'image/jpg':
            imagejpeg($image, $watermarked_path, 90);
            break;
        case 'image/png':
            imagepng($image, $watermarked_path, 9);
            break;
        case 'image/gif':
            imagegif($image, $watermarked_path);
            break;
    }
    
    imagedestroy($image);
    
    // Delete original and rename watermarked
    if (file_exists($watermarked_path)) {
        unlink($image_path);
        return $watermarked_path;
    }
    
    return false;
}

    // ✅ Start transaction
    $pdo->beginTransaction();

    try {
        // 🛒 Get user's cart items
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

        // ✅ Check stock and deduct
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

        // ✅ Insert into orders (includes proof if GCash)
        file_put_contents('../debug_checkout.log', "About to insert order for user_id: $user_id\n", FILE_APPEND);
        
        // Check if metadata and image_hash columns exist, if not use default columns
        $has_metadata = false;
        $has_image_hash = false;
        try {
            $check_columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'proof_metadata'");
            $has_metadata = $check_columns->rowCount() > 0;
            
            $check_columns = $pdo->query("SHOW COLUMNS FROM orders LIKE 'image_hash'");
            $has_image_hash = $check_columns->rowCount() > 0;
        } catch (Exception $e) {
            // Columns don't exist, continue without them
        }
        
        if ($has_metadata && $has_image_hash) {
            $stmt = $pdo->prepare("INSERT INTO orders (
                    user_id, fullname, contact_number, region_id, province_id, municipality_id, barangay_id,
                    house, notes, payment_method, status, date, amount, shipping_fee, proof_of_payment, rider_id, seen, cancel_reason, proof_image, proof_metadata, image_hash
                ) VALUES (
                    :user_id, :fullname, :contact_number, :region_id, :province_id, :municipality_id, :barangay_id,
                    :house, :notes, :payment_method, 'Pending', :date, :amount, :shipping_fee, :proof, :rider_id, :seen, :cancel_reason, :proof_image, :metadata, :image_hash
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
                ':metadata'       => isset($metadata) ? $metadata : null,
                ':image_hash'     => isset($image_hash) ? $image_hash : null
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO orders (
                    user_id, fullname, contact_number, region_id, province_id, municipality_id, barangay_id,
                    house, notes, payment_method, status, date, amount, shipping_fee, proof_of_payment, rider_id, seen, cancel_reason, proof_image
                ) VALUES (
                    :user_id, :fullname, :contact_number, :region_id, :province_id, :municipality_id, :barangay_id,
                    :house, :notes, :payment_method, 'Pending', :date, :amount, :shipping_fee, :proof, :rider_id, :seen, :cancel_reason, :proof_image
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
                ':proof_image'    => ''
            ]);
        }

        file_put_contents('../debug_checkout.log', "Order inserted successfully\n", FILE_APPEND);
        $order_id = $pdo->lastInsertId();

        // ✅ Insert each item into order_items
        $sql = "INSERT INTO order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)";
        $stmt = $pdo->prepare($sql);
        foreach ($cart_data as $row) {
            $stmt->execute([
                ':order_id'   => $order_id,
                ':product_id' => $row['product_id'],
                ':quantity'   => $row['quantity']
            ]);
        }

        // ✅ Clear the cart (only selected items if specified)
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
        // Debug logging
        file_put_contents('../debug_checkout.log', "Checkout error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage() . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Unexpected error. Please try again.']);
        exit();
    }
}
?>
