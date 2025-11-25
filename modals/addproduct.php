<?php 
    $stmt = $pdo->prepare("SELECT * FROM product_category");
    $stmt->execute();
    $product_category = $stmt->fetchAll();
    // Fetch materials for dropdown
    $stmtMaterials = $pdo->prepare("SELECT material_id, material_name, stock FROM materials ORDER BY material_name ASC");
    $stmtMaterials->execute();
    $materials = $stmtMaterials->fetchAll();
?>

<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="backend/addproduct.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3 d-flex justify-content-center">
                        <img id="imagePreview" src="" class="img-fluid mb-2 border border-secondary" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="product_image" accept="image/*" onchange="previewImage(event)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="product_name" placeholder="King Kong Bedsheet" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" step="0.01" name="price" placeholder="12000.00" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-12">
                            <div class="mb-3">
                                <label class="form-label">Restock Alert</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                    <input type="number" class="form-control" step="1" name="restock_alert" id="restock_alert" placeholder="10" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" rows="2" name="description" placeholder="25 Latest Bed Sheet Designs With Pictures In 2023" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Stock</label>
                                <input type="number" class="form-control" step="1" name="stock" placeholder="100" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <option value="" disabled selected hidden>Select Category</option>
                            <?php foreach($product_category as $row):?>
                                <option value="<?php echo $row['category_id']?>"><?php echo $row['category_name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Size</label>
                                <input type="text" class="form-control" name="size" placeholder="King" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Material</label>
                                <select class="form-select" name="material" required>
                                    <option value="" disabled selected hidden>Select Material</option>
                                    <?php foreach($materials as $mat): ?>
                                        <option value="<?php echo htmlspecialchars($mat['material_name']); ?>">
                                            <?php echo htmlspecialchars($mat['material_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function previewImage(event) {
        const image = document.getElementById('imagePreview');
        const file = event.target.files[0];
        if (file) {
            image.src = URL.createObjectURL(file);
            image.style.display = 'block';
        } else {
            image.src = '';
            image.style.display = 'none';
        }
    }
</script>
