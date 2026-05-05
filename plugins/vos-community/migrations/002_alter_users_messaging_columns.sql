-- ============================================================
-- vos-community — rzx_users 메시지·프로필 컬럼 추가
-- IF NOT EXISTS 로 중복 안전. 비활성/삭제 시 컬럼은 유지 (호환성).
-- ============================================================

ALTER TABLE `rzx_users`
    ADD COLUMN IF NOT EXISTS `bio` VARCHAR(500) DEFAULT NULL COMMENT '자기소개',
    ADD COLUMN IF NOT EXISTS `is_profile_public` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '프로필 공개 여부',
    ADD COLUMN IF NOT EXISTS `allow_messages_from` ENUM('all','followers','none') NOT NULL DEFAULT 'all' COMMENT 'all=누구나, followers=팔로워만, none=차단',
    ADD COLUMN IF NOT EXISTS `messages_paused_until` DATETIME DEFAULT NULL COMMENT 'rate limit 일시 차단';
