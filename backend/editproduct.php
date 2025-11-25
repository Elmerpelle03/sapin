<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $product_name = $_POST['product_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $restock_alert = $_POST['restock_alert'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $material = $_POST['material'] ?? '';

    $image = $_FILES['product_image'];
    $image_name = '';

    // Validation
    if (
        empty($product_id) || empty($product_name) ||
        empty($description) || empty($restock_alert) ||
        empty($category_id) || empty($material)
    ) {
        $_SESSION['error_message'] = "All fields are required!";
        header('Location: ../products.php');
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // If a new image is uploaded
        if ($image['error'] === 0) {
            $image_ext = pathinfo($image['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('prod_', true) . '.' . $image_ext;
            $upload_path = '../../uploads/products/' . $image_name;

            if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
                $pdo->rollBack();
                $_SESSION['error_message'] = "Image upload failed.";
                header('Location: ../products.php');
                exit;
            }

            // Update including image
            $stmt = $pdo->prepare("UPDATE products SET
                product_name = :product_name,
                description = :description,
                restock_alert = :restock_alert,
                category_id = :category_id,
                material = :material,
                image_url = :image_url
                WHERE product_id = :product_id
            ");

            $stmt->execute([
                ':product_name' => $product_name,
                ':description' => $description,
                ':restock_alert' => $restock_alert,
                ':category_id' => $category_id,
                ':material' => $material,
                ':image_url' => $image_name,
                ':product_id' => $product_id
            ]);

        } else {
            // Update without changing image
            $stmt = $pdo->prepare("UPDATE products SET
                product_name = :product_name,
                description = :description,
                restock_alert = :restock_alert,
                category_id = :category_id,
                material = :material
                WHERE product_id = :product_id
            ");

            $stmt->execute([
                ':product_name' => $product_name,
                ':description' => $description,
                ':restock_alert' => $restock_alert,
                ':category_id' => $category_id,
                ':material' => $material,
                ':product_id' => $product_id
            ]);
        }

        $pdo->commit();
        
        $message = "Product updated successfully.";
        $_SESSION['success_message'] = $message;
        header('Location: ../products.php');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Unexpected error occurred.";
        header('Location: ../products.php');
        exit;
    }
}
?>
