<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once "../config.php";

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    $stmt = $conn->query("SELECT id, name, email FROM users WHERE role = 'admin' LIMIT 1");
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
} elseif ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->name) && !empty($data->email)) {
        if (!empty($data->password)) {
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, password = :password WHERE role = 'admin'");
            $stmt->bindParam(":password", $password_hash);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE role = 'admin'");
        }
        $stmt->bindParam(":name", $data->name);
        $stmt->bindParam(":email", $data->email);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Profile updated successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update profile."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Name and email are required."]);
    }
}
?>
