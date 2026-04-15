<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Update cart item
    if (isset($_POST['update_cart'])) {
        $cart_id = mysqli_real_escape_string($conn, $_POST['cart_id']);
        $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
        
        // Get cart item details
        $cart_query = "SELECT c.*, m.stock FROM cart c 
                       JOIN medicines m ON c.medicine_id = m.id 
                       WHERE c.id = $cart_id AND c.user_id = $user_id";
        $cart_result = mysqli_query($conn, $cart_query);
        
        if ($cart_item = mysqli_fetch_assoc($cart_result)) {
            if ($quantity <= $cart_item['stock']) {
                $update_query = "UPDATE cart SET quantity = $quantity WHERE id = $cart_id";
                mysqli_query($conn, $update_query);
                $message = "Cart updated successfully";
            } else {
                $error = "Only {$cart_item['stock']} items available";
            }
        }
    }
    
    // Remove from cart
    elseif (isset($_POST['remove_from_cart'])) {
        $cart_id = mysqli_real_escape_string($conn, $_POST['cart_id']);
        $delete_query = "DELETE FROM cart WHERE id = $cart_id AND user_id = $user_id";
        mysqli_query($conn, $delete_query);
        $message = "Item removed from cart";
    }
    
    // Clear cart
    elseif (isset($_POST['clear_cart'])) {
        $delete_query = "DELETE FROM cart WHERE user_id = $user_id";
        mysqli_query($conn, $delete_query);
        $message = "Cart cleared successfully";
    }
    
    // Proceed to checkout
    elseif (isset($_POST['checkout'])) {
        // Move cart items to orders
        $cart_query = "SELECT c.*, m.price, m.stock, m.name 
                       FROM cart c 
                       JOIN medicines m ON c.medicine_id = m.id 
                       WHERE c.user_id = $user_id";
        $cart_result = mysqli_query($conn, $cart_query);
        
        $success_count = 0;
        $error_items = [];
        
        while ($item = mysqli_fetch_assoc($cart_result)) {
            // Check stock
            if ($item['quantity'] <= $item['stock']) {
                $total_amount = $item['price'] * $item['quantity'];
                
                // Create order
                $order_query = "INSERT INTO orders (user_id, medicine_id, quantity, total_amount, payment_method, status) 
                               VALUES ($user_id, {$item['medicine_id']}, {$item['quantity']}, $total_amount, 'pending', 'pending')";
                
                if (mysqli_query($conn, $order_query)) {
                    // Update stock
                    $new_stock = $item['stock'] - $item['quantity'];
                    $stock_query = "UPDATE medicines SET stock = $new_stock WHERE id = {$item['medicine_id']}";
                    mysqli_query($conn, $stock_query);
                    
                    // Remove from cart
                    $delete_query = "DELETE FROM cart WHERE id = {$item['id']}";
                    mysqli_query($conn, $delete_query);
                    
                    $success_count++;
                } else {
                    $error_items[] = $item['name'];
                }
            } else {
                $error_items[] = $item['name'] . " (insufficient stock)";
            }
        }
        
        if ($success_count > 0) {
            $message = "$success_count items moved to orders successfully";
            if (!empty($error_items)) {
                $error = "Some items couldn't be processed: " . implode(', ', $error_items);
            }
        } else {
            $error = "Could not process checkout. Please check your cart.";
        }
    }
}

// Fetch cart items
$cart_query = "SELECT c.*, m.name, m.price, m.image_url, m.stock, m.category,
              (m.price * c.quantity) as subtotal
              FROM cart c 
              JOIN medicines m ON c.medicine_id = m.id 
              WHERE c.user_id = $user_id 
              ORDER BY c.added_at DESC";
$cart_result = mysqli_query($conn, $cart_query);

// Calculate totals
$total_query = "SELECT SUM(m.price * c.quantity) as total, COUNT(*) as item_count 
                FROM cart c 
                JOIN medicines m ON c.medicine_id = m.id 
                WHERE c.user_id = $user_id";
$total_result = mysqli_query($conn, $total_query);
$totals = mysqli_fetch_assoc($total_result);
$cart_total = $totals['total'] ?? 0;
$item_count = $totals['item_count'] ?? 0;
?>

<?php include 'header.php'; ?>

