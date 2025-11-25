<!-- Modal for Managing Product Materials -->
<div class="modal fade" id="manageProductMaterialsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manage Product Materials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="materialProductId">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Define materials needed for this product.</strong> When you add stock, these materials will be automatically deducted from inventory.
                </div>

                <!-- Current Materials -->
                <div class="mb-3">
                    <h6>Current Materials Required:</h6>
                    <div id="currentMaterialsList" class="list-group mb-3">
                        <!-- Will be populated via AJAX -->
                    </div>
                </div>

                <!-- Add New Material -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Add Material Requirement</h6>
                    </div>
                    <div class="card-body">
                        <form id="addMaterialRequirementForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Material</label>
                                    <select class="form-select" id="newMaterialId" required>
                                        <option value="">Select material...</option>
                                        <!-- Will be populated via AJAX -->
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Quantity Needed (per unit)</label>
                                    <input type="number" class="form-control" id="newMaterialQuantity" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-plus-circle"></i> Add
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Load product materials when modal opens
$('#manageProductMaterialsModal').on('show.bs.modal', function (event) {
    const button = $(event.relatedTarget);
    const productId = button.data('product-id');
    $('#materialProductId').val(productId);
    
    loadProductMaterials(productId);
    loadAvailableMaterials();
});

function loadProductMaterials(productId) {
    $.ajax({
        url: 'backend/get_product_materials.php',
        method: 'GET',
        data: { product_id: productId },
        success: function(response) {
            const data = JSON.parse(response);
            let html = '';
            
            if (data.length === 0) {
                html = '<div class="list-group-item text-muted">No materials defined yet.</div>';
            } else {
                data.forEach(item => {
                    html += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${item.material_name}</strong>
                                <br><small class="text-muted">${item.quantity_needed} ${item.unit} per unit</small>
                            </div>
                            <button class="btn btn-sm btn-danger" onclick="removeMaterial(${item.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    `;
                });
            }
            
            $('#currentMaterialsList').html(html);
        }
    });
}

function loadAvailableMaterials() {
    $.ajax({
        url: 'backend/get_materials_list.php',
        method: 'GET',
        success: function(response) {
            const data = JSON.parse(response);
            let options = '<option value="">Select material...</option>';
            
            data.forEach(material => {
                options += `<option value="${material.material_id}">${material.material_name} (${material.stock} ${material.unit} available)</option>`;
            });
            
            $('#newMaterialId').html(options);
        }
    });
}

$('#addMaterialRequirementForm').on('submit', function(e) {
    e.preventDefault();
    
    const productId = $('#materialProductId').val();
    const materialId = $('#newMaterialId').val();
    const quantity = $('#newMaterialQuantity').val();
    
    $.ajax({
        url: 'backend/add_product_material.php',
        method: 'POST',
        data: {
            product_id: productId,
            material_id: materialId,
            quantity_needed: quantity
        },
        success: function(response) {
            if (response === 'success') {
                Swal.fire('Success', 'Material requirement added!', 'success');
                loadProductMaterials(productId);
                $('#newMaterialId').val('');
                $('#newMaterialQuantity').val('');
            } else {
                Swal.fire('Error', response, 'error');
            }
        }
    });
});

function removeMaterial(id) {
    Swal.fire({
        title: 'Remove Material?',
        text: 'This will remove the material requirement from this product.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, remove it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'backend/remove_product_material.php',
                method: 'POST',
                data: { id: id },
                success: function(response) {
                    if (response === 'success') {
                        Swal.fire('Removed!', 'Material requirement removed.', 'success');
                        const productId = $('#materialProductId').val();
                        loadProductMaterials(productId);
                    } else {
                        Swal.fire('Error', response, 'error');
                    }
                }
            });
        }
    });
}
</script>
