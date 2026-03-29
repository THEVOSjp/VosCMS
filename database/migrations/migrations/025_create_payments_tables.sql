-- 025: 결제 시스템 테이블 생성
-- payments: 결제 이력
-- refunds: 환불 이력

CREATE TABLE IF NOT EXISTS `rzx_payments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `reservation_id` CHAR(36) DEFAULT NULL COMMENT '연결 예약',
    `user_id` CHAR(36) DEFAULT NULL COMMENT '결제자',
    `order_id` VARCHAR(64) NOT NULL COMMENT '자체 주문번호',
    `payment_key` VARCHAR(200) DEFAULT NULL COMMENT 'PG 결제 키',
    `gateway` VARCHAR(50) NOT NULL DEFAULT 'stripe' COMMENT 'stripe, toss, payjp, portone',
    `method` VARCHAR(50) DEFAULT NULL COMMENT 'card, bank_transfer, etc.',
    `method_detail` JSON DEFAULT NULL COMMENT '카드사, 계좌 등 상세',
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '결제 금액',
    `discount_amount` DECIMAL(12,2) DEFAULT 0 COMMENT '할인 금액',
    `point_amount` DECIMAL(12,2) DEFAULT 0 COMMENT '적립금 사용액',
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, ready, paid, cancelled, partial_cancelled, failed, refunded',
    `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT '결제 완료 시간',
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `cancel_reason` VARCHAR(500) DEFAULT NULL,
    `receipt_url` VARCHAR(500) DEFAULT NULL COMMENT '영수증 URL',
    `failure_code` VARCHAR(50) DEFAULT NULL,
    `failure_message` VARCHAR(500) DEFAULT NULL,
    `raw_response` JSON DEFAULT NULL COMMENT 'PG 원본 응답',
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY `uk_payment_uuid` (`uuid`),
    UNIQUE KEY `uk_payment_order` (`order_id`),
    INDEX `idx_payment_reservation` (`reservation_id`),
    INDEX `idx_payment_user` (`user_id`),
    INDEX `idx_payment_status` (`status`),
    INDEX `idx_payment_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='결제';

CREATE TABLE IF NOT EXISTS `rzx_refunds` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_id` INT UNSIGNED NOT NULL,
    `refund_key` VARCHAR(200) DEFAULT NULL COMMENT 'PG 환불 키',
    `amount` DECIMAL(12,2) NOT NULL COMMENT '환불 금액',
    `reason` VARCHAR(500) DEFAULT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, completed, failed',
    `refunded_at` TIMESTAMP NULL DEFAULT NULL,
    `failure_reason` VARCHAR(500) DEFAULT NULL,
    `raw_response` JSON DEFAULT NULL,
    `requested_by` CHAR(36) DEFAULT NULL COMMENT '요청자 user_id',
    `processed_by` INT UNSIGNED DEFAULT NULL COMMENT '처리자 admin_id',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_refund_payment` (`payment_id`),
    INDEX `idx_refund_status` (`status`),
    CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `rzx_payments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='환불';

-- reservations에 payment_id 연결 컬럼 추가
ALTER TABLE `rzx_reservations`
    ADD COLUMN `payment_id` INT UNSIGNED DEFAULT NULL AFTER `payment_status`,
    ADD INDEX `idx_reservation_payment` (`payment_id`);
