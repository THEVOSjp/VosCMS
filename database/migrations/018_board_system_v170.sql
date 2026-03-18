-- RezlyX v1.7.0 Board System Migration
-- 게시판 시스템 전면 확장 + 스태프 인사말 + 게시글 다국어

-- === rzx_boards 신규 컬럼 ===
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `skin_config` LONGTEXT DEFAULT NULL AFTER `skin`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `layout` VARCHAR(50) DEFAULT 'default' AFTER `skin_config`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `show_bottom_list` TINYINT(1) DEFAULT 1 AFTER `except_notice`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `bottom_skip_old_days` INT DEFAULT 0 AFTER `show_bottom_list`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `bottom_skip_robot` TINYINT(1) DEFAULT 1 AFTER `bottom_skip_old_days`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `hide_categories` TINYINT(1) DEFAULT 0 AFTER `show_category`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `allow_uncategorized` TINYINT(1) DEFAULT 1 AFTER `hide_categories`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `admin_anon_exclude` TINYINT(1) DEFAULT 0 AFTER `consultation`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `data_url_limit` INT DEFAULT 64 AFTER `comment_length_limit`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `use_edit_history` TINYINT(1) DEFAULT 0 AFTER `data_url_limit`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `unicode_abuse_block` TINYINT(1) DEFAULT 1 AFTER `use_edit_history`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `protect_edit_comment_count` INT DEFAULT 0 AFTER `protect_content_by_comment`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `protect_delete_comment_count` INT DEFAULT 0 AFTER `protect_edit_comment_count`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `protect_comment_edit_reply` INT DEFAULT 0 AFTER `protect_delete_comment_count`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `protect_comment_delete_reply` INT DEFAULT 0 AFTER `protect_comment_edit_reply`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `restrict_edit_days` INT DEFAULT 0 AFTER `protect_comment_delete_reply`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `restrict_comment_days` INT DEFAULT 0 AFTER `restrict_edit_days`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `admin_protect_delete` TINYINT(1) DEFAULT 1 AFTER `restrict_comment_days`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `admin_protect_edit` TINYINT(1) DEFAULT 1 AFTER `admin_protect_delete`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `allow_public` TINYINT(1) DEFAULT 1 AFTER `admin_protect_edit`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `allow_secret_default` TINYINT(1) DEFAULT 0 AFTER `allow_public`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_placeholder_type` VARCHAR(20) DEFAULT 'none' AFTER `comment_delete_message`;

-- 통합 게시판 / 문서 / 댓글 / 에디터 / 파일 / 피드
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `merge_boards` TEXT DEFAULT NULL AFTER `allow_secret_default`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `merge_period` INT DEFAULT 0 AFTER `merge_boards`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `merge_notice` TINYINT(1) DEFAULT 1 AFTER `merge_period`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `use_history` VARCHAR(10) DEFAULT 'none' AFTER `merge_notice`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `use_vote` VARCHAR(10) DEFAULT 'use' AFTER `use_history`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `use_downvote` VARCHAR(10) DEFAULT 'use' AFTER `use_vote`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `vote_same_ip` TINYINT(1) DEFAULT 0 AFTER `use_downvote`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `vote_cancel` TINYINT(1) DEFAULT 0 AFTER `vote_same_ip`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `vote_non_member` TINYINT(1) DEFAULT 0 AFTER `vote_cancel`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `report_same_ip` TINYINT(1) DEFAULT 0 AFTER `vote_non_member`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `report_cancel` TINYINT(1) DEFAULT 0 AFTER `report_same_ip`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `report_notify` VARCHAR(50) DEFAULT '' AFTER `report_cancel`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_count` INT DEFAULT 50 AFTER `report_notify`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_page_count` INT DEFAULT 10 AFTER `comment_count`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_max_depth` INT DEFAULT 0 AFTER `comment_page_count`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_default_page` VARCHAR(10) DEFAULT 'last' AFTER `comment_max_depth`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_approval` TINYINT(1) DEFAULT 0 AFTER `comment_default_page`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_vote` VARCHAR(10) DEFAULT 'use' AFTER `comment_approval`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_downvote` VARCHAR(10) DEFAULT 'use' AFTER `comment_vote`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_vote_same_ip` TINYINT(1) DEFAULT 0 AFTER `comment_downvote`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_vote_cancel` TINYINT(1) DEFAULT 0 AFTER `comment_vote_same_ip`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_vote_non_member` TINYINT(1) DEFAULT 0 AFTER `comment_vote_cancel`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_report_same_ip` TINYINT(1) DEFAULT 0 AFTER `comment_vote_non_member`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_report_cancel` TINYINT(1) DEFAULT 0 AFTER `comment_report_same_ip`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_report_notify` VARCHAR(50) DEFAULT '' AFTER `comment_report_cancel`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `editor_use_default` TINYINT(1) DEFAULT 1 AFTER `comment_report_notify`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `editor_html_perm` VARCHAR(100) DEFAULT '' AFTER `editor_use_default`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `editor_file_perm` VARCHAR(100) DEFAULT '' AFTER `editor_html_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `editor_component_perm` VARCHAR(100) DEFAULT '' AFTER `editor_file_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `editor_ext_component_perm` VARCHAR(100) DEFAULT '' AFTER `editor_component_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_editor_html_perm` VARCHAR(100) DEFAULT '' AFTER `editor_ext_component_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_editor_file_perm` VARCHAR(100) DEFAULT '' AFTER `comment_editor_html_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_editor_component_perm` VARCHAR(100) DEFAULT '' AFTER `comment_editor_file_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `comment_editor_ext_component_perm` VARCHAR(100) DEFAULT '' AFTER `comment_editor_component_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `file_use_default` TINYINT(1) DEFAULT 1 AFTER `comment_editor_ext_component_perm`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `file_image_default` TINYINT(1) DEFAULT 1 AFTER `file_use_default`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `file_video_default` TINYINT(1) DEFAULT 1 AFTER `file_image_default`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `file_download_groups` VARCHAR(100) DEFAULT '' AFTER `file_video_default`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `feed_type` VARCHAR(20) DEFAULT 'none' AFTER `file_download_groups`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `feed_include_merged` TINYINT(1) DEFAULT 1 AFTER `feed_type`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `feed_description` TEXT DEFAULT NULL AFTER `feed_include_merged`;
ALTER TABLE `rzx_boards` ADD COLUMN IF NOT EXISTS `feed_copyright` TEXT DEFAULT NULL AFTER `feed_description`;

