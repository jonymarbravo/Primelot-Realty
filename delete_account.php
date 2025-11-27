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

// Validate session
function validateUserSession() {
    if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || !isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        return false;
    }
    
    if ($_SESSION['role'] !== 'user') {
        return false;
    }
    
    // Check if session is too old (24 hours)
    if (time() - $_SESSION['login_time'] > 86400) {
        session_unset();
        session_destroy();
        return false;
    }
    
    return true;
}

// Check if user is logged in
if (!validateUserSession()) {
    $_SESSION['login_error'] = "Please log in to access this page.";
    $_SESSION['active_form'] = 'login';
    header("Location: index.php");
    exit();
}

// Function to send account deletion confirmation email
function sendDeletionConfirmationEmail($user_email, $user_name) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jonymarbarrete88@gmail.com';
        $mail->Password   = 'jafk jdxh kvwd hzfe';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('jonymarbarrete88@gmail.com', 'Primelot Realty');
        $mail->addAddress($user_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Account Deletion Confirmation - Primelot Realty';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Primelot Realty</h2>
                    <p style='margin: 5px 0 0 0;'>Account Deletion Confirmation</p>
                </div>
                
                <div style='padding: 30px 20px;'>
                    <h3 style='color: #333; margin-top: 0;'>Goodbye, $user_name</h3>
                    
                    <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                        We're sorry to see you go. Your Primelot Realty account has been successfully deleted.
                    </p>
                    
                    <div style='background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 25px 0;'>
                        <p style='margin: 0; color: #856404; font-weight: bold; margin-bottom: 10px;'>
                            ⚠ Account Deletion Summary
                        </p>
                        <ul style='margin: 10px 0 0 0; padding-left: 20px; color: #856404; font-size: 14px;'>
                            <li>Your profile information has been removed</li>
                            <li>All appointment history has been deleted</li>
                            <li>Your account access has been revoked</li>
                            <li>All personal data has been permanently erased</li>
                        </ul>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'>
                        <strong>What happens now?</strong>
                    </p>
                    <p style='color: #666; font-size: 14px; line-height: 1.6;'>
                        Your email address (<strong>$user_email</strong>) is now available for registration again if you change your mind in the future. 
                        We'd love to have you back anytime!
                    </p>
                    
                    <div style='background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0d6efd; margin-top: 20px;'>
                        <p style='margin: 0; color: #084298; font-size: 14px;'>
                            <strong>Thank you for being part of Primelot Realty!</strong><br>
                            If you deleted your account by mistake or have any questions, please contact our support team immediately at jonymarbarrete88@gmail.com or call (123) 456-7890.
                        </p>
                    </div>
                </div>
                
                <div style='background: #f8f9fa; padding: 15px 20px; text-align: center; border-top: 1px solid #eee;'>
                    <p style='color: #666; font-size: 12px; margin: 0;'>
                        This is an automated confirmation from Primelot Realty. Please do not reply to this email.
                    </p>
                    <p style='color: #666; font-size: 12px; margin: 5px 0 0 0;'>
                        © 2025 Primelot Realty - Educational Purposes Only
                    </p>
                </div>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Account deletion email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_password'])) {
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['email'];
    $user_name = $_SESSION['name'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password input
    if (empty($confirm_password)) {
        $_SESSION['error'] = "Password is required to delete your account.";
        header("Location: user_page.php");
        exit();
    }
    
    // Get user's password from database
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "User not found.";
        header("Location: user_page.php");
        exit();
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($confirm_password, $user['password'])) {
        $_SESSION['error'] = "Incorrect password. Account deletion cancelled.";
        header("Location: user_page.php");
        exit();
    }
    
    // Password is correct, proceed with account deletion
    // Start transaction for data integrity
    $conn->begin_transaction();
    
    try {
        // Delete user's appointments first (due to foreign key constraint)
        $delete_appointments = "DELETE FROM appointments WHERE user_id = ?";
        $stmt_appointments = $conn->prepare($delete_appointments);
        $stmt_appointments->bind_param("i", $user_id);
        $stmt_appointments->execute();
        
        // Delete user's support requests if table exists
        $delete_support = "DELETE FROM support_requests WHERE user_id = ?";
        $stmt_support = $conn->prepare($delete_support);
        if ($stmt_support) {
            $stmt_support->bind_param("i", $user_id);
            $stmt_support->execute();
            $stmt_support->close();
        }
        
        // Delete user account
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt_user = $conn->prepare($delete_user);
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        
        // Check if user was actually deleted
        if ($stmt_user->affected_rows === 0) {
            throw new Exception("Failed to delete user account");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Send confirmation email
        sendDeletionConfirmationEmail($user_email, $user_name);
        
        // Close statements
        $stmt_appointments->close();
        $stmt_user->close();
        $stmt->close();
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Start new session for success message
        session_start();
        $_SESSION['register_success'] = "Your account has been successfully deleted. We're sorry to see you go. You can create a new account anytime.";
        $_SESSION['active_form'] = 'login';
        
        header("Location: index.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        $_SESSION['error'] = "Failed to delete account. Please try again or contact support.";
        error_log("Account deletion error: " . $e->getMessage());
        
        header("Location: user_page.php");
        exit();
    }
    
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: user_page.php");
    exit();
}
?>