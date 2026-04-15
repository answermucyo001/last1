<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Default admin credentials (using constants from config.php)
// No need to redeclare the function here

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = "Please enter username and password";
    } else {
        // First check if it's the default admin
        if ($username === DEFAULT_ADMIN_USERNAME && $password === DEFAULT_ADMIN_PASSWORD) {
            // Check if admin exists in database
            $query = "SELECT id, username, role FROM users WHERE username = '" . DEFAULT_ADMIN_USERNAME . "' AND role = 'admin'";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) == 1) {
                $user = mysqli_fetch_assoc($result);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Success message for admin login
                $success = "Welcome back, Admin!";
                
                if ($user['role'] == 'admin') {
                    redirect('overview.php');
                }
            } else {
                // Admin doesn't exist in database but credentials are correct
                $error = "Admin account not found in database. Please contact support.";
            }
        } else {
            // Regular user login
            $query = "SELECT id, username, password, role FROM users WHERE username = '$username' OR email = '$username'";
            $result = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($result) == 1) {
                $user = mysqli_fetch_assoc($result);
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($user['role'] == 'admin') {
                        redirect('admin/dashboard.php');
                    } else {
                        redirect('index.php');
                    }
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "User not found";
            }
        }
    }
}


// Add this near the top of login.php where you handle messages
if (isset($_GET['message'])) {
    if ($_GET['message'] == 'loggedout') {
        $success = "You have been successfully logged out.";
    } elseif ($_GET['message'] == 'timeout') {
        $success = "Your session has expired. Please login again.";
    }
}
?>

