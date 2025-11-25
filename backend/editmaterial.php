<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the form data
    $material_id = $_POST['material_id'];
    $material_name = $_POST['material_name'];
    $description = $_POST['description'];
    $materialunit_id = $_POST['materialunit_id'];
    $stock = $_POST['stock'];
    $reorder_point = $_POST['reorder_point'];

    // Validate form data
    if (empty($material_name) || empty($description) || $materialunit_id === '' || $materialunit_id === null) {
        $_SESSION['error_message'] = "All fields are required!";
        header('Location: ../materialinventory.php');
        exit;
    }
    $stockNum = floatval($stock);
    $ropNum = floatval($reorder_point);
    if (!is_numeric($stock) || !is_numeric($reorder_point) || $stockNum <= 0 || $ropNum <= 0) {
        $_SESSION['error_message'] = "Stock and Reorder Point must be greater than 0.";
        header('Location: ../materialinventory.php');
        exit;
    }

    try {
        // Prepare SQL query to update the material in the database
        $stmt = $pdo->prepare("UPDATE materials 
                               SET material_name = :material_name, description = :description, 
                                   materialunit_id = :materialunit_id, stock = :stock, reorder_point = :reorder_point 
                               WHERE material_id = :material_id");

        // Bind parameters
        $stmt->bindParam(':material_name', $material_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':materialunit_id', $materialunit_id);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':reorder_point', $reorder_point);
        $stmt->bindParam(':material_id', $material_id);

        // Execute the query
        $stmt->execute();

        $_SESSION['success_message'] = "Material updated successfully.";
        header("Location: ../materialinventory.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Unexpected error: " . $e->getMessage();
        header('Location: ../materialinventory.php');
        exit;
    }
}
?>
