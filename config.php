<?php 
// Database Configuration Template
// IMPORTANT: Fill in your actual database credentials below

$host = "your_host_here";           // e.g., "localhost" or "sql301.infinityfree.com"
$user = "your_username_here";       // Your database username
$password = "your_password_here";   // Your database password
$database = "your_database_here";   // Your database name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
