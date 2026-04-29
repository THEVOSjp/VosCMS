-- ============================================================================
-- VosCMS Base Tables Migration (core only — reservation 분리 후)
-- 핵심 5개 테이블: admins, users, member_grades, settings, migrations
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Admins Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_admins` (
    `id` CHAR(36) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('super', 'admin', 'manager') DEFAULT 'admin',
    `permissions` JSON NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Users Table (Customers)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_users` (
    `id` CHAR(36) NOT NULL,
    `email` VARCHAR(255) NULL,
    `password` VARCHAR(255) NULL,
    `name` VARCHAR(100) NOT NULL,
    `nick_name` VARCHAR(100) NULL,
    `phone` VARCHAR(30) NULL,
    `birth_date` DATE NULL,
    `gender` ENUM('M', 'F', 'O') NULL,
    `address` VARCHAR(500) NULL,
    `notes` TEXT NULL,
    `total_reservations` INT DEFAULT 0,
    `total_spent` DECIMAL(12,2) DEFAULT 0,
    `points_balance` DECIMAL(12,2) DEFAULT 0,
    `grade_id` CHAR(36) NULL,
    `tags` JSON NULL,
    `is_blocked` TINYINT(1) DEFAULT 0,
    `last_visited_at` TIMESTAMP NULL,
    `marketing_consent` TINYINT(1) DEFAULT 0,
    `email_verified_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_phone` (`phone`),
    KEY `idx_grade` (`grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Member Grades Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_member_grades` (
    `id` CHAR(36) NOT NULL,
    `name` VARCHAR(50) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `min_reservations` INT DEFAULT 0,
    `min_spent` DECIMAL(12,2) DEFAULT 0,
    `discount_rate` DECIMAL(5,2) DEFAULT 0 COMMENT 'percentage',
    `point_rate` DECIMAL(5,2) DEFAULT 0 COMMENT 'percentage',
    `color` VARCHAR(7) DEFAULT '#6B7280',
    `sort_order` INT DEFAULT 0,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Settings Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_settings` (
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Migrations Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_migrations` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `migration` VARCHAR(255) NOT NULL,
    `batch` INT NOT NULL,
    `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Initial Data (core only)
-- ============================================================================

-- Default member grade
INSERT INTO `rzx_member_grades` (`id`, `name`, `slug`, `min_reservations`, `min_spent`, `discount_rate`, `point_rate`, `color`, `sort_order`, `is_default`)
VALUES (UUID(), '일반', 'normal', 0, 0, 0, 2.00, '#6B7280', 0, 1);

-- Default settings (core only — reservation 관련 시드는 015 로 이동)
INSERT INTO `rzx_settings` (`key`, `value`) VALUES
    ('site_name', 'VosCMS'),
    ('site_url', 'http://localhost'),
    ('admin_path', 'admin'),
    ('timezone', 'Asia/Seoul'),
    ('locale', 'ko'),
    ('version', '1.0.0');

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('001_create_base_tables', 1);
