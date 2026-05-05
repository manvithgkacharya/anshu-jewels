-- Sample Products for Testing
USE anshu_jewels;

-- Insert sample products
INSERT INTO products (category_id, title, slug, description, price, original_price, stock_quantity, sku, is_active, is_featured) VALUES
(1, 'Golden Pearl Necklace', 'golden-pearl-necklace', 'Elegant handcrafted necklace with golden pearls and intricate design. Perfect for special occasions.', 2499.00, 2999.00, 15, 'AJ-NK-001', 1, 1),
(2, 'Diamond Stud Earrings', 'diamond-stud-earrings', 'Beautiful diamond-studded earrings with 18k gold plating. Timeless elegance.', 1899.00, NULL, 20, 'AJ-ER-001', 1, 1),
(3, 'Silver Charm Bracelet', 'silver-charm-bracelet', 'Delicate silver bracelet with customizable charms. A perfect gift for loved ones.', 1299.00, 1499.00, 25, 'AJ-BR-001', 1, 1),
(4, 'Rose Gold Ring', 'rose-gold-ring', 'Stunning rose gold ring with intricate floral patterns. Handcrafted with love.', 1599.00, NULL, 12, 'AJ-RG-001', 1, 1),
(5, 'Traditional Anklet', 'traditional-anklet', 'Traditional Indian anklet with bells and beads. Perfect for festive occasions.', 899.00, 1099.00, 30, 'AJ-AN-001', 1, 1),
(1, 'Emerald Pendant Necklace', 'emerald-pendant-necklace', 'Exquisite emerald pendant on a delicate gold chain. A statement piece.', 3499.00, NULL, 8, 'AJ-NK-002', 1, 1),
(2, 'Hoop Earrings Set', 'hoop-earrings-set', 'Set of 3 different sized hoop earrings in gold, silver, and rose gold.', 1199.00, 1399.00, 18, 'AJ-ER-002', 1, 0),
(3, 'Beaded Friendship Bracelet', 'beaded-friendship-bracelet', 'Colorful beaded bracelet perfect for everyday wear. Adjustable size.', 499.00, NULL, 50, 'AJ-BR-002', 1, 0),
(4, 'Vintage Style Ring', 'vintage-style-ring', 'Antique-inspired ring with detailed engravings. Unique and elegant.', 1799.00, 1999.00, 10, 'AJ-RG-002', 1, 0),
(5, 'Layered Anklet Chain', 'layered-anklet-chain', 'Modern layered anklet with multiple chains and small charms.', 699.00, NULL, 22, 'AJ-AN-002', 1, 0);

-- Insert sample product images (using placeholder URLs)
INSERT INTO product_images (product_id, image_url, is_primary, sort_order) VALUES
(1, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Golden+Pearl+Necklace', 1, 1),
(2, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Diamond+Stud+Earrings', 1, 1),
(3, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Silver+Charm+Bracelet', 1, 1),
(4, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Rose+Gold+Ring', 1, 1),
(5, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Traditional+Anklet', 1, 1),
(6, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Emerald+Pendant', 1, 1),
(7, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Hoop+Earrings', 1, 1),
(8, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Beaded+Bracelet', 1, 1),
(9, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Vintage+Ring', 1, 1),
(10, 'https://via.placeholder.com/600x600/f59e0b/ffffff?text=Layered+Anklet', 1, 1);

-- Insert sample coupon codes
INSERT INTO coupons (code, discount_type, discount_value, min_purchase_amount, usage_limit, valid_from, valid_until, is_active) VALUES
('WELCOME10', 'percentage', 10.00, 1000.00, 100, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1),
('FLAT500', 'flat', 500.00, 2000.00, 50, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY), 1),
('FESTIVE20', 'percentage', 20.00, 1500.00, 200, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 1);

-- Insert sample test user (password: test123)
INSERT INTO users (name, email, password, phone, is_active) VALUES
('Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98765 43210', 1);
