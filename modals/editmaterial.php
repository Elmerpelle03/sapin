<?php 
    $stmt = $pdo->prepare("SELECT * FROM materialunits");
    $stmt->execute();
    $materialunits_data = $stmt->fetchAll();
?>
<div class="modal fade" id="editMaterialModal" tabindex="-1" aria-labelledby="editMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMaterialModalLabel">Edit Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="backend/editmaterial.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="material_id" id="editMaterialId">
                    <div class="mb-3">
                        <label for="editMaterialName" class="form-label">Material Name</label>
                        <input type="text" class="form-control" id="editMaterialName" name="material_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" rows="2" id="editDescription" name="description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editMaterialUnit" class="form-label">Material Unit</label>
                        <select class="form-select" id="editMaterialUnit" name="materialunit_id" required>
                            <option value="">Select Unit</option>
                            <?php foreach($materialunits_data as $row): ?>
                                <option value="<?php echo $row['materialunit_id']?>"><?php echo $row['materialunit_name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="editStock" name="stock" required min="1" step="1" placeholder=">= 1">
                    </div>
                    <div class="mb-3">
                        <label for="editReorderPoint" class="form-label">Reorder Point</label>
                        <input type="number" class="form-control" id="editReorderPoint" name="reorder_point" required min="1" step="1" placeholder=">= 1">
                        <small class="text-muted">Stock level at which to trigger a reorder alert</small>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Material</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
