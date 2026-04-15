<?php
require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('login.php');
}

$error = '';
$success = '';
$payment_details = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['medicine_id'])) {
    $medicine_id = mysqli_real_escape_string($conn, $_POST['medicine_id']);
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity']);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'MTN');
    $user_id = $_SESSION['user_id'];
    
    // Validate quantity
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Get medicine details - only select columns that definitely exist
    $medicine_query = "SELECT id, name, description, price, stock, image_url, category 
                      FROM medicines 
                      WHERE id = $medicine_id AND stock >= $quantity";
    $medicine_result = mysqli_query($conn, $medicine_query);
    
    // Check for query error
    if (!$medicine_result) {
        die("Query failed: " . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($medicine_result) == 1) {
        $medicine = mysqli_fetch_assoc($medicine_result);
        
        // Calculate price (no discount column)
        $price = $medicine['price'];
        $total_amount = $price * $quantity;
        
        // Get payment instructions
        $payment_query = "SELECT * FROM payment_methods WHERE provider = '$payment_method' AND is_active = 1";
        $payment_result = mysqli_query($conn, $payment_query);
        
        // Default payment info if table doesn't exist
        $payment_info = [
            'number' => ($payment_method == 'MTN') ? '0700 000 000' : '0750 000 000',
            'instructions' => ($payment_method == 'MTN') ? '*165#' : '*185#'
        ];
        
        if ($payment_result && mysqli_num_rows($payment_result) > 0) {
            $payment_info = mysqli_fetch_assoc($payment_result);
        }
        
        // Generate transaction ID
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        
        // Check if orders table has all columns
        $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_status'");
        $has_payment_status = mysqli_num_rows($check_columns) > 0;
        
        $check_transaction = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'transaction_id'");
        $has_transaction_id = mysqli_num_rows($check_transaction) > 0;
        
        // Build order query based on existing columns
        if ($has_payment_status && $has_transaction_id) {
            $order_query = "INSERT INTO orders (user_id, medicine_id, quantity, total_amount, payment_method, transaction_id, payment_status) 
                           VALUES ($user_id, $medicine_id, $quantity, $total_amount, '{$payment_method} Mobile Money', '$transaction_id', 'pending')";
        } else {
            $order_query = "INSERT INTO orders (user_id, medicine_id, quantity, total_amount, payment_method) 
                           VALUES ($user_id, $medicine_id, $quantity, $total_amount, '{$payment_method} Mobile Money')";
        }
        
        if (mysqli_query($conn, $order_query)) {
            $order_id = mysqli_insert_id($conn);
            
            // Update stock
            $new_stock = $medicine['stock'] - $quantity;
            $update_query = "UPDATE medicines SET stock = $new_stock WHERE id = $medicine_id";
            mysqli_query($conn, $update_query);
            
            // Prepare payment details
            $payment_details = [
                'order_id' => $order_id,
                'amount' => $total_amount * 3700, // Convert to UGX
                'number' => $payment_info['number'],
                'instructions' => $payment_info['instructions'],
                'transaction_id' => $transaction_id,
                'medicine_name' => $medicine['name'],
                'quantity' => $quantity,
                'payment_method' => $payment_method
            ];
            
            $success = "Order placed successfully! Please complete payment to proceed.";
        } else {
            $error = "Failed to place order. Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Medicine not available or insufficient stock.";
    }
} else {
    // If no POST data, redirect to home
    redirect('index.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Purchase - Pharmacy GOLD Health</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .payment-success {
            text-align: center;
        }
        
        .success-animation {
            margin: 20px auto;
            width: 100px;
            height: 100px;
        }
        
        .checkmark {
            width: 100px;
            height: 100px;
        }
        
        .checkmark__circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #27ae60;
            fill: none;
            animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
        }
        
        .checkmark__check {
            transform-origin: 50% 50%;
            stroke-dasharray: 48;
            stroke-dashoffset: 48;
            stroke: #27ae60;
            animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
        }
        
        @keyframes stroke {
            100% { stroke-dashoffset: 0; }
        }
        
        .payment-instructions {
            text-align: left;
            margin-top: 30px;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 15px;
            border-left: 4px solid #27ae60;
        }
        
        .order-summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .item-label {
            color: #666;
            font-weight: 500;
        }
        
        .item-value {
            font-weight: bold;
            color: #333;
        }
        
        .total-amount {
            font-size: 1.3rem;
            color: #27ae60;
            font-weight: bold;
        }
        
        .payment-details-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            opacity: 0.9;
        }
        
        .detail-value {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .highlight-number {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 1.2rem;
            letter-spacing: 1px;
        }
        
        .steps-container {
            margin: 25px 0;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background: #27ae60;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .step-text {
            flex: 1;
            color: #333;
        }
        
        .step-text strong {
            color: #27ae60;
        }
        
        .payment-confirm-form {
            margin-top: 25px;
            padding: 20px;
            background: white;
            border-radius: 10px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: #27ae60;
            outline: none;
            box-shadow: 0 0 0 3px rgba(39,174,96,0.1);
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(39,174,96,0.4);
        }
        
        .payment-timer {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 10px;
            border-left: 4px solid #ffc107;
        }
        
        .timer-label {
            color: #856404;
            font-weight: 500;
        }
        
        #paymentTimer {
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
            margin-left: 10px;
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .payment-method-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 20px;
            background: #f0f0f0;
            border-radius: 30px;
            margin: 15px 0;
        }
        
        .payment-method-badge img {
            width: 25px;
            height: 25px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        
        .back-link:hover {
            color: #27ae60;
        }
        
        .payment-error {
            text-align: center;
            padding: 40px;
        }
        
        .payment-error h2 {
            color: #e74c3c;
            margin-bottom: 15px;
        }
        
        .payment-error p {
            color: #666;
            margin-bottom: 25px;
        }
        
        .btn-secondary {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(52,152,219,0.4);
        }
        
        @media (max-width: 768px) {
            .payment-container {
                margin: 20px;
                padding: 20px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .step {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="payment-container">
        <?php if ($success): ?>
            <div class="payment-success">
                <div class="success-animation">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                        <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
                
                <h2>✅ Order Placed Successfully!</h2>
                <p>Your order #<?php echo $payment_details['order_id']; ?> has been placed.</p>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h3>📋 Order Summary</h3>
                    <div class="summary-item">
                        <span class="item-label">Medicine:</span>
                        <span class="item-value"><?php echo htmlspecialchars($payment_details['medicine_name']); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Quantity:</span>
                        <span class="item-value"><?php echo $payment_details['quantity']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Unit Price:</span>
                        <span class="item-value">UGX <?php echo number_format($payment_details['amount'] / $payment_details['quantity'], 0); ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="item-label">Total Amount:</span>
                        <span class="item-value total-amount">UGX <?php echo number_format($payment_details['amount'], 0); ?></span>
                    </div>
                </div>
                
                <!-- Payment Method Badge -->
                <div class="payment-method-badge">
                    <img src="https://cdn-icons-png.flaticon.com/512/3030/3030<?php echo $payment_details['payment_method'] == 'MTN' ? '247' : '251'; ?>.png" 
                         alt="<?php echo $payment_details['payment_method']; ?>">
                    <span>Paying with <?php echo $payment_details['payment_method']; ?> Mobile Money</span>
                </div>
                
                <div class="payment-instructions">
                    <h3>📱 Payment Instructions</h3>
                    
                    <div class="payment-details-card">
                        <div class="detail-row">
                            <span class="detail-label">Amount to pay:</span>
                            <span class="detail-value">UGX <?php echo number_format($payment_details['amount'], 0); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Pay to number:</span>
                            <span class="detail-value highlight-number"><?php echo $payment_details['number']; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Transaction ID:</span>
                            <span class="detail-value"><?php echo $payment_details['transaction_id']; ?></span>
                        </div>
                    </div>
                    
                    <div class="steps-container">
                        <h4>Follow these steps:</h4>
                        
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-text">
                                Dial <strong><?php echo $payment_details['instructions']; ?></strong> on your phone
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-text">
                                Select "Send Money" or "Pay"
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-text">
                                Enter merchant number: <strong><?php echo $payment_details['number']; ?></strong>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-text">
                                Enter amount: <strong>UGX <?php echo number_format($payment_details['amount'], 0); ?></strong>
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">5</div>
                            <div class="step-text">
                                Enter your PIN to confirm
                            </div>
                        </div>
                        
                        <div class="step">
                            <div class="step-number">6</div>
                            <div class="step-text">
                                Keep the confirmation SMS
                            </div>
                        </div>
                    </div>
                    
                    <form action="confirm_payment.php" method="POST" class="payment-confirm-form">
                        <input type="hidden" name="order_id" value="<?php echo $payment_details['order_id']; ?>">
                        <input type="hidden" name="amount" value="<?php echo $payment_details['amount']; ?>">
                        
                        <div class="form-group">
                            <label for="transaction_ref">📱 MTN/Airtel Transaction Reference</label>
                            <input type="text" id="transaction_ref" name="transaction_ref" required 
                                   placeholder="Enter the reference number from SMS"
                                   pattern="[A-Za-z0-9]{8,20}"
                                   title="Transaction reference should be 8-20 alphanumeric characters">
                            <small style="color: #666; display: block; margin-top: 5px;">
                                Example: TXN12345678 or REF123456
                            </small>
                        </div>
                        
                        <button type="submit" class="btn-primary">✓ Confirm Payment</button>
                    </form>
                    
                    <div class="payment-timer">
                        <span class="timer-label">⏰ Complete payment within:</span>
                        <span class="timer" id="paymentTimer">10:00</span>
                    </div>
                    
                    <p style="color: #666; font-size: 0.9rem; margin-top: 15px;">
                        <i class="fas fa-info-circle"></i> 
                        Your order will be processed immediately after payment confirmation
                    </p>
                </div>
                
                <a href="orders.php" class="back-link">← View My Orders</a>
            </div>
            
            <script>
            // Payment timer
            let timeLeft = 600; // 10 minutes in seconds
            const timerElement = document.getElementById('paymentTimer');
            
            const timer = setInterval(() => {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    timerElement.textContent = "EXPIRED";
                    timerElement.style.color = "red";
                    
                    // Show warning
                    const warning = document.createElement('div');
                    warning.style.cssText = 'background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-top: 10px;';
                    warning.innerHTML = '⏰ Payment time expired. Please place a new order.';
                    document.querySelector('.payment-timer').appendChild(warning);
                }
                timeLeft--;
            }, 1000);
            
            // Auto-copy transaction reference
            document.getElementById('transaction_ref').addEventListener('focus', function() {
                this.select();
            });
            
            // Validate form before submit
            document.querySelector('.payment-confirm-form').addEventListener('submit', function(e) {
                const ref = document.getElementById('transaction_ref').value;
                if (ref.length < 8) {
                    e.preventDefault();
                    alert('Please enter a valid transaction reference (minimum 8 characters)');
                }
            });
            </script>
            
        <?php else: ?>
            <div class="payment-error">
                <h2>❌ Payment Error</h2>
                <p><?php echo htmlspecialchars($error ?: 'Invalid request. Please try again.'); ?></p>
                <a href="index.php#medicines" class="btn-secondary">← Back to Shopping</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>