SET @uid := (SELECT id FROM users WHERE is_active = 1 ORDER BY id LIMIT 1);

-- Suppliers (idempotent)
INSERT IGNORE INTO suppliers (supplier_name, contact_person, phone, email) VALUES
('Tokyo Beans Co.', 'Akira Tanaka', '+81-3-1234-5678', 'sales@tokyobeans.jp'),
('Kyoto Bakery Supply', 'Yuki Nakamura', '+81-75-234-5678', 'orders@kyotobakery.jp'),
('Osaka Dairy Ltd.', 'Hana Suzuki', '+81-6-3456-7890', 'service@osakadairy.jp');

-- Inventory items (idempotent, link suppliers by name)
INSERT IGNORE INTO inventory_items (item_name, item_description, supplier_id, unit_of_measure) VALUES
('Arabica Beans 1kg', 'Premium arabica coffee beans 1kg bag', (SELECT id FROM suppliers WHERE supplier_name='Tokyo Beans Co.' LIMIT 1), 'kg'),
('House Blend Beans 1kg', 'House blend beans 1kg', (SELECT id FROM suppliers WHERE supplier_name='Tokyo Beans Co.' LIMIT 1), 'kg'),
('Matcha Powder 500g', 'Ceremonial-grade matcha 500g', (SELECT id FROM suppliers WHERE supplier_name='Kyoto Bakery Supply' LIMIT 1), 'g'),
('Croissant Dough Box', 'Frozen croissant dough, 50 pcs', (SELECT id FROM suppliers WHERE supplier_name='Kyoto Bakery Supply' LIMIT 1), 'pcs'),
('Whole Milk 1L', 'UHT whole milk 1 liter', (SELECT id FROM suppliers WHERE supplier_name='Osaka Dairy Ltd.' LIMIT 1), 'L'),
('Whipping Cream 1L', 'Dairy whipping cream 1 liter', (SELECT id FROM suppliers WHERE supplier_name='Osaka Dairy Ltd.' LIMIT 1), 'L');

-- Stock levels (upsert by (item_id, expiry_date))
INSERT INTO stock_levels (item_id, quantity, expiry_date, minimum_stock_threshold)
VALUES
((SELECT id FROM inventory_items WHERE item_name='Arabica Beans 1kg' LIMIT 1), 15.00, '2026-12-31', 5.00),
((SELECT id FROM inventory_items WHERE item_name='House Blend Beans 1kg' LIMIT 1), 10.00, '2026-12-31', 5.00),
((SELECT id FROM inventory_items WHERE item_name='Matcha Powder 500g' LIMIT 1), 8.00, '2026-06-30', 2.00),
((SELECT id FROM inventory_items WHERE item_name='Croissant Dough Box' LIMIT 1), 40.00, '2025-12-31', 20.00),
((SELECT id FROM inventory_items WHERE item_name='Whole Milk 1L' LIMIT 1), 30.00, '2025-11-30', 10.00),
((SELECT id FROM inventory_items WHERE item_name='Whipping Cream 1L' LIMIT 1), 18.00, '2025-11-15', 6.00)
ON DUPLICATE KEY UPDATE
quantity = VALUES(quantity),
minimum_stock_threshold = VALUES(minimum_stock_threshold),
last_updated_at = CURRENT_TIMESTAMP;

-- Supplier Purchase Order
INSERT INTO orders (order_type, supplier_id, order_status, order_date, created_by_user_id)
VALUES ('Supplier', (SELECT id FROM suppliers WHERE supplier_name='Tokyo Beans Co.' LIMIT 1), 'Confirmed', NOW(), @uid);
SET @po1 := LAST_INSERT_ID();

INSERT INTO order_items (order_id, item_id, quantity_ordered, unit_price) VALUES
(@po1, (SELECT id FROM inventory_items WHERE item_name='Arabica Beans 1kg' LIMIT 1), 10.00, 12.50),
(@po1, (SELECT id FROM inventory_items WHERE item_name='House Blend Beans 1kg' LIMIT 1), 8.00, 10.00);

-- Customer Sale Order
INSERT INTO orders (order_type, supplier_id, order_status, order_date, created_by_user_id)
VALUES ('Customer', NULL, 'Confirmed', NOW(), @uid);
SET @so1 := LAST_INSERT_ID();

INSERT INTO order_items (order_id, item_id, quantity_ordered, unit_price) VALUES
(@so1, (SELECT id FROM inventory_items WHERE item_name='Whole Milk 1L' LIMIT 1), 6.00, 1.80),
(@so1, (SELECT id FROM inventory_items WHERE item_name='Croissant Dough Box' LIMIT 1), 12.00, 0.75);

-- Activity log entries
INSERT INTO activity_log (user_id, activity_type, details)
VALUES
(@uid, 'Stock Update', 'Initial sample stock levels seeded'),
(@uid, 'Order Created', CONCAT('Supplier PO #', @po1, ' created for beans')),
(@uid, 'Order Created', CONCAT('Customer SO #', @so1, ' created for milk & croissants'));

