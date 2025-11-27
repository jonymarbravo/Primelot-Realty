<?php
session_start();
header('Content-Type: application/json');

function validateSession() {
    if (!isset($_SESSION['email']) || !isset($_SESSION['role']) || !isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
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

$response = array(
    'valid' => validateSession(),
    'role' => isset($_SESSION['role']) ? $_SESSION['role'] : null
);

echo json_encode($response);
?>