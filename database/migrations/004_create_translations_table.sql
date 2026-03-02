-- ============================================================================
-- RezlyX Translations Table Migration
-- ============================================================================
-- 다국어 번역을 DB에 저장하기 위한 테이블
-- 관리자가 사이트 제목, 설명 등을 언어별로 입력할 수 있음

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Translations Table (다국어 번역 테이블)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_translations` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `lang_key` VARCHAR(255) NOT NULL COMMENT '번역 키 (예: site.title, site.description)',
    `locale` VARCHAR(10) NOT NULL COMMENT '언어 코드 (ko, en, ja 등)',
    `content` TEXT NOT NULL COMMENT '번역된 내용',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key_locale` (`lang_key`, `locale`),
    KEY `idx_locale` (`locale`),
    KEY `idx_lang_key` (`lang_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('004_create_translations_table', 4);
