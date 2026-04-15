<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0, 'total' => 0]);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT SUM(quantity) as count, SUM(m.price * c.quantity) as total 
          FROM cart c 
          JOIN medicines m ON c.medicine_id = m.id 
          WHERE c.user_id = $user_id";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

echo json_encode([
    'count' => (int)($data['count'] ?? 0),
    'total' => (float)($data['total'] ?? 0)
]);
?>