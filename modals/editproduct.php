<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="backend/editproduct.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3 d-flex justify-content-center">
                        <img id="editImagePreview" src="" class="img-fluid mb-2 border border-secondary" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="product_image" accept="image/*" onchange="previewImage1(event)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="product_name" id="edit_product_name" required>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Restock Alert</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-box-seam"></i></span>
                                    <input type="number" class="form-control" step="1" name="restock_alert" id="edit_restock_alert" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" rows="2" name="description" id="edit_description" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="edit_category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach($product_category as $row):?>
                                <option value="<?php echo $row['category_id']?>"><?php echo $row['category_name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Material</label>
                                <input type="text" class="form-control" name="material" id="edit_material" required>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Variant Summary</h6>
                                <small class="text-muted">Read-only. Use "Manage Sizes" to edit.</small>
                            </div>
                            <div id="edit_variant_summary">
                                <div class="text-muted small">No sizes yet.</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="product_id" id="edit_product_id">
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Preview image function (same as before)
    function previewImage1(event) {
        const image = document.getElementById('editImagePreview');
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
