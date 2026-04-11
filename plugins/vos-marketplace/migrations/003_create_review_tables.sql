-- VosCMS Marketplace - Review Tables
-- 아이템 리뷰, 별점

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_mp_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT UNSIGNED NOT NULL,
    `admin_id` CHAR(36) NOT NULL,
    `order_item_id` INT UNSIGNED DEFAULT NULL COMMENT '구매 인증 증빙',
    `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5 COMMENT '1-5',
    `title` VARCHAR(200) DEFAULT NULL,
    `content` TEXT DEFAULT NULL,
    `is_verified_purchase` TINYINT(1) DEFAULT 0,
    `helpful_count` INT UNSIGNED DEFAULT 0,
    `status` ENUM('active', 'hidden', 'reported') DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_admin_item` (`admin_id`, `item_id`),
    KEY `idx_item` (`item_id`),
    KEY `idx_rating` (`rating`),
    KEY `idx_status` (`status`),
    KEY `idx_verified` (`is_verified_purchase`),
    CONSTRAINT `fk_mpr_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mp_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