<div class="cart-page">
    <!-- Page Header -->
    <div class="cart-header">
        <div class="header-title">
            <h1>🛒 Shopping Cart</h1>
            <p><?php echo $item_count; ?> item(s) in your cart</p>
        </div>
        
        <?php if ($item_count > 0): ?>
            <form method="POST" onsubmit="return confirm('Clear all items from cart?')">
                <button type="submit" name="clear_cart" class="clear-cart-btn">
                    <span class="btn-icon">🗑️</span>
                    Clear Cart
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <!-- Messages -->
    <?php if ($message): ?>
        <div class="alert alert-success">
            <span class="alert-icon">✅</span>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <span class="alert-icon">❌</span>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($item_count > 0): ?>
        <!-- Cart Content -->
        <div class="cart-container">
            <!-- Cart Items -->
            <div class="cart-items">
                <?php while ($item = mysqli_fetch_assoc($cart_result)): 
                    $stock_status = $item['quantity'] <= $item['stock'] ? 'available' : 'low-stock';
                    $item_total = $item['price'] * $item['quantity'] * 3700; // Convert to UGX
                ?>
                    <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                        <div class="item-image">
                            <img src="<?php echo getOnlineMedicineImage($item['name']); ?>" 
                                 alt="<?php echo $item['name']; ?>">
                        </div>
                        
                        <div class="item-details">
                            <h3 class="item-name"><?php echo $item['name']; ?></h3>
                            <p class="item-category"><?php echo $item['category']; ?></p>
                            
                            <div class="item-price">
                                <span class="price-label">Price:</span>
                                <span class="price-value">UGX <?php echo number_format($item['price'] * 3700, 0); ?></span>
                            </div>
                            
                            <div class="item-stock stock-<?php echo $stock_status; ?>">
                                <span class="stock-indicator"></span>
                                <?php echo $item['stock']; ?> units available
                            </div>
                        </div>
                        
                        <div class="item-actions">
                            <div class="quantity-control">
                                <button type="button" class="qty-btn" onclick="updateCartItem(<?php echo $item['id']; ?>, -1)">−</button>
                                <input type="number" class="qty-input" id="qty-<?php echo $item['id']; ?>" 
                                       value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" 
                                       onchange="updateCartItemQuantity(<?php echo $item['id']; ?>, this.value)">
                                <button type="button" class="qty-btn" onclick="updateCartItem(<?php echo $item['id']; ?>, 1)">+</button>
                            </div>
                            
                            <div class="item-subtotal">
                                <span class="subtotal-label">Subtotal:</span>
                                <span class="subtotal-value">UGX <?php echo number_format($item_total, 0); ?></span>
                            </div>
                            
                            <form method="POST" class="remove-form" onsubmit="return confirm('Remove this item from cart?')">
                                <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_from_cart" class="remove-item-btn">
                                    <span class="btn-icon">🗑️</span>
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Cart Summary -->
            <div class="cart-summary-sidebar">
                <h3>Order Summary</h3>
                
                <div class="summary-items">
                    <div class="summary-row">
                        <span>Subtotal (<?php echo $item_count; ?> items)</span>
                        <span>UGX <?php echo number_format($cart_total * 3700, 0); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Delivery Fee</span>
                        <span class="free-delivery">FREE</span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span class="total-amount">UGX <?php echo number_format($cart_total * 3700, 0); ?></span>
                    </div>
                </div>
                
                <div class="payment-methods-summary">
                    <h4>We Accept:</h4>
                    <div class="payment-icons">
                        <img src="https://cdn-icons-png.flaticon.com/512/3030/3030247.png" alt="MTN">
                        <img src="https://cdn-icons-png.flaticon.com/512/3030/3030251.png" alt="Airtel">
                    </div>
                </div>
                
                <form method="POST" onsubmit="return confirm('Proceed to checkout?')">
                    <button type="submit" name="checkout" class="checkout-btn">
                        <span class="btn-icon">💰</span>
                        Proceed to Checkout
                    </button>
                </form>
                
                <a href="index.php#medicines" class="continue-shopping">
                    <span class="btn-icon">←</span>
                    Continue Shopping
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <!-- Empty Cart -->
        <div class="empty-cart">
            <div class="empty-cart-animation">
                <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" alt="Empty Cart">
            </div>
            <h2>Your cart is empty</h2>
            <p>Looks like you haven't added any items to your cart yet</p>
            <a href="index.php#medicines" class="shop-now-btn">
                <span class="btn-icon">🛒</span>
                Start Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Quick Update Form (hidden) -->
<form method="POST" id="updateCartForm" style="display: none;">
    <input type="hidden" name="cart_id" id="update_cart_id">
    <input type="hidden" name="quantity" id="update_quantity">
    <input type="hidden" name="update_cart">
</form>

<style>
/* Cart Page Styles */
.cart-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

/* Header */
.cart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.header-title h1 {
    font-size: 2.2rem;
    color: #1e293b;
    margin-bottom: 0.3rem;
}

.header-title p {
    color: #64748b;
    font-size: 1rem;
}

.clear-cart-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 40px;
    font-size: 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.clear-cart-btn:hover {
    background: #dc2626;
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(239,68,68,0.3);
}

/* Cart Container */
.cart-container {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

/* Cart Items */
.cart-items {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
}

.cart-item {
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 1.5rem;
    padding: 1.5rem;
    border-bottom: 1px solid #eef2f6;
    transition: all 0.3s;
}

.cart-item:hover {
    background: #f8fafc;
}

.cart-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-name {
    font-size: 1.2rem;
    color: #1e293b;
    margin: 0;
}

.item-category {
    color: #64748b;
    font-size: 0.9rem;
}

.item-price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.price-label {
    color: #64748b;
    font-size: 0.9rem;
}

.price-value {
    font-weight: 600;
    color: #2563eb;
    font-size: 1.1rem;
}

.item-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.stock-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.stock-available .stock-indicator {
    background: #10b981;
    animation: pulse 2s infinite;
}

.stock-low-stock .stock-indicator {
    background: #f59e0b;
    animation: blink 1s infinite;
}

.item-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 1rem;
    min-width: 200px;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #f1f5f9;
    padding: 0.3rem;
    border-radius: 40px;
}

