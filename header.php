<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config if not already included
if (!isset($conn)) {
    require_once 'config.php';
}

// Get cart count for logged in users
$cart_count = 0;
$cart_total = 0;
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $cart_query = "SELECT COALESCE(SUM(quantity), 0) as count, 
                          COALESCE(SUM(m.price * c.quantity), 0) as total 
                   FROM cart c 
                   JOIN medicines m ON c.medicine_id = m.id 
                   WHERE c.user_id = $user_id";
    $cart_result = mysqli_query($conn, $cart_query);
    if ($cart_result && mysqli_num_rows($cart_result) > 0) {
        $cart_data = mysqli_fetch_assoc($cart_result);
        $cart_count = $cart_data['count'];
        $cart_total = $cart_data['total'];
    }
}

// Get current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy GOLD Health - Your Trusted Online Pharmacy</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Header Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 80px; /* Prevent content from hiding under fixed header */
        }

        .modern-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .modern-header.scrolled {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0.8rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-icon {
            font-size: 2.5rem;
            margin-right: 0.5rem;
            animation: pulse 2s infinite;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .logo-main {
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.2;
        }

        .logo-main .gold {
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
        }

        .logo-main .health {
            color: #27ae60;
        }

        .logo-tagline {
            font-size: 0.7rem;
            color: #64748b;
            letter-spacing: 1px;
        }

        /* Desktop Navigation */
        .desktop-nav {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links {
            display: flex;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            padding: 0.5rem 0;
            position: relative;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #27ae60;
        }

        .nav-links a.active {
            color: #27ae60;
        }

        .nav-links a.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border-radius: 3px;
            animation: slideIn 0.3s ease;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        /* Search Bar */
        .search-bar {
            position: relative;
            width: 250px;
        }

        .search-bar input {
            width: 100%;
            padding: 0.6rem 1rem 0.6rem 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39,174,96,0.1);
            width: 300px;
        }

        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        /* Cart Icon */
        .cart-icon-container {
            position: relative;
            text-decoration: none;
            color: #1e293b;
            font-size: 1.3rem;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .cart-icon-container:hover {
            background: #f1f5f9;
            color: #27ae60;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            animation: pulse 2s infinite;
        }

        /* User Menu */
        .user-menu {
            position: relative;
        }

        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .user-menu-btn:hover {
            border-color: #27ae60;
            background: white;
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }

        .user-name {
            font-weight: 500;
            color: #1e293b;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .user-dropdown {
            position: absolute;
            top: 120%;
            right: 0;
            width: 250px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 1000;
        }

        .user-menu:hover .user-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 1rem;
            border-bottom: 1px solid #eef2f6;
        }

        .dropdown-header strong {
            display: block;
            color: #1e293b;
            margin-bottom: 0.2rem;
        }

        .dropdown-header small {
            color: #64748b;
            font-size: 0.8rem;
        }

        .dropdown-menu {
            list-style: none;
            padding: 0.5rem 0;
        }

        .dropdown-menu li a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1rem;
            text-decoration: none;
            color: #1e293b;
            transition: all 0.3s;
        }

        .dropdown-menu li a:hover {
            background: #f8fafc;
            color: #27ae60;
        }

        .dropdown-menu li a i {
            width: 20px;
            color: #64748b;
        }

        .dropdown-divider {
            height: 1px;
            background: #eef2f6;
            margin: 0.5rem 0;
        }

        .logout-btn {
            color: #ef4444 !important;
        }

        .logout-btn i {
            color: #ef4444 !important;
        }

        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 0.8rem;
        }

        .btn-login, .btn-register {
            padding: 0.6rem 1.2rem;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-login {
            background: transparent;
            color: #27ae60;
            border: 2px solid #27ae60;
        }

        .btn-login:hover {
            background: #27ae60;
            color: white;
            transform: translateY(-2px);
        }

        .btn-register {
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: 2px solid transparent;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(39,174,96,0.4);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            gap: 6px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 100;
        }

        .mobile-menu-btn span {
            width: 30px;
            height: 3px;
            background: #1e293b;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .mobile-menu-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }

        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            background: white;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transform: translateY(-150%);
            transition: transform 0.3s ease;
            z-index: 999;
            max-height: calc(100vh - 70px);
            overflow-y: auto;
        }

        .mobile-menu.active {
            transform: translateY(0);
        }

        .mobile-nav-links {
            list-style: none;
            margin-bottom: 2rem;
        }

        .mobile-nav-links li {
            margin-bottom: 1rem;
        }

        .mobile-nav-links a {
            display: block;
            padding: 0.8rem;
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .mobile-nav-links a:hover,
        .mobile-nav-links a.active {
            background: #f0fdf4;
            color: #27ae60;
        }

        .mobile-user-info {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 15px;
            margin-bottom: 1rem;
        }

        .mobile-user-info p {
            color: #1e293b;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .mobile-user-info small {
            color: #64748b;
        }

        .mobile-auth-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mobile-auth-buttons .btn-login,
        .mobile-auth-buttons .btn-register {
            text-align: center;
        }

        /* Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes slideIn {
            from {
                width: 0;
                opacity: 0;
            }
            to {
                width: 100%;
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .search-bar {
                width: 200px;
            }
            
            .search-bar input:focus {
                width: 250px;
            }
        }

        @media (max-width: 768px) {
            .desktop-nav {
                display: none;
            }

            .mobile-menu-btn {
                display: flex;
            }

            .mobile-menu {
                display: block;
            }

            .header-container {
                padding: 0.8rem 1rem;
            }

            .logo-main {
                font-size: 1.2rem;
            }

            .logo-tagline {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .header-actions {
                gap: 0.5rem;
            }

            .cart-icon-container {
                font-size: 1.2rem;
            }

            .user-name {
                display: none;
            }
        }

        /* Quick Search Results */
        .search-results {
            position: absolute;
            top: 120%;
            left: 0;
            right: 0;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-height: 400px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
        }

        .search-results.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1rem;
            text-decoration: none;
            color: #1e293b;
            transition: all 0.3s;
        }

        .search-result-item:hover {
            background: #f8fafc;
        }

        .search-result-item img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
        }

        .result-info h4 {
            font-size: 0.95rem;
            margin-bottom: 0.2rem;
        }

        .result-info p {
            font-size: 0.8rem;
            color: #27ae60;
            font-weight: 600;
        }

        .no-results {
            padding: 2rem;
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>
    <header class="modern-header" id="mainHeader">
        <div class="header-container">
            <!-- Logo -->
            <a href="index.php" class="logo">
                <span class="logo-icon">💊</span>
                <div class="logo-text">
                    <span class="logo-main">
                        <span class="gold">GOLD</span> <span class="health">Health</span>
                    </span>
                    <span class="logo-tagline">Your Trusted Pharmacy</span>
                </div>
            </a>

            <!-- Desktop Navigation -->
            <nav class="desktop-nav">
                <ul class="nav-links">
                    <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">About Us</a></li>
                    <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">My Orders</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="overview.php">Admin</a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Search Bar -->
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               id="quickSearch" 
                               placeholder="Search medicines..." 
                               autocomplete="off"
                               onkeyup="quickSearch(this.value)">
                        <div class="search-results" id="searchResults"></div>
                    </div>

                    <!-- Cart Icon -->
                    <a href="cart.php" class="cart-icon-container">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if ($cart_count > 0): ?>
                            <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- User Menu / Auth Buttons -->
                    <?php if (isLoggedIn()): ?>
                        <div class="user-menu">
                            <div class="user-menu-btn">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: #64748b;"></i>
                            </div>
                            
                            <div class="user-dropdown">
                                <div class="dropdown-header">
                                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                                    <small><?php echo isAdmin() ? 'Administrator' : 'Customer'; ?></small>
                                </div>
                                
                                <ul class="dropdown-menu">
                                    <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                                    <li><a href="orders.php"><i class="fas fa-box"></i> My Orders</a></li>
                                    <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Shopping Cart</a></li>
                                    <?php if (isAdmin()): ?>
                                        <li><a href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                        <li><a href="admin/manage_medicines.php"><i class="fas fa-pills"></i> Medicines</a></li>
                                        <li><a href="admin/manage_orders.php"><i class="fas fa-truck"></i> Orders</a></li>
                                    <?php endif; ?>
                                    <li class="dropdown-divider"></li>
                                    <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="auth-buttons">
                            <a href="login.php" class="btn-login">Login</a>
                            <a href="register.php" class="btn-register">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <ul class="mobile-nav-links">
                <li><a href="index.php" class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">🏠 Home</a></li>
                <li><a href="about.php" class="<?php echo $current_page == 'about.php' ? 'active' : ''; ?>">📖 About Us</a></li>
                <li><a href="contact.php" class="<?php echo $current_page == 'contact.php' ? 'active' : ''; ?>">📞 Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="orders.php" class="<?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">📦 My Orders</a></li>
                    <li><a href="cart.php">🛒 Cart <?php echo $cart_count > 0 ? "($cart_count)" : ''; ?></a></li>
                    <?php if (isAdmin()): ?>
                        <li><a href="admin/dashboard.php">⚡ Admin Panel</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <?php if (isLoggedIn()): ?>
                <div class="mobile-user-info">
                    <p>👋 Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <small><?php echo isAdmin() ? 'Administrator' : 'Customer'; ?></small>
                </div>
                
                <div class="mobile-auth-buttons">
                    <a href="profile.php" class="btn-login">👤 My Profile</a>
                    <a href="logout.php" class="btn-register" style="background: #ef4444;">🚪 Logout</a>
                </div>
            <?php else: ?>
                <div class="mobile-auth-buttons">
                    <a href="login.php" class="btn-login">🔑 Login</a>
                    <a href="register.php" class="btn-register">📝 Register</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.getElementById('mainHeader');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        function toggleMobileMenu() {
            const menuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            
            menuBtn.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            
            // Prevent body scroll when menu is open
            if (mobileMenu.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const mobileMenu = document.getElementById('mobileMenu');
            const menuBtn = document.getElementById('mobileMenuBtn');
            
            if (!mobileMenu.contains(event.target) && !menuBtn.contains(event.target)) {
                mobileMenu.classList.remove('active');
                menuBtn.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });

        // Quick search functionality
        let searchTimeout;
        function quickSearch(query) {
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                document.getElementById('searchResults').classList.remove('active');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                fetch(`search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        const resultsDiv = document.getElementById('searchResults');
                        
                        if (data.length > 0) {
                            let html = '';
                            data.forEach(item => {
                                html += `
                                    <a href="product.php?id=${item.id}" class="search-result-item">
                                        <img src="${item.image}" alt="${item.name}">
                                        <div class="result-info">
                                            <h4>${item.name}</h4>
                                            <p>UGX ${(item.price * 3700).toLocaleString()}</p>
                                        </div>
                                    </a>
                                `;
                            });
                            resultsDiv.innerHTML = html;
                        } else {
                            resultsDiv.innerHTML = '<div class="no-results">No medicines found</div>';
                        }
                        
                        resultsDiv.classList.add('active');
                    });
            }, 300);
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(event) {
            const searchBar = document.querySelector('.search-bar');
            const searchResults = document.getElementById('searchResults');
            
            if (!searchBar.contains(event.target)) {
                searchResults.classList.remove('active');
            }
        });

        // Update cart badge (called from cart.php after changes)
        function updateCartBadge(count) {
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }

        // Auto-refresh cart count
        setInterval(() => {
            if (<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
                fetch('get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        updateCartBadge(data.count);
                    });
            }
        }, 30000);

        // Add to cart function (for use on other pages)
        function addToCart(medicineId, quantity = 1) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `medicine_id=${medicineId}&quantity=${quantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showNotification('Added to cart!', 'success');
                    
                    // Update cart badge
                    if (data.cart_count) {
                        updateCartBadge(data.cart_count);
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            });
        }

        // Show notification
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <span class="notification-icon">${type === 'success' ? '✅' : '❌'}</span>
                <span class="notification-message">${message}</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add notification styles
        const style = document.createElement('style');
        style.textContent = `
            .notification {
                position: fixed;
                top: 100px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                display: flex;
                align-items: center;
                gap: 1rem;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                z-index: 9999;
                border-left: 4px solid;
            }
            
            .notification.show {
                transform: translateX(0);
            }
            
            .notification-success {
                border-left-color: #10b981;
            }
            
            .notification-error {
                border-left-color: #ef4444;
            }
            
            .notification-icon {
                font-size: 1.5rem;
            }
            
            .notification-message {
                color: #1e293b;
                font-weight: 500;
            }
        `;
        document.head.appendChild(style);
    </script>