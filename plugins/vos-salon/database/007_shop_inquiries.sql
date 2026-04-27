-- ============================================================================
-- VosCMS Shop: Add missing fields + Q&A inquiry system
-- ============================================================================
SET NAMES utf8mb4;

-- shops 테이블 컬럼 추가
ALTER TABLE `rzx_shops`
    ADD COLUMN `contact_person` VARCHAR(100) DEFAULT NULL COMMENT '담당자명' AFTER `representative`,
    ADD COLUMN `seat_count` SMALLINT UNSIGNED DEFAULT NULL COMMENT '좌석/시술대 수' AFTER `contact_person`,
    ADD COLUMN `opening_status` VARCHAR(20) DEFAULT 'opened' COMMENT 'planned=개업예정, opened=개업완료' AFTER `seat_count`,
    ADD COLUMN `opened_at` DATE DEFAULT NULL COMMENT '개업 시기' AFTER `opening_status`;

-- Q&A 문의 테이블
CREATE TABLE IF NOT EXISTS `rzx_shop_inquiries` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT UNSIGNED NOT NULL,
    `user_id` CHAR(36) NOT NULL COMMENT '질문 작성자',
    `question` TEXT NOT NULL,
    `answer` TEXT DEFAULT NULL,
    `answered_by` CHAR(36) DEFAULT NULL COMMENT '답변자 (사업장 운영자 또는 관리자)',
    `answered_at` DATETIME DEFAULT NULL,
    `is_public` TINYINT(1) DEFAULT 1 COMMENT '공개 여부',
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending=답변대기, answered=답변완료, hidden=비공개',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_shop` (`shop_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_shop_status` (`shop_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
