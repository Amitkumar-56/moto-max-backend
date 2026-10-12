<?php
// api/products.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"));
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($method) {
    case 'GET':
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        } elseif ($category) {
            $stmt = $conn->prepare("SELECT * FROM products WHERE category = :category ORDER BY id DESC");
            $stmt->bindParam(":category", $category);
            $stmt->execute();
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } else {
            $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        break;

    case 'POST': // Create Product (Admin)
        $name = isset($_POST['name']) ? $_POST['name'] : (isset($data->name) ? $data->name : '');
        $price = isset($_POST['price']) ? $_POST['price'] : (isset($data->price) ? $data->price : '');
        
        if (!empty($name) && !empty($price)) {
            $range = isset($_POST['range']) ? $_POST['range'] : (isset($data->range) ? $data->range : '');
            $description = isset($_POST['description']) ? $_POST['description'] : (isset($data->description) ? $data->description : '');
            $key_points = isset($_POST['key_points']) ? $_POST['key_points'] : (isset($data->key_points) ? $data->key_points : '');
            $category = isset($_POST['category']) && !empty($_POST['category']) ? $_POST['category'] : (isset($data->category) ? $data->category : 'General');
            
            $image = isset($_POST['image']) ? $_POST['image'] : (isset($data->image) ? $data->image : '');
            $video = '';

            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../uploads/";
                if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
                
                $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
                $new_filename = uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image = 'http://localhost/assinment/php-backend/uploads/' . $new_filename;
                }
            }

            $product_id = isset($_POST['id']) ? $_POST['id'] : (isset($data->id) ? $data->id : null);

            if ($product_id) {
                if (!empty($image)) {
                    $query = "UPDATE products SET name=:name, price=:price, `range`=:range, image=:image, description=:description, key_points=:key_points, category=:category WHERE id=:id";
                } else {
                    $query = "UPDATE products SET name=:name, price=:price, `range`=:range, description=:description, key_points=:key_points, category=:category WHERE id=:id";
                }
                $stmt = $conn->prepare($query);
                $stmt->bindParam(":id", $product_id);
            } else {
                $query = "INSERT INTO products (name, price, `range`, image, video, description, key_points, category) VALUES (:name, :price, :range, :image, :video, :description, :key_points, :category)";
                $stmt = $conn->prepare($query);
                if (empty($image)) { $image = ''; }
                $stmt->bindParam(":image", $image);
                $stmt->bindParam(":video", $video);
            }
            
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":price", $price);
            $stmt->bindParam(":range", $range);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":key_points", $key_points);
            $stmt->bindParam(":category", $category);
            
            if ($product_id && !empty($image)) {
                $stmt->bindParam(":image", $image);
            }
            
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => $product_id ? "Product updated successfully." : "Product added successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => $product_id ? "Failed to update product." : "Failed to add product."]);
            }
        }
        break;

    case 'DELETE': // Delete Product (Admin)
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id = :id");
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Product deleted."]);
            }
        }
        break;

    case 'PUT':
        if ($id && isset($data->price)) {
            $stmt = $conn->prepare("UPDATE products SET price = :price WHERE id = :id");
            $stmt->bindParam(":price", $data->price);
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Price updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update price."]);
            }
        }
        break;
}
?>
