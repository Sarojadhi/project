CREATE DATABASE IF NOT EXISTS dairy_management;
USE dairy_management;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff','farmer') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(150) DEFAULT '',
    join_date DATE DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(150) DEFAULT '',
    join_date DATE DEFAULT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS milk_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    entry_date DATE NOT NULL,
    shift ENUM('morning','evening') NOT NULL,
    litre DECIMAL(6,2) NOT NULL,
    fat DECIMAL(4,2) NOT NULL,
    snf DECIMAL(4,2) NOT NULL,
    rate_applied DECIMAL(6,2) DEFAULT 0,
    amount DECIMAL(9,2) DEFAULT 0,
    entered_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dana_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    entry_date DATE NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(6,2) NOT NULL,
    price_applied DECIMAL(6,2) NOT NULL,
    amount DECIMAL(9,2) NOT NULL,
    entered_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dana_price (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    price_per_unit DECIMAL(6,2) NOT NULL,
    effective_from DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rate_chart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fat_value DECIMAL(4,2) NOT NULL,
    snf_value DECIMAL(4,2) NOT NULL,
    rate_per_litre DECIMAL(6,2) NOT NULL,
    effective_from DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO users (username, password, role, full_name, phone, status)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', '', 'active');