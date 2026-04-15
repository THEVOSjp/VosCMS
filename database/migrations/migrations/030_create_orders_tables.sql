-- ============================================================
-- VosCMS 서비스 주문 + 구독 테이블
-- Migration: 030_create_orders_tables
-- Date: 2026-04-15
-- ============================================================

-- 서비스 주문
CREATE TABLE IF NOT EXISTS rzx_orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  uuid CHAR(36) NOT NULL,
  order_number VARCHAR(20) NOT NULL,
  user_id CHAR(36) NOT NULL,
  status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, paid, active, expired, cancelled, failed',

  -- 주문 내용
  items JSON NOT NULL COMMENT '[{type, label, qty, unit, unit_price, amount, ...}]',

  -- 금액
  subtotal DECIMAL(12,2) DEFAULT 0 COMMENT '소계 (세전)',
  tax_rate DECIMAL(5,2) DEFAULT 10.00 COMMENT '세율 (%)',
  tax DECIMAL(12,2) DEFAULT 0 COMMENT '부가세',
  total DECIMAL(12,2) DEFAULT 0 COMMENT '최종 결제 금액',
  currency VARCHAR(3) DEFAULT 'JPY',

  -- 계약 기간
  contract_months INT DEFAULT 12,
  started_at DATETIME NULL,
  expires_at DATETIME NULL,

  -- 결제 연결
  payment_id INT UNSIGNED NULL COMMENT 'rzx_payments.id',
  payment_method VARCHAR(20) COMMENT 'card, bank',
  payment_gateway VARCHAR(50) COMMENT 'payjp, stripe, toss, portone',

  -- 서비스 정보
  domain VARCHAR(255) NULL COMMENT '도메인 (구입/무료/보유)',
  domain_option VARCHAR(20) NULL COMMENT 'free, new, existing',
  hosting_plan VARCHAR(100) NULL COMMENT '호스팅 플랜명',
  hosting_capacity VARCHAR(20) NULL COMMENT '호스팅 용량',

  -- 신청자 정보
  applicant_name VARCHAR(100),
  applicant_email VARCHAR(255),
  applicant_phone VARCHAR(50),
  applicant_company VARCHAR(200),
  applicant_category VARCHAR(100),

  notes TEXT,
  admin_notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY uk_uuid (uuid),
  UNIQUE KEY uk_order_number (order_number),
  INDEX idx_user (user_id),
  INDEX idx_status (status),
  INDEX idx_expires (expires_at),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 구독 (서비스별 자동연장 관리)
CREATE TABLE IF NOT EXISTS rzx_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL COMMENT 'rzx_orders.id',
  user_id CHAR(36) NOT NULL,
  type VARCHAR(30) NOT NULL COMMENT 'hosting, domain, maintenance, mail, support',
  label VARCHAR(200) COMMENT '서비스 표시명',

  -- 금액
  unit_price DECIMAL(12,2) DEFAULT 0 COMMENT '단가',
  quantity INT DEFAULT 1 COMMENT '수량 (메일 계정 수 등)',
  billing_amount DECIMAL(12,2) DEFAULT 0 COMMENT '갱신 시 청구 금액',
  billing_cycle VARCHAR(10) DEFAULT 'monthly' COMMENT 'monthly, yearly, custom',
  billing_months INT DEFAULT 12 COMMENT '갱신 주기 (개월)',
  currency VARCHAR(3) DEFAULT 'JPY',

  -- 기간
  started_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  next_billing_at DATETIME NULL COMMENT '다음 결제 예정일',

  -- 자동연장
  auto_renew TINYINT(1) DEFAULT 1,
  renew_notified_at DATETIME NULL COMMENT '갱신 안내 메일 발송일',
  retry_count TINYINT DEFAULT 0 COMMENT '결제 재시도 횟수',
  last_retry_at DATETIME NULL,

  -- 결제 고객 (카드 저장)
  payment_customer_id VARCHAR(100) NULL COMMENT 'PG Customer ID (PAY.JP/Stripe)',
  payment_gateway VARCHAR(50) NULL,

  -- 상태
  status VARCHAR(20) DEFAULT 'active' COMMENT 'active, expired, cancelled, suspended, pending',
  cancelled_at DATETIME NULL,
  cancel_reason VARCHAR(500),
  suspended_at DATETIME NULL,

  -- 상세 정보
  metadata JSON NULL COMMENT '서비스별 추가 정보 (도메인명, 플랜 상세 등)',

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_order (order_id),
  INDEX idx_user (user_id),
  INDEX idx_type (type),
  INDEX idx_status (status),
  INDEX idx_expires (expires_at),
  INDEX idx_next_billing (next_billing_at, auto_renew, status),
  INDEX idx_renew_check (status, auto_renew, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 주문 상태 변경 이력
CREATE TABLE IF NOT EXISTS rzx_order_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  action VARCHAR(50) NOT NULL COMMENT 'created, paid, activated, renewed, cancelled, suspended, failed',
  detail TEXT,
  actor_type VARCHAR(20) DEFAULT 'system' COMMENT 'system, user, admin, cron',
  actor_id VARCHAR(36) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_order (order_id),
  INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
