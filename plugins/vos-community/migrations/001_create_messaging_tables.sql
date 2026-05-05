-- ============================================================
-- vos-community 플러그인 — 메시지·팔로우·알림·차단·신고·푸시 테이블
-- 코어 migration 034 에서 이전. 활성 시에만 실행.
-- 모두 IF NOT EXISTS — 기존 데이터 보존.
-- ============================================================

-- 1. 대화 (양방향, user1 < user2 정규화로 UNIQUE)
CREATE TABLE IF NOT EXISTS `rzx_conversations` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user1_id`        CHAR(36) NOT NULL COMMENT 'min(sender, recipient)',
    `user2_id`        CHAR(36) NOT NULL COMMENT 'max(sender, recipient)',
    `last_message_id` BIGINT UNSIGNED DEFAULT NULL,
    `last_message_at` DATETIME DEFAULT NULL,
    `last_preview`    VARCHAR(200) DEFAULT NULL,
    `user1_unread`    INT UNSIGNED NOT NULL DEFAULT 0,
    `user2_unread`    INT UNSIGNED NOT NULL DEFAULT 0,
    `user1_deleted`   TINYINT(1) NOT NULL DEFAULT 0,
    `user2_deleted`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uniq_pair` (`user1_id`, `user2_id`),
    KEY `idx_user1` (`user1_id`, `last_message_at`),
    KEY `idx_user2` (`user2_id`, `last_message_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 메시지
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

-- 3. 팔로우 (단방향 Twitter 방식)
CREATE TABLE IF NOT EXISTS `rzx_user_follows` (
    `follower_id`  CHAR(36) NOT NULL,
    `following_id` CHAR(36) NOT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`follower_id`, `following_id`),
    KEY `idx_following` (`following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 차단
CREATE TABLE IF NOT EXISTS `rzx_message_blocks` (
    `blocker_id` CHAR(36) NOT NULL,
    `blocked_id` CHAR(36) NOT NULL,
    `reason`     VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`blocker_id`, `blocked_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 신고 (어드민 검토용)
CREATE TABLE IF NOT EXISTS `rzx_message_reports` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id`    CHAR(36) NOT NULL,
    `target_user_id` CHAR(36) NOT NULL,
    `message_id`     BIGINT UNSIGNED DEFAULT NULL,
    `reason`         VARCHAR(50) NOT NULL,
    `detail`         TEXT DEFAULT NULL,
    `status`         ENUM('pending','reviewed','dismissed','actioned') NOT NULL DEFAULT 'pending',
    `admin_note`     TEXT DEFAULT NULL,
    `reviewed_at`    DATETIME DEFAULT NULL,
    `reviewed_by`    CHAR(36) DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_status` (`status`, `created_at`),
    KEY `idx_target` (`target_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 어드민 메시지 열람 audit 로그 (개인정보 보호)
CREATE TABLE IF NOT EXISTS `rzx_message_audit_logs` (
    `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_user_id`   CHAR(36) NOT NULL,
    `action`          VARCHAR(50) NOT NULL COMMENT 'view_conversation|view_message|export',
    `conversation_id` BIGINT UNSIGNED DEFAULT NULL,
    `message_id`      BIGINT UNSIGNED DEFAULT NULL,
    `target_user_id`  CHAR(36) DEFAULT NULL,
    `ip_address`      VARCHAR(45) DEFAULT NULL,
    `user_agent`      VARCHAR(500) DEFAULT NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_admin` (`admin_user_id`, `created_at`),
    KEY `idx_conv` (`conversation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
