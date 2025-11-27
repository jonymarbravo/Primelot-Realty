<?php 
session_start();
require_once 'config.php';

// PHPMailer imports
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

// Function to generate random verification code
function generateVerificationCode() {
    return sprintf("%06d", mt_rand(0, 999999));
}

// Function to send verification email with PHPMailer
function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jonymarbarrete88@gmail.com';        // Your Gmail
        $mail->Password   = 'jafk jdxh kvwd hzfe';               // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('jonymarbarrete88@gmail.com', 'Primelot Realty');
        $mail->addAddress($email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Primelot Realty';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Primelot Realty</h2>
                    <p style='margin: 5px 0 0 0;'>Password Reset Request</p>
                </div>
                
                <div style='padding: 30px 20px;'>
                    <p style='color: #333; font-size: 16px;'>Hello,</p>
                    <p style='color: #333; font-size: 16px;'>You have requested to reset your password for your Primelot Realty account.</p>
                    
                    <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; text-align: center; margin: 25px 0; border-left: 4px solid #667eea;'>
                        <p style='margin: 0 0 10px 0; color: #666; font-size: 14px;'>Your Verification Code:</p>
                        <h1 style='color: #667eea; font-size: 36px; letter-spacing: 6px; margin: 0; font-weight: bold;'>$code</h1>
                    </div>
                    
                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 0; color: #856404; font-size: 14px;'>
                            <strong>⏰ Important:</strong> This code will expire in 10 minutes for security reasons.
                        </p>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'>If you didn't request this password reset, please ignore this email and your password will remain unchanged.</p>
                </div>
                
                <div style='background: #f8f9fa; padding: 15px 20px; text-align: center; border-top: 1px solid #eee;'>
                    <p style='color: #666; font-size: 12px; margin: 0;'>
                        This is an automated message from Primelot Realty. Please do not reply to this email.
                    </p>
                    <p style='color: #666; font-size: 12px; margin: 5px 0 0 0;'>
                        © 2024 Primelot Realty - Educational Purposes Only
                    </p>
                </div>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Handle user registration
if (isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['register_error'] = "All fields are required.";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Please enter a valid email address.";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }
    
    // Check password length
    if (strlen($password) < 6) {
        $_SESSION['register_error'] = "Password must be at least 6 characters long.";
        $_SESSION['active_form'] = 'register';
        header("Location: index.php");
        exit();
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'user'; // Always user

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['register_error'] = "Email is already registered.";
        $_SESSION['active_form'] = 'register';
    } else {
        // Insert new user
        $insertUser = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $insertUser->bind_param("ssss", $name, $email, $hashed_password, $role);
        
        if ($insertUser->execute()) {
            $_SESSION['register_success'] = "Registration successful! Please login.";
            $_SESSION['active_form'] = 'login';
        } else {
            $_SESSION['register_error'] = "Registration failed. Please try again.";
            $_SESSION['active_form'] = 'register';
        }
    }

    header("Location: index.php");
    exit();
}

// Handle user login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required.";
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Clear any existing session data
            session_unset();
            session_regenerate_id(true);
            
            // Set new session data
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();

            if ($user['role'] === 'admin') {
                header("Location: admin_page.php");
            } else {
                header("Location: user_page.php");
            }
            exit();
        }
    }

    $_SESSION['login_error'] = "Incorrect email or password.";
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}

