-- ============================================================================
-- RezlyX Social Accounts Migration
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- User Social Accounts Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_user_social_accounts` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `provider` VARCHAR(20) NOT NULL COMMENT 'line, google, kakao, naver',
    `provider_id` VARCHAR(255) NOT NULL,
    `provider_email` VARCHAR(255) NULL,
    `provider_name` VARCHAR(100) NULL,
    `provider_avatar` VARCHAR(500) NULL,
    `access_token` TEXT NULL,
    `refresh_token` TEXT NULL,
    `token_expires_at` TIMESTAMP NULL,
    `raw_data` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_provider_user` (`provider`, `provider_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_provider` (`provider`),
    CONSTRAINT `fk_social_user` FOREIGN KEY (`user_id`) REFERENCES `rzx_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- User Remember Tokens Table (if not exists via Auth class)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_user_remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_token` (`token`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('002_create_social_accounts', 2);
