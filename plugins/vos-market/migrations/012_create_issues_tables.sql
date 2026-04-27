-- 마켓플레이스 아이템 이슈 / Q&A
-- type='issue': 버그·문제 신고, type='qna': 사용법·질문

CREATE TABLE IF NOT EXISTS `rzx_mkt_issues` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_id`         INT UNSIGNED NOT NULL,
    `type`            ENUM('issue','qna') NOT NULL DEFAULT 'issue',
    `title`           VARCHAR(200) NOT NULL,
    `body`            TEXT,
    `author_name`     VARCHAR(100) DEFAULT NULL,
    `author_domain`   VARCHAR(255) DEFAULT NULL,
    `is_verified`     TINYINT(1) NOT NULL DEFAULT 0,
    `status`          ENUM('open','closed','resolved') NOT NULL DEFAULT 'open',
    `reply_count`     INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`      DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_item_type` (`item_id`, `type`, `created_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_mkt_issue_replies` (
    `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `issue_id`         INT UNSIGNED NOT NULL,
    `body`             TEXT NOT NULL,
    `author_name`      VARCHAR(100) DEFAULT NULL,
    `author_domain`    VARCHAR(255) DEFAULT NULL,
    `is_partner_reply` TINYINT(1) NOT NULL DEFAULT 0,
    `is_verified`      TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_issue` (`issue_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
