<?php
require '../../config/db.php';
require '../../config/session_courier.php';

// Retrieve DataTables parameters
$search_value = $_GET['search']['value'] ?? '';
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$order_column_index = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 0;
$order_direction = ($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Define sortable columns
// Align with frontend columns: [#, date, fullname, contact, address, payment, amount, status, action]
// Only actual DB/derived fields are sortable; action is not.
$columns = [
    'order_id',       // 0
    'date',           // 1
    'fullname',       // 2
    'contact_number', // 3
    'shipping_address', // 4 (alias OK in ORDER BY)
    'payment_method', // 5
    'amount',         // 6 (derived)
    'status',         // 7
    'order_id'        // 8 fallback for action
];
if (!isset($columns[$order_column_index])) { $order_column_index = 0; }

// Main query: fetch rider’s orders
$sql = "
    SELECT 
        o.order_id,
        o.date,
        o.fullname,
        o.contact_number,
        CONCAT(o.house, ' ', b.barangay_name, ', ', m.municipality_name, ', ', p.province_name) AS shipping_address,
        o.payment_method,
        (o.amount + o.shipping_fee) AS amount,
        o.status
    FROM orders o
    LEFT JOIN table_barangay b ON o.barangay_id = b.barangay_id
    LEFT JOIN table_municipality m ON o.municipality_id = m.municipality_id
    LEFT JOIN table_province p ON o.province_id = p.province_id
    WHERE o.rider_id = :rider_id
      AND o.fullname LIKE :search
    ORDER BY {$columns[$order_column_index]} $order_direction
    LIMIT :start, :length
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search_value . '%', PDO::PARAM_STR);
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
$stmt->bindValue(':rider_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total record count (rider-specific for accuracy)
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE rider_id = :rider_id");
$total_stmt->bindValue(':rider_id', $_SESSION['user_id'], PDO::PARAM_INT);
$total_stmt->execute();
$total_records = $total_stmt->fetchColumn();

// Filtered record count — now includes rider filter
$filtered_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders 
    WHERE rider_id = :rider_id
      AND fullname LIKE :search
");
$filtered_stmt->bindValue(':rider_id', $_SESSION['user_id'], PDO::PARAM_INT);
$filtered_stmt->bindValue(':search', '%' . $search_value . '%', PDO::PARAM_STR);
$filtered_stmt->execute();
$filtered_records = $filtered_stmt->fetchColumn();

// Format DataTables response
$response = [
    "draw" => $_GET['draw'] ?? 1,
    "recordsTotal" => (int)$total_records,
    "recordsFiltered" => (int)$filtered_records,
    "data" => array_map(function($row) {
        $status_badge = '';
        switch ($row['status']) {
            case 'Pending':
                $status_badge = '<span class="badge rounded-pill bg-warning">Pending</span>';
                break;
            case 'Processing':
                $status_badge = '<span class="badge rounded-pill bg-info">Processing</span>';
                break;
            case 'Shipping':
                $status_badge = '<span class="badge rounded-pill bg-primary">Shipping</span>';
                break;
            case 'Delivered':
                $status_badge = '<span class="badge rounded-pill bg-success">Delivered</span>';
                break;
            case 'Cancelled':
                $status_badge = '<span class="badge rounded-pill bg-danger">Cancelled</span>';
                break;
            case 'Received':
                $status_badge = '<span class="badge rounded-pill bg-success">Received</span>';
                break;
            default:
                $status_badge = '<span class="badge rounded-pill bg-secondary">Unknown</span>';
                break;
        }
        $date = new DateTime($row['date']);
        return [
            'order_id' => $row['order_id'],
            'date' => $date->format('F j, Y - g:iA'),
            'fullname' => $row['fullname'],
            'contact_number' => $row['contact_number'],
            'shipping_address' => $row['shipping_address'],
            'payment_method' => $row['payment_method'],
            // return raw values; frontend will render formatting/badges
            'amount' => (float)$row['amount'],
            'status' => $row['status'],
            'action' => '<a class="btn btn-primary btn-sm edit-btn" href="view_order.php?order_id=' . $row['order_id'] . '">View</a>'
        ];
    }, $data)
];

header('Content-Type: application/json');
echo json_encode($response);
