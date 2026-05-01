-- VosCMS 호스팅 1:1 상담 (Phase B) — 신규 테이블
-- 기존 rzx_contact_messages (front contact form) 와는 별개. 호스팅 고객 전용 티켓 시스템.

CREATE TABLE IF NOT EXISTS rzx_support_tickets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL COMMENT '고객 user_id',
    host_subscription_id INT UNSIGNED NULL COMMENT '연결된 호스팅 sub (선택)',
    addon_subscription_id INT UNSIGNED NULL COMMENT '기술 지원 등 부가서비스 sub (선택)',
    title VARCHAR(200) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open' COMMENT 'open / answered / closed',
    priority TINYINT NOT NULL DEFAULT 0 COMMENT '0=normal, 1=high',
    last_message_at DATETIME NULL,
    last_message_by VARCHAR(10) NULL COMMENT 'user / admin',
    unread_by_user TINYINT NOT NULL DEFAULT 0 COMMENT '고객이 읽지 않은 새 답변 있음',
    unread_by_admin TINYINT NOT NULL DEFAULT 0 COMMENT '관리자가 읽지 않은 새 글 있음',
    closed_at DATETIME NULL,
    closed_by CHAR(36) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_support_uuid (uuid),
    KEY idx_user (user_id, last_message_at),
    KEY idx_host_sub (host_subscription_id),
    KEY idx_addon_sub (addon_subscription_id),
    KEY idx_status (status, last_message_at),
    KEY idx_unread_admin (unread_by_admin, last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='호스팅 1:1 상담 티켓';

CREATE TABLE IF NOT EXISTS rzx_support_messages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id INT UNSIGNED NOT NULL,
    sender_user_id CHAR(36) NOT NULL,
    is_admin TINYINT NOT NULL DEFAULT 0 COMMENT '0=고객, 1=관리자/supervisor',
    body TEXT NOT NULL,
    attachments LONGTEXT NULL COMMENT 'JSON: [{name, url, size}]',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ticket_created (ticket_id, created_at),
    KEY idx_admin_recent (is_admin, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='1:1 상담 메시지 (Q&A thread)';
