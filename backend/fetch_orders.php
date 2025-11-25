<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// DataTables parameters
$search_value = $_GET['search']['value'] ?? '';
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$order_column_index = $_GET['order'][0]['column'] ?? 0;
$order_direction = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';

// Custom filters
$status_filter = $_GET['status'] ?? '';
$customer_type_filter = $_GET['customer_type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$customer = $_GET['customer'] ?? '';

$columns = [
    'o.order_id',
    'o.date',
    'o.fullname',
    'o.contact_number',
    'shipping_address',
    'o.payment_method',
    '(o.amount + o.shipping_fee)',
    'o.status',
    'o.order_id'
];

// Base query
$baseSelect = "SELECT 
    o.order_id,
    o.date,
    o.fullname,
    o.contact_number,
    CONCAT(o.house, ' ', b.barangay_name, ', ', m.municipality_name, ', ', p.province_name) AS shipping_address,
    o.payment_method,
    (o.amount + o.shipping_fee) as amount,
    o.status,
    o.proof_of_payment,
    o.proof_metadata as metadata,
    cr.status AS cancellation_status,
    cr.admin_response AS cancellation_response,
    u.usertype_id
FROM orders o
LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
LEFT JOIN table_province p ON o.province_id = p.province_id
LEFT JOIN cancellation_requests cr ON o.order_id = cr.order_id
LEFT JOIN users u ON o.user_id = u.user_id";

$where = [];
$params = [];

// Global search (by customer name and contact)
if ($search_value !== '') {
    $where[] = '(o.fullname LIKE :search1 OR o.contact_number LIKE :search2)';
    $params[':search1'] = '%' . $search_value . '%';
    $params[':search2'] = '%' . $search_value . '%';
}

// Customer filter (more specific)
if ($customer !== '') {
    $where[] = 'o.fullname LIKE :customer';
    $params[':customer'] = '%' . $customer . '%';
}

// Status filter
if ($status_filter !== '' && strtolower($status_filter) !== 'all') {
    $where[] = 'o.status = :status';
    $params[':status'] = $status_filter;
}

// Customer type filter (bulk buyers vs regular)
if ($customer_type_filter !== '' && strtolower($customer_type_filter) !== 'all') {
    if ($customer_type_filter === 'bulk') {
        $where[] = 'u.usertype_id = 3';
    } elseif ($customer_type_filter === 'regular') {
        $where[] = '(u.usertype_id != 3 OR u.usertype_id IS NULL)';
    }
}

// Date range filter (assuming o.date is DATETIME)
if ($date_from !== '') {
    $where[] = 'DATE(o.date) >= :date_from';
    $params[':date_from'] = $date_from;
}
if ($date_to !== '') {
    $where[] = 'DATE(o.date) <= :date_to';
    $params[':date_to'] = $date_to;
}

$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

// Data query with order and paging
$orderBy = ' ORDER BY ' . ($columns[$order_column_index] ?? 'o.date') . ' ' . $order_direction;
$limit = ' LIMIT :start, :length';

$sql = $baseSelect . $whereSql . $orderBy . $limit;
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $paramType = ($key === ':start' || $key === ':length') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $val, $paramType);
}
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total records (no filters)
$total_records = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();

// Filtered records count
$countSql = 'SELECT COUNT(*) FROM orders o
LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
LEFT JOIN table_province p ON o.province_id = p.province_id
LEFT JOIN cancellation_requests cr ON o.order_id = cr.order_id
LEFT JOIN users u ON o.user_id = u.user_id' . $whereSql;
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$countStmt->execute();
$filtered_records = (int)$countStmt->fetchColumn();

// Format data for DataTables (plain values; frontend will render badges/pills)
$rows = array_map(function($row){
    $date = new DateTime($row['date']);
    $is_bulk_buyer = ($row['usertype_id'] == 3);
    
    // Extract reference number from metadata
    $payment_reference = null;
    if (!empty($row['metadata'])) {
        $metadata = json_decode($row['metadata'], true);
        if (isset($metadata['payment_reference'])) {
            $payment_reference = $metadata['payment_reference'];
        }
    }
    
    return [
        'order_id' => $row['order_id'],
        'date' => $date->format('Y-m-d H:i:s'),
        'fullname' => $row['fullname'] . ($is_bulk_buyer ? ' <span class="badge bg-success ms-1"><i class="bi bi-star-fill"></i> Wholesaler</span>' : ''),
        'contact_number' => $row['contact_number'],
        'shipping_address' => $row['shipping_address'],
        'payment_method' => $row['payment_method'],
        'payment_reference' => $payment_reference,
        'amount' => (float)$row['amount'],
        'status' => $row['status'],
        'proof_of_payment' => $row['proof_of_payment'],
        'cancellation_status' => $row['cancellation_status'] ?? null,
        'cancellation_response' => $row['cancellation_response'] ?? null,
        'is_bulk_buyer' => $is_bulk_buyer,
    ];
}, $data);

$response = [
    'draw' => isset($_GET['draw']) ? (int)$_GET['draw'] : 1,
    'recordsTotal' => $total_records,
    'recordsFiltered' => $filtered_records,
    'data' => $rows,
];

header('Content-Type: application/json');
echo json_encode($response);
?>
