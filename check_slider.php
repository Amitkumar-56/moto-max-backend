<?php
include_once 'c:/xamp/htdocs/assinment/php-backend/config.php';
$stmt = $conn->query("SELECT * FROM settings WHERE setting_key = 'homepage_slider'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo json_encode($row);
?>
