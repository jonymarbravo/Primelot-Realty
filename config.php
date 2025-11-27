<?php 

$host = "sql301.infinityfree.com";      
$user = "if0_39993233";                 
$password = "wvs0ZHKMJhdBD9";           
$database = "if0_39993233_users_db";    // this is your InfinityFree DB name

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

