<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Update order quantity
    if (isset($_POST['update_quantity'])) {
        $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
        $new_quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
        
        // Get order details
        $order_query = "SELECT o.*, m.price, m.stock, m.name 
                       FROM orders o 
                       JOIN medicines m ON o.medicine_id = m.id 
                       WHERE o.id = $order_id AND o.user_id = $user_id AND o.status = 'pending'";
        $order_result = mysqli_query($conn, $order_query);
        
        if ($order = mysqli_fetch_assoc($order_result)) {
            $old_quantity = $order['quantity'];
            $stock_available = $order['stock'] + $old_quantity;
            
            if ($new_quantity <= $stock_available) {
                // Update medicine stock
                $stock_diff = $new_quantity - $old_quantity;
                $new_stock = $order['stock'] - $stock_diff;
                
                // Calculate new total
                $price = $order['price'];
                $new_total = $price * $new_quantity;
                
                // Update order
                $update_query = "UPDATE orders SET quantity = $new_quantity, total_amount = $new_total 
                               WHERE id = $order_id";
                
                // Update medicine stock
                $stock_query = "UPDATE medicines SET stock = $new_stock WHERE id = " . $order['medicine_id'];
                
                if (mysqli_query($conn, $update_query) && mysqli_query($conn, $stock_query)) {
                    $message = "Order #$order_id quantity updated successfully!";
                } else {
                    $error = "Failed to update order: " . mysqli_error($conn);
                }
            } else {
                $error = "Insufficient stock. Only $stock_available items available.";
            }
        }
    }
    
    // Remove order item
    elseif (isset($_POST['remove_item'])) {
        $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
        
        // Get order details to restore stock
        $order_query = "SELECT o.*, m.id as medicine_id FROM orders o 
                       JOIN medicines m ON o.medicine_id = m.id 
                       WHERE o.id = $order_id AND o.user_id = $user_id AND o.status = 'pending'";
        $order_result = mysqli_query($conn, $order_query);
        
        if ($order = mysqli_fetch_assoc($order_result)) {
            // Restore stock
            $restore_stock = "UPDATE medicines SET stock = stock + {$order['quantity']} 
                             WHERE id = {$order['medicine_id']}";
            mysqli_query($conn, $restore_stock);
            
            // Delete order
            $delete_query = "DELETE FROM orders WHERE id = $order_id";
            
            if (mysqli_query($conn, $delete_query)) {
                $message = "Item removed from your orders.";
            } else {
                $error = "Failed to remove item: " . mysqli_error($conn);
            }
        }
    }
    
    // Cancel order
    elseif (isset($_POST['cancel_order'])) {
        $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
        
        // Get order details to restore stock
        $order_query = "SELECT o.*, m.id as medicine_id FROM orders o 
                       JOIN medicines m ON o.medicine_id = m.id 
                       WHERE o.id = $order_id AND o.user_id = $user_id AND o.status = 'pending'";
        $order_result = mysqli_query($conn, $order_query);
        
        if ($order = mysqli_fetch_assoc($order_result)) {
            // Restore stock
            $restore_stock = "UPDATE medicines SET stock = stock + {$order['quantity']} 
                             WHERE id = {$order['medicine_id']}";
            mysqli_query($conn, $restore_stock);
            
            // Update order status to cancelled
            $cancel_query = "UPDATE orders SET status = 'cancelled' WHERE id = $order_id";
            
            if (mysqli_query($conn, $cancel_query)) {
                $message = "Order #$order_id has been cancelled.";
            } else {
                $error = "Failed to cancel order: " . mysqli_error($conn);
            }
        }
    }
    
    // Reorder item
    elseif (isset($_POST['reorder'])) {
        $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
        
        // Get original order details
        $order_query = "SELECT o.*, m.stock, m.price, m.name 
                       FROM orders o 
                       JOIN medicines m ON o.medicine_id = m.id 
                       WHERE o.id = $order_id AND o.user_id = $user_id";
        $order_result = mysqli_query($conn, $order_query);
        
        if ($order = mysqli_fetch_assoc($order_result)) {
            // Check if medicine still has stock
            if ($order['stock'] >= $order['quantity']) {
                // Create new order
                $insert_query = "INSERT INTO orders (user_id, medicine_id, quantity, total_amount, payment_method, status) 
                               VALUES ($user_id, {$order['medicine_id']}, {$order['quantity']}, {$order['total_amount']}, '{$order['payment_method']}', 'pending')";
                
                // Update stock
                $new_stock = $order['stock'] - $order['quantity'];
                $stock_query = "UPDATE medicines SET stock = $new_stock WHERE id = {$order['medicine_id']}";
                
                if (mysqli_query($conn, $insert_query) && mysqli_query($conn, $stock_query)) {
                    $message = "Item added to your cart successfully!";
                } else {
                    $error = "Failed to reorder: " . mysqli_error($conn);
                }
            } else {
                $error = "Insufficient stock available.";
            }
        }
    }
    
    // Add review
    elseif (isset($_POST['add_review'])) {
        $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
        $rating = mysqli_real_escape_string($conn, $_POST['rating']);
        $review_text = mysqli_real_escape_string($conn, $_POST['review_text']);
        
        // Get medicine id from order
        $medicine_query = "SELECT medicine_id FROM orders WHERE id = $order_id AND user_id = $user_id";
        $medicine_result = mysqli_query($conn, $medicine_query);
        $medicine = mysqli_fetch_assoc($medicine_result);
        
        // Insert review
        $review_query = "INSERT INTO reviews (user_id, medicine_id, order_id, rating, review_text, created_at) 
                        VALUES ($user_id, {$medicine['medicine_id']}, $order_id, $rating, '$review_text', NOW())";
        
        if (mysqli_query($conn, $review_query)) {
            $message = "Thank you for your review!";
        } else {
            $error = "Failed to submit review: " . mysqli_error($conn);
        }
    }
}

