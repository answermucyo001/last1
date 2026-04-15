<?php
require_once 'config.php';

// Check if user is admin
if (!isAdmin()) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    redirect('../login.php');
}

$admin_id = $_SESSION['user_id'];
$active_section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';

// Handle all POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ============ MEDICINE ACTIONS ============
    if (isset($_POST['add_medicine'])) {
        $name = sanitize($_POST['name']);
        $generic_name = sanitize($_POST['generic_name'] ?? '');
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $cost_price = floatval($_POST['cost_price'] ?? 0);
        $stock = intval($_POST['stock']);
        $category = sanitize($_POST['category']);
        $manufacturer = sanitize($_POST['manufacturer'] ?? '');
        $dosage_form = sanitize($_POST['dosage_form'] ?? '');
        $strength = sanitize($_POST['strength'] ?? '');
        $image_url = sanitize($_POST['image_url']);
        $discount = intval($_POST['discount'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $prescription_required = isset($_POST['prescription_required']) ? 1 : 0;
        $expiry_date = !empty($_POST['expiry_date']) ? "'" . sanitize($_POST['expiry_date']) . "'" : "NULL";
        $batch_number = sanitize($_POST['batch_number'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        
        $query = "INSERT INTO medicines (
            name, generic_name, description, price, cost_price, stock, 
            category, manufacturer, dosage_form, strength, image_url, 
            discount, featured, prescription_required, expiry_date, batch_number, location
        ) VALUES (
            '$name', '$generic_name', '$description', $price, $cost_price, $stock,
            '$category', '$manufacturer', '$dosage_form', '$strength', '$image_url',
            $discount, $featured, $prescription_required, $expiry_date, '$batch_number', '$location'
        )";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Medicine added successfully!";
            logActivity("Added medicine: $name");
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['update_medicine'])) {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $generic_name = sanitize($_POST['generic_name'] ?? '');
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $cost_price = floatval($_POST['cost_price'] ?? 0);
        $stock = intval($_POST['stock']);
        $category = sanitize($_POST['category']);
        $manufacturer = sanitize($_POST['manufacturer'] ?? '');
        $dosage_form = sanitize($_POST['dosage_form'] ?? '');
        $strength = sanitize($_POST['strength'] ?? '');
        $image_url = sanitize($_POST['image_url']);
        $discount = intval($_POST['discount'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $prescription_required = isset($_POST['prescription_required']) ? 1 : 0;
        $expiry_date = !empty($_POST['expiry_date']) ? "'" . sanitize($_POST['expiry_date']) . "'" : "NULL";
        $batch_number = sanitize($_POST['batch_number'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        
        $query = "UPDATE medicines SET 
                  name='$name', generic_name='$generic_name', description='$description', 
                  price=$price, cost_price=$cost_price, stock=$stock,
                  category='$category', manufacturer='$manufacturer', 
                  dosage_form='$dosage_form', strength='$strength', image_url='$image_url',
                  discount=$discount, featured=$featured, prescription_required=$prescription_required,
                  expiry_date=$expiry_date, batch_number='$batch_number', location='$location'
                  WHERE id=$id";
        
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Medicine updated successfully!";
            logActivity("Updated medicine: $name");
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['delete_medicine'])) {
        $id = intval($_POST['id']);
        $name_query = "SELECT name FROM medicines WHERE id = $id";
        $name_result = mysqli_query($conn, $name_query);
        $medicine = mysqli_fetch_assoc($name_result);
        
        $query = "DELETE FROM medicines WHERE id = $id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Medicine deleted successfully!";
            logActivity("Deleted medicine: " . $medicine['name']);
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    // ============ ORDER ACTIONS ============
    elseif (isset($_POST['update_order_status'])) {
        $order_id = intval($_POST['order_id']);
        $status = sanitize($_POST['status']);
        
        $query = "UPDATE orders SET status = '$status' WHERE id = $order_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Order #$order_id status updated to $status";
            logActivity("Updated order #$order_id status to $status");
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['delete_order'])) {
        $order_id = intval($_POST['order_id']);
        
        // Get order items to restore stock
        $items_query = "SELECT medicine_id, quantity FROM orders WHERE id = $order_id";
        $items_result = mysqli_query($conn, $items_query);
        
        if ($items_result) {
            while ($item = mysqli_fetch_assoc($items_result)) {
                $restore = "UPDATE medicines SET stock = stock + {$item['quantity']} WHERE id = {$item['medicine_id']}";
                mysqli_query($conn, $restore);
            }
        }
        
        $query = "DELETE FROM orders WHERE id = $order_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Order #$order_id deleted successfully";
            logActivity("Deleted order #$order_id");
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    // ============ MESSAGE ACTIONS ============
    elseif (isset($_POST['reply_message'])) {
        $message_id = intval($_POST['message_id']);
        $reply = sanitize($_POST['reply']);
        
        // Check if columns exist
        $check_replied = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'replied_at'");
        $has_replied_at = mysqli_num_rows($check_replied) > 0;
        
        $check_status = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'status'");
        $has_status = mysqli_num_rows($check_status) > 0;
        
        $check_admin_reply = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'admin_reply'");
        $has_admin_reply = mysqli_num_rows($check_admin_reply) > 0;
        
        $updates = [];
        if ($has_admin_reply) {
            $updates[] = "admin_reply = '$reply'";
        }
        if ($has_status) {
            $updates[] = "status = 'replied'";
        }
        if ($has_replied_at) {
            $updates[] = "replied_at = NOW()";
        }
        
        if (!empty($updates)) {
            $query = "UPDATE messages SET " . implode(', ', $updates) . " WHERE id = $message_id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = "Reply sent successfully";
                logActivity("Replied to message #$message_id");
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['success'] = "Message noted (reply feature not available)";
        }
    }
    
    elseif (isset($_POST['delete_message'])) {
        $message_id = intval($_POST['message_id']);
        
        $query = "DELETE FROM messages WHERE id = $message_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "Message deleted successfully";
            logActivity("Deleted message #$message_id");
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['mark_read'])) {
        $message_id = intval($_POST['message_id']);
        
        $check_status = mysqli_query($conn, "SHOW COLUMNS FROM messages LIKE 'status'");
        if (mysqli_num_rows($check_status) > 0) {
            $query = "UPDATE messages SET status = 'read' WHERE id = $message_id";
            mysqli_query($conn, $query);
        }
        $_SESSION['success'] = "Message marked as read";
    }
    
    // ============ USER ACTIONS ============
    elseif (isset($_POST['update_user_role'])) {
        $user_id = intval($_POST['user_id']);
        $role = sanitize($_POST['role']);
        
        $query = "UPDATE users SET role = '$role' WHERE id = $user_id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = "User role updated to $role";
            logActivity("Updated user #$user_id role to $role");
        } else {
            $_SESSION['error'] = "Error: " . mysqli_error($conn);
        }
    }
    
    elseif (isset($_POST['update_user_status'])) {
        $user_id = intval($_POST['user_id']);
        $status = sanitize($_POST['status']);
        
        // Check if status column exists
        $check_status = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
        if (mysqli_num_rows($check_status) > 0) {
            $query = "UPDATE users SET status = '$status' WHERE id = $user_id";
            mysqli_query($conn, $query);
            $_SESSION['success'] = "User status updated to $status";
        } else {
            $_SESSION['success'] = "User role updated (status feature not available)";
        }
        logActivity("Updated user #$user_id");
    }
    
    elseif (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        
        if ($user_id == $admin_id) {
            $_SESSION['error'] = "You cannot delete your own account!";
        } else {
            $query = "DELETE FROM users WHERE id = $user_id";
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = "User deleted successfully";
                logActivity("Deleted user #$user_id");
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        }
    }
    
    // ============ SETTINGS ACTIONS ============
    elseif (isset($_POST['save_settings'])) {
        $site_name = sanitize($_POST['site_name']);
        $site_email = sanitize($_POST['site_email']);
        $site_phone = sanitize($_POST['site_phone']);
        $address = sanitize($_POST['address']);
        $mtn_number = sanitize($_POST['mtn_number']);
        $airtel_number = sanitize($_POST['airtel_number']);
        $delivery_fee = floatval($_POST['delivery_fee']);
        $tax_rate = floatval($_POST['tax_rate']);
        $low_stock_threshold = intval($_POST['low_stock_threshold']);
        $order_prefix = sanitize($_POST['order_prefix']);
        $currency = sanitize($_POST['currency']);
        $timezone = sanitize($_POST['timezone']);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $enable_reviews = isset($_POST['enable_reviews']) ? 1 : 0;
        
        // Create settings table if not exists
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $settings = [
            'site_name' => $site_name,
            'site_email' => $site_email,
            'site_phone' => $site_phone,
            'address' => $address,
            'mtn_number' => $mtn_number,
            'airtel_number' => $airtel_number,
            'delivery_fee' => $delivery_fee,
            'tax_rate' => $tax_rate,
            'low_stock_threshold' => $low_stock_threshold,
            'order_prefix' => $order_prefix,
            'currency' => $currency,
            'timezone' => $timezone,
            'maintenance_mode' => $maintenance_mode,
            'enable_reviews' => $enable_reviews
        ];
        
        foreach ($settings as $key => $value) {
            $check = "SELECT * FROM settings WHERE setting_key = '$key'";
            $check_result = mysqli_query($conn, $check);
            
            if (mysqli_num_rows($check_result) > 0) {
                $update = "UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'";
                mysqli_query($conn, $update);
            } else {
                $insert = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')";
                mysqli_query($conn, $insert);
            }
        }
        
        $_SESSION['success'] = "Settings saved successfully!";
        logActivity("Updated system settings");
    }
    
    // ============ CLEAR LOGS ============
    elseif (isset($_POST['clear_logs'])) {
        $days = intval($_POST['days']);
        
        $check_logs = mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_log'");
        if (mysqli_num_rows($check_logs) > 0) {
            $query = "DELETE FROM admin_activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)";
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = "Activity logs older than $days days cleared";
                logActivity("Cleared activity logs older than $days days");
            } else {
                $_SESSION['error'] = "Error: " . mysqli_error($conn);
            }
        } else {
            $_SESSION['success'] = "Logs cleared (if table exists)";
        }
    }
    
    // Redirect to refresh page
    header("Location: " . $_SERVER['PHP_SELF'] . "?section=" . $active_section);
    exit();
}

// Get statistics
$stats = [];

// Users statistics
$users_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins
                FROM users";
$users_result = mysqli_query($conn, $users_query);
$stats['users'] = $users_result ? mysqli_fetch_assoc($users_result) : ['total' => 0, 'today' => 0, 'admins' => 0];

// Medicines statistics
$medicines_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock < 10 THEN 1 ELSE 0 END) as low_stock
                    FROM medicines";
$medicines_result = mysqli_query($conn, $medicines_query);
$stats['medicines'] = $medicines_result ? mysqli_fetch_assoc($medicines_result) : ['total' => 0, 'out_of_stock' => 0, 'low_stock' => 0];

// Orders statistics
$orders_query = "SELECT 
                 COUNT(*) as total,
                 SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                 SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                 SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                 SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                 SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                 SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                 SUM(total_amount) as revenue,
                 SUM(CASE WHEN DATE(order_date) = CURDATE() THEN 1 ELSE 0 END) as today_orders,
                 SUM(CASE WHEN DATE(order_date) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue
                 FROM orders";
$orders_result = mysqli_query($conn, $orders_query);
$stats['orders'] = $orders_result ? mysqli_fetch_assoc($orders_result) : ['total' => 0, 'pending' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'completed' => 0, 'cancelled' => 0, 'revenue' => 0, 'today_orders' => 0, 'today_revenue' => 0];

// Messages statistics
$messages_query = "SELECT COUNT(*) as total FROM messages";
$messages_result = mysqli_query($conn, $messages_query);
$stats['messages'] = $messages_result ? mysqli_fetch_assoc($messages_result) : ['total' => 0];

// Get all medicines
$medicines = mysqli_query($conn, "SELECT * FROM medicines ORDER BY id DESC");

// Get all orders with user details
$orders = mysqli_query($conn, "SELECT o.*, u.username, u.email, u.phone 
                               FROM orders o 
                               JOIN users u ON o.user_id = u.id 
                               ORDER BY o.order_date DESC");

// Get all users (except current admin)
$users = mysqli_query($conn, "SELECT * FROM users WHERE id != $admin_id ORDER BY created_at DESC");

// Get all messages
$messages = mysqli_query($conn, "SELECT m.*, u.username 
                                 FROM messages m 
                                 LEFT JOIN users u ON m.user_id = u.id 
                                 ORDER BY m.created_at DESC");

// Get low stock alerts
$low_stock = mysqli_query($conn, "SELECT * FROM medicines WHERE stock < 10 ORDER BY stock ASC");

// Get activity logs if table exists
$activity = false;
$check_logs = mysqli_query($conn, "SHOW TABLES LIKE 'admin_activity_log'");
if (mysqli_num_rows($check_logs) > 0) {
    $activity = mysqli_query($conn, "SELECT a.*, u.username 
                                     FROM admin_activity_log a
                                     JOIN users u ON a.admin_id = u.id
                                     ORDER BY a.created_at DESC LIMIT 100");
}

// Get categories for filter
$categories = mysqli_query($conn, "SELECT DISTINCT category FROM medicines WHERE category IS NOT NULL AND category != ''");

// Get settings
$settings = [];
$check_settings = mysqli_query($conn, "SHOW TABLES LIKE 'settings'");
if (mysqli_num_rows($check_settings) > 0) {
    $settings_query = "SELECT * FROM settings";
    $settings_result = mysqli_query($conn, $settings_query);
    while ($row = mysqli_fetch_assoc($settings_result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// Default settings if not set
$default_settings = [
    'site_name' => 'Pharmacy GOLD Health',
    'site_email' => 'info@pharmacygold.com',
    'site_phone' => '+256 700 000000',
    'address' => 'Kampala, Uganda',
    'mtn_number' => '0700 000 000',
    'airtel_number' => '0750 000 000',
    'delivery_fee' => '5000',
    'tax_rate' => '18',
    'low_stock_threshold' => '10',
    'order_prefix' => 'ORD',
    'currency' => 'UGX',
    'timezone' => 'Africa/Kampala',
    'maintenance_mode' => '0',
    'enable_reviews' => '1'
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Pharmacy GOLD Health</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .admin-header {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-title h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .header-title p {
            color: #666;
            font-size: 0.95rem;
        }

        .header-stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .stat-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: #f8f9fa;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .stat-badge:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-badge i {
            font-size: 1.5rem;
            color: #667eea;
        }

        .stat-badge .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-badge .stat-value {
            font-weight: bold;
            font-size: 1.2rem;
            color: #333;
        }

        .stat-badge .stat-label {
            font-size: 0.8rem;
            color: #666;
        }

        /* Navigation Tabs */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            background: white;
            padding: 15px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .nav-tab {
            padding: 12px 25px;
            background: #f8f9fa;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-tab:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .nav-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-tab i {
            font-size: 1rem;
        }

        .nav-tab .badge {
            background: #ef4444;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            margin-left: 5px;
        }

        /* Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Content Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-content h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-trend {
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .stat-trend.positive {
            color: #10b981;
        }

        .stat-trend.warning {
            color: #f59e0b;
        }

        .stat-trend.danger {
            color: #ef4444;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .card-header h2 {
            color: #333;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #667eea;
        }

        .btn-primary {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .btn-secondary {
            padding: 8px 15px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            font-size: 0.85rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover td {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-shipped {
            background: #c7d2fe;
            color: #3730a3;
        }

        .status-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .role-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .role-admin {
            background: #fef3c7;
            color: #b45309;
        }

        .role-user {
            background: #e2e8f0;
            color: #475569;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-icon {
            padding: 8px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: none;
            color: #666;
        }

        .btn-icon:hover {
            background: #f0f0f0;
            color: #667eea;
            transform: translateY(-2px);
        }

        .btn-icon.delete:hover {
            color: #ef4444;
        }

        /* Message View */
        .message-thread {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .message-original {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }

        .message-reply {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 20px;
            margin-left: 30px;
            border-left: 4px solid #10b981;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            color: #666;
            font-size: 0.9rem;
        }

        .message-sender {
            font-weight: bold;
            color: #333;
        }

        .message-content {
            line-height: 1.6;
            color: #444;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            max-width: 700px;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            color: #333;
        }

        .close-modal {
            font-size: 2rem;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: #ef4444;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-stats {
                width: 100%;
            }
            
            .stat-badge {
                flex: 1;
            }
            
            .nav-tabs {
                justify-content: center;
            }
            
            .nav-tab {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-badge {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <div class="header-title">
                <h1>💊 Pharmacy GOLD Health</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! Manage your pharmacy system</p>
            </div>
            <div class="header-stats">
                <div class="stat-badge">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['orders']['pending']; ?></span>
                        <span class="stat-label">Pending Orders</span>
                    </div>
                </div>
                <div class="stat-badge">
                    <i class="fas fa-envelope"></i>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['messages']['total']; ?></span>
                        <span class="stat-label">Messages</span>
                    </div>
                </div>
                <div class="stat-badge">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="stat-info">
                        <span class="stat-value"><?php echo $stats['medicines']['low_stock']; ?></span>
                        <span class="stat-label">Low Stock</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="nav-tab <?php echo $active_section == 'dashboard' ? 'active' : ''; ?>" onclick="switchSection('dashboard')">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </button>
            <button class="nav-tab <?php echo $active_section == 'orders' ? 'active' : ''; ?>" onclick="switchSection('orders')">
                <i class="fas fa-shopping-cart"></i> Orders
                <?php if ($stats['orders']['pending'] > 0): ?>
                    <span class="badge"><?php echo $stats['orders']['pending']; ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-tab <?php echo $active_section == 'messages' ? 'active' : ''; ?>" onclick="switchSection('messages')">
                <i class="fas fa-envelope"></i> Messages
                <span class="badge"><?php echo $stats['messages']['total']; ?></span>
            </button>
            <button class="nav-tab <?php echo $active_section == 'medicines' ? 'active' : ''; ?>" onclick="switchSection('medicines')">
                <i class="fas fa-pills"></i> Medicines
                <?php if ($stats['medicines']['low_stock'] > 0): ?>
                    <span class="badge"><?php echo $stats['medicines']['low_stock']; ?></span>
                <?php endif; ?>
            </button>
            <button class="nav-tab <?php echo $active_section == 'users' ? 'active' : ''; ?>" onclick="switchSection('users')">
                <i class="fas fa-users"></i> Users
            </button>
            <button class="nav-tab <?php echo $active_section == 'settings' ? 'active' : ''; ?>" onclick="switchSection('settings')">
                <i class="fas fa-cog"></i> Settings
            </button>
            <button class="nav-tab <?php echo $active_section == 'logs' ? 'active' : ''; ?>" onclick="switchSection('logs')">
                <i class="fas fa-history"></i> Activity Logs
 <!-- In your admin navigation -->
<a href="admin logout.php" class="nav-tab" style="margin-left: auto; background: linear-gradient(135deg, #ef4444, #dc2626); color: white; text-decoration: none;">
    <i class="fas fa-sign-out-alt"></i> Logout
</a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- ============ DASHBOARD SECTION ============ -->
        <div id="dashboard" class="content-section <?php echo $active_section == 'dashboard' ? 'active' : ''; ?>">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-content">
                        <h3>Total Users</h3>
                        <div class="stat-number"><?php echo $stats['users']['total']; ?></div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> <?php echo $stats['users']['today']; ?> new today
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-pills"></i></div>
                    <div class="stat-content">
                        <h3>Medicines</h3>
                        <div class="stat-number"><?php echo $stats['medicines']['total']; ?></div>
                        <div class="stat-trend <?php echo $stats['medicines']['low_stock'] > 0 ? 'warning' : 'positive'; ?>">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $stats['medicines']['low_stock']; ?> low stock
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-content">
                        <h3>Total Orders</h3>
                        <div class="stat-number"><?php echo $stats['orders']['total']; ?></div>
                        <div class="stat-trend <?php echo $stats['orders']['pending'] > 0 ? 'warning' : 'positive'; ?>">
                            <i class="fas fa-clock"></i> <?php echo $stats['orders']['pending']; ?> pending
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="stat-content">
                        <h3>Revenue</h3>
                        <div class="stat-number">UGX <?php echo number_format(($stats['orders']['revenue'] ?? 0) * 3700, 0); ?></div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i> UGX <?php echo number_format(($stats['orders']['today_revenue'] ?? 0) * 3700, 0); ?> today
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Recent Orders</h2>
                    <button class="btn-primary" onclick="switchSection('orders')">View All</button>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($orders, 0);
                            $count = 0;
                            while ($order = mysqli_fetch_assoc($orders)): 
                                if ($count++ >= 5) break;
                            ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td>UGX <?php echo number_format($order['total_amount'] * 3700, 0); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            <?php if (mysqli_num_rows($low_stock) > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Low Stock Alerts</h2>
                    <button class="btn-primary" onclick="switchSection('medicines')">Manage Stock</button>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Current Stock</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = mysqli_fetch_assoc($low_stock)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><strong style="color: <?php echo $item['stock'] == 0 ? '#ef4444' : '#f59e0b'; ?>;"><?php echo $item['stock']; ?></strong></td>
                                    <td><?php echo $item['category']; ?></td>
                                    <td>
                                        <?php if ($item['stock'] == 0): ?>
                                            <span class="status-badge status-cancelled">Out of Stock</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Low Stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-secondary" onclick="editMedicine(<?php echo $item['id']; ?>)">Restock</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ============ ORDERS SECTION ============ -->
        <div id="orders" class="content-section <?php echo $active_section == 'orders' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-shopping-cart"></i> All Orders</h2>
                    <div>
                        <select onchange="filterOrders(this.value)" style="padding: 8px; border-radius: 8px; border: 1px solid #ddd;">
                            <option value="">All Orders</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($orders, 0); while ($order = mysqli_fetch_assoc($orders)): ?>
                                <tr data-status="<?php echo $order['status']; ?>">
                                    <td><strong>#<?php echo $order['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                                    <td>
                                        <small><?php echo $order['email']; ?><br>
                                        <?php echo $order['phone']; ?></small>
                                    </td>
                                    <td><?php echo $order['quantity']; ?> item(s)</td>
                                    <td><strong>UGX <?php echo number_format($order['total_amount'] * 3700, 0); ?></strong></td>
                                    <td><?php echo $order['payment_method'] ?: 'Pending'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon delete" onclick="deleteOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============ MESSAGES SECTION ============ -->
        <div id="messages" class="content-section <?php echo $active_section == 'messages' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-envelope"></i> Contact Messages</h2>
                </div>
                
                <?php if (mysqli_num_rows($messages) > 0): ?>
                    <?php while ($msg = mysqli_fetch_assoc($messages)): ?>
                        <div class="message-thread" id="message-<?php echo $msg['id']; ?>">
                            <div class="message-original">
                                <div class="message-header">
                                    <div>
                                        <span class="message-sender">
                                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($msg['name']); ?>
                                            <?php if ($msg['username']): ?>
                                                <small>(@<?php echo $msg['username']; ?>)</small>
                                            <?php endif; ?>
                                        </span>
                                        <span style="margin-left: 15px;">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <small><?php echo date('d M Y H:i', strtotime($msg['created_at'])); ?></small>
                                    </div>
                                </div>
                                
                                <?php if (!empty($msg['subject'])): ?>
                                    <h4 style="margin: 10px 0; color: #333;"><?php echo htmlspecialchars($msg['subject']); ?></h4>
                                <?php endif; ?>
                                
                                <div class="message-content">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                
                                <div style="margin-top: 15px; display: flex; gap: 10px;">
                                    <button class="btn-secondary" onclick="replyToMessage(<?php echo $msg['id']; ?>)">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <button class="btn-secondary" onclick="markMessageRead(<?php echo $msg['id']; ?>)">
                                        <i class="fas fa-check"></i> Mark Read
                                    </button>
                                    <button class="btn-secondary" onclick="deleteMessage(<?php echo $msg['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            
                            <?php if (!empty($msg['admin_reply'])): ?>
                                <div class="message-reply">
                                    <div class="message-header">
                                        <span class="message-sender">
                                            <i class="fas fa-user-shield"></i> Admin Reply
                                        </span>
                                        <?php if (!empty($msg['replied_at'])): ?>
                                            <small><?php echo date('d M Y H:i', strtotime($msg['replied_at'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-content">
                                        <?php echo nl2br(htmlspecialchars($msg['admin_reply'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 50px; color: #666;">
                        <i class="fas fa-inbox fa-3x" style="display: block; margin-bottom: 20px; color: #ddd;"></i>
                        No messages yet
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============ MEDICINES SECTION ============ -->
        <div id="medicines" class="content-section <?php echo $active_section == 'medicines' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-pills"></i> Medicine Inventory</h2>
                    <button class="btn-primary" onclick="openModal('addMedicineModal')">
                        <i class="fas fa-plus"></i> Add New Medicine
                    </button>
                </div>
                
                <!-- Search and Filter -->
                <div style="display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                    <input type="text" id="medicineSearch" placeholder="Search medicines..." 
                           style="flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <select id="categoryFilter" style="padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; min-width: 150px;">
                        <option value="">All Categories</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></option>
                        <?php endwhile; ?>
                    </select>
                    <select id="stockFilter" style="padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; min-width: 150px;">
                        <option value="">All Stock</option>
                        <option value="in">In Stock</option>
                        <option value="low">Low Stock (<10)</option>
                        <option value="out">Out of Stock</option>
                    </select>
                    <button class="btn-primary" onclick="filterMedicines()">Apply Filters</button>
                </div>
                
                <div class="table-responsive">
                    <table id="medicinesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price (UGX)</th>
                                <th>Stock</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php mysqli_data_seek($medicines, 0); while ($med = mysqli_fetch_assoc($medicines)): ?>
                                <tr data-category="<?php echo $med['category']; ?>" data-stock="<?php echo $med['stock']; ?>">
                                    <td>#<?php echo $med['id']; ?></td>
                                    <td>
                                        <img src="<?php echo !empty($med['image_url']) ? $med['image_url'] : 'https://via.placeholder.com/50'; ?>" 
                                             alt="<?php echo $med['name']; ?>" 
                                             style="width: 50px; height: 50px; border-radius: 8px; object-fit: cover;">
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($med['name']); ?></strong></td>
                                    <td><?php echo $med['category']; ?></td>
                                    <td>UGX <?php echo number_format($med['price'] * 3700, 0); ?></td>
                                    <td>
                                        <span style="color: <?php echo $med['stock'] < 10 ? '#ef4444' : '#10b981'; ?>; font-weight: bold;">
                                            <?php echo $med['stock']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $med['discount']; ?>%</td>
                                    <td>
                                        <?php if ($med['featured']): ?>
                                            <span class="status-badge" style="background: #fef3c7; color: #b45309;">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($med['prescription_required']): ?>
                                            <span class="status-badge" style="background: #e2e8f0; color: #475569;">Rx</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" onclick="editMedicine(<?php echo $med['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon delete" onclick="deleteMedicine(<?php echo $med['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============ USERS SECTION ============ -->
        <div id="users" class="content-section <?php echo $active_section == 'users' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <input type="text" id="userSearch" placeholder="Search users..." 
                           style="padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; width: 300px;">
                </div>
                
                <div class="table-responsive">
                    <table id="usersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['phone']; ?></td>
                                    <td>
                                        <select class="role-select" onchange="updateUserRole(<?php echo $user['id']; ?>, this.value)" 
                                                style="padding: 5px; border-radius: 5px;">
                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo ($user['status'] ?? 'active') == 'active' ? '#d1fae5' : '#fee2e2'; ?>;">
                                            <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============ SETTINGS SECTION ============ -->
        <div id="settings" class="content-section <?php echo $active_section == 'settings' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-cog"></i> System Settings</h2>
                </div>
                
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Site Name</label>
                            <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Site Email</label>
                            <input type="email" name="site_email" value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Site Phone</label>
                            <input type="text" name="site_phone" value="<?php echo htmlspecialchars($settings['site_phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" value="<?php echo htmlspecialchars($settings['address']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>MTN Money Number</label>
                            <input type="text" name="mtn_number" value="<?php echo htmlspecialchars($settings['mtn_number']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Airtel Money Number</label>
                            <input type="text" name="airtel_number" value="<?php echo htmlspecialchars($settings['airtel_number']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Delivery Fee (UGX)</label>
                            <input type="number" name="delivery_fee" value="<?php echo $settings['delivery_fee']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Tax Rate (%)</label>
                            <input type="number" name="tax_rate" value="<?php echo $settings['tax_rate']; ?>" step="0.1" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Low Stock Threshold</label>
                            <input type="number" name="low_stock_threshold" value="<?php echo $settings['low_stock_threshold']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Order Prefix</label>
                            <input type="text" name="order_prefix" value="<?php echo htmlspecialchars($settings['order_prefix']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Currency</label>
                            <select name="currency">
                                <option value="UGX" <?php echo $settings['currency'] == 'UGX' ? 'selected' : ''; ?>>UGX (Ugandan Shilling)</option>
                                <option value="USD" <?php echo $settings['currency'] == 'USD' ? 'selected' : ''; ?>>USD (US Dollar)</option>
                                <option value="KES" <?php echo $settings['currency'] == 'KES' ? 'selected' : ''; ?>>KES (Kenyan Shilling)</option>
                                <option value="TZS" <?php echo $settings['currency'] == 'TZS' ? 'selected' : ''; ?>>TZS (Tanzanian Shilling)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Timezone</label>
                            <select name="timezone">
                                <option value="Africa/Kampala" <?php echo $settings['timezone'] == 'Africa/Kampala' ? 'selected' : ''; ?>>Africa/Kampala (UTC+3)</option>
                                <option value="Africa/Nairobi" <?php echo $settings['timezone'] == 'Africa/Nairobi' ? 'selected' : ''; ?>>Africa/Nairobi (UTC+3)</option>
                                <option value="Africa/Dar_es_Salaam" <?php echo $settings['timezone'] == 'Africa/Dar_es_Salaam' ? 'selected' : ''; ?>>Africa/Dar es Salaam (UTC+3)</option>
                                <option value="Africa/Kigali" <?php echo $settings['timezone'] == 'Africa/Kigali' ? 'selected' : ''; ?>>Africa/Kigali (UTC+2)</option>
                                <option value="Africa/Juba" <?php echo $settings['timezone'] == 'Africa/Juba' ? 'selected' : ''; ?>>Africa/Juba (UTC+2)</option>
                            </select>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="maintenance_mode" value="1" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                                Maintenance Mode
                            </label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" name="enable_reviews" value="1" <?php echo $settings['enable_reviews'] ? 'checked' : ''; ?>>
                                Enable Customer Reviews
                            </label>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" name="save_settings" class="btn-primary" style="padding: 12px 40px; font-size: 1rem;">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============ ACTIVITY LOGS SECTION ============ -->
        <div id="logs" class="content-section <?php echo $active_section == 'logs' ? 'active' : ''; ?>">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Admin Activity Logs</h2>
                    <form method="POST" style="display: flex; gap: 10px;">
                        <select name="days" style="padding: 8px; border-radius: 8px;">
                            <option value="7">Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                            <option value="365">Last year</option>
                        </select>
                        <button type="submit" name="clear_logs" class="btn-secondary" onclick="return confirm('Clear old logs?')">
                            <i class="fas fa-trash"></i> Clear Old Logs
                        </button>
                    </form>
                </div>
                
                <?php if ($activity && mysqli_num_rows($activity) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($log = mysqli_fetch_assoc($activity)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><small><?php echo htmlspecialchars($log['details']); ?></small></td>
                                        <td><code><?php echo $log['ip_address']; ?></code></td>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; padding: 50px; color: #666;">
                        <i class="fas fa-history fa-3x" style="display: block; margin-bottom: 20px; color: #ddd;"></i>
                        No activity logs yet
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div id="addMedicineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Medicine</h2>
                <span class="close-modal" onclick="closeModal('addMedicineModal')">&times;</span>
            </div>
            
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Medicine Name *</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Generic Name</label>
                        <input type="text" name="generic_name">
                    </div>
                    
                    <div class="form-group">
                        <label>Category *</label>
                        <input type="text" name="category" list="categories" required>
                        <datalist id="categories">
                            <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php endwhile; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer">
                    </div>
                    
                    <div class="form-group">
                        <label>Dosage Form</label>
                        <select name="dosage_form">
                            <option value="">Select</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Cream">Cream</option>
                            <option value="Drops">Drops</option>
                            <option value="Inhaler">Inhaler</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Strength</label>
                        <input type="text" name="strength" placeholder="e.g., 500mg">
                    </div>
                    
                    <div class="form-group">
                        <label>Price (USD) *</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Cost Price (USD)</label>
                        <input type="number" name="cost_price" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Batch Number</label>
                        <input type="text" name="batch_number">
                    </div>
                    
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date">
                    </div>
                    
                    <div class="form-group">
                        <label>Storage Location</label>
                        <input type="text" name="location" placeholder="e.g., Shelf A, Row 1">
                    </div>
                    
                    <div class="form-group">
                        <label>Discount (%)</label>
                        <input type="number" name="discount" min="0" max="100" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image_url" placeholder="https://...">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="featured"> Featured Medicine
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="prescription_required"> Prescription Required
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="add_medicine" class="btn-primary" style="flex: 1; padding: 12px;">
                        <i class="fas fa-save"></i> Add Medicine
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('addMedicineModal')" style="padding: 12px 20px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Medicine Modal -->
    <div id="editMedicineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Medicine</h2>
                <span class="close-modal" onclick="closeModal('editMedicineModal')">&times;</span>
            </div>
            
            <form method="POST" id="editMedicineForm">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Medicine Name *</label>
                        <input type="text" name="name" id="edit_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Generic Name</label>
                        <input type="text" name="generic_name" id="edit_generic_name">
                    </div>
                    
                    <div class="form-group">
                        <label>Category *</label>
                        <input type="text" name="category" id="edit_category" list="categories" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer" id="edit_manufacturer">
                    </div>
                    
                    <div class="form-group">
                        <label>Dosage Form</label>
                        <select name="dosage_form" id="edit_dosage_form">
                            <option value="">Select</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Cream">Cream</option>
                            <option value="Drops">Drops</option>
                            <option value="Inhaler">Inhaler</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Strength</label>
                        <input type="text" name="strength" id="edit_strength">
                    </div>
                    
                    <div class="form-group">
                        <label>Price (USD) *</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Cost Price (USD)</label>
                        <input type="number" name="cost_price" id="edit_cost_price" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity *</label>
                        <input type="number" name="stock" id="edit_stock" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Batch Number</label>
                        <input type="text" name="batch_number" id="edit_batch_number">
                    </div>
                    
                    <div class="form-group">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" id="edit_expiry_date">
                    </div>
                    
                    <div class="form-group">
                        <label>Storage Location</label>
                        <input type="text" name="location" id="edit_location">
                    </div>
                    
                    <div class="form-group">
                        <label>Discount (%)</label>
                        <input type="number" name="discount" id="edit_discount" min="0" max="100">
                    </div>
                    
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" name="image_url" id="edit_image_url">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="featured" id="edit_featured"> Featured Medicine
                        </label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <label>
                            <input type="checkbox" name="prescription_required" id="edit_prescription_required"> Prescription Required
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="4"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_medicine" class="btn-primary" style="flex: 1; padding: 12px;">
                        <i class="fas fa-save"></i> Update Medicine
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('editMedicineModal')" style="padding: 12px 20px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reply Message Modal -->
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-reply"></i> Reply to Message</h2>
                <span class="close-modal" onclick="closeModal('replyModal')">&times;</span>
            </div>
            
            <form method="POST" id="replyForm">
                <input type="hidden" name="message_id" id="reply_message_id">
                
                <div class="form-group">
                    <label>Your Reply</label>
                    <textarea name="reply" rows="6" required placeholder="Type your reply here..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="reply_message" class="btn-primary" style="flex: 1; padding: 12px;">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('replyModal')" style="padding: 12px 20px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2><i class="fas fa-trash" style="color: #ef4444;"></i> Confirm Delete</h2>
                <span class="close-modal" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            
            <p style="margin: 30px 0; font-size: 1.1rem; text-align: center;">
                Are you sure you want to delete this item? This action cannot be undone.
            </p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="id" id="delete_id">
                <input type="hidden" name="delete_type" id="delete_type">
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" name="" id="delete_submit" class="btn-primary" style="flex: 1; background: #ef4444;">
                        <i class="fas fa-trash"></i> Yes, Delete
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('deleteModal')" style="padding: 12px 20px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Order Status Modal -->
    <div id="orderStatusModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Update Order Status</h2>
                <span class="close-modal" onclick="closeModal('orderStatusModal')">&times;</span>
            </div>
            
            <form method="POST" id="orderStatusForm">
                <input type="hidden" name="order_id" id="status_order_id">
                
                <div class="form-group">
                    <label>Select Status</label>
                    <select name="status" id="order_status" required style="padding: 12px;">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update_order_status" class="btn-primary" style="flex: 1; padding: 12px;">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                    <button type="button" class="btn-secondary" onclick="closeModal('orderStatusModal')" style="padding: 12px 20px;">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Switch sections
        function switchSection(section) {
            window.location.href = '?section=' + section;
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Medicine functions
        function editMedicine(id) {
            fetch('get_medicine.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_generic_name').value = data.generic_name || '';
                    document.getElementById('edit_description').value = data.description || '';
                    document.getElementById('edit_price').value = data.price;
                    document.getElementById('edit_cost_price').value = data.cost_price || 0;
                    document.getElementById('edit_stock').value = data.stock;
                    document.getElementById('edit_category').value = data.category || '';
                    document.getElementById('edit_manufacturer').value = data.manufacturer || '';
                    document.getElementById('edit_dosage_form').value = data.dosage_form || '';
                    document.getElementById('edit_strength').value = data.strength || '';
                    document.getElementById('edit_image_url').value = data.image_url || '';
                    document.getElementById('edit_discount').value = data.discount || 0;
                    document.getElementById('edit_batch_number').value = data.batch_number || '';
                    document.getElementById('edit_location').value = data.location || '';
                    document.getElementById('edit_expiry_date').value = data.expiry_date || '';
                    document.getElementById('edit_featured').checked = data.featured == 1;
                    document.getElementById('edit_prescription_required').checked = data.prescription_required == 1;
                    
                    openModal('editMedicineModal');
                });
        }

        function deleteMedicine(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_type').value = 'medicine';
            document.getElementById('delete_submit').name = 'delete_medicine';
            openModal('deleteModal');
        }

        // Order functions
        function updateOrderStatus(id) {
            document.getElementById('status_order_id').value = id;
            openModal('orderStatusModal');
        }

        function deleteOrder(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_type').value = 'order';
            document.getElementById('delete_submit').name = 'delete_order';
            openModal('deleteModal');
        }

        function viewOrderDetails(id) {
            // Implement view order details
            alert('View order #' + id + ' details');
        }

        // Message functions
        function replyToMessage(id) {
            document.getElementById('reply_message_id').value = id;
            openModal('replyModal');
        }

        function markMessageRead(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'mark_read';
            input.value = '1';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'message_id';
            idInput.value = id;
            
            form.appendChild(input);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }

        function deleteMessage(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_type').value = 'message';
            document.getElementById('delete_submit').name = 'delete_message';
            openModal('deleteModal');
        }

        // User functions
        function updateUserRole(userId, role) {
            if (confirm('Update user role to ' + role + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                
                const action = document.createElement('input');
                action.type = 'hidden';
                action.name = 'update_user_role';
                action.value = '1';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'user_id';
                idInput.value = userId;
                
                const roleInput = document.createElement('input');
                roleInput.type = 'hidden';
                roleInput.name = 'role';
                roleInput.value = role;
                
                form.appendChild(action);
                form.appendChild(idInput);
                form.appendChild(roleInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteUser(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_type').value = 'user';
            document.getElementById('delete_submit').name = 'delete_user';
            openModal('deleteModal');
        }

        // Filter functions
        function filterOrders(status) {
            const rows = document.querySelectorAll('#ordersTable tbody tr');
            rows.forEach(row => {
                if (!status || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterMedicines() {
            const search = document.getElementById('medicineSearch').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const stock = document.getElementById('stockFilter').value;
            const rows = document.querySelectorAll('#medicinesTable tbody tr');
            
            rows.forEach(row => {
                let show = true;
                const name = row.cells[2].textContent.toLowerCase();
                const rowCategory = row.dataset.category;
                const rowStock = parseInt(row.dataset.stock);
                
                if (search && !name.includes(search)) show = false;
                if (category && rowCategory !== category) show = false;
                if (stock === 'in' && rowStock <= 0) show = false;
                if (stock === 'low' && (rowStock >= 10 || rowStock <= 0)) show = false;
                if (stock === 'out' && rowStock > 0) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        }

        // Search users
        document.getElementById('userSearch')?.addEventListener('keyup', function() {
            const search = this.value.toLowerCase();
            const rows = document.querySelectorAll('#usersTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>