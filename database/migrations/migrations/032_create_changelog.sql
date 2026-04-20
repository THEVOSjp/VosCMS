-- ============================================================
-- VosCMS Changelog 시스템 — 버전별 다국어 변경 이력 저장
-- Migration: 032_create_changelog
-- Date: 2026-04-20
-- ============================================================

-- 변경 이력 테이블 (버전별 × locale 별 별도 행)
CREATE TABLE IF NOT EXISTS `rzx_changelog` (
    `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `version`            VARCHAR(50) NOT NULL
        COMMENT '시맨틱 버전 (예: 2.2.2)',
    `version_label`      VARCHAR(200) DEFAULT NULL
        COMMENT 'MD 헤더 원문 (예: VosCMS 2.2.2 — stats 위젯 개선)',
    `release_date`       DATE NOT NULL
        COMMENT '릴리스 일자 (YYYY-MM-DD)',
    `locale`             VARCHAR(10) NOT NULL DEFAULT 'ko'
        COMMENT '콘텐츠 언어',
    `content`            LONGTEXT DEFAULT NULL
        COMMENT '해당 버전 본문 (마크다운 원본)',
    `content_hash`       CHAR(32) DEFAULT NULL
        COMMENT 'content MD5 (재업로드 diff 판정용)',
    `translation_source` ENUM('original','ai','manual') NOT NULL DEFAULT 'original'
        COMMENT 'original=원본 업로드, ai=자동 번역, manual=수동 번역',
    `source_locale`      VARCHAR(10) DEFAULT NULL
        COMMENT 'ai/manual 일 때 기반이 된 원본 locale',
    `source_hash`        CHAR(32) DEFAULT NULL
        COMMENT '번역 당시 원본의 content_hash — 원본 변경 감지용',
    `is_internal`        TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '내부용 플래그 (공개 페이지에서 숨김)',
    `is_active`          TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '비활성 시 공개 페이지에 표시 안 함',
    `created_at`         DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_version_locale` (`version`, `locale`),
    KEY `idx_date` (`release_date` DESC),
    KEY `idx_locale` (`locale`),
    KEY `idx_source` (`translation_source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='버전별 변경 이력 (다국어)';

-- changelog 시스템 페이지 번역 키 (페이지 제목)
-- 실제 값은 resources/lang/{locale}/site.php 에서 관리
-- 여기서는 DB 기반 번역(rzx_translations)도 병행 등록 가능하도록 자리 확보
