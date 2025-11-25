<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Restrict to Super Admin only
if (!isset($_SESSION['usertype_id']) || $_SESSION['usertype_id'] != 5) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Retrieve the search term from DataTables' request
$search_value = $_GET['search']['value'] ?? '';
$start = $_GET['start'] ?? 0;
$length = $_GET['length'] ?? 10;
$order_column_index = $_GET['order'][0]['column'] ?? 0;
$order_direction = $_GET['order'][0]['dir'] ?? 'desc';

// Get filters
$category = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Define the columns for sorting
$columns = ['expense_id', 'expense_date', 'expense_category', 'expense_name', 'amount', 'description'];

// Build the WHERE clause
$where_conditions = [];
$params = [];

if ($search_value) {
    $where_conditions[] = "(expense_name LIKE :search1 OR description LIKE :search2 OR expense_category LIKE :search3)";
    $params[':search1'] = '%' . $search_value . '%';
    $params[':search2'] = '%' . $search_value . '%';
    $params[':search3'] = '%' . $search_value . '%';
}

if ($category) {
    $where_conditions[] = "expense_category = :category";
    $params[':category'] = $category;
}

if ($date_from) {
    $where_conditions[] = "expense_date >= :date_from";
    $params[':date_from'] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "expense_date <= :date_to";
    $params[':date_to'] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Build the SQL query
$sql = "SELECT 
            expense_id,
            expense_category,
            expense_name,
            amount,
            expense_date,
            description,
            receipt_path
        FROM 
            expenses
        $where_clause
        ORDER BY 
            {$columns[$order_column_index]} $order_direction 
        LIMIT :start, :length";

// Prepare the SQL query
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

// Execute the query
$stmt->execute();

// Fetch the data
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count the total number of records (without filtering)
$total_records_stmt = $pdo->query("SELECT COUNT(*) FROM expenses");
$total_records = $total_records_stmt->fetchColumn();

// Count the filtered number of records
$count_sql = "SELECT COUNT(*) FROM expenses $where_clause";
$filtered_records_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $value) {
    $filtered_records_stmt->bindValue($key, $value, PDO::PARAM_STR);
}
$filtered_records_stmt->execute();
$filtered_records = $filtered_records_stmt->fetchColumn();

// Prepare the response in the format DataTables expects
$response = [
    "draw" => $_GET['draw'] ?? 1,
    "recordsTotal" => $total_records,
    "recordsFiltered" => $filtered_records,
    "data" => $data
];

// Return the JSON response
echo json_encode($response);
?>
