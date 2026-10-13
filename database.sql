CREATE DATABASE IF NOT EXISTS assignment_db;
USE assignment_db;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) DEFAULT 'Admin',
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price VARCHAR(100) NOT NULL,
    `range` VARCHAR(255),
    image VARCHAR(255),
    video VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    message TEXT,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS blogs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    image_url VARCHAR(255),
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Dummy Admin (Password: admin123)
-- MD5 or Hash can be used, here is BCRYPT of 'admin123'
INSERT INTO users (name, email, password, role) VALUES 
('Super Admin', 'admin@test.com', '$2y$10$tZ922QcM2R7zO0Vn9/3nLuT7dO.O06O2GfK32/V35F1nNf7H5rV1i', 'admin');

INSERT INTO products (name, price, `range`, image, video) VALUES
('EV Fast Charger 7.2kW', '₹35,000', 'Compatible with 4-wheelers', 'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=500', '');

INSERT INTO inquiries (name, email, message, status) VALUES
('Rahul Kumar', 'rahul@test.com', 'I want to bulk order EV chargers for my fleet.', 'pending');
