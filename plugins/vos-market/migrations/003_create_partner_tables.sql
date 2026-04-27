-- vos-market: 파트너 계정 / 수익 / 정산
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_mkt_partners` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255) DEFAULT NULL,
    `display_name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `url` VARCHAR(500) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `company` VARCHAR(200) DEFAULT NULL,
    `github` VARCHAR(200) DEFAULT NULL,
    `type` ENUM('general','verified','partner') DEFAULT 'general',
    `commission_rate` DECIMAL(5,2) DEFAULT 20.00,
    `bank_info` JSON DEFAULT NULL,
    `tax_info` JSON DEFAULT NULL,
    `status` ENUM('pending','active','suspended','rejected') DEFAULT 'pending',
    `verification_note` VARCHAR(500) DEFAULT NULL,
    `is_verified` TINYINT(1) DEFAULT 0,
    `item_count` INT UNSIGNED DEFAULT 0,
    `total_earnings` DECIMAL(12,2) DEFAULT 0.00,
    `total_paid` DECIMAL(12,2) DEFAULT 0.00,
    `pending_balance` DECIMAL(12,2) DEFAULT 0.00,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_status` (`status`),
    KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_partner_earnings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `partner_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED DEFAULT NULL,
    `order_item_id` INT UNSIGNED DEFAULT NULL,
    `item_name` VARCHAR(200) DEFAULT NULL,
    `buyer_domain` VARCHAR(255) DEFAULT NULL,
    `gross_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `commission` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `currency` VARCHAR(3) DEFAULT 'JPY',
    `status` ENUM('pending','confirmed','paid','cancelled') DEFAULT 'pending',
    `payout_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_partner` (`partner_id`),
    KEY `idx_item` (`item_id`),
    KEY `idx_status` (`status`),
    KEY `idx_payout` (`payout_id`),
    CONSTRAINT `fk_mkt_earn_partner` FOREIGN KEY (`partner_id`) REFERENCES `rzx_mkt_partners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_payouts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `partner_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `currency` VARCHAR(3) DEFAULT 'JPY',
    `method` VARCHAR(50) DEFAULT NULL,
    `reference` VARCHAR(200) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `status` ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `processed_at` DATETIME DEFAULT NULL,
    KEY `idx_partner` (`partner_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_mkt_payout_partner` FOREIGN KEY (`partner_id`) REFERENCES `rzx_mkt_partners`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_audit_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `actor_type` ENUM('admin','partner','system') DEFAULT 'system',
    `actor_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` INT UNSIGNED DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `metadata` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_actor` (`actor_type`,`actor_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
