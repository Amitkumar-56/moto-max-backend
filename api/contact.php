<?php
// api/contact.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

// Ensure contact_settings table exists
$conn->exec("CREATE TABLE IF NOT EXISTS contact_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value LONGTEXT
)");

// Default fallback settings
$defaults = [
    'hero_badge' => 'Get In Touch',
    'hero_title' => 'Contact MotoMax EV',
    'hero_subtitle' => "We're here to help and answer any questions you might have. We look forward to hearing from you.",
    'office_company' => 'MotoMax EV Limited',
    'office_address' => "A-53, Naraina Industrial Area\nPhase-1, Naraina, Delhi-110028",
    'office_cin' => 'CIN: U27101DL2006PLC154820',
    'phone_primary' => '+91-11-48224444',
    'phone_secondary' => '+91-11-48022444',
    'working_hours' => 'Mon - Fri: 9:00 AM - 6:00 PM',
    'email_primary' => 'info@motomaxev.com',
    'email_secondary' => 'support@motomaxev.com',
    'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3501.9960251783063!2d77.1390978761159!3d28.62988118420074!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390d032e367856b3%3A0xc3b832b85a30a109!2sNaraina%20Industrial%20Area%20Phase%201%2C%20Naraina%2C%20New%20Delhi%2C%20Delhi%20110028!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin'
];

// Seed defaults if empty
$count = $conn->query("SELECT COUNT(*) FROM contact_settings")->fetchColumn();
if ($count == 0) {
    $ins = $conn->prepare("INSERT INTO contact_settings (setting_key, setting_value) VALUES (:k, :v)");
    foreach ($defaults as $k => $v) {
        $ins->execute([':k' => $k, ':v' => $v]);
    }
}

if ($method === 'GET') {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM contact_settings");
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[$row['setting_key']] = $row['setting_value'];
    }
    foreach ($defaults as $k => $v) {
        if (!isset($data[$k])) {
            $data[$k] = $v;
        }
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit();
}

if ($method === 'POST') {
    $raw = file_get_contents("php://input");
    $body = json_decode($raw, true);

    if (empty($body)) {
        echo json_encode(["status" => "error", "message" => "No data supplied."]);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO contact_settings (setting_key, setting_value) 
                           VALUES (:k, :v) 
                           ON DUPLICATE KEY UPDATE setting_value = :v2");

    foreach ($body as $key => $val) {
        $strVal = (string)$val;
        $stmt->execute([
            ':k' => $key,
            ':v' => $strVal,
            ':v2' => $strVal
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Contact Us page settings updated successfully!"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Method not supported."]);
?>
