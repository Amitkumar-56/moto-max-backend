<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once "../config.php";

$user_id = isset($_GET["user_id"]) ? intval($_GET["user_id"]) : 0;
$action = isset($_GET["action"]) ? $_GET["action"] : "profile";
$method = $_SERVER["REQUEST_METHOD"];

if ($user_id > 0) {
    if ($action == "profile" && $method == "GET") {
        $stmt = $conn->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = :id");
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["status" => "success", "data" => $user]);
        } else {
            echo json_encode(["status" => "error", "message" => "User not found."]);
        }
    } 
    elseif ($action == "update_profile" && $method == "POST") {
        $data = json_decode(file_get_contents("php://input"));
        if (!empty($data->name) && !empty($data->email)) {
            if (!empty($data->password)) {
                $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, password = :password WHERE id = :id");
                $stmt->bindParam(":password", $password_hash);
            } else {
                $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
            }
            $stmt->bindParam(":name", $data->name);
            $stmt->bindParam(":email", $data->email);
            $stmt->bindParam(":id", $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Profile updated successfully.", "user" => ["id" => $user_id, "name" => $data->name, "email" => $data->email]]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update profile."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Incomplete data."]);
        }
    }
    elseif ($action == "inquiries" && $method == "GET") {
        // Get user email first
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = :id");
        $stmt->bindParam(":id", $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $q_stmt = $conn->prepare("SELECT * FROM inquiries WHERE email = :email ORDER BY created_at DESC");
            $q_stmt->bindParam(":email", $user["email"]);
            $q_stmt->execute();
            $inquiries = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["status" => "success", "data" => $inquiries]);
        } else {
            echo json_encode(["status" => "error", "message" => "User not found."]);
        }
    }
    else {
        echo json_encode(["status" => "error", "message" => "Invalid action or method."]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User ID is required."]);
}
?>
