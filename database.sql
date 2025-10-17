-- 1. ROLES Table (For RBAC)
CREATE TABLE `roles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `role_name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g., Manager, Employee'
);

-- 2. USERS Table (Employee Accounts)
CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `employee_id` VARCHAR(20) NOT NULL UNIQUE,
    -- Store hashed password using a strong algorithm (e.g., bcrypt/Argon2 via PHP, not raw)
    `password_hash` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `starting_date` DATE,
    `role_id` INT UNSIGNED NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
);

-- 3. SUPPLIERS Table
CREATE TABLE `suppliers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `supplier_name` VARCHAR(100) NOT NULL,
    `contact_person` VARCHAR(100),
    `phone` VARCHAR(20),
    `email` VARCHAR(100)
);

-- 4. INVENTORY_ITEMS Table
CREATE TABLE `inventory_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_name` VARCHAR(100) NOT NULL UNIQUE,
    `item_description` TEXT,
    `supplier_id` INT UNSIGNED,
    `unit_of_measure` VARCHAR(20) NOT NULL,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`)
);

-- 5. STOCK_LEVELS Table (For real-time stock and alerts)
-- NOTE: The 'encrypted_records' requirement for AES-256 storage will be handled at the application (Laravel) level 
-- *before* insertion into the database, for sensitive data if needed.
CREATE TABLE `stock_levels` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT UNSIGNED NOT NULL,
    `quantity` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `expiry_date` DATE NULL COMMENT 'For expiring items alert [cite: 48]',
    `minimum_stock_threshold` DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'For Low Stock alert ',
    `last_updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`),
    UNIQUE KEY `uk_item_expiry` (`item_id`, `expiry_date`) -- Allows tracking different batches/expiries
);

-- 6. ORDERS Table (Header)
CREATE TABLE `orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_type` ENUM('Supplier', 'Customer') NOT NULL,
    `supplier_id` INT UNSIGNED NULL, -- NULL if a customer order/sale
    `order_status` VARCHAR(50) NOT NULL COMMENT 'e.g., Pending, Confirmed, Cancelled',
    `order_date` DATETIME NOT NULL,
    `created_by_user_id` INT UNSIGNED NOT NULL,
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
    FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`)
);

-- 7. ORDER_ITEMS Table (Line Items)
CREATE TABLE `order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `quantity_ordered` DECIMAL(10, 2) NOT NULL,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`),
    FOREIGN KEY (`item_id`) REFERENCES `inventory_items`(`id`)
);

-- 8. ACTIVITY_LOG Table (For accountability)
CREATE TABLE `activity_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `activity_type` VARCHAR(100) NOT NULL, -- e.g., 'Stock Update', 'Login', 'Report Generated'
    `details` TEXT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
);