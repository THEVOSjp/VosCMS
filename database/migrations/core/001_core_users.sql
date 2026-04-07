-- ============================================================================
-- VosCMS Core: Users & Authentication
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `rzx_users` (
    `id` CHAR(36) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) DEFAULT NULL,
    `nick_name` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `phone_country` VARCHAR(10) DEFAULT NULL,
    `phone_number` VARCHAR(50) DEFAULT NULL,
    `avatar` VARCHAR(500) DEFAULT NULL,
    `birth_date` DATE DEFAULT NULL,
    `gender` ENUM('male','female','other') DEFAULT NULL,
    `company` VARCHAR(200) DEFAULT NULL,
    `blog` VARCHAR(500) DEFAULT NULL,
    `profile_image` VARCHAR(500) DEFAULT NULL,
    `role` VARCHAR(20) DEFAULT 'member',
    `permissions` JSON DEFAULT NULL,
    `grade_id` INT UNSIGNED DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `email_verified_at` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `furigana` VARCHAR(200) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_admins` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) DEFAULT NULL,
    `staff_id` INT DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `role` ENUM('master','manager','staff') DEFAULT 'manager',
    `permissions` LONGTEXT DEFAULT NULL,
    `status` ENUM('active','inactive') DEFAULT 'active',
    `is_master` TINYINT(1) DEFAULT 0,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_user` (`user_id`),
    UNIQUE KEY `uk_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_member_grades` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `level` INT DEFAULT 0,
    `min_reservations` INT DEFAULT 0,
    `min_spent` DECIMAL(10,2) DEFAULT 0,
    `discount_rate` DECIMAL(5,2) DEFAULT 0,
    `point_rate` DECIMAL(5,2) DEFAULT 0,
    `benefits` TEXT DEFAULT NULL,
    `color` VARCHAR(20) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_default` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_password_resets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_email` (`email`),
    KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_user_social_accounts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `provider` VARCHAR(30) NOT NULL,
    `provider_id` VARCHAR(255) NOT NULL,
    `provider_token` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_provider` (`provider`, `provider_id`),
    KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rzx_user_remember_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
