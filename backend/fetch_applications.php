<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// DataTables parameters
$search_value = $_GET['search']['value'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$order_column_index = $_GET['order'][0]['column'] ?? 0;
$order_direction = $_GET['order'][0]['dir'] ?? 'asc';

// Column mapping for sorting
$columns = [
    'a.application_id',
    'u.username',
    'fullname',
    'address',
    'a.purpose',
    'a.id_type',
    'a.status'
];

// Build dynamic WHERE clause for search + status
$where = [];
$params = [];
$where[] = '(u.username LIKE :search1 OR CONCAT(ud.firstname, " ", ud.lastname) LIKE :search2 OR a.purpose LIKE :search3)';
$params[':search1'] = "%$search_value%";
$params[':search2'] = "%$search_value%";
$params[':search3'] = "%$search_value%";
if (in_array($status_filter, ['Pending','Approved','Declined'])) {
    $where[] = 'a.status = :status';
    $params[':status'] = $status_filter;
}
$whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// SQL with JOIN to users and userdetails
$sql = "
    SELECT 
        a.application_id,
        a.user_id,
        a.purpose,
        a.id_type,
        a.id_image,
        a.status,
        a.submitted_at,
        u.username,
        u.email,
        u.usertype_id,
        CONCAT(ud.firstname, ' ', ud.lastname) AS fullname,
        ud.contact_number,
        CONCAT(ud.house, ' ', b.barangay_name, ', ', m.municipality_name, ', ', p.province_name) AS address
    FROM 
        bulk_buyer_applications a
    LEFT JOIN users u ON a.user_id = u.user_id
    LEFT JOIN userdetails ud ON u.user_id = ud.user_id
    LEFT JOIN table_barangay b ON ud.barangay_id = b.barangay_id
    LEFT JOIN table_municipality m ON ud.municipality_id = m.municipality_id
    LEFT JOIN table_province p ON ud.province_id = p.province_id
    $whereSql
    ORDER BY {$columns[$order_column_index]} $order_direction
    LIMIT :start, :length
";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_string($v) ? PDO::PARAM_STR : PDO::PARAM_INT);
}
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total counts
$total_records_stmt = $pdo->query("SELECT COUNT(*) FROM bulk_buyer_applications");
$total_records = $total_records_stmt->fetchColumn();

$filtered_sql = "
    SELECT COUNT(*)
    FROM bulk_buyer_applications a
    LEFT JOIN users u ON a.user_id = u.user_id
    LEFT JOIN userdetails ud ON u.user_id = ud.user_id
    $whereSql
";
$filtered_stmt = $pdo->prepare($filtered_sql);
foreach ($params as $k => $v) {
    $filtered_stmt->bindValue($k, $v, is_string($v) ? PDO::PARAM_STR : PDO::PARAM_INT);
}
$filtered_stmt->execute();
$filtered_records = $filtered_stmt->fetchColumn();

// Format for DataTables
$response = [
    "draw" => intval($_GET['draw'] ?? 1),
    "recordsTotal" => $total_records,
    "recordsFiltered" => $filtered_records,
    "data" => array_map(function ($row) {
        $statusBadge = $row['status'] === 'Approved'
            ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>'
            : ($row['status'] === 'Declined'
                ? '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Declined</span>'
                : '<span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Pending</span>');

        return [
            'application_id' => $row['application_id'],
            'username' => $row['username'],
            'fullname' => $row['fullname'],
            'address' => $row['address'],
            'purpose' => strlen($row['purpose']) > 20 
                ? substr($row['purpose'], 0, 20) . '...' 
                : $row['purpose'],
            'id_type' => $row['id_type'],
            'status' => $statusBadge,
            'action' => '<button class="btn btn-primary btn-sm view-user-btn" 
                data-user=\'' . json_encode([
                    "application_id" => $row["application_id"],
                    "user_id" => $row["user_id"],
                    "username" => $row["username"],
                    "email" => $row["email"],
                    "fullname" => $row["fullname"],
                    "contact" => $row["contact_number"],
                    "address" => $row["address"],
                    "purpose" => $row["purpose"],
                    'submitted_at' => date("F j, Y - g:iA", strtotime($row["submitted_at"])),
                    "status" => $row['status'],
                    "id_type" => $row["id_type"],
                    "id_image" => "../uploads/ids/" . $row["id_image"]
                ]) . '\'>View</button>' . 
                ($row['status'] === 'Approved' 
                    ? ' <a href="bulk_messages.php?user_id=' . $row['user_id'] . '" class="btn btn-success btn-sm" title="Message this bulk buyer">
                        <i class="bi bi-chat-dots"></i> Message
                       </a>' 
                    : '')
        ];
    }, $data)
];

echo json_encode($response);
?>
