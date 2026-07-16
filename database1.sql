-- ============================================
-- CAMPUS FOOD ORDERING SYSTEM - COMPLETE SQL
-- ============================================

-- Create database
CREATE DATABASE IF NOT EXISTS campus_food_ordering_system1;
USE campus_food_ordering_system1;

-- ============================================
-- 1. AUTHORIZATION (RBAC)
-- ============================================

-- Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Permissions
CREATE TABLE IF NOT EXISTS permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Role Permissions
CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- ============================================
-- 2. USER MANAGEMENT
-- ============================================

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(10),
    verification_expires_at TIMESTAMP,
    email_verified_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- ============================================
-- 3. FOOD MANAGEMENT
-- ============================================

-- Food Statuses
CREATE TABLE IF NOT EXISTS food_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(30) UNIQUE NOT NULL
);

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Foods
CREATE TABLE IF NOT EXISTS foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    status_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    stock INT DEFAULT 0,
    image VARCHAR(255),
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (status_id) REFERENCES food_statuses(id)
);

-- ============================================
-- 4. SHOPPING CART
-- ============================================

-- Carts
CREATE TABLE IF NOT EXISTS carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cart Items
CREATE TABLE IF NOT EXISTS cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(10,2),
    food_size_id INT DEFAULT NULL,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (food_size_id) REFERENCES food_sizes(id) ON DELETE SET NULL,
    UNIQUE KEY unique_cart_food (cart_id, food_id, food_size_id)
);

-- ============================================
-- 5. ORDER MANAGEMENT
-- ============================================

-- Order Statuses
CREATE TABLE IF NOT EXISTS order_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) UNIQUE NOT NULL
);

-- Orders
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status_id INT NOT NULL,
    total_amount DECIMAL(10,2),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delivery_address TEXT,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES order_statuses(id)
);

-- Order Items
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT,
    unit_price DECIMAL(10,2),
    subtotal DECIMAL(10,2),
    food_size_id INT DEFAULT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (food_size_id) REFERENCES food_sizes(id) ON DELETE SET NULL
);

-- ============================================
-- 6. PAYMENT MANAGEMENT
-- ============================================

-- Payment Methods
CREATE TABLE IF NOT EXISTS payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) UNIQUE NOT NULL,
    account_name VARCHAR(100),
    account_number VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payment Statuses
CREATE TABLE IF NOT EXISTS payment_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT UNIQUE NOT NULL,
    payment_method_id INT NOT NULL,
    payment_status_id INT NOT NULL,
    amount DECIMAL(10,2),
    transaction_no VARCHAR(100),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    FOREIGN KEY (payment_status_id) REFERENCES payment_statuses(id)
);

-- ============================================
-- 7. NOTIFICATIONS
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 8. REVIEWS
-- ============================================

CREATE TABLE IF NOT EXISTS reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    rating INT,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_food_review (user_id, food_id)
);

-- ============================================
-- 9. SYSTEM SETTINGS
-- ============================================

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- Roles
INSERT INTO roles (name) VALUES 
('admin'),
('staff'),
('user');

-- Permissions
INSERT INTO permissions (name, display_name, module) VALUES
('view_dashboard', 'View Dashboard', 'dashboard'),
('manage_users', 'Manage Users', 'user'),
('manage_menu', 'Manage Menu', 'food'),
('manage_orders', 'Manage Orders', 'order'),
('view_reports', 'View Reports', 'report');

-- Assign all permissions to admin
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions;

-- Food Statuses
INSERT INTO food_statuses (status_name) VALUES 
('active'),
('inactive'),
('out_of_stock');

-- Order Statuses
INSERT INTO order_statuses (status_name) VALUES 
('pending'),
('accepted'),
('preparing'),
('ready'),
('completed'),
('cancelled');

-- Payment Statuses
INSERT INTO payment_statuses (status_name) VALUES 
('pending'),
('paid'),
('failed');

-- Payment Methods
INSERT INTO payment_methods (method_name, account_name, account_number, is_active) VALUES
('Cash on Delivery', NULL, NULL, 1),
('K Pay', 'Foodie Restaurant', '0987654321', 1),
('Wave Pay', 'Foodie Restaurant', '0976543210', 1),
('AYA Pay', 'Foodie Restaurant', '0981234567', 1),
('CB Pay', 'Foodie Restaurant', '0971234568', 1);

-- Settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'FOODIE', 'general'),
('site_email', 'admin@foodie.com', 'general'),
('site_phone', '+1234567890', 'general'),
('timezone', 'Asia/Manila', 'general'),
('preparation_time', '15', 'order'),
('cancellation_time', '5', 'order'),
('currency', 'USD', 'system'),
('maintenance_mode', '0', 'system');

-- Admin User (password: admin123)
INSERT INTO users (role_id, name, email, password, phone, address, is_verified, email_verified_at) VALUES 
(1, 'Admin User', 'admin@gmail.com', '$2y$12$w/5unCs2AaDn5nKLN9nwYOfD67tWiEhCuLa3O0Yld0ktMYwGKDXWa', '0912345678', 'Admin Office', 1, NOW());

-- Sample User (password: user123)
INSERT INTO users (role_id, name, email, password, phone, address, is_verified, email_verified_at) VALUES 
(3, 'John Student', 'john@gmail.com', '$2y$12$F/ONKXixdkfEqZclqtAyneZtDGYpvZDiUxstUxsvojL9C3esiYBma', '0912345680', 'Dormitory A', 1, NOW());

