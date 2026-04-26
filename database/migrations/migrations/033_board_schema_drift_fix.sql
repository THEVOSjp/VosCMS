-- 게시판 시스템 컬럼 누락 보정 (v2.3.x 개발 중 추가된 컬럼들)
-- 멱등 적용: ADD COLUMN IF NOT EXISTS (MariaDB 10.0+)

-- 1) rzx_board_files: 대표 이미지 플래그 (카드/갤러리/웹진 뷰에서 사용)
ALTER TABLE `rzx_board_files`
    ADD COLUMN IF NOT EXISTS `is_primary` TINYINT(1) NOT NULL DEFAULT 0;

-- 2) rzx_board_comments: 트리 댓글 깊이 + 좋아요 카운트
ALTER TABLE `rzx_board_comments`
    ADD COLUMN IF NOT EXISTS `depth` TINYINT(3) UNSIGNED DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `like_count` INT(10) UNSIGNED DEFAULT 0;

-- 3) rzx_boards: 댓글 작성 시 정렬 갱신, 삭제 메시지, 콘텐츠 보호, 관리자 메일
ALTER TABLE `rzx_boards`
    ADD COLUMN IF NOT EXISTS `update_order_on_comment` TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `comment_delete_message` VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `protect_content_by_comment` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `protect_by_days` INT(11) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `admin_mail` VARCHAR(255) DEFAULT NULL;

-- 4) rzx_board_extra_vars: 목록 표시 + 편집 권한
ALTER TABLE `rzx_board_extra_vars`
    ADD COLUMN IF NOT EXISTS `is_shown_in_list` TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `permission` ENUM('all','member','admin') NOT NULL DEFAULT 'all';
