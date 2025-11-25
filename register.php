<?php 
  require 'config/db.php';
  session_start();
  if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Register</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="assets/css/style.css" rel="stylesheet" />

  <style>
    :root {
      --primary-color: #2563eb;
      --secondary-color: #f59e0b;
      --accent-color: #ffffff;
    }
    .bg-custom {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    }
    #loading-overlay.d-none {
      display: none !important;
    }
    .brand {
      color: #2563eb !important;
    }
    .btn-primary {
      background-color: #2563eb !important;
      border-color: #2563eb !important;
    }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
      background-color: #1d4ed8 !important;
      border-color: #1d4ed8 !important;
    }
    .btn-outline-primary {
      color: #2563eb !important;
      border-color: #2563eb !important;
    }
    .btn-outline-primary:hover, .btn-outline-primary:focus, .btn-outline-primary:active {
      background-color: #2563eb !important;
      border-color: #2563eb !important;
      color: white !important;
    }
    .text-primary {
      color: #2563eb !important;
    }
    .toggle-password i {
      color: #2563eb !important;
    }
    
    /* Hide browser's default password reveal button */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
      display: none;
    }
    input[type="password"]::-webkit-credentials-auto-fill-button,
    input[type="password"]::-webkit-contacts-auto-fill-button {
      visibility: hidden;
      pointer-events: none;
      position: absolute;
      right: 0;
    }
    
    .form-select:focus, .form-control:focus {
      border-color: #2563eb !important;
      box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25) !important;
    }
    .card {
      border-radius: 15px !important;
    }
    .section-divider {
      border: none;
      height: 2px;
      background: linear-gradient(90deg, #2563eb, #f59e0b);
      border-radius: 2px;
    }
    .form-label {
      font-weight: 600;
      color: #374151;
    }
    .input-group-text {
      background-color: #f8fafc;
      border-color: #e5e7eb;
    }
    .form-control, .form-select {
      border-color: #e5e7eb;
    }
    .form-control:hover, .form-select:hover {
      border-color: #2563eb;
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 768px) {
      .container-fluid {
        padding: 1rem !important;
      }
      .col-lg-10 {
        margin: 0;
      }
      .card-body {
        padding: 1.5rem !important;
      }
      .btn-lg {
        padding: 0.8rem 2rem !important;
        font-size: 1rem !important;
      }
      h3.brand {
        font-size: 1.5rem !important;
      }
      .col-md-4, .col-md-6 {
        margin-bottom: 1rem !important;
      }
      .input-group-text {
        min-width: 45px;
        justify-content: center;
      }
      .section-divider {
        margin: 1rem 0 !important;
      }
    }
    
    @media (max-width: 576px) {
      .container-fluid {
        padding: 0.5rem !important;
      }
      .card {
        margin: 0.5rem !important;
        border-radius: 10px !important;
      }
      .card-body {
        padding: 1rem !important;
      }
      h3.brand {
        font-size: 1.3rem !important;
      }
      h5 {
        font-size: 1.1rem !important;
      }
      .btn-lg {
        padding: 0.7rem 1.5rem !important;
        font-size: 0.9rem !important;
      }
      .form-label {
        font-size: 0.9rem !important;
      }
      .input-group-text {
        min-width: 40px;
        padding: 0.5rem !important;
      }
    }
  </style>
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-white">

  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="row w-100 justify-content-center">
      <div class="col-lg-10 col-xl-8">
        <div class="card border-0 shadow-lg">
          <form id="registerForm" method="POST" action="auth/register.php">
            <div class="text-center mb-4 mt-4">
              <img src="assets/img/logo_forsapin.jpg" style="width: 120px;" alt="logo">
              <h3 class="mt-3 brand">SAPIN Registration</h3>
              <p class="text-muted">Create your account to start shopping</p>
            </div>

            <div class="card-body px-5">
              <!-- Account Information Section -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary mb-3"><i class="bi bi-person-circle me-2"></i>Account Information</h5>
                  <hr class="section-divider mb-4">
                </div>
                <div class="col-md-4 mb-3">
                  <label for="username" class="form-label">Username</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill text-primary"></i></span>
                    <input type="text" name="username" class="form-control" placeholder="Username" required
                      value="<?= isset($_SESSION['reg_username']) ? htmlspecialchars($_SESSION['reg_username']) : '' ?>">
                  </div>
                </div>
                <div class="col-md-4 mb-3">
                  <label for="email" class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope-fill text-primary"></i></span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required
                      oninput="checkEmailAvailability(this.value)"
                      value="<?= isset($_SESSION['reg_email']) ? htmlspecialchars($_SESSION['reg_email']) : '' ?>">
                  </div>
                  <div id="emailFeedback" class="small mt-1"></div>
                </div>
                <div class="col-md-4 mb-3">
                  <label for="contact_number" class="form-label">Contact Number (Philippines)</label>
                  <div class="input-group">
                    <span class="input-group-text">+63</span>
                    <input type="text" name="contact_number" id="contact_number" class="form-control" placeholder="9123456789" 
                      pattern="9\d{9}" maxlength="10" required
                      oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);"
                      value="<?= isset($_SESSION['reg_contact_number']) ? str_replace('+63', '', htmlspecialchars($_SESSION['reg_contact_number'])) : '' ?>">
                    <div class="invalid-feedback">
                      Please enter a valid 10-digit Philippine mobile number starting with 9 (e.g., 9123456789).
                    </div>
                  </div>
                  <small class="text-muted">Format: 9123456789 (10 digits starting with 9)</small>
                </div>
              </div>

              <!-- Password Section -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary mb-3"><i class="bi bi-shield-lock me-2"></i>Security</h5>
                  <hr class="section-divider mb-4">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="reg_password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill text-primary"></i></span>
                    <input type="password" name="password" id="reg_password" class="form-control" placeholder="Enter your password" required>
                    <span class="input-group-text toggle-password" data-target="reg_password" style="cursor: pointer;">
                      <i class="bi bi-eye-fill text-primary"></i>
                    </span>
                  </div>
                  <small class="text-muted">Must be at least 8 characters with uppercase, lowercase, number, and special character</small>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="reg_confirm_password" class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill text-primary"></i></span>
                    <input type="password" name="confirm_password" id="reg_confirm_password" class="form-control" placeholder="Confirm your password" required>
                    <span class="input-group-text toggle-password" data-target="reg_confirm_password" style="cursor: pointer;">
                      <i class="bi bi-eye-fill text-primary"></i>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Personal Information Section -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>Personal Information</h5>
                  <hr class="section-divider mb-4">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="firstname" class="form-label">First Name</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill text-primary"></i></span>
                    <input type="text" name="firstname" id="firstname" class="form-control" 
                      placeholder="First name" required minlength="2" maxlength="50"
                      oninput="validateNameInput(this)" 
                      onblur="validateNameField(this)"
                      title="Please enter a valid name (2-50 characters, letters, spaces, hyphens, and apostrophes only)"
                      value="<?= isset($_SESSION['reg_firstname']) ? htmlspecialchars($_SESSION['reg_firstname']) : '' ?>">
                    <div class="invalid-feedback">
                      Please enter a valid first name (2-50 characters, letters, spaces, hyphens, and apostrophes only)
                    </div>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="lastname" class="form-label">Last Name</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill text-primary"></i></span>
                    <input type="text" name="lastname" id="lastname" class="form-control" 
                      placeholder="Last name" required minlength="2" maxlength="50"
                      oninput="validateNameInput(this)" 
                      onblur="validateNameField(this)"
                      title="Please enter a valid name (2-50 characters, letters, spaces, hyphens, and apostrophes only)"
                      value="<?= isset($_SESSION['reg_lastname']) ? htmlspecialchars($_SESSION['reg_lastname']) : '' ?>">
                    <div class="invalid-feedback">
                      Please enter a valid last name (2-50 characters, letters, spaces, hyphens, and apostrophes only)
                    </div>
                  </div>
                </div>
              </div>

              <!-- Address Information Section -->
              <div class="row mb-4">
                <div class="col-12">
                  <h5 class="text-primary mb-3"><i class="bi bi-geo-alt me-2"></i>Address Information</h5>
                  <hr class="section-divider mb-4">
                </div>
                <div class="col-md-6 mb-3">
                  <label for="region" class="form-label">Region</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                    <?php 
                      $stmt = $pdo->prepare("SELECT * from table_region");
                      $stmt->execute();
                      $region = $stmt->fetchAll();
                    ?>
                    <select name="region_id" id="region" class="form-select" required>
                      <option disabled <?= !isset($_SESSION['reg_region_id']) ? 'selected' : '' ?>>Select Region</option>
                      <?php foreach($region as $row): ?>
                        <option value="<?= $row['region_id'] ?>" 
                          <?= (isset($_SESSION['reg_region_id']) && $_SESSION['reg_region_id'] == $row['region_id']) ? 'selected' : '' ?>>
                          <?= $row['region_name'] ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="province" class="form-label">Province</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                    <select name="province_id" id="province" class="form-select" required>
                      <option selected disabled>Select Province</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="municipality" class="form-label">Municipality</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                    <select name="municipality_id" id="municipality" class="form-select" required>
                      <option selected disabled>Select Municipality</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="barangay" class="form-label">Barangay</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                    <select name="barangay_id" id="barangay" class="form-select" required>
                      <option selected disabled>Select Barangay</option>
                    </select>
                  </div>
                </div>
                <div class="col-12 mb-3">
                  <label for="house" class="form-label">Street/House Number</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-house-fill text-primary"></i></span>
                    <input type="text" name="house" class="form-control" placeholder="Street/House Number" required
                      value="<?= isset($_SESSION['reg_house']) ? htmlspecialchars($_SESSION['reg_house']) : '' ?>">
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="row mb-4">
                <div class="col-12 text-center">
                  <button id="register-btn" class="btn btn-primary btn-lg px-5 py-3" type="submit">
                    <i class="bi bi-person-plus me-2"></i>Create Account
                  </button>
                </div>
              </div>

              <div class="text-center mb-4">
                <p class="text-muted mb-2">Already have an account?</p>
                <button type="button" id="login-btn" class="btn btn-outline-primary">Login</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Name Validation Script -->
  <script>
    // Function to validate name input (allows international characters, spaces, hyphens, and apostrophes)
    function validateNameInput(input) {
      // Allow letters (including international), spaces, hyphens, and apostrophes
      input.value = input.value
        .replace(/[^\p{L} '\-]/gu, '') // Remove invalid characters
        .replace(/  +/g, ' ')            // Replace multiple spaces with single space
        .replace(/^\s+|\s+$/g, '')      // Trim leading/trailing spaces
        .replace(/''+/g, "'")            // Replace multiple apostrophes with single
        .replace(/--+/g, '-')            // Replace multiple hyphens with single
        .replace(/(^|\s)-(\s|$)/g, '$1$2') // Remove hyphens between spaces
        .replace(/(^|\s)'(\s|$)/g, '$1$2'); // Remove apostrophes between spaces
      
      // Update the validation state
      validateNameField(input);
    }
    
    // Function to validate name field and show feedback
    function validateNameField(input) {
      const isValid = /^[\p{L}' -]{2,50}$/u.test(input.value);
      input.setCustomValidity(isValid ? '' : 'Please enter a valid name (2-50 characters, letters, spaces, hyphens, and apostrophes only)');
      input.reportValidity();
    }
    
    // Password Toggle Script
    document.querySelectorAll('.toggle-password').forEach(toggle => {
      toggle.addEventListener('click', () => {
        const targetId = toggle.getAttribute('data-target');
        const input = document.getElementById(targetId);
        const icon = toggle.querySelector('i');

        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.replace('bi-eye-fill', 'bi-eye-slash-fill');
        } else {
          input.type = 'password';
          icon.classList.replace('bi-eye-slash-fill', 'bi-eye-fill');
        }
      });
    });

    // Form Value Retention with LocalStorage
    document.addEventListener('DOMContentLoaded', function() {
      // Fields to save (excluding passwords)
      const fieldsToSave = [
        'username', 'email', 'firstname', 'lastname', 'contact_number',
        'house', 'region_id', 'province_id', 'municipality_id', 'barangay_id'
      ];

      // Load saved values from localStorage
      fieldsToSave.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        const savedValue = localStorage.getItem(`register_${fieldName}`);
        if (field && savedValue && !field.value) {
          field.value = savedValue;
          // Trigger change event for select elements
          if (field.tagName === 'SELECT') {
            field.dispatchEvent(new Event('change'));
          }
        }
      });

      // Save values to localStorage on input
      fieldsToSave.forEach(fieldName => {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
          field.addEventListener('input', function() {
            localStorage.setItem(`register_${fieldName}`, this.value);
          });
          field.addEventListener('change', function() {
            localStorage.setItem(`register_${fieldName}`, this.value);
          });
        }
      });

      // Clear localStorage on successful registration
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('success') === '1') {
        fieldsToSave.forEach(fieldName => {
          localStorage.removeItem(`register_${fieldName}`);
        });
      }

      // Clear password fields on page load
      document.querySelector('[name="password"]').value = '';
      document.querySelector('[name="confirm_password"]').value = '';
    });
  </script>

          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- swal -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    .custom-spinner {
      width: 3rem;
      height: 3rem;
      border: 0.4rem solid #f3f3f3;
      border-top: 0.4rem solid #2563eb;
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
      const registerBtn = document.getElementById('register-btn');
      const loginBtn = document.getElementById('login-btn');

      const customSwal = (title, text) => {
        Swal.fire({
          title: title,
          html: `
            <div class="custom-spinner"></div>
            <p>${text}</p>
          `,
          showConfirmButton: false,
          allowOutsideClick: false,
          allowEscapeKey: false
        });
      };

      if (registerBtn) {
        registerBtn.addEventListener('click', function (event) {
          event.preventDefault();
          
          const password = document.querySelector('input[name="password"]').value;
          const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

          const form = document.getElementById('registerForm');

          // Check required address fields
          const regionId = document.querySelector('select[name="region_id"]').value;
          const provinceId = document.querySelector('select[name="province_id"]').value;
          const municipalityId = document.querySelector('select[name="municipality_id"]').value;
          const barangayId = document.querySelector('select[name="barangay_id"]').value;
          
          if (!regionId || regionId === '' || !provinceId || provinceId === '' || !municipalityId || municipalityId === '' || !barangayId || barangayId === '') {
            Swal.fire({
              icon: 'error',
              title: 'Incomplete Address',
              text: 'Please select your complete address (Region, Province, Municipality, and Barangay).'
            });
            return;
          }

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

          if (password !== confirmPassword) {
            Swal.fire({
              icon: 'error',
              title: 'Password Mismatch',
              text: 'Confirm password does not match.'
            });
          } else {
            customSwal('Please Wait...', 'Submitting your registration...');
            setTimeout(() => {
              Swal.close();
              form.submit();
            }, 1000);
          }
        });
      }


      if (loginBtn) {
        loginBtn.addEventListener('click', function () {
          customSwal('Please Wait...', 'Redirecting to login...');
          setTimeout(() => {
            Swal.close();
            window.location.href = 'login.php';
          }, 1000);
        });
      }
    });
  </script>

  <script>
    <?php if (isset($error_message)): ?>
      Swal.fire({
        icon: 'error',
        title: 'Login Failed.',
        text: '<?php echo $error_message; ?>',
        confirmButtonText: 'OK'
      });
    <?php elseif (isset($success_message)): ?>
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: '<?php echo $success_message; ?>',
        confirmButtonText: 'OK'
      });
    <?php endif; ?>
  </script>
  <script>
    function loadLocation(parentId, type, targetSelectId) {
      fetch(`backend/get_location.php?type=${type}&parent_id=${parentId}`)
        .then(response => response.json())
        .then(data => {
          const select = document.getElementById(targetSelectId);
          select.innerHTML = `<option selected disabled>Select ${type.charAt(0).toUpperCase() + type.slice(1)}</option>`;
          data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
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

    // Event listeners
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

<style>
  /* Email validation feedback styles */
  .email-valid { color: #198754; }
  .email-invalid { color: #dc3545; }
  .email-checking { color: #6c757d; }
  .email-valid i, .email-invalid i, .email-checking i {
    font-size: 1.1em;
    vertical-align: middle;
  }
  #email { padding-right: 0; }
  #emailFeedback {
    min-height: 20px;
    font-size: 0.875em;
  }
</style>

<script>
  // Email availability check function
  let emailCheckTimeout;
  
  function checkEmailAvailability(email) {
    clearTimeout(emailCheckTimeout);
    
    const emailInput = document.getElementById('email');
    const emailStatus = document.getElementById('emailStatus');
    const emailFeedback = document.getElementById('emailFeedback');
    
    // Reset status
    if (emailStatus) {
      emailStatus.className = '';
      emailStatus.innerHTML = '<i class="bi bi-hourglass-split"></i>';
      emailStatus.classList.add('email-checking');
    }
    emailFeedback.textContent = 'Checking email availability...';
    emailFeedback.className = 'small mt-1 text-muted';
    
    // Basic email format validation
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      if (email.length > 0) {
        if (emailStatus) { emailStatus.innerHTML = '<i class="bi bi-x-circle"></i>'; emailStatus.classList.add('email-invalid'); }
        emailInput.classList.remove('is-valid');
        emailInput.classList.add('is-invalid');
        emailFeedback.textContent = 'Please enter a valid email address';
        emailFeedback.classList.add('text-danger');
        emailInput.setCustomValidity('Invalid email format');
      } else {
        if (emailStatus) { emailStatus.innerHTML = '<i class="bi bi-question-circle"></i>'; emailStatus.classList.add('text-muted'); }
        emailInput.classList.remove('is-valid', 'is-invalid');
        emailFeedback.textContent = '';
        emailInput.setCustomValidity('');
      }
      return;
    }
    
    // Only check server if email is valid format
    emailCheckTimeout = setTimeout(() => {
      fetch(`auth/check_email.php?email=${encodeURIComponent(email)}`)
        .then(response => response.json())
        .then(data => {
          if (data.available) {
            if (emailStatus) { emailStatus.innerHTML = '<i class="bi bi-check-circle"></i>'; emailStatus.className = 'email-valid'; }
            emailInput.classList.remove('is-invalid');
            emailInput.classList.add('is-valid');
            emailFeedback.textContent = data.message;
            emailFeedback.className = 'small mt-1 text-success';
            emailInput.setCustomValidity('');
          } else {
            if (emailStatus) { emailStatus.innerHTML = '<i class="bi bi-x-circle"></i>'; emailStatus.className = 'email-invalid'; }
            emailInput.classList.remove('is-valid');
            emailInput.classList.add('is-invalid');
            emailFeedback.textContent = data.message;
            emailFeedback.className = 'small mt-1 text-danger';
            emailInput.setCustomValidity('Email is already in use');
          }
        })
        .catch(error => {
          console.error('Error checking email:', error);
          if (emailStatus) { emailStatus.innerHTML = '<i class="bi bi-exclamation-triangle"></i>'; emailStatus.className = 'text-warning'; }
          emailInput.classList.remove('is-valid', 'is-invalid');
          emailFeedback.textContent = 'Error checking email availability';
          emailFeedback.className = 'small mt-1 text-warning';
          emailInput.setCustomValidity('');
        });
    }, 500); // 500ms delay before checking
  }
  
  // Password validation function
    function validatePassword(password) {
      // Check for at least 8 characters
      if (password.length < 8) return false;
      
      // Check for at least one uppercase letter
      if (!/[A-Z]/.test(password)) return false;
      
      // Check for at least one lowercase letter
      if (!/[a-z]/.test(password)) return false;
      
      // Check for at least one number
      if (!/[0-9]/.test(password)) return false;
      
      // Check for at least one special character (including underscore)
      if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) return false;
      
      return true;
    }

    // Add form submission handler
    document.getElementById('registerForm').addEventListener('submit', function(e) {
      const password = document.getElementById('reg_password').value;
      const confirmPassword = document.getElementById('reg_confirm_password').value;
      
      if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
      }
      
      if (!validatePassword(password)) {
        e.preventDefault();
        alert('Password must be at least 8 characters long and include uppercase, lowercase, number, and special character (including _)');
        return false;
      }
      
      return true;
    });

    document.addEventListener('DOMContentLoaded', () => {
      const sessionRegionId = '<?= isset($_SESSION['reg_region_id']) ? $_SESSION['reg_region_id'] : '' ?>';
    const sessionProvinceId = '<?= isset($_SESSION['reg_province_id']) ? $_SESSION['reg_province_id'] : '' ?>';
    const sessionMunicipalityId = '<?= isset($_SESSION['reg_municipality_id']) ? $_SESSION['reg_municipality_id'] : '' ?>';
    const sessionBarangayId = '<?= isset($_SESSION['reg_barangay_id']) ? $_SESSION['reg_barangay_id'] : '' ?>';

    if (sessionRegionId) {
      setTimeout(() => {
        document.getElementById('region').value = sessionRegionId;
        loadLocation(sessionRegionId, 'province', 'province');

        // Wait 500ms for provinces to load
        setTimeout(() => {
          if (sessionProvinceId) {
            document.getElementById('province').value = sessionProvinceId;
            loadLocation(sessionProvinceId, 'municipality', 'municipality');

            setTimeout(() => {
              if (sessionMunicipalityId) {
                document.getElementById('municipality').value = sessionMunicipalityId;
                loadLocation(sessionMunicipalityId, 'barangay', 'barangay');

                setTimeout(() => {
                  if (sessionBarangayId) {
                    document.getElementById('barangay').value = sessionBarangayId;
                  }
                }, 500);

              }
            }, 500);
          }
        }, 500);
      }, 500);
    }
  });
</script>




</body>
</html>
