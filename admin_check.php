<?php
require_once 'config.php';

// This page will show you the admin credentials if you forget them
// IMPORTANT: Delete this file after use for security!

if (!isAdmin()) {
    die("Access denied. Admin only.");
}

// Get admin details
$query = "SELECT id, username, email, phone, created_at FROM users WHERE role = 'admin'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Information</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fa;
            padding: 40px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }
        .info-box {
            background: #e8f5e9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        td:first-child {
            font-weight: bold;
            width: 150px;
        }
        .btn {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>👑 Admin Information</h1>
        
        <div class="warning">
            <strong>⚠️ SECURITY WARNING:</strong> Delete this file (admin_check.php) immediately after use!
        </div>
        
        <div class="info-box">
            <h3>Admin Login Credentials:</h3>
            <table>
                <?php while ($admin = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>Username:</td>
                        <td><strong><?php echo $admin['username']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Email:</td>
                        <td><?php echo $admin['email']; ?></td>
                    </tr>
                    <tr>
                        <td>Default Password:</td>
                        <td><strong>Admin@123</strong> (if you haven't changed it)</td>
                    </tr>
                    <tr>
                        <td>Phone:</td>
                        <td><?php echo $admin['phone']; ?></td>
                    </tr>
                    <tr>
                        <td>Created:</td>
                        <td><?php echo $admin['created_at']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        
        <div class="warning">
            <p><strong>Important:</strong></p>
            <ul>
                <li>Default password: <code>Admin@123</code></li>
                <li>Change your password immediately after first login</li>
                <li>Delete this file after use</li>
                <li>Keep your admin credentials secure</li>
            </ul>
        </div>
        
        <a href="admin/dashboard.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">Go to Admin Panel</a>
        <a href="logout.php" class="btn">Logout</a>
    </div>
</body>
</html>