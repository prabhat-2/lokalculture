USE lokal_culture;

CREATE TABLE IF NOT EXISTS hsn_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hsn_code VARCHAR(30) NOT NULL,
    description VARCHAR(255) NULL,
    gst_rate DECIMAL(5,2) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hsn_codes_code (hsn_code),
    KEY idx_hsn_codes_active (is_active)
);

INSERT INTO hsn_codes (hsn_code, description, gst_rate, is_active)
VALUES ('XYZ1234', 'Temporary marketplace HSN', 12.00, TRUE)
ON DUPLICATE KEY UPDATE description = VALUES(description), gst_rate = VALUES(gst_rate), is_active = VALUES(is_active);

ALTER TABLE vendors
    ADD COLUMN state VARCHAR(100) NULL AFTER description,
    ADD KEY idx_vendors_state (state);

ALTER TABLE products
    ADD COLUMN hsn_id BIGINT UNSIGNED NULL AFTER category_id,
    ADD KEY idx_products_hsn_id (hsn_id);

UPDATE products
SET hsn_id = (SELECT id FROM hsn_codes WHERE hsn_code = 'XYZ1234')
WHERE hsn_id IS NULL;

ALTER TABLE products
    MODIFY COLUMN hsn_id BIGINT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_products_hsn FOREIGN KEY (hsn_id) REFERENCES hsn_codes(id);

ALTER TABLE order_items
    ADD COLUMN hsn_code VARCHAR(30) NULL AFTER product_name,
    ADD COLUMN gst_rate DECIMAL(5,2) NULL AFTER hsn_code,
    ADD COLUMN taxable_amount DECIMAL(12,2) NULL AFTER unit_price,
    ADD COLUMN cgst_rate DECIMAL(5,2) NULL AFTER gst_rate,
    ADD COLUMN cgst_amount DECIMAL(12,2) NULL AFTER cgst_rate,
    ADD COLUMN sgst_rate DECIMAL(5,2) NULL AFTER cgst_amount,
    ADD COLUMN sgst_amount DECIMAL(12,2) NULL AFTER sgst_rate,
    ADD COLUMN igst_rate DECIMAL(5,2) NULL AFTER sgst_amount,
    ADD COLUMN igst_amount DECIMAL(12,2) NULL AFTER igst_rate,
    ADD COLUMN gst_amount DECIMAL(12,2) NULL AFTER igst_amount;