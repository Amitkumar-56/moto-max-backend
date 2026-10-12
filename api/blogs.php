<?php
// api/blogs.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM blogs WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } else {
            $stmt = $conn->query("SELECT * FROM blogs ORDER BY id DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST': // Create Blog (Admin)
        if (!empty($data->title) && !empty($data->content)) {
            $query = "INSERT INTO blogs (title, category, image_url, content) VALUES (:title, :category, :image_url, :content)";
            $stmt = $conn->prepare($query);
            
            $category = isset($data->category) ? $data->category : '';
            $image_url = isset($data->image_url) ? $data->image_url : '';
            
            $stmt->bindParam(":title", $data->title);
            $stmt->bindParam(":category", $category);
            $stmt->bindParam(":image_url", $image_url);
            $stmt->bindParam(":content", $data->content);
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Blog added successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to add blog."]);
            }
        }
        break;

    case 'DELETE': // Delete Blog (Admin)
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM blogs WHERE id = :id");
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Blog deleted."]);
            }
        }
        break;
}
?>
