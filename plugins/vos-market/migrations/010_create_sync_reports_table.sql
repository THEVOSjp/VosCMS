-- VosCMS 설치 아이템 동기화 추적 테이블
-- 마켓을 통하지 않고 설치된 아이템을 감지하기 위해
-- VosCMS가 주기적으로 installed items를 market으로 전송할 때 기록됨

CREATE TABLE IF NOT EXISTS `rzx_mkt_sync_reports` (
    `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `vos_key`        VARCHAR(24)      NOT NULL COMMENT 'rzx_vos_licenses.license_key',
    `domain`         VARCHAR(255)     NOT NULL COMMENT '정규화된 설치 도메인',
    `item_id`        INT UNSIGNED     NULL     COMMENT 'rzx_mkt_items.id (NULL = 알 수 없는 product_key)',
    `product_key`    CHAR(36)         NOT NULL COMMENT '아이템 고유 UUID',
    `slug`           VARCHAR(100)     NULL     COMMENT '보고된 아이템 슬러그',
    `version`        VARCHAR(20)      NULL     COMMENT '설치된 버전',
    `status`         ENUM(
                         'licensed',        -- 정상 라이선스 있음
                         'unlicensed',      -- 라이선스 없이 설치됨
                         'unknown_product'  -- 마켓에 없는 product_key
                     )                NOT NULL DEFAULT 'unlicensed',
    `first_seen_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen_at`   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE  KEY `uk_vos_item`  (`vos_key`, `product_key`),
    INDEX   `idx_domain`       (`domain`),
    INDEX   `idx_status`       (`status`),
    INDEX   `idx_item_id`      (`item_id`),
    INDEX   `idx_last_seen`    (`last_seen_at`),

    CONSTRAINT `fk_sync_item`
        FOREIGN KEY (`item_id`) REFERENCES `rzx_mkt_items` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
