-- ============================================================================
-- VosCMS Core: Plugin System
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_plugins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plugin_id` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `version` VARCHAR(20) NOT NULL DEFAULT '1.0.0',
    `author` VARCHAR(200) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT 'general',
    `is_active` TINYINT(1) NOT NULL DEFAULT 0,
    `settings` JSON DEFAULT NULL,
    `installed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_plugin_migrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plugin_id` VARCHAR(100) NOT NULL,
    `migration_file` VARCHAR(255) NOT NULL,
    `executed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_plugin_migration` (`plugin_id`, `migration_file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_plugin_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `plugin_id` VARCHAR(100) NOT NULL,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    UNIQUE KEY `uk_plugin_setting` (`plugin_id`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
