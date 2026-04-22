-- VosCMS Marketplace - License Tables
-- 라이선스 키, 도메인별 활성화

SET NAMES utf8mb4;

-- ─── 라이선스 ───
CREATE TABLE IF NOT EXISTS `rzx_mp_licenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_key` CHAR(36) NOT NULL COMMENT 'UUID 형식 라이선스 키',
    `order_item_id` INT UNSIGNED DEFAULT NULL,
    `item_id` INT UNSIGNED NOT NULL,
    `admin_id` CHAR(36) NOT NULL COMMENT '라이선스 소유자',
    `type` ENUM('single', 'unlimited', 'subscription') DEFAULT 'single',
    `max_activations` INT UNSIGNED DEFAULT 1,
    `status` ENUM('active', 'expired', 'revoked', 'suspended') DEFAULT 'active',
    `expires_at` DATETIME DEFAULT NULL COMMENT 'NULL = 영구',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_license_key` (`license_key`),
    KEY `idx_item` (`item_id`),
    KEY `idx_admin` (`admin_id`),
    KEY `idx_order_item` (`order_item_id`),
    KEY `idx_status` (`status`),
    KEY `idx_expires` (`expires_at`),
    CONSTRAINT `fk_mpl_item` FOREIGN KEY (`item_id`) REFERENCES `rzx_mp_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 라이선스 활성화 (도메인별) ───
CREATE TABLE IF NOT EXISTS `rzx_mp_license_activations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `license_id` INT UNSIGNED NOT NULL,
    `domain` VARCHAR(255) NOT NULL COMMENT '활성화 도메인 (정규화)',
    `instance_id` VARCHAR(64) DEFAULT NULL COMMENT '설치 고유 해시',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `voscms_version` VARCHAR(20) DEFAULT NULL,
    `activated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_check_at` DATETIME DEFAULT NULL,
    `deactivated_at` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    UNIQUE KEY `uk_license_domain` (`license_id`, `domain`),
    KEY `idx_license` (`license_id`),
    KEY `idx_domain` (`domain`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_mla_license` FOREIGN KEY (`license_id`) REFERENCES `rzx_mp_licenses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
