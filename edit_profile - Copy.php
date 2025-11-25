<?php
require('config/db.php');
require('config/session.php');

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT a.username, b.firstname, b.lastname, b.barangay_id, b.municipality_id, b.province_id, b.region_id, b.house, b.contact_number, a.email 
    FROM users a 
    LEFT JOIN userdetails b ON a.user_id = b.user_id 
    WHERE a.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch();

    if(isset($_SESSION['error_message'])){
        $error_message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
    }
    else if(isset($_SESSION['warning_message'])){
        $warning_message = $_SESSION['warning_message'];
        unset($_SESSION['warning_message']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Desktop: Keep the form contained */
        .container {
            max-width: 500px;
        }
        
        .card {
            border-radius: 10px;
        }
        .alert {
            border-radius: 10px;
        }
        .bg-primary{
            background-color: var(--bs-primary) !important;
        }
        .btn-outline-primary {
            color: var(--bs-primary);
            border-color: var(--bs-primary);
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
            color: #fff;
        }

        /* Tablet Responsiveness */
        @media (max-width: 768px) {
            .container {
                max-width: 90% !important;
                padding: 20px !important;
                margin: 0 auto !important;
            }
            
            .card {
                margin: 0 !important;
                width: 100% !important;
                border-radius: 15px !important;
            }
            
            .card-body {
                padding: 1.5rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px !important;
                min-height: 45px !important;
                padding: 12px 15px !important;
            }
            
            .form-label {
                font-size: 14px !important;
                font-weight: 600 !important;
                margin-bottom: 8px !important;
            }
            
            .btn {
                min-height: 45px !important;
                font-size: 16px !important;
                font-weight: 500 !important;
            }
        }

        /* Mobile Phones */
        @media (max-width: 576px) {
            .container {
                max-width: 95% !important;
                width: 95% !important;
                padding: 15px !important;
                margin: 0 auto !important;
            }
            
            .min-vh-100 {
                padding: 15px 0 !important;
            }
            
            .card {
                margin: 0 !important;
                width: 100% !important;
                border-radius: 12px !important;
            }
            
            .card-body {
                padding: 1.5rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px !important;
                min-height: 50px !important;
                padding: 15px !important;
                border-radius: 8px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            
            .form-label {
                font-size: 15px !important;
                font-weight: 600 !important;
                margin-bottom: 10px !important;
            }
            
            .btn {
                width: 100% !important;
                margin-bottom: 10px !important;
                min-height: 50px !important;
                font-size: 16px !important;
                border-radius: 8px !important;
            }
            
            .btn:last-child {
                margin-bottom: 0 !important;
            }
            
            .card-header {
                padding: 1rem 1.5rem !important;
            }
            
            .card-header h5 {
                font-size: 1.2rem !important;
                margin-bottom: 0 !important;
            }
            
            .input-group .btn {
                min-height: 50px !important;
                padding: 15px !important;
                border-radius: 0 8px 8px 0 !important;
            }
            
            .input-group .form-control {
                border-radius: 8px 0 0 8px !important;
            }
            
            .row .col-md-4, .row .col-sm-6, .row .col-12 {
                margin-bottom: 0.75rem !important;
            }
            
            .mb-3 {
                margin-bottom: 1.5rem !important;
            }
        }

        /* Extra small phones */
        @media (max-width: 375px) {
            .container {
                max-width: 98% !important;
                width: 98% !important;
                padding: 10px !important;
                margin: 0 auto !important;
            }
            
            .card-body {
                padding: 1.25rem !important;
            }
            
            .form-control, .form-select {
                font-size: 16px !important;
                min-height: 52px !important;
                padding: 16px 12px !important;
            }
            
            .btn {
                min-height: 52px !important;
                padding: 16px 12px !important;
            }
            
            .input-group .btn {
                min-height: 52px !important;
                padding: 16px 12px !important;
            }
        }
    </style>
</head>
<body>
<div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg w-100">
        <div class="card-header bg-primary text-white text-center">
            <h5 class="mb-0">Edit Profile</h5>
        </div>
        <div class="card-body">
            <form action="profile/update_profile.php" method="POST">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo $user_data['username']?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="firstname" value="<?php echo $user_data['firstname'] ?? ''; ?>" required>
                </div>
                <div class="mb-3">
                    <labe class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="lastname" value="<?php echo $user_data['lastname'] ?? ''; ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Region</label>
                    <?php 
                        $stmt = $pdo->prepare("SELECT * FROM table_region");
                        $stmt->execute();
                        $region = $stmt->fetchAll();
                    ?>
                    <select class="form-select" name="region_id" id="region" required>
                    <?php foreach($region as $row): ?>
                        <option value="<?php echo $row['region_id']; ?>"
                            <?php if ($row['region_id'] == $user_data['region_id']) echo 'selected'; ?>>
                            <?php echo $row['region_name']; ?>
                        </option>
                    <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="mb-3">
                            <label class="form-label">Province</label>
                            <select name="province_id" id="province" class="form-select" required>
                                <option selected disabled>Select Province</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12">
                        <div class="mb-3">
                            <label class="form-label">Municipality</label>
                            <select name="municipality_id" id="municipality" class="form-select" required>
                                <option selected disabled>Select Municipality</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 col-12">
                        <div class="mb-3">
                            <label class="form-label">Barangay</label>
                            <select name="barangay_id" id="barangay" class="form-select" required>
                                <option selected disabled>Select Barangay</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Street/House Number</label>
                    <input type="text" class="form-control" name="house" value="<?php echo $user_data['house'];?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-control" name="contact_number" value="<?php echo $user_data['contact_number'] ?? ''; ?>" required id="contact-number" placeholder="09123456789">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <input type="email" class="form-control" value="<?php echo $user_data['email'] ?? ''; ?>" disabled>
                        <a href="change_email.php" class="btn btn-outline-primary">Change Email</a>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" value="********" disabled>
                        <a href="change_password.php" class="btn btn-outline-primary">Change Password</a>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to Home</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS + SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Restrict input to digits only and limit to 11 digits
    document.getElementById('contact-number').addEventListener('input', function (event) {
        // Remove non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit the input to 11 digits
        if (this.value.length > 11) {
            this.value = this.value.slice(0, 11);
        }
    });
</script>
<?php if(isset($error_message)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $error_message; ?>'
    }).then(() => {
        window.location.href = 'edit_profile.php';
    });
</script>
<?php elseif(isset($warning_message)): ?>
<script>
    Swal.fire({
        icon: 'warning',
        title: 'Warning!',
        text: '<?php echo $warning_message; ?>'
    }).then(() => {
        window.location.href = 'edit_profile.php';
    });
</script>
<?php elseif(isset($not_found)): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?php echo $not_found; ?>'
    }).then(() => {
        window.location.href = 'index.php';
    });
