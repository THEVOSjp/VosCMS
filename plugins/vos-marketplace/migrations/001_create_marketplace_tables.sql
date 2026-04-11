-- VosCMS Marketplace - Core Tables
-- 마켓플레이스 아이템, 버전, 카테고리, 주문

SET NAMES utf8mb4;

-- ─── 카테고리 ───
CREATE TABLE IF NOT EXISTS `rzx_mp_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL,
    `name` JSON NOT NULL COMMENT '{"en":"...","ko":"...","ja":"..."}',
    `description` JSON DEFAULT NULL,
    `icon` VARCHAR(200) DEFAULT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `item_count` INT UNSIGNED DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 마켓플레이스 아이템 ───
CREATE TABLE IF NOT EXISTS `rzx_mp_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `remote_id` VARCHAR(100) DEFAULT NULL COMMENT '원격 마켓 서버 아이템 ID',
    `slug` VARCHAR(150) NOT NULL,
    `type` ENUM('plugin', 'theme', 'widget', 'skin') NOT NULL,
    `name` JSON NOT NULL COMMENT '{"en":"...","ko":"...","ja":"..."}',
    `description` JSON DEFAULT NULL,
    `short_description` JSON DEFAULT NULL,
    `author_name` VARCHAR(200) DEFAULT NULL,
    `author_url` VARCHAR(500) DEFAULT NULL,
    `seller_id` INT UNSIGNED DEFAULT NULL COMMENT 'Phase 2: 판매자 계정',
    `category_id` INT UNSIGNED DEFAULT NULL,
    `tags` JSON DEFAULT NULL,
    `icon` VARCHAR(500) DEFAULT NULL,
    `banner_image` VARCHAR(500) DEFAULT NULL,
    `screenshots` JSON DEFAULT NULL COMMENT 'URL 배열',
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '0 = 무료',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
    `sale_price` DECIMAL(10,2) DEFAULT NULL,
    `sale_ends_at` DATETIME DEFAULT NULL,
    `latest_version` VARCHAR(20) DEFAULT '1.0.0',
    `min_voscms_version` VARCHAR(20) DEFAULT NULL,
    `min_php_version` VARCHAR(20) DEFAULT NULL,
    `requires_plugins` JSON DEFAULT NULL COMMENT '의존 플러그인 ID 배열',
    `download_count` INT UNSIGNED DEFAULT 0,
    `rating_avg` DECIMAL(2,1) DEFAULT 0.0,
    `rating_count` INT UNSIGNED DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `is_verified` TINYINT(1) DEFAULT 0,
    `status` ENUM('draft', 'active', 'suspended', 'archived') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`),
    UNIQUE KEY `uk_remote_id` (`remote_id`),
    KEY `idx_type` (`type`),
    KEY `idx_category` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_featured` (`is_featured`),
    KEY `idx_price` (`price`),
    KEY `idx_rating` (`rating_avg` DESC),
    KEY `idx_downloads` (`download_count` DESC),
    CONSTRAINT `fk_mpi_category` FOREIGN KEY (`category_id`) REFERENCES `rzx_mp_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 아이템 버전 ───
CREATE TABLE IF NOT EXISTS `rzx_mp_item_versions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT UNSIGNED NOT NULL,
    `version` VARCHAR(20) NOT NULL,
    `changelog` TEXT DEFAULT NULL,
    `download_url` VARCHAR(500) DEFAULT NULL COMMENT '서명된 URL 또는 경로',
    `file_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA-256 해시',
    `file_size` INT UNSIGNED DEFAULT NULL COMMENT 'bytes',
    `min_voscms_version` VARCHAR(20) DEFAULT NULL,
    `min_php_version` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('draft', 'active', 'yanked') DEFAULT 'active',
    `released_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_item_version` (`item_id`, `version`),
    KEY `idx_item` (`item_id`),
    CONSTRAINT `fk_mpv_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mp_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 주문 ───
CREATE TABLE IF NOT EXISTS `rzx_mp_orders` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `admin_id` CHAR(36) NOT NULL COMMENT '구매자 admin ID',
    `payment_id` INT UNSIGNED DEFAULT NULL COMMENT 'FK: rzx_payments.id',
    `order_number` VARCHAR(64) NOT NULL COMMENT '주문번호 (MKT-YYYYMMDD-XXXX)',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `discount` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `total` DECIMAL(10,2) NOT NULL DEFAULT 0,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'USD',
    `coupon_code` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('pending', 'paid', 'refunded', 'cancelled') DEFAULT 'pending',
    `paid_at` DATETIME DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_uuid` (`uuid`),
    UNIQUE KEY `uk_order_number` (`order_number`),
    KEY `idx_admin` (`admin_id`),
    KEY `idx_payment` (`payment_id`),
    KEY `idx_status` (`status`),
    KEY `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 주문 항목 ───
CREATE TABLE IF NOT EXISTS `rzx_mp_order_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `version_id` INT UNSIGNED DEFAULT NULL,
    `item_name` VARCHAR(200) NOT NULL COMMENT '구매 시점 스냅샷',
    `item_type` ENUM('plugin', 'theme', 'widget', 'skin') NOT NULL,
    `item_slug` VARCHAR(150) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `discount` DECIMAL(10,2) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_order` (`order_id`),
    KEY `idx_item` (`item_id`),
    CONSTRAINT `fk_mpoi_order` FOREIGN KEY (`order_id`) REFERENCES `rzx_mp_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 기본 카테고리 데이터 ───
INSERT INTO `rzx_mp_categories` (`slug`, `name`, `icon`, `sort_order`) VALUES
('business', '{"ko":"비즈니스","en":"Business","ja":"ビジネス"}', 'briefcase', 1),
('design', '{"ko":"디자인","en":"Design","ja":"デザイン"}', 'palette', 2),
('marketing', '{"ko":"마케팅","en":"Marketing","ja":"マーケティング"}', 'megaphone', 3),
('social', '{"ko":"소셜","en":"Social","ja":"ソーシャル"}', 'users', 4),
('utility', '{"ko":"유틸리티","en":"Utility","ja":"ユーティリティ"}', 'wrench', 5),
('content', '{"ko":"콘텐츠","en":"Content","ja":"コンテンツ"}', 'document', 6),
('ecommerce', '{"ko":"이커머스","en":"E-Commerce","ja":"Eコマース"}', 'shopping-bag', 7),
('analytics', '{"ko":"분석","en":"Analytics","ja":"アナリティクス"}', 'chart', 8)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