// Handle send verification code
if (isset($_POST['send_verification']) || isset($_POST['resend_verification'])) {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $_SESSION['forgot_password_error'] = "Email address is required.";
        $_SESSION['active_form'] = 'forgot-password';
        header("Location: index.php");
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['forgot_password_error'] = "Please enter a valid email address.";
        $_SESSION['active_form'] = 'forgot-password';
        header("Location: index.php");
        exit();
    }
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['forgot_password_error'] = "No account found with this email address.";
        $_SESSION['active_form'] = 'forgot-password';
        header("Location: index.php");
        exit();
    }
    
    // Generate verification code
    $verification_code = generateVerificationCode();
    $expiry_time = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now
    
    // Store or update verification code in database
    // First, check if there's already a verification code for this email
    $checkStmt = $conn->prepare("SELECT email FROM password_reset_tokens WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update existing token
        $updateStmt = $conn->prepare("UPDATE password_reset_tokens SET token = ?, expires_at = ?, created_at = NOW() WHERE email = ?");
        $updateStmt->bind_param("sss", $verification_code, $expiry_time, $email);
        $updateStmt->execute();
    } else {
        // Insert new token
        $insertStmt = $conn->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
        $insertStmt->bind_param("sss", $email, $verification_code, $expiry_time);
        $insertStmt->execute();
    }
    
    // Send verification email
    if (sendVerificationEmail($email, $verification_code)) {
        $_SESSION['forgot_password_success'] = "Verification code sent to your email address. Please check your inbox.";
        $_SESSION['active_form'] = 'verify-code';
        $_SESSION['reset_email'] = $email;
        
        header("Location: index.php?action=verify");
        exit();
    } else {
        $_SESSION['forgot_password_error'] = "Failed to send verification email. Please try again.";
        $_SESSION['active_form'] = 'forgot-password';
        header("Location: index.php");
        exit();
    }
}

// Handle verification code verification
if (isset($_POST['verify_code'])) {
    $email = trim($_POST['email']);
    $entered_code = trim($_POST['verification_code']);
    
    if (empty($email) || empty($entered_code)) {
        $_SESSION['forgot_password_error'] = "Email and verification code are required.";
        $_SESSION['active_form'] = 'verify-code';
        header("Location: index.php?action=verify");
        exit();
    }
    
    if (!preg_match('/^[0-9]{6}$/', $entered_code)) {
        $_SESSION['forgot_password_error'] = "Please enter a valid 6-digit verification code.";
        $_SESSION['active_form'] = 'verify-code';
        header("Location: index.php?action=verify");
        exit();
    }
    
    // Check verification code in database
    $stmt = $conn->prepare("SELECT token, expires_at FROM password_reset_tokens WHERE email = ? AND token = ?");
    $stmt->bind_param("ss", $email, $entered_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['forgot_password_error'] = "Invalid verification code. Please check and try again.";
        $_SESSION['active_form'] = 'verify-code';
        header("Location: index.php?action=verify");
        exit();
    }
    
    $token_data = $result->fetch_assoc();
    $expiry_time = $token_data['expires_at'];
    
    // Check if token has expired
    if (strtotime($expiry_time) < time()) {
        $_SESSION['forgot_password_error'] = "Verification code has expired. Please request a new one.";
        $_SESSION['active_form'] = 'forgot-password';
        
        // Clean up expired token
        $deleteStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        
        header("Location: index.php");
        exit();
    }
    
    // Verification successful
    $_SESSION['active_form'] = 'reset-password';
    $_SESSION['verified_email'] = $email;
    header("Location: index.php?action=reset");
    exit();
}

// Handle password reset
if (isset($_POST['reset_password'])) {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['forgot_password_error'] = "All fields are required.";
        $_SESSION['active_form'] = 'reset-password';
        header("Location: index.php?action=reset");
        exit();
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['forgot_password_error'] = "Password must be at least 6 characters long.";
        $_SESSION['active_form'] = 'reset-password';
        header("Location: index.php?action=reset");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['forgot_password_error'] = "Passwords do not match.";
        $_SESSION['active_form'] = 'reset-password';
        header("Location: index.php?action=reset");
        exit();
    }
    
    // Verify that we have a valid verification session
    if (!isset($_SESSION['verified_email']) || $_SESSION['verified_email'] !== $email) {
        $_SESSION['forgot_password_error'] = "Invalid session. Please start the password reset process again.";
        $_SESSION['active_form'] = 'forgot-password';
        header("Location: index.php");
        exit();
    }
    
    // Update user password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $hashed_password, $email);
    
    if ($updateStmt->execute()) {
        // Clean up the verification token
        $deleteStmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();
        
        // Clear verification session
        unset($_SESSION['verified_email']);
        
        $_SESSION['login_success'] = "Password updated successfully! Please login with your new password.";
        $_SESSION['active_form'] = 'login';
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['forgot_password_error'] = "Failed to update password. Please try again.";
        $_SESSION['active_form'] = 'reset-password';
        header("Location: index.php?action=reset");
        exit();
    }
}

// If no valid action, redirect to home
header("Location: index.php");
exit();
?>