-- === rzx_board_categories 신규 컬럼 ===
ALTER TABLE `rzx_board_categories` ADD COLUMN IF NOT EXISTS `parent_id` INT DEFAULT 0 AFTER `board_id`;
ALTER TABLE `rzx_board_categories` ADD COLUMN IF NOT EXISTS `description` TEXT DEFAULT NULL AFTER `slug`;
ALTER TABLE `rzx_board_categories` ADD COLUMN IF NOT EXISTS `font_color` VARCHAR(20) DEFAULT '' AFTER `color`;
ALTER TABLE `rzx_board_categories` ADD COLUMN IF NOT EXISTS `allowed_groups` VARCHAR(200) DEFAULT '' AFTER `font_color`;
ALTER TABLE `rzx_board_categories` ADD COLUMN IF NOT EXISTS `is_expanded` TINYINT(1) DEFAULT 0 AFTER `allowed_groups`;
ALTER TABLE `rzx_board_categories` ADD COLUMN IF NOT EXISTS `is_default` TINYINT(1) DEFAULT 0 AFTER `is_expanded`;

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
ALTER TABLE `rzx_board_posts` ADD COLUMN IF NOT EXISTS `original_locale` VARCHAR(5) DEFAULT 'ko' AFTER `status`;
ALTER TABLE `rzx_board_posts` ADD COLUMN IF NOT EXISTS `source_locale` VARCHAR(5) DEFAULT 'ko' AFTER `original_locale`;

-- === rzx_staff 인사말 컬럼 ===
ALTER TABLE `rzx_staff` ADD COLUMN IF NOT EXISTS `banner` VARCHAR(255) DEFAULT NULL AFTER `avatar`;
ALTER TABLE `rzx_staff` ADD COLUMN IF NOT EXISTS `greeting_before` TEXT DEFAULT NULL AFTER `banner`;
ALTER TABLE `rzx_staff` ADD COLUMN IF NOT EXISTS `greeting_after` TEXT DEFAULT NULL AFTER `greeting_before`;