// Fetch user orders with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query based on filters
$where_clause = "WHERE o.user_id = $user_id";
if ($status_filter && $status_filter != 'all') {
    $where_clause .= " AND o.status = '$status_filter'";
}

// Count total orders
$count_query = "SELECT COUNT(*) as total FROM orders o $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_orders = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_orders / $limit);

// Fetch orders with medicine details
$query = "SELECT o.*, m.name as medicine_name, m.image_url, m.category,
          CASE 
            WHEN o.status = 'pending' THEN 'Pending Payment'
            WHEN o.status = 'processing' THEN 'Processing'
            WHEN o.status = 'shipped' THEN 'Shipped'
            WHEN o.status = 'delivered' THEN 'Delivered'
            WHEN o.status = 'completed' THEN 'Completed'
            WHEN o.status = 'cancelled' THEN 'Cancelled'
          END as status_text,
          CASE
            WHEN o.status = 'pending' THEN 'warning'
            WHEN o.status = 'processing' THEN 'info'
            WHEN o.status = 'shipped' THEN 'primary'
            WHEN o.status = 'delivered' THEN 'success'
            WHEN o.status = 'completed' THEN 'success'
            WHEN o.status = 'cancelled' THEN 'danger'
          END as status_color
          FROM orders o 
          JOIN medicines m ON o.medicine_id = m.id 
          $where_clause
          ORDER BY 
            CASE 
              WHEN o.status = 'pending' THEN 1
              WHEN o.status = 'processing' THEN 2
              WHEN o.status = 'shipped' THEN 3
              WHEN o.status = 'delivered' THEN 4
              WHEN o.status = 'completed' THEN 5
              ELSE 6
            END,
            o.order_date DESC 
          LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// Calculate cart totals
$cart_query = "SELECT COUNT(*) as item_count, SUM(total_amount) as cart_total 
               FROM orders 
               WHERE user_id = $user_id AND status = 'pending'";
$cart_result = mysqli_query($conn, $cart_query);
$cart_data = mysqli_fetch_assoc($cart_result);
$cart_count = $cart_data['item_count'] ?? 0;
$cart_total = $cart_data['cart_total'] ?? 0;

// Get order statistics
$stats_query = "SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_count,
                COUNT(CASE WHEN status = 'shipped' THEN 1 END) as shipped_count,
                COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_spent
                FROM orders 
                WHERE user_id = $user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>

<?php include 'header.php'; ?>

