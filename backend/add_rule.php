<?php
require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule_name = $_POST['rule_name'] ?? '';
    $fee = $_POST['fee'] ?? 0;
    $region_id = $_POST['region_id'] ?? null;
    $province_id = $_POST['province_id'] ?? null;
    $municipality_id = $_POST['municipality_id'] ?? null;
    $barangay_id = $_POST['barangay_id'] ?? null;

    // Convert empty strings to NULL for optional fields (do this early)
    $province_id = !empty($province_id) ? $province_id : null;
    $municipality_id = !empty($municipality_id) ? $municipality_id : null;
    $barangay_id = !empty($barangay_id) ? $barangay_id : null;

    // Validate required fields
    if (empty($rule_name) || empty($fee) || empty($region_id)) {
        $_SESSION['error_message'] = "Please fill out all required fields.";
        header("Location: ../shipping.php");
        exit;
    }

    try {
        // Check for duplicate rule - simplified query
        $check_sql = "SELECT COUNT(*) FROM shipping_rules WHERE region_id = :region_id";
        $check_params = [':region_id' => $region_id];
        
        if ($province_id !== null) {
            $check_sql .= " AND province_id = :province_id";
            $check_params[':province_id'] = $province_id;
        } else {
            $check_sql .= " AND province_id IS NULL";
        }
        
        if ($municipality_id !== null) {
            $check_sql .= " AND municipality_id = :municipality_id";
            $check_params[':municipality_id'] = $municipality_id;
        } else {
            $check_sql .= " AND municipality_id IS NULL";
        }
        
        if ($barangay_id !== null) {
            $check_sql .= " AND barangay_id = :barangay_id";
            $check_params[':barangay_id'] = $barangay_id;
        } else {
            $check_sql .= " AND barangay_id IS NULL";
        }
        
        $check = $pdo->prepare($check_sql);
        $check->execute($check_params);

        if ($check->fetchColumn() > 0) {
            $_SESSION['error_message'] = "A shipping rule with the same location already exists.";
            header("Location: ../shipping.php");
            exit;
        }

        // Insert the new rule
        $stmt = $pdo->prepare("
            INSERT INTO shipping_rules (rule_name, shipping_fee, region_id, province_id, municipality_id, barangay_id)
            VALUES (:rule_name, :shipping_fee, :region_id, :province_id, :municipality_id, :barangay_id)
        ");

        // Use execute with array instead of bindParam for better NULL handling
        $result = $stmt->execute([
            ':rule_name' => $rule_name,
            ':shipping_fee' => $fee,
            ':region_id' => $region_id,
            ':province_id' => $province_id,
            ':municipality_id' => $municipality_id,
            ':barangay_id' => $barangay_id
        ]);

        if ($result) {
            $_SESSION['success_message'] = "Shipping rule added successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to add shipping rule.";
        }
        header("Location: ../shipping.php");
        exit;

    } catch (PDOException $e) {
        // Log the actual error for debugging
        error_log("Shipping rule add error: " . $e->getMessage());
        $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        header("Location: ../shipping.php");
        exit;
    }
}
?>
