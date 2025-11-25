<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

// Check if it's a POST request and has a rule_id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rule_id'])) {
    $rule_id = $_POST['rule_id'];

    try {
        // Prepare and execute delete query
        $stmt = $pdo->prepare("DELETE FROM shipping_rules WHERE rule_id = :rule_id");
        $stmt->bindParam(':rule_id', $rule_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Deletion failed.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
