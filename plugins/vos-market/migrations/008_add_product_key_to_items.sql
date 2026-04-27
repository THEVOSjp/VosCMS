-- rzx_mkt_items에 product_key(UUID) 컬럼 추가
SET NAMES utf8mb4;

ALTER TABLE `rzx_mkt_items`
    ADD COLUMN `product_key` CHAR(36) NOT NULL DEFAULT '' COMMENT '아이템 고유 UUID' AFTER `id`;

-- 기존 아이템에 UUID 자동 생성
UPDATE `rzx_mkt_items` SET `product_key` = UUID() WHERE `product_key` = '';

-- DEFAULT 제거 후 NOT NULL + UNIQUE 적용
ALTER TABLE `rzx_mkt_items`
    MODIFY COLUMN `product_key` CHAR(36) NOT NULL COMMENT '아이템 고유 UUID',
    ADD UNIQUE KEY `uk_product_key` (`product_key`);
