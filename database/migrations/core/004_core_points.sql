-- ============================================================================
-- VosCMS Core: Point System
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_point_levels` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `min_points` INT DEFAULT 0,
    `icon` VARCHAR(100) DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_member_points` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `total_points` INT DEFAULT 0,
    `available_points` INT DEFAULT 0,
    `used_points` INT DEFAULT 0,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_point_transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `points` INT NOT NULL,
    `type` VARCHAR(30) DEFAULT 'earn',
    `reason` VARCHAR(500) DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` VARCHAR(50) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
