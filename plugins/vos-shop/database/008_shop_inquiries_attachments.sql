-- ============================================================================
-- VosCMS Shop: Add attachments to shop_inquiries
-- ============================================================================
ALTER TABLE `rzx_shop_inquiries`
    ADD COLUMN `attachments` JSON DEFAULT NULL COMMENT '첨부파일 [{name, path, type, size}]' AFTER `answer`;
