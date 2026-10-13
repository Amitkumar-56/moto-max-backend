<?php
include_once "../config.php";

$name = "Admin";
$email = "admin@motomax.com";
$password = "admin123";
$role = "admin";

$password_hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("SELECT id FROM admin WHERE email = :email");
$stmt->bindParam(":email", $email);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    echo "Admin already exists! (admin@motomax.com / admin123)";
} else {
    $stmt = $conn->prepare("INSERT INTO admin (name, email, password) VALUES (:name, :email, :password)");
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password_hash);
    
    if ($stmt->execute()) {
        echo "Admin created successfully! (admin@motomax.com / admin123)";
    } else {
        echo "Failed to create admin.";
    }
}
?>
