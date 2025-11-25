<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Get current month and year
$currentYear = date('Y');
$currentMonth = date('m');

// Calculate the start date 12 months ago from current month (start of that month)
$startDate = date('Y-m-01', strtotime("-11 months")); // 11 months back including current month

// Prepare and execute the query
$sql = "
    SELECT 
        DATE_FORMAT(date, '%Y-%m') AS month,
        SUM(amount) AS total_amount
    FROM orders
    WHERE (status = 'Delivered' OR status = 'Received')
      AND date >= :startDate
      AND date <= LAST_DAY(CURRENT_DATE)
    GROUP BY month
    ORDER BY month
";
$stmt = $pdo->prepare($sql);
$stmt->execute(['startDate' => $startDate]);

// Fetch results as an associative array: month => total_amount
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build a month => amount map to fill missing months with 0
$months = [];
$amounts = [];
for ($i = 0; $i < 12; $i++) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[$m] = 0;
}
// Override with actual data
foreach ($results as $row) {
    $months[$row['month']] = (float)$row['total_amount'];
}

// Reverse to get oldest to newest
$months = array_reverse($months, true);

// Prepare arrays for labels and data
$labels = [];
$data = [];
foreach ($months as $month => $amount) {
    $labels[] = date('M Y', strtotime($month)); // e.g. Jan, Feb
    $data[] = $amount;
}

// Output JSON to JavaScript
echo json_encode(['labels' => $labels, 'data' => $data]);
?>