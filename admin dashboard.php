<?php
require_once '../config.php';

// Check if user is admin
if (!isAdmin()) {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    redirect('../login.php');
}

$admin_id = $_SESSION['user_id'];

// Get real-time statistics
$stats = [];

// Total users
$users_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins
                FROM users";
$users_result = mysqli_query($conn, $users_query);
$stats['users'] = mysqli_fetch_assoc($users_result);

// Total medicines
$medicines_query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock < 10 THEN 1 ELSE 0 END) as low_stock
                    FROM medicines";
$medicines_result = mysqli_query($conn, $medicines_query);
$stats['medicines'] = mysqli_fetch_assoc($medicines_result);

// Orders statistics
$orders_query = "SELECT 
                 COUNT(*) as total,
                 SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                 SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                 SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                 SUM(CASE WHEN DATE(order_date) = CURDATE() THEN 1 ELSE 0 END) as today,
                 SUM(total_amount) as revenue
                 FROM orders";
$orders_result = mysqli_query($conn, $orders_query);
$stats['orders'] = mysqli_fetch_assoc($orders_result);

// Revenue statistics
$revenue_query = "SELECT 
                  SUM(total_amount) as total_revenue,
                  SUM(CASE WHEN MONTH(order_date) = MONTH(CURDATE()) THEN total_amount ELSE 0 END) as monthly_revenue,
                  SUM(CASE WHEN DATE(order_date) = CURDATE() THEN total_amount ELSE 0 END) as daily_revenue
                  FROM orders 
                  WHERE status = 'completed'";
$revenue_result = mysqli_query($conn, $revenue_query);
$stats['revenue'] = mysqli_fetch_assoc($revenue_result);

// Recent activities
$activity_query = "SELECT a.*, u.username 
                   FROM admin_activity_log a
                   JOIN users u ON a.admin_id = u.id
                   ORDER BY a.created_at DESC 
                   LIMIT 10";
$activities = mysqli_query($conn, $activity_query);

// Low stock alerts
$low_stock_query = "SELECT * FROM medicines WHERE stock < 10 ORDER BY stock ASC LIMIT 5";
$low_stock = mysqli_query($conn, $low_stock_query);

// Recent orders
$recent_orders_query = "SELECT o.*, u.username, m.name as medicine_name 
                        FROM orders o
                        JOIN users u ON o.user_id = u.id
                        JOIN medicines m ON o.medicine_id = m.id
                        ORDER BY o.order_date DESC 
                        LIMIT 10";
