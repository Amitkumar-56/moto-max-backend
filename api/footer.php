<?php
// api/footer.php
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

// Ensure footer_settings table exists
$conn->exec("CREATE TABLE IF NOT EXISTS footer_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value LONGTEXT
)");

// Default fallback settings
$defaults = [
    'logo_url' => '/Icon.png',
    'company_name' => 'MotoMax EV',
    'address' => "A-53, Naraina Industrial Area Phase-1,\nNaraina, Delhi-110028 (India)",
    'phone' => '+91-11-48224444',
    'email' => 'info@motomaxev.com',
    'website' => 'www.motomaxev.com',
    'cta_title' => "Let's energize\nour spaces with\nclean power",
    'cta_btn_text' => 'ENQUIRE NOW',
    'cta_btn_link' => '/contact-us',
    'facebook_url' => 'https://facebook.com',
    'instagram_url' => 'https://instagram.com',
    'linkedin_url' => 'https://linkedin.com',
    'twitter_url' => 'https://twitter.com',
    'youtube_url' => '',
    'copyright_text' => '© ' . date('Y') . ' MotoMax EV Industries. All Rights Reserved.',
    'useful_links' => json_encode([
        ['title' => 'Home', 'url' => '/'],
        ['title' => 'About Us', 'url' => '/about-us'],
        ['title' => 'Contact', 'url' => '/contact-us'],
        ['title' => 'Management', 'url' => '/team'],
        ['title' => 'News & Events', 'url' => '/news-events'],
        ['title' => 'Blog', 'url' => '/blog'],
        ['title' => 'Gallery', 'url' => '/gallery']
    ]),
    'product_links' => json_encode([
        ['title' => 'Automotive Lithium Battery', 'url' => '/products/electric-scooter-battery'],
        ['title' => 'Inverter Lithium Battery', 'url' => '/products/lithium-inverter-battery-home'],
        ['title' => 'Lithium Battery Solar App.', 'url' => '/products/solar-street-light-storage'],
        ['title' => 'Drone Lithium Battery', 'url' => '/products/solid-state'],
        ['title' => 'EV Charger', 'url' => '/products/charger'],
        ['title' => 'Inverter', 'url' => '/products/inverter']
    ])
];

// Seed defaults if table is empty
$count = $conn->query("SELECT COUNT(*) FROM footer_settings")->fetchColumn();
if ($count == 0) {
    $ins = $conn->prepare("INSERT INTO footer_settings (setting_key, setting_value) VALUES (:k, :v)");
    foreach ($defaults as $k => $v) {
        $ins->execute([':k' => $k, ':v' => $v]);
    }
}

if ($method === 'GET') {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM footer_settings");
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['setting_key'];
        $val = $row['setting_value'];
        if ($key === 'useful_links' || $key === 'product_links') {
            $decoded = json_decode($val, true);
            $data[$key] = $decoded !== null ? $decoded : $val;
        } else {
            $data[$key] = $val;
        }
    }
    // merge with defaults if missing
    foreach ($defaults as $k => $v) {
        if (!isset($data[$k])) {
            $data[$k] = ($k === 'useful_links' || $k === 'product_links') ? json_decode($v, true) : $v;
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

    $stmt = $conn->prepare("INSERT INTO footer_settings (setting_key, setting_value) 
                           VALUES (:k, :v) 
                           ON DUPLICATE KEY UPDATE setting_value = :v2");

    foreach ($body as $key => $val) {
        $saveVal = is_array($val) ? json_encode($val) : (string)$val;
        $stmt->execute([
            ':k' => $key,
            ':v' => $saveVal,
            ':v2' => $saveVal
        ]);
    }

    echo json_encode(["status" => "success", "message" => "Footer settings saved successfully!"]);
    exit();
}

echo json_encode(["status" => "error", "message" => "Method not supported."]);
?>
