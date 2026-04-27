-- ============================================================
-- VosCMS 서비스 관리 확장 — 서비스 분류 + 1회성 완료
-- Plugin: vos-hosting
-- Origin: 031_service_management_expansion (코어 → 플러그인 이전)
-- Date: 2026-04-16
-- ============================================================
-- 멱등성: ADD COLUMN IF NOT EXISTS (MariaDB 10.0+)
--   이미 코어 마이그레이션 031 이 적용된 사이트에서도 안전

-- 서비스 분류 (recurring: 유료 반복, one_time: 1회성, free: 무료)
ALTER TABLE rzx_subscriptions
  ADD COLUMN IF NOT EXISTS service_class VARCHAR(15) NOT NULL DEFAULT 'recurring'
    COMMENT 'recurring | one_time | free' AFTER type;

-- 1회성 서비스 완료일
ALTER TABLE rzx_subscriptions
  ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL DEFAULT NULL
    COMMENT '1회성 서비스 완료 시각' AFTER next_billing_at;

-- 기존 데이터 보정: 무료 구독 → free, 자동연장 OFF (이미 적용된 행은 무영향)
UPDATE rzx_subscriptions SET service_class = 'free', auto_renew = 0
WHERE billing_amount = 0 AND service_class != 'free';
