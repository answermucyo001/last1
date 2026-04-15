<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

if (isset($_GET['id'])) {
    $order_id = mysqli_real_escape_string($conn, $_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT o.*, m.name as medicine_name,
              CASE 
                WHEN o.status = 'pending' THEN 'Pending Payment'
                WHEN o.status = 'processing' THEN 'Processing'
                WHEN o.status = 'shipped' THEN 'Shipped'
                WHEN o.status = 'delivered' THEN 'Delivered'
                WHEN o.status = 'completed' THEN 'Completed'
                WHEN o.status = 'cancelled' THEN 'Cancelled'
              END as status_text
              FROM orders o 
              JOIN medicines m ON o.medicine_id = m.id 
              WHERE o.id = $order_id AND o.user_id = $user_id";
    
    $result = mysqli_query($conn, $query);
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Order not found']);
    }
}
?>