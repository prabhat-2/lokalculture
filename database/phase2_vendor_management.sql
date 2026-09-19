USE lokal_culture;

ALTER TABLE vendors
    ADD COLUMN region VARCHAR(120) NULL AFTER state,
    ADD COLUMN craft_specialty VARCHAR(120) NULL AFTER region,
    ADD COLUMN gstin VARCHAR(30) NULL AFTER craft_specialty,
    ADD COLUMN gst_status ENUM('not_provided', 'pending', 'verified', 'rejected') NOT NULL DEFAULT 'not_provided' AFTER gstin,
    ADD COLUMN gst_document_path VARCHAR(255) NULL AFTER gst_status,
    ADD COLUMN registered_address VARCHAR(255) NULL AFTER gst_document_path,
    ADD COLUMN postal_code VARCHAR(20) NULL AFTER registered_address,
    ADD COLUMN is_active BOOLEAN NOT NULL DEFAULT FALSE AFTER commission_rate,
    ADD KEY idx_vendors_status (verification_status, is_active);

UPDATE vendors SET is_active = 1 WHERE verification_status = 'approved';
UPDATE vendors SET state = 'Rajasthan', region = 'Jaipur, Rajasthan', craft_specialty = 'Handwoven textiles' WHERE slug = 'mitti-and-loom';
UPDATE vendors SET state = 'Telangana', region = 'Hyderabad, Telangana', craft_specialty = 'Crafted homeware' WHERE slug = 'deccan-foundry';
UPDATE vendors SET state = 'Karnataka', region = 'Bengaluru, Karnataka', craft_specialty = 'Modern Indian art' WHERE slug = 'canvas-art';