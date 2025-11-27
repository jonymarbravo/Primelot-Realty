<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jonymarbarrete88@gmail.com';      // Replace with your Gmail
    $mail->Password   = 'jafk jdxh kvwd hzfe';            // Your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('jonymarbarrete88@gmail.com', 'Primelot Realty');
    $mail->addAddress('jonymarbarrete88@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Primelot Realty';
    $mail->Body    = '<h1>Test Successful!</h1><p>Your email setup is working.</p>';

    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo "Email failed: {$mail->ErrorInfo}";
}
?>