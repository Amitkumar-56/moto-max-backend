<?php
// api/dealership.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Create table if not exists
$conn->exec("CREATE TABLE IF NOT EXISTS dealership_inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_name VARCHAR(255) DEFAULT '',
    owner_name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(150) DEFAULT '',
    gst_registered VARCHAR(10) DEFAULT 'No',
    gst_number VARCHAR(50) DEFAULT '',
    state VARCHAR(100) DEFAULT '',
    city VARCHAR(100) DEFAULT '',
    investment_capacity VARCHAR(100) DEFAULT '',
    interested_vertical VARCHAR(100) DEFAULT 'Both',
    showroom_area VARCHAR(100) DEFAULT '',
    existing_business VARCHAR(255) DEFAULT '',
    message LONGTEXT,
    status VARCHAR(50) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
@$conn->exec("ALTER TABLE dealership_inquiries MODIFY email VARCHAR(150) DEFAULT ''");

switch ($method) {
    case 'GET':
        // Admin fetches all dealership inquiries
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $search = isset($_GET['search']) ? $_GET['search'] : '';

        $sql = "SELECT * FROM dealership_inquiries WHERE 1=1";
        $params = [];

        if (!empty($status_filter) && $status_filter !== 'all') {
            $sql .= " AND status = :status";
            $params[':status'] = $status_filter;
        }

        if (!empty($search)) {
            $sql .= " AND (business_name LIKE :s OR owner_name LIKE :s2 OR phone LIKE :s3 OR city LIKE :s4 OR state LIKE :s5 OR gst_number LIKE :s6)";
            $params[':s'] = "%$search%";
            $params[':s2'] = "%$search%";
            $params[':s3'] = "%$search%";
            $params[':s4'] = "%$search%";
            $params[':s5'] = "%$search%";
            $params[':s6'] = "%$search%";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats summary
        $statsStmt = $conn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
            SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) as contacted_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM dealership_inquiries");
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "data" => $results,
            "stats" => $stats
        ]);
        break;

    case 'POST':
        // Candidate submits dealership inquiry
        if (empty($data['owner_name']) || empty($data['phone'])) {
            echo json_encode(["status" => "error", "message" => "Owner Name and Contact Number are required."]);
            exit();
        }

        $business_name = isset($data['business_name']) ? trim($data['business_name']) : '';
        $owner_name = trim($data['owner_name']);
        $phone = trim($data['phone']);
        $email = isset($data['email']) ? trim($data['email']) : '';
        $gst_registered = isset($data['gst_registered']) ? trim($data['gst_registered']) : 'No';
        $gst_number = isset($data['gst_number']) ? trim($data['gst_number']) : '';
        $state = isset($data['state']) ? trim($data['state']) : '';
        $city = isset($data['city']) ? trim($data['city']) : '';
        $investment_capacity = isset($data['investment_capacity']) ? trim($data['investment_capacity']) : '';
        $interested_vertical = isset($data['interested_vertical']) ? trim($data['interested_vertical']) : 'Both';
        $showroom_area = isset($data['showroom_area']) ? trim($data['showroom_area']) : '';
        $existing_business = isset($data['existing_business']) ? trim($data['existing_business']) : '';
        $message = isset($data['message']) ? trim($data['message']) : '';

        $query = "INSERT INTO dealership_inquiries (
            business_name, owner_name, phone, email, gst_registered, gst_number,
            state, city, investment_capacity, interested_vertical, showroom_area,
            existing_business, message, status
        ) VALUES (
            :business_name, :owner_name, :phone, :email, :gst_registered, :gst_number,
            :state, :city, :investment_capacity, :interested_vertical, :showroom_area,
            :existing_business, :message, 'new'
        )";

        $stmt = $conn->prepare($query);
        $stmt->bindParam(":business_name", $business_name);
        $stmt->bindParam(":owner_name", $owner_name);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":gst_registered", $gst_registered);
        $stmt->bindParam(":gst_number", $gst_number);
        $stmt->bindParam(":state", $state);
        $stmt->bindParam(":city", $city);
        $stmt->bindParam(":investment_capacity", $investment_capacity);
        $stmt->bindParam(":interested_vertical", $interested_vertical);
        $stmt->bindParam(":showroom_area", $showroom_area);
        $stmt->bindParam(":existing_business", $existing_business);
        $stmt->bindParam(":message", $message);

        if ($stmt->execute()) {
            $inserted_id = $conn->lastInsertId();

            // Send notification email to admin/company
            $to = "info@motomaxev.com";
            $subject = "New Dealership Application: " . $owner_name . " (" . ($city ? $city . ", " : "") . $state . ")";
            $email_body = "A new dealership application has been received on the website:\n\n" .
                "Application ID: #" . $inserted_id . "\n" .
                "Firm/Business Name: " . ($business_name ?: "N/A") . "\n" .
                "Owner/Applicant Name: " . $owner_name . "\n" .
                "Contact Number: " . $phone . "\n" .
                "Email: " . $email . "\n" .
                "GST Registered: " . $gst_registered . "\n" .
                "GST Number: " . ($gst_number ?: "N/A") . "\n" .
                "Location: " . $city . ", " . $state . "\n" .
                "Investment Capacity: " . $investment_capacity . "\n" .
                "Interested Vertical: " . $interested_vertical . "\n" .
                "Proposed Area: " . ($showroom_area ?: "N/A") . "\n" .
                "Existing Business: " . ($existing_business ?: "N/A") . "\n\n" .
                "Message/Notes:\n" . ($message ?: "None") . "\n\n" .
                "Submitted On: " . date("Y-m-d H:i:s") . "\n";

            $reply_to = !empty($email) ? $email : "noreply@motomaxev.com";
            $headers = "From: noreply@motomaxev.com\r\n" .
                       "Reply-To: " . $reply_to . "\r\n" .
                       "X-Mailer: PHP/" . phpversion();

            // Attempt mail send (will succeed if mail server configured)
            @mail($to, $subject, $email_body, $headers);

            echo json_encode([
                "status" => "success",
                "message" => "Your dealership inquiry has been submitted successfully! Our regional business development team will contact you within 24 hours.",
                "id" => $inserted_id
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to submit dealership inquiry. Please try again."]);
        }
        break;

    case 'PUT':
        // Admin updates status of an inquiry
        if (isset($_GET['id']) && !empty($data['status'])) {
            $id = intval($_GET['id']);
            $status = trim($data['status']);
            $stmt = $conn->prepare("UPDATE dealership_inquiries SET status = :status WHERE id = :id");
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Status updated successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to update status."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Missing ID or status."]);
        }
        break;

    case 'DELETE':
        // Admin deletes an inquiry
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("DELETE FROM dealership_inquiries WHERE id = :id");
            $stmt->bindParam(":id", $id);
            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "Inquiry deleted successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to delete inquiry."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Missing inquiry ID."]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Method not allowed."]);
        break;
}
?>
