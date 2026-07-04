CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `url_path` VARCHAR(255) UNIQUE NOT NULL,
    `lang` VARCHAR(10) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `meta_description` TEXT,
    `content_html` LONGTEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
