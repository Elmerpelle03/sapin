<?php
require '../../config/db.php';
require '../../config/session_courier.php';

header('Content-Type: application/json');

// Get the courier's order data for the last 7 days for chart
$courier_id = $_SESSION['user_id'] ?? 0;

// Validate courier_id
if (!$courier_id || !is_numeric($courier_id)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid courier ID',
        'message' => 'Courier session not found or invalid'
    ]);
    exit;
}

try {
    // Get daily sales/revenue data for the last 7 days (including today)
    $stmt = $pdo->prepare("
        SELECT
            DATE(o.date) as order_date,
            SUM(o.amount + o.shipping_fee) as daily_total,
            COUNT(*) as order_count
        FROM orders o
        WHERE o.rider_id = :rider_id
        AND o.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        AND o.date <= CURDATE()
        GROUP BY DATE(o.date)
        ORDER BY order_date ASC
    ");

    $stmt->execute([':rider_id' => $courier_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate labels and values for the last 7 days
    $labels = [];
    $values = [];

    // Create array of the last 7 days (including today)
    for ($i = 6; $i >= 0; $i--) {
        $date = new DateTime();
        $date->modify("-{$i} days");
        $dateStr = $date->format('Y-m-d');
        $displayDate = $date->format('M j');

        $labels[] = $displayDate;

        // Find matching data or use 0
        $found = false;
        foreach ($data as $row) {
            if ($row['order_date'] === $dateStr) {
                $values[] = (float)$row['daily_total'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $values[] = 0;
        }
    }

    // Ensure we have exactly 7 data points
    if (count($labels) !== 7 || count($values) !== 7) {
        throw new Exception('Invalid data range generated');
    }

    echo json_encode([
        'labels' => $labels,
        'data' => $values,
        'debug' => [
            'courier_id' => $courier_id,
            'data_points' => count($data),
            'date_range' => [
                'start' => $labels[0] ?? 'N/A',
                'end' => $labels[6] ?? 'N/A'
            ]
        ]
    ]);

} catch (PDOException $e) {
    // Database error
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Failed to fetch delivery data from database',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // General error
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'Failed to process dashboard data',
        'details' => $e->getMessage()
    ]);
}
?>
