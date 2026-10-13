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
            // Public registration is strictly for user role
            $role = 'user';
            
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
elseif ($action == 'admin_login') {
    if (!empty($data->email) && !empty($data->password)) {
        $input = trim($data->email);
        $query = "SELECT id, name, email, password FROM admin WHERE LOWER(email) = LOWER(:email) LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $input);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data->password, $row['password'])) {
                echo json_encode([
                    "status" => "success", 
                    "message" => "Admin login successful.",
                    "user" => [
                        "id" => $row['id'],
                        "name" => $row['name'],
                        "email" => $row['email'],
                        "role" => "admin"
                    ]
                ]);
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid password for admin."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Admin account not found with this email."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Please enter admin email and password."]);
    }
}
elseif ($action == 'login' || $action == 'user_login') {
    if (!empty($data->email) && !empty($data->password)) {
        $input = trim($data->email);

        $query = "SELECT id, name, email, password, role FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":email", $input);
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
            echo json_encode(["status" => "error", "message" => "User account not found with this email."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Please enter email and password."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
}
?>
