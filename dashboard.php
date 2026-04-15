<?php
// ===== CONFIGURATION =====
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'pharmacy_gold';
$username = 'root';
$password = '';

try {
    $conn = mysqli_connect($host, $username, $password, $dbname);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Admin check function
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Check if user is admin
if (!isAdmin()) {
    redirect('login.php');
}

// Get statistics
$users_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='user'"))['count'];
$medicines_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM medicines"))['count'];
$orders_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status='pending'"))['count'];

// Fetch recent orders
$recent_orders = mysqli_query($conn, "SELECT o.*, u.username, u.email, m.name as medicine_name, m.price 
                                      FROM orders o 
                                      JOIN users u ON o.user_id = u.id 
                                      JOIN medicines m ON o.medicine_id = m.id 
                                      ORDER BY o.order_date DESC LIMIT 10");

// Get order statistics for charts
$order_stats = mysqli_query($conn, "SELECT DATE(order_date) as date, COUNT(*) as count, SUM(total_amount) as revenue 
                                     FROM orders 
                                     WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                     GROUP BY DATE(order_date)
                                     ORDER BY date DESC");
$order_data = [];
while ($row = mysqli_fetch_assoc($order_stats)) {
    $order_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pharmacy GOLD Health</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary: #2A5C8F;
            --primary-dark: #1E4A76;
            --primary-light: #3B7CB8;
            --secondary: #4ECDC4;
            --success: #4CAF50;
            --warning: #FFC107;
            --danger: #DC3545;
            --dark: #2C3E50;
            --light: #F5F7FA;
            --white: #FFFFFF;
            --gray-100: #F8F9FA;
            --gray-200: #E9ECEF;
            --gray-300: #DEE2E6;
            --gray-400: #CED4DA;
            --gray-500: #ADB5BD;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px rgba(0,0,0,0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* ===== ADMIN CONTAINER ===== */
        .admin-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        /* ===== NAVIGATION ===== */
        .admin-nav {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
            animation: slideInLeft 0.5s ease-out;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .admin-nav h2 {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--gray-200);
            position: relative;
            animation: fadeIn 0.8s ease-out;
        }

        .admin-nav h2 i {
            margin-right: 10px;
            color: var(--secondary);
        }

        .admin-nav h2::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            animation: expandWidth 0.8s ease-out forwards;
        }

        @keyframes expandWidth {
            from { width: 0; }
            to { width: 50px; }
        }

        .admin-nav ul {
            list-style: none;
        }

        .admin-nav ul li {
            margin-bottom: 0.5rem;
            animation: slideInRight 0.5s ease-out;
            animation-fill-mode: both;
        }

        .admin-nav ul li:nth-child(1) { animation-delay: 0.1s; }
        .admin-nav ul li:nth-child(2) { animation-delay: 0.2s; }
        .admin-nav ul li:nth-child(3) { animation-delay: 0.3s; }
        .admin-nav ul li:nth-child(4) { animation-delay: 0.4s; }
        .admin-nav ul li:nth-child(5) { animation-delay: 0.5s; }
        .admin-nav ul li:nth-child(6) { animation-delay: 0.6s; }
        .admin-nav ul li:nth-child(7) { animation-delay: 0.7s; }
        .admin-nav ul li:nth-child(8) { animation-delay: 0.8s; }

        @keyframes slideInRight {
            from {
                transform: translateX(50px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .admin-nav ul li a {
            display: block;
            padding: 0.8rem 1rem;
            color: var(--dark);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .admin-nav ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .admin-nav ul li a.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
        }

        .admin-nav ul li a::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .admin-nav ul li a:hover::before {
            width: 300px;
            height: 300px;
        }

        .admin-nav ul li a:hover {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(42, 92, 143, 0.3);
        }

        .badge {
            background: var(--danger);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: 5px;
        }

        /* ===== ADMIN CONTENT ===== */
        .admin-content {
            flex: 1;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            overflow-y: auto;
            animation: fadeIn 0.8s ease-out;
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

        .admin-content h1 {
            color: var(--dark);
            margin-bottom: 2rem;
            font-size: 2.5rem;
            position: relative;
            padding-bottom: 1rem;
            animation: slideInDown 0.6s ease-out;
        }

        .admin-content h1 i {
            margin-right: 10px;
            color: var(--primary);
        }

        @keyframes slideInDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .admin-content h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
            animation: expandWidth 1s ease-out forwards;
        }

        /* ===== SEARCH CONTAINER ===== */
        .search-container {
            position: relative;
            margin: 20px 0;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid var(--gray-200);
            border-radius: 25px;
            font-size: 14px;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 92, 143, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
        }

        .highlight {
            background-color: rgba(255, 255, 0, 0.3);
            padding: 2px;
            border-radius: 3px;
            font-weight: bold;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            animation: scaleIn 0.5s ease-out;
            animation-fill-mode: both;
            cursor: pointer;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.5s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .stat-card h3 {
            color: var(--gray-500);
            font-size: 1rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-card p {
            color: var(--dark);
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .stat-icon {
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 3rem;
            opacity: 0.1;
            transition: var(--transition);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.2);
            opacity: 0.2;
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .quick-action-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            padding: 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .quick-action-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(42, 92, 143, 0.3);
        }

        .quick-action-btn i {
            margin-right: 8px;
        }

        /* ===== RECENT ORDERS ===== */
        .recent-orders {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-md);
            animation: slideInUp 0.6s ease-out 0.5s both;
        }

        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .recent-orders h2 {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-orders h2 i {
            color: var(--primary);
        }

        .table-responsive {
            overflow-x: auto;
        }

        /* ===== ADMIN TABLE ===== */
        .admin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .admin-table thead tr {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--white);
        }

        .admin-table thead th {
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .admin-table thead th:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .admin-table thead th:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .admin-table tbody tr {
            background: var(--white);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            animation: fadeInRow 0.5s ease-out;
            animation-fill-mode: both;
            cursor: pointer;
        }

        @keyframes fadeInRow {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .admin-table tbody tr:nth-child(1) { animation-delay: 0.1s; }
        .admin-table tbody tr:nth-child(2) { animation-delay: 0.15s; }
        .admin-table tbody tr:nth-child(3) { animation-delay: 0.2s; }
        .admin-table tbody tr:nth-child(4) { animation-delay: 0.25s; }
        .admin-table tbody tr:nth-child(5) { animation-delay: 0.3s; }
        .admin-table tbody tr:nth-child(6) { animation-delay: 0.35s; }
        .admin-table tbody tr:nth-child(7) { animation-delay: 0.4s; }
        .admin-table tbody tr:nth-child(8) { animation-delay: 0.45s; }
        .admin-table tbody tr:nth-child(9) { animation-delay: 0.5s; }
        .admin-table tbody tr:nth-child(10) { animation-delay: 0.55s; }

        .admin-table tbody tr:hover {
            transform: translateX(5px) scale(1.02);
            box-shadow: var(--shadow-lg);
            background: linear-gradient(90deg, var(--white), var(--gray-100));
        }

        .admin-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .admin-table tbody td small {
            display: block;
            color: var(--gray-500);
            font-size: 0.75rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: var(--transition);
        }

        .action-btn:hover {
            background: var(--gray-200);
            transform: scale(1.1);
        }

        /* ===== STATUS BADGES ===== */
        .status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
            100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
        }

        .status.pending {
            background: rgba(255, 193, 7, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }

        .status.completed {
            background: rgba(76, 175, 80, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .status.cancelled {
            background: rgba(220, 53, 69, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .status.processing {
            background: rgba(42, 92, 143, 0.2);
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        /* ===== CHARTS ===== */
        .chart-container {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: var(--shadow-md);
            animation: fadeIn 1s ease-out;
        }

        .chart-title {
            color: var(--dark);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-title i {
            color: var(--primary);
        }

        /* ===== TOAST NOTIFICATIONS ===== */
        #notificationContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }

        .toast {
            background: var(--white);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            margin-bottom: 10px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateX(400px);
            animation: slideIn 0.3s forwards;
            position: relative;
            overflow: hidden;
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
            }
        }

        @keyframes slideOut {
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }

        .toast.success {
            border-left: 4px solid var(--success);
        }

        .toast.error {
            border-left: 4px solid var(--danger);
        }

        .toast.warning {
            border-left: 4px solid var(--warning);
        }

        .toast.info {
            border-left: 4px solid var(--primary);
        }

        .toast i {
            font-size: 1.2rem;
        }

        .toast.success i { color: var(--success); }
        .toast.error i { color: var(--danger); }
        .toast.warning i { color: var(--warning); }
        .toast.info i { color: var(--primary); }

        .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            width: 0%;
            animation: progress 3s linear forwards;
        }

        @keyframes progress {
            to {
                width: 100%;
            }
        }

        /* ===== LOADING SPINNER ===== */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--gray-200);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== TOOLTIP ===== */
        .tooltip {
            position: fixed;
            background: var(--dark);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 10000;
            animation: fadeIn 0.2s ease-out;
        }

        .tooltip::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px 5px 0;
            border-style: solid;
            border-color: var(--dark) transparent transparent;
        }

        /* ===== THEME TOGGLE BUTTON ===== */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            animation: bounce 2s infinite;
            transition: var(--transition);
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }

        /* ===== DARK THEME ===== */
        .dark-theme {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        }

        .dark-theme .admin-nav {
            background: rgba(26, 26, 46, 0.95);
        }

        .dark-theme .admin-content {
            background: rgba(22, 33, 62, 0.95);
        }

        .dark-theme .stat-card,
        .dark-theme .recent-orders,
        .dark-theme .chart-container {
            background: #1e1e2f;
            color: #fff;
        }

        .dark-theme .stat-card h3,
        .dark-theme .recent-orders h2,
        .dark-theme .chart-title {
            color: #fff;
        }

        .dark-theme .stat-card p {
            color: var(--secondary);
        }

        .dark-theme .admin-table tbody tr {
            background: #2d2d44;
            color: #fff;
        }

        .dark-theme .admin-table tbody td {
            color: #fff;
            border-bottom-color: #3d3d5c;
        }

        .dark-theme .admin-table tbody td small {
            color: var(--gray-400);
        }

        .dark-theme .search-input {
            background: #2d2d44;
            color: #fff;
            border-color: #3d3d5c;
        }

        .dark-theme .action-btn {
            color: var(--secondary);
        }

        .dark-theme .action-btn:hover {
            background: #3d3d5c;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1024px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-nav {
                width: 100%;
                padding: 1rem;
            }
            
            .admin-nav ul {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .admin-nav ul li {
                flex: 1 1 auto;
                margin-bottom: 0;
            }
            
            .admin-content {
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .admin-table {
                font-size: 0.9rem;
            }
            
            .admin-table thead {
                display: none;
            }
            
            .admin-table tbody tr {
                display: block;
                margin-bottom: 1rem;
            }
            
            .admin-table tbody td {
                display: block;
                text-align: right;
                padding-left: 50%;
                position: relative;
                border-bottom: 1px solid var(--gray-200);
            }
            
            .admin-table tbody td:last-child {
                border-bottom: none;
            }
            
            .admin-table tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 1rem;
                width: 45%;
                text-align: left;
                font-weight: 600;
                color: var(--gray-500);
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            .admin-nav,
            .quick-actions,
            .search-container,
            .theme-toggle,
            .action-btn,
            #notificationContainer {
                display: none;
            }
            
            .admin-content {
                padding: 0;
            }
            
            .stat-card {
                break-inside: avoid;
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .admin-table {
                border-collapse: collapse;
            }
            
            .admin-table tbody tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Navigation Sidebar -->
        <nav class="admin-nav">
            <h2><i class="fas fa-capsules"></i> Pharmacy GOLD</h2>
            <ul>
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="manage_medicines.php"><i class="fas fa-pills"></i> Medicines</a></li>
                <li><a href="manage_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="manage_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages <span class="badge">3</span></a></li>
                <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
        
        <!-- Main Content Area -->
        <div class="admin-content">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h1>
            
            <!-- Search Bar (will be populated by JavaScript) -->
            <div class="search-container">
                <input type="text" class="search-input" id="globalSearch" placeholder="Search orders, users, medicines...">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card" onclick="window.location.href='manage_users.php'">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <h3>Total Users</h3>
                    <p id="usersCount"><?php echo $users_count; ?></p>
                </div>
                <div class="stat-card" onclick="window.location.href='manage_medicines.php'">
                    <div class="stat-icon"><i class="fas fa-pills"></i></div>
                    <h3>Total Medicines</h3>
                    <p id="medicinesCount"><?php echo $medicines_count; ?></p>
                </div>
                <div class="stat-card" onclick="window.location.href='manage_orders.php'">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <h3>Total Orders</h3>
                    <p id="ordersCount"><?php echo $orders_count; ?></p>
                </div>
                <div class="stat-card" onclick="window.location.href='manage_orders.php?status=pending'">
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                    <h3>Pending Orders</h3>
                    <p id="pendingCount"><?php echo $pending_orders; ?></p>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <button class="quick-action-btn" onclick="quickAction('addMedicine')">
                    <i class="fas fa-plus-circle"></i> Add Medicine
                </button>
                <button class="quick-action-btn" onclick="quickAction('addUser')">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
                <button class="quick-action-btn" onclick="quickAction('processOrders')">
                    <i class="fas fa-truck"></i> Process Orders
                </button>
                <button class="quick-action-btn" onclick="quickAction('generateReport')">
                    <i class="fas fa-file-pdf"></i> Generate Report
                </button>
            </div>
            
            <!-- Charts Section -->
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-line"></i> Orders Overview (Last 7 Days)</h3>
                <canvas id="ordersChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-bar"></i> Revenue Trend (Last 7 Days)</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            
            <!-- Recent Orders Table -->
            <div class="recent-orders">
                <h2>
                    <i class="fas fa-history"></i> Recent Orders
                    <button class="quick-action-btn" onclick="exportData()" style="margin-left: auto; width: auto; padding: 0.5rem 1rem;">
                        <i class="fas fa-download"></i> Export
                    </button>
                </h2>
                
                <div class="table-responsive">
                    <table class="admin-table" id="ordersTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Medicine</th>
                                <th>Quantity</th>
                                <th>Total (UGX)</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $counter = 0;
                            while ($order = mysqli_fetch_assoc($recent_orders)): 
                                $counter++;
                            ?>
                                <tr data-order-id="<?php echo $order['id']; ?>">
                                    <td data-label="Order ID">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td data-label="Customer">
                                        <?php echo htmlspecialchars($order['username']); ?>
                                        <small><?php echo htmlspecialchars($order['email']); ?></small>
                                    </td>
                                    <td data-label="Medicine"><?php echo htmlspecialchars($order['medicine_name']); ?></td>
                                    <td data-label="Quantity"><?php echo $order['quantity']; ?></td>
                                    <td data-label="Total">UGX <?php echo number_format($order['total_amount'], 0); ?></td>
                                    <td data-label="Status">
                                        <span class="status <?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td data-label="Date"><?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></td>
                                    <td data-label="Actions">
                                        <button class="action-btn" onclick="viewOrder(<?php echo $order['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn" onclick="updateStatus(<?php echo $order['id']; ?>)" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($counter == 0): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 2rem;">No orders found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification Container -->
    <div id="notificationContainer"></div>
    
    <!-- Theme Toggle Button -->
    <button class="theme-toggle" onclick="toggleTheme()" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>
    
    <script>
        // ===== DASHBOARD JAVASCRIPT =====
        
        // Pass PHP data to JavaScript
        const orderData = <?php echo json_encode($order_data); ?>;
        
        // Initialize dashboard when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            initializeStatsAnimation();
            initializeSearch();
            initializeRowClick();
            startRealTimeUpdates();
        });
        
        // ===== CHARTS INITIALIZATION =====
        function initializeCharts() {
            // Orders Chart
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            new Chart(ordersCtx, {
                type: 'line',
                data: {
                    labels: getLast7Days(),
                    datasets: [{
                        label: 'Number of Orders',
                        data: getOrdersData(),
                        borderColor: '#2A5C8F',
                        backgroundColor: 'rgba(42, 92, 143, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#4ECDC4',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutQuart'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            enabled: true,
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                display: true,
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: getLast7Days(),
                    datasets: [{
                        label: 'Revenue (UGX)',
                        data: getRevenueData(),
                        backgroundColor: 'rgba(78, 205, 196, 0.8)',
                        borderRadius: 8,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutBounce'
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'UGX ' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'UGX ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function getLast7Days() {
            const days = [];
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                days.push(date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }));
            }
            return days;
        }
        
        function getOrdersData() {
            // Use PHP data if available, otherwise generate sample data
            if (orderData && orderData.length > 0) {
                return orderData.map(item => parseInt(item.count)).reverse();
            }
            return [5, 8, 12, 15, 20, 18, 25];
        }
        
        function getRevenueData() {
            // Use PHP data if available, otherwise generate sample data
            if (orderData && orderData.length > 0) {
                return orderData.map(item => parseInt(item.revenue)).reverse();
            }
            return [500000, 750000, 1000000, 1250000, 1500000, 1400000, 2000000];
        }
        
        // ===== STATISTICS ANIMATION =====
        function initializeStatsAnimation() {
            const statCards = document.querySelectorAll('.stat-card p');
            
            statCards.forEach(card => {
                const finalValue = parseInt(card.textContent.replace(/,/g, ''));
                animateValue(card, 0, finalValue, 2000);
            });
        }
        
        function animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 10);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= end) {
                    current = end;
                    clearInterval(timer);
                }
                element.textContent = formatNumber(Math.floor(current));
            }, 10);
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        // ===== SEARCH FUNCTIONALITY =====
        function initializeSearch() {
            const searchInput = document.getElementById('globalSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    performSearch(e.target.value);
                });
            }
        }
        
        function performSearch(query) {
            if (query.length < 2) {
                // Reset highlighting
                document.querySelectorAll('.highlight').forEach(el => {
                    el.outerHTML = el.innerHTML;
                });
                return;
            }
            
            const rows = document.querySelectorAll('.admin-table tbody tr');
            query = query.toLowerCase();
            
            rows.forEach(row => {
                if (row.cells.length < 2) return; // Skip empty rows
                
                let found = false;
                const cells = row.querySelectorAll('td');
                
                cells.forEach(cell => {
                    if (cell.getAttribute('data-label') === 'Actions') return;
                    
                    const text = cell.textContent.toLowerCase();
                    if (text.includes(query)) {
                        found = true;
                        highlightText(cell, query);
                    } else {
                        // Remove highlights
                        const originalText = cell.textContent;
                        cell.innerHTML = originalText;
                    }
                });
                
                row.style.display = found ? '' : 'none';
            });
        }
        
        function highlightText(element, query) {
            const text = element.textContent;
            const regex = new RegExp(`(${query})`, 'gi');
            element.innerHTML = text.replace(regex, '<span class="highlight">$1</span>');
        }
        
        // ===== ROW CLICK HANDLER =====
        function initializeRowClick() {
            const rows = document.querySelectorAll('.admin-table tbody tr');
            rows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on buttons
                    if (e.target.closest('.action-btn')) return;
                    
                    const orderId = this.getAttribute('data-order-id');
                    if (orderId) {
                        viewOrder(orderId);
                    }
                });
            });
        }
        
        // ===== QUICK ACTIONS =====
        function quickAction(action) {
            switch(action) {
                case 'addMedicine':
                    window.location.href = 'manage_medicines.php?action=add';
                    break;
                case 'addUser':
                    window.location.href = 'manage_users.php?action=add';
                    break;
                case 'processOrders':
                    window.location.href = 'manage_orders.php?status=pending';
                    break;
                case 'generateReport':
                    generateReport();
                    break;
            }
        }
        
        function generateReport() {
            showNotification('Generating report...', 'info');
            
            // Simulate report generation
            setTimeout(() => {
                showNotification('Report generated successfully!', 'success');
                
                // Create and download a simple CSV report
                exportData();
            }, 2000);
        }
        
        // ===== EXPORT DATA =====
        function exportData() {
            showNotification('Preparing export...', 'info');
            
            setTimeout(() => {
                const rows = document.querySelectorAll('.admin-table tbody tr');
                if (rows.length === 0) {
                    showNotification('No data to export', 'warning');
                    return;
                }
                
                const csv = [];
                
                // Get headers
                const headers = [];
                document.querySelectorAll('.admin-table thead th').forEach(th => {
                    headers.push(th.textContent);
                });
                csv.push(headers.join(','));
                
                // Get data
                rows.forEach(row => {
                    if (row.cells.length < 2) return;
                    
                    const rowData = [];
                    row.querySelectorAll('td').forEach((td, index) => {
                        if (index === 7) return; // Skip actions column
                        
                        let text = td.textContent.replace(/,/g, '');
                        // Clean up email formatting
                        text = text.replace(/\s+/g, ' ').trim();
                        rowData.push('"' + text + '"');
                    });
                    csv.push(rowData.join(','));
                });
                
                // Download
                const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `orders_export_${new Date().toISOString().split('T')[0]}.csv`;
                a.click();
                
                showNotification('Data exported successfully!', 'success');
            }, 1000);
        }
        
        // ===== ORDER ACTIONS =====
        function viewOrder(orderId) {
            window.location.href = `order_details.php?id=${orderId}`;
        }
        
        function updateStatus(orderId) {
            window.location.href = `update_order.php?id=${orderId}`;
        }
        
        // ===== NOTIFICATIONS =====
        function showNotification(message, type = 'info') {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `toast ${type}`;
            
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            
            notification.innerHTML = `
                <i class="fas ${icons[type]}"></i>
                <span>${message}</span>
                <div class="toast-progress"></div>
            `;
            
            container.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // ===== REAL-TIME UPDATES =====
        function startRealTimeUpdates() {
            // Check for updates every 30 seconds
            setInterval(() => {
                checkForUpdates();
            }, 30000);
        }
        
        function checkForUpdates() {
            // Simulate checking for updates
            const random = Math.random();
            if (random > 0.7) { // 30% chance of update
                showNotification('New orders received!', 'info');
                
                // Update stats (simulated)
                const ordersCount = document.getElementById('ordersCount');
                const pendingCount = document.getElementById('pendingCount');
                
                if (ordersCount && pendingCount) {
                    const currentOrders = parseInt(ordersCount.textContent.replace(/,/g, ''));
                    const currentPending = parseInt(pendingCount.textContent.replace(/,/g, ''));
                    
                    ordersCount.textContent = formatNumber(currentOrders + 1);
                    pendingCount.textContent = formatNumber(currentPending + 1);
                }
            }
        }
        
        // ===== THEME TOGGLE =====
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            const themeToggle = document.getElementById('themeToggle');
            const icon = themeToggle.querySelector('i');
            
            if (document.body.classList.contains('dark-theme')) {
                icon.className = 'fas fa-sun';
                showNotification('Dark mode enabled', 'success');
            } else {
                icon.className = 'fas fa-moon';
                showNotification('Light mode enabled', 'success');
            }
        }
        
        // ===== TOOLTIP INITIALIZATION =====
        document.querySelectorAll('[title]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = this.getAttribute('title');
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
                tooltip.style.left = (rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)) + 'px';
                
                this.addEventListener('mouseleave', () => tooltip.remove());
            });
        });
        
        // ===== KEYBOARD SHORTCUTS =====
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                document.getElementById('globalSearch').focus();
            }
            
            // Ctrl/Cmd + E for export
            if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
                e.preventDefault();
                exportData();
            }
            
            // Esc to clear search
            if (e.key === 'Escape') {
                const searchInput = document.getElementById('globalSearch');
                if (searchInput === document.activeElement) {
                    searchInput.value = '';
                    performSearch('');
                    searchInput.blur();
                }
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>