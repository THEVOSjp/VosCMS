-- VosCMS 설치 라이선스 테이블
-- 마켓플레이스 아이템 라이선스(rzx_mkt_licenses)와 별도로 관리
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_vos_licenses` (
    `id`            INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    `license_key`   VARCHAR(24)     NOT NULL COMMENT 'RZX-XXXX-XXXX-XXXX',
    `domain`        VARCHAR(255)    NOT NULL COMMENT '정규화된 도메인 (프로토콜·www·경로 제거)',
    `plan`          ENUM('free','starter','pro','enterprise') NOT NULL DEFAULT 'free',
    `status`        ENUM('active','suspended','expired','revoked') NOT NULL DEFAULT 'active',
    `version`       VARCHAR(20)     DEFAULT NULL COMMENT 'VosCMS 버전',
    `php_version`   VARCHAR(20)     DEFAULT NULL,
    `server_ip`     VARCHAR(45)     DEFAULT NULL,
    `last_seen_at`  DATETIME        DEFAULT NULL COMMENT '마지막 유효성 검사 시각',
    `expires_at`    DATETIME        DEFAULT NULL COMMENT 'NULL = 무제한',
    `note`          TEXT            DEFAULT NULL COMMENT '관리자 메모',
    `registered_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_license_key` (`license_key`),
    UNIQUE KEY `uk_domain`      (`domain`),
    KEY `idx_status`  (`status`),
    KEY `idx_plan`    (`plan`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
