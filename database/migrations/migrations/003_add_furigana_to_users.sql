-- ============================================================================
-- Add furigana column to users table (for Japanese locale)
-- ============================================================================

ALTER TABLE `rzx_users`
ADD COLUMN `furigana` VARCHAR(200) NULL AFTER `name`;

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('003_add_furigana_to_users', 2);
