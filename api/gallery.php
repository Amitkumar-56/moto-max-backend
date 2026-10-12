<?php
require_once '../config.php';

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure the gallery table exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS gallery (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100) NOT NULL,
        image_url VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    // Ignore error if table exists
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch all gallery images
    $stmt = $conn->query("SELECT * FROM gallery ORDER BY id DESC");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert relative URLs to absolute if needed, assuming they are stored relative to frontend or backend
    echo json_encode($images);
    exit;
}

if ($method === 'POST') {
    // Upload a new gallery image
    $title = $_POST['title'] ?? 'Untitled';
    $category = $_POST['category'] ?? 'General';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../my-react-app/public/uploads/gallery/';
        
        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        $targetFilePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imageUrl = '/uploads/gallery/' . $fileName; // Path relative to public folder
            
            $stmt = $conn->prepare("INSERT INTO gallery (title, category, image_url) VALUES (?, ?, ?)");
            if ($stmt->execute([$title, $category, $imageUrl])) {
                echo json_encode(["status" => "success", "message" => "Image uploaded successfully", "image_url" => $imageUrl]);
            } else {
                echo json_encode(["status" => "error", "message" => "Database insertion failed"]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "File upload failed"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No image uploaded or upload error"]);
    }
    exit;
}

if ($method === 'DELETE') {
    // Delete a gallery image
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    
    if ($id) {
        // First get the image URL to delete the file
        $stmt = $conn->prepare("SELECT image_url FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($image) {
            $filePath = '../../my-react-app/public' . $image['image_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Delete from database
            $delStmt = $conn->prepare("DELETE FROM gallery WHERE id = ?");
            if ($delStmt->execute([$id])) {
                echo json_encode(["status" => "success", "message" => "Image deleted successfully"]);
                exit;
            }
        }
    }
    echo json_encode(["status" => "error", "message" => "Failed to delete image"]);
    exit;
}
?>
