-- 016: rzx_reservation_services 누락 컬럼 추가
-- service_name, sort_order, bundle_id 컬럼이 002 마이그레이션에 누락됨
-- POS 페이지에서 rs.service_name 참조 시 에러 발생 수정

ALTER TABLE `rzx_reservation_services`
  ADD COLUMN IF NOT EXISTS `service_name` VARCHAR(200) NULL DEFAULT NULL AFTER `service_id`,
  ADD COLUMN IF NOT EXISTS `sort_order` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `duration`,
  ADD COLUMN IF NOT EXISTS `bundle_id` CHAR(36) NULL DEFAULT NULL AFTER `sort_order`;
