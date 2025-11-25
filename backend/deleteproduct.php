<?php
require '../../config/db.php';
require '../../config/session_admin.php';

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    try {
        // Optional: Get and delete the image file (optional cleanup)
        $stmt = $pdo->prepare("SELECT image_url FROM products WHERE product_id = :id");
        $stmt->execute([':id' => $product_id]);
        $product = $stmt->fetch();

        if ($product && !empty($product['image_url'])) {
            $image_path = '../../uploads/products/' . $product['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = :id");
        $stmt->execute([':id' => $product_id]);

        $_SESSION['success_message'] = "Product deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Failed to delete product.";
    }
} else {
    $_SESSION['error_message'] = "Invalid product ID.";
}

header('Location: ../products.php');
exit;
?>
