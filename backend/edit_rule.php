<?php
require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule_id = $_POST['rule_id'];
    $rule_name = $_POST['rule_name'] ?? '';
    $fee = $_POST['fee'] ?? 0;
    $region_id = $_POST['region_id'] ?? null;
    $province_id = $_POST['province_id'] ?? null;
    $municipality_id = $_POST['municipality_id'] ?? null;
    $barangay_id = $_POST['barangay_id'] ?? null;

    // Convert empty strings to NULL for optional fields
    $province_id = !empty($province_id) ? $province_id : null;
    $municipality_id = !empty($municipality_id) ? $municipality_id : null;
    $barangay_id = !empty($barangay_id) ? $barangay_id : null;

    if (empty($rule_name) || empty($fee) || empty($region_id)) {
        $_SESSION['error_message'] = "Please fill out all required fields.";
        header("Location: ../shipping.php");
        exit;
    }

    try {
        // Check for duplicate rule excluding itself - simplified query
        $check_sql = "SELECT COUNT(*) FROM shipping_rules WHERE region_id = :region_id AND rule_id != :rule_id";
        $check_params = [':region_id' => $region_id, ':rule_id' => $rule_id];
        
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
            $_SESSION['error_message'] = "A rule with the same location already exists.";
            header("Location: ../shipping.php");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE shipping_rules
            SET rule_name = :rule_name,
                shipping_fee = :shipping_fee,
                region_id = :region_id,
                province_id = :province_id,
                municipality_id = :municipality_id,
                barangay_id = :barangay_id
            WHERE rule_id = :rule_id
        ");

        $stmt->execute([
            ':rule_name' => $rule_name,
            ':shipping_fee' => $fee,
            ':region_id' => $region_id,
            ':province_id' => $province_id,
            ':municipality_id' => $municipality_id,
            ':barangay_id' => $barangay_id,
            ':rule_id' => $rule_id
        ]);

        $_SESSION['success_message'] = "Shipping rule updated.";
        header("Location: ../shipping.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: ../shipping.php");
        exit;
    }
}
?>