<!-- Add this where you display messages -->
<?php if (isset($success) && $success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pharmacy GOLD Health</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Additional styles for admin identity */
        .admin-identity-card {
            background: linear-gradient(135deg, #27ae60 0%, #2c3e50 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
            box-shadow: 0 10px 30px rgba(39,174,96,0.3);
        }
        
        .admin-identity-card::before {
            content: '👑';
            position: absolute;
            top: -20px;
            right: -20px;
            font-size: 100px;
            opacity: 0.2;
            transform: rotate(20deg);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .admin-badge {
            background: #f39c12;
            color: #fff;
            padding: 8px 25px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: bold;
            display: inline-block;
            margin-top: 15px;
            box-shadow: 0 5px 20px rgba(243,156,18,0.4);
            animation: glow 1.5s infinite;
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 5px 20px rgba(243,156,18,0.4); }
            50% { box-shadow: 0 5px 30px rgba(243,156,18,0.8); }
        }
        
        .credentials-box {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .credential-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
        }
        
        .credential-row:last-child {
            border-bottom: none;
        }
        
        .credential-label {
            font-weight: 600;
            opacity: 0.9;
        }
        
        .credential-value {
            font-weight: 700;
            letter-spacing: 1px;
            background: rgba(0,0,0,0.2);
            padding: 3px 10px;
            border-radius: 5px;
        }
        
        .quick-login-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
        }
        
        .quick-login-btn {
            background: rgba(255,255,255,0.25);
            border: 2px solid white;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .quick-login-btn:hover {
            background: white;
            color: #27ae60;
            transform: translateY(-5px);
        }
        
        .admin-note {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.95rem;
            border-left: 4px solid #ffc107;
            text-align: left;
        }
        
        .welcome-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .system-badge {
            display: inline-block;
            background: #17a2b8;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 10px;
        }
        
        .feature-list {
            text-align: left;
            margin-top: 15px;
            padding-left: 20px;
        }
        
        .feature-list li {
            margin: 8px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <!-- Pharmacy Logo/Title -->
            <div style="text-align: center; margin-bottom: 20px;">
                <h1 style="color: #27ae60; font-size: 2.5rem;">💊 Pharmacy</h1>
                <h2 style="color: #f39c12; margin-top: -10px;">GOLD Health</h2>
                <span class="system-badge">Management System v1.0</span>
            </div>
            
            <!-- Admin Identity Card -->
            <div class="admin-identity-card">
                <h3 style="font-size: 1.5rem; margin-bottom: 10px;">👑 System Administrator</h3>
                <p style="opacity: 0.95; margin-bottom: 15px;">Default Admin Access Credentials</p>
                
                <div class="credentials-box">
                    <div class="credential-row">
                        <span class="credential-label">Username:</span>
                        <span class="credential-value">admin</span>
                    </div>
                    <div class="credential-row">
                        <span class="credential-label">Password:</span>
                        <span class="credential-value">Admin@123</span>
                    </div>
                    <div class="credential-row">
                        <span class="credential-label">Email:</span>
                        <span class="credential-value">admin@pharmacygold.com</span>
                    </div>
                    <div class="credential-row">
                        <span class="credential-label">Phone:</span>
                        <span class="credential-value">0700 000 000</span>
                    </div>
                </div>
                
                <span class="admin-badge">🔐 Full System Access</span>
                
                <!-- Quick Login Buttons -->
                <div class="quick-login-buttons">
                    <button onclick="fillAdminCredentials()" class="quick-login-btn">
                        <span>📋</span> Fill Credentials
                    </button>
                    <button onclick="quickLoginAdmin()" class="quick-login-btn">
                        <span>⚡</span> Quick Login
                    </button>
                </div>
            </div>
            
            <!-- Success Message -->
            <?php if ($success): ?>
                <div class="welcome-message">
                    <strong>✅ <?php echo $success; ?></strong>
                </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>❌ Error:</strong> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Admin Creation Success from config.php -->
            <?php if (isset($admin_created) && $admin_created): ?>
                <div class="welcome-message">
                    <strong>✅ Default admin account created successfully!</strong><br>
                    <small>Username: <strong>admin</strong> | Password: <strong>Admin@123</strong></small>
                </div>
            <?php endif; ?>
            
            <h2>Login to Your Account</h2>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username">
                        <span style="font-size: 1.2rem;">👤</span> Username or Email
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="Enter username or email"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <span style="font-size: 1.2rem;">🔒</span> Password
                    </label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter password">
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <p class="auth-link">
                <a href="forgot_password.php">Forgot Password?</a>
            </p>
            
            <p class="auth-link">Don't have an account? <a href="register.php">Register here</a></p>
            
            <!-- Important Note -->
            <div class="admin-note">
                <strong>⚠️ Important Security Notes:</strong>
                <ul class="feature-list">
                    <li>🔑 Default admin credentials are shown above</li>
                    <li>🔄 Change your password immediately after first login</li>
                    <li>🔒 Never share your admin credentials</li>
                    <li>💪 Use strong passwords (mix of letters, numbers, symbols)</li>
                    <li>📱 Enable two-factor authentication if available</li>
                </ul>
            </div>
            
            <!-- System Features -->
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <h4 style="color: #333; margin-bottom: 10px;">✨ System Features:</h4>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; font-size: 0.9rem;">
                    <span>✅ Medicine Management</span>
                    <span>✅ Order Processing</span>
                    <span>✅ User Management</span>
                    <span>✅ Payment Integration</span>
                    <span>✅ Reports & Analytics</span>
                    <span>✅ Customer Support</span>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="margin-top: 20px; text-align: center; color: #666; font-size: 0.85rem;">
                <p>© 2024 Pharmacy GOLD Health. All rights reserved.</p>
                <p>Version 2.0 | Secure | Reliable | Fast</p>
            </div>
        </div>
    </div>

    <script>
        // Fill admin credentials in the form
        function fillAdminCredentials() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'Admin@123';
            
            // Highlight the fields
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            
            usernameField.style.borderColor = '#27ae60';
            passwordField.style.borderColor = '#27ae60';
            usernameField.style.boxShadow = '0 0 10px rgba(39,174,96,0.5)';
            passwordField.style.boxShadow = '0 0 10px rgba(39,174,96,0.5)';
            
            showNotification('✅ Admin credentials filled! Click login to continue.', 'success');
        }
        
        // Quick login as admin
        function quickLoginAdmin() {
            document.getElementById('username').value = 'admin';
            document.getElementById('password').value = 'Admin@123';
            document.getElementById('loginForm').submit();
        }
        
        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.style.position = 'fixed';
            notification.style.top = '20px';
            notification.style.right = '20px';
            notification.style.zIndex = '9999';
            notification.style.animation = 'slideInRight 0.3s ease';
            notification.style.maxWidth = '300px';
            notification.style.boxShadow = '0 5px 20px rgba(0,0,0,0.2)';
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
        
        // Add animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .alert-success {
                background: #d4edda;
                color: #155724;
                border-left: 4px solid #28a745;
            }
        `;
        document.head.appendChild(style);
        
        // Add input focus effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
                this.parentElement.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Add password show/hide toggle
        const passwordField = document.getElementById('password');
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.innerHTML = '👁️';
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '10px';
        toggleBtn.style.top = '35px';
        toggleBtn.style.background = 'none';
        toggleBtn.style.border = 'none';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.fontSize = '1.2rem';
        
        passwordField.parentElement.style.position = 'relative';
        passwordField.parentElement.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.innerHTML = '👁️‍🗨️';
            } else {
                passwordField.type = 'password';
                toggleBtn.innerHTML = '👁️';
            }
        });
    </script>
</body>
</html>