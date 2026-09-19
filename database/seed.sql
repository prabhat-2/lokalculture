USE lokal_culture;

INSERT INTO users (role_id, name, email, password_hash)
SELECT id, 'Mitti & Loom', 'mitti@example.test', 'seed-placeholder'
FROM roles WHERE name = 'vendor'
AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'mitti@example.test');

INSERT INTO users (role_id, name, email, password_hash)
SELECT id, 'Deccan Foundry', 'deccan@example.test', 'seed-placeholder'
FROM roles WHERE name = 'vendor'
AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'deccan@example.test');

INSERT INTO users (role_id, name, email, password_hash)
SELECT id, 'Canvas Art', 'canvas@example.test', 'seed-placeholder'
FROM roles WHERE name = 'vendor'
AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'canvas@example.test');

UPDATE users SET password_hash = '$2y$12$bStbBj6VCG0TF6upeiv2KeZcZlYIdum4XslwlTtRzDuYGh.Ya895.'
WHERE email IN ('mitti@example.test', 'deccan@example.test', 'canvas@example.test') AND password_hash = 'seed-placeholder';

INSERT INTO users (role_id, name, email, password_hash)
SELECT id, 'Lokal Admin', 'admin@example.test', '$2y$12$bStbBj6VCG0TF6upeiv2KeZcZlYIdum4XslwlTtRzDuYGh.Ya895.'
FROM roles WHERE name = 'administrator'
AND NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@example.test');

INSERT INTO vendors (user_id, shop_name, slug, description, state, region, craft_specialty, verification_status, is_active)
SELECT u.id, 'Mitti & Loom', 'mitti-and-loom', 'Handwoven textiles', 'Rajasthan', 'Jaipur, Rajasthan', 'Handwoven textiles', 'approved', 1
FROM users u WHERE u.email = 'mitti@example.test'
AND NOT EXISTS (SELECT 1 FROM vendors WHERE slug = 'mitti-and-loom');
INSERT INTO vendors (user_id, shop_name, slug, description, state, region, craft_specialty, verification_status, is_active)
SELECT u.id, 'Deccan Foundry', 'deccan-foundry', 'Crafted homeware', 'Telangana', 'Hyderabad, Telangana', 'Crafted homeware', 'approved', 1
FROM users u WHERE u.email = 'deccan@example.test'
AND NOT EXISTS (SELECT 1 FROM vendors WHERE slug = 'deccan-foundry');
INSERT INTO vendors (user_id, shop_name, slug, description, state, region, craft_specialty, verification_status, is_active)
SELECT u.id, 'Canvas Art', 'canvas-art', 'Modern Indian art', 'Karnataka', 'Bengaluru, Karnataka', 'Modern Indian art', 'approved', 1
FROM users u WHERE u.email = 'canvas@example.test'
AND NOT EXISTS (SELECT 1 FROM vendors WHERE slug = 'canvas-art');
INSERT INTO categories (name, slug, is_active)
SELECT 'Traditional clothing', 'traditional-clothing', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'traditional-clothing');
INSERT INTO categories (name, slug, is_active)
SELECT 'Traditional footwear', 'traditional-footwear', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'traditional-footwear');
INSERT INTO categories (name, slug, is_active)
SELECT 'Home furnishing', 'home-furnishing', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'home-furnishing');
INSERT INTO categories (name, slug, is_active)
SELECT 'Fashion accessories', 'fashion-accessories', 1
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE slug = 'fashion-accessories');

INSERT INTO hsn_codes (hsn_code, description, gst_rate, is_active)
VALUES ('XYZ1234', 'Temporary marketplace HSN', 12.00, 1)
ON DUPLICATE KEY UPDATE description = VALUES(description), gst_rate = VALUES(gst_rate), is_active = VALUES(is_active);

