<?php
// api/about.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config.php';

// Create table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS about_settings (
        id INT PRIMARY KEY DEFAULT 1,
        hero_badge VARCHAR(255) DEFAULT '⚡ Pioneering Green Mobility Since 2004',
        hero_title VARCHAR(255) DEFAULT 'Powering a Greener Tomorrow',
        hero_subtitle TEXT,
        hero_image TEXT,
        story_tag VARCHAR(100) DEFAULT 'Our Heritage',
        story_title VARCHAR(255) DEFAULT 'Driving India\'s Electric Revolution',
        story_para1 TEXT,
        story_para2 TEXT,
        story_image TEXT,
        story_badge_number VARCHAR(100) DEFAULT '20+ Years',
        story_badge_text VARCHAR(100) DEFAULT 'Clean Tech Innovation',
        founder_quote TEXT,
        founder_name VARCHAR(255) DEFAULT '— Mr. Samrath Jit Singh, Founder & CEO',
        stat1_number VARCHAR(50) DEFAULT '300,000+',
        stat1_label VARCHAR(255) DEFAULT 'Electric 2-Wheelers Powered',
        stat2_number VARCHAR(50) DEFAULT '250,000+',
        stat2_label VARCHAR(255) DEFAULT 'E-Rickshaws on the Road',
        stat3_number VARCHAR(50) DEFAULT '800+',
        stat3_label VARCHAR(255) DEFAULT 'Dedicated Professionals',
        stat4_number VARCHAR(50) DEFAULT '100%',
        stat4_label VARCHAR(255) DEFAULT 'AIS-156 & BIS Certified',
        infra_tag VARCHAR(100) DEFAULT 'Manufacturing Excellence',
        infra_title VARCHAR(255) DEFAULT 'World-Class Infrastructure & People',
        infra_desc TEXT,
        infra_image TEXT,
        infra_f1_title VARCHAR(255) DEFAULT '800+ Team Strength',
        infra_f1_text TEXT,
        infra_f2_title VARCHAR(255) DEFAULT 'Dedicated R&D Center',
        infra_f2_text TEXT,
        infra_f3_title VARCHAR(255) DEFAULT 'Pan-India Service Network',
        infra_f3_text TEXT,
        cta_title VARCHAR(255) DEFAULT 'Together, Let\'s Drive India\'s Net-Zero Future',
        cta_text TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Check if initial row exists
    $check = $conn->query("SELECT COUNT(*) FROM about_settings WHERE id = 1")->fetchColumn();
    if ($check == 0) {
        $stmt = $conn->prepare("INSERT INTO about_settings (
            id, hero_badge, hero_title, hero_subtitle, hero_image,
            story_tag, story_title, story_para1, story_para2, story_image, story_badge_number, story_badge_text,
            founder_quote, founder_name,
            stat1_number, stat1_label, stat2_number, stat2_label, stat3_number, stat3_label, stat4_number, stat4_label,
            infra_tag, infra_title, infra_desc, infra_image,
            infra_f1_title, infra_f1_text, infra_f2_title, infra_f2_text, infra_f3_title, infra_f3_text,
            cta_title, cta_text
        ) VALUES (
            1,
            '⚡ Pioneering Green Mobility Since 2004',
            'Powering a Greener Tomorrow',
            'India\'s premier manufacturer of high-reliability lithium-ion batteries and advanced EV charging ecosystems.',
            '/assets/slider-3.jpg',
            'Our Heritage',
            'Driving India\'s Electric Revolution',
            'MotoMax EV is one of India\'s earliest pioneers and most trusted names in the energy storage and electric mobility space. Established in 2004, the company has consistently led the transition towards sustainable transportation, emerging as India\'s leading manufacturer of lithium-ion batteries.',
            'From heavy-duty commercial e-rickshaws to next-gen electric two-wheelers, our battery architectures are built from ground up to deliver uncompromising range, thermal safety, and extended lifecycles.',
            '/assets/slider-1.jpg',
            '20+ Years',
            'Clean Tech Innovation',
            'Our vision is to build an energy-independent India powered by smart, clean, and reliable mobility.',
            '— Mr. Samrath Jit Singh, Founder & CEO',
            '300,000+',
            'Electric 2-Wheelers Powered',
            '250,000+',
            'E-Rickshaws on the Road',
            '800+',
            'Dedicated Professionals',
            '100%',
            'AIS-156 & BIS Certified',
            'Manufacturing Excellence',
            'World-Class Infrastructure & People',
            'Our state-of-the-art automated manufacturing plants are backed by dedicated testing laboratories and rapid customer service hubs.',
            '/assets/slider-2.jpg',
            '800+ Team Strength',
            'Expanding to 1,000+ members with our upcoming gigawatt-scale assembly facility.',
            'Dedicated R&D Center',
            'Over 40 elite engineers focusing solely on advanced battery chemistries and power electronics.',
            'Pan-India Service Network',
            '160+ service engineers ensuring on-ground product support, quick turnaround, and OEM confidence.',
            'Together, Let\'s Drive India\'s Net-Zero Future',
            'Partner with MotoMax EV for industry-leading lithium battery solutions, OEM integrations, and nationwide distribution.'
        )");
        $stmt->execute();
    }
} catch (Exception $e) {
    // Continue
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $conn->query("SELECT * FROM about_settings WHERE id = 1 LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo json_encode(["status" => "success", "data" => $row]);
        } else {
            echo json_encode(["status" => "error", "message" => "Settings not found"]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!empty($data)) {
            $fields = [
                'hero_badge', 'hero_title', 'hero_subtitle', 'hero_image',
                'story_tag', 'story_title', 'story_para1', 'story_para2', 'story_image', 'story_badge_number', 'story_badge_text',
                'founder_quote', 'founder_name',
                'stat1_number', 'stat1_label', 'stat2_number', 'stat2_label', 'stat3_number', 'stat3_label', 'stat4_number', 'stat4_label',
                'infra_tag', 'infra_title', 'infra_desc', 'infra_image',
                'infra_f1_title', 'infra_f1_text', 'infra_f2_title', 'infra_f2_text', 'infra_f3_title', 'infra_f3_text',
                'cta_title', 'cta_text'
            ];

            $updates = [];
            $params = [':id' => 1];

            foreach ($fields as $f) {
                if (isset($data[$f])) {
                    $updates[] = "$f = :$f";
                    $params[":$f"] = $data[$f];
                }
            }

            if (!empty($updates)) {
                $sql = "UPDATE about_settings SET " . implode(", ", $updates) . " WHERE id = :id";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute($params)) {
                    echo json_encode(["status" => "success", "message" => "About Us content updated successfully."]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Failed to update About Us content."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "No valid fields provided."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid request payload."]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
