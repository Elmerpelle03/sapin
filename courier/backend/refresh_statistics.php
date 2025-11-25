<?php
// refresh_statistics.php - Backend endpoint for refreshing dashboard statistics
require_once('../config/session_courier.php');
require_once('../config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Count total deliveries (all orders assigned to this rider)
    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rider_id");
    $totalStmt->bindValue(':rider_id', $user_id, PDO::PARAM_INT);
    $totalStmt->execute();
    $total = (int)$totalStmt->fetchColumn();

    // Count completed deliveries (Delivered + Received)
    $completedStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rider_id AND status IN ('Delivered', 'Received')");
    $completedStmt->bindValue(':rider_id', $user_id, PDO::PARAM_INT);
    $completedStmt->execute();
    $completed = (int)$completedStmt->fetchColumn();

    // Count pending deliveries (Pending + Processing + Shipping)
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rider_id AND status IN ('Pending', 'Processing', 'Shipping')");
    $pendingStmt->bindValue(':rider_id', $user_id, PDO::PARAM_INT);
    $pendingStmt->execute();
    $pending = (int)$pendingStmt->fetchColumn();

    // Count failed/returned deliveries (Cancelled)
    $failedStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rider_id AND status = 'Cancelled'");
    $failedStmt->bindValue(':rider_id', $user_id, PDO::PARAM_INT);
    $failedStmt->execute();
    $failed = (int)$failedStmt->fetchColumn();

    // Check for any other statuses not accounted for
    $otherStatusesStmt = $pdo->prepare("SELECT DISTINCT status FROM orders WHERE rider_id = :rider_id AND status NOT IN ('Delivered', 'Received', 'Pending', 'Processing', 'Shipping', 'Cancelled')");
    $otherStatusesStmt->bindValue(':rider_id', $user_id, PDO::PARAM_INT);
    $otherStatusesStmt->execute();
    $otherStatuses = $otherStatusesStmt->fetchAll(PDO::FETCH_COLUMN);

    // Calculate expected total from our categories
    $calculatedTotal = $completed + $pending + $failed;

    // If there's a discrepancy, log it for debugging
    if ($total !== $calculatedTotal && !empty($otherStatuses)) {
        error_log("Status discrepancy for rider $user_id: Total=$total, Calculated=$calculatedTotal, Other statuses: " . implode(', ', $otherStatuses));
    }

    // Return comprehensive statistics
    echo json_encode([
        'total' => $total,
        'completed' => $completed,
        'pending' => $pending,
        'failed' => $failed,
        'calculated_total' => $calculatedTotal,
        'unaccounted' => $total - $calculatedTotal,
        'other_statuses' => $otherStatuses,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
