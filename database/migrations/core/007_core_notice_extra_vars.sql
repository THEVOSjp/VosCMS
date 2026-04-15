-- 공지 게시판에 notice_type, notice_url 추가 필드 등록
-- 2026-04-12

INSERT IGNORE INTO `rzx_board_extra_vars` (`board_id`, `var_name`, `var_type`, `title`, `description`, `is_required`, `is_searchable`, `is_active`, `sort_order`, `options`) VALUES
(1, 'notice_type', 'select', 'Notice Type', 'API 공지 유형 (release/feature/security/maintenance/info)', 0, 1, 1, 1, '{"options":[{"value":"info","label":"Info"},{"value":"release","label":"Release/Update"},{"value":"feature","label":"New Feature"},{"value":"security","label":"Security"},{"value":"maintenance","label":"Maintenance"}]}'),
(1, 'notice_url', 'text', 'External URL', '공지 외부 링크 (선택)', 0, 0, 1, 2, NULL);
