<?php
include_once '../config.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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
    
    $query = "SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($cart_items);
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->user_id) && !empty($data->product_id)) {
        // Check if already in cart
        $check_query = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND product_id = :product_id";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bindParam(':user_id', $data->user_id);
        $check_stmt->bindParam(':product_id', $data->product_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update quantity
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $new_qty = $row['quantity'] + 1;
            
            $update_query = "UPDATE cart SET quantity = :qty WHERE id = :cart_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':qty', $new_qty);
            $update_stmt->bindParam(':cart_id', $row['id']);
            $update_stmt->execute();
            
            echo json_encode(["status" => "success", "message" => "Cart updated."]);
        } else {
            // Insert new
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $data->user_id);
            $insert_stmt->bindParam(':product_id', $data->product_id);
            $insert_stmt->execute();
            
            echo json_encode(["status" => "success", "message" => "Added to cart."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
}
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));
    $cart_id = isset($_GET['cart_id']) ? $_GET['cart_id'] : (isset($data->id) ? $data->id : null);
    
    if (!$cart_id) {
        echo json_encode(["status" => "error", "message" => "cart_id is required"]);
        exit;
    }
    
    $query = "DELETE FROM cart WHERE id = :cart_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cart_id', $cart_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Item removed from cart."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove item."]);
    }
}
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (isset($data->cart_id) && isset($data->quantity)) {
        if ($data->quantity <= 0) {
            // Delete if 0 or less
            $query = "DELETE FROM cart WHERE id = :cart_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':cart_id', $data->cart_id);
            $stmt->execute();
            echo json_encode(["status" => "success", "message" => "Item removed."]);
        } else {
            // Update quantity
            $query = "UPDATE cart SET quantity = :qty WHERE id = :cart_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':qty', $data->quantity);
            $stmt->bindParam(':cart_id', $data->cart_id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Quantity updated."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update."]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "cart_id and quantity required"]);
    }
}
?>
