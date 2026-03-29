# RezlyX Order System (주문 시스템)

> 독립 모듈로 개발 — RezlyX 코어와 분리된 테이블 오더 시스템

---

## 1. 개요

### 배경
RezlyX는 범용 예약 관리 시스템이나, 요식업 적용 시 **예약 외에 매장 내 메뉴 주문** 기능이 필수적이다. 기존 POS 시스템과 연계하되, 고객이 직접 주문할 수 있는 셀프 오더 시스템을 독립 모듈로 구현한다.

### 명칭
- **모듈명:** Order System (주문 시스템)
- **URL 접두사:** `/order/`
- **DB 접두사:** `rzx_order_`
- **관리자 메뉴:** 주문 관리 (`/admin/orders/`)

### 설계 원칙
- RezlyX 코어(예약/회원/설정)와 **완전 분리된 모듈**
- 공유하는 것: DB 연결, 인증, 다국어, 레이아웃, 설정 시스템
- 공유하지 않는 것: 서비스 테이블, 예약 테이블, 번들 테이블
- 주문 시스템만 단독 활성화/비활성화 가능

---

## 2. 주문 방식

### 2-1. QR 코드 주문 (고객 휴대폰)

```
고객 스마트폰 → QR 스캔 → https://{domain}/order?table=5
                        → 로그인 불필요 (세션 기반 임시 주문자)
                        → 메뉴 탐색 → 장바구니 → 주문 확정
                        → POS/관리 화면에 실시간 수신
```

| 항목 | 내용 |
|------|------|
| QR 내용 | `https://{domain}/order?table={table_id}` |
| 테이블 식별 | URL 파라미터로 테이블 번호 전달 |
| 로그인 | 불필요 (세션 + 쿠키로 임시 주문자 식별) |
| 언어 | 고객 브라우저 언어 자동 감지 → 다국어 메뉴 표시 |
| 장점 | 하드웨어 비용 없음, 외국인 고객 자동 대응 |

### 2-2. 태블릿 주문 (매장 비치)

```
매장 태블릿 → https://{domain}/order/kiosk?table=5
           → 키오스크 모드 (풀스크린, 네비게이션 차단)
           → 메뉴 탐색 → 장바구니 → 주문 확정
           → 일정 시간 무조작 시 자동 초기화
```

| 항목 | 내용 |
|------|------|
| 접속 URL | `/order/kiosk?table={table_id}` |
| 테이블 식별 | 태블릿별 고정 URL (한번 설정하면 변경 불필요) |
| 모드 | 키오스크 모드 (뒤로가기 차단, 풀스크린) |
| 초기화 | 주문 완료 또는 3분 무조작 시 메인 화면 복귀 |
| 장점 | 큰 사진, 직관적 조작, 추가 주문 용이 |

### 2-3. 공통 백엔드

두 방식 모두 **동일한 API, 메뉴 데이터, 주문 처리 로직** 사용.
프론트엔드만 모바일용 / 태블릿용으로 분리.

---

## 3. 기능 목록

### 3-1. 관리자 (Admin)

| 기능 | 설명 |
|------|------|
| **메뉴 카테고리 관리** | 카테고리 CRUD, 정렬, 아이콘/이미지 |
| **메뉴 아이템 관리** | 이름, 가격, 사진, 설명, 옵션, 품절 처리 |
| **메뉴 옵션 관리** | 옵션 그룹 (맛, 사이즈 등) + 옵션 아이템 + 추가금액 |
| **테이블/좌석 관리** | 테이블 번호, 이름, 구역, 인원, 상태 |
| **QR 코드 생성** | 테이블별 QR 이미지 생성 + 일괄 인쇄 |
| **주문 현황 대시보드** | 실시간 주문 목록, 상태 변경 (접수→조리중→완료) |
| **주문 내역** | 날짜별 주문 조회, 매출 집계 |
| **주문 시스템 설정** | 활성화/비활성화, 운영시간, 주문 알림음 |

### 3-2. 고객 (Customer)

| 기능 | 설명 |
|------|------|
| **메뉴 탐색** | 카테고리별 메뉴 목록, 사진, 가격, 설명 |
| **메뉴 상세** | 옵션 선택 (필수/선택), 수량 조절 |
| **장바구니** | 추가/수정/삭제, 합계 금액 |
| **주문 확정** | 주문 전송, 주문번호 발급 |
| **주문 상태 확인** | 현재 주문 진행 상태 표시 |
| **추가 주문** | 같은 테이블에서 추가 주문 가능 |
| **직원 호출** | 호출 버튼 (POS에 알림) |

---

## 4. 데이터베이스 설계

### 메뉴 관련 (서비스 테이블과 별도)

