-- 014: 결제 상태 컬럼 추가 (unpaid, partial, paid)
-- 2026-03-15

ALTER TABLE rzx_reservations
ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'unpaid' AFTER source,
ADD COLUMN paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER payment_status;
