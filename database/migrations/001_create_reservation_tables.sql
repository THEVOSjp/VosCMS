-- ============================================================================
-- RezlyX 예약 시스템 데이터베이스 스키마
-- 버전: 1.0.0
-- 실행: mysql -u root -p rezlyx < 001_create_reservation_tables.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 서비스 카테고리 테이블
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_service_categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL COMMENT '카테고리명',
    `slug` VARCHAR(100) NOT NULL COMMENT 'URL 슬러그',
    `description` TEXT NULL COMMENT '설명',
    `image` VARCHAR(255) NULL COMMENT '이미지 경로',
    `parent_id` INT UNSIGNED NULL COMMENT '상위 카테고리 ID',
    `sort_order` INT NOT NULL DEFAULT 0 COMMENT '정렬 순서',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`)
        REFERENCES `rzx_service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='서비스 카테고리';

-- ============================================================================
-- 서비스 테이블
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_services` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL COMMENT '서비스명',
    `slug` VARCHAR(200) NOT NULL COMMENT 'URL 슬러그',
    `description` TEXT NULL COMMENT '상세 설명',
    `short_description` VARCHAR(500) NULL COMMENT '짧은 설명',
    `duration` INT NOT NULL DEFAULT 60 COMMENT '소요시간 (분)',
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '가격',
    `currency` VARCHAR(3) NOT NULL DEFAULT 'KRW' COMMENT '통화',
    `category_id` INT UNSIGNED NULL COMMENT '카테고리 ID',
    `image` VARCHAR(255) NULL COMMENT '대표 이미지',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `max_capacity` INT NOT NULL DEFAULT 1 COMMENT '동시 예약 가능 인원',
    `buffer_time` INT NOT NULL DEFAULT 0 COMMENT '예약 간 버퍼 시간 (분)',
    `advance_booking_days` INT NULL COMMENT '예약 가능 기간 (일)',
    `min_notice_hours` INT NULL COMMENT '최소 예약 사전 알림 (시간)',
    `meta` JSON NULL COMMENT '추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_service_category` FOREIGN KEY (`category_id`)
        REFERENCES `rzx_service_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='서비스/상품';

-- ============================================================================
-- 시간대 테이블
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_time_slots` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `service_id` INT UNSIGNED NULL COMMENT '서비스 ID (NULL이면 전체 적용)',
    `day_of_week` TINYINT NOT NULL COMMENT '요일 (0=일, 1=월, ..., 6=토)',
    `start_time` TIME NOT NULL COMMENT '시작 시간',
    `end_time` TIME NOT NULL COMMENT '종료 시간',
    `max_bookings` INT NOT NULL DEFAULT 1 COMMENT '최대 예약 수',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `specific_date` DATE NULL COMMENT '특정 날짜 (휴일/특별일)',
    `is_blocked` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '예약 차단 여부',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_service` (`service_id`),
    KEY `idx_day` (`day_of_week`),
    KEY `idx_specific_date` (`specific_date`),
    CONSTRAINT `fk_slot_service` FOREIGN KEY (`service_id`)
        REFERENCES `rzx_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예약 시간대';

