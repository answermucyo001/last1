<?php
require_once '../config.php';

if (!isAdmin()) {
    http_response_code(403);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $admin_id = $_SESSION['user_id'];
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $details = mysqli_real_escape_string($conn, $_POST['details'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO admin_activity_log (admin_id, action, details, ip_address) 
              VALUES ($admin_id, '$action', '$details', '$ip')";
    mysqli_query($conn, $query);
}
?>