-- ============================================================================
-- 번들 시스템 마이그레이션
-- 번들: 여러 서비스를 묶어서 판매하는 패키지
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- Bundles Table (번들/패키지)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_bundles` (
    `id` CHAR(36) NOT NULL COMMENT 'UUID',
    `name` VARCHAR(200) NOT NULL COMMENT '번들명',
    `slug` VARCHAR(200) NOT NULL COMMENT 'URL 슬러그',
    `description` TEXT NULL COMMENT '번들 설명',
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '번들 가격',
    `original_price` DECIMAL(12,2) NULL COMMENT '원가 (할인율 계산용)',
    `duration` INT NOT NULL DEFAULT 0 COMMENT '총 소요 시간 (분)',
    `image` VARCHAR(255) NULL COMMENT '대표 이미지',
    `gallery` JSON NULL COMMENT '갤러리 이미지 [{url, caption}]',
    `category_id` CHAR(36) NULL COMMENT '카테고리 ID',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '정렬 순서',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    KEY `idx_active` (`is_active`),
    KEY `idx_sort` (`sort_order`),
    CONSTRAINT `fk_bundle_category` FOREIGN KEY (`category_id`)
        REFERENCES `rzx_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='번들/패키지';

-- ============================================================================
-- Bundle Services Table (번들-서비스 연결)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_bundle_services` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'PK',
    `bundle_id` CHAR(36) NOT NULL COMMENT '번들 ID',
    `service_id` CHAR(36) NOT NULL COMMENT '서비스 ID',
    `quantity` INT NOT NULL DEFAULT 1 COMMENT '수량 (같은 서비스 중복 선택)',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '정렬 순서',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_bundle` (`bundle_id`),
    KEY `idx_service` (`service_id`),
    UNIQUE KEY `uk_bundle_service_order` (`bundle_id`, `service_id`, `sort_order`),
    CONSTRAINT `fk_bundle_services_bundle` FOREIGN KEY (`bundle_id`)
        REFERENCES `rzx_bundles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_bundle_services_service` FOREIGN KEY (`service_id`)
        REFERENCES `rzx_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='번들-서비스 연결';

SET FOREIGN_KEY_CHECKS = 1;