<div class="orders-page">
    <!-- Page Header with Stats -->
    <div class="orders-header">
        <div class="header-title">
            <h1>My Orders</h1>
            <p>Manage and track your orders</p>
        </div>
        
        <?php if ($cart_count > 0): ?>
            <div class="cart-summary" onclick="window.location.href='#pending-orders'">
                <div class="cart-icon">🛒</div>
                <div class="cart-info">
                    <span class="cart-count"><?php echo $cart_count; ?> items</span>
                    <span class="cart-total">RW <?php echo number_format($cart_total * 3700, 0); ?></span>
                </div>
                <button class="checkout-btn" onclick="event.stopPropagation(); checkoutAll()">
                    Checkout All
                </button>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Order Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card pending">
            <div class="stat-icon">⏳</div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['pending_count'] ?? 0; ?></span>
                <span class="stat-label">Pending</span>
            </div>
        </div>
        
        <div class="stat-card processing">
            <div class="stat-icon">⚙️</div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['processing_count'] ?? 0; ?></span>
                <span class="stat-label">Processing</span>
            </div>
        </div>
        
        <div class="stat-card shipped">
            <div class="stat-icon">🚚</div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['shipped_count'] ?? 0; ?></span>
                <span class="stat-label">Shipped</span>
            </div>
        </div>
        
        <div class="stat-card delivered">
            <div class="stat-icon">✅</div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['delivered_count'] ?? 0; ?></span>
                <span class="stat-label">Delivered</span>
            </div>
        </div>
        
        <div class="stat-card completed">
            <div class="stat-icon">⭐</div>
            <div class="stat-details">
                <span class="stat-value"><?php echo $stats['completed_count'] ?? 0; ?></span>
                <span class="stat-label">Completed</span>
            </div>
        </div>
        
        <div class="stat-card total-spent">
            <div class="stat-icon">💰</div>
            <div class="stat-details">
                <span class="stat-value">RW <?php echo number_format(($stats['total_spent'] ?? 0) * 3700, 0); ?></span>
                <span class="stat-label">Total Spent</span>
            </div>
        </div>
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
    
    <!-- Order Filters -->
    <div class="order-filters">
        <div class="filter-tabs">
            <a href="?status=all" class="filter-tab <?php echo !$status_filter || $status_filter == 'all' ? 'active' : ''; ?>">
                All Orders
            </a>
            <a href="?status=pending" class="filter-tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                Pending
            </a>
            <a href="?status=processing" class="filter-tab <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">
                Processing
            </a>
            <a href="?status=shipped" class="filter-tab <?php echo $status_filter == 'shipped' ? 'active' : ''; ?>">
                Shipped
            </a>
            <a href="?status=delivered" class="filter-tab <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
                Delivered
            </a>
            <a href="?status=completed" class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                Completed
            </a>
            <a href="?status=cancelled" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                Cancelled
            </a>
        </div>
        
        <div class="search-box">
            <input type="text" id="orderSearch" placeholder="Search orders..." onkeyup="searchOrders()">
            <span class="search-icon">🔍</span>
        </div>
    </div>
    
    <!-- Orders List -->
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="orders-list" id="ordersList">
            <?php while ($order = mysqli_fetch_assoc($result)): 
                $status = $order['status'];
                $is_pending = $status == 'pending';
                $is_processing = $status == 'processing';
                $is_shipped = $status == 'shipped';
                $is_delivered = $status == 'delivered';
                $is_completed = $status == 'completed';
                $is_cancelled = $status == 'cancelled';
                
                // Calculate delivery estimate
                $delivery_estimate = '';
                if ($is_processing) {
                    $delivery_estimate = 'Estimated delivery: 2-3 business days';
                } elseif ($is_shipped) {
                    $delivery_estimate = 'Out for delivery today';
                } elseif ($is_delivered) {
                    $delivery_estimate = 'Delivered on ' . date('d M Y', strtotime($order['order_date'] . ' +3 days'));
                }
            ?>
                <div class="order-card status-<?php echo $status; ?>" data-order-id="<?php echo $order['id']; ?>" data-status="<?php echo $status; ?>">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-id-badge">
                                <span class="id-label">Order #</span>
                                <span class="id-value"><?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="order-date">
                                <span class="date-icon">📅</span>
                                <?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?>
                            </div>
                        </div>
                        
                        <div class="order-status-badge status-<?php echo $status; ?>">
                            <span class="status-dot"></span>
                            <?php echo $order['status_text']; ?>
                        </div>
                    </div>
                    
                    <!-- Order Body -->
                    <div class="order-body">
                        <!-- Medicine Details -->
                        <div class="medicine-details">
                            <div class="medicine-image">
                                <img src="<?php echo getOnlineMedicineImage($order['medicine_name']); ?>" 
                                     alt="<?php echo $order['medicine_name']; ?>">
                            </div>
                            <div class="medicine-info">
                                <h3 class="medicine-name"><?php echo $order['medicine_name']; ?></h3>
                                <p class="medicine-category"><?php echo $order['category']; ?></p>
                                
                                <!-- Progress Tracker for active orders -->
                                <?php if (!$is_cancelled && !$is_completed): ?>
                                    <div class="order-progress">
                                        <div class="progress-steps">
                                            <div class="step <?php echo $is_pending ? 'active' : 'completed'; ?>">
                                                <span class="step-icon">📝</span>
                                                <span class="step-label">Ordered</span>
                                            </div>
                                            <div class="step <?php echo $is_processing ? 'active' : ($is_shipped || $is_delivered ? 'completed' : ''); ?>">
                                                <span class="step-icon">⚙️</span>
                                                <span class="step-label">Processing</span>
                                            </div>
                                            <div class="step <?php echo $is_shipped ? 'active' : ($is_delivered ? 'completed' : ''); ?>">
                                                <span class="step-icon">🚚</span>
                                                <span class="step-label">Shipped</span>
                                            </div>
                                            <div class="step <?php echo $is_delivered ? 'active' : ''; ?>">
                                                <span class="step-icon">✅</span>
                                                <span class="step-label">Delivered</span>
                                            </div>
                                        </div>
                                        <?php if ($delivery_estimate): ?>
                                            <p class="delivery-estimate"><?php echo $delivery_estimate; ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Order Details -->
                        <div class="order-details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Quantity</span>
                                <?php if ($is_pending): ?>
                                    <div class="quantity-control">
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo $order['id']; ?>, -1)">−</button>
                                        <span class="quantity" id="qty-<?php echo $order['id']; ?>"><?php echo $order['quantity']; ?></span>
                                        <button class="qty-btn" onclick="updateQuantity(<?php echo $order['id']; ?>, 1)">+</button>
                                    </div>
                                <?php else: ?>
                                    <span class="detail-value"><?php echo $order['quantity']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Unit Price</span>
                                <span class="detail-value">RW <?php echo number_format(($order['total_amount'] / $order['quantity']) * 3700, 0); ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value total">RW <?php echo number_format($order['total_amount'] * 3700, 0); ?></span>
                            </div>
                            
                            <?php if ($order['payment_method']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Payment Method</span>
                                    <span class="detail-value payment-method">
                                        <img src="https://cdn-icons-png.flaticon.com/512/3030/3030<?php echo $order['payment_method'] == 'MTN' ? '247' : '251'; ?>.png" 
                                             alt="<?php echo $order['payment_method']; ?>" width="20">
                                        <?php echo $order['payment_method']; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['transaction_id']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Transaction ID</span>
                                    <span class="detail-value transaction-id"><?php echo $order['transaction_id']; ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($order['payment_status'] == 'paid'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Payment Status</span>
                                    <span class="badge-paid">✓ Paid</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Actions -->
                    <div class="order-actions">
                        <?php if ($is_pending): ?>
                            <!-- Pending Order Actions -->
                            <form method="POST" class="action-form" onsubmit="return confirm('Update quantity?')">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="quantity" id="input-qty-<?php echo $order['id']; ?>" value="<?php echo $order['quantity']; ?>">
                                <button type="submit" name="update_quantity" class="action-btn update-btn">
                                    <span class="btn-icon">🔄</span>
                                    Update
                                </button>
                            </form>
                            
                            <form method="POST" class="action-form" onsubmit="return confirm('Remove this item from cart?')">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="remove_item" class="action-btn remove-btn">
                                    <span class="btn-icon">🗑️</span>
                                    Remove
                                </button>
                            </form>
                            
                            <form method="POST" class="action-form" onsubmit="return confirm('Cancel this order?')">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="cancel_order" class="action-btn cancel-btn">
                                    <span class="btn-icon">✖️</span>
                                    Cancel
                                </button>
                            </form>
                            
                            <button class="action-btn pay-btn" onclick="showPaymentModal(<?php echo $order['id']; ?>, '<?php echo $order['medicine_name']; ?>', <?php echo $order['total_amount'] * 3700; ?>)">
                                <span class="btn-icon">💰</span>
                                Pay Now
                            </button>
                            
                        <?php elseif ($is_processing || $is_shipped): ?>
                            <!-- Processing/Shipped Order Actions -->
                            <button class="action-btn track-btn" onclick="trackOrder(<?php echo $order['id']; ?>)">
                                <span class="btn-icon">📍</span>
                                Track Order
                            </button>
                            
                            <button class="action-btn contact-btn" onclick="contactSupport(<?php echo $order['id']; ?>)">
                                <span class="btn-icon">💬</span>
                                Contact Support
                            </button>
                            
                        <?php elseif ($is_delivered || $is_completed): ?>
                            <!-- Delivered/Completed Order Actions -->
                            <?php if (!$is_completed): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <button type="submit" name="reorder" class="action-btn reorder-btn">
                                        <span class="btn-icon">🔄</span>
                                        Reorder
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <button class="action-btn review-btn" onclick="showReviewModal(<?php echo $order['id']; ?>, '<?php echo $order['medicine_name']; ?>')">
                                <span class="btn-icon">⭐</span>
                                Write Review
                            </button>
                            
                            <button class="action-btn details-btn" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                <span class="btn-icon">📋</span>
                                View Details
                            </button>
                            
                        <?php elseif ($is_cancelled): ?>
                            <!-- Cancelled Order Actions -->
                            <form method="POST" class="action-form">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="reorder" class="action-btn reorder-btn">
                                    <span class="btn-icon">🔄</span>
                                    Order Again
                                </button>
                            </form>
                            
                            <button class="action-btn why-btn" onclick="showCancellationReason(<?php echo $order['id']; ?>)">
                                <span class="btn-icon">❓</span>
                                Why Cancelled?
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo max(1, $page-1); ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>" 
                   class="page-link <?php echo $page == 1 ? 'disabled' : ''; ?>">
                    ← Previous
                </a>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <a href="?page=<?php echo min($total_pages, $page+1); ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?>" 
                   class="page-link <?php echo $page == $total_pages ? 'disabled' : ''; ?>">
                    Next →
                </a>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- No Orders -->
        <div class="no-orders">
            <div class="no-orders-animation">
                <img src="https://cdn-icons-png.flaticon.com/512/4076/4076478.png" alt="No orders">
            </div>
            <h2>No orders found</h2>
            <p>Start shopping to see your orders here</p>
            <a href="index.php#medicines" class="shop-now-btn">
                <span class="btn-icon">🛒</span>
                Browse Medicines
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content payment-modal">
        <div class="modal-header">
            <h2>Complete Payment</h2>
            <button class="close-modal" onclick="closePaymentModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="payment-summary" id="paymentSummary"></div>
            
            <form method="POST" action="" id="paymentForm">
                <input type="hidden" name="order_id" id="paymentOrderId">
                
                <div class="payment-methods">
                    <h3>Select Payment Method</h3>
                    <div class="method-grid">
                        <label class="method-card">
                            <input type="radio" name="payment_method" value="MTN" checked>
                            <img src="https://cdn-icons-png.flaticon.com/512/3030/3030247.png" alt="MTN">
                            <h4>MTN Mobile Money</h4>
                            <p>Pay using MTN MoMo</p>
                            <span class="method-number">0700 000 000</span>
                        </label>
                        
                        <label class="method-card">
                            <input type="radio" name="payment_method" value="AIRTEL">
                            <img src="https://cdn-icons-png.flaticon.com/512/3030/3030251.png" alt="Airtel">
                            <h4>Airtel Money</h4>
                            <p>Pay using Airtel Money</p>
                            <span class="method-number">0750 000 000</span>
                        </label>
                    </div>
                </div>
                
                <div class="payment-instructions">
                    <h4>📱 How to Pay:</h4>
                    <ol class="instruction-list">
                        <li>Dial <span class="highlight">*165#</span> for MTN or <span class="highlight">*185#</span> for Airtel</li>
                        <li>Select "Send Money" or "Pay"</li>
                        <li>Enter the merchant number shown above</li>
                        <li>Enter the amount shown below</li>
                        <li>Enter your PIN to confirm</li>
                        <li>Enter the reference number from SMS</li>
                    </ol>
                </div>
                
                <div class="amount-box">
                    <span class="amount-label">Total Amount:</span>
                    <span class="amount-value" id="paymentAmount">RW 0</span>
                </div>
                
                <button type="submit" name="pay_now" class="confirm-payment-btn">
                    <span>Confirm Payment</span>
                    <div class="loader" style="display: none;"></div>
                </button>
                
                <p class="payment-note">
                    <span class="note-icon">⏰</span>
                    Complete payment within 10 minutes
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content review-modal">
        <div class="modal-header">
            <h2>Write a Review</h2>
            <button class="close-modal" onclick="closeReviewModal()">&times;</button>
        </div>
        
        <div class="modal-body">
            <form method="POST" action="" id="reviewForm">
                <input type="hidden" name="order_id" id="reviewOrderId">
                
                <div class="product-info" id="reviewProduct"></div>
                
                <div class="rating-section">
                    <label>Your Rating</label>
                    <div class="star-rating">
                        <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                        <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                        <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                        <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                        <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="review_text">Your Review</label>
                    <textarea id="review_text" name="review_text" rows="4" placeholder="Share your experience with this product..."></textarea>
                </div>
                
                <button type="submit" name="add_review" class="submit-review-btn">
                    Submit Review
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content details-modal">
        <div class="modal-header">
            <h2>Order Details</h2>
            <button class="close-modal" onclick="closeDetailsModal()">&times;</button>
        </div>
        
        <div class="modal-body" id="orderDetailsContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<style>
/* Orders Page Styles */
.orders-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    background: #f8fafc;
    min-height: calc(100vh - 200px);
}

/* Header Styles */
.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
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

.cart-summary {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 1rem 2rem;
    border-radius: 60px;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 10px 30px rgba(37,99,235,0.3);
}

.cart-summary:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(37,99,235,0.4);
}