-- ============================================================================
-- 예약 테이블
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_reservations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `booking_code` VARCHAR(20) NOT NULL COMMENT '예약번호',
    `service_id` INT UNSIGNED NOT NULL COMMENT '서비스 ID',
    `user_id` INT UNSIGNED NULL COMMENT '사용자 ID',
    `customer_name` VARCHAR(100) NOT NULL COMMENT '고객명',
    `customer_email` VARCHAR(255) NOT NULL COMMENT '고객 이메일',
    `customer_phone` VARCHAR(20) NULL COMMENT '고객 전화번호',
    `booking_date` DATE NOT NULL COMMENT '예약 날짜',
    `start_time` TIME NOT NULL COMMENT '시작 시간',
    `end_time` TIME NOT NULL COMMENT '종료 시간',
    `guests` INT NOT NULL DEFAULT 1 COMMENT '인원수',
    `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT '총 가격',
    `status` ENUM('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending' COMMENT '예약 상태',
    `payment_status` ENUM('pending','paid','refunded','partial') NOT NULL DEFAULT 'pending' COMMENT '결제 상태',
    `payment_method` VARCHAR(50) NULL COMMENT '결제 방법',
    `payment_id` VARCHAR(100) NULL COMMENT '결제 ID',
    `notes` TEXT NULL COMMENT '고객 메모',
    `admin_notes` TEXT NULL COMMENT '관리자 메모',
    `meta` JSON NULL COMMENT '추가 메타데이터',
    `confirmed_at` DATETIME NULL COMMENT '확정 일시',
    `cancelled_at` DATETIME NULL COMMENT '취소 일시',
    `cancellation_reason` TEXT NULL COMMENT '취소 사유',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_booking_code` (`booking_code`),
    KEY `idx_service` (`service_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_date` (`booking_date`),
    KEY `idx_status` (`status`),
    KEY `idx_email` (`customer_email`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_reservation_service` FOREIGN KEY (`service_id`)
        REFERENCES `rzx_services` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_reservation_user` FOREIGN KEY (`user_id`)
        REFERENCES `rzx_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예약';

-- ============================================================================
-- 사용자 테이블 (없는 경우에만 생성)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL COMMENT '이메일',
    `password` VARCHAR(255) NOT NULL COMMENT '비밀번호 (해시)',
    `name` VARCHAR(100) NOT NULL COMMENT '이름',
    `phone` VARCHAR(20) NULL COMMENT '전화번호',
    `role` ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user' COMMENT '역할',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '활성 상태',
    `email_verified_at` DATETIME NULL COMMENT '이메일 인증 일시',
    `remember_token` VARCHAR(100) NULL COMMENT '로그인 유지 토큰',
    `last_login_at` DATETIME NULL COMMENT '마지막 로그인',
    `meta` JSON NULL COMMENT '추가 메타데이터',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_email` (`email`),
    KEY `idx_role` (`role`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사용자';

-- ============================================================================
-- 설정 테이블
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL COMMENT '설정 키',
    `value` TEXT NULL COMMENT '설정 값',
    `type` VARCHAR(20) NOT NULL DEFAULT 'string' COMMENT '데이터 타입',
    `group` VARCHAR(50) NULL COMMENT '설정 그룹',
    `description` VARCHAR(255) NULL COMMENT '설명',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_key` (`key`),
    KEY `idx_group` (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시스템 설정';

-- ============================================================================
-- 예약 이력 테이블
-- ============================================================================
CREATE TABLE IF NOT EXISTS `rzx_reservation_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reservation_id` INT UNSIGNED NOT NULL COMMENT '예약 ID',
    `action` VARCHAR(50) NOT NULL COMMENT '액션 (created, updated, cancelled, etc.)',
    `old_data` JSON NULL COMMENT '이전 데이터',
    `new_data` JSON NULL COMMENT '새 데이터',
    `user_id` INT UNSIGNED NULL COMMENT '수행한 사용자 ID',
    `ip_address` VARCHAR(45) NULL COMMENT 'IP 주소',
    `user_agent` VARCHAR(255) NULL COMMENT 'User Agent',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reservation` (`reservation_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_log_reservation` FOREIGN KEY (`reservation_id`)
        REFERENCES `rzx_reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='예약 이력';

-- ============================================================================
-- 샘플 데이터
-- ============================================================================

-- 기본 설정
INSERT INTO `rzx_settings` (`key`, `value`, `type`, `group`, `description`) VALUES
('app_name', 'RezlyX', 'string', 'general', '애플리케이션 이름'),
('app_timezone', 'Asia/Seoul', 'string', 'general', '시간대'),
('app_locale', 'ko', 'string', 'general', '기본 언어'),
('admin_path', 'admin', 'string', 'general', '관리자 경로'),
('booking_auto_confirm', '0', 'boolean', 'booking', '예약 자동 확정'),
('booking_email_notification', '1', 'boolean', 'booking', '예약 이메일 알림'),
('booking_advance_days', '30', 'integer', 'booking', '기본 예약 가능 기간 (일)')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- 샘플 카테고리
INSERT INTO `rzx_service_categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('헤어', 'hair', '헤어 스타일링 서비스', 1),
('네일', 'nail', '네일 아트 및 케어', 2),
('마사지', 'massage', '바디 마사지 서비스', 3),
('컨설팅', 'consulting', '전문 상담 서비스', 4)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- 샘플 서비스
INSERT INTO `rzx_services` (`name`, `slug`, `description`, `short_description`, `duration`, `price`, `category_id`, `max_capacity`) VALUES
('기본 커트', 'basic-cut', '기본 헤어 커트 서비스입니다.', '깔끔한 기본 커트', 30, 20000, 1, 3),
('펌', 'perm', '볼륨 펌 또는 디지털 펌 서비스입니다.', '스타일리시한 펌 서비스', 120, 80000, 1, 2),
('젤 네일', 'gel-nail', '오래 지속되는 젤 네일 아트입니다.', '트렌디한 젤 네일', 60, 50000, 2, 4),
('전신 마사지', 'full-body-massage', '60분 전신 힐링 마사지입니다.', '피로 회복 마사지', 60, 70000, 3, 5),
('비즈니스 컨설팅', 'business-consulting', '비즈니스 전략 상담 서비스입니다.', '1:1 맞춤 컨설팅', 60, 100000, 4, 1)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- 기본 시간대 (월~금 09:00~18:00)
INSERT INTO `rzx_time_slots` (`service_id`, `day_of_week`, `start_time`, `end_time`, `max_bookings`) VALUES
(NULL, 1, '09:00:00', '18:00:00', 10),
(NULL, 2, '09:00:00', '18:00:00', 10),
(NULL, 3, '09:00:00', '18:00:00', 10),
(NULL, 4, '09:00:00', '18:00:00', 10),
(NULL, 5, '09:00:00', '18:00:00', 10),
(NULL, 6, '10:00:00', '16:00:00', 5)
ON DUPLICATE KEY UPDATE `start_time` = VALUES(`start_time`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- 완료 메시지
-- ============================================================================
SELECT '예약 시스템 테이블 생성 완료!' AS message;
