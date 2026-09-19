USE lokal_culture;

CREATE TABLE IF NOT EXISTS cultural_content (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(210) NOT NULL UNIQUE,
    excerpt VARCHAR(255) NULL,
    body TEXT NOT NULL,
    media_type ENUM('story', 'photo', 'video') NOT NULL DEFAULT 'story',
    media_path VARCHAR(255) NULL,
    region VARCHAR(120) NULL,
    craft VARCHAR(120) NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    instagram_status ENUM('not_requested', 'queued', 'published', 'failed') NOT NULL DEFAULT 'not_requested',
    created_by BIGINT UNSIGNED NOT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_cultural_content_status (status, created_at),
    FOREIGN KEY (created_by) REFERENCES users(id)
);