<?php
require '../../config/db.php';
require '../../config/session_admin.php';

// Check if the material_id is provided in the request
if (isset($_POST['material_id'])) {
    $material_id = $_POST['material_id'];

    try {
        // Prepare the SQL query to delete the material
        $stmt = $pdo->prepare("DELETE FROM materials WHERE material_id = :material_id");
        $stmt->bindParam(':material_id', $material_id, PDO::PARAM_INT);

        // Execute the query
        $stmt->execute();

        // Respond with 'success' if the deletion was successful
        echo 'success';

    } catch (PDOException $e) {
        // Respond with an error message if something went wrong
        echo 'error';
    }
}
?>
