<?php
require_once '../config.php';

if (!isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Add Medicine
    if (isset($_POST['add_medicine'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $category = sanitize($_POST['category']);
        $image_url = sanitize($_POST['image_url']);
        $discount = intval($_POST['discount'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $query = "INSERT INTO medicines (name, description, price, stock, category, image_url, discount, featured) 
                  VALUES ('$name', '$description', $price, $stock, '$category', '$image_url', $discount, $featured)";
        
        if (mysqli_query($conn, $query)) {
            $message = "Medicine added successfully!";
            logActivity("Added medicine: $name");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
    
    // Update Medicine
    elseif (isset($_POST['update_medicine'])) {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $category = sanitize($_POST['category']);
        $image_url = sanitize($_POST['image_url']);
        $discount = intval($_POST['discount'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $query = "UPDATE medicines SET 
                  name='$name', description='$description', price=$price, 
                  stock=$stock, category='$category', image_url='$image_url',
                  discount=$discount, featured=$featured
                  WHERE id=$id";
        
        if (mysqli_query($conn, $query)) {
            $message = "Medicine updated successfully!";
            logActivity("Updated medicine: $name");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
    
    // Delete Medicine
    elseif (isset($_POST['delete_medicine'])) {
        $id = intval($_POST['id']);
        
        // Get name for logging
        $name_query = "SELECT name FROM medicines WHERE id = $id";
        $name_result = mysqli_query($conn, $name_query);
        $medicine = mysqli_fetch_assoc($name_result);
        
        $query = "DELETE FROM medicines WHERE id = $id";
        
        if (mysqli_query($conn, $query)) {
            $message = "Medicine deleted successfully!";
            logActivity("Deleted medicine: " . $medicine['name']);
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
    
    // Bulk Actions
    elseif (isset($_POST['bulk_action'])) {
        $action = $_POST['bulk_action'];
        $ids = $_POST['medicine_ids'] ?? [];
        
        if (!empty($ids)) {
            $ids_string = implode(',', array_map('intval', $ids));
            
            if ($action == 'delete') {
                $query = "DELETE FROM medicines WHERE id IN ($ids_string)";
                logActivity("Bulk deleted " . count($ids) . " medicines");
            } elseif ($action == 'featured') {
                $query = "UPDATE medicines SET featured = 1 WHERE id IN ($ids_string)";
                logActivity("Bulk featured " . count($ids) . " medicines");
            } elseif ($action == 'unfeatured') {
                $query = "UPDATE medicines SET featured = 0 WHERE id IN ($ids_string)";
            }
            
            if (mysqli_query($conn, $query)) {
                $message = "Bulk action completed successfully!";
            }
        }
    }
}

// Get medicine for editing
$edit_medicine = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_query = "SELECT * FROM medicines WHERE id = $id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_medicine = mysqli_fetch_assoc($edit_result);
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search and filter
$where = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = sanitize($_GET['search']);
    $where[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
}

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = sanitize($_GET['category']);
    $where[] = "category = '$category'";
}

if (isset($_GET['stock']) && $_GET['stock'] == 'low') {
    $where[] = "stock < 10";
}

if (isset($_GET['stock']) && $_GET['stock'] == 'out') {
    $where[] = "stock = 0";
}

$where_clause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

// Get total count
$count_query = "SELECT COUNT(*) as total FROM medicines $where_clause";
$count_result = mysqli_query($conn, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $limit);

// Get medicines
$query = "SELECT * FROM medicines $where_clause ORDER BY id DESC LIMIT $offset, $limit";
$result = mysqli_query($conn, $query);

// Get categories for filter
$categories_query = "SELECT DISTINCT category FROM medicines ORDER BY category";
$categories = mysqli_query($conn, $categories_query);
?>

<!-- HTML for manage medicines page -->
<!-- ... (I'll provide this in a separate response due to length) ... -->