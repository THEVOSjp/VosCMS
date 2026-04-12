-- ============================================================================
-- VosCMS Core: Board System
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_boards` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(50) DEFAULT 'board',
    `description` TEXT DEFAULT NULL,
    `seo_keywords` VARCHAR(500) DEFAULT '',
    `seo_description` VARCHAR(500) DEFAULT '',
    `robots_tag` VARCHAR(10) DEFAULT 'all',
    `skin` VARCHAR(50) DEFAULT 'default',
    `skin_config` LONGTEXT DEFAULT NULL,
    `layout` VARCHAR(50) DEFAULT 'default',
    `per_page` INT DEFAULT 20,
    `search_per_page` INT DEFAULT 20,
    `page_count` INT DEFAULT 10,
    `header_content` TEXT DEFAULT NULL,
    `footer_content` TEXT DEFAULT NULL,
    `list_columns` LONGTEXT DEFAULT NULL,
    `sort_field` VARCHAR(30) DEFAULT 'created_at',
    `sort_direction` VARCHAR(4) DEFAULT 'DESC',
    `except_notice` TINYINT(1) DEFAULT 1,
    `show_category` TINYINT(1) DEFAULT 0,
    `allow_comment` TINYINT(1) DEFAULT 1,
    `use_anonymous` TINYINT(1) DEFAULT 0,
    `anonymous_name` VARCHAR(50) DEFAULT 'anonymous',
    `allow_secret` TINYINT(1) DEFAULT 0,
    `consultation` TINYINT(1) DEFAULT 0,
    `use_trash` TINYINT(1) DEFAULT 1,
    `comment_length_limit` INT DEFAULT 128,
    `doc_length_limit` INT DEFAULT 0,
    `perm_list` VARCHAR(20) DEFAULT 'all',
    `perm_read` VARCHAR(20) DEFAULT 'all',
    `perm_write` VARCHAR(20) DEFAULT 'member',
    `perm_comment` VARCHAR(20) DEFAULT 'member',
    `perm_manage` VARCHAR(20) DEFAULT 'admin',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `board_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    KEY `idx_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `board_id` INT NOT NULL,
    `category_id` INT UNSIGNED DEFAULT NULL,
    `user_id` VARCHAR(36) DEFAULT NULL,
    `title` VARCHAR(500) NOT NULL,
    `content` LONGTEXT DEFAULT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    `is_notice` TINYINT(1) DEFAULT 0,
    `is_secret` TINYINT(1) DEFAULT 0,
    `is_anonymous` TINYINT(1) DEFAULT 0,
    `nick_name` VARCHAR(100) DEFAULT NULL,
    `view_count` INT DEFAULT 0,
    `comment_count` INT DEFAULT 0,
    `file_count` INT DEFAULT 0,
    `vote_up` INT DEFAULT 0,
    `vote_down` INT DEFAULT 0,
    `list_order` BIGINT DEFAULT 0,
    `update_order` BIGINT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'published',
    `original_locale` VARCHAR(5) DEFAULT 'ko',
    `source_locale` VARCHAR(5) DEFAULT 'ko',
    `extra_vars` LONGTEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_board` (`board_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_list_order` (`list_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED NOT NULL,
    `board_id` INT NOT NULL,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `user_id` VARCHAR(36) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `password` VARCHAR(255) DEFAULT NULL,
    `nick_name` VARCHAR(100) DEFAULT NULL,
    `is_anonymous` TINYINT(1) DEFAULT 0,
    `is_secret` TINYINT(1) DEFAULT 0,
    `vote_up` INT DEFAULT 0,
    `vote_down` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'published',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_post` (`post_id`),
    KEY `idx_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT UNSIGNED NOT NULL,
    `board_id` INT NOT NULL,
    `original_name` VARCHAR(500) DEFAULT NULL,
    `stored_name` VARCHAR(500) DEFAULT NULL,
    `file_path` VARCHAR(500) DEFAULT NULL,
    `file_size` INT DEFAULT 0,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `download_count` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_votes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `target_type` VARCHAR(20) NOT NULL,
    `target_id` INT UNSIGNED NOT NULL,
    `user_id` VARCHAR(36) DEFAULT NULL,
    `vote_type` TINYINT(1) DEFAULT 1,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_extra_vars` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `board_id` INT NOT NULL,
    `var_name` VARCHAR(100) NOT NULL,
    `var_type` VARCHAR(30) DEFAULT 'text',
    `title` VARCHAR(200) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `is_required` TINYINT(1) DEFAULT 0,
    `is_searchable` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `options` JSON DEFAULT NULL,
    `default_value` VARCHAR(500) DEFAULT NULL,
    KEY `idx_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_admins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `board_id` INT NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `perm_document` TINYINT(1) DEFAULT 1,
    `perm_comment` TINYINT(1) DEFAULT 1,
    `perm_settings` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_board_user` (`board_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기본 게시판은 install.php Step 4에서 skin 포함하여 생성
