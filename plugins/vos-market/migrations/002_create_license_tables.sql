-- vos-market: 라이선스 / 활성화
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_mkt_licenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_key` CHAR(36) NOT NULL,
    `order_item_id` INT UNSIGNED DEFAULT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `buyer_email` VARCHAR(255) DEFAULT NULL,
    `buyer_site_url` VARCHAR(255) DEFAULT NULL,
    `type` ENUM('single','unlimited','subscription') DEFAULT 'single',
    `max_activations` INT UNSIGNED DEFAULT 1,
    `status` ENUM('active','expired','revoked','suspended') DEFAULT 'active',
    `expires_at` DATETIME DEFAULT NULL,
    `note` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_license_key` (`license_key`),
    KEY `idx_item` (`item_id`),
    KEY `idx_buyer_email` (`buyer_email`),
    KEY `idx_status` (`status`),
    KEY `idx_expires` (`expires_at`),
    CONSTRAINT `fk_mkt_lic_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mkt_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_license_activations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` INT UNSIGNED NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `instance_id` VARCHAR(64) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `voscms_version` VARCHAR(20) DEFAULT NULL,
    `activated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_check_at` DATETIME DEFAULT NULL,
    `deactivated_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    UNIQUE KEY `uk_lic_domain` (`license_id`,`domain`),
    KEY `idx_license` (`license_id`),
    KEY `idx_domain` (`domain`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_mkt_la_lic` FOREIGN KEY (`license_id`) REFERENCES `rzx_mkt_licenses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