```sql
-- 메뉴 카테고리
CREATE TABLE rzx_order_categories (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    image VARCHAR(500) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 메뉴 아이템
CREATE TABLE rzx_order_items (
    id CHAR(36) PRIMARY KEY,
    category_id CHAR(36) NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    image VARCHAR(500) DEFAULT NULL,
    is_soldout TINYINT(1) DEFAULT 0,
    is_popular TINYINT(1) DEFAULT 0,
    is_new TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_active_sort (is_active, sort_order)
);

-- 옵션 그룹 (맛, 사이즈, 토핑 등)
CREATE TABLE rzx_order_option_groups (
    id CHAR(36) PRIMARY KEY,
    item_id CHAR(36) DEFAULT NULL,          -- NULL이면 공용 옵션
    name VARCHAR(100) NOT NULL,             -- "맛 선택", "사이즈"
    is_required TINYINT(1) DEFAULT 0,       -- 필수 선택 여부
    min_select INT DEFAULT 0,               -- 최소 선택 수
    max_select INT DEFAULT 1,               -- 최대 선택 수 (1=단일, N=다중)
    sort_order INT DEFAULT 0
);

-- 옵션 아이템
CREATE TABLE rzx_order_options (
    id CHAR(36) PRIMARY KEY,
    group_id CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,             -- "순한맛", "보통", "매운맛"
    price_add DECIMAL(10,2) DEFAULT 0,      -- 추가 금액
    is_default TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    INDEX idx_group (group_id)
);
```

### 테이블/좌석 관리

```sql
-- 구역
CREATE TABLE rzx_order_zones (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,             -- "홀", "테라스", "룸"
    sort_order INT DEFAULT 0
);

-- 테이블
CREATE TABLE rzx_order_tables (
    id CHAR(36) PRIMARY KEY,
    zone_id CHAR(36) DEFAULT NULL,
    table_number VARCHAR(20) NOT NULL,      -- "1", "A-3" 등
    name VARCHAR(100) DEFAULT NULL,         -- "창가석", "단체석"
    seats INT DEFAULT 4,
    status ENUM('available','occupied','reserved','closed') DEFAULT 'available',
    qr_token VARCHAR(64) DEFAULT NULL,      -- QR 고유 토큰
    UNIQUE KEY uk_number (table_number),
    UNIQUE KEY uk_qr (qr_token)
);
```

### 주문

```sql
-- 주문
CREATE TABLE rzx_order_orders (
    id CHAR(36) PRIMARY KEY,
    table_id CHAR(36) NOT NULL,
    order_number VARCHAR(20) NOT NULL,      -- "20260321-001"
    session_id VARCHAR(128) DEFAULT NULL,   -- 비로그인 고객 식별
    user_id CHAR(36) DEFAULT NULL,          -- 로그인 고객 (선택)
    status ENUM('pending','accepted','preparing','ready','served','cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0,
    note TEXT DEFAULT NULL,                 -- 고객 요청사항
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_table (table_id),
    INDEX idx_status (status),
    INDEX idx_date (created_at)
);

-- 주문 아이템
CREATE TABLE rzx_order_order_items (
    id CHAR(36) PRIMARY KEY,
    order_id CHAR(36) NOT NULL,
    item_id CHAR(36) NOT NULL,
    item_name VARCHAR(200) NOT NULL,        -- 주문 시점 메뉴명 스냅샷
    item_price DECIMAL(10,2) NOT NULL,      -- 주문 시점 가격 스냅샷
    quantity INT NOT NULL DEFAULT 1,
    options_json JSON DEFAULT NULL,         -- 선택된 옵션 스냅샷
    options_price DECIMAL(10,2) DEFAULT 0,  -- 옵션 추가 금액 합계
    subtotal DECIMAL(10,2) NOT NULL,        -- (item_price + options_price) × quantity
    INDEX idx_order (order_id)
);

-- 직원 호출
CREATE TABLE rzx_order_calls (
    id CHAR(36) PRIMARY KEY,
    table_id CHAR(36) NOT NULL,
    type ENUM('call','water','bill') DEFAULT 'call',
    status ENUM('pending','acknowledged') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pending (status, created_at)
);
```

---

## 5. 파일 구조

