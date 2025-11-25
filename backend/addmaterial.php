<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $material_name = $_POST['material_name'];
    $description = $_POST['description'];
    $materialunit_id = $_POST['materialunit_id'];
    $stock = $_POST['stock'];
    $reorder_point = $_POST['reorder_point'];

    // Validate form data (you can add more validation if needed)
    if (empty($material_name) || empty($description) || empty($stock) || empty($reorder_point)) {
        $_SESSION['error_message'] = "All fields are required!";
        header('Location: ../materialinventory.php');
        exit;
    }

    try {
        // Prepare SQL query to insert the new material into the database
        $stmt = $pdo->prepare("INSERT INTO materials (material_name, description, materialunit_id, stock, reorder_point) 
                               VALUES (:material_name, :description, :materialunit_id, :stock, :reorder_point)");

        // Bind parameters
        $stmt->bindParam(':material_name', $material_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':materialunit_id', $materialunit_id);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':reorder_point', $reorder_point);

        // Execute the query
        $stmt->execute();

        $_SESSION['success_message'] = "Material added successfully.";
        header("Location: ../materialinventory.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Unexpected error.";
        header('Location: ../materialinventory.php');
        exit;
    }
}
?>
