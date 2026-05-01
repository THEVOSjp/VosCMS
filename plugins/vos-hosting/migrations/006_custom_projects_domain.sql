-- VosCMS 호스팅 — 제작 프로젝트에 도메인/호스팅 매칭 정보 추가
-- 견적 발행·납품 시 어디에 설치할지 명확화.

ALTER TABLE rzx_custom_projects
    ADD COLUMN domain_option VARCHAR(20) NULL COMMENT 'addon / new / existing / free / discuss' AFTER reference_urls,
    ADD COLUMN domain_name VARCHAR(255) NULL COMMENT '예정 도메인명 (예: example.com)' AFTER domain_option,
    ADD COLUMN linked_host_subscription_id INT UNSIGNED NULL COMMENT '연결될 기존 호스팅 sub (보유한 경우)' AFTER domain_name,
    ADD COLUMN need_new_hosting TINYINT NOT NULL DEFAULT 0 COMMENT '신규 호스팅 함께 신청 (1) / 보유 호스팅 사용 (0)' AFTER linked_host_subscription_id,
    ADD KEY idx_linked_host_sub (linked_host_subscription_id);
