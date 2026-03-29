-- ============================================================================
-- RezlyX Additional Tables Migration
-- Tables added after initial release
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Service Categories Table (서비스 카테고리)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_service_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(100) NOT NULL COMMENT '카테고리명',
    `slug` VARCHAR(100) NOT NULL COMMENT 'URL 슬러그',
    `description` TEXT NULL COMMENT '설명',
    `image` VARCHAR(255) NULL COMMENT '이미지 경로',
    `parent_id` INT UNSIGNED NULL COMMENT '상위 카테고리 ID',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '정렬 순서',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `rzx_service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='서비스 카테고리';

-- -----------------------------------------------------------------------------
-- Translations Table (다국어 번역)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_translations` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `lang_key` VARCHAR(255) NOT NULL COMMENT '번역 키',
    `locale` VARCHAR(10) NOT NULL COMMENT '언어 코드',
    `source_locale` VARCHAR(5) NULL,
    `content` MEDIUMTEXT NOT NULL COMMENT '번역된 내용',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key_locale` (`lang_key`, `locale`),
    KEY `idx_lang_key` (`lang_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='다국어 번역 데이터';

-- -----------------------------------------------------------------------------
-- Widgets Table (위젯)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_widgets` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `type` ENUM('builtin', 'custom', 'marketplace') DEFAULT 'builtin',
    `category` VARCHAR(50) DEFAULT 'general',
    `icon` VARCHAR(50) DEFAULT 'cube',
    `version` VARCHAR(20) DEFAULT '1.0.0',
    `author` VARCHAR(255) DEFAULT 'RezlyX',
    `config_schema` LONGTEXT NULL,
    `default_config` LONGTEXT NULL,
    `template` TEXT NULL,
    `css` TEXT NULL,
    `js` TEXT NULL,
    `thumbnail` VARCHAR(500) NULL,
    `marketplace_id` VARCHAR(100) NULL,
    `price` DECIMAL(12,2) DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `installed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Page Widgets Table (페이지-위젯 연결)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_page_widgets` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `page_slug` VARCHAR(100) NOT NULL DEFAULT 'home',
    `widget_id` INT UNSIGNED NOT NULL,
    `sort_order` INT DEFAULT 0,
    `config` LONGTEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_page_sort` (`page_slug`, `sort_order`),
    KEY `idx_widget` (`widget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Page Contents Table (페이지 콘텐츠)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_page_contents` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `page_slug` VARCHAR(100) NOT NULL,
    `locale` VARCHAR(10) NOT NULL DEFAULT 'ko',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `content` LONGTEXT NULL,
    `is_system` TINYINT(1) DEFAULT 0 COMMENT 'system page cannot be deleted',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug_locale` (`page_slug`, `locale`),
    KEY `idx_slug` (`page_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Sitemaps Table (사이트맵)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_sitemaps` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Menu Items Table (메뉴 항목)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_menu_items` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `sitemap_id` INT UNSIGNED NOT NULL,
    `parent_id` INT UNSIGNED NULL,
    `title` VARCHAR(200) NOT NULL,
    `url` VARCHAR(500) DEFAULT '',
    `target` VARCHAR(20) DEFAULT '_self',
    `icon` VARCHAR(100) DEFAULT '',
    `css_class` VARCHAR(200) DEFAULT '',
    `description` VARCHAR(500) DEFAULT '',
    `menu_type` VARCHAR(50) DEFAULT 'page',
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_home` TINYINT(1) NOT NULL DEFAULT 0,
    `is_shortcut` TINYINT(1) NOT NULL DEFAULT 0,
    `open_window` TINYINT(1) NOT NULL DEFAULT 0,
    `expand` TINYINT(1) NOT NULL DEFAULT 0,
    `group_srls` VARCHAR(500) DEFAULT '',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `sitemap_id` (`sitemap_id`),
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `rzx_menu_items_ibfk_1` FOREIGN KEY (`sitemap_id`) REFERENCES `rzx_sitemaps` (`id`) ON DELETE CASCADE,
    CONSTRAINT `rzx_menu_items_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `rzx_menu_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Widget Marketplace Table (위젯 마켓플레이스)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_widget_marketplace` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `marketplace_id` VARCHAR(100) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `author` VARCHAR(255) NULL,
    `version` VARCHAR(20) DEFAULT '1.0.0',
    `category` VARCHAR(50) DEFAULT 'general',
    `price` DECIMAL(12,2) DEFAULT 0.00,
    `currency` VARCHAR(3) DEFAULT 'USD',
    `downloads` INT DEFAULT 0,
    `rating` DECIMAL(3,2) DEFAULT 0.00,
    `thumbnail` VARCHAR(500) NULL,
    `preview_url` VARCHAR(500) NULL,
    `package_url` VARCHAR(500) NULL,
    `config_schema` LONGTEXT NULL,
    `template` TEXT NULL,
    `css` TEXT NULL,
    `js` TEXT NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `marketplace_id` (`marketplace_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Staff Schedules Table (스태프 근무 일정)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_staff_schedules` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `staff_id` INT UNSIGNED NOT NULL,
    `day_of_week` TINYINT NOT NULL COMMENT '0=Sun~6=Sat',
    `is_working` TINYINT(1) DEFAULT 1,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `break_start` TIME NULL,
    `break_end` TIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_day` (`staff_id`, `day_of_week`),
    KEY `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Staff Services Table (스태프-서비스 연결)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_staff_services` (
    `staff_id` INT UNSIGNED NOT NULL,
    `service_id` CHAR(36) NOT NULL,
    PRIMARY KEY (`staff_id`, `service_id`),
    CONSTRAINT `rzx_staff_services_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `rzx_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Staff Schedule Overrides Table (스태프 일정 예외)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_staff_schedule_overrides` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `staff_id` INT UNSIGNED NOT NULL,
    `override_date` DATE NOT NULL,
    `is_working` TINYINT(1) DEFAULT 0,
    `start_time` TIME NULL,
    `end_time` TIME NULL,
    `break_start` TIME NULL,
    `break_end` TIME NULL,
    `memo` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_date` (`staff_id`, `override_date`),
    KEY `idx_date` (`override_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Attendance Table (출퇴근)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_attendance` (
    `id` CHAR(36) NOT NULL,
    `staff_id` INT NOT NULL,
    `clock_in` DATETIME NOT NULL,
    `clock_out` DATETIME NULL,
    `break_out` DATETIME NULL,
    `break_in` DATETIME NULL,
    `break_minutes` INT DEFAULT 0,
    `work_hours` DECIMAL(5,2) NULL,
    `status` ENUM('working', 'completed', 'absent', 'late', 'early_leave', 'break', 'outside') NOT NULL DEFAULT 'working',
    `source` ENUM('manual', 'card') NOT NULL DEFAULT 'manual',
    `memo` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_id` (`staff_id`),
    KEY `idx_clock_in` (`clock_in`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Password Resets Table (비밀번호 재설정)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_password_resets` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`),
    KEY `idx_token` (`token`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Push Subscribers Table (푸시 구독자)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_push_subscribers` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `endpoint` TEXT NOT NULL,
    `p256dh` VARCHAR(255) NULL,
    `auth` VARCHAR(255) NULL,
    `user_agent` VARCHAR(500) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_id` INT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Push Messages Table (푸시 메시지)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_push_messages` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `url` VARCHAR(500) NULL,
    `icon` VARCHAR(500) NULL,
    `target` ENUM('all', 'customers', 'admins') DEFAULT 'all',
    `sent_count` INT DEFAULT 0,
    `failed_count` INT DEFAULT 0,
    `status` ENUM('pending', 'sending', 'completed', 'failed') DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `sent_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Reservation Services Table (예약-서비스 연결)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_reservation_services` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `reservation_id` CHAR(36) NOT NULL,
    `service_id` CHAR(36) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `duration` INT NOT NULL DEFAULT 60,
    PRIMARY KEY (`id`),
    KEY `idx_reservation` (`reservation_id`),
    KEY `idx_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- User Remember Tokens Table (자동 로그인 토큰)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_user_remember_tokens` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_token` (`token`),
    KEY `idx_user` (`user_id`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('002_create_additional_tables', 2);
