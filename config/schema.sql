-- config/schema.sql
-- Mobile Accessories Inventory System Database Schema

CREATE DATABASE IF NOT EXISTS mobile_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mobile_inventory;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'box',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Suppliers table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(30),
    address TEXT,
    city VARCHAR(80),
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    supplier_id INT,
    brand VARCHAR(80),
    model VARCHAR(80),
    compatible_phones VARCHAR(255),
    cost_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    selling_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    low_stock_threshold INT DEFAULT 10,
    image VARCHAR(255),
    barcode VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
);

-- Stock movements table
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment', 'return') NOT NULL,
    quantity INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reference_no VARCHAR(100),
    notes TEXT,
    user_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Purchase orders table
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT,
    status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
    order_date DATE,
    expected_date DATE,
    received_date DATE,
    total_amount DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT,
    user_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Purchase order items table
CREATE TABLE IF NOT EXISTS purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    received_qty INT DEFAULT 0,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@mobilestockpro.com', 'admin');

-- Default categories
INSERT INTO categories (name, slug, description, icon) VALUES
('Cases & Covers', 'cases-covers', 'Phone cases, covers, and protective sleeves', 'shield'),
('Screen Protectors', 'screen-protectors', 'Tempered glass and plastic screen protectors', 'tablet'),
('Chargers & Cables', 'chargers-cables', 'USB cables, fast chargers, wireless chargers', 'zap'),
('Power Banks', 'power-banks', 'Portable battery packs and power banks', 'battery'),
('Earphones & Headsets', 'earphones-headsets', 'Wired and wireless audio accessories', 'headphones'),
('Mounts & Holders', 'mounts-holders', 'Car mounts, desk stands, and holders', 'navigation'),
('Memory & Storage', 'memory-storage', 'MicroSD cards and OTG storage devices', 'hard-drive'),
('Selfie Sticks & Tripods', 'selfie-sticks-tripods', 'Photography accessories', 'camera');

-- Sample supplier
INSERT INTO suppliers (name, contact_person, email, phone, address, city) VALUES
('TechSource PH', 'Juan Dela Cruz', 'juan@techsource.ph', '09171234567', '123 Tech Street, Binondo', 'Manila'),
('GadgetHub Wholesale', 'Maria Santos', 'maria@gadgethub.com', '09281234567', '456 Commerce Ave', 'Cebu City');

-- Sample products
INSERT INTO products (sku, name, description, category_id, supplier_id, brand, cost_price, selling_price, stock_quantity, low_stock_threshold) VALUES
('CAS-001', 'iPhone 15 Silicone Case Black', 'Premium silicone protective case', 1, 1, 'Spigen', 120.00, 299.00, 50, 10),
('CAS-002', 'Samsung S24 Clear Case', 'Crystal clear TPU case', 1, 1, 'Ringke', 80.00, 199.00, 30, 8),
('SCR-001', 'Universal Tempered Glass 6.5"', '9H hardness screen protector', 2, 1, 'Nillkin', 40.00, 99.00, 100, 20),
('CHR-001', '65W GaN Fast Charger', 'USB-C PD fast charging adapter', 3, 2, 'Anker', 350.00, 799.00, 25, 5),
('CHR-002', 'USB-C to Lightning Cable 1m', 'MFi certified charging cable', 3, 2, 'Anker', 150.00, 349.00, 40, 10),
('PWR-001', '20000mAh Power Bank', 'Dual USB + USB-C power bank', 4, 2, 'Baseus', 600.00, 1299.00, 15, 5),
('EAR-001', 'Wireless Earbuds TWS', 'Bluetooth 5.3 true wireless earbuds', 5, 2, 'JBL', 800.00, 1799.00, 8, 5),
('MNT-001', 'Magnetic Car Mount', 'Dashboard/windshield phone holder', 6, 1, 'Baseus', 120.00, 299.00, 35, 10);
