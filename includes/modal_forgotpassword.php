<!-- Modal for Reset Password -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="auth/send_reset_link.php" class="modal-content" id="resetForm">
        <div class="modal-header">
            <h5 class="modal-title">Reset Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <label for="resetEmail" class="form-label">Enter your email address</label>
            <input type="email" name="email" id="resetEmail" class="form-control" required>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="sendreset">Send Reset Link</button>
        </div>
    </form>
  </div>
</div>
