<?php
require '../config.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (!isset($_GET['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'user_id required']);
        exit;
    }
    $stmt = $conn->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY id DESC');
    $stmt->bindParam(':user_id', $_GET['user_id']);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'));
    $state = isset($data->state) ? $data->state : 'N/A';
    
    $stmt = $conn->prepare('INSERT INTO user_addresses (user_id, address_line, city, state, zip, phone) VALUES (:user_id, :address_line, :city, :state, :zip, :phone)');
    $stmt->bindParam(':user_id', $data->user_id);
    $stmt->bindParam(':address_line', $data->address);
    $stmt->bindParam(':city', $data->city);
    $stmt->bindParam(':state', $state);
    $stmt->bindParam(':zip', $data->pincode);
    $stmt->bindParam(':phone', $data->phone);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'id' => $conn->lastInsertId()]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->errorInfo()]);
    }
}
?>
