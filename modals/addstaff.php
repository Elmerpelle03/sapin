

<div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="backend/add_staff.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">User Type</label>
                        <?php if (!$isPageUsers): ?>
                            <select class="form-select" disabled>
                                <option value="1">Admin</option>
                                <option value="4" selected>Courier</option>
                            </select>
                            <input type="hidden" name="usertype" value="4">
                        <?php else: ?>
                            <select name="usertype" class="form-select">
                                <option value="1">Admin</option>
                                <option value="4">Courier</option>
                            </select>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Password" required>
                    </div>
                    <input type="hidden" name="destination" value="<?php echo $isPageUsers ? 'users.php' : 'courier.php'; ?>">
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
