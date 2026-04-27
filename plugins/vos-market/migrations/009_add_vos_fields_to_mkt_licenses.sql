-- rzx_mkt_licenses에 VosCMS 설치 키 및 도메인 컬럼 추가
SET NAMES utf8mb4;

ALTER TABLE `rzx_mkt_licenses`
    ADD COLUMN `vos_license_key` VARCHAR(24)  DEFAULT NULL COMMENT 'rzx_vos_licenses.license_key 참조' AFTER `order_item_id`,
    ADD COLUMN `domain`          VARCHAR(255) DEFAULT NULL COMMENT '설치 도메인 (정규화)' AFTER `vos_license_key`,
    ADD KEY `idx_vos_key`  (`vos_license_key`),
    ADD KEY `idx_domain`   (`domain`);
