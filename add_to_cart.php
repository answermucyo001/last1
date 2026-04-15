<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $medicine_id = mysqli_real_escape_string($conn, $_POST['medicine_id']);
    $quantity = mysqli_real_escape_string($conn, $_POST['quantity'] ?? 1);
    
    // Validate quantity
    if ($quantity < 1) $quantity = 1;
    
    // Check if medicine exists and has stock
    $medicine_query = "SELECT * FROM medicines WHERE id = $medicine_id AND stock >= $quantity";
    $medicine_result = mysqli_query($conn, $medicine_query);
    
    if (mysqli_num_rows($medicine_result) == 0) {
        echo json_encode(['success' => false, 'message' => 'Medicine not available or insufficient stock']);
        exit();
    }
    
    $medicine = mysqli_fetch_assoc($medicine_result);
    
    // Check if item already in cart
    $check_query = "SELECT * FROM cart WHERE user_id = $user_id AND medicine_id = $medicine_id";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update quantity
        $cart_item = mysqli_fetch_assoc($check_result);
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        if ($new_quantity > $medicine['stock']) {
            echo json_encode(['success' => false, 'message' => 'Cannot add more than available stock']);
            exit();
        }
        
        $update_query = "UPDATE cart SET quantity = $new_quantity WHERE id = {$cart_item['id']}";
        mysqli_query($conn, $update_query);
    } else {
        // Add new item
        $insert_query = "INSERT INTO cart (user_id, medicine_id, quantity) VALUES ($user_id, $medicine_id, $quantity)";
        mysqli_query($conn, $insert_query);
    }
    
    // Get updated cart count
    $count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
    $count_result = mysqli_query($conn, $count_query);
    $count_data = mysqli_fetch_assoc($count_result);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Added to cart successfully',
        'cart_count' => $count_data['total'] ?? 0
    ]);
    exit();
}
?>