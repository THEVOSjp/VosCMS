-- RezlyX v1.9.0: 적립금/포인트 시스템 테이블

-- 레벨 포인트 테이블
CREATE TABLE IF NOT EXISTS rzx_point_levels (
    level INT UNSIGNED NOT NULL PRIMARY KEY,
    point INT NOT NULL DEFAULT 0,
    group_id CHAR(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 회원 포인트 테이블
CREATE TABLE IF NOT EXISTS rzx_member_points (
    user_id CHAR(36) NOT NULL PRIMARY KEY,
    point INT NOT NULL DEFAULT 0,
    balance INT NOT NULL DEFAULT 0,
    total_accumulated INT NOT NULL DEFAULT 0,
    level INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_point (point),
    INDEX idx_level (level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 번들 이벤트 할인 컬럼 추가
ALTER TABLE rzx_service_bundles ADD COLUMN event_price DECIMAL(10,2) DEFAULT NULL;
ALTER TABLE rzx_service_bundles ADD COLUMN event_start DATETIME DEFAULT NULL;
ALTER TABLE rzx_service_bundles ADD COLUMN event_end DATETIME DEFAULT NULL;
ALTER TABLE rzx_service_bundles ADD COLUMN event_label VARCHAR(100) DEFAULT NULL;
