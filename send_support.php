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
    $_SESSION['error'] = "Please log in to contact support.";
    header("Location: index.php");
    exit();
}

// Function to send support email
function sendSupportEmail($user_name, $user_email, $subject, $message) {
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
        $mail->setFrom($user_email, $user_name);
        $mail->addAddress('jonymarbarrete88@gmail.com', 'Primelot Realty Support');
        $mail->addReplyTo($user_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Support Request: ' . $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Primelot Realty</h2>
                    <p style='margin: 5px 0 0 0;'>Support Request</p>
                </div>
                
                <div style='padding: 30px 20px;'>
                    <h3 style='color: #333; margin-top: 0;'>New Support Request</h3>
                    
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>
                        <p style='margin: 5px 0;'><strong>From:</strong> $user_name</p>
                        <p style='margin: 5px 0;'><strong>Email:</strong> $user_email</p>
                        <p style='margin: 5px 0;'><strong>Subject:</strong> $subject</p>
                        <p style='margin: 5px 0;'><strong>Date:</strong> " . date('F d, Y h:i A') . "</p>
                    </div>
                    
                    <div style='background: white; padding: 20px; border-left: 4px solid #667eea; margin-bottom: 20px;'>
                        <p style='margin: 0; color: #666; font-size: 14px; font-weight: bold; margin-bottom: 10px;'>Message:</p>
                        <p style='margin: 0; color: #333; font-size: 16px; line-height: 1.6;'>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                    
                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px;'>
                        <p style='margin: 0; color: #856404; font-size: 14px;'>
                            <strong>⚠ Action Required:</strong> Please respond to this support request within 24 hours.
                        </p>
                    </div>
                </div>
                
                <div style='background: #f8f9fa; padding: 15px 20px; text-align: center; border-top: 1px solid #eee;'>
                    <p style='color: #666; font-size: 12px; margin: 0;'>
                        This is an automated message from Primelot Realty Support System.
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
        error_log("Support email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Function to send confirmation email to user
function sendConfirmationEmail($user_email, $user_name, $subject) {
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
        $mail->setFrom('jonymarbarrete88@gmail.com', 'Primelot Realty Support');
        $mail->addAddress($user_email, $user_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Support Request Received - ' . $subject;
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>Primelot Realty</h2>
                    <p style='margin: 5px 0 0 0;'>Support Request Confirmation</p>
                </div>
                
                <div style='padding: 30px 20px;'>
                    <h3 style='color: #333; margin-top: 0;'>Hello, $user_name!</h3>
                    
                    <p style='color: #666; font-size: 16px; line-height: 1.6;'>
                        Thank you for contacting Primelot Realty Support. We have received your support request regarding <strong>$subject</strong>.
                    </p>
                    
                    <div style='background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0d6efd; margin: 25px 0;'>
                        <p style='margin: 0; color: #0d6efd; font-weight: bold; margin-bottom: 10px;'>
                            ✓ Your request has been submitted successfully
                        </p>
                        <p style='margin: 0; color: #666; font-size: 14px;'>
                            Our support team will review your message and respond within 24 hours during business days (Monday-Friday, 8:00 AM - 4:00 PM).
                        </p>
                    </div>
                    
                    <p style='color: #666; font-size: 14px;'>
                        <strong>What happens next?</strong>
                    </p>
                    <ul style='color: #666; font-size: 14px; line-height: 1.8;'>
                        <li>Our support team will review your request</li>
                        <li>We'll respond to your email address: <strong>$user_email</strong></li>
                        <li>You'll receive a detailed response within 24 hours</li>
                    </ul>
                    
                    <div style='background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-top: 20px;'>
                        <p style='margin: 0; color: #856404; font-size: 14px;'>
                            <strong>Note:</strong> If you don't receive a response within 24 hours, please check your spam folder or contact us directly at (123) 456-7890.
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
        error_log("Confirmation email send failed: " . $mail->ErrorInfo);
        return false;
    }
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject']) && isset($_POST['message'])) {
    $user_name = $_SESSION['name'];
    $user_email = $_SESSION['email'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($subject)) {
        $_SESSION['error'] = "Please select a subject for your support request.";
        header("Location: user_page.php");
        exit();
    }
    
    if (empty($message)) {
        $_SESSION['error'] = "Message cannot be empty.";
        header("Location: user_page.php");
        exit();
    }
    
    if (strlen($message) < 10) {
        $_SESSION['error'] = "Message must be at least 10 characters long.";
        header("Location: user_page.php");
        exit();
    }
    
    // Convert subject code to readable text
    $subject_map = [
        'booking' => 'Booking Issues',
        'technical' => 'Technical Problems',
        'account' => 'Account Issues',
        'general' => 'General Inquiry',
        'other' => 'Other'
    ];
    
    $subject_text = isset($subject_map[$subject]) ? $subject_map[$subject] : $subject;
    
    // Optional: Save support request to database for tracking
    $insert_query = "INSERT INTO support_requests (user_id, user_name, user_email, subject, message, status, created_at) 
                     VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($insert_query);
    
    if ($stmt) {
        $stmt->bind_param("issss", $_SESSION['user_id'], $user_name, $user_email, $subject_text, $message);
        $stmt->execute();
        $stmt->close();
    }
    
    // Send support email to admin
    $email_sent = sendSupportEmail($user_name, $user_email, $subject_text, $message);
    
    if ($email_sent) {
        // Send confirmation email to user
        sendConfirmationEmail($user_email, $user_name, $subject_text);
        
        $_SESSION['success'] = "Your support request has been submitted successfully! We'll respond within 24 hours.";
    } else {
        $_SESSION['error'] = "Failed to send support request. Please try again or contact us directly.";
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: user_page.php");
exit();
?>