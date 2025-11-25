<?php
require '../../config/db.php'; // Make sure this file connects to your database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the order ID from the POST request
    $order_id = $_POST['order_id'];

    // Start a transaction to ensure both deletions happen together
    $pdo->beginTransaction();

    try {
        // Prepare the SQL statement to delete the order items first
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = :order_id");

        // Bind the order_id to the query and execute it
        if ($stmt->execute([':order_id' => $order_id])) {
            // Prepare the SQL statement to delete the order itself
            $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = :order_id");

            // Execute the deletion of the order
            if ($stmt->execute([':order_id' => $order_id])) {
                // Commit the transaction if both deletions were successful
                $pdo->commit();
                echo 'success';
            } else {
                // Rollback if deleting the order fails
                $pdo->rollBack();
                echo 'error';
            }
        } else {
            // Rollback if deleting the order items fails
            $pdo->rollBack();
            echo 'error';
        }
    } catch (PDOException $e) {
        // Rollback in case of any exception
        $pdo->rollBack();
        echo 'error';
    }
}
?>
