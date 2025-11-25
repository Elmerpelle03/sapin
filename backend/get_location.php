<?php
require '../config/db.php';
session_start();

$type = $_GET['type'] ?? '';
$parentId = $_GET['parent_id'] ?? '';

if ($type === 'province') {
    $stmt = $pdo->prepare("SELECT province_id AS id, province_name AS name FROM table_province WHERE region_id = :parent_id");
} elseif ($type === 'municipality') {
    $stmt = $pdo->prepare("SELECT municipality_id AS id, municipality_name AS name FROM table_municipality WHERE province_id = :parent_id");
} elseif ($type === 'barangay') {
    $stmt = $pdo->prepare("SELECT barangay_id AS id, barangay_name AS name FROM table_barangay WHERE municipality_id = :parent_id");
} else {
    echo json_encode([]);
    exit;
}

$stmt->execute(['parent_id' => $parentId]);
echo json_encode($stmt->fetchAll());
?>
