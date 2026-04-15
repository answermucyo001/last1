<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_id'])) {
    $order_id = mysqli_real_escape_string($conn, $_POST['order_id']);
    $transaction_ref = mysqli_real_escape_string($conn, $_POST['transaction_ref'] ?? '');
    $amount = mysqli_real_escape_string($conn, $_POST['amount'] ?? 0);
    
    // First, check if the order belongs to this user
    $check_query = "SELECT * FROM orders WHERE id = $order_id AND user_id = {$_SESSION['user_id']}";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) == 1) {
        $order = mysqli_fetch_assoc($check_result);
        
        // Check which columns exist in the orders table
        $columns_query = "SHOW COLUMNS FROM orders";
        $columns_result = mysqli_query($conn, $columns_query);
        $existing_columns = [];
        while ($column = mysqli_fetch_assoc($columns_result)) {
            $existing_columns[] = $column['Field'];
        }
        
        // Build update query based on existing columns
        $update_fields = [];
        
        if (in_array('payment_status', $existing_columns)) {
            $update_fields[] = "payment_status = 'paid'";
        }
        
        if (in_array('status', $existing_columns)) {
            $update_fields[] = "status = 'processing'";
        }
        
        if (in_array('transaction_ref', $existing_columns)) {
            $update_fields[] = "transaction_ref = '$transaction_ref'";
        }
        
        if (in_array('transaction_id', $existing_columns)) {
            $update_fields[] = "transaction_id = '$transaction_ref'";
        }
        
        if (in_array('paid_at', $existing_columns)) {
            $update_fields[] = "paid_at = NOW()";
        }
        
        if (empty($update_fields)) {
            // If no columns to update, at least update status if it exists
            if (in_array('status', $existing_columns)) {
                $update_fields[] = "status = 'processing'";
            } else {
                // If no relevant columns, just redirect with message
                $_SESSION['success'] = "Payment recorded! Your order will be processed soon.";
                redirect('orders.php');
            }
        }
        
        $update_query = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = $order_id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Payment confirmed! Your order is now being processed.";
            
            // Record payment in payments table if it exists
            $check_payments_table = mysqli_query($conn, "SHOW TABLES LIKE 'payments'");
            if (mysqli_num_rows($check_payments_table) > 0) {
                $payment_query = "INSERT INTO payments (order_id, user_id, amount, transaction_ref, payment_method, status) 
                                 VALUES ($order_id, {$_SESSION['user_id']}, {$order['total_amount']}, '$transaction_ref', '{$order['payment_method']}', 'completed')";
                mysqli_query($conn, $payment_query);
            }
            
            redirect('orders.php');
        } else {
            $error = "Failed to confirm payment: " . mysqli_error($conn);
        }
    } else {
        $error = "Order not found or you don't have permission to update it.";
    }
} else {
    redirect('orders.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - Pharmacy GOLD Health</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .payment-confirmation {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #ef4444;
            margin-bottom: 20px;
        }
        
        .error-title {
            color: #ef4444;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #64748b;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-back {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
            border-radius: 40px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(59,130,246,0.4);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="payment-confirmation">
        <div class="error-icon">❌</div>
        <h1 class="error-title">Payment Confirmation Error</h1>
        <p class="error-message"><?php echo htmlspecialchars($error ?: 'An error occurred while confirming your payment.'); ?></p>
        <a href="orders.php" class="btn-back">Back to Orders</a>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>