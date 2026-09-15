CREATE DATABASE IF NOT EXISTS dairy_management;

USE dairy_management;


-- Users: admin, staff and farmers
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'farmer') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Farmers
CREATE TABLE farmers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(255),
    join_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',

    FOREIGN KEY (user_id)
        REFERENCES users(id)
);


-- Staff
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    address VARCHAR(255),
    join_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',

    FOREIGN KEY (user_id)
        REFERENCES users(id)
);


-- Milk rate chart
CREATE TABLE rate_chart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fat_value DECIMAL(4,1) NOT NULL,
    snf_value DECIMAL(4,1) NOT NULL,
    rate_per_litre DECIMAL(8,2) NOT NULL,
    effective_from DATE
);


-- Dana/feed prices
CREATE TABLE dana_price (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    price_per_unit DECIMAL(8,2) NOT NULL,
    effective_from DATE
);


-- Milk collection entries
CREATE TABLE milk_entries (
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

    FOREIGN KEY (farmer_id)
        REFERENCES farmers(id),

    FOREIGN KEY (entered_by)
        REFERENCES users(id),

    UNIQUE KEY unique_milk (farmer_id, entry_date, shift)
);


-- Dana/feed entries
CREATE TABLE dana_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmer_id INT NOT NULL,
    entry_date DATE NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(8,2) NOT NULL,
    price_applied DECIMAL(8,2),
    amount DECIMAL(10,2),
    entered_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (farmer_id)
        REFERENCES farmers(id),

    FOREIGN KEY (entered_by)
        REFERENCES users(id)
);


-- Default dana prices
INSERT INTO dana_price
    (item_name, price_per_unit, effective_from)
VALUES
    ('Cow Feed', 45.00, CURDATE()),
    ('Buffalo Feed', 50.00, CURDATE());
