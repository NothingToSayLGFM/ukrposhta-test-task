CREATE TABLE IF NOT EXISTS post_indexes (
    post_code VARCHAR(10) PRIMARY KEY,

    region VARCHAR(255) NOT NULL,
    district_old VARCHAR(255) NULL,
    district_new VARCHAR(255) NOT NULL,
    city VARCHAR(255) NOT NULL,
    post_office VARCHAR(255) NOT NULL,

    source ENUM('import','manual') NOT NULL DEFAULT 'import',

    hash CHAR(32) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_city (city),
    INDEX idx_district_new (district_new),
    INDEX idx_post_office (post_office)
);