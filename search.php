<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_GET['q'])) {
    $search = mysqli_real_escape_string($conn, $_GET['q']);
    
    $query = "SELECT id, name, price, image_url 
              FROM medicines 
              WHERE name LIKE '%$search%' 
              AND stock > 0 
              LIMIT 5";
    
    $result = mysqli_query($conn, $query);
    $medicines = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $row['image'] = getOnlineMedicineImage($row['name']);
        $medicines[] = $row;
    }
    
    echo json_encode($medicines);
}

function getOnlineMedicineImage($medicineName) {
    $imageMap = [
        'Paracetamol' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&100x100',
        'Amoxicillin' => 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?ixlib=rb-1.2.1&auto=format&fit=crop&100x100',
        'Vitamin C' => 'https://images.unsplash.com/photo-1584017911766-451b3d0e8434?ixlib=rb-1.2.1&auto=format&fit=crop&100x100',
        'Ibuprofen' => 'https://images.unsplash.com/photo-1550574697-7d776f405ed2?ixlib=rb-1.2.1&auto=format&fit=crop&100x100',
        'Cough Syrup' => 'https://images.unsplash.com/photo-1628771065518-0d82f1938462?ixlib=rb-1.2.1&auto=format&fit=crop&100x100',
        'Antihistamine' => 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?ixlib=rb-1.2.1&auto=format&fit=crop&100x100'
    ];
    
    return isset($imageMap[$medicineName]) ? $imageMap[$medicineName] : 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?ixlib=rb-1.2.1&auto=format&fit=crop&100x100';
}
?>