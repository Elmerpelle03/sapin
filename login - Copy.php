<?php 
  session_start();
  if(isset($_SESSION['user_id'])){
    header('Location: index.php');
    exit();
  }
  if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
  }
  else if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
  }
  $userInput = "";
  if(isset($_GET['user'])){
    $userInput = $_GET['user'];
  }
  $destination = "";
  $des_id = "";
  if(isset($_GET['destination'])){
    $destination = $_GET['destination'];
  }
  if(isset($_GET['des_id'])){
    $des_id = $_GET['des_id'];
  }

  
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login Page</title>

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
    .btn-primary {
      background-color: #2563eb !important;
      border-color: #2563eb !important;
    }
    .btn-primary:hover, .btn-primary:focus, .btn-primary:active {
      background-color: #1d4ed8 !important;
      border-color: #1d4ed8 !important;
    }
    .btn-outline-secondary {
      color: #2563eb !important;
      border-color: #2563eb !important;
    }
    .btn-outline-secondary:hover, .btn-outline-secondary:focus, .btn-outline-secondary:active {
      background-color: #2563eb !important;
      border-color: #2563eb !important;
      color: white !important;
    }
    .text-primary {
      color: #2563eb !important;
    }
    .brand {
      color: #2563eb !important;
    }
    .toggle-password i {
      color: #2563eb !important;
    }
    
    /* Hide browser's built-in password reveal button */
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
    
    /* Mobile Responsiveness */
    @media (max-width: 992px) {
      .col-lg-6:first-child {
        order: 1;
      }
      .col-lg-6:last-child {
        order: 0;
        text-align: center;
        padding: 2rem 1rem !important;
      }
    }
    
    @media (max-width: 768px) {
      .container-fluid {
        padding: 1rem !important;
      }
      .col-lg-8 {
        margin: 0;
      }
      .card-body {
        padding: 2rem 1.5rem !important;
      }
      .col-lg-6 {
        padding: 0 !important;
      }
      .bg-custom .p-4 {
        padding: 1.5rem !important;
      }
      h4 {
        font-size: 1.3rem !important;
      }
      .btn {
        padding: 0.8rem 1.5rem !important;
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
        padding: 1.5rem 1rem !important;
      }
      .bg-custom .p-4 {
        padding: 1rem !important;
      }
      .bg-custom h4 {
        font-size: 1.2rem !important;
      }
      .bg-custom p {
        font-size: 0.85rem !important;
      }
      .input-group-text {
        min-width: 40px;
        padding: 0.5rem !important;
      }
      .form-control {
        font-size: 0.9rem !important;
      }
      .btn {
        padding: 0.7rem 1.2rem !important;
        font-size: 0.9rem !important;
      }
    }
  </style>

  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-white">

  <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center py-4">
    <div class="row w-100 justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow">
          <div class="row g-0">
            <div class="col-lg-6">
              <div class="card-body p-4">

                <div class="text-center mb-4">
                  <img src="assets/img/logo_forsapin.jpg" style="width: 150px;" alt="logo">
                  <h4 class="mt-3 brand">Sapin Bedsheets</h4>
                </div>

                <form method="POST" action="auth/login.php" id="loginForm">
                  <p>Please login to your account.</p>

                  <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-person-fill text-primary"></i></span>
                      <input type="text" name="user" id="username" value="<?php echo $userInput; ?>" class="form-control" placeholder="Username or email address">
                    </div>
                  </div>

                  <div class="mb-4">
                    <label for="login_password" class="form-label">Password</label>
                    <div class="input-group">
                      <span class="input-group-text"><i class="bi bi-lock-fill text-primary"></i></span>
                      <input type="password" name="password" id="login_password" class="form-control" placeholder="Enter your password" autocomplete="current-password">
                      <span class="input-group-text toggle-password" data-target="login_password" style="cursor: pointer;">
                        <i class="bi bi-eye-fill"></i>
                      </span>
                    </div>
                  </div>
                  <script>
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
                  </script>

                  <div class="d-grid mb-3">
                    <input type="text" name="destination" value="<?php echo $destination?>" hidden>
                    <input type="text" name="des_id" value="<?php echo $des_id?>" hidden>
                    <button id="login-btn" class="btn btn-primary" type="submit">Log in</button>
                  </div>

                  <div class="text-center mb-4">
                    <a href="#" class="text-muted" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
                  </div>

                  <div class="text-center">
                    <p class="mb-2">Don't have an account?</p>
                    <button type="button" id="register-btn" class="btn btn-outline-secondary">Create new</button>
                  </div>
                </form>

              </div>
            </div>
            <div class="col-lg-6 d-flex align-items-center bg-custom">
              <div class="p-4 text-white">
                <h4>We are more than just a company</h4>
                <p class="small">
                  At Sapin Bedsheets, we aim to provide the most comfortable and stylish bedsheets that enhance your sleep experience. Our commitment to quality and customer satisfaction is at the heart of everything we do.
                </p>
            
                <h5>Contact Us</h5>
                <p class="small">
                  Have any questions or need assistance? We're here to help! Reach out to us via email or social media.
                </p>
                <ul class="list-unstyled">
                  <li><strong>Email:</strong> support@sapinbedsheets.com</li>
                  <li><strong>Phone:</strong> +639 48 551 2573</li>
                </ul>
            
                <h5>Follow Us</h5>
                <ul class="list-inline">
                  <li class="list-inline-item"><a href="https://www.facebook.com/share/1B9EdmQiAm/" target="_blank" class="text-white"><i class="bi bi-facebook"></i> Facebook</a></li>
                  <li class="list-inline-item"><a href="https://www.instagram.com/sapinbedsheet?igsh=am8xMmZ0cWIwaWkw" target="_blank" class="text-white"><i class="bi bi-instagram"></i> Instagram</a></li>
                </ul>
            
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php require 'includes/modal_forgotpassword.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <!-- SweetAlert2 -->
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
    const loginBtn = document.getElementById('login-btn');
    const registerBtn = document.getElementById('register-btn');
    const sendreset = document.getElementById('sendreset');

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

    if (loginBtn) {
      loginBtn.addEventListener('click', function (event) {
        event.preventDefault();
        customSwal('Please Wait...', 'Logging in...');
        setTimeout(() => {
          Swal.close();
          document.getElementById('loginForm').submit();
        }, 1000);
      });
    }

    if (registerBtn) {
      registerBtn.addEventListener('click', function () {
        customSwal('Please Wait...', 'Redirecting to register...');
        setTimeout(() => {
          Swal.close();
          window.location.href = 'register.php';
        }, 1000);
      });
    }

    if (sendreset) {
      sendreset.addEventListener('click', function (event) {
        event.preventDefault(); // Prevent form submission
        customSwal('Please Wait...', 'Sending reset link...');
        
        const formData = new FormData(document.getElementById('resetForm')); // Gather form data

        // Sending AJAX request
        fetch('auth/send_reset_link.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json()) // Parse the response as JSON
        .then(data => {
          Swal.close(); // Close the loading screen
          if (data.status === 'success') {
            Swal.fire('Success!', 'Reset link has been sent to your email.', 'success');
          } else {
            Swal.fire('Error', data.message || 'Something went wrong.', 'error');
          }
        })
        .catch(error => {
          Swal.close();
          Swal.fire('Error', 'There was an error processing your request.', 'error');
        });
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

</body>
</html>