</script>
<?php endif; ?>

<script>
function loadLocation(parentId, type, targetSelectId, selectedValue = null) {
    fetch(`backend/get_location.php?type=${type}&parent_id=${parentId}`)
    .then(response => response.json())
    .then(data => {
        const select = document.getElementById(targetSelectId);
        select.innerHTML = `<option selected disabled>Select ${type.charAt(0).toUpperCase() + type.slice(1)}</option>`;
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            if (selectedValue && item.id == selectedValue) {
                option.selected = true;
            }
            select.appendChild(option);
        });

        // Clear dependent dropdowns
        if (type === 'province') {
            document.getElementById('municipality').innerHTML = '<option selected disabled>Select Municipality</option>';
            document.getElementById('barangay').innerHTML = '<option selected disabled>Select Barangay</option>';
        } else if (type === 'municipality') {
            document.getElementById('barangay').innerHTML = '<option selected disabled>Select Barangay</option>';
        }
    })
    .catch(err => console.error(err));
}

document.addEventListener('DOMContentLoaded', function () {
    const userRegionId = <?php echo json_encode($user_data['region_id']); ?>;
    const userProvinceId = <?php echo json_encode($user_data['province_id']); ?>;
    const userMunicipalityId = <?php echo json_encode($user_data['municipality_id']); ?>;
    const userBarangayId = <?php echo json_encode($user_data['barangay_id']); ?>;

    if (userRegionId) {
        loadLocation(userRegionId, 'province', 'province', userProvinceId);

        setTimeout(() => {
            if (userProvinceId) {
                loadLocation(userProvinceId, 'municipality', 'municipality', userMunicipalityId);
            }
        }, 300);

        setTimeout(() => {
            if (userMunicipalityId) {
                loadLocation(userMunicipalityId, 'barangay', 'barangay', userBarangayId);
            }
        }, 600);
    }
});

// Event listeners (keep them for when user changes dropdowns)
document.getElementById('region').addEventListener('change', function () {
    loadLocation(this.value, 'province', 'province');
});

document.getElementById('province').addEventListener('change', function () {
    loadLocation(this.value, 'municipality', 'municipality');
});

document.getElementById('municipality').addEventListener('change', function () {
    loadLocation(this.value, 'barangay', 'barangay');
});
</script>

</body>
</html>
