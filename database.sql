CREATE DATABASE IF NOT EXISTS campus_food_ordering_system1;

USE campus_food_ordering_system1;

CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES user_roles(id)
);

-- ============================================
-- 2. CATEGORY & FOOD TABLES
-- ============================================

-- Categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Foods
CREATE TABLE foods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255),
    preparation_time INT DEFAULT 15,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ============================================
-- 3. ORDER TABLES
-- ============================================

-- Order Statuses
CREATE TABLE order_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    delivery_address TEXT,
    payment_method VARCHAR(50),
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    account_name VARCHAR(100),
    account_number VARCHAR(50),
    transaction_image VARCHAR(255),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_id) REFERENCES order_statuses(id)
);

-- Order Items
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
);

-- ============================================
-- 4. PAYMENT TABLES
-- ============================================

-- Payment Methods
CREATE TABLE payment_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    method_name VARCHAR(50) UNIQUE NOT NULL,
    account_name VARCHAR(100),
    account_number VARCHAR(50),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment Statuses
CREATE TABLE payment_statuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    status_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT UNIQUE NOT NULL,
    payment_method_id INT NOT NULL,
    payment_status_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_no VARCHAR(50),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id),
    FOREIGN KEY (payment_status_id) REFERENCES payment_statuses(id)
);

-- ============================================
-- 5. CART TABLES
-- ============================================

-- Cart
CREATE TABLE carts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_cart (user_id)
);

-- Cart Items
CREATE TABLE cart_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cart_id INT NOT NULL,
    food_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_food (cart_id, food_id)
);

-- ============================================
-- 6. NOTIFICATION TABLES
-- ============================================

-- Notifications
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- 7. INVENTORY TABLES
-- ============================================

-- Inventory Actions
CREATE TABLE inventory_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action_name VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory Logs
CREATE TABLE inventory_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    food_id INT NOT NULL,
    action_id INT NOT NULL,
    quantity_change INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    FOREIGN KEY (action_id) REFERENCES inventory_actions(id)
);

-- ============================================
-- 8. REVIEWS TABLE
-- ============================================

-- Reviews
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    food_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_food_review (user_id, food_id)
);

-- ============================================
-- INSERT DEFAULT DATA
-- ============================================

-- User Roles
INSERT INTO user_roles (role_name) VALUES 
('admin'),
('staff'),
('user');

-- Order Statuses
INSERT INTO order_statuses (status_name) VALUES 
('pending'),
('accepted'),
('preparing'),
('ready'),
('completed'),
('cancelled');
INSERT INTO payment_statuses (status_name) VALUES 
('pending'),
('paid'),
('failed');
-- Payment Methods
INSERT INTO payment_methods (method_name) VALUES 
('cash'),
('qr'),
('mobile_banking');

-- Payment Statuses
INSERT INTO payment_statuses (status_name) VALUES 
('pending'),
('paid'),
('failed');

-- Inventory Actions
INSERT INTO inventory_actions (action_name) VALUES 
('restock'),
('sale'),
('adjustment');

-- Categories
INSERT INTO categories (name, description) VALUES 
('Burgers', 'Delicious burgers with premium ingredients'),
('Pizza', 'Authentic pizzas with fresh toppings'),
('Drinks', 'Refreshing beverages'),
('Sweets', 'Desserts and sweet treats'),
('Rice Meals', 'Hearty rice-based meals');

-- Foods
INSERT INTO foods (category_id, name, description, price, stock, preparation_time, image) VALUES
(1, 'Cheese Burger Deluxe', 'Juicy beef patty with double cheddar cheese', 100.00, 50, 15, 'burger.png'),
(1, 'Bacon Burger', 'Crispy bacon with beef patty and cheese', 120.00, 45, 15, 'bacon-burger.png'),
(2, 'Pepperoni Pizza', 'Classic pepperoni with mozzarella cheese', 120.00, 30, 20, 'pizza.png'),
(2, 'Margherita Pizza', 'Fresh tomatoes, basil, and mozzarella', 110.00, 35, 20, 'margherita.png'),
(3, 'Iced Tea', 'Refreshing lemon iced tea', 30.00, 100, 5, 'tea.png'),
(3, 'Fresh Lemonade', 'Homemade fresh lemonade', 35.00, 80, 5, 'lemonade.png'),
(4, 'Chocolate Cake', 'Rich chocolate cake slice', 45.00, 40, 10, 'cake.png'),
(4, 'Cheesecake', 'Creamy cheesecake with berry topping', 55.00, 30, 10, 'cheesecake.png'),
(5, 'Chicken Rice Bowl', 'Grilled chicken with garlic rice', 85.00, 25, 15, 'ricebowl.png'),
(5, 'Pork Adobo Rice Bowl', 'Tender pork adobo with rice', 95.00, 20, 15, 'adobo.png');

