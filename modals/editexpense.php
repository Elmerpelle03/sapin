<!-- Edit Expense Modal -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editExpenseModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>Edit Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editExpenseForm" action="backend/edit_expense.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="editExpenseId" name="expense_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editExpenseCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="editExpenseCategory" name="expense_category" required>
                                <option value="">Select Category</option>
                                <option value="Materials">Materials</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Salaries">Salaries</option>
                                <option value="Rent">Rent</option>
                                <option value="Transportation">Transportation</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Miscellaneous">Miscellaneous</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editExpenseName" class="form-label">Expense Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editExpenseName" name="expense_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editAmount" class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="editAmount" name="amount" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editExpenseDate" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editExpenseDate" name="expense_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editReceipt" class="form-label">Receipt/Proof (Optional)</label>
                        <input type="file" class="form-control" id="editReceipt" name="receipt" accept="image/*,.pdf">
                        <small class="text-muted">Upload new receipt to replace existing one (Max 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Update Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#editExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'backend/edit_expense.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Expense updated successfully!'
                    }).then(() => {
                        $('#editExpenseModal').modal('hide');
                        $('#expensesTable').DataTable().ajax.reload();
                        if (typeof loadSummary === 'function') loadSummary();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response || 'Failed to update expense'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An unexpected error occurred'
                });
            }
        });
    });
});
</script>
