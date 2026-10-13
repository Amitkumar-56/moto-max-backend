<?php
include_once 'config.php';

try {
    $conn->exec("
    CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) DEFAULT 'Admin',
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ");

    $count = $conn->query("SELECT COUNT(*) FROM admin")->fetchColumn();
    if ($count == 0) {
        $adminUser = $conn->query("SELECT name, email, password FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($adminUser) {
            $ins = $conn->prepare("INSERT INTO admin (name, email, password) VALUES (:name, :email, :password)");
            $ins->execute([
                ':name' => $adminUser['name'],
                ':email' => $adminUser['email'],
                ':password' => $adminUser['password']
            ]);
            echo "Admin migrated from users table.\n";
        } else {
            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO admin (name, email, password) VALUES ('Admin', 'admin@motomax.com', :password)");
            $ins->execute([':password' => $hash]);
            echo "Created default admin in admin table.\n";
        }
    } else {
        echo "Admin table already populated.\n";
    }

    $rows = $conn->query("SELECT id, name, email FROM admin")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
