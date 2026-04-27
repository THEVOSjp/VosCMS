-- vos-market: 카테고리 / 아이템 / 버전 / 주문
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_mkt_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL,
    `name` JSON NOT NULL,
    `description` JSON DEFAULT NULL,
    `icon` VARCHAR(200) DEFAULT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `item_count` INT UNSIGNED DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(150) NOT NULL,
    `type` ENUM('plugin','theme','widget','skin') NOT NULL,
    `name` JSON NOT NULL,
    `description` JSON DEFAULT NULL,
    `short_description` JSON DEFAULT NULL,
    `author_name` VARCHAR(200) DEFAULT NULL,
    `author_url` VARCHAR(500) DEFAULT NULL,
    `repo_url` VARCHAR(500) DEFAULT NULL,
    `demo_url` VARCHAR(500) DEFAULT NULL,
    `partner_id` INT UNSIGNED DEFAULT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `tags` JSON DEFAULT NULL,
    `icon` VARCHAR(500) DEFAULT NULL,
    `banner_image` VARCHAR(500) DEFAULT NULL,
    `screenshots` JSON DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'JPY',
    `sale_price` DECIMAL(10,2) DEFAULT NULL,
    `sale_ends_at` DATETIME DEFAULT NULL,
    `latest_version` VARCHAR(20) DEFAULT '1.0.0',
    `min_voscms_version` VARCHAR(20) DEFAULT NULL,
    `min_php_version` VARCHAR(20) DEFAULT NULL,
    `requires_plugins` JSON DEFAULT NULL,
    `license` VARCHAR(50) DEFAULT NULL,
    `download_count` INT UNSIGNED DEFAULT 0,
    `rating_avg` DECIMAL(2,1) DEFAULT 0.0,
    `rating_count` INT UNSIGNED DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_verified` TINYINT(1) DEFAULT 0,
    `status` ENUM('draft','pending','active','suspended','archived') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_type` (`type`),
    KEY `idx_category` (`category_id`),
    KEY `idx_partner` (`partner_id`),
    KEY `idx_status` (`status`),
    KEY `idx_featured` (`is_featured`),
    KEY `idx_price` (`price`),
    KEY `idx_downloads` (`download_count` DESC),
    KEY `idx_rating` (`rating_avg` DESC),
    CONSTRAINT `fk_mkt_item_cat` FOREIGN KEY (`category_id`) REFERENCES `rzx_mkt_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_item_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT UNSIGNED NOT NULL,
    `version` VARCHAR(20) NOT NULL,
    `changelog` TEXT DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_hash` VARCHAR(64) DEFAULT NULL,
    `file_size` INT UNSIGNED DEFAULT NULL,
    `min_voscms_version` VARCHAR(20) DEFAULT NULL,
    `min_php_version` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('draft','active','yanked') DEFAULT 'active',
    `released_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_item_ver` (`item_id`,`version`),
    KEY `idx_item` (`item_id`),
    CONSTRAINT `fk_mkt_ver_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mkt_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `order_number` VARCHAR(64) NOT NULL,
    `buyer_site_url` VARCHAR(255) DEFAULT NULL,
    `buyer_email` VARCHAR(255) DEFAULT NULL,
    `payment_ref` VARCHAR(100) DEFAULT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'JPY',
    `coupon_code` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('pending','paid','refunded','cancelled') DEFAULT 'pending',
    `paid_at` DATETIME DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_uuid` (`uuid`),
    UNIQUE KEY `uk_order_num` (`order_number`),
    KEY `idx_buyer_email` (`buyer_email`),
    KEY `idx_status` (`status`),
    KEY `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `version_id` INT UNSIGNED DEFAULT NULL,
    `item_name` VARCHAR(200) NOT NULL,
    `item_type` ENUM('plugin','theme','widget','skin') NOT NULL,
    `item_slug` VARCHAR(150) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `discount` DECIMAL(10,2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_order` (`order_id`),
    KEY `idx_item` (`item_id`),
    CONSTRAINT `fk_mkt_oi_order` FOREIGN KEY (`order_id`) REFERENCES `rzx_mkt_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT UNSIGNED NOT NULL,
    `reviewer_name` VARCHAR(100) DEFAULT NULL,
    `reviewer_domain` VARCHAR(255) DEFAULT NULL,
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `body` TEXT DEFAULT NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `status` ENUM('pending','approved','rejected') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_item` (`item_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_mkt_rev_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mkt_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rzx_mkt_categories` (`slug`,`name`,`icon`,`sort_order`) VALUES
('business',  '{"ko":"비즈니스","en":"Business","ja":"ビジネス"}',     'briefcase',    1),
('design',    '{"ko":"디자인","en":"Design","ja":"デザイン"}',         'palette',      2),
('marketing', '{"ko":"마케팅","en":"Marketing","ja":"マーケティング"}', 'megaphone',    3),
('social',    '{"ko":"소셜","en":"Social","ja":"ソーシャル"}',         'users',        4),
('utility',   '{"ko":"유틸리티","en":"Utility","ja":"ユーティリティ"}', 'wrench',       5),
('content',   '{"ko":"콘텐츠","en":"Content","ja":"コンテンツ"}',      'document-text',6),
('ecommerce', '{"ko":"이커머스","en":"E-Commerce","ja":"Eコマース"}',  'shopping-bag', 7),
('analytics', '{"ko":"분석","en":"Analytics","ja":"アナリティクス"}',  'chart-bar',    8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
