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
$columns = ['user_id', 'username', 'firstname', 'lastname', 'email', 'is_verified', 'accountstatus_name'];

// Build the SQL query for the search
$sql = "SELECT 
            u.user_id,
            u.username,
            u.usertype_id,
            ut.usertype_name,
            ud.contact_number,
            CONCAT(ud.house, ' ', tb.barangay_name, ', ',tm.municipality_name, ', ',tp.province_name) AS address,
            CONCAT(ud.firstname, ' ', ud.lastname) AS fullname,
            u.email,
            u.is_verified,
            ac.accountstatus_name
        FROM 
            users u
        LEFT JOIN 
            userdetails ud ON u.user_id = ud.user_id
        LEFT JOIN
            accountstatus ac ON u.accountstatus_id = ac.accountstatus_id
        LEFT JOIN
            table_barangay tb ON ud.barangay_id = tb.barangay_id
        LEFT JOIN
            table_municipality tm ON ud.municipality_id = tm.municipality_id
        LEFT JOIN
            table_province tp ON ud.province_id = tp.province_id
        LEFT JOIN  
            usertype ut ON u.usertype_id = ut.usertype_id
        WHERE 
            u.user_id != 1 AND u.usertype_id != 4 AND (
                u.username LIKE :search1
                OR CONCAT(ud.firstname, ' ', ud.lastname) LIKE :search2
                OR u.email LIKE :search3
            )
        ORDER BY 
            {$columns[$order_column_index]} $order_direction 
        LIMIT :start, :length";

// Prepare the SQL query
$stmt = $pdo->prepare($sql);
$search_param = '%' . $search_value . '%';
$stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
$stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
$stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

// Execute the query
$stmt->execute();

// Fetch the data
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count the total number of records (without filtering)
$total_records_stmt = $pdo->query("SELECT COUNT(*) FROM users");
$total_records = $total_records_stmt->fetchColumn();

// Count the filtered number of records (with the search term)
$filtered_records_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN userdetails ud ON u.user_id = ud.user_id WHERE u.username LIKE :search1 OR CONCAT(ud.firstname, ' ', ud.lastname) LIKE :search2 OR u.email LIKE :search3");
$filtered_records_stmt->bindValue(':search1', $search_param, PDO::PARAM_STR);
$filtered_records_stmt->bindValue(':search2', $search_param, PDO::PARAM_STR);
$filtered_records_stmt->bindValue(':search3', $search_param, PDO::PARAM_STR);
$filtered_records_stmt->execute();
$filtered_records = $filtered_records_stmt->fetchColumn();

// Prepare the response in the format DataTables expects
$response = [
    "draw" => $_GET['draw'] ?? 1, // Keep track of how many requests the client has made
    "recordsTotal" => $total_records,
    "recordsFiltered" => $filtered_records,
    "data" => array_map(function($row) {
        $verifiedBadge = $row['is_verified'] == 1 
            ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Verified</span>' 
            : '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Not Verified</span>';
        $statusBadge = $row['accountstatus_name'] == 'Active'
            ? '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>'
            : '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Disabled</span>';
        return [
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'fullname' => $row['fullname'],
            'email' => $row['email'],
            'verified' => $verifiedBadge,
            'status' => $statusBadge,
            'action' => '<button class="btn btn-primary btn-sm view-user-btn" 
                data-user=\'' . json_encode([
                    "user_id" => $row["user_id"],
                    "username" => $row["username"],
                    "usertype_id" => $row["usertype_id"],
                    "usertype_name" => $row["usertype_name"],
                    "email" => $row["email"],
                    "fullname" => $row["fullname"],
                    "contact" => $row["contact_number"],
                    "address" => $row["address"],
                    "status_html" => $statusBadge,   
                    "status_raw" => $row['accountstatus_name'],
                    "verified_html" => $verifiedBadge,
                    "verified_raw" => $row['is_verified'] == 1 ? "Verified" : "Not Verified"
                ]) . '\'>View</button>'


        ];
    }, $data)
];

// Return the JSON response
echo json_encode($response);
?>
