<?php
// api/auth.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config.php';

$data = json_decode(file_get_contents("php://input"));
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'register') {
    if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
        // Check if email exists
        $check_query = "SELECT id FROM users WHERE email = :email";
        $stmt = $conn->prepare($check_query);
        $stmt->bindParam(':email', $data->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(["status" => "error", "message" => "Email already exists."]);
        } else {
            $query = "INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)";
            $stmt = $conn->prepare($query);
            
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            $role = isset($data->role) && $data->role == 'admin' ? 'admin' : 'user';
            
            $stmt->bindParam(":name", $data->name);
            $stmt->bindParam(":email", $data->email);
            $stmt->bindParam(":password", $password_hash);
            $stmt->bindParam(":role", $role);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "User registered successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to register user."]);
            }
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
} 
elseif ($action == 'login') {
    if (!empty($data->email) && !empty($data->password)) {
        $query = "SELECT id, name, email, password, role FROM users WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $data->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data->password, $row['password'])) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Login successful.",
                    "user" => [
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "email" => $row['email'],
                        "role" => $row['role']
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid password."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "User not found."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
}
?>
