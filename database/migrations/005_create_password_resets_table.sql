-- ============================================================================
-- RezlyX Password Resets Table Migration
-- ============================================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- Password Resets Table (비밀번호 재설정 토큰 저장)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_password_resets` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_email` (`email`),
    KEY `idx_token` (`token`),
    KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('005_create_password_resets_table', 5);
