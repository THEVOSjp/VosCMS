-- vos-market: 아이템 심사 제출
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_mkt_submissions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `partner_id` INT UNSIGNED DEFAULT NULL,
    `item_id` INT UNSIGNED DEFAULT NULL,
    `is_update` TINYINT(1) DEFAULT 0,
    `item_type` ENUM('plugin','theme','widget','skin') NOT NULL,
    `submitted_slug` VARCHAR(150) DEFAULT NULL,
    `submitted_version` VARCHAR(20) DEFAULT NULL,
    `submitted_data` JSON DEFAULT NULL,
    `package_path` VARCHAR(500) DEFAULT NULL,
    `package_hash` VARCHAR(64) DEFAULT NULL,
    `package_size` INT UNSIGNED DEFAULT NULL,
    `status` ENUM('pending','reviewing','approved','rejected') DEFAULT 'pending',
    `reviewer_id` VARCHAR(64) DEFAULT NULL,
    `reviewer_note` TEXT DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_partner` (`partner_id`),
    KEY `idx_item` (`item_id`),
    KEY `idx_status` (`status`),
    KEY `idx_submitted` (`submitted_at` DESC),
    CONSTRAINT `fk_mkt_sub_partner` FOREIGN KEY (`partner_id`) REFERENCES `rzx_mkt_partners`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_mkt_sub_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mkt_items`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_api_keys` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `partner_id` INT UNSIGNED NOT NULL,
    `key_hash` VARCHAR(64) NOT NULL,
    `label` VARCHAR(100) DEFAULT NULL,
    `last_used_at` DATETIME DEFAULT NULL,
    `expires_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_key_hash` (`key_hash`),
    KEY `idx_partner` (`partner_id`),
    CONSTRAINT `fk_mkt_ak_partner` FOREIGN KEY (`partner_id`) REFERENCES `rzx_mkt_partners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
