<?php
include_once '../config.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_GET['user_id'])) {
        echo json_encode(["status" => "error", "message" => "user_id is required"]);
        exit;
    }
    $user_id = $_GET['user_id'];
    
    $query = "SELECT c.id as wishlist_id, p.id as product_id, p.name, p.price, p.image 
              FROM wishlist c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $wishlist_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($wishlist_items);
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->user_id) && !empty($data->product_id)) {
        // Check if already in wishlist
        $check_query = "SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':user_id', $data->user_id);
        $check_stmt->bindParam(':product_id', $data->product_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(["status" => "success", "message" => "Already in wishlist."]);
        } else {
            // Insert new
            $insert_query = "INSERT INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $data->user_id);
            $insert_stmt->bindParam(':product_id', $data->product_id);
            $insert_stmt->execute();
            
            echo json_encode(["status" => "success", "message" => "Added to wishlist."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
}
elseif ($method === 'DELETE') {
    if (!isset($_GET['wishlist_id'])) {
        echo json_encode(["status" => "error", "message" => "wishlist_id is required"]);
        exit;
    }
    
    $wishlist_id = $_GET['wishlist_id'];
    $query = "DELETE FROM wishlist WHERE id = :wishlist_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':wishlist_id', $wishlist_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Item removed from wishlist."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove item."]);
    }
}
?>