```
rzxlib/
  Order/                              # 주문 시스템 모듈
    Controllers/
      OrderAdminController.php        # 관리자 API
      OrderCustomerController.php     # 고객 주문 API
    Models/
      Menu.php, Table.php, Order.php
    Services/
      OrderService.php                # 주문 처리 로직
      QrGenerator.php                 # QR 코드 생성

resources/views/
  admin/orders/                       # 관리자 페이지
    dashboard.php                     # 주문 현황 대시보드
    menu-categories.php               # 메뉴 카테고리 관리
    menu-items.php                    # 메뉴 아이템 관리
    tables.php                        # 테이블/좌석 관리
    qr-codes.php                      # QR 코드 생성/인쇄
    history.php                       # 주문 내역
    settings.php                      # 주문 시스템 설정
  order/                              # 고객 주문 화면
    index.php                         # 메뉴 목록 (모바일)
    item.php                          # 메뉴 상세 + 옵션
    cart.php                          # 장바구니
    confirm.php                       # 주문 확인
    status.php                        # 주문 상태
    kiosk.php                         # 태블릿 키오스크 모드

resources/lang/{locale}/
    order.php                         # 주문 시스템 번역 파일

database/migrations/
    030_order_system.sql              # 주문 시스템 테이블 생성
```

---

## 6. 설계 결정 사항

### 메뉴 ≠ 서비스 (별도 테이블)

| 비교 | 서비스 (rzx_services) | 메뉴 (rzx_order_items) |
|------|----------------------|----------------------|
| 용도 | 예약 대상 | 즉석 주문 대상 |
| 소요시간 | 필수 (30분, 60분) | 불필요 |
| 스태프 배정 | 필수 | 불필요 |
| 옵션 | 없음 | 필수 (맛, 사이즈, 토핑) |
| 품절 처리 | 불필요 | 필수 (실시간) |
| 이미지 | 1장 | 대형 사진 필수 |

→ 성격이 완전히 다르므로 **별도 테이블**이 올바른 선택.

### 실시간 주문 수신 방식

| 방식 | 난이도 | 실시간성 | 선택 |
|------|--------|---------|------|
| Polling (5초 간격) | 낮음 | 충분 | **1단계 채택** |
| SSE (Server-Sent Events) | 중간 | 좋음 | 2단계 업그레이드 |
| WebSocket | 높음 | 최고 | 불필요 (소규모 매장) |

### 결제 연동

| 단계 | 방식 |
|------|------|
| **1단계** | 주문만 (결제는 카운터에서) |
| **2단계** | 온라인 결제 연동 (PG사) |

→ 처음부터 결제를 넣으면 복잡도가 급증하므로 1단계는 주문 기능에 집중.

### 주문 데이터 스냅샷

주문 아이템에 **메뉴명/가격을 스냅샷으로 저장**한다.
메뉴 가격이 변경되어도 과거 주문 기록에 영향 없음.

---

## 7. 개발 단계

### 1단계: 기반 (관리자)
- [ ] DB 마이그레이션 (테이블 생성)
- [ ] 메뉴 카테고리 CRUD
- [ ] 메뉴 아이템 CRUD (사진, 가격, 옵션, 품절)
- [ ] 테이블/좌석 관리
- [ ] 다국어 적용 (메뉴명, 설명, 카테고리명)

### 2단계: QR 주문 (고객)
- [ ] QR 코드 생성 + 일괄 인쇄
- [ ] 고객 모바일 메뉴 화면 (PWA)
- [ ] 장바구니 + 주문 확정
- [ ] 주문 상태 확인 화면

### 3단계: POS 연동
- [ ] 관리자 주문 현황 대시보드 (실시간 Polling)
- [ ] 주문 상태 변경 (접수 → 조리중 → 완료)
- [ ] 주문 알림음
- [ ] 직원 호출 기능

### 4단계: 태블릿 키오스크
- [ ] 태블릿 전용 UI (큰 사진, 터치 최적화)
- [ ] 키오스크 모드 (풀스크린, 자동 초기화)
- [ ] 추가 주문 플로우

### 5단계: 확장
- [ ] 결제 연동 (PG사)
- [ ] 주문 통계/매출 리포트
- [ ] 인기 메뉴 분석
- [ ] 프린터 연동 (주방 출력)

---

## 8. RezlyX 코어와의 관계

```
RezlyX Core
├── 예약 시스템 (Reservation)     ← 기존
├── POS 시스템 (POS)              ← 기존
├── 회원 시스템 (Members)         ← 공유
├── 설정 시스템 (Settings)        ← 공유
├── 다국어 시스템 (I18n)          ← 공유
└── 주문 시스템 (Order) ★         ← 신규 독립 모듈
    ├── 메뉴 관리
    ├── 테이블 관리
    ├── QR 주문
    ├── 태블릿 주문
    └── 주문 처리
```

- **활성화/비활성화:** 관리자 설정에서 주문 시스템 ON/OFF
- **메뉴 노출:** 주문 시스템 OFF 시 사이드바/라우트에서 완전 숨김
- **POS 연계:** 주문 → POS 화면에 표시 (별도 탭 또는 알림)
- **업종 무관:** 요식업 외에도 카페, 베이커리, 바 등 활용 가능
