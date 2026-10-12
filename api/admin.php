<?php
// api/admin.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

include_once '../config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'dashboard';

if ($action == 'dashboard') {
    // Return some admin stats
    $stats = [];
    
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total admins
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $stats['total_admins'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode(["status" => "success", "data" => $stats]);
} 
elseif ($action == 'users') {
    // List all users
    $stmt = $conn->query("SELECT id, name, email, role, created_at FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["status" => "success", "data" => $users]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid action."]);
}
?>