.qty-btn {
    width: 35px;
    height: 35px;
    border: none;
    background: white;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1.2rem;
    font-weight: bold;
    color: #1e293b;
    transition: all 0.3s;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.qty-btn:hover {
    background: #2563eb;
    color: white;
    transform: scale(1.1);
}

.qty-input {
    width: 50px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 600;
    font-size: 1rem;
}

.qty-input:focus {
    outline: none;
}

.item-subtotal {
    text-align: right;
}

.subtotal-label {
    color: #64748b;
    font-size: 0.85rem;
    display: block;
}

.subtotal-value {
    font-weight: bold;
    color: #059669;
    font-size: 1.2rem;
}

.remove-item-btn {
    background: none;
    border: 1px solid #e2e8f0;
    color: #ef4444;
    padding: 0.5rem 1rem;
    border-radius: 30px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    transition: all 0.3s;
}

.remove-item-btn:hover {
    background: #ef4444;
    color: white;
    border-color: #ef4444;
}

/* Cart Summary Sidebar */
.cart-summary-sidebar {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    position: sticky;
    top: 100px;
    height: fit-content;
}

.cart-summary-sidebar h3 {
    color: #1e293b;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #eef2f6;
}

.summary-items {
    margin-bottom: 1.5rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.8rem 0;
    color: #64748b;
}

.summary-row.total {
    border-top: 2px solid #eef2f6;
    margin-top: 0.5rem;
    padding-top: 1.5rem;
    font-weight: bold;
    color: #1e293b;
    font-size: 1.2rem;
}

.total-amount {
    color: #059669;
}

.free-delivery {
    color: #10b981;
    font-weight: 600;
}

.payment-methods-summary {
    background: #f8fafc;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.payment-methods-summary h4 {
    color: #1e293b;
    margin-bottom: 0.8rem;
    font-size: 0.9rem;
}

.payment-icons {
    display: flex;
    gap: 1rem;
}

.payment-icons img {
    width: 40px;
    height: 40px;
}

.checkout-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #059669, #047857);
    color: white;
    border: none;
    border-radius: 40px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    transition: all 0.3s;
    margin-bottom: 1rem;
}

.checkout-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(5,150,105,0.4);
}

.continue-shopping {
    display: block;
    text-align: center;
    color: #64748b;
    text-decoration: none;
    padding: 0.5rem;
    transition: color 0.3s;
}

.continue-shopping:hover {
    color: #2563eb;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.05);
}

.empty-cart-animation img {
    width: 200px;
    height: 200px;
    margin-bottom: 2rem;
    animation: float 3s ease-in-out infinite;
}

.empty-cart h2 {
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
}

.empty-cart p {
    color: #64748b;
    margin-bottom: 2rem;
}

.shop-now-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1rem 2.5rem;
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s;
}

.shop-now-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(37,99,235,0.4);
}

/* Alerts */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideInDown 0.3s ease;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* Animations */
@keyframes slideInDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Responsive */
@media (max-width: 968px) {
    .cart-container {
        grid-template-columns: 1fr;
    }
    
    .cart-summary-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .item-image {
        width: 100%;
        height: 200px;
    }
    
    .item-actions {
        align-items: flex-start;
    }
    
    .cart-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .cart-page {
        padding: 1rem;
    }
    
    .quantity-control {
        width: 100%;
    }
    
    .remove-item-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
// Update cart item quantity
function updateCartItem(cartId, change) {
    const input = document.getElementById(`qty-${cartId}`);
    let newValue = parseInt(input.value) + change;
    const max = parseInt(input.max);
    
    if (newValue >= 1 && newValue <= max) {
        input.value = newValue;
        updateCartItemQuantity(cartId, newValue);
    }
}

function updateCartItemQuantity(cartId, quantity) {
    document.getElementById('update_cart_id').value = cartId;
    document.getElementById('update_quantity').value = quantity;
    document.getElementById('updateCartForm').submit();
}

// Add to cart function (for use on index page)
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
            // Show success message
            showNotification(data.message, 'success');
            
            // Update cart icon count
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.display = 'inline';
            }
            
            // Add animation to cart icon
            const cartIcon = document.querySelector('.cart-icon');
            if (cartIcon) {
                cartIcon.classList.add('bounce');
                setTimeout(() => cartIcon.classList.remove('bounce'), 1000);
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
        top: 20px;
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
    
    .bounce {
        animation: bounce 0.5s ease;
    }
`;
document.head.appendChild(style);
</script>

<?php include 'footer.php'; ?>