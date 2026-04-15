<?php
require_once 'config.php';

echo "<h1>🔍 Database Check: pharmacy_gold_health</h1>";

// Check if users table exists
$tables = mysqli_query($conn, "SHOW TABLES");
echo "<h2>Existing Tables:</h2>";
echo "<ul>";
while ($table = mysqli_fetch_array($tables)) {
    echo "<li>" . $table[0] . "</li>";
}
echo "</ul>";

// Check if admin exists
$check_admin = mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'");
if (mysqli_num_rows($check_admin) > 0) {
    echo "<h2 style='color: green;'>✅ Admin User Exists</h2>";
    while ($admin = mysqli_fetch_assoc($check_admin)) {
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
    }
} else {
    echo "<h2 style='color: red;'>❌ No Admin User Found</h2>";
    
    // Create admin
    $hashed_password = password_hash('Admin@123', PASSWORD_DEFAULT);
    $insert = "INSERT INTO users (username, email, password, phone, role) 
               VALUES ('admin', 'admin@pharmacygold.com', '$hashed_password', '0700000000', 'admin')";
    
    if (mysqli_query($conn, $insert)) {
        echo "<p style='color: green;'>✅ Admin created successfully!</p>";
    } else {
        echo "<p style='color: red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// Show login info
echo "<div style='margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 10px;'>";
echo "<h3>🔐 Login Credentials:</h3>";
echo "<p><strong>Username:</strong> admin</p>";
echo "<p><strong>Password:</strong> Admin@123</p>";
echo "<p><strong>Email:</strong> admin@pharmacygold.com</p>";
echo "</div>";
?>