INSERT INTO products (vendor_id, category_id, hsn_id, name, slug, sku, description, price, status)
SELECT v.id, c.id, h.id, 'Rangoli Edit', 'rangoli-edit', 'LC-RANGOLI-001', 'Festive saree edit', 2499, 'published'
FROM vendors v, categories c, hsn_codes h
WHERE v.slug = 'mitti-and-loom' AND c.slug = 'traditional-clothing'
AND h.hsn_code = 'XYZ1234'
AND NOT EXISTS (SELECT 1 FROM products WHERE slug = 'rangoli-edit');

INSERT INTO products (vendor_id, category_id, hsn_id, name, slug, sku, description, price, status)
SELECT v.id, c.id, h.id, 'The Green Room', 'the-green-room', 'LC-GREEN-001', 'Contemporary kurta set', 1899, 'published'
FROM vendors v, categories c, hsn_codes h
WHERE v.slug = 'mitti-and-loom' AND c.slug = 'traditional-clothing'
AND h.hsn_code = 'XYZ1234'
AND NOT EXISTS (SELECT 1 FROM products WHERE slug = 'the-green-room');

INSERT INTO products (vendor_id, category_id, hsn_id, name, slug, sku, description, price, status)
SELECT v.id, c.id, h.id, 'Deccan Stoneware', 'deccan-stoneware', 'LC-DECCAN-001', 'Hand-finished homeware', 1299, 'published'
FROM vendors v, categories c, hsn_codes h
WHERE v.slug = 'deccan-foundry' AND c.slug = 'home-furnishing'
AND h.hsn_code = 'XYZ1234'
AND NOT EXISTS (SELECT 1 FROM products WHERE slug = 'deccan-stoneware');

INSERT INTO products (vendor_id, category_id, hsn_id, name, slug, sku, description, price, status)
SELECT v.id, c.id, h.id, 'Canvas Stories', 'canvas-stories', 'LC-CANVAS-001', 'Modern Indian art print', 1599, 'published'
FROM vendors v, categories c, hsn_codes h
WHERE v.slug = 'canvas-art' AND c.slug = 'fashion-accessories'
AND h.hsn_code = 'XYZ1234'
AND NOT EXISTS (SELECT 1 FROM products WHERE slug = 'canvas-stories');

INSERT INTO product_images (product_id, path, sort_order)
SELECT id, 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?auto=format&fit=crop&w=700&q=85', 0
FROM products WHERE slug = 'rangoli-edit'
AND NOT EXISTS (SELECT 1 FROM product_images WHERE product_id = products.id AND sort_order = 0);

INSERT INTO product_images (product_id, path, sort_order)
SELECT id, 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=700&q=85', 0
FROM products WHERE slug = 'the-green-room'
AND NOT EXISTS (SELECT 1 FROM product_images WHERE product_id = products.id AND sort_order = 0);

INSERT INTO product_images (product_id, path, sort_order)
SELECT id, 'https://images.unsplash.com/photo-1610701596007-11502861dcfa?auto=format&fit=crop&w=400&q=85', 0
FROM products WHERE slug = 'deccan-stoneware'
AND NOT EXISTS (SELECT 1 FROM product_images WHERE product_id = products.id AND sort_order = 0);

INSERT INTO product_images (product_id, path, sort_order)
SELECT id, 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=700&q=85', 0
FROM products WHERE slug = 'canvas-stories'
AND NOT EXISTS (SELECT 1 FROM product_images WHERE product_id = products.id AND sort_order = 0);

INSERT INTO inventory (product_id, available_quantity)
SELECT id, 25 FROM products
WHERE slug IN ('rangoli-edit', 'the-green-room')
AND NOT EXISTS (SELECT 1 FROM inventory WHERE product_id = products.id);

INSERT INTO inventory (product_id, available_quantity)
SELECT id, 25 FROM products
WHERE slug IN ('deccan-stoneware', 'canvas-stories')
AND NOT EXISTS (SELECT 1 FROM inventory WHERE product_id = products.id);