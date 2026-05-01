-- VosCMS 호스팅 — 제작 프로젝트 결제 카드 저장 (저장 카드 재사용)
-- 첫 결제 시 PAY.JP customer_id 저장 → 이후 결제는 토큰 없이 그대로 진행.

ALTER TABLE rzx_custom_projects
    ADD COLUMN payment_customer_id VARCHAR(100) NULL COMMENT 'PAY.JP customer id — 저장 카드 결제용' AFTER contract_currency,
    ADD COLUMN payment_card_brand VARCHAR(40) NULL COMMENT 'visa / master / amex / jcb...' AFTER payment_customer_id,
    ADD COLUMN payment_card_last4 CHAR(4) NULL AFTER payment_card_brand;
