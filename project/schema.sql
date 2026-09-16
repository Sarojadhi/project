CREATE DATABASE IF NOT EXISTS dairy_management;

USE dairy_management;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'farmer') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Farmers
CREATE TABLE IF NOT EXISTS farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(255),
    join_date DATE,
    status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Staff
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(255),
    join_date DATE,
    status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Dana/feed prices
CREATE TABLE IF NOT EXISTS dana_price (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    price_per_unit DECIMAL(8,2) NOT NULL,
    effective_from DATE
);

-- Milk FAT and SNF rates
CREATE TABLE IF NOT EXISTS milk_rate_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fat_rate DECIMAL(8,2) NOT NULL,
    snf_rate DECIMAL(8,2) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Milk collection entries
CREATE TABLE IF NOT EXISTS milk_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    entry_date DATE NOT NULL,
    shift ENUM('morning', 'evening') NOT NULL,
    litre DECIMAL(8,2) NOT NULL,
    fat DECIMAL(4,1) NOT NULL,
    snf DECIMAL(4,1) NOT NULL,
    rate_applied DECIMAL(8,2),
    amount DECIMAL(10,2),
    entered_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id),
    FOREIGN KEY (entered_by) REFERENCES users(id),
    UNIQUE KEY unique_milk (farmer_id, entry_date, shift)
);

-- Dana/feed entries
CREATE TABLE IF NOT EXISTS dana_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    entry_date DATE NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(8,2) NOT NULL,
    price_applied DECIMAL(8,2),
    amount DECIMAL(10,2),
    entered_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (farmer_id) REFERENCES farmers(id),
    FOREIGN KEY (entered_by) REFERENCES users(id)
);

-- Fix existing status columns
ALTER TABLE users
MODIFY COLUMN status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active';

ALTER TABLE farmers
MODIFY COLUMN status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active';

ALTER TABLE staff
MODIFY COLUMN status ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active';

-- Default dana prices
INSERT INTO dana_price (item_name, price_per_unit, effective_from)
SELECT 'Cow Feed', 45.00, CURDATE()
WHERE NOT EXISTS (
    SELECT 1 FROM dana_price WHERE item_name = 'Cow Feed'
);

INSERT INTO dana_price (item_name, price_per_unit, effective_from)
SELECT 'Buffalo Feed', 50.00, CURDATE()
WHERE NOT EXISTS (
    SELECT 1 FROM dana_price WHERE item_name = 'Buffalo Feed'
);

-- Default milk FAT/SNF rates
INSERT INTO milk_rate_settings (fat_rate, snf_rate)
SELECT 8.50, 4.00
WHERE NOT EXISTS (
    SELECT 1 FROM milk_rate_settings
);