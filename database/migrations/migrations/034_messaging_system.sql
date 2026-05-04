-- ============================================================
-- VosCMS 메시지·팔로우·알림 시스템
-- Migration: 034_messaging_system
-- Date: 2026-05-04
-- ============================================================

-- 1. 대화 (양방향, user1 < user2 정규화로 UNIQUE)
CREATE TABLE IF NOT EXISTS `rzx_conversations` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user1_id`        CHAR(36) NOT NULL COMMENT 'min(sender, recipient)',
    `user2_id`        CHAR(36) NOT NULL COMMENT 'max(sender, recipient)',
    `last_message_id` BIGINT UNSIGNED DEFAULT NULL,
    `last_message_at` DATETIME DEFAULT NULL,
    `last_preview`    VARCHAR(200) DEFAULT NULL COMMENT '미리보기 (앞 200자)',
    `user1_unread`    INT UNSIGNED NOT NULL DEFAULT 0,
    `user2_unread`    INT UNSIGNED NOT NULL DEFAULT 0,
    `user1_deleted`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '소프트 삭제 (각자)',
    `user2_deleted`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_pair` (`user1_id`, `user2_id`),
    KEY `idx_user1` (`user1_id`, `last_message_at`),
    KEY `idx_user2` (`user2_id`, `last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 메시지 (개별 메시지)
CREATE TABLE IF NOT EXISTS `rzx_messages` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `conversation_id` BIGINT UNSIGNED NOT NULL,
    `sender_id`       CHAR(36) NOT NULL,
    `body`            TEXT NOT NULL,
    `is_read`         TINYINT(1) NOT NULL DEFAULT 0,
    `read_at`         DATETIME DEFAULT NULL,
    `sender_deleted`  TINYINT(1) NOT NULL DEFAULT 0,
    `recipient_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `sent_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_conv` (`conversation_id`, `sent_at`),
    KEY `idx_sender` (`sender_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 알림 (시스템 + 푸시 통합)
CREATE TABLE IF NOT EXISTS `rzx_notifications` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    CHAR(36) NOT NULL,
    `type`       VARCHAR(50) NOT NULL COMMENT 'system|admin|message|follow|payment|hosting|board',
    `category`   VARCHAR(50) DEFAULT NULL COMMENT '세부 분류 (db_quota, ssl_expiry, new_message...)',
    `title`      VARCHAR(255) NOT NULL,
    `body`       TEXT DEFAULT NULL,
    `link`       VARCHAR(500) DEFAULT NULL COMMENT '클릭 시 이동 URL',
    `icon`       VARCHAR(50) DEFAULT NULL COMMENT '아이콘 키 (예: bell, warning, message)',
    `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
    `read_at`    DATETIME DEFAULT NULL,
    `is_pushed`  TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Web Push 발송 여부',
    `pushed_at`  DATETIME DEFAULT NULL,
    `meta`       JSON DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL COMMENT '90일 자동 정리용',
    KEY `idx_user_unread` (`user_id`, `is_read`, `created_at`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 팔로우 (단방향, Twitter 방식)
CREATE TABLE IF NOT EXISTS `rzx_user_follows` (
    `follower_id`  CHAR(36) NOT NULL COMMENT '팔로우 거는 사용자',
    `following_id` CHAR(36) NOT NULL COMMENT '팔로우 당하는 사용자',
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`follower_id`, `following_id`),
    KEY `idx_following` (`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 차단 (메시지 차단)
CREATE TABLE IF NOT EXISTS `rzx_message_blocks` (
    `blocker_id` CHAR(36) NOT NULL,
    `blocked_id` CHAR(36) NOT NULL,
    `reason`     VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`blocker_id`, `blocked_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 메시지 신고 (어드민 검토용)
CREATE TABLE IF NOT EXISTS `rzx_message_reports` (
    `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id`  CHAR(36) NOT NULL,
    `target_user_id` CHAR(36) NOT NULL COMMENT '신고 대상',
    `message_id`   BIGINT UNSIGNED DEFAULT NULL,
    `reason`       VARCHAR(50) NOT NULL COMMENT 'spam|harassment|inappropriate|other',
    `detail`       TEXT DEFAULT NULL,
    `status`       ENUM('pending','reviewed','dismissed','actioned') NOT NULL DEFAULT 'pending',
    `admin_note`   TEXT DEFAULT NULL,
    `reviewed_at`  DATETIME DEFAULT NULL,
    `reviewed_by`  CHAR(36) DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_status` (`status`, `created_at`),
    KEY `idx_target` (`target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. users 테이블 — 메시지·프로필 관련 컬럼 추가
-- (display_name 은 nick_name 재사용. bio + 수신/공개 설정만 추가)
ALTER TABLE `rzx_users`
    ADD COLUMN IF NOT EXISTS `bio` VARCHAR(500) DEFAULT NULL COMMENT '자기소개' AFTER `profile_image`,
    ADD COLUMN IF NOT EXISTS `is_profile_public` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '프로필 공개 여부' AFTER `bio`,
    ADD COLUMN IF NOT EXISTS `allow_messages_from` ENUM('all','followers','none') NOT NULL DEFAULT 'all' COMMENT 'all=누구나, followers=팔로워만, none=차단' AFTER `is_profile_public`,
    ADD COLUMN IF NOT EXISTS `messages_paused_until` DATETIME DEFAULT NULL COMMENT 'rate limit 일시 차단';

-- 8. Web Push 구독 (Phase 3 에서 사용, 미리 생성)
CREATE TABLE IF NOT EXISTS `rzx_push_subscriptions` (
    `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    CHAR(36) NOT NULL,
    `endpoint`   VARCHAR(500) NOT NULL,
    `p256dh`     VARCHAR(255) NOT NULL,
    `auth`       VARCHAR(100) NOT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_used_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uniq_endpoint` (`endpoint`(255)),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
