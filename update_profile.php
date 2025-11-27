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
    $_SESSION['error'] = "Please log in to update your profile.";
    header("Location: index.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $user_id = $_SESSION['user_id'];
    $new_name = trim($_POST['name']);
    
    // Validate name
    if (empty($new_name)) {
        $_SESSION['error'] = "Name cannot be empty.";
        header("Location: user_page.php");
        exit();
    }
    
    if (strlen($new_name) < 3) {
        $_SESSION['error'] = "Name must be at least 3 characters long.";
        header("Location: user_page.php");
        exit();
    }
    
    if (!preg_match("/^[a-zA-Z\s]+$/", $new_name)) {
        $_SESSION['error'] = "Name can only contain letters and spaces.";
        header("Location: user_page.php");
        exit();
    }
    
    // Update name in database
    $update_query = "UPDATE users SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_name, $user_id);
    
    if ($stmt->execute()) {
        // Update session with new name
        $_SESSION['name'] = $new_name;
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update profile. Please try again.";
        error_log("Profile update error: " . $stmt->error);
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: user_page.php");
exit();
?>