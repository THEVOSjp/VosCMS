-- 016: rzx_reservation_services 누락 컬럼 추가
-- POS 페이지에서 rs.service_name 참조 시 에러 발생 수정
-- MySQL/MariaDB 모두 호환: 컬럼이 이미 있으면 에러 발생 → DatabaseMigrator가 ignorable로 처리

ALTER TABLE `rzx_reservation_services` ADD COLUMN `service_name` VARCHAR(200) NULL DEFAULT NULL;
ALTER TABLE `rzx_reservation_services` ADD COLUMN `sort_order` INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE `rzx_reservation_services` ADD COLUMN `bundle_id` CHAR(36) NULL DEFAULT NULL;
