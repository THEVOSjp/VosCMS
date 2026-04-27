-- VosCMS 측 마켓 구매 내역 (mp_orders/mp_order_items 보다 단순한 구매 기록 전용)
-- 결제 성공 시 마켓 API 응답을 그대로 저장해 상세 페이지 버튼 분기 + 구매 내역 표시에 사용

CREATE TABLE IF NOT EXISTS `rzx_mp_purchases` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `item_slug`     VARCHAR(100) NOT NULL,
    `item_name`     VARCHAR(200) DEFAULT NULL,
    `item_type`     VARCHAR(20)  DEFAULT NULL,
    `license_key`   VARCHAR(64)  DEFAULT NULL,
    `serial_key`    VARCHAR(64)  DEFAULT NULL,
    `product_key`   VARCHAR(64)  DEFAULT NULL,
    `order_number`  VARCHAR(64)  DEFAULT NULL,
    `amount`        DECIMAL(10,2) DEFAULT 0,
    `currency`      VARCHAR(10)  DEFAULT 'JPY',
    `installment`   TINYINT UNSIGNED DEFAULT 0,
    `buyer_email`   VARCHAR(255) DEFAULT NULL,
    `admin_id`      INT UNSIGNED DEFAULT NULL,
    `purchased_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_slug`         (`item_slug`),
    KEY `idx_purchased_at` (`purchased_at`),
    UNIQUE KEY `uq_order_number` (`order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
