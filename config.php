<?php
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pharmacy_gold_health');

// Default admin credentials
define('DEFAULT_ADMIN_USERNAME', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'Admin@123');
define('DEFAULT_ADMIN_EMAIL', 'admin@pharmacygold.com');
define('DEFAULT_ADMIN_PHONE', '0700000000');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

date_default_timezone_set('Africa/Kampala');

// Function to ensure admin exists (KEEP THIS HERE ONLY)
function ensureAdminExists($conn) {
    $check_query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) == 0) {
        $hashed_password = password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT);
        
        $insert_query = "INSERT INTO users (username, email, password, phone, role) 
                         VALUES (
                            '" . DEFAULT_ADMIN_USERNAME . "', 
                            '" . DEFAULT_ADMIN_EMAIL . "', 
                            '$hashed_password', 
                            '" . DEFAULT_ADMIN_PHONE . "', 
                            'admin'
                         )";
        
        return mysqli_query($conn, $insert_query);
    }
    return false;
}

// Call it once here
$admin_created = ensureAdminExists($conn);

// Other functions...
function isLoggedIn() { return isset($_SESSION['user_id']); }
function isAdmin() { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function redirect($url) { header("Location: $url"); exit(); }
function sanitize($data) { global $conn; return mysqli_real_escape_string($conn, htmlspecialchars(trim($data))); }

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log admin activity
function logActivity($action, $details = '') {
    global $conn;
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $admin_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $action = mysqli_real_escape_string($conn, $action);
        $details = mysqli_real_escape_string($conn, $details);
        
        // Check if admin_activity_log table exists
        $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_log'");
        if (mysqli_num_rows($check_table) > 0) {
            $query = "INSERT INTO admin_activity_log (admin_id, action, details, ip_address) 
                      VALUES ($admin_id, '$action', '$details', '$ip')";
            mysqli_query($conn, $query);
        }
    }
}
?>