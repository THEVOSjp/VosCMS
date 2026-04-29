-- ============================================================================
-- Reservation Core Tables (RezlyX/예약 시스템 코어)
-- 기존 001_create_base_tables.sql 에서 분리됨
-- 호스팅 dist 빌드 시 build-voscms.sh exclude — 호스팅 고객은 예약 모듈 미설치
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Categories Table (레거시 — 사용처 없음, 호환성 유지)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_categories` (
    `id` CHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Services Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_services` (
    `id` CHAR(36) NOT NULL,
    `category_id` CHAR(36) NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(200) NOT NULL,
    `description` TEXT NULL,
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `duration` INT NOT NULL DEFAULT 30 COMMENT 'minutes',
    `buffer_time` INT DEFAULT 0 COMMENT 'minutes',
    `image` VARCHAR(255) NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    CONSTRAINT `fk_service_category` FOREIGN KEY (`category_id`) REFERENCES `rzx_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Service Options Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_service_options` (
    `id` CHAR(36) NOT NULL,
    `service_id` CHAR(36) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(12,2) DEFAULT 0,
    `duration` INT DEFAULT 0,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_service` (`service_id`),
    CONSTRAINT `fk_option_service` FOREIGN KEY (`service_id`) REFERENCES `rzx_services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Reservations Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_reservations` (
    `id` CHAR(36) NOT NULL,
    `reservation_number` VARCHAR(20) NOT NULL,
    `user_id` CHAR(36) NULL,
    `service_id` CHAR(36) NOT NULL,
    `staff_id` INT UNSIGNED NULL COMMENT '담당 스태프',
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_phone` VARCHAR(20) NOT NULL,
    `customer_email` VARCHAR(255) NULL,
    `reservation_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `discount_amount` DECIMAL(12,2) DEFAULT 0,
    `points_used` DECIMAL(12,2) DEFAULT 0,
    `designation_fee` DECIMAL(12,2) DEFAULT 0 COMMENT '지명료',
    `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'confirmed', 'completed', 'cancelled', 'no_show') DEFAULT 'pending',
    `notes` TEXT NULL,
    `admin_notes` TEXT NULL,
    `cancelled_at` TIMESTAMP NULL,
    `cancel_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_reservation_number` (`reservation_number`),
    KEY `idx_user` (`user_id`),
    KEY `idx_service` (`service_id`),
    KEY `idx_staff` (`staff_id`),
    KEY `idx_date` (`reservation_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_reservation_user` FOREIGN KEY (`user_id`) REFERENCES `rzx_users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_reservation_service` FOREIGN KEY (`service_id`) REFERENCES `rzx_services`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Payments Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_payments` (
    `id` CHAR(36) NOT NULL,
    `order_id` VARCHAR(50) NOT NULL,
    `reservation_id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NULL,
    `gateway` VARCHAR(20) NOT NULL DEFAULT 'toss',
    `method` VARCHAR(30) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `tax_amount` DECIMAL(12,2) DEFAULT 0,
    `discount_amount` DECIMAL(12,2) DEFAULT 0,
    `point_amount` DECIMAL(12,2) DEFAULT 0,
    `status` ENUM('pending', 'ready', 'paid', 'cancelled', 'partial_cancelled', 'failed', 'refunded') DEFAULT 'pending',
    `payment_key` VARCHAR(200) NULL,
    `receipt_url` VARCHAR(500) NULL,
    `paid_at` TIMESTAMP NULL,
    `failed_at` TIMESTAMP NULL,
    `failure_reason` TEXT NULL,
    `metadata` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_id` (`order_id`),
    KEY `idx_reservation` (`reservation_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_payment_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `rzx_reservations`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Points Transactions Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_point_transactions` (
    `id` CHAR(36) NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `type` ENUM('earn', 'use', 'cancel', 'expire', 'admin_add', 'admin_deduct', 'refund') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `balance_after` DECIMAL(12,2) NOT NULL,
    `source` VARCHAR(50) NULL,
    `source_id` CHAR(36) NULL,
    `description` VARCHAR(255) NULL,
    `expires_at` TIMESTAMP NULL,
    `admin_id` CHAR(36) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_type` (`type`),
    KEY `idx_expires` (`expires_at`),
    CONSTRAINT `fk_points_user` FOREIGN KEY (`user_id`) REFERENCES `rzx_users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Business Hours Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_business_hours` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `day_of_week` TINYINT NOT NULL COMMENT '0=Sunday, 6=Saturday',
    `is_open` TINYINT(1) DEFAULT 1,
    `open_time` TIME NULL,
    `close_time` TIME NULL,
    `break_start` TIME NULL,
    `break_end` TIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_day` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Holidays Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_holidays` (
    `id` CHAR(36) NOT NULL,
    `date` DATE NOT NULL,
    `name` VARCHAR(100) NULL,
    `is_recurring` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Staff Positions Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_staff_positions` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `name_i18n` JSON NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Staff Table
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rzx_staff` (
    `id` INT UNSIGNED AUTO_INCREMENT NOT NULL,
    `user_id` CHAR(36) NULL,
    `card_number` VARCHAR(50) NULL,
    `name` VARCHAR(100) NOT NULL,
    `name_i18n` JSON NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,
    `avatar` VARCHAR(255) NULL,
    `banner` VARCHAR(255) NULL COMMENT '배너/커버 이미지',
    `gallery` JSON NULL COMMENT '소개 사진 갤러리 [{url, caption}]',
    `bio` TEXT NULL,
    `bio_i18n` JSON NULL,
    `designation_fee` DECIMAL(12,2) DEFAULT 0,
    `position_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_card_number` (`card_number`),
    KEY `idx_user_id` (`user_id`),
    CONSTRAINT `fk_staff_position` FOREIGN KEY (`position_id`) REFERENCES `rzx_staff_positions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Reservation Initial Data
-- ============================================================================

-- Default business hours (Mon-Sat 9:00-21:00)
INSERT INTO `rzx_business_hours` (`day_of_week`, `is_open`, `open_time`, `close_time`, `break_start`, `break_end`)
VALUES
    (0, 0, NULL, NULL, NULL, NULL),
    (1, 1, '09:00', '21:00', '12:00', '13:00'),
    (2, 1, '09:00', '21:00', '12:00', '13:00'),
    (3, 1, '09:00', '21:00', '12:00', '13:00'),
    (4, 1, '09:00', '21:00', '12:00', '13:00'),
    (5, 1, '09:00', '21:00', '12:00', '13:00'),
    (6, 1, '10:00', '18:00', NULL, NULL);

-- Reservation 관련 settings 시드 (이전에 base 에 있던 것)
INSERT INTO `rzx_settings` (`key`, `value`) VALUES
    ('point_enabled', 'true'),
    ('point_rate', '3'),
    ('point_min_use', '1000'),
    ('point_max_use_rate', '50'),
    ('point_expiry_months', '12')
ON DUPLICATE KEY UPDATE `value`=VALUES(`value`);

-- Record migration
INSERT INTO `rzx_migrations` (`migration`, `batch`) VALUES ('015_create_reservation_core', 1);
