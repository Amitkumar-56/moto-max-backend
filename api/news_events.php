<?php
// api/news_events.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config.php';

// Auto-create tables if they don't exist
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS news_articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(255) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(100) DEFAULT 'Press & Media',
        source VARCHAR(100) DEFAULT 'MotoMax EV',
        date_text VARCHAR(50) DEFAULT '',
        read_time VARCHAR(50) DEFAULT '3 min read',
        image TEXT,
        tags VARCHAR(255) DEFAULT '',
        summary TEXT,
        content LONGTEXT,
        is_featured TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->exec("CREATE TABLE IF NOT EXISTS upcoming_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        date_text VARCHAR(100) NOT NULL,
        location VARCHAR(255) NOT NULL,
        booth VARCHAR(100) DEFAULT '',
        badge VARCHAR(100) DEFAULT 'Exhibitor',
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default news if empty
    $checkNews = $conn->query("SELECT COUNT(*) FROM news_articles")->fetchColumn();
    if ($checkNews == 0) {
        $seedNews = [
            [
                'slug' => 'featured-in-battery-magazine',
                'title' => 'Featured in The Battery Magazine',
                'category' => 'Press & Media',
                'source' => 'The Battery Magazine',
                'date_text' => '15 Sep 2026',
                'read_time' => '4 min read',
                'image' => 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&q=80&w=1200',
                'tags' => '#EVTech, #BatteryMagazine, #CleanEnergy',
                'summary' => 'We are thrilled to be spotlighted in the September 2026 edition of The Battery Magazine, documenting MotoMax EV\'s transformative journey from localized battery assembly to pioneering global-grade smart thermal management systems.',
                'content' => "MotoMax EV's trailblazing approach to battery thermal management and indigenous cell balancing has earned nationwide recognition. In an in-depth 6-page feature, The Battery Magazine explores how our advanced R&D laboratory engineered smart BMS architectures capable of enduring India's extreme ambient temperatures from 0°C to 52°C.\n\n\"Our goal was never just to build battery packs, but to formulate an ecosystem of infallible energy storage that instills uncompromising confidence in commercial EV fleet operators and personal mobility riders alike,\" stated our engineering director.\n\nThe feature delves deep into our automated micro-welding lines, real-time IoT battery monitoring modules, and zero-degradation safety validation protocols.",
                'is_featured' => 1
            ],
            [
                'slug' => 'future-proofing-india',
                'title' => "Future-Proofing India's Clean Mobility Ecosystem",
                'category' => 'Press & Media',
                'source' => 'EV Tech News',
                'date_text' => '28 Aug 2026',
                'read_time' => '3 min read',
                'image' => 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&q=80&w=800',
                'tags' => '#Mobility, #GreenIndia, #Sustainability',
                'summary' => 'Featured on EV Tech News exploring our strategic focus on in-house engineering, rapid prototyping, and high-efficiency lithium iron phosphate (LFP) formulations tailored for heavy-duty fleet operations.',
                'content' => "In an exclusive investigative report, EV Tech News evaluated how modular pack architectures are dismantling the adoption barriers for last-mile commercial logistics. MotoMax EV's high-cycle LFP formulations yield over 3,000 charge cycles while maintaining 85%+ state-of-health, slashing the total cost of ownership (TCO) by over 42% compared to conventional combustion powertrains.",
                'is_featured' => 0
            ],
            [
                'slug' => 'pioneering-next-frontier',
                'title' => 'Pioneering the Next Frontier in Fleet Electrification',
                'category' => 'Company Milestones',
                'source' => 'Motorindia Official',
                'date_text' => '10 Jul 2026',
                'read_time' => '5 min read',
                'image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?auto=format&fit=crop&q=80&w=800',
                'tags' => '#FleetElectrification, #Motorindia, #Innovation',
                'summary' => 'Motorindia covers our transformation of the landscape from commercial e-rickshaws to heavy-payload industrial delivery trucks and high-speed two-wheeler battery swappable packs.',
                'content' => "Commercial fleets operate on razor-thin uptime margins. MotoMax EV announced its turnkey fleet telemetry and hot-swappable battery ecosystem across 12 tier-1 transit corridors. Motorindia's cover story examines how automated diagnostic reporting alerts fleet managers before cell imbalances can cause downtime.",
                'is_featured' => 0
            ],
            [
                'slug' => 'solid-state-launch',
                'title' => 'Launch of Next-Gen Solid State & Drone Batteries',
                'category' => 'Product Launches',
                'source' => 'TechRadar Automotive',
                'date_text' => '05 Jun 2026',
                'read_time' => '4 min read',
                'image' => 'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?auto=format&fit=crop&q=80&w=800',
                'tags' => '#SolidState, #DronePower, #NextGen',
                'summary' => 'Announcing our breakthrough ultra-high energy density solid-state prototype and ultra-lightweight high-discharge drone battery packs for agricultural & commercial aerial logistics.',
                'content' => "MotoMax EV officially took the wraps off its high-energy density solid-state cell packaging prototype at the National Tech Innovation Summit. Offering 380 Wh/kg and virtually non-flammable solid electrolyte chemistry, this technology marks a pivotal leap toward aerospace and high-altitude surveillance operations.",
                'is_featured' => 0
            ],
            [
                'slug' => 'smart-bms-4-unveiled',
                'title' => 'Smart BMS 4.0: AI-Driven Predictive Health Unveiled',
                'category' => 'Product Launches',
                'source' => 'Clean Energy Review',
                'date_text' => '18 May 2026',
                'read_time' => '3 min read',
                'image' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&q=80&w=800',
                'tags' => '#SmartBMS, #ArtificialIntelligence, #BatterySafety',
                'summary' => 'Introducing Smart BMS 4.0 integrated with on-chip machine learning models that forecast thermal anomalies up to 45 minutes before critical thresholds.',
                'content' => "Safety is non-negotiable. With our newly patented Smart BMS 4.0, every individual battery pack runs neural-edge anomaly detection algorithms directly on the microcontroller board. The module syncs with cloud dashboards via 4G/LTE telemetry, providing riders and fleet dispatchers with real-time health scores.",
                'is_featured' => 0
            ],
            [
                'slug' => 'pan-india-expansion',
                'title' => 'MotoMax EV Expands Mega Manufacturing to 50,000 Units/Month',
                'category' => 'Company Milestones',
                'source' => 'Economic Times Energy',
                'date_text' => '02 Apr 2026',
                'read_time' => '4 min read',
                'image' => 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&q=80&w=800',
                'tags' => '#MakeInIndia, #Manufacturing, #Scale',
                'summary' => 'Commissioning our second smart gigafactory equipped with robotic ultrasonic welding cells, dual-stage automated testing corridors, and 100% solar-powered assembly lines.',
                'content' => "To satisfy surging pan-India demand, MotoMax EV has officially commissioned Phase II of its mega facility. Spanning over 80,000 sq.ft., the zero-carbon green manufacturing hub elevates total monthly pack throughput to 50,000 units while reducing manufacturing emissions by 60%.",
                'is_featured' => 0
            ]
        ];

        $insStmt = $conn->prepare("INSERT INTO news_articles (slug, title, category, source, date_text, read_time, image, tags, summary, content, is_featured) 
            VALUES (:slug, :title, :category, :source, :date_text, :read_time, :image, :tags, :summary, :content, :is_featured)");
        foreach ($seedNews as $n) {
            $insStmt->execute($n);
        }
    }

    // Seed default events if empty
    $checkEvents = $conn->query("SELECT COUNT(*) FROM upcoming_events")->fetchColumn();
    if ($checkEvents == 0) {
        $seedEvents = [
            [
                'title' => 'EV India Expo 2026',
                'date_text' => '18 - 20 Nov 2026',
                'location' => 'Pragati Maidan, New Delhi',
                'booth' => 'Hall 5, Booth #A-42',
                'badge' => 'Official Exhibitor',
                'description' => 'Experience live demonstrations of our smart swappable batteries, heavy commercial battery packs, and next-gen BMS telematics.'
            ],
            [
                'title' => 'Global Clean Energy Summit 2026',
                'date_text' => '05 - 07 Dec 2026',
                'location' => 'HITEX Exhibition Centre, Hyderabad',
                'booth' => 'Keynote Hall B',
                'badge' => 'Keynote Speaker',
                'description' => "Our leadership team delivers the keynote address on 'Next-Decade Battery Chemistries & India\'s Path to 100% Zero-Emission Fleets'."
            ],
            [
                'title' => 'Renewable & Battery World Expo 2027',
                'date_text' => '12 - 14 Jan 2027',
                'location' => 'BIEC, Bengaluru',
                'booth' => 'Pavilion 3, Space #110',
                'badge' => 'Product Showcase',
                'description' => 'Unveiling our ultra-fast charging stationary solar energy storage systems and agricultural drone power packs.'
            ]
        ];

        $insEvt = $conn->prepare("INSERT INTO upcoming_events (title, date_text, location, booth, badge, description) 
            VALUES (:title, :date_text, :location, :booth, :badge, :description)");
        foreach ($seedEvents as $e) {
            $insEvt->execute($e);
        }
    }
} catch (Exception $e) {
    // Continue even if table creation has minor issues
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        // Check single article by slug or id
        if (isset($_GET['slug'])) {
            $stmt = $conn->prepare("SELECT * FROM news_articles WHERE slug = :slug LIMIT 1");
            $stmt->execute([':slug' => $_GET['slug']]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($item) {
                echo json_encode(["status" => "success", "data" => $item]);
            } else {
                echo json_encode(["status" => "error", "message" => "Article not found"]);
            }
            exit();
        }

        if (isset($_GET['id']) && (!isset($_GET['type']) || $_GET['type'] === 'news')) {
            $stmt = $conn->prepare("SELECT * FROM news_articles WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => intval($_GET['id'])]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(["status" => "success", "data" => $item]);
            exit();
        }

        // Return all news and upcoming events
        $newsStmt = $conn->query("SELECT * FROM news_articles ORDER BY is_featured DESC, id DESC");
        $newsList = $newsStmt->fetchAll(PDO::FETCH_ASSOC);

        $eventsStmt = $conn->query("SELECT * FROM upcoming_events ORDER BY id ASC");
        $eventsList = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "news" => $newsList,
            "events" => $eventsList
        ]);
        break;

    case 'POST':
        $action = isset($data['action']) ? $data['action'] : (isset($_GET['action']) ? $_GET['action'] : 'create_news');
        $type = isset($data['type']) ? $data['type'] : 'news';

        if ($type === 'event' || $action === 'create_event' || $action === 'update_event') {
            // Event create / update
            if ($action === 'update_event' && !empty($data['id'])) {
                $stmt = $conn->prepare("UPDATE upcoming_events SET 
                    title = :title, 
                    date_text = :date_text, 
                    location = :location, 
                    booth = :booth, 
                    badge = :badge, 
                    description = :description 
                    WHERE id = :id");
                $stmt->execute([
                    ':title' => $data['title'] ?? '',
                    ':date_text' => $data['date_text'] ?? '',
                    ':location' => $data['location'] ?? '',
                    ':booth' => $data['booth'] ?? '',
                    ':badge' => $data['badge'] ?? 'Exhibitor',
                    ':description' => $data['description'] ?? '',
                    ':id' => intval($data['id'])
                ]);
                echo json_encode(["status" => "success", "message" => "Event updated successfully."]);
            } else {
                if (empty($data['title'])) {
                    echo json_encode(["status" => "error", "message" => "Event title is required."]);
                    exit();
                }
                $stmt = $conn->prepare("INSERT INTO upcoming_events (title, date_text, location, booth, badge, description) 
                    VALUES (:title, :date_text, :location, :booth, :badge, :description)");
                $stmt->execute([
                    ':title' => $data['title'] ?? '',
                    ':date_text' => $data['date_text'] ?? '',
                    ':location' => $data['location'] ?? '',
                    ':booth' => $data['booth'] ?? '',
                    ':badge' => $data['badge'] ?? 'Exhibitor',
                    ':description' => $data['description'] ?? ''
                ]);
                echo json_encode(["status" => "success", "message" => "Event added successfully."]);
            }
        } else {
            // News create / update
            $title = $data['title'] ?? '';
            if (empty($title)) {
                echo json_encode(["status" => "error", "message" => "Article title is required."]);
                exit();
            }

            // Generate slug if empty
            $slug = !empty($data['slug']) ? trim($data['slug']) : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

            if ($action === 'update_news' && !empty($data['id'])) {
                if (!empty($data['is_featured'])) {
                    $conn->query("UPDATE news_articles SET is_featured = 0");
                }
                $stmt = $conn->prepare("UPDATE news_articles SET 
                    slug = :slug,
                    title = :title,
                    category = :category,
                    source = :source,
                    date_text = :date_text,
                    read_time = :read_time,
                    image = :image,
                    tags = :tags,
                    summary = :summary,
                    content = :content,
                    is_featured = :is_featured
                    WHERE id = :id");
                $stmt->execute([
                    ':slug' => $slug,
                    ':title' => $title,
                    ':category' => $data['category'] ?? 'Press & Media',
                    ':source' => $data['source'] ?? 'MotoMax EV',
                    ':date_text' => $data['date_text'] ?? date('d M Y'),
                    ':read_time' => $data['read_time'] ?? '3 min read',
                    ':image' => $data['image'] ?? 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&q=80&w=800',
                    ':tags' => $data['tags'] ?? '',
                    ':summary' => $data['summary'] ?? '',
                    ':content' => $data['content'] ?? '',
                    ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
                    ':id' => intval($data['id'])
                ]);
                echo json_encode(["status" => "success", "message" => "News article updated successfully."]);
            } else {
                if (!empty($data['is_featured'])) {
                    $conn->query("UPDATE news_articles SET is_featured = 0");
                }
                // Ensure slug uniqueness
                $checkSlug = $conn->prepare("SELECT id FROM news_articles WHERE slug = :slug");
                $checkSlug->execute([':slug' => $slug]);
                if ($checkSlug->fetch()) {
                    $slug .= '-' . time();
                }

                $stmt = $conn->prepare("INSERT INTO news_articles 
                    (slug, title, category, source, date_text, read_time, image, tags, summary, content, is_featured) 
                    VALUES (:slug, :title, :category, :source, :date_text, :read_time, :image, :tags, :summary, :content, :is_featured)");
                $stmt->execute([
                    ':slug' => $slug,
                    ':title' => $title,
                    ':category' => $data['category'] ?? 'Press & Media',
                    ':source' => $data['source'] ?? 'MotoMax EV',
                    ':date_text' => $data['date_text'] ?? date('d M Y'),
                    ':read_time' => $data['read_time'] ?? '3 min read',
                    ':image' => $data['image'] ?? 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&q=80&w=800',
                    ':tags' => $data['tags'] ?? '',
                    ':summary' => $data['summary'] ?? '',
                    ':content' => $data['content'] ?? '',
                    ':is_featured' => !empty($data['is_featured']) ? 1 : 0
                ]);
                echo json_encode(["status" => "success", "message" => "News article published successfully."]);
            }
        }
        break;

    case 'DELETE':
        $type = isset($_GET['type']) ? $_GET['type'] : 'news';
        $id = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['id']) ? intval($data['id']) : null);

        if (!$id) {
            echo json_encode(["status" => "error", "message" => "ID is required for deletion."]);
            exit();
        }

        if ($type === 'event') {
            $stmt = $conn->prepare("DELETE FROM upcoming_events WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(["status" => "success", "message" => "Event deleted successfully."]);
        } else {
            $stmt = $conn->prepare("DELETE FROM news_articles WHERE id = :id");
            $stmt->execute([':id' => $id]);
            echo json_encode(["status" => "success", "message" => "News article deleted successfully."]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
?>
