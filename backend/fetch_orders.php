<?php
require '../config/db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode([
        "draw" => intval($_GET['draw'] ?? 0),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => []
    ]);
    exit();
}

$draw = intval($_GET['draw'] ?? 0);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$searchValue = $_GET['search']['value'] ?? '';

$where = " WHERE o.user_id = :user_id ";
$params = [':user_id' => $user_id];

if ($searchValue) {
    $where .= " AND (o.order_id LIKE :search OR o.status LIKE :search) ";
    $params[':search'] = "%$searchValue%";
}

$totalQuery = "SELECT COUNT(*) FROM orders WHERE user_id = :user_id";
$totalStmt = $pdo->prepare($totalQuery);
$totalStmt->execute([':user_id' => $user_id]);
$totalRecords = $totalStmt->fetchColumn();

$filteredQuery = "SELECT COUNT(*) FROM orders o LEFT JOIN cancellation_requests cr ON o.order_id = cr.order_id $where";
$filteredStmt = $pdo->prepare($filteredQuery);
$filteredStmt->execute($params);
$totalFiltered = $filteredStmt->fetchColumn();

$dataQuery = "SELECT 
    o.order_id, 
    o.date, 
    o.status,
    (o.amount + o.shipping_fee) AS amount, 
    (SELECT image_url FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = o.order_id LIMIT 1) AS product_image, 
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) AS item_count, 
    (SELECT GROUP_CONCAT(p.product_name SEPARATOR ', ') FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = o.order_id) AS product_name,
    cr.status AS cancellation_status,
    cr.admin_response AS cancellation_response
FROM orders o 
LEFT JOIN cancellation_requests cr ON o.order_id = cr.order_id
$where 
ORDER BY o.date DESC 
LIMIT :start, :length";
$dataStmt = $pdo->prepare($dataQuery);

$dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
$dataStmt->bindValue(':length', $length, PDO::PARAM_INT);

foreach ($params as $key => $val) {
    if ($key !== ':start' && $key !== ':length') {
        $dataStmt->bindValue($key, $val, PDO::PARAM_STR);
    }
}

$dataStmt->execute();
$data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// Function to return Bootstrap 5 badge HTML by status with custom colors
function getStatusBadge($status) {
    $status = strtolower($status);
    $badgeClass = match($status) {
        'delivered' => 'badge-delivered',
        'processing' => 'badge-processing',
        'pending' => 'badge-pending',
        'received' => 'badge-received',
        'cancelled' => 'badge-cancelled',
        'shipping' => 'badge-processing', // Assuming shipping is similar to processing
        default => 'badge-pending'
    };
    return '<span class="badge ' . $badgeClass . '">' . ucfirst($status) . '</span>';
}

foreach ($data as &$row) {
    // Format date
    $dateObj = new DateTime($row['date']);
    $row['date'] = $dateObj->format('F j, Y - g:iA'); // e.g. March 2, 2000 - 10:00AM
    $row['sort_date'] = $dateObj->format('c'); // ISO 8601 for sorting

    // Add peso sign with 2 decimals
    $row['amount'] = '₱' . number_format((float)$row['amount'], 2);

    // Replace status with badge
    $row['status'] = getStatusBadge($row['status']);
    
    // Add cancellation badge if exists
    if (!empty($row['cancellation_status'])) {
        $cancelBadge = '';
        if ($row['cancellation_status'] === 'pending') {
            $cancelBadge = '<span class="badge bg-warning text-dark ms-2"><i class="bi bi-clock"></i> Cancellation Pending</span>';
        } elseif ($row['cancellation_status'] === 'approved') {
            $cancelBadge = '<span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Cancellation Approved</span>';
        } elseif ($row['cancellation_status'] === 'rejected') {
            $cancelBadge = '<span class="badge bg-danger ms-2"><i class="bi bi-x-circle"></i> Cancellation Rejected</span>';
        }
        $row['status'] .= $cancelBadge;
    }

    // Add action button
    $row['action'] = '<a href="order_details.php?order_id=' . htmlspecialchars($row['order_id']) . '" class="btn btn-sm btn-primary">View</a>';
}

$response = [
    "draw" => $draw,
    "recordsTotal" => intval($totalRecords),
    "recordsFiltered" => intval($totalFiltered),
    "data" => $data
];

header('Content-Type: application/json');
echo json_encode($response);
exit();
?>
