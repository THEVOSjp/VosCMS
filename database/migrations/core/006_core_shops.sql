-- ============================================================================
-- VosCMS Core: Shop System (업소 등록/리뷰/쿠폰/즐겨찾기)
-- ============================================================================
SET NAMES utf8mb4;

-- 업종 카테고리
CREATE TABLE IF NOT EXISTS `rzx_shop_categories` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(50) NOT NULL,
    `name` JSON NOT NULL,
    `icon` VARCHAR(100) DEFAULT NULL,
    `sort_order` INT DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 업소
CREATE TABLE IF NOT EXISTS `rzx_shops` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uuid` CHAR(36) NOT NULL,
    `user_id` CHAR(36) DEFAULT NULL COMMENT '등록한 회원',
    `category_id` INT UNSIGNED DEFAULT NULL,
    `name` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `phone` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `website` VARCHAR(500) DEFAULT NULL,
    `country` VARCHAR(10) DEFAULT NULL COMMENT '국가 코드 (JP, KR, US...)',
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `address` VARCHAR(500) DEFAULT NULL,
    `address_detail` VARCHAR(200) DEFAULT NULL,
    `latitude` DECIMAL(10,7) DEFAULT NULL,
    `longitude` DECIMAL(10,7) DEFAULT NULL,
    `images` JSON DEFAULT NULL COMMENT '매장 사진 URL 배열',
    `logo` VARCHAR(500) DEFAULT NULL,
    `cover_image` VARCHAR(500) DEFAULT NULL,
    `business_hours` JSON DEFAULT NULL COMMENT '요일별 영업시간',
    `holidays` JSON DEFAULT NULL COMMENT '정기 휴무일',
    `sns` JSON DEFAULT NULL COMMENT 'SNS 링크 (instagram, facebook, line...)',
    `features` JSON DEFAULT NULL COMMENT '특징 태그 (주차가능, 카드결제...)',
    `plan` VARCHAR(20) DEFAULT 'free' COMMENT 'free, premium, pro',
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, active, suspended, rejected',
    `view_count` INT DEFAULT 0,
    `review_count` INT DEFAULT 0,
    `rating_avg` DECIMAL(2,1) DEFAULT 0.0,
    `favorite_count` INT DEFAULT 0,
    `rezlyx_url` VARCHAR(500) DEFAULT NULL COMMENT 'RezlyX 도입 시 예약 페이지 URL',
    `is_verified` TINYINT(1) DEFAULT 0,
    `verified_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_uuid` (`uuid`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category` (`category_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    KEY `idx_country` (`country`),
    KEY `idx_location` (`latitude`, `longitude`),
    KEY `idx_rating` (`rating_avg` DESC),
    KEY `idx_plan` (`plan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 업소 다국어 (이름, 설명 등)
CREATE TABLE IF NOT EXISTS `rzx_shop_translations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT UNSIGNED NOT NULL,
    `locale` VARCHAR(10) NOT NULL,
    `name` VARCHAR(200) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `address` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_shop_locale` (`shop_id`, `locale`),
    KEY `idx_shop` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 리뷰
CREATE TABLE IF NOT EXISTS `rzx_shop_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT UNSIGNED NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `rating` TINYINT NOT NULL DEFAULT 5 COMMENT '1~5',
    `rating_service` TINYINT DEFAULT NULL COMMENT '서비스 평점',
    `rating_price` TINYINT DEFAULT NULL COMMENT '가격 평점',
    `rating_atmosphere` TINYINT DEFAULT NULL COMMENT '분위기 평점',
    `rating_access` TINYINT DEFAULT NULL COMMENT '접근성 평점',
    `content` TEXT DEFAULT NULL,
    `images` JSON DEFAULT NULL COMMENT '리뷰 사진',
    `is_verified` TINYINT(1) DEFAULT 0 COMMENT '실제 방문 인증',
    `helpful_count` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'active' COMMENT 'active, hidden, reported',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_shop` (`shop_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_rating` (`rating`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 즐겨찾기 (찜)
CREATE TABLE IF NOT EXISTS `rzx_shop_favorites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT UNSIGNED NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_shop` (`user_id`, `shop_id`),
    KEY `idx_shop` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 쿠폰
CREATE TABLE IF NOT EXISTS `rzx_coupons` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `discount_type` VARCHAR(20) DEFAULT 'percent' COMMENT 'percent, amount',
    `discount_value` DECIMAL(10,2) DEFAULT 0,
    `min_amount` DECIMAL(10,2) DEFAULT 0 COMMENT '최소 결제 금액',
    `max_downloads` INT DEFAULT NULL COMMENT '발행 수량 제한 (NULL=무제한)',
    `download_count` INT DEFAULT 0,
    `usage_count` INT DEFAULT 0,
    `conditions` JSON DEFAULT NULL COMMENT '사용 조건 (첫방문, 특정 서비스...)',
    `image` VARCHAR(500) DEFAULT NULL,
    `code` VARCHAR(50) DEFAULT NULL COMMENT '쿠폰 코드',
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_shop` (`shop_id`),
    KEY `idx_active_date` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 쿠폰 다운로드 기록
CREATE TABLE IF NOT EXISTS `rzx_coupon_downloads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT UNSIGNED NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `qr_code` VARCHAR(100) DEFAULT NULL COMMENT 'QR 코드 값',
    `is_used` TINYINT(1) DEFAULT 0,
    `used_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_coupon` (`user_id`, `coupon_id`),
    KEY `idx_coupon` (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 업소 이벤트/프로모션
CREATE TABLE IF NOT EXISTS `rzx_shop_events` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `shop_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `discount_info` VARCHAR(200) DEFAULT NULL COMMENT '할인 정보 텍스트',
    `start_date` DATETIME DEFAULT NULL,
    `end_date` DATETIME DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `view_count` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_shop` (`shop_id`),
    KEY `idx_active_date` (`is_active`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 스타일북 (시술 결과 사진 공유)
CREATE TABLE IF NOT EXISTS `rzx_style_posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` CHAR(36) NOT NULL,
    `shop_id` INT UNSIGNED DEFAULT NULL,
    `staff_name` VARCHAR(100) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL COMMENT 'hair, nail, skin...',
    `images` JSON NOT NULL COMMENT '사진 배열 [{url, type: before/after/result}]',
    `tags` JSON DEFAULT NULL COMMENT '스타일 태그 ["숏","펌","컬러"]',
    `content` VARCHAR(500) DEFAULT NULL COMMENT '한줄평',
    `like_count` INT DEFAULT 0,
    `comment_count` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'active' COMMENT 'active, hidden, reported',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_shop` (`shop_id`),
    KEY `idx_category` (`category`),
    KEY `idx_likes` (`like_count` DESC),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 스타일북 좋아요
CREATE TABLE IF NOT EXISTS `rzx_style_likes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `style_post_id` INT UNSIGNED NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_user_post` (`user_id`, `style_post_id`),
    KEY `idx_post` (`style_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 스타일북 댓글
CREATE TABLE IF NOT EXISTS `rzx_style_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `style_post_id` INT UNSIGNED NOT NULL,
    `user_id` CHAR(36) NOT NULL,
    `content` VARCHAR(500) NOT NULL,
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_post` (`style_post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 기본 카테고리 데이터 (11개 업종)
-- ============================================================================
INSERT IGNORE INTO `rzx_shop_categories` (`slug`, `name`, `icon`, `sort_order`) VALUES
('hair', '{"ko":"미용실 / 헤어살롱","en":"Hair Salon","ja":"ヘアサロン","de":"Friseursalon","es":"Peluquería","fr":"Salon de Coiffure","id":"Salon Rambut","mn":"Үсчин","ru":"Парикмахерская","tr":"Kuaför","vi":"Salon Tóc","zh_CN":"美发沙龙","zh_TW":"美髮沙龍"}', 'scissors', 1),
('nail', '{"ko":"네일 / 에스테틱","en":"Nail & Aesthetic","ja":"ネイル・エステ","de":"Nagel & Ästhetik","es":"Uñas y Estética","fr":"Nail & Esthétique","id":"Nail & Estetika","mn":"Хумс & Гоо сайхан","ru":"Ногти и Эстетика","tr":"Tırnak & Estetik","vi":"Nail & Thẩm mỹ","zh_CN":"美甲美容","zh_TW":"美甲美容"}', 'sparkles', 2),
('clinic', '{"ko":"클리닉 / 병원","en":"Clinic & Hospital","ja":"クリニック・病院","de":"Klinik & Krankenhaus","es":"Clínica y Hospital","fr":"Clinique & Hôpital","id":"Klinik & Rumah Sakit","mn":"Эмнэлэг","ru":"Клиника и Больница","tr":"Klinik & Hastane","vi":"Phòng khám & Bệnh viện","zh_CN":"诊所医院","zh_TW":"診所醫院"}', 'heart', 3),
('fitness', '{"ko":"피트니스 / 요가","en":"Fitness & Yoga","ja":"フィットネス・ヨガ","de":"Fitness & Yoga","es":"Fitness y Yoga","fr":"Fitness & Yoga","id":"Fitness & Yoga","mn":"Фитнесс & Йог","ru":"Фитнес и Йога","tr":"Fitness & Yoga","vi":"Fitness & Yoga","zh_CN":"健身瑜伽","zh_TW":"健身瑜伽"}', 'lightning', 4),
('restaurant', '{"ko":"레스토랑 / 카페","en":"Restaurant & Cafe","ja":"レストラン・カフェ","de":"Restaurant & Café","es":"Restaurante y Café","fr":"Restaurant & Café","id":"Restoran & Kafe","mn":"Ресторан & Кафе","ru":"Ресторан и Кафе","tr":"Restoran & Kafe","vi":"Nhà hàng & Cafe","zh_CN":"餐厅咖啡","zh_TW":"餐廳咖啡"}', 'gift', 5),
('education', '{"ko":"교육 / 컨설팅","en":"Education & Consulting","ja":"教育・コンサルティング","de":"Bildung & Beratung","es":"Educación y Consultoría","fr":"Éducation & Conseil","id":"Pendidikan & Konsultasi","mn":"Боловсрол & Зөвлөх","ru":"Образование и Консалтинг","tr":"Eğitim & Danışmanlık","vi":"Giáo dục & Tư vấn","zh_CN":"教育咨询","zh_TW":"教育諮詢"}', 'star', 6),
('pet', '{"ko":"반려동물 / 펫살롱","en":"Pet Salon","ja":"ペットサロン","de":"Tiersalon","es":"Salón de Mascotas","fr":"Salon pour Animaux","id":"Salon Hewan","mn":"Тэжээвэр амьтан","ru":"Салон для Животных","tr":"Evcil Hayvan Salonu","vi":"Salon Thú cưng","zh_CN":"宠物沙龙","zh_TW":"寵物沙龍"}', 'heart', 7),
('photo', '{"ko":"사진 스튜디오","en":"Photo Studio","ja":"フォトスタジオ","de":"Fotostudio","es":"Estudio Fotográfico","fr":"Studio Photo","id":"Studio Foto","mn":"Фото Студи","ru":"Фотостудия","tr":"Fotoğraf Stüdyosu","vi":"Studio Ảnh","zh_CN":"摄影工作室","zh_TW":"攝影工作室"}', 'camera', 8),
('rental', '{"ko":"렌탈 / 공유 오피스","en":"Rental & Co-working","ja":"レンタル・コワーキング","de":"Vermietung & Co-Working","es":"Alquiler y Coworking","fr":"Location & Coworking","id":"Sewa & Co-working","mn":"Түрээс & Хамтран","ru":"Аренда и Коворкинг","tr":"Kiralama & Co-working","vi":"Cho thuê & Co-working","zh_CN":"租赁共享办公","zh_TW":"租賃共享辦公"}', 'globe', 9),
('hotel', '{"ko":"숙박업 / 호텔","en":"Hotel & Accommodation","ja":"ホテル・宿泊","de":"Hotel & Unterkunft","es":"Hotel y Alojamiento","fr":"Hôtel & Hébergement","id":"Hotel & Akomodasi","mn":"Зочид буудал","ru":"Отель и Проживание","tr":"Otel & Konaklama","vi":"Khách sạn & Lưu trú","zh_CN":"酒店住宿","zh_TW":"酒店住宿"}', 'shield', 10),
('spa', '{"ko":"마사지 / 스파","en":"Massage & Spa","ja":"マッサージ・スパ","de":"Massage & Spa","es":"Masaje y Spa","fr":"Massage & Spa","id":"Pijat & Spa","mn":"Массаж & Спа","ru":"Массаж и Спа","tr":"Masaj & Spa","vi":"Massage & Spa","zh_CN":"按摩水疗","zh_TW":"按摩水療"}', 'sparkles', 11);
