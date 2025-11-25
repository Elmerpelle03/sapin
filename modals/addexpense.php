<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-labelledby="addExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addExpenseModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>Add New Expense
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addExpenseForm" action="backend/add_expense.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expenseCategory" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="expenseCategory" name="expense_category" required>
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
                            <label for="expenseName" class="form-label">Expense Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="expenseName" name="expense_name" placeholder="e.g., Cotton Fabric Purchase" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="expenseDate" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="expenseDate" name="expense_date" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Optional notes about this expense"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="receipt" class="form-label">Receipt/Proof (Optional)</label>
                        <input type="file" class="form-control" id="receipt" name="receipt" accept="image/*,.pdf">
                        <small class="text-muted">Upload receipt image or PDF (Max 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Set today's date as default
    $('#expenseDate').val(new Date().toISOString().split('T')[0]);

    $('#addExpenseForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'backend/add_expense.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Expense added successfully!'
                    }).then(() => {
                        $('#addExpenseModal').modal('hide');
                        $('#addExpenseForm')[0].reset();
                        $('#expensesTable').DataTable().ajax.reload();
                        if (typeof loadSummary === 'function') loadSummary();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response || 'Failed to add expense'
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
