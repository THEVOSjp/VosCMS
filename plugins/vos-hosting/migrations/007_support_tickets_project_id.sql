-- VosCMS 호스팅 — 1:1 상담 시스템에 제작 프로젝트 연결
-- 계약 체결 시 프로젝트별 채널 자동 생성 (Phase 2C).

ALTER TABLE rzx_support_tickets
    ADD COLUMN custom_project_id INT UNSIGNED NULL COMMENT '연결된 제작 프로젝트 (계약 후 자동 생성된 채널)' AFTER addon_subscription_id,
    ADD KEY idx_custom_project (custom_project_id, last_message_at);
