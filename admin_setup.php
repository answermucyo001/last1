<?php
require_once 'config.php';

// Default admin credentials
$default_admin = [
    'username' => 'admin',
    'email' => 'admin@pharmacygold.com',
    'password' => 'Admin@123', // Change this to your desired password
    'phone' => '0700000000'
];

// Check if admin already exists
$check_query = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    // Hash the password
    $hashed_password = password_hash($default_admin['password'], PASSWORD_DEFAULT);
    
    // Insert admin
    $insert_query = "INSERT INTO users (username, email, password, phone, role) 
                     VALUES (
                         '{$default_admin['username']}', 
                         '{$default_admin['email']}', 
                         '$hashed_password', 
                         '{$default_admin['phone']}', 
                         'admin'
                     )";
    
    if (mysqli_query($conn, $insert_query)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 20px; border-radius: 5px; margin: 20px;'>";
        echo "<h2>✅ Admin Created Successfully!</h2>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>Password:</strong> Admin@123</p>";
        echo "<p><strong>Email:</strong> admin@pharmacygold.com</p>";
        echo "<p style='color: #856404;'><strong>⚠️ Please change your password after first login!</strong></p>";
        echo "<a href='login.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Go to Login</a>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border-radius: 5px; margin: 20px;'>";
        echo "<h2>❌ Error Creating Admin</h2>";
        echo "<p>" . mysqli_error($conn) . "</p>";
        echo "</div>";
    }
} else {
    // Admin exists, show credentials
    $admin_query = "SELECT username, email FROM users WHERE role = 'admin' LIMIT 1";
    $admin_result = mysqli_query($conn, $admin_query);
    $admin = mysqli_fetch_assoc($admin_result);
    
    echo "<div style='background: #fff3cd; color: #856404; padding: 20px; border-radius: 5px; margin: 20px;'>";
    echo "<h2>⚠️ Admin Already Exists</h2>";
    echo "<p><strong>Username:</strong> " . $admin['username'] . "</p>";
    echo "<p><strong>Email:</strong> " . $admin['email'] . "</p>";
    echo "<p><strong>Password:</strong> [Use your existing password]</p>";
    echo "<p>If you forgot the password, you can reset it from the database.</p>";
    echo "<a href='login.php' style='display: inline-block; background: #ffc107; color: #856404; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>Go to Login</a>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8f9fa;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
    }
    .container {
        max-width: 600px;
        margin: 0 auto;
    }
</style>