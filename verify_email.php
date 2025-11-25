<?php
    require('config/db.php');
    require('config/session.php');

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT email, is_verified FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $user_data = $stmt->fetch();

    if (!$user_data || empty($user_data['email'])) {
        $error_message = 'User or email not found.';
    }
    if($user_data['is_verified']){
        $already_verified = "User already verified.";
    }

    // Check if user just registered or just logged in and OTP was sent
    $just_registered = isset($_SESSION['just_registered']) && $_SESSION['just_registered'] === true;
    $otp_sent = isset($_SESSION['otp_sent']) && $_SESSION['otp_sent'] === true;

    // Clear the flags after use
    if ($just_registered) {
        unset($_SESSION['just_registered']);
    }
    if ($otp_sent) {
        unset($_SESSION['otp_sent']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons (for icon use) -->
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
            background-color: var(--bs-primary) !important;
        }
        .alert-info {
            background-color: #e6e0ff;
            color: #5a4fb0;
            border: 1px solid #c5baff;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .card {
                margin: 10px;
                width: calc(100% - 20px) !important;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .container {
                padding: 20px 10px;
            }
            
            .min-vh-100 {
                min-height: auto;
            }
        }

        @media (max-width: 576px) {
            .card {
                margin: 5px;
                width: calc(100% - 10px) !important;
            }
            
            .card-body {
                padding: 0.75rem;
            }
            
            .alert {
                padding: 0.75rem;
            }
            
            .alert-heading {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>

    <!-- Main Container -->
    <div class="container py-5 d-flex justify-content-center align-items-center min-vh-100">

        <!-- OTP Form Card -->
        <div class="card shadow-lg w-100">
            <div class="card-header bg-primary text-white text-center">
                <h5 class="mb-0">Enter OTP for Email Verification</h5>
            </div>
            <div class="card-body">
                <!-- Verification Alert -->
                <div class="alert alert-info text-center" role="alert">
                    <h4 class="alert-heading">Email Verification Required</h4>
                    <?php if ($just_registered): ?>
                        <p>Welcome! We've sent a verification email to your email address.</p>
                        <p>Please check your email (including spam folder) and enter the 6-digit OTP below:</p>
                    <?php else: ?>
                        <p>Please confirm your email by entering the 6-digit OTP. Click "Send OTP" to receive a new verification code.</p>
                    <?php endif; ?>
                    <h5 class="mb-0"><?php echo htmlspecialchars($user_data['email']); ?></h5>
                </div>

                <!-- Form to send OTP -->
                <form action="verify_otp.php" method="POST" id="otp-form">
                    <?php if (!($just_registered || $otp_sent)): ?>
                    <div class="mb-3">
                        <button type="button" id="send-otp-btn" class="btn btn-primary w-100" onclick="sendOtp()">Send OTP to Email</button>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <button type="button" id="resend-otp-btn" class="btn btn-outline-primary w-100" onclick="resendOtp()" disabled>
                            <span id="resend-text">Resend OTP in <span id="countdown">60</span>s</span>
                        </button>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="otp" class="form-label">Enter OTP</label>
                        <input type="text" class="form-control" id="otp" name="otp" maxlength="6" pattern="\d{6}" required placeholder="Enter 6-digit OTP" <?php echo ($just_registered || $otp_sent) ? '' : 'disabled'; ?>>
                    </div>
                    
                    <div class="d-flex justify-content-center">
                        <button type="submit" class="btn btn-primary" id="verify-btn" <?php echo ($just_registered || $otp_sent) ? '' : 'disabled'; ?>>Verify OTP</button>
                    </div>
                    <?php if (!empty($user_data['is_verified'])): ?>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left-circle"></i> Back to Home</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        

    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


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
        let countdownInterval;
        let remainingTime = <?php echo $just_registered ? '60' : '0'; ?>;

        function startCountdown() {
            const countdown = document.getElementById('countdown');
            const resendBtn = document.getElementById('resend-otp-btn');
            const resendText = document.getElementById('resend-text');
            
            if (remainingTime > 0) {
                countdown.textContent = remainingTime;
                resendBtn.disabled = true;
                
                countdownInterval = setInterval(() => {
                    remainingTime--;
                    countdown.textContent = remainingTime;
                    
                    if (remainingTime <= 0) {
                        clearInterval(countdownInterval);
                        resendBtn.disabled = false;
                        resendText.innerHTML = 'Resend OTP';
                    }
                }, 1000);
            }
        }

        function sendOtp() {
            const sendBtn = document.getElementById('send-otp-btn');
            sendBtn.disabled = true;

            // Show loading spinner using Swal
            const loadingSwal = Swal.fire({
                title: 'Please Wait...',
                html: `
                    <div class="custom-spinner"></div>
                    <p>Sending OTP...</p>
                `,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            fetch('auth/send_otp.php')
                .then(response => response.json())
                .then(data => {
                    // Close the loading Swal
                    loadingSwal.close();

                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'OTP Sent!',
                            text: 'The 6-digit OTP has been sent to your email.',
                            confirmButtonColor: '#a084f5'
                        });
                        
                        // Enable form elements
                        document.getElementById('otp').disabled = false;
                        document.getElementById('verify-btn').disabled = false;
                        document.getElementById('otp').focus();
                        
                        // Convert to resend button with countdown
                        sendBtn.outerHTML = `
                            <button type="button" id="resend-otp-btn" class="btn btn-outline-primary w-100" onclick="resendOtp()" disabled>
                                <span id="resend-text">Resend OTP in <span id="countdown">60</span>s</span>
                            </button>
                        `;
                        
                        // Start countdown
                        remainingTime = 60;
                        startCountdown();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Unable to Send OTP',
                            text: data.message || 'Failed to send OTP. Please try again later.',
                            confirmButtonColor: '#a084f5'
                        });
                        sendBtn.disabled = false;
                    }
                })
                .catch(error => {
                    // Close the loading Swal
                    loadingSwal.close();
                    
                    console.error('Fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Please check your internet connection and try again.',
                        confirmButtonColor: '#a084f5'
                    });
                    sendBtn.disabled = false;
                });
        }

        function resendOtp() {
            const resendBtn = document.getElementById('resend-otp-btn');
            resendBtn.disabled = true;

            // Show loading spinner using Swal
            const loadingSwal = Swal.fire({
                title: 'Please Wait...',
                html: `
                    <div class="custom-spinner"></div>
                    <p>Resending OTP...</p>
                `,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            fetch('auth/send_otp.php')
                .then(response => response.json())
                .then(data => {
                    // Close the loading Swal
                    loadingSwal.close();

                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'OTP Resent!',
                            text: 'A new 6-digit OTP has been sent to your email.',
                            confirmButtonColor: '#a084f5'
                        });
                        
                        // Reset countdown
                        remainingTime = 60;
                        document.getElementById('resend-text').innerHTML = 'Resend OTP in <span id="countdown">60</span>s';
                        startCountdown();
                        
                        // Clear previous OTP input
                        document.getElementById('otp').value = '';
                        document.getElementById('otp').focus();
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Unable to Resend OTP',
                            text: data.message || 'Failed to resend OTP. Please try again later.',
                            confirmButtonColor: '#a084f5'
                        });
                        resendBtn.disabled = false;
                    }
                })
                .catch(error => {
                    // Close the loading Swal
                    loadingSwal.close();
                    
                    console.error('Fetch error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Please check your internet connection and try again.',
                        confirmButtonColor: '#a084f5'
                    });
                    resendBtn.disabled = false;
                });
        }

        // Auto-focus OTP input for just-registered users
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($just_registered): ?>
                document.getElementById('otp').focus();
                startCountdown();
            <?php endif; ?>
        });

        // Real-time OTP input formatting
        document.getElementById('otp').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, ''); // Only numbers
            this.value = value;
            
            // Auto-submit when 6 digits entered
            if (value.length === 6) {
                setTimeout(() => {
                    document.getElementById('otp-form').dispatchEvent(new Event('submit'));
                }, 300);
            }
        });

        document.getElementById('otp-form').addEventListener('submit', function (event) {
            event.preventDefault();

            const otp = document.getElementById('otp').value;
            const verifyBtn = document.getElementById('verify-btn');
            
            if (otp.length !== 6) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid OTP',
                    text: 'Please enter a 6-digit OTP.',
                    confirmButtonColor: '#a084f5'
                });
                return;
            }
            
            verifyBtn.disabled = true;

            // Show loading spinner using Swal
            const loadingSwal = Swal.fire({
                title: 'Please Wait...',
                html: `
                    <div class="custom-spinner"></div>
                    <p>Verifying OTP...</p>
                `,
                showConfirmButton: false,
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            fetch('auth/verify_otp.php', {
                method: 'POST',
                body: new FormData(document.getElementById('otp-form')),
            })
            .then(response => response.json())
            .then(data => {
                // Close the loading spinner
                loadingSwal.close();

                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Verified!',
                        text: data.message || 'Your email has been successfully verified!',
                        confirmButtonColor: '#a084f5'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Verification Failed',
                        text: data.message || 'Invalid or expired OTP. Please try again.',
                        confirmButtonColor: '#a084f5'
                    });
                    verifyBtn.disabled = false;
                    document.getElementById('otp').value = '';
                    document.getElementById('otp').focus();
                }
            })
            .catch(error => {
                // Close the loading spinner
                loadingSwal.close();

                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Unexpected Error',
                    text: 'An unexpected error occurred. Please try again later.',
                    confirmButtonColor: '#a084f5'
                });
                verifyBtn.disabled = false;
            });
        });
    </script>

    <!-- swal -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if(isset($error_message)):?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $error_message; ?>'
            }).then(() => {
                <?php unset($_SESSION['error_message']); ?>
                window.location.href = 'verify_email.php';
            });
    </script>
    <?php elseif(isset($already_verified)): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Already Verified!',
                text: '<?php echo $already_verified; ?>'
            }).then(() => {
                <?php unset($_SESSION['error_message']); unset($_SESSION['success_message']); ?>
                window.location.href = 'index.php';
            });
        </script>
    <?php endif; ?>
</body>

</html>
