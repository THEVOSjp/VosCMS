-- VosCMS 호스팅 — 제작 프로젝트 결제 일정 (Phase 2A)
-- 견적 발행 시 관리자가 결제 일정(분할/일시불)을 정의.
-- 견적 수락 시 자동 활성화. 고객이 각 분할금을 PAY.JP 로 결제.

CREATE TABLE IF NOT EXISTS rzx_custom_project_payments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id INT UNSIGNED NOT NULL,
    quote_id INT UNSIGNED NOT NULL COMMENT '연결된 견적 (수락된 견적의 일정만 활성)',
    sequence_no TINYINT UNSIGNED NOT NULL COMMENT '1=계약금, 2=중도금, 3=잔금... 자유 순번',
    label VARCHAR(100) NOT NULL COMMENT '계약금 / 중도금 / 잔금 / 일시불 등 자유',
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'JPY',
    due_date DATE NULL COMMENT '결제 예정일 (선택)',
    status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending / paid / cancelled',
    paid_at DATETIME NULL,
    payment_id INT UNSIGNED NULL COMMENT 'rzx_payments 전표 row id',
    payment_key VARCHAR(100) NULL COMMENT 'PAY.JP charge id (UNIQUE 검증용)',
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_project (project_id, sequence_no),
    KEY idx_quote (quote_id),
    KEY idx_status (status, due_date),
    KEY idx_payment_id (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
