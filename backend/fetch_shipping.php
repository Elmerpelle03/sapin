<?php
// Start output buffering to catch any unwanted output
ob_start();

require '../../config/db.php';
require '../../config/session_admin.php';

// Clear any output from includes
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// DataTables parameters
$search_value = $_GET['search']['value'] ?? '';
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$order_column_index = $_GET['order'][0]['column'] ?? 0;
$order_direction = $_GET['order'][0]['dir'] ?? 'asc';

try {
    $columns = [
        's.rule_id',
        's.rule_name',
        'region_name',
        's.shipping_fee'
    ];

    $sql = "
        SELECT 
            s.rule_id,
            s.rule_name,
            s.shipping_fee,
            s.region_id,
            s.province_id,
            s.municipality_id,
            s.barangay_id,
            r.region_name,
            p.province_name,
            m.municipality_name,
            b.barangay_name
        FROM shipping_rules s
        LEFT JOIN table_region r ON s.region_id = r.region_id
        LEFT JOIN table_province p ON s.province_id = p.province_id
        LEFT JOIN table_municipality m ON s.municipality_id = m.municipality_id
        LEFT JOIN table_barangay b ON s.barangay_id = b.barangay_id
        WHERE 
            s.rule_name LIKE :search1
            OR r.region_name LIKE :search2
            OR p.province_name LIKE :search3
            OR m.municipality_name LIKE :search4
            OR b.barangay_name LIKE :search5
        ORDER BY {$columns[$order_column_index]} $order_direction
        LIMIT :start, :length
    ";

    $stmt = $pdo->prepare($sql);
    $search_param = "%$search_value%";
    $stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search4', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':search5', $search_param, PDO::PARAM_STR);
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total
$total_stmt = $pdo->query("SELECT COUNT(*) FROM shipping_rules");
$total = $total_stmt->fetchColumn();

// Filtered
$filtered_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM shipping_rules s
    LEFT JOIN table_region r ON s.region_id = r.region_id
    LEFT JOIN table_province p ON s.province_id = p.province_id
    LEFT JOIN table_municipality m ON s.municipality_id = m.municipality_id
    LEFT JOIN table_barangay b ON s.barangay_id = b.barangay_id
    WHERE 
        s.rule_name LIKE :search1
        OR r.region_name LIKE :search2
        OR p.province_name LIKE :search3
        OR m.municipality_name LIKE :search4
        OR b.barangay_name LIKE :search5
");
$filtered_stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
$filtered_stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
$filtered_stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
$filtered_stmt->bindValue(':search4', $search_param, PDO::PARAM_STR);
$filtered_stmt->bindValue(':search5', $search_param, PDO::PARAM_STR);
$filtered_stmt->execute();
$filtered = $filtered_stmt->fetchColumn();

    // Response
    $response = [
        "draw" => intval($_GET['draw'] ?? 1),
        "recordsTotal" => $total,
        "recordsFiltered" => $filtered,
        "data" => array_map(function ($row) {
            $areaParts = array_filter([
                $row['region_name'],
                $row['province_name'] ?? null,
                $row['municipality_name'] ?? null,
                $row['barangay_name'] ?? null
            ]);
            return [
                "rule_id" => $row['rule_id'],
                "rule_name" => $row['rule_name'],
                "area" => implode(', ', $areaParts),
                "shipping_fee" => '₱' . number_format($row['shipping_fee'], 2),
                "action" => '
                    <button class="btn btn-sm btn-secondary edit-btn"
                        data-id="' . $row['rule_id'] . '"
                        data-name="' . htmlspecialchars($row['rule_name'], ENT_QUOTES) . '"
                        data-fee="' . $row['shipping_fee'] . '"
                        data-region="' . $row['region_id'] . '"
                        data-province="' . $row['province_id'] . '"
                        data-municipality="' . $row['municipality_id'] . '"
                        data-barangay="' . $row['barangay_id'] . '">
                        Edit
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn" data-id="' . $row['rule_id'] . '">
                        Delete
                    </button>'

            ];
        }, $data)
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // Return error in DataTables format
    echo json_encode([
        "draw" => intval($_GET['draw'] ?? 1),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
