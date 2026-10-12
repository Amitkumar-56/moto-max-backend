<?php
include_once "../config.php";

$name = "Admin";
$email = "admin@motomax.com";
$password = "admin123";
$role = "admin";

$password_hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
$stmt->bindParam(":email", $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "Admin already exists! (admin@motomax.com / admin123)";
} else {
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password_hash);
    $stmt->bindParam(":role", $role);
    
    if ($stmt->execute()) {
        echo "Admin created successfully! (admin@motomax.com / admin123)";
    } else {
        echo "Failed to create admin.";
    }
}
?>
