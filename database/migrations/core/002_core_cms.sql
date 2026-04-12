-- ============================================================================
-- VosCMS Core: CMS (Pages, Menus, Widgets, Translations)
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL,
    `value` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_translations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `lang_key` VARCHAR(255) NOT NULL,
    `locale` VARCHAR(10) NOT NULL,
    `source_locale` VARCHAR(10) DEFAULT NULL,
    `content` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_key_locale` (`lang_key`, `locale`),
    KEY `idx_locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_page_contents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(100) NOT NULL,
    `page_type` VARCHAR(20) DEFAULT 'document',
    `locale` VARCHAR(10) DEFAULT 'ko',
    `title` VARCHAR(500) DEFAULT NULL,
    `content` LONGTEXT DEFAULT NULL,
    `seo_title` VARCHAR(500) DEFAULT NULL,
    `seo_description` VARCHAR(500) DEFAULT NULL,
    `seo_keywords` VARCHAR(500) DEFAULT NULL,
    `og_image` VARCHAR(500) DEFAULT NULL,
    `is_system` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_slug` (`page_slug`),
    KEY `idx_locale` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_page_widgets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_slug` VARCHAR(100) NOT NULL,
    `widget_id` INT UNSIGNED DEFAULT 0,
    `widget_slug` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `config` JSON DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_page` (`page_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_widgets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `title` VARCHAR(200) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `type` VARCHAR(20) DEFAULT 'builtin',
    `category` VARCHAR(50) DEFAULT 'general',
    `icon` VARCHAR(100) DEFAULT NULL,
    `version` VARCHAR(20) DEFAULT '1.0.0',
    `author` VARCHAR(200) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `config_schema` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_widget_marketplace` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `thumbnail` VARCHAR(500) DEFAULT NULL,
    `download_url` VARCHAR(500) DEFAULT NULL,
    `version` VARCHAR(20) DEFAULT '1.0.0',
    `author` VARCHAR(200) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT 'general',
    `is_installed` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_sitemaps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_menu_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sitemap_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `title` VARCHAR(200) NOT NULL,
    `url` VARCHAR(500) DEFAULT NULL,
    `target` VARCHAR(10) DEFAULT '_self',
    `icon` VARCHAR(100) DEFAULT NULL,
    `css_class` VARCHAR(200) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `menu_type` VARCHAR(20) DEFAULT 'page',
    `sort_order` INT DEFAULT 0,
    `is_shortcut` TINYINT(1) DEFAULT 0,
    `open_window` TINYINT(1) DEFAULT 0,
    `expand` TINYINT(1) DEFAULT 0,
    `group_srls` VARCHAR(500) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_sitemap` (`sitemap_id`),
    KEY `idx_parent` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_push_subscribers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) DEFAULT NULL,
    `endpoint` TEXT NOT NULL,
    `p256dh` VARCHAR(500) DEFAULT NULL,
    `auth` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_push_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `body` TEXT DEFAULT NULL,
    `url` VARCHAR(500) DEFAULT NULL,
    `sent_count` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_admin_memos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` CHAR(36) NOT NULL,
    `target_type` VARCHAR(50) DEFAULT NULL,
    `target_id` VARCHAR(50) DEFAULT NULL,
    `content` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration` VARCHAR(255) NOT NULL,
    `version` VARCHAR(20) DEFAULT NULL,
    `executed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
