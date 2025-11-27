<?php 
session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'register' => $_SESSION['register_error'] ?? '',
    'forgot_password' => $_SESSION['forgot_password_error'] ?? '',
];

$success_messages = [
    'login' => $_SESSION['login_success'] ?? '',
    'register' => $_SESSION['register_success'] ?? '',
    'forgot_password' => $_SESSION['forgot_password_success'] ?? '',
];

$activeForm = $_SESSION['active_form'] ?? 'login';

// Only clear specific session variables, NOT verified_email
unset($_SESSION['login_error']);
unset($_SESSION['register_error']); 
unset($_SESSION['forgot_password_error']);
unset($_SESSION['login_success']);
unset($_SESSION['register_success']);
unset($_SESSION['forgot_password_success']);
unset($_SESSION['active_form']);

function showError($error) {
    return !empty($error) ? "<p class = 'error-message'>$error</p>" : '';
}

function showSuccess($success) {
    return !empty($success) ? "<p class = 'success-message'>$success</p>" : '';
}

function isActiveForm($formName, $activeForm) {
    return $formName === $activeForm ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
  <link href="https://fonts.cdnfonts.com/css/poppins" rel="stylesheet">
  <title>Login | Register | Primelot Realty</title>
  <link rel="icon" type="image/svg+xml" href="Images/company_brand.svg">
  <link rel="icon" type="image/png" href="Images/company_brand.svg">
</head>
<style>
    * {
        box-sizing: border-box;
        font-family: 'Poppins', sans-serif;
    }

    .main_login_container {
        height: 90vh;
    }

    .row {
        display: flex;
        height: 100%;
    }

    .error-message {
        color: #dc3545;
        background: rgba(220, 53, 69, 0.1);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .success-message {
        color: #198754;
        background: rgba(25, 135, 84, 0.1);
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Column 1 Section */
    .row .col-8 {
        background: url('Images/Login_Register-image.jpg') no-repeat center center/cover;
        object-fit: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        border-right: 1px solid #000000;
        opacity: 0.8;
    }

    /* Column 2 Section */
    .row .col-4 {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login_register {
        box-shadow: inset 0px 4px 4px 0px rgba(0, 0, 0, 0.25);
    }

    .form-box {
        background: white;
        padding: 2.5rem;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        width: 100%;
        max-width: 420px;
        transition: all 0.3s ease;
        display: none; /* Hidden by default */
    }

    .form-box.active {
        display: block; /* Show active form */
        animation: fadeIn 0.3s ease;
    }

    .form-box:hover {
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        transform: translateY(-3px);
    }

    @keyframes fadeInUp {
        from { opacity: 0; }
        to   { opacity: 1; }
    }

    .form-box h2 {
        text-align: center;
        margin-bottom: 2rem;
        color: #333;
        font-weight: 600;
        font-size: 1.8rem;
        position: relative;
    }

    .form-box h2:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
    }

    .floating-label-group {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .floating-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #e1e5e9;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: transparent;
        outline: none;
    }

    .floating-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .floating-input:focus + .floating-label,
    .floating-input:not(:placeholder-shown) + .floating-label {
        top: -0.5rem;
        left: 0.75rem;
        font-size: 0.75rem;
        color: #667eea;
        background: white;
        padding: 0 0.5rem;
        font-weight: 600;
    }

    .floating-label {
        position: absolute;
        top: 0.75rem;
        left: 0.75rem;
        color: #999;
        font-size: 1rem;
        transition: all 0.3s ease;
        pointer-events: none;
        background: white;
        padding: 0;
    }

    .btn-primary {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        font-size: 1rem;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-top: 1rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    }

    .form-switch-text {
        text-align: center;
        margin-top: 1.5rem;
        color: #666;
    }

    .form-switch-text a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        border-bottom: 1px solid transparent;
    }

    .form-switch-text a:hover {
        color: #764ba2;
        border-bottom-color: #764ba2;
    }

    .forgot-password-link {
        text-align: center;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    .forgot-password-link a {
        color: #dc3545;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .forgot-password-link a:hover {
        color: #b02a37;
        text-decoration: underline;
    }

    .alert {
        margin-bottom: 1.5rem;
        border-radius: 8px;
        border: none;
        font-size: 0.9rem;
    }
    .disclaimer_txt {
        text-align: center;
        padding: 15px;
        color: #666;
        font-size: 0.9rem;
    }

    .verification-timer {
        text-align: center;
        margin: 1rem 0;
        font-size: 0.9rem;
        color: #666;
    }

    .timer-text {
        font-weight: 600;
        color: #dc3545;
    }

    .resend-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .resend-link:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    @media screen and (max-width: 1400px) {
        .row .col-8 {
            width: 100%;
            height: 100%;
        }
        .row .col-4 {
            display: none;
        }
        .form-box {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .form-box:hover {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            transform: translate(-50%, -50%) translateY(-3px);
        }
        .form-switch-text {
            font-size: 0.9rem;
        }
    }
    @media screen and (max-width: 1360px) {
        .disclaimer_txt {
            font-size: 12px;
        }
    }
    @media screen and (max-width: 720px) {
        .form-box h2 {
            font-size: 1.5rem;
        }
        .disclaimer_txt {
            font-size: 10px;
        }
    }
    @media screen and (max-width: 500px) {
        .form-box h2 {
            font-size: 1.2rem;
        }
        .form-switch-text {
            font-size: 0.8rem;
        }
        .disclaimer_txt {
            font-size: 8px;
        }
    }
    @media screen and (max-width: 432px) {
        .form-box {
            max-width: 320px;
            padding: 1.5rem;
        }
        .form-box h2 {
            font-size: 1rem;
        }
        .form-switch-text {
            font-size: 0.7rem;
        }
        .form-box input[type="email"],
        .form-box input[type="password"] {
            font-size: 0.9rem;
        }
        .form-box input[type="submit"] {
            font-size: 0.9rem;
        }
        .alert {
            font-size: 10px;
        }
        .disclaimer_txt {
            font-size: 6px;
        }
    }
</style>
<body>

 <div class="container-fluid main_login_container">

    <div class="row">
        <div class="col-8">

        </div>

        <div class="col-4 login_register d-flex justify-content-center">
            <!-- Login Form -->
            <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
                <form action="login_register.php" method="post">
                    <h2>Primelot Realty</h2>
                    <?= showError($errors['login']); ?>
                    <?= showSuccess($success_messages['login']); ?>
                    
                    <div class="floating-label-group">
                        <input type="email" name="email" class="floating-input" placeholder=" " required />
                        <label class="floating-label">Email Address</label>
                    </div>
                    
                    <div class="floating-label-group">
                        <input type="password" name="password" class="floating-input" placeholder=" " required />
                        <label class="floating-label">Password</label>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary">Login</button>
                    
                    <div class="forgot-password-link">
                        <a href="#" onclick="showForm('forgot-password-form')">Forgot Password?</a>
                    </div>
                    
                    <p class="form-switch-text">
                        Don't have an account? 
                        <a href="#" onclick="showForm('register-form')">Create Account</a>
                    </p>
                </form>
            </div>

            <!-- Register Form -->
            <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
                <form action="login_register.php" method="post">
                    <h2>Create Account</h2>
                    <?= showError($errors['register']); ?>
                    <?= showSuccess($success_messages['register']); ?>
                    
                    <div class="floating-label-group">
                        <input type="text" name="name" class="floating-input" placeholder=" " required />
                        <label class="floating-label">Full Name</label>
                    </div>
                    
                    <div class="floating-label-group">
                        <input type="email" name="email" class="floating-input" placeholder=" " required />
                        <label class="floating-label">Email Address</label>
                    </div>
                    
                    <div class="floating-label-group">
                        <input type="password" name="password" class="floating-input" placeholder=" " required />
                        <label class="floating-label">Password</label>
                    </div>
                    
                    <!-- Only user role is allowed -->
                    <input type="hidden" name="role" value="user" />
                    
                    <button type="submit" name="register" class="btn btn-primary">Register</button>
                    
                    <p class="form-switch-text">
                        Already have an account? 
                        <a href="#" onclick="showForm('login-form')">Sign In</a>
                    </p>
                </form>
            </div>

            <!-- Forgot Password Form -->
            <div class="form-box <?= isActiveForm('forgot-password', $activeForm); ?>" id="forgot-password-form">
                <form action="login_register.php" method="post" id="forgot-password-email-form">
                    <h2>Reset Password</h2>
                    <?= showError($errors['forgot_password']); ?>
                    <?= showSuccess($success_messages['forgot_password']); ?>
                    
                    <p style="color: #666; margin-bottom: 1.5rem; text-align: center;">
                        Enter your registered email address and we'll send you a verification code to reset your password.
                    </p>
                    
                    <div class="floating-label-group">
                        <input type="email" name="email" class="floating-input" placeholder=" " required />
                        <label class="floating-label">Email Address</label>
                    </div>
                    
                    <button type="submit" name="send_verification" class="btn btn-primary">Send Verification Code</button>
                    
                    <p class="form-switch-text">
                        Remember your password? 
                        <a href="#" onclick="showForm('login-form')">Back to Login</a>
                    </p>
                </form>
            </div>

            <!-- Verification Code Form -->
            <div class="form-box <?= isActiveForm('verify-code', $activeForm); ?>" id="verify-code-form">
                <form action="login_register.php" method="post">
                    <h2>Enter Verification Code</h2>
                    <?= showError($errors['forgot_password']); ?>
                    
                    <p style="color: #666; margin-bottom: 1.5rem; text-align: center;">
                        We've sent a 6-digit verification code to your email address. Please enter it below.
                    </p>
                    
                    <div class="floating-label-group">
                        <input type="text" name="verification_code" class="floating-input" placeholder=" " maxlength="6" pattern="[0-9]{6}" required />
                        <label class="floating-label">Verification Code</label>
                    </div>
                    
                    <input type="hidden" name="email" id="verify-email" />
                    
                    <button type="submit" name="verify_code" class="btn btn-primary">Verify Code</button>
                    
                    <div class="verification-timer">
                        <p>Code expires in: <span class="timer-text" id="timer">10:00</span></p>
                        <p id="resend-option" style="display: none;">
                            Didn't receive the code? 
                            <a href="#" class="resend-link" onclick="resendCode()">Resend Code</a>
                        </p>
                    </div>
                    
                    <p class="form-switch-text">
                        <a href="#" onclick="showForm('login-form')">Back to Login</a>
                    </p>
                </form>
            </div>

            <!-- Reset Password Form -->
            <div class="form-box <?= isActiveForm('reset-password', $activeForm); ?>" id="reset-password-form">
                <form action="login_register.php" method="post">
                    <h2>New Password</h2>
                    <?= showError($errors['forgot_password']); ?>
                    
                    <p style="color: #666; margin-bottom: 1.5rem; text-align: center;">
                        Please enter your new password. Make sure it's secure and remember it for future logins.
                    </p>
                    
                    <div class="floating-label-group">
                        <input type="password" name="new_password" class="floating-input" placeholder=" " minlength="6" required />
                        <label class="floating-label">New Password</label>
                    </div>
                    
                    <div class="floating-label-group">
                        <input type="password" name="confirm_password" class="floating-input" placeholder=" " minlength="6" required />
                        <label class="floating-label">Confirm New Password</label>
                    </div>
                    
                    <input type="hidden" name="email" id="reset-email" />
                    
                    <button type="submit" name="reset_password" class="btn btn-primary">Update Password</button>
                    
                    <p class="form-switch-text">
                        <a href="#" onclick="showForm('login-form')">Back to Login</a>
                    </p>
                </form>
            </div>
        

        </div>
    </div>
    <div class="disclaimer_txt d-flex justify-content-center text-center">
        <p><strong>Disclaimer</strong>
        This website is created for <strong>educational purposes only</strong>. The images used here are sourced from free stock platforms such as <strong>Pexels</strong> and <strong>Pixabay</strong>, and full credit goes to their respective owners. 
        All content presented on this site is not factual and should not be considered true. This project is solely intended for learning and design practice.</p>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

   <script>
        let timerInterval;

        function showForm(formId) {
            // Hide all forms
            document.querySelectorAll('.form-box').forEach(form => {
                form.classList.remove('active');
            });
            
            // Show selected form with animation
            const targetForm = document.getElementById(formId);
            if (targetForm) {
                targetForm.classList.add('active');
            }

            // Clear any running timers
            if (timerInterval) {
                clearInterval(timerInterval);
            }
        }

        function startTimer() {
            let timeLeft = 600; // 10 minutes in seconds
            const timerElement = document.getElementById('timer');
            const resendOption = document.getElementById('resend-option');
            
            timerInterval = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                timerElement.textContent = 
                    (minutes < 10 ? '0' : '') + minutes + ':' + 
                    (seconds < 10 ? '0' : '') + seconds;
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    timerElement.textContent = 'Expired';
                    resendOption.style.display = 'block';
                }
                
                timeLeft--;
            }, 1000);
        }

        function resendCode() {
            const email = document.getElementById('verify-email').value;
            if (email) {
                // Create a hidden form to resend verification
                const form = document.createElement('form');
                form.method = 'post';
                form.action = 'login_register.php';
                form.style.display = 'none';

                const emailInput = document.createElement('input');
                emailInput.type = 'hidden';
                emailInput.name = 'email';
                emailInput.value = email;

                const resendInput = document.createElement('input');
                resendInput.type = 'hidden';
                resendInput.name = 'resend_verification';
                resendInput.value = '1';

                form.appendChild(emailInput);
                form.appendChild(resendInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize form visibility on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Check if any form has active class from PHP, if not show login by default
            const activeForms = document.querySelectorAll('.form-box.active');
            if (activeForms.length === 0) {
                document.getElementById('login-form').classList.add('active');
            }

            // Start timer if verify code form is active
            const verifyForm = document.getElementById('verify-code-form');
            if (verifyForm && verifyForm.classList.contains('active')) {
                startTimer();
            }

            // Handle form submissions for forgot password flow
            const forgotPasswordForm = document.getElementById('forgot-password-email-form');
            if (forgotPasswordForm) {
                forgotPasswordForm.addEventListener('submit', function(e) {
                    const email = this.querySelector('input[name="email"]').value;
                    // Store email for verification step
                    sessionStorage.setItem('reset_email', email);
                });
            }

            // Set stored email in verification forms
            const storedEmail = sessionStorage.getItem('reset_email');
            if (storedEmail) {
                const verifyEmailInput = document.getElementById('verify-email');
                const resetEmailInput = document.getElementById('reset-email');
                
                if (verifyEmailInput) verifyEmailInput.value = storedEmail;
                if (resetEmailInput) resetEmailInput.value = storedEmail;
            }

            // Handle password confirmation validation
            const newPasswordInput = document.querySelector('input[name="new_password"]');
            const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
            
            if (confirmPasswordInput && newPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (this.value !== newPasswordInput.value) {
                        this.setCustomValidity('Passwords do not match');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });

        // Add smooth focus transitions for better UX
        document.querySelectorAll('.floating-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Check URL parameters to show appropriate form
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action') === 'verify') {
            showForm('verify-code-form');
            startTimer();
        } else if (urlParams.get('action') === 'reset') {
            showForm('reset-password-form');
        }
    </script>
</body>
</html>