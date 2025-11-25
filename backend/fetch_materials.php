<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Retrieve the search term from DataTables' request
$search_value = $_GET['search']['value'] ?? ''; // The global search value
$start = $_GET['start'] ?? 0; // The start index for pagination
$length = $_GET['length'] ?? 10; // Number of rows per page
$order_column_index = $_GET['order'][0]['column'] ?? 0; // Column index for sorting
$order_direction = $_GET['order'][0]['dir'] ?? 'asc'; // Sorting direction (asc or desc)

// Define the columns for sorting
$columns = ['material_id', 'material_name', 'description', 'stock_with_unit', 'reorder_point'];

// Build the SQL query for the search
$sql = "SELECT 
            m.material_id,
            m.material_name,
            m.description,
            m.stock,
            CONCAT(m.stock, ' ', mu.materialunit_name) AS stock_with_unit,
            mu.materialunit_name AS unit,
            m.reorder_point,
            mu.materialunit_id
        FROM 
            materials m
        JOIN 
            materialunits mu ON m.materialunit_id = mu.materialunit_id
        WHERE 
            m.material_name LIKE :search
        ORDER BY 
            {$columns[$order_column_index]} $order_direction 
        LIMIT :start, :length";

// Prepare the SQL query
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':search', '%' . $search_value . '%', PDO::PARAM_STR);
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

// Execute the query
$stmt->execute();

// Fetch the data
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count the total number of records (without filtering)
$total_records_stmt = $pdo->query("SELECT COUNT(*) FROM materials");
$total_records = $total_records_stmt->fetchColumn();

// Count the filtered number of records (with the search term)
$filtered_records_stmt = $pdo->prepare("SELECT COUNT(*) FROM materials m JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id WHERE m.material_name LIKE :search");
$filtered_records_stmt->bindValue(':search', '%' . $search_value . '%', PDO::PARAM_STR);
$filtered_records_stmt->execute();
$filtered_records = $filtered_records_stmt->fetchColumn();

// Prepare the response in the format DataTables expects
$response = [
    "draw" => $_GET['draw'] ?? 1, // Keep track of how many requests the client has made
    "recordsTotal" => $total_records,
    "recordsFiltered" => $filtered_records,
    "data" => array_map(function($row) {
        return [
            'material_id' => $row['material_id'],
            'material_name' => $row['material_name'],
            'description' => $row['description'],
            'stock' => $row['stock'],
            'stock_with_unit' => $row['stock_with_unit'],
            'unit' => $row['unit'], // Added unit field
            'reorder_point' => $row['reorder_point'],
            'materialunit_id' => $row['materialunit_id'],
            'action' => '<button class="btn btn-primary btn-sm edit-btn" data-material-id="' . $row['material_id'] . '" data-bs-toggle="modal" data-bs-target="#editMaterialModal">Edit</button>
                        <button class="btn btn-danger btn-sm delete-btn">Delete</button>'
        ];
    }, $data)
];

// Return the JSON response
echo json_encode($response);
?>