-- Users (password: admin123, staff123, user123)
INSERT INTO users (role_id, name, email, password, phone, address) VALUES 
(1, 'Admin User', 'admin@foodie.com', '$2y$12$w/5unCs2AaDn5nKLN9nwYOfD67tWiEhCuLa3O0Yld0ktMYwGKDXWa', '0912345678', 'Admin Office'),
(2, 'Staff User', 'staff@foodie.com', '$2y$12$F/ONKXixdkfEqZclqtAyneZtDGYpvZDiUxstUxsvojL9C3esiYBma', '0912345679', 'Restaurant'),
(3, 'John Student', 'john@foodie.com', '$2y$12$F/ONKXixdkfEqZclqtAyneZtDGYpvZDiUxstUxsvojL9C3esiYBma', '0912345680', 'Dormitory A');

-- Cart for John (user_id = 3)
INSERT INTO carts (user_id) VALUES (3);

-- Sample Order
INSERT INTO orders (user_id, status_id, total_amount) VALUES 
(3, 5, 130.00); -- status_id 5 = completed

INSERT INTO order_items (order_id, food_id, quantity, unit_price, subtotal) VALUES 
(1, 1, 1, 100.00, 100.00),
(1, 5, 1, 30.00, 30.00);

-- Sample Payment
INSERT INTO payments (order_id, payment_method_id, payment_status_id, amount, transaction_no) VALUES 
(1, 1, 2, 130.00, 'TRX-001'); -- method: cash, status: paid

-- Sample Review
INSERT INTO reviews (user_id, food_id, rating, comment) VALUES 
(3, 1, 5, 'Best burger on campus!');

-- Inventory Log
INSERT INTO inventory_logs (food_id, action_id, quantity_change) VALUES 
(1, 2, -1), -- sale
(5, 2, -1); -- sale

-- ============================================
-- VERIFY DATA
-- ============================================

SELECT 'user_roles' as alldata, COUNT(*) as Count FROM user_roles
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'foods', COUNT(*) FROM foods
UNION ALL
SELECT 'order_statuses', COUNT(*) FROM order_statuses
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL
SELECT 'payment_methods', COUNT(*) FROM payment_methods
UNION ALL
SELECT 'payment_statuses', COUNT(*) FROM payment_statuses
UNION ALL
SELECT 'payments', COUNT(*) FROM payments
UNION ALL
SELECT 'carts', COUNT(*) FROM carts
UNION ALL
SELECT 'cart_items', COUNT(*) FROM cart_items
UNION ALL
SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL
SELECT 'inventory_actions', COUNT(*) FROM inventory_actions
UNION ALL
SELECT 'inventory_logs', COUNT(*) FROM inventory_logs
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews;


SELECT * FROM payment_statuses;

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT IGNORE INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'FOODIE', 'general'),
('site_email', 'admin@foodie.com', 'general'),
('site_phone', '+1234567890', 'general'),
('timezone', 'Asia/Manila', 'general'),
('preparation_time', '15', 'order'),
('cancellation_time', '5', 'order'),
('cash_on_delivery', '1', 'payment'),
('qr_payment', '1', 'payment'),
('maintenance_mode', '0', 'system'),
('currency', 'USD', 'system'),
('max_orders_per_day', '100', 'order'),
('notification_email', 'orders@foodie.com', 'notification');

-- Verify settings
SELECT * FROM settings;

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    verification_code VARCHAR(10) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN email_verified_at TIMESTAMP NULL;