.cart-icon {
    font-size: 2rem;
}

.cart-info {
    display: flex;
    flex-direction: column;
}

.cart-count {
    font-size: 1.1rem;
    font-weight: 500;
}

.cart-total {
    font-size: 1.3rem;
    font-weight: bold;
}

.checkout-btn {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 40px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
}

.checkout-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.2rem;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.stat-card.pending { border-left: 4px solid #f59e0b; }
.stat-card.processing { border-left: 4px solid #3b82f6; }
.stat-card.shipped { border-left: 4px solid #8b5cf6; }
.stat-card.delivered { border-left: 4px solid #10b981; }
.stat-card.completed { border-left: 4px solid #059669; }
.stat-card.total-spent { border-left: 4px solid #6366f1; }

.stat-icon {
    font-size: 2rem;
}

.stat-details {
    display: flex;
    flex-direction: column;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #1e293b;
}

.stat-label {
    font-size: 0.85rem;
    color: #64748b;
}

/* Alert Messages */
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

.alert-icon {
    font-size: 1.3rem;
}

/* Filters */
.order-filters {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.filter-tabs {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.filter-tab {
    padding: 0.6rem 1.2rem;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    color: #64748b;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.3s;
}

.filter-tab:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.filter-tab.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.search-box {
    position: relative;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 0.6rem 1rem 0.6rem 2.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 30px;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.search-box input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

/* Order Cards */
.order-card {
    background: white;
    border-radius: 20px;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    overflow: hidden;
    transition: all 0.3s;
    border: 1px solid #eef2f6;
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.1);
}

.order-card.status-pending { border-top: 4px solid #f59e0b; }
.order-card.status-processing { border-top: 4px solid #3b82f6; }
.order-card.status-shipped { border-top: 4px solid #8b5cf6; }
.order-card.status-delivered { border-top: 4px solid #10b981; }
.order-card.status-completed { border-top: 4px solid #059669; }
.order-card.status-cancelled { border-top: 4px solid #ef4444; }

.order-header {
    padding: 1.2rem 1.5rem;
    background: #f8fafc;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #eef2f6;
}

.order-info {
    display: flex;
    gap: 2rem;
    align-items: center;
}

.order-id-badge {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.id-label {
    color: #64748b;
    font-size: 0.9rem;
}

.id-value {
    font-weight: bold;
    color: #1e293b;
    font-size: 1.1rem;
}

.order-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #64748b;
    font-size: 0.9rem;
}

.order-status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 1rem;
    border-radius: 30px;
    font-size: 0.85rem;
    font-weight: 600;
}

.order-status-badge.status-pending {
    background: #fef3c7;
    color: #b45309;
}

.order-status-badge.status-processing {
    background: #dbeafe;
    color: #1e40af;
}

.order-status-badge.status-shipped {
    background: #ede9fe;
    color: #5b21b6;
}

.order-status-badge.status-delivered {
    background: #d1fae5;
    color: #065f46;
}

.order-status-badge.status-completed {
    background: #d1fae5;
    color: #065f46;
}

.order-status-badge.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: currentColor;
}

.order-body {
    padding: 1.5rem;
}

.medicine-details {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.medicine-image {
    width: 100px;
    height: 100px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #eef2f6;
}

.medicine-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.medicine-info {
    flex: 1;
}

.medicine-name {
    font-size: 1.2rem;
    color: #1e293b;
    margin-bottom: 0.3rem;
}

.medicine-category {
    color: #64748b;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

/* Progress Tracker */
.order-progress {
    margin-top: 1rem;
}

.progress-steps {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    flex: 1;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    right: -50%;
    width: 100%;
    height: 2px;
    background: #e2e8f0;
    z-index: 1;
}

.step.completed:not(:last-child)::after {
    background: #10b981;
}

.step-icon {
    width: 30px;
    height: 30px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.3rem;
    z-index: 2;
    font-size: 1rem;
}

.step.completed .step-icon {
    background: #10b981;
    color: white;
}

.step.active .step-icon {
    background: #3b82f6;
    color: white;
    animation: pulse 1.5s infinite;
}

.step-label {
    font-size: 0.75rem;
    color: #64748b;
    text-align: center;
}

.delivery-estimate {
    font-size: 0.85rem;
    color: #10b981;
    margin-top: 0.5rem;
}

/* Order Details Grid */
.order-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px dashed #e2e8f0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.detail-label {
    font-size: 0.8rem;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
}

.detail-value.total {
    color: #059669;
    font-size: 1.2rem;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.3s;
}

.qty-btn:hover {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.quantity {
    font-weight: 600;
    min-width: 30px;
    text-align: center;
}

.payment-method {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.transaction-id {
    font-family: monospace;
    background: #f1f5f9;
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.9rem;
}

.badge-paid {
    background: #10b981;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Order Actions */
.order-actions {
    padding: 1.2rem 1.5rem;
    background: #f8fafc;
    display: flex;
    gap: 0.8rem;
    justify-content: flex-end;
    border-top: 1px solid #eef2f6;
    flex-wrap: wrap;
}

.action-form {
    display: inline;
}

.action-btn {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 30px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s;
}

.update-btn {
    background: #3b82f6;
    color: white;
}

.remove-btn {
    background: #ef4444;
    color: white;
}

.cancel-btn {
    background: #f59e0b;
    color: white;
}

.pay-btn {
    background: #059669;
    color: white;
}

.track-btn {
    background: #8b5cf6;
    color: white;
}

.contact-btn {
    background: #64748b;
    color: white;
}

.reorder-btn {
    background: #1e293b;
    color: white;
}

.review-btn {
    background: #f59e0b;
    color: white;
}

.details-btn {
    background: #475569;
    color: white;
}

.why-btn {
    background: #94a3b8;
    color: white;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.btn-icon {
    font-size: 1.1rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.6rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-decoration: none;
    color: #64748b;
    transition: all 0.3s;
    min-width: 40px;
    text-align: center;
}

.page-link:hover:not(.disabled) {
    background: #f1f5f9;
    border-color: #94a3b8;
}

.page-link.active {
    background: #2563eb;
    color: white;
    border-color: #2563eb;
}

.page-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* No Orders */
.no-orders {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 30px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.05);
}

.no-orders-animation img {
    width: 150px;
    height: 150px;
    margin-bottom: 2rem;
    opacity: 0.5;
    animation: float 3s ease-in-out infinite;
}

.no-orders h2 {
    color: #1e293b;
    margin-bottom: 0.5rem;
    font-size: 1.8rem;
}

.no-orders p {
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

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease;
}

.modal-content {
    background: white;
    margin: 5% auto;
    border-radius: 24px;
    max-width: 600px;
    position: relative;
    animation: slideInUp 0.4s ease;
}

.modal-header {
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    color: #1e293b;
    font-size: 1.5rem;
}

.close-modal {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #94a3b8;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #1e293b;
}

.modal-body {
    padding: 2rem;
}

/* Payment Modal */
.payment-summary {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 2rem;
}

.payment-methods h3 {
    color: #1e293b;
    margin-bottom: 1rem;
}

.method-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.method-card {
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 1.5rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.method-card:hover {
    border-color: #2563eb;
    transform: translateY(-3px);
}

.method-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.method-card input[type="radio"]:checked + img {
    transform: scale(1.1);
}

.method-card img {
    width: 50px;
    height: 50px;
    margin-bottom: 0.8rem;
    transition: transform 0.3s;
}

.method-card h4 {
    color: #1e293b;
    margin-bottom: 0.3rem;
}

.method-card p {
    color: #64748b;
    font-size: 0.8rem;
    margin-bottom: 0.5rem;
}

.method-number {
    display: inline-block;
    padding: 0.3rem 1rem;
    background: #f1f5f9;
    border-radius: 20px;
    font-weight: 600;
    color: #2563eb;
}

.payment-instructions {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 16px;
    margin-bottom: 1.5rem;
}

.payment-instructions h4 {
    color: #1e293b;
    margin-bottom: 1rem;
}

.instruction-list {
    padding-left: 1.5rem;
    color: #475569;
}

.instruction-list li {
    margin-bottom: 0.5rem;
}

.highlight {
    background: #2563eb;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 6px;
    font-weight: 600;
}

.amount-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.2rem;
    background: linear-gradient(135deg, #059669, #047857);
    color: white;
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.amount-label {
    font-size: 1rem;
    opacity: 0.9;
}

.amount-value {
    font-size: 1.5rem;
    font-weight: bold;
}

.confirm-payment-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.confirm-payment-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(37,99,235,0.4);
}

.payment-note {
    text-align: center;
    color: #64748b;
    font-size: 0.85rem;
    margin-top: 1rem;
}

/* Review Modal */
.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.3rem;
    margin: 1rem 0;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2rem;
    color: #e2e8f0;
    cursor: pointer;
    transition: color 0.3s;
}

.star-rating label:hover,
.star-rating label:hover ~ label,
.star-rating input:checked ~ label {
    color: #fbbf24;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #1e293b;
    font-weight: 500;
}

.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.95rem;
    resize: vertical;
}

.form-group textarea:focus {
    outline: none;
    border-color: #2563eb;
}

.submit-review-btn {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.submit-review-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(245,158,11,0.4);
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

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

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .orders-page {
        padding: 1rem;
    }
    
    .orders-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .cart-summary {
        width: 100%;
        justify-content: space-between;
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .order-filters {
        flex-direction: column;
    }
    
    .filter-tabs {
        justify-content: center;
    }
    
    .search-box {
        width: 100%;
    }
    
    .order-info {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .medicine-details {
        flex-direction: column;
    }
    
    .medicine-image {
        width: 100%;
        height: 150px;
    }
    
    .order-details-grid {
        grid-template-columns: 1fr 1fr;
    }
    
    .order-actions {
        justify-content: center;
    }
    
    .method-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 2% 1rem;
        max-height: 90vh;
        overflow-y: auto;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .order-details-grid {
        grid-template-columns: 1fr;
    }
    
    .action-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
let currentOrderId = null;

// Search orders
function searchOrders() {
    const searchTerm = document.getElementById('orderSearch').value.toLowerCase();
    const orders = document.querySelectorAll('.order-card');
    
    orders.forEach(order => {
        const orderId = order.dataset.orderId;
        const medicineName = order.querySelector('.medicine-name')?.textContent.toLowerCase() || '';
        
        if (orderId.includes(searchTerm) || medicineName.includes(searchTerm)) {
            order.style.display = 'block';
        } else {
            order.style.display = 'none';
        }
    });
}

// Update quantity
function updateQuantity(orderId, change) {
    const qtyElement = document.getElementById(`qty-${orderId}`);
    const inputElement = document.getElementById(`input-qty-${orderId}`);
    let currentQty = parseInt(qtyElement.textContent);
    let newQty = currentQty + change;
    
    if (newQty >= 1) {
        qtyElement.textContent = newQty;
        inputElement.value = newQty;
    }
}

// Payment modal
function showPaymentModal(orderId, medicineName, amount) {
    currentOrderId = orderId;
    document.getElementById('paymentOrderId').value = orderId;
    document.getElementById('paymentAmount').textContent = `RW ${amount.toLocaleString()}`;
    
    document.getElementById('paymentSummary').innerHTML = `
        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
            <span>Medicine:</span>
            <strong>${medicineName}</strong>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Total Amount:</span>
            <strong style="font-size: 1.2rem;">RW ${amount.toLocaleString()}</strong>
        </div>
    `;
    
    document.getElementById('paymentModal').style.display = 'block';
    
    // Start payment timer
    startPaymentTimer();
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

// Review modal
function showReviewModal(orderId, medicineName) {
    document.getElementById('reviewOrderId').value = orderId;
    document.getElementById('reviewProduct').innerHTML = `
        <p style="margin-bottom: 1rem; color: #64748b;">
            Reviewing: <strong>${medicineName}</strong>
        </p>
    `;
    document.getElementById('reviewModal').style.display = 'block';
}

function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

// Order details modal
function viewOrderDetails(orderId) {
    // Fetch order details via AJAX
    fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="details-content">
                    <p><strong>Order ID:</strong> #${String(orderId).padStart(6, '0')}</p>
                    <p><strong>Medicine:</strong> ${data.medicine_name}</p>
                    <p><strong>Quantity:</strong> ${data.quantity}</p>
                    <p><strong>Total Amount:</strong> UGX ${(data.total_amount * 3700).toLocaleString()}</p>
                    <p><strong>Order Date:</strong> ${new Date(data.order_date).toLocaleString()}</p>
                    <p><strong>Status:</strong> ${data.status_text}</p>
                    <p><strong>Payment Method:</strong> ${data.payment_method || 'Not specified'}</p>
                    ${data.transaction_id ? `<p><strong>Transaction ID:</strong> ${data.transaction_id}</p>` : ''}
                    ${data.payment_status ? `<p><strong>Payment Status:</strong> ${data.payment_status}</p>` : ''}
                </div>
            `;
            document.getElementById('orderDetailsModal').style.display = 'block';
        });
}

function closeDetailsModal() {
    document.getElementById('orderDetailsModal').style.display = 'none';
}

// Track order
function trackOrder(orderId) {
    window.location.href = `track_order.php?id=${orderId}`;
}

// Contact support
function contactSupport(orderId) {
    window.location.href = `contact.php?order=${orderId}`;
}

// Show cancellation reason
function showCancellationReason(orderId) {
    alert('This order was cancelled. Please contact support for more information.');
}

// Checkout all items
function checkoutAll() {
    const total = <?php echo $cart_total * 3700; ?>;
    if (total > 0) {
        window.location.href = 'checkout_all.php';
    }
}

// Payment timer
let paymentTimer;
function startPaymentTimer() {
    let timeLeft = 600; // 10 minutes
    const timerDisplay = document.createElement('div');
    timerDisplay.className = 'payment-timer';
    timerDisplay.style.cssText = 'text-align: center; margin-top: 1rem; color: #ef4444; font-weight: bold;';
    
    const modalBody = document.querySelector('#paymentModal .modal-body');
    modalBody.appendChild(timerDisplay);
    
    paymentTimer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.innerHTML = `⏰ Time remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(paymentTimer);
            timerDisplay.innerHTML = '⏰ Payment time expired';
            closePaymentModal();
            alert('Payment time expired. Please try again.');
        }
        timeLeft--;
    }, 1000);
}

// Close modals when clicking outside
window.onclick = function(event) {
    const modals = ['paymentModal', 'reviewModal', 'orderDetailsModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target == modal) {
            modal.style.display = 'none';
            if (modalId === 'paymentModal' && paymentTimer) {
                clearInterval(paymentTimer);
            }
        }
    });
}

// Auto-refresh pending orders count
setInterval(() => {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const cartBadge = document.querySelector('.cart-count');
            const cartTotal = document.querySelector('.cart-total');
            if (cartBadge && cartTotal) {
                cartBadge.textContent = `${data.count} items`;
                cartTotal.textContent = `UGX ${(data.total * 3700).toLocaleString()}`;
            }
        });
}, 30000);
</script>

<?php include 'footer.php'; ?>

<?php
// Helper function for medicine images
function getOnlineMedicineImage($medicineName) {
    $imageMap = [
        'Paracetamol' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&200x200',
        'Amoxicillin' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?ixlib=rb-1.2.1&auto=format&fit=crop&200x200',
        'Vitamin C' => 'https://images.unsplash.com/photo-1584017911766-451b3d0e8434?ixlib=rb-1.2.1&auto=format&fit=crop&200x200',
        'Ibuprofen' => 'https://images.unsplash.com/photo-1550574697-7d776f405ed2?ixlib=rb-1.2.1&auto=format&fit=crop&200x200',
        'Cough Syrup' => 'https://images.unsplash.com/photo-1628771065518-0d82f1938462?ixlib=rb-1.2.1&auto=format&fit=crop&200x200',
        'Antihistamine' => 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?ixlib=rb-1.2.1&auto=format&fit=crop&200x200'
    ];
    
    return isset($imageMap[$medicineName]) ? $imageMap[$medicineName] : 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&200x200';
}
?>