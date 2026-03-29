-- 예약 테이블에 번들 정보 컬럼 추가
ALTER TABLE `rzx_reservations`
    ADD COLUMN `bundle_id` char(36) DEFAULT NULL AFTER `staff_id`,
    ADD COLUMN `bundle_price` decimal(12,2) DEFAULT NULL AFTER `bundle_id`;
