-- rzx_mkt_orders: 시리얼 키 컬럼 추가
SET NAMES utf8mb4;

ALTER TABLE `rzx_mkt_orders`
    ADD COLUMN `serial_key`      VARCHAR(24) DEFAULT NULL COMMENT '구매 시리얼 키 (MKT-XXXX-XXXX-XXXX)' AFTER `payment_ref`,
    ADD COLUMN `vos_license_key` VARCHAR(24) DEFAULT NULL COMMENT '구매자 VosCMS 라이선스 키' AFTER `serial_key`,
    ADD KEY `idx_serial_key` (`serial_key`),
    ADD KEY `idx_vos_key`    (`vos_license_key`);
