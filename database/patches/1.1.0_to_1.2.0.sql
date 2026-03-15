-- ============================================================================
-- RezlyX Patch: v1.1.0 → v1.2.0
-- 스태프 배너/갤러리 컬럼 추가
-- ============================================================================

-- rzx_staff에 banner 컬럼 추가 (이미 있으면 무시)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rzx_staff' AND COLUMN_NAME = 'banner');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE rzx_staff ADD COLUMN banner VARCHAR(255) NULL COMMENT ''배너/커버 이미지''',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- rzx_staff에 gallery 컬럼 추가 (이미 있으면 무시)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rzx_staff' AND COLUMN_NAME = 'gallery');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE rzx_staff ADD COLUMN gallery JSON NULL COMMENT ''소개 사진 갤러리 [{url, caption}]''',
    'DO 0');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
