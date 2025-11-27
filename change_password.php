<?php
session_start();
require_once 'config.php';

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
    $_SESSION['error'] = "Please log in to change your password.";
    header("Location: index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = "All password fields are required.";
        header("Location: user_page.php");
        exit();
    }
    
    if (strlen($new_password) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters long.";
        header("Location: user_page.php");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New password and confirm password do not match.";
        header("Location: user_page.php");
        exit();
    }
    
    if ($current_password === $new_password) {
        $_SESSION['error'] = "New password must be different from current password.";
        header("Location: user_page.php");
        exit();
    }
    
    // Get current password hash from database
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
    
    // Verify current password
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect.";
        header("Location: user_page.php");
        exit();
    }
    
    // Hash new password
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password in database
    $update_query = "UPDATE users SET password = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("si", $new_password_hash, $user_id);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Password changed successfully!";
    } else {
        $_SESSION['error'] = "Failed to change password. Please try again.";
        error_log("Password change error: " . $update_stmt->error);
    }
    
    $update_stmt->close();
    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: user_page.php");
exit();
?>