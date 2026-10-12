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
    if (isset($_GET['admin']) && $_GET['admin'] === 'true') {
        $query = "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    } else {
        if (!isset($_GET['user_id'])) {
            echo json_encode(["status" => "error", "message" => "user_id is required"]);
            exit;
        }
        $user_id = $_GET['user_id'];
        $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get items for each order
    foreach ($orders as &$order) {
        $item_query = "SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id";
        $item_stmt = $conn->prepare($item_query);
        $item_stmt->bindParam(':order_id', $order['id']);
        $item_stmt->execute();
        $order['items'] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($orders);
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->user_id) && !empty($data->total_amount)) {
        try {
            $conn->beginTransaction();
            
            // 1. Create order
            $payment_method = isset($data->payment_method) ? $data->payment_method : 'COD';
            $cust_name = isset($data->address->name) ? $data->address->name : '';
            $cust_phone = isset($data->address->phone) ? $data->address->phone : '';
            $ship_address = isset($data->address->address) ? $data->address->address . ', ' . $data->address->city . ' - ' . $data->address->pincode : '';

            $order_query = "INSERT INTO orders (user_id, total_amount, payment_method, order_status, customer_name, customer_phone, shipping_address) VALUES (:user_id, :total_amount, :payment_method, 'Processing', :customer_name, :customer_phone, :shipping_address)";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bindParam(':user_id', $data->user_id);
            $order_stmt->bindParam(':total_amount', $data->total_amount);
            $order_stmt->bindParam(':payment_method', $payment_method);
            $order_stmt->bindParam(':customer_name', $cust_name);
            $order_stmt->bindParam(':customer_phone', $cust_phone);
            $order_stmt->bindParam(':shipping_address', $ship_address);
            $order_stmt->execute();
            
            $order_id = $conn->lastInsertId();
            
            // 2. Move cart items to order_items
            $cart_query = "SELECT c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id";
            $cart_stmt = $conn->prepare($cart_query);
            $cart_stmt->bindParam(':user_id', $data->user_id);
            $cart_stmt->execute();
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($cart_items as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
                $item_stmt = $conn->prepare($item_query);
                $item_stmt->bindParam(':order_id', $order_id);
                $item_stmt->bindParam(':product_id', $item['product_id']);
                $item_stmt->bindParam(':quantity', $item['quantity']);
                $item_stmt->bindParam(':price', $item['price']);
                $item_stmt->execute();
            }
            
            // 3. Clear Cart
            $clear_query = "DELETE FROM cart WHERE user_id = :user_id";
            $clear_stmt = $conn->prepare($clear_query);
            $clear_stmt->bindParam(':user_id', $data->user_id);
            $clear_stmt->execute();
            
            $conn->commit();
            
            echo json_encode(["status" => "success", "message" => "Order placed successfully!"]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(["status" => "error", "message" => "Failed to place order."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
}
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    if (isset($data->order_id) && isset($data->order_status)) {
        $query = "UPDATE orders SET order_status = :order_status WHERE id = :order_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':order_status', $data->order_status);
        $stmt->bindParam(':order_id', $data->order_id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Order updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update"]);
        }
    }
}
?>
