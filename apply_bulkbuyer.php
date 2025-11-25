<?php
require('config/db.php');
require('config/session.php');
require('config/session_disallow_courier.php');

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT a.username, b.firstname, b.lastname, b.house, tb.barangay_name, tm.municipality_name, tp.province_name, b.contact_number, a.email 
    FROM users a 
    LEFT JOIN userdetails b ON a.user_id = b.user_id 
    LEFT JOIN table_barangay tb ON b.barangay_id = tb.barangay_id
    LEFT JOIN table_municipality tm ON b.municipality_id = tm.municipality_id
    LEFT JOIN table_province tp ON b.province_id = tp.province_id
    WHERE a.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch();
}

if(isset($_SESSION['error_message'])){
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
else if(isset($_SESSION['warning_message'])){
    $warning_message = $_SESSION['warning_message'];
    unset($_SESSION['warning_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply as Wholesaler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
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
            background-color: #2563eb !important;
        }
        .btn-outline-primary {
            color: #2563eb;
            border-color: #2563eb;
        }

        .btn-outline-primary:hover,
        .btn-outline-primary:focus {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .btn-primary {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
        }
        .text-primary {
            color: #2563eb !important;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .container {
                max-width: 90% !important;
                padding: 1rem;
            }
            .card {
                margin: 1rem 0;
            }
            .card-body {
                padding: 1.5rem;
            }
            .btn {
                padding: 0.8rem 1.5rem;
                font-size: 0.9rem;
            }
            .form-control, .form-select {
                font-size: 0.9rem;
            }
            h4 {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 576px) {
            .container {
                max-width: 95% !important;
                padding: 0.5rem;
            }
            .card-body {
                padding: 1rem;
            }
            .btn {
                padding: 0.7rem 1.2rem;
                font-size: 0.8rem;
            }
            .form-control, .form-select {
                font-size: 0.8rem;
            }
            h4 {
                font-size: 1.2rem;
            }
            .input-group-text {
                min-width: 40px;
                padding: 0.5rem;
            }
        }
    </style>
    </style>
</head>
<body>
<div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg w-100">
        <div class="card-header bg-primary text-white text-center">
            <h5 class="mb-0">Apply as Wholesaler</h5>
        </div>
        <div class="card-body">
            <form action="backend/apply.php" method="POST" enctype="multipart/form-data" id="bulkBuyerForm">
                <input type="text" name="user_id" value="<?php echo $user_id?>" hidden>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="<?php echo $user_data['username']?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" value="<?php echo $user_data['firstname'].' '.$user_data['lastname'] ?? ''; ?>" disabled>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Street/House Number</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['house'] ?? ''; ?>" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Barangay</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['barangay_name'] ?? ''; ?>" disabled>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Municipality</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['municipality_name'] ?? ''; ?>" disabled>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label class="form-label">Province</label>
                            <input type="text" class="form-control" value="<?php echo $user_data['province_name'] ?? ''; ?>" disabled>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contact Number</label>
                    <input type="text" class="form-control" value="<?php echo $user_data['contact_number'] ?? ''; ?>" disabled id="contact-number" placeholder="09123456789">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <input type="email" class="form-control" value="<?php echo $user_data['email'] ?? ''; ?>" disabled>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Purpose of Applying <span class="text-danger">*</span></label>
                    <textarea name="purpose" class="form-control" rows="3" placeholder="Please describe why you want to become a wholesaler (e.g., business, events, etc.)" required></textarea>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Explain your reason for applying as a wholesaler.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Valid ID <span class="text-danger">*</span></label>
                        <select name="id_type" id="id_type" class="form-select" required>
                            <option value="">Select Valid ID</option>
                            <option value="National ID">National ID (PhilSys)</option>
                            <option value="UMID">UMID (Unified Multi-Purpose ID)</option>
                            <option value="Passport">Passport</option>
                            <option value="Drivers License">Driver's License</option>
                            <option value="SSS ID">SSS ID</option>
                            <option value="GSIS ID">GSIS ID</option>
                            <option value="Voters ID">Voter's ID (COMELEC)</option>
                            <option value="PhilHealth ID">PhilHealth ID</option>
                            <option value="TIN ID">TIN ID</option>
                            <option value="Postal ID">Postal ID</option>
                            <option value="PRC ID">PRC ID (Professional Regulation Commission)</option>
                            <option value="Senior Citizen ID">Senior Citizen ID</option>
                            <option value="PWD ID">PWD ID</option>
                            <option value="Company ID">Company ID</option>
                            <option value="Barangay ID">Barangay ID</option>
                            <option value="Other">Other Government-Issued ID</option>
                        </select>
                        <div class="form-text">
                            <i class="bi bi-info-circle"></i> Please select a government-issued or valid identification document.
                        </div>
                </div>
                <div class="mb-3" id="other_id_field" style="display: none;">
                    <label class="form-label">Please specify the ID type <span class="text-danger">*</span></label>
                    <input type="text" name="other_id_type" id="other_id_type" class="form-control" placeholder="e.g., OFW ID, OWWA ID, etc.">
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Please specify what type of government ID you are submitting.
                    </div>
                </div>
                <div class="mb-3">
                    <label for="imageUpload" class="form-label">Upload ID Photo <span class="text-danger">*</span></label>
                    <input class="form-control" type="file" name="image" accept="image/jpeg,image/jpg,image/png" required>
                    <div class="form-text">
                        <i class="bi bi-info-circle"></i> Upload a clear photo of your selected ID. Accepted formats: JPG, PNG (Max 5MB)
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
    
    // Show/hide "Other ID" specification field
    document.getElementById('id_type').addEventListener('change', function() {
        const otherIdField = document.getElementById('other_id_field');
        const otherIdInput = document.getElementById('other_id_type');
        
        if (this.value === 'Other') {
            otherIdField.style.display = 'block';
            otherIdInput.required = true;
        } else {
            otherIdField.style.display = 'none';
            otherIdInput.required = false;
            otherIdInput.value = ''; // Clear the field when hidden
        }
    });
    
    // Form validation before submit
    document.getElementById('bulkBuyerForm').addEventListener('submit', function(e) {
        const idType = document.getElementById('id_type').value;
        const otherIdType = document.getElementById('other_id_type').value.trim();
        
        // Check if "Other" is selected but not specified
        if (idType === 'Other' && !otherIdType) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Missing Information',
                text: 'Please specify what type of ID you are submitting.'
            });
            return false;
        }
    });
    
    // Validate file size before upload
    document.querySelector('input[type="file"][name="image"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Check file size (5MB = 5 * 1024 * 1024 bytes)
            const maxSize = 5 * 1024 * 1024;
            if (file.size > maxSize) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Please select an image smaller than 5MB.'
                });
                e.target.value = ''; // Clear the file input
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Please upload only JPG or PNG images.'
                });
                e.target.value = ''; // Clear the file input
                return;
            }
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
</body>
</html>
