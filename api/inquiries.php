<?php
// api/inquiries.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));

switch ($method) {
    case 'GET': // Admin views all inquiries
        $stmt = $conn->query("SELECT * FROM inquiries ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'POST': // User/Guest submits an inquiry
        if (!empty($data->name) && !empty($data->email) && !empty($data->message)) {
            $query = "INSERT INTO inquiries (name, email, message, status) VALUES (:name, :email, :message, 'pending')";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(":name", $data->name);
            $stmt->bindParam(":email", $data->email);
            $stmt->bindParam(":message", $data->message);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Inquiry sent successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to send inquiry."]);
            }
        }
        break;
        
    case 'PUT': // Admin updates status (e.g. to resolved)
        if (isset($_GET['id']) && isset($data->status)) {
            $id = $_GET['id'];
            $stmt = $conn->prepare("UPDATE inquiries SET status = :status WHERE id = :id");
            $stmt->bindParam(":status", $data->status);
            $stmt->bindParam(":id", $id);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Inquiry status updated."]);
            }
        }
        break;
}
?>
