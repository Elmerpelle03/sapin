<?php 
    $app_status = null;
    if(isset($_SESSION['user_id'])){
        try {
            // Fetch most recent application status
            $stmt = $pdo->prepare('SELECT status FROM bulk_buyer_applications WHERE user_id = :user_id ORDER BY submitted_at DESC LIMIT 1');
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $app_status = $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Modal profile error: " . $e->getMessage());
        }
    }
    
?>

<!-- Modal for User Info -->
<?php if (isset($_SESSION['user_id']) && isset($user_data)): ?>
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></p>
                <p><strong>Full Name:</strong> <?php echo ($user_data['firstname'] ?? '').' '.($user_data['lastname'] ?? ''); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($user_data['address'] ?? 'N/A'); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($user_data['contact_number'] ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> 
                    <?php echo htmlspecialchars($user_data['email']); ?>
                    <?php if (!empty($user_data['is_verified']) && $user_data['is_verified']): ?>
                        <span class="text-primary ms-2" title="Verified">
                            <i class="bi bi-check-circle-fill"></i> Verified
                        </span>
                    <?php else: ?>
                        <span class="text-danger ms-2" title="Not Verified">
                            <i class="bi bi-x-circle-fill"></i> Not Verified
                        </span>
                        <a href="verify_email.php" class="ms-2 text-primary">Verify now</a>
                    <?php endif; ?>
                </p>
                <p><strong>Joined:</strong> <?php echo date('F j, Y', strtotime($user_data['join_date'])); ?></p>
                <p><strong>User Type:</strong> 
                    <?php echo $user_data['usertype_name']; ?>
                    <?php if ($user_data['usertype_id'] == 2): ?>
                        <?php if ($app_status === 'Pending'): ?>
                            <span class="ms-2 text-warning">Application Pending</span>
                        <?php elseif ($app_status === 'Declined'): ?>
                            <span class="ms-2 text-danger">Application Declined</span>
                            <a href="apply_bulkbuyer.php" class="ms-2 text-primary">Reapply</a>
                        <?php else: ?>
                            <a href="apply_bulkbuyer.php" class="ms-2 text-primary">Apply as Wholesaler</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="modal-footer">
                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
    .custom-spinner {
      width: 3rem;
      height: 3rem;
      border: 0.4rem solid #f3f3f3;
      border-top: 0.4rem solid var(--bs-primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin: 1rem auto;
    }

    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const logoutBtn = document.getElementById('logoutBtn');
    const modalElement = document.getElementById('userModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const navLoginBtn = document.getElementById('nav-login-btn');
    const navRegisterBtn = document.getElementById('nav-register-btn');

    const customSwal = (title, text) => {
        Swal.fire({
            title: title,
            html: `
                <div class="custom-spinner mb-2"></div>
                <p>${text}</p>
            `,
            showConfirmButton: false,
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    };

    if (navLoginBtn) {
        navLoginBtn.addEventListener('click', function (e) {
            e.preventDefault();
            customSwal('Please Wait...', 'Redirecting to login...');
            setTimeout(() => {
                Swal.close();
                window.location.href = 'login.php';
            }, 1000);
        });
    }

    if (navRegisterBtn) {
        navRegisterBtn.addEventListener('click', function (e) {
            e.preventDefault();
            customSwal('Please Wait...', 'Redirecting to register...');
            setTimeout(() => {
                Swal.close();
                window.location.href = 'register.php';
            }, 1000);
        });
    }

    if (logoutBtn && modal) {
        logoutBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Are you sure?',
                text: 'Do you really want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    customSwal('Logging out...', 'Please wait...');
                    modal.hide();
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.remove(); // remove modal backdrop manually
                    }
                    setTimeout(() => {
                        window.location.href = 'auth/logout.php';
                    }, 1000);
                }
            });
        });
    }
});
</script>