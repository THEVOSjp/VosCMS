-- RezlyX v1.17.0 → v1.18.0 패치
-- 자동 마이그레이션으로 실행됨

-- 1. page_type 컬럼 추가
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rzx_page_contents' AND COLUMN_NAME = 'page_type');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE rzx_page_contents ADD COLUMN page_type VARCHAR(20) DEFAULT "document"', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. 사이트 레이아웃 기본 설정
INSERT INTO rzx_settings (`key`, `value`) VALUES ('site_layout', 'default') ON DUPLICATE KEY UPDATE `value` = `value`;
INSERT INTO rzx_settings (`key`, `value`) VALUES ('site_page_skin', 'default') ON DUPLICATE KEY UPDATE `value` = `value`;
INSERT INTO rzx_settings (`key`, `value`) VALUES ('site_board_skin', 'default') ON DUPLICATE KEY UPDATE `value` = `value`;
INSERT INTO rzx_settings (`key`, `value`) VALUES ('site_member_skin', 'default') ON DUPLICATE KEY UPDATE `value` = `value`;

-- 3. 이미지 경로 통일 (uploads/ → storage/uploads/)
UPDATE rzx_services SET image = CONCAT('storage/', image) WHERE image LIKE 'uploads/%' AND image NOT LIKE 'storage/%';
UPDATE rzx_staff SET avatar = REPLACE(avatar, 'http://localhost/storage/', '/storage/') WHERE avatar LIKE 'http://localhost/%';
UPDATE rzx_staff SET banner = REPLACE(banner, 'http://localhost/storage/', '/storage/') WHERE banner LIKE 'http://localhost/%';
UPDATE rzx_service_bundles SET image = REPLACE(image, 'http://localhost/storage/', '/storage/') WHERE image LIKE 'http://localhost/%';

-- 4. 시스템 페이지 page_type 설정
UPDATE rzx_page_contents SET is_system = 1, page_type = 'system' WHERE page_slug IN ('staff', 'booking', 'lookup');
UPDATE rzx_page_contents SET is_system = 1, page_type = 'document' WHERE page_slug IN ('terms', 'privacy', 'refund-policy', 'data-policy', 'tokushoho', 'funds-settlement');

-- 5. 직책 다국어 (name_i18n이 비어있는 경우)
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"디자이너","en":"Designer","ja":"デザイナー","zh_CN":"设计师","zh_TW":"設計師","de":"Designer","es":"Diseñador","fr":"Designer","id":"Desainer","mn":"Дизайнер","ru":"Дизайнер","tr":"Tasarımcı","vi":"Nhà thiết kế"}' WHERE id = 1 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"스태프","en":"Staff","ja":"スタッフ","zh_CN":"员工","zh_TW":"員工","de":"Mitarbeiter","es":"Personal","fr":"Personnel","id":"Staf","mn":"Ажилтан","ru":"Сотрудник","tr":"Personel","vi":"Nhân viên"}' WHERE id = 2 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"테라피스트","en":"Therapist","ja":"セラピスト","zh_CN":"理疗师","zh_TW":"理療師","de":"Therapeut","es":"Terapeuta","fr":"Thérapeute","id":"Terapis","mn":"Терапист","ru":"Терапевт","tr":"Terapist","vi":"Chuyên viên trị liệu"}' WHERE id = 3 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"강사","en":"Instructor","ja":"講師","zh_CN":"讲师","zh_TW":"講師","de":"Ausbilder","es":"Instructor","fr":"Instructeur","id":"Instruktur","mn":"Багш","ru":"Инструктор","tr":"Eğitmen","vi":"Giảng viên"}' WHERE id = 4 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"컨설턴트","en":"Consultant","ja":"コンサルタント","zh_CN":"顾问","zh_TW":"顧問","de":"Berater","es":"Consultor","fr":"Consultant","id":"Konsultan","mn":"Зөвлөх","ru":"Консультант","tr":"Danışman","vi":"Tư vấn viên"}' WHERE id = 5 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"수석 디자이너","en":"Senior Designer","ja":"シニアデザイナー","zh_CN":"高级设计师","zh_TW":"高級設計師","de":"Senior Designer","es":"Diseñador Senior","fr":"Designer Senior","id":"Desainer Senior","mn":"Ахлах дизайнер","ru":"Старший дизайнер","tr":"Kıdemli Tasarımcı","vi":"Nhà thiết kế cao cấp"}' WHERE id = 6 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"주임 스태프","en":"Lead Staff","ja":"主任スタッフ","zh_CN":"主任员工","zh_TW":"主任員工","de":"Leitender Mitarbeiter","es":"Personal Principal","fr":"Personnel Principal","id":"Staf Utama","mn":"Ахлах ажилтан","ru":"Ведущий сотрудник","tr":"Baş Personel","vi":"Nhân viên trưởng"}' WHERE id = 7 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
UPDATE rzx_staff_positions SET name_i18n = '{"ko":"원장","en":"Director","ja":"院長","zh_CN":"院长","zh_TW":"院長","de":"Direktor","es":"Director","fr":"Directeur","id":"Direktur","mn":"Захирал","ru":"Директор","tr":"Müdür","vi":"Giám đốc"}' WHERE id = 8 AND (name_i18n IS NULL OR name_i18n = '' OR name_i18n = '{}');
