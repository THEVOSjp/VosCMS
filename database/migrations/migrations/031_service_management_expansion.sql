-- ============================================================
-- VosCMS 서비스 관리 확장 — 서비스 분류 + 1회성 완료
-- Migration: 031_service_management_expansion
-- Date: 2026-04-16
-- ============================================================

-- 서비스 분류 (recurring: 유료 반복, one_time: 1회성, free: 무료)
ALTER TABLE rzx_subscriptions
  ADD COLUMN service_class VARCHAR(15) NOT NULL DEFAULT 'recurring'
    COMMENT 'recurring | one_time | free' AFTER type;

-- 1회성 서비스 완료일
ALTER TABLE rzx_subscriptions
  ADD COLUMN completed_at DATETIME NULL DEFAULT NULL
    COMMENT '1회성 서비스 완료 시각' AFTER next_billing_at;

-- 기존 데이터 보정: 무료 구독 → free, 자동연장 OFF
UPDATE rzx_subscriptions SET service_class = 'free', auto_renew = 0 WHERE billing_amount = 0;
