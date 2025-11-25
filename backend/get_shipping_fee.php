<?php
require '../config/db.php';

$region_id = $_GET['region_id'] ?? null;
$province_id = $_GET['province_id'] ?? null;
$municipality_id = $_GET['municipality_id'] ?? null;
$barangay_id = $_GET['barangay_id'] ?? null;

$default_fee = 150;
$fee = false;

// 1. Most specific: region + province + municipality + barangay
$stmt = $pdo->prepare("
    SELECT shipping_fee FROM shipping_rules
    WHERE region_id = :region_id
      AND province_id = :province_id
      AND municipality_id = :municipality_id
      AND barangay_id = :barangay_id
    LIMIT 1
");
$stmt->execute([
    ':region_id' => $region_id,
    ':province_id' => $province_id,
    ':municipality_id' => $municipality_id,
    ':barangay_id' => $barangay_id
]);
$fee = $stmt->fetchColumn();

// 2. Less specific: region + province + municipality
if ($fee === false) {
    $stmt = $pdo->prepare("
        SELECT shipping_fee FROM shipping_rules
        WHERE region_id = :region_id
          AND province_id = :province_id
          AND municipality_id = :municipality_id
          AND barangay_id = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':region_id' => $region_id,
        ':province_id' => $province_id,
        ':municipality_id' => $municipality_id
    ]);
    $fee = $stmt->fetchColumn();
}

// 3. Province-level rule: region + province only
if ($fee === false) {
    $stmt = $pdo->prepare("
        SELECT shipping_fee FROM shipping_rules
        WHERE region_id = :region_id
          AND province_id = :province_id
          AND municipality_id = 0
          AND barangay_id = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':region_id' => $region_id,
        ':province_id' => $province_id
    ]);
    $fee = $stmt->fetchColumn();
}

// 4. Region-level rule: region only
if ($fee === false) {
    $stmt = $pdo->prepare("
        SELECT shipping_fee FROM shipping_rules
        WHERE region_id = :region_id
          AND province_id = 0
          AND municipality_id = 0
          AND barangay_id = 0
        LIMIT 1
    ");
    $stmt->execute([
        ':region_id' => $region_id
    ]);
    $fee = $stmt->fetchColumn();
}

// Final result
echo json_encode([
    'fee' => $fee !== false ? floatval($fee) : floatval($default_fee)
]);
