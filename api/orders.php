<?php
include_once '../config.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Ensure all required columns exist in orders table
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(100) DEFAULT ''");
} catch (Exception $e) {}
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(50) DEFAULT ''");
} catch (Exception $e) {}
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN customer_email VARCHAR(150) DEFAULT ''");
} catch (Exception $e) {}
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN shipping_address TEXT");
} catch (Exception $e) {}

if ($method === 'GET') {
    if (isset($_GET['admin']) && $_GET['admin'] === 'true') {
        $query = "SELECT * FROM orders ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
    } else {
        if (!isset($_GET['user_id'])) {
            echo json_encode(["status" => "error", "message" => "user_id is required"]);
            exit;
        }
        $user_id = $_GET['user_id'];
        $query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get items for each order
    foreach ($orders as &$order) {
        $item_query = "SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = :order_id";
        $item_stmt = $conn->prepare($item_query);
        $item_stmt->bindParam(':order_id', $order['id']);
        $item_stmt->execute();
        $order['items'] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($orders);
}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!empty($data->user_id) && !empty($data->total_amount)) {
        try {
            $conn->beginTransaction();
            
            // 1. Resolve Customer Information
            $payment_method = isset($data->payment_method) ? $data->payment_method : 'COD';
            $cust_name = isset($data->address->name) ? $data->address->name : '';
            $cust_phone = isset($data->address->phone) ? $data->address->phone : '';
            $ship_address = isset($data->address->address) ? $data->address->address . ', ' . $data->address->city . ' - ' . $data->address->pincode : '';

            // Resolve Customer Email
            $cust_email = '';
            if (!empty($data->customer_email)) {
                $cust_email = trim($data->customer_email);
            } elseif (!empty($data->email)) {
                $cust_email = trim($data->email);
            } elseif (isset($data->address->email) && !empty($data->address->email)) {
                $cust_email = trim($data->address->email);
            }

            // Resolve Customer User ID and Fallback
            $valid_user_id = $data->user_id;
            $chkUser = $conn->prepare("SELECT id, name, email FROM users WHERE id = :uid");
            $chkUser->execute([':uid' => $data->user_id]);
            $uRow = $chkUser->fetch(PDO::FETCH_ASSOC);
            if ($uRow) {
                if (empty($cust_name) && !empty($uRow['name'])) $cust_name = $uRow['name'];
                if (empty($cust_email) && !empty($uRow['email'])) $cust_email = trim($uRow['email']);
            } else {
                $fbUser = $conn->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($fbUser) {
                    $valid_user_id = $fbUser['id'];
                }
            }

            // 2. Create Order in Database
            $order_query = "INSERT INTO orders (user_id, total_amount, payment_method, order_status, customer_name, customer_phone, customer_email, shipping_address) VALUES (:user_id, :total_amount, :payment_method, 'Processing', :customer_name, :customer_phone, :customer_email, :shipping_address)";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bindParam(':user_id', $valid_user_id);
            $order_stmt->bindParam(':total_amount', $data->total_amount);
            $order_stmt->bindParam(':payment_method', $payment_method);
            $order_stmt->bindParam(':customer_name', $cust_name);
            $order_stmt->bindParam(':customer_phone', $cust_phone);
            $order_stmt->bindParam(':customer_email', $cust_email);
            $order_stmt->bindParam(':shipping_address', $ship_address);
            $order_stmt->execute();
            
            $order_id = $conn->lastInsertId();
            
            // 3. Move Cart items to order_items & compile product list for email
            $ordered_items_list = [];
            $cart_query = "SELECT c.product_id, c.quantity, p.name as product_name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = :user_id";
            $cart_stmt = $conn->prepare($cart_query);
            $cart_stmt->bindParam(':user_id', $data->user_id);
            $cart_stmt->execute();
            $cart_items = $cart_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($cart_items)) {
                foreach ($cart_items as $item) {
                    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
                    $item_stmt = $conn->prepare($item_query);
                    $item_stmt->bindParam(':order_id', $order_id);
                    $item_stmt->bindParam(':product_id', $item['product_id']);
                    $item_stmt->bindParam(':quantity', $item['quantity']);
                    $item_stmt->bindParam(':price', $item['price']);
                    $item_stmt->execute();

                    $ordered_items_list[] = [
                        'name' => $item['product_name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ];
                }
            } elseif (!empty($data->items) && is_array($data->items)) {
                foreach ($data->items as $item) {
                    $pid = isset($item->product_id) ? $item->product_id : (isset($item->id) ? $item->id : 0);
                    $pqty = isset($item->quantity) ? $item->quantity : 1;
                    $pprice = isset($item->price) ? $item->price : 0;
                    $pname = isset($item->name) ? $item->name : 'Product #' . $pid;

                    $item_query = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (:order_id, :product_id, :quantity, :price)";
                    $item_stmt = $conn->prepare($item_query);
                    $item_stmt->bindParam(':order_id', $order_id);
                    $item_stmt->bindParam(':product_id', $pid);
                    $item_stmt->bindParam(':quantity', $pqty);
                    $item_stmt->bindParam(':price', $pprice);
                    $item_stmt->execute();

                    $ordered_items_list[] = [
                        'name' => $pname,
                        'quantity' => $pqty,
                        'price' => $pprice
                    ];
                }
            }
            
            // 4. Clear Cart
            $clear_query = "DELETE FROM cart WHERE user_id = :user_id";
            $clear_stmt = $conn->prepare($clear_query);
            $clear_stmt->bindParam(':user_id', $data->user_id);
            $clear_stmt->execute();
            
            $conn->commit();
            
            // 5. Build Ordered Items Text for Emails
            $items_text = "";
            $item_index = 1;
            foreach ($ordered_items_list as $it) {
                $it_name = !empty($it['name']) ? $it['name'] : 'Product';
                $it_qty = !empty($it['quantity']) ? $it['quantity'] : 1;
                $it_price = !empty($it['price']) ? $it['price'] : '';
                $items_text .= $item_index . ". " . $it_name . "  x " . $it_qty . "  (" . $it_price . ")\n";
                $item_index++;
            }
            if (empty($items_text)) {
                $items_text = "Items details recorded in order database.\n";
            }

            // 6. Send Email to ADMIN
            $admin_email = 'admin@test.com';
            try {
                $admStmt = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
                if ($admStmt) {
                    $admRow = $admStmt->fetch(PDO::FETCH_ASSOC);
                    if (!empty($admRow['email'])) {
                        $admin_email = trim($admRow['email']);
                    }
                }
            } catch (Exception $e) {}

            $admin_subject = "🛒 New Order Placed: #" . $order_id . " - Total: ₹" . $data->total_amount;
            $admin_body = "Hello Admin,\n\n" .
                "A new product order has been successfully placed on MotoMax EV!\n\n" .
                "ORDER SUMMARY:\n" .
                "--------------------------------------------------\n" .
                "Order ID: #" . $order_id . "\n" .
                "Order Date: " . date("Y-m-d H:i:s") . "\n" .
                "Payment Method: " . $payment_method . "\n" .
                "Total Amount: ₹" . $data->total_amount . "\n" .
                "Order Status: Processing\n\n" .
                "CUSTOMER DETAILS:\n" .
                "--------------------------------------------------\n" .
                "Name: " . ($cust_name ?: "N/A") . "\n" .
                "Phone: " . ($cust_phone ?: "N/A") . "\n" .
                "Email: " . ($cust_email ?: "N/A") . "\n" .
                "Shipping Address:\n" . ($ship_address ?: "N/A") . "\n\n" .
                "ITEMS ORDERED:\n" .
                "--------------------------------------------------\n" .
                $items_text . "\n" .
                "--------------------------------------------------\n\n" .
                "You can view and manage this order from the MotoMax EV Admin Panel -> Orders tab.\n\n" .
                "— MotoMax EV Automated Notification System\n";

            $admin_headers = "From: orders@motomaxev.com\r\n" .
                             "Reply-To: " . (!empty($cust_email) ? $cust_email : "info@motomaxev.com") . "\r\n" .
                             "Cc: info@motomaxev.com\r\n" .
                             "X-Mailer: PHP/" . phpversion();

            @mail($admin_email, $admin_subject, $admin_body, $admin_headers);

            // 7. Send Confirmation Email to CUSTOMER (if email exists)
            if (!empty($cust_email)) {
                $customer_subject = "Order Confirmation: Your MotoMax EV Order #" . $order_id . " is Placed!";
                $customer_body = "Dear " . ($cust_name ?: "Customer") . ",\n\n" .
                    "Thank you for your order with MotoMax EV! We have received your order and our logistics team is preparing it for dispatch.\n\n" .
                    "YOUR ORDER DETAILS:\n" .
                    "--------------------------------------------------\n" .
                    "Order Number: #" . $order_id . "\n" .
                    "Date: " . date("d M Y, h:i A") . "\n" .
                    "Payment Method: " . $payment_method . "\n" .
                    "Total Amount: ₹" . $data->total_amount . "\n" .
                    "Order Status: Processing\n\n" .
                    "DELIVERY ADDRESS:\n" .
                    "--------------------------------------------------\n" .
                    ($cust_name ?: "") . "\n" .
                    "Contact Phone: " . ($cust_phone ?: "") . "\n" .
                    "Address: " . ($ship_address ?: "") . "\n\n" .
                    "ITEMS IN YOUR ORDER:\n" .
                    "--------------------------------------------------\n" .
                    $items_text . "\n" .
                    "--------------------------------------------------\n\n" .
                    "We will notify you once your order is dispatched with tracking information.\n\n" .
                    "If you have any questions, please contact our support team:\n" .
                    "📞 Phone: +91-11-48224444\n" .
                    "✉️ Email: support@motomaxev.com\n" .
                    "🌐 Website: www.motomaxev.com\n\n" .
                    "Thank you for choosing MotoMax EV — Driving India's Green Revolution!\n";

                $customer_headers = "From: orders@motomaxev.com\r\n" .
                                    "Reply-To: support@motomaxev.com\r\n" .
                                    "X-Mailer: PHP/" . phpversion();

                @mail($cust_email, $customer_subject, $customer_body, $customer_headers);
            }

            echo json_encode([
                "status" => "success",
                "message" => "Order placed successfully! Confirmation emails sent.",
                "order_id" => $order_id
            ]);
        } catch (Exception $e) {
            $conn->rollBack();
            echo json_encode(["status" => "error", "message" => "Failed to place order: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Incomplete data."]);
    }
}
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    if (isset($data->order_id) && isset($data->order_status)) {
        $query = "UPDATE orders SET order_status = :order_status WHERE id = :order_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':order_status', $data->order_status);
        $stmt->bindParam(':order_id', $data->order_id);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Order updated"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update"]);
        }
    }
}
?>
