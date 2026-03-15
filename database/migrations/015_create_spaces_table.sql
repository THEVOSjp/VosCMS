-- 015: 공간(테이블/룸) 관리 테이블 생성
-- 공간 중심 업종(레스토랑, 숙박업 등)의 POS에서 사용
-- 2026-03-15

CREATE TABLE IF NOT EXISTS rzx_spaces (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL COMMENT '공간 이름 (Table 1, Room 201 등)',
    type        VARCHAR(30) NOT NULL DEFAULT 'table' COMMENT 'table, room, seat, booth, zone',
    capacity    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '수용 인원 (0=미지정)',
    floor       VARCHAR(30) DEFAULT '' COMMENT '층/구역 (1F, 2F, Terrace 등)',
    description VARCHAR(255) DEFAULT '' COMMENT '설명',
    sort_order  INT NOT NULL DEFAULT 0 COMMENT '정렬 순서',
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 예약 테이블에 공간 연결 컬럼 추가
ALTER TABLE rzx_reservations
ADD COLUMN space_id INT UNSIGNED DEFAULT NULL AFTER service_id,
ADD INDEX idx_space (space_id);
