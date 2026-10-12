<?php
require 'config.php';
try {
    $conn->exec("ALTER TABLE orders ADD COLUMN customer_name VARCHAR(100), ADD COLUMN customer_phone VARCHAR(20), ADD COLUMN shipping_address TEXT");
    echo "Done";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
