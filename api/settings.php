<?php
// api/settings.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Create table if not exists
$conn->exec("CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT
)");

if ($method === 'GET') {
    $stmt = $conn->query("SELECT * FROM settings");
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[$row['setting_key']] = $row['setting_value'];
    }
    echo json_encode(["status" => "success", "data" => $result]);
} 
elseif ($method === 'POST') {
    // Admin saves settings
    if (!empty($data)) {
        foreach ($data as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2");
            $stmt->bindParam(":k", $key);
            $stmt->bindParam(":v", $value);
            $stmt->bindParam(":v2", $value);
            $stmt->execute();
        }
        echo json_encode(["status" => "success", "message" => "Settings updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "No data provided."]);
    }
}
?>