$recent_orders = mysqli_query($conn, $recent_orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pharmacy GOLD Health</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9;
            display: flex;
        }

        /* Admin Sidebar */
        .admin-sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 100;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.3rem;
            background: linear-gradient(135deg, #FFD700, #FDB931);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .admin-profile {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .profile-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }

        .profile-info h4 {
            font-size: 1rem;
            margin-bottom: 0.2rem;
        }

        .profile-info p {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section-title {
            padding: 0 1.5rem;
            margin-bottom: 0.8rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.5rem;
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .nav-item.active {
            background: linear-gradient(90deg, #3b82f6, transparent);
            color: white;
            border-left: 4px solid #3b82f6;
        }

        .nav-item i {
            width: 20px;
            font-size: 1.1rem;
        }

        .nav-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title h1 {
            font-size: 1.8rem;
            color: #1e293b;
        }

        .page-title p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .date-time {
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.9rem;
            color: #1e293b;
        }

        .date-time i {
            margin-right: 0.5rem;
            color: #3b82f6;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .stat-details {
            flex: 1;
        }

        .stat-details h3 {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.3rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 0.3rem;
        }

        .stat-trend {
            font-size: 0.8rem;
            color: #10b981;
        }

        .stat-trend.negative {
            color: #ef4444;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-header h2 {
            font-size: 1.2rem;
            color: #1e293b;
        }

        .card-header select {
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #1e293b;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #eef2f6;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 35px;
            height: 35px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3b82f6;
        }

        .activity-details {
            flex: 1;
        }

        .activity-details p {
            color: #1e293b;
            margin-bottom: 0.2rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: #94a3b8;
        }

        /* Alerts */
        .alert-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .alert-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eef2f6;
        }

        .alert-item.warning .alert-icon {
            color: #f59e0b;
        }

        .alert-item.danger .alert-icon {
            color: #ef4444;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }

        .alert-desc {
            font-size: 0.85rem;
            color: #64748b;
        }

        .alert-action {
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.85rem;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .quick-action-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            background: #3b82f6;
            color: white;
            transform: translateY(-3px);
        }

        .quick-action-btn i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid #eef2f6;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fef3c7;
            color: #b45309;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .admin-sidebar {
                width: 80px;
                overflow: visible;
            }
            
            .sidebar-header h2,
            .sidebar-header p,
            .profile-info,
            .nav-item span:not(.nav-badge) {
                display: none;
            }
            
            .admin-main {
                margin-left: 80px;
            }
            
            .nav-item {
                justify-content: center;
                padding: 1rem;
            }
            
            .nav-item i {
                margin: 0;
            }
            
            .nav-badge {
                position: absolute;
                top: 5px;
                right: 5px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h2>Pharmacy GOLD</h2>
            <p>Admin Panel</p>
        </div>
        
        <div class="admin-profile">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h4><?php echo htmlspecialchars($_SESSION['username']); ?></h4>
                <p>Administrator</p>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="dashboard.php" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="manage_medicines.php" class="nav-item">
                    <i class="fas fa-pills"></i>
                    <span>Medicines</span>
                    <?php if ($stats['medicines']['low_stock'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['medicines']['low_stock']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_orders.php" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                    <?php if ($stats['orders']['pending'] > 0): ?>
                        <span class="nav-badge"><?php echo $stats['orders']['pending']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="manage_users.php" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Management</div>
                <a href="categories.php" class="nav-item">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="reviews.php" class="nav-item">
                    <i class="fas fa-star"></i>
                    <span>Reviews</span>
                </a>
                <a href="messages.php" class="nav-item">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
                <a href="payments.php" class="nav-item">
                    <i class="fas fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Settings</div>
                <a href="settings.php" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="backup.php" class="nav-item">
                    <i class="fas fa-database"></i>
                    <span>Backup</span>
                </a>
                <a href="logs.php" class="nav-item">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
                <a href="../logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </div>
    
    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>! Here's what's happening today.</p>
            </div>
            <div class="top-bar-actions">
                <div class="date-time">
                    <i class="far fa-calendar"></i>
                    <?php echo date('l, F j, Y'); ?>
                </div>
                <div class="date-time">
                    <i class="far fa-clock"></i>
                    <?php echo date('h:i A'); ?>
                </div>
            </div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Users</h3>
                    <div class="stat-number"><?php echo $stats['users']['total']; ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> +<?php echo $stats['users']['today']; ?> today
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="stat-details">
                    <h3>Medicines</h3>
                    <div class="stat-number"><?php echo $stats['medicines']['total']; ?></div>
                    <div class="stat-trend <?php echo $stats['medicines']['low_stock'] > 0 ? 'negative' : ''; ?>">
                        <?php echo $stats['medicines']['low_stock']; ?> low in stock
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Orders</h3>
                    <div class="stat-number"><?php echo $stats['orders']['total']; ?></div>
                    <div class="stat-trend">
                        <?php echo $stats['orders']['pending']; ?> pending
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <h3>Revenue</h3>
                    <div class="stat-number">UGX <?php echo number_format(($stats['revenue']['total_revenue'] ?? 0) * 3700, 0); ?></div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up"></i> UGX <?php echo number_format(($stats['revenue']['daily_revenue'] ?? 0) * 3700, 0); ?> today
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div>
                <!-- Recent Orders -->
                <div class="chart-card">
                    <div class="card-header">
                        <h2><i class="fas fa-clock"></i> Recent Orders</h2>
                        <a href="manage_orders.php" style="color: #3b82f6; text-decoration: none;">View All →</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Medicine</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['username']; ?></td>
                                        <td><?php echo substr($order['medicine_name'], 0, 20); ?>...</td>
                                        <td>UGX <?php echo number_format($order['total_amount'] * 3700, 0); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="chart-card" style="margin-top: 1.5rem;">
                    <div class="card-header">
                        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="manage_medicines.php?action=add" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            Add Medicine
                        </a>
                        <a href="manage_orders.php" class="quick-action-btn">
                            <i class="fas fa-truck"></i>
                            Process Orders
                        </a>
                        <a href="backup.php" class="quick-action-btn">
                            <i class="fas fa-database"></i>
                            Backup Now
                        </a>
                        <a href="settings.php" class="quick-action-btn">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Alerts -->
                <div class="alert-card">
                    <div class="card-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> Alerts</h2>
                    </div>
                    
                    <?php if (mysqli_num_rows($low_stock) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($low_stock)): ?>
                            <div class="alert-item warning">
                                <div class="alert-icon">
                                    <i class="fas fa-exclamation-circle"></i>
                                </div>
                                <div class="alert-content">
                                    <div class="alert-title">Low Stock: <?php echo $item['name']; ?></div>
                                    <div class="alert-desc">Only <?php echo $item['stock']; ?> units remaining</div>
                                </div>
                                <a href="manage_medicines.php?edit=<?php echo $item['id']; ?>" class="alert-action">Restock</a>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    
                    <?php if ($stats['orders']['pending'] > 0): ?>
                        <div class="alert-item warning">
                            <div class="alert-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="alert-content">
                                <div class="alert-title">Pending Orders</div>
                                <div class="alert-desc"><?php echo $stats['orders']['pending']; ?> orders need attention</div>
                            </div>
                            <a href="manage_orders.php?status=pending" class="alert-action">View</a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($stats['medicines']['out_of_stock'] > 0): ?>
                        <div class="alert-item danger">
                            <div class="alert-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="alert-content">
                                <div class="alert-title">Out of Stock</div>
                                <div class="alert-desc"><?php echo $stats['medicines']['out_of_stock']; ?> medicines are out of stock</div>
                            </div>
                            <a href="manage_medicines.php?filter=out_of_stock" class="alert-action">View</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Recent Activity -->
                <div class="chart-card">
                    <div class="card-header">
                        <h2><i class="fas fa-history"></i> Recent Activity</h2>
                    </div>
                    
                    <ul class="activity-list">
                        <?php while ($activity = mysqli_fetch_assoc($activities)): ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="activity-details">
                                    <p><strong><?php echo $activity['username']; ?></strong> <?php echo $activity['action']; ?></p>
                                    <div class="activity-time"><?php echo timeAgo($activity['created_at']); ?></div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Log admin activity
        function logActivity(action, details = '') {
            fetch('log_activity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${encodeURIComponent(action)}&details=${encodeURIComponent(details)}`
            });
        }
        
        // Auto-refresh dashboard every 60 seconds
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>

<?php
// Helper function for time ago
function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>