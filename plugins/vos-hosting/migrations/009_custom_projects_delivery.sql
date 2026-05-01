-- VosCMS 호스팅 — 제작 프로젝트 납품 인수인계 정보 (Phase 3)
-- 납품 처리 시 관리자가 입력하는 사이트 URL / 관리자 페이지 / 임시 비밀번호 등.

ALTER TABLE rzx_custom_projects
    ADD COLUMN delivery_info LONGTEXT NULL COMMENT 'JSON: site_url, admin_url, admin_username, admin_password, notes' AFTER admin_note;