-- Categories
INSERT INTO categories (name, description) VALUES 
('Burgers', 'Delicious burgers with premium ingredients'),
('Pizza', 'Authentic pizzas with fresh toppings'),
('Drinks', 'Refreshing beverages'),
('Sweets', 'Desserts and sweet treats'),
('Rice Meals', 'Hearty rice-based meals');

-- Foods
INSERT INTO foods (category_id, status_id, name, description, price, stock, preparation_time, image) VALUES
(1, 1, 'Cheese Burger Deluxe', 'Juicy beef patty with double cheddar cheese', 100.00, 50, 15, 'burger.png'),
(1, 1, 'Bacon Burger', 'Crispy bacon with beef patty and cheese', 120.00, 45, 15, 'bacon-burger.png'),
(2, 1, 'Pepperoni Pizza', 'Classic pepperoni with mozzarella cheese', 120.00, 30, 20, 'pizza.png'),
(2, 1, 'Margherita Pizza', 'Fresh tomatoes, basil, and mozzarella', 110.00, 35, 20, 'margherita.png'),
(3, 1, 'Iced Tea', 'Refreshing lemon iced tea', 30.00, 100, 5, 'tea.png'),
(3, 1, 'Fresh Lemonade', 'Homemade fresh lemonade', 35.00, 80, 5, 'lemonade.png'),
(4, 1, 'Chocolate Cake', 'Rich chocolate cake slice', 45.00, 40, 10, 'cake.png'),
(4, 1, 'Cheesecake', 'Creamy cheesecake with berry topping', 55.00, 30, 10, 'cheesecake.png'),
(5, 1, 'Chicken Rice Bowl', 'Grilled chicken with garlic rice', 85.00, 25, 15, 'ricebowl.png'),
(5, 1, 'Pork Adobo Rice Bowl', 'Tender pork adobo with rice', 95.00, 20, 15, 'adobo.png');

-- Verify data
SELECT 'roles' as able, COUNT(*) as Count FROM roles
UNION ALL
SELECT 'permissions', COUNT(*) FROM permissions
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'foods', COUNT(*) FROM foods
UNION ALL
SELECT 'order_statuses', COUNT(*) FROM order_statuses
UNION ALL
SELECT 'payment_methods', COUNT(*) FROM payment_methods
UNION ALL
SELECT 'payment_statuses', COUNT(*) FROM payment_statuses
UNION ALL
SELECT 'settings', COUNT(*) FROM settings;

-- Create refund_statuses table
CREATE TABLE IF NOT EXISTS refund_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert refund statuses
INSERT INTO refund_statuses (status_name) VALUES 
('pending'),
('approved'),
('rejected'),
('completed');


-- Create refunds table
CREATE TABLE IF NOT EXISTS refunds (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_id INT NOT NULL,
    requested_by INT NOT NULL,
    approved_by INT NULL,
    reason TEXT NOT NULL,
    refund_status_id INT NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add payment_status_id if not exists
-- 1 = pending, 2 = paid, 3 = refund_pending, 4 = refunded, 5 = failed
ALTER TABLE payments 
ADD COLUMN payment_status_id INT DEFAULT 1 AFTER payment_method_id;

-- Update existing payments
UPDATE payments SET payment_status_id = 2 WHERE payment_status_id = 2;

INSERT INTO order_statuses (status_name) VALUES ('refund_requested');

-- Create food_sizes table
CREATE TABLE `food_sizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `food_id` int NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int DEFAULT '0',
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `food_id` (`food_id`),
  CONSTRAINT `food_sizes_ibfk_1` FOREIGN KEY (`food_id`) REFERENCES `foods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default sizes for existing foods
-- For each food, create a default size 'Standard' with the existing price
INSERT INTO food_sizes (food_id, size_name, price, stock, is_default)
SELECT id, 'Standard', price, stock, 1 FROM foods;

-- Add food_size_id to cart_items
ALTER TABLE `cart_items` 
ADD COLUMN `food_size_id` int NULL AFTER `food_id`,
ADD KEY `food_size_id` (`food_size_id`),
ADD CONSTRAINT `cart_items_ibfk_3` FOREIGN KEY (`food_size_id`) REFERENCES `food_sizes` (`id`) ON DELETE CASCADE;

-- Add food_size_id to order_items
ALTER TABLE `order_items` 
ADD COLUMN `food_size_id` int NULL AFTER `food_id`,
ADD KEY `food_size_id` (`food_size_id`),
ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`food_size_id`) REFERENCES `food_sizes` (`id`) ON DELETE CASCADE;

-- Migrate existing data: link cart_items to default sizes
UPDATE cart_items ci
JOIN foods f ON ci.food_id = f.id
JOIN food_sizes fs ON fs.food_id = f.id AND fs.is_default = 1
SET ci.food_size_id = fs.id
WHERE ci.food_size_id IS NULL;

-- Migrate existing data: link order_items to default sizes
UPDATE order_items oi
JOIN foods f ON oi.food_id = f.id
JOIN food_sizes fs ON fs.food_id = f.id AND fs.is_default = 1
SET oi.food_size_id = fs.id
WHERE oi.food_size_id IS NULL;

-- Make food_size_id NOT NULL after migration
ALTER TABLE `cart_items` 
MODIFY COLUMN `food_size_id` int NOT NULL;

ALTER TABLE `order_items` 
MODIFY COLUMN `food_size_id` int NOT NULL;

-- Remove unit_price from cart_items (price comes from food_sizes)
ALTER TABLE `cart_items` 
DROP COLUMN `unit_price`;	

ALTER TABLE foods DROP COLUMN price;
ALTER TABLE foods DROP COLUMN stock;