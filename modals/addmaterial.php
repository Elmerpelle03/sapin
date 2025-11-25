<?php 
    $stmt = $pdo->prepare("SELECT * FROM materialunits");
    $stmt->execute();
    $materialunits_data = $stmt->fetchAll();
?>
<div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMaterialModalLabel">Add New Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="backend/addmaterial.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="materialName" class="form-label">Material Name</label>
                        <input type="text" class="form-control" id="materialName" name="material_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" rows="2" id="description" name="description" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="materialUnit" class="form-label">Material Unit</label>
                        <select class="form-select" id="materialUnit" name="materialunit_id" required>
                            <option value="">Select Unit</option>
                            <?php foreach($materialunits_data as $row):?>
                                <option value="<?php echo $row['materialunit_id']?>"><?php echo $row['materialunit_name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                    <div class="mb-3">
                        <label for="reorderPoint" class="form-label">Reorder Point</label>
                        <input type="number" class="form-control" id="reorderPoint" name="reorder_point" required>
                        <small class="text-muted">Stock level at which to trigger a reorder alert</small>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Add Material</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
