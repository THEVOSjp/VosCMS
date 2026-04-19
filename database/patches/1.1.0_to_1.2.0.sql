-- ============================================================================
-- RezlyX Patch: v1.1.0 → v1.2.0
-- 스태프 배너/갤러리 컬럼 추가
--
-- rzx_staff 는 RezlyX (살롱/예약) 전용 테이블이다. 일반 VosCMS 배포에는 없으므로
-- information_schema 에서 테이블 존재 여부부터 확인한 뒤 컬럼 추가한다.
-- ============================================================================

SET @tbl_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rzx_staff');

-- rzx_staff 에 banner 컬럼 추가
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rzx_staff' AND COLUMN_NAME = 'banner');
SET @sql = IF(@tbl_exists > 0 AND @col_exists = 0,
    'ALTER TABLE rzx_staff ADD COLUMN banner VARCHAR(255) NULL COMMENT ''배너/커버 이미지''',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- rzx_staff 에 gallery 컬럼 추가
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rzx_staff' AND COLUMN_NAME = 'gallery');
SET @sql = IF(@tbl_exists > 0 AND @col_exists = 0,
    'ALTER TABLE rzx_staff ADD COLUMN gallery JSON NULL COMMENT ''소개 사진 갤러리 [{url, caption}]''',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
