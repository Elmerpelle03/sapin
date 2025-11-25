<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Restrict to Super Admin only
if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

try {
    // Total expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
    $total = $stmt->fetchColumn();

    // This month's expenses
    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as monthly 
                         FROM expenses 
                         WHERE MONTH(expense_date) = MONTH(CURRENT_DATE()) 
                         AND YEAR(expense_date) = YEAR(CURRENT_DATE())");
    $monthly = $stmt->fetchColumn();

    // Average per month (last 12 months)
    $stmt = $pdo->query("SELECT COALESCE(AVG(monthly_total), 0) as average
                         FROM (
                             SELECT SUM(amount) as monthly_total
                             FROM expenses
                             WHERE expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                             GROUP BY YEAR(expense_date), MONTH(expense_date)
                         ) as monthly_expenses");
    $average = $stmt->fetchColumn();

    echo json_encode([
        'total' => $total,
        'monthly' => $monthly,
        'average' => $average
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'total' => 0,
        'monthly' => 0,
        'average' => 0,
        'error' => $e->getMessage()
    ]);
}
?>
