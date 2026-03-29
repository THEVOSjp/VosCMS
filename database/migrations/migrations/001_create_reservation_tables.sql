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
    `id` CHAR(36) NOT NULL COMMENT 'UUID',
    `category_id` INT UNSIGNED NULL COMMENT '카테고리 ID',
    `name` VARCHAR(200) NOT NULL COMMENT '서비스명',
    `slug` VARCHAR(200) NOT NULL COMMENT 'URL 슬러그',
    `description` TEXT NULL COMMENT '상세 설명',
    `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '가격',
    `duration` INT NOT NULL DEFAULT 30 COMMENT '소요시간 (분)',
    `buffer_time` INT DEFAULT 0 COMMENT '예약 간 버퍼 시간 (분)',
    `image` VARCHAR(255) NULL COMMENT '대표 이미지',
    `sort_order` INT DEFAULT 0 COMMENT '정렬 순서',
    `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성 상태',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
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

-- 기본 카테고리 (미용실)
INSERT INTO `rzx_service_categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('헤어', 'hair', '헤어 스타일링 서비스', 1),
('네일', 'nail', '네일 아트 및 케어', 2),
('마사지', 'massage', '바디 마사지 서비스', 3),
('컨설팅', 'consulting', '전문 상담 서비스', 4),
('메이크업', 'makeup', '메이크업 서비스', 5)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- 기본 서비스 (미용실) - UUID는 고정값 사용
SET @cat_hair = (SELECT id FROM `rzx_service_categories` WHERE slug='hair');
SET @cat_nail = (SELECT id FROM `rzx_service_categories` WHERE slug='nail');
SET @cat_massage = (SELECT id FROM `rzx_service_categories` WHERE slug='massage');
SET @cat_consulting = (SELECT id FROM `rzx_service_categories` WHERE slug='consulting');
SET @cat_makeup = (SELECT id FROM `rzx_service_categories` WHERE slug='makeup');

INSERT INTO `rzx_services` (`id`, `category_id`, `name`, `slug`, `description`, `price`, `duration`, `buffer_time`, `sort_order`) VALUES
-- 헤어 (12개)
('00000000-0000-0000-0000-000000000001', @cat_hair, '여성 커트', 'women-cut', '샴푸, 커트, 드라이 포함', 35000, 60, 10, 1),
('00000000-0000-0000-0000-000000000002', @cat_hair, '남성 커트', 'men-cut', '샴푸, 커트, 드라이 포함', 20000, 30, 5, 2),
('00000000-0000-0000-0000-000000000003', @cat_hair, '아동 커트', 'kids-cut', '12세 이하 아동', 15000, 30, 5, 3),
('00000000-0000-0000-0000-000000000004', @cat_hair, '샴푸 & 블로우드라이', 'shampoo-blowdry', '샴푸 후 스타일링 블로우', 20000, 40, 5, 4),
('00000000-0000-0000-0000-000000000005', @cat_hair, '일반 펌', 'perm', '일반 펌 시술 (샴푸, 트리트먼트 포함)', 80000, 120, 10, 5),
('00000000-0000-0000-0000-000000000006', @cat_hair, '디지털 펌', 'digital-perm', '열펌 시술로 자연스러운 컬', 100000, 150, 10, 6),
('00000000-0000-0000-0000-000000000007', @cat_hair, '염색', 'color', '전체 염색 (뿌리~끝)', 70000, 90, 10, 7),
('00000000-0000-0000-0000-000000000008', @cat_hair, '하이라이트', 'highlights', '부분 하이라이트 염색', 90000, 120, 10, 8),
('00000000-0000-0000-0000-000000000009', @cat_hair, '탈색', 'bleach', '전체 탈색 1회', 80000, 90, 10, 9),
('00000000-0000-0000-0000-00000000000a', @cat_hair, '트리트먼트', 'treatment', '손상 모발 집중 트리트먼트', 40000, 45, 5, 10),
('00000000-0000-0000-0000-00000000000b', @cat_hair, '두피 케어', 'scalp-care', '두피 스케일링 및 영양 공급', 50000, 60, 10, 11),
('00000000-0000-0000-0000-00000000000c', @cat_hair, '앞머리 다듬기', 'bang-trim', '앞머리만 간단히 다듬기', 5000, 10, 0, 12),
-- 네일 (4개)
('00000000-0000-0000-0000-000000000101', @cat_nail, '젤 네일', 'gel-nail', '젤 네일 풀세트', 50000, 60, 5, 13),
('00000000-0000-0000-0000-000000000102', @cat_nail, '네일 아트', 'nail-art', '디자인 네일 아트 (10본)', 70000, 90, 5, 14),
('00000000-0000-0000-0000-000000000103', @cat_nail, '네일 케어', 'nail-care', '큐티클 정리 및 손톱 케어', 30000, 40, 5, 15),
('00000000-0000-0000-0000-000000000104', @cat_nail, '젤 제거', 'nail-removal', '기존 젤 네일 제거', 15000, 20, 5, 16),
-- 메이크업 (3개)
('00000000-0000-0000-0000-000000000201', @cat_makeup, '데일리 메이크업', 'daily-makeup', '자연스러운 데일리 메이크업', 50000, 45, 5, 17),
('00000000-0000-0000-0000-000000000202', @cat_makeup, '웨딩 메이크업', 'wedding-makeup', '웨딩 전문 메이크업 (리허설 포함)', 150000, 90, 10, 18),
('00000000-0000-0000-0000-000000000203', @cat_makeup, '특수 메이크업', 'special-makeup', '파티, 촬영 등 특수 메이크업', 80000, 60, 5, 19),
-- 마사지 (2개)
('00000000-0000-0000-0000-000000000301', @cat_massage, '헤드 스파', 'head-spa', '두피 마사지 & 딥 클렌징 스파', 60000, 60, 10, 20),
('00000000-0000-0000-0000-000000000302', @cat_massage, '어깨 마사지', 'shoulder-massage', '시술 중 목·어깨 마사지 추가', 40000, 30, 5, 21),
-- 컨설팅 (2개)
('00000000-0000-0000-0000-000000000401', @cat_consulting, '헤어 상담', 'hair-consulting', '무료 헤어 스타일 상담', 0, 15, 0, 22),
('00000000-0000-0000-0000-000000000402', @cat_consulting, '스타일 컨설팅', 'style-consulting', '얼굴형·피부톤에 맞는 종합 스타일 제안', 30000, 30, 5, 23)
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
