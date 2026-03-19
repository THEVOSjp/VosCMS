-- RezlyX v1.7.0 Board System Migration
-- 게시판 시스템 전면 확장 + 스태프 인사말 + 게시글 다국어

-- === 기본 테이블 생성 (017 포함 — 없으면 생성) ===

CREATE TABLE IF NOT EXISTS `rzx_boards` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'board',
  `description` TEXT DEFAULT NULL,
  `seo_keywords` VARCHAR(500) DEFAULT '',
  `seo_description` VARCHAR(500) DEFAULT '',
  `robots_tag` VARCHAR(10) DEFAULT 'all',
  `skin` VARCHAR(50) DEFAULT 'default',
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
  `update_order_on_comment` TINYINT(1) DEFAULT 0,
  `comment_delete_message` VARCHAR(20) DEFAULT 'no',
  `doc_length_limit` INT DEFAULT 1024,
  `comment_length_limit` INT DEFAULT 128,
  `protect_content_by_comment` TINYINT(1) DEFAULT 0,
  `protect_by_days` INT DEFAULT 0,
  `admin_mail` VARCHAR(200) DEFAULT '',
  `perm_list` VARCHAR(20) DEFAULT 'all',
  `perm_read` VARCHAR(20) DEFAULT 'all',
  `perm_write` VARCHAR(20) DEFAULT 'member',
  `perm_comment` VARCHAR(20) DEFAULT 'member',
  `perm_manage` VARCHAR(20) DEFAULT 'admin',
  `is_active` TINYINT(1) DEFAULT 1,
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_categories` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `board_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) DEFAULT NULL,
  `color` VARCHAR(7) DEFAULT '',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_board_id` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_posts` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `board_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `title` VARCHAR(300) NOT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `is_notice` TINYINT(1) DEFAULT 0,
  `is_secret` TINYINT(1) DEFAULT 0,
  `is_anonymous` TINYINT(1) DEFAULT 0,
  `nick_name` VARCHAR(100) DEFAULT '',
  `view_count` INT DEFAULT 0,
  `comment_count` INT DEFAULT 0,
  `like_count` INT DEFAULT 0,
  `dislike_count` INT NOT NULL DEFAULT 0,
  `file_count` INT DEFAULT 0,
  `list_order` BIGINT DEFAULT 0,
  `update_order` BIGINT DEFAULT 0,
  `status` ENUM('published','draft','trash') DEFAULT 'published',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_board` (`board_id`),
  KEY `idx_category` (`category_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_comments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `post_id` INT NOT NULL,
  `board_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `parent_id` INT DEFAULT NULL,
  `depth` TINYINT DEFAULT 0,
  `content` TEXT NOT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `is_secret` TINYINT(1) DEFAULT 0,
  `is_anonymous` TINYINT(1) DEFAULT 0,
  `nick_name` VARCHAR(100) DEFAULT '',
  `like_count` INT DEFAULT 0,
  `status` ENUM('published','deleted','trash') DEFAULT 'published',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`),
  KEY `idx_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_files` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `post_id` INT NOT NULL,
  `board_id` INT NOT NULL,
  `original_name` VARCHAR(300) NOT NULL,
  `stored_name` VARCHAR(300) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT DEFAULT 0,
  `mime_type` VARCHAR(100) DEFAULT '',
  `download_count` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_board_votes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `post_id` INT NOT NULL,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
  `vote_type` ENUM('like','dislike') NOT NULL DEFAULT 'like',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_post` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- === rzx_boards 신규 컬럼 ===
ALTER TABLE `rzx_boards` ADD COLUMN `skin_config` LONGTEXT DEFAULT NULL;
ALTER TABLE `rzx_boards` ADD COLUMN `layout` VARCHAR(50) DEFAULT 'default';
ALTER TABLE `rzx_boards` ADD COLUMN `show_bottom_list` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `bottom_skip_old_days` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `bottom_skip_robot` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `hide_categories` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `allow_uncategorized` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `admin_anon_exclude` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `data_url_limit` INT DEFAULT 64;
ALTER TABLE `rzx_boards` ADD COLUMN `use_edit_history` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `unicode_abuse_block` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `protect_edit_comment_count` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `protect_delete_comment_count` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `protect_comment_edit_reply` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `protect_comment_delete_reply` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `restrict_edit_days` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `restrict_comment_days` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `admin_protect_delete` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `admin_protect_edit` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `allow_public` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `allow_secret_default` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_placeholder_type` VARCHAR(20) DEFAULT 'none';

-- 통합 게시판 / 문서 / 댓글 / 에디터 / 파일 / 피드
ALTER TABLE `rzx_boards` ADD COLUMN `merge_boards` TEXT DEFAULT NULL;
ALTER TABLE `rzx_boards` ADD COLUMN `merge_period` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `merge_notice` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `use_history` VARCHAR(10) DEFAULT 'none';
ALTER TABLE `rzx_boards` ADD COLUMN `use_vote` VARCHAR(10) DEFAULT 'use';
ALTER TABLE `rzx_boards` ADD COLUMN `use_downvote` VARCHAR(10) DEFAULT 'use';
ALTER TABLE `rzx_boards` ADD COLUMN `vote_same_ip` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `vote_cancel` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `vote_non_member` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `report_same_ip` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `report_cancel` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `report_notify` VARCHAR(50) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_count` INT DEFAULT 50;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_page_count` INT DEFAULT 10;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_max_depth` INT DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_default_page` VARCHAR(10) DEFAULT 'last';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_approval` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_vote` VARCHAR(10) DEFAULT 'use';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_downvote` VARCHAR(10) DEFAULT 'use';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_vote_same_ip` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_vote_cancel` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_vote_non_member` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_report_same_ip` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_report_cancel` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_boards` ADD COLUMN `comment_report_notify` VARCHAR(50) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `editor_use_default` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `editor_html_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `editor_file_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `editor_component_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `editor_ext_component_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_editor_html_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_editor_file_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_editor_component_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `comment_editor_ext_component_perm` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `file_use_default` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `file_image_default` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `file_video_default` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `file_download_groups` VARCHAR(100) DEFAULT '';
ALTER TABLE `rzx_boards` ADD COLUMN `feed_type` VARCHAR(20) DEFAULT 'none';
ALTER TABLE `rzx_boards` ADD COLUMN `feed_include_merged` TINYINT(1) DEFAULT 1;
ALTER TABLE `rzx_boards` ADD COLUMN `feed_description` TEXT DEFAULT NULL;
ALTER TABLE `rzx_boards` ADD COLUMN `feed_copyright` TEXT DEFAULT NULL;

-- === rzx_board_categories 신규 컬럼 ===
ALTER TABLE `rzx_board_categories` ADD COLUMN `parent_id` INT DEFAULT 0;
ALTER TABLE `rzx_board_categories` ADD COLUMN `description` TEXT DEFAULT NULL;
ALTER TABLE `rzx_board_categories` ADD COLUMN `font_color` VARCHAR(20) DEFAULT '';
ALTER TABLE `rzx_board_categories` ADD COLUMN `allowed_groups` VARCHAR(200) DEFAULT '';
ALTER TABLE `rzx_board_categories` ADD COLUMN `is_expanded` TINYINT(1) DEFAULT 0;
ALTER TABLE `rzx_board_categories` ADD COLUMN `is_default` TINYINT(1) DEFAULT 0;

-- === rzx_board_extra_vars (신규 테이블) ===
CREATE TABLE IF NOT EXISTS `rzx_board_extra_vars` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `board_id` INT NOT NULL,
    `var_name` VARCHAR(50) NOT NULL,
    `var_type` VARCHAR(20) NOT NULL DEFAULT 'text',
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_required` TINYINT(1) DEFAULT 0,
    `is_searchable` TINYINT(1) DEFAULT 0,
    `is_shown_in_list` TINYINT(1) DEFAULT 0,
    `options` TEXT DEFAULT NULL,
    `default_value` VARCHAR(500) DEFAULT '',
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_board` (`board_id`),
    UNIQUE KEY `uk_board_var` (`board_id`, `var_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === rzx_board_admins (신규 테이블) ===
CREATE TABLE IF NOT EXISTS `rzx_board_admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `board_id` INT NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `perm_document` TINYINT(1) DEFAULT 1,
    `perm_comment` TINYINT(1) DEFAULT 1,
    `perm_settings` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_board_user` (`board_id`, `user_id`),
    INDEX `idx_board` (`board_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- === rzx_board_posts 다국어 컬럼 ===
ALTER TABLE `rzx_board_posts` ADD COLUMN `original_locale` VARCHAR(5) DEFAULT 'ko';
ALTER TABLE `rzx_board_posts` ADD COLUMN `source_locale` VARCHAR(5) DEFAULT 'ko';

-- === rzx_staff 인사말 컬럼 ===
ALTER TABLE `rzx_staff` ADD COLUMN `banner` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `rzx_staff` ADD COLUMN `greeting_before` TEXT DEFAULT NULL;
ALTER TABLE `rzx_staff` ADD COLUMN `greeting_after` TEXT DEFAULT NULL;

-- === rzx_reservations 컬럼 확장 ===
ALTER TABLE `rzx_reservations` MODIFY COLUMN `reservation_number` VARCHAR(50) NOT NULL;

-- === rzx_reservations 누락 컬럼 ===
ALTER TABLE `rzx_reservations` ADD COLUMN `source` VARCHAR(20) NOT NULL DEFAULT 'online';
ALTER TABLE `rzx_reservations` ADD COLUMN `payment_status` VARCHAR(20) NOT NULL DEFAULT 'unpaid';
ALTER TABLE `rzx_reservations` ADD COLUMN `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00;

-- === rzx_admin_memos 테이블 ===
CREATE TABLE IF NOT EXISTS `rzx_admin_memos` (
  `id` char(36) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `reservation_id` int(10) unsigned DEFAULT NULL,
  `reservation_number` varchar(50) DEFAULT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_reservation_id` (`reservation_id`),
  KEY `idx_admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
