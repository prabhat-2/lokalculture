USE lokal_culture;

CREATE TABLE IF NOT EXISTS payment_webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id VARCHAR(190) NOT NULL UNIQUE,
    event_type VARCHAR(80) NOT NULL,
    payload JSON NOT NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE shipments
    ADD COLUMN vendor_id BIGINT UNSIGNED NULL AFTER order_id,
    ADD COLUMN pickup_address VARCHAR(255) NULL AFTER tracking_number,
    ADD COLUMN delivery_address VARCHAR(255) NULL AFTER pickup_address,
    ADD KEY idx_shipments_vendor (vendor_id),
    DROP INDEX order_id,
    ADD UNIQUE KEY order_vendor (order_id, vendor_id),
    ADD CONSTRAINT fk_shipments_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id);

CREATE TABLE IF NOT EXISTS returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    status ENUM('requested', 'approved', 'rejected', 'refunded') NOT NULL DEFAULT 'requested',
    reason VARCHAR(255) NOT NULL,
    refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    inventory_restored BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    UNIQUE KEY one_return_per_order (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id)
);