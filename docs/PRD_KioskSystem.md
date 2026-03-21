# RezlyX Kiosk System (키오스크 시스템)

> 독립 모듈로 개발 — RezlyX 코어와 분리된 셀프서비스 키오스크

---

## 1. 개요

### 배경
매장에 비치된 태블릿/터치스크린에서 고객이 직접 체크인, 접수, 대기 등록을 하는 셀프서비스 키오스크. POS가 없어도 키오스크 단독으로 운용 가능하며, POS와 함께 사용 시 연동된다.

### 명칭
- **모듈명:** Kiosk System (키오스크 시스템)
- **URL 접두사:** `/kiosk/`
- **DB 접두사:** `rzx_kiosk_`
- **관리자 메뉴:** 키오스크 관리 (`/admin/kiosk/`)

### 기존 코드 현황
현재 `resources/views/customer/kiosk/`에 프로토타입 수준의 코드가 존재. 이를 독립 모듈로 재구성하여 코어에서 분리한다.

### 설계 원칙
- RezlyX 코어와 **완전 분리된 모듈**
- 공유: DB 연결, 인증, 다국어, 서비스/스태프 데이터
- 별도 구매/납품 가능한 독립 상품
- 키오스크 OFF 시 라우트/메뉴에서 완전 숨김

---

## 2. 적용 업종별 활용

| 업종 | 키오스크 용도 |
|------|-------------|
| 미용실/네일샵 | 셀프 체크인 (예약 확인, 워크인 접수) |
| 병원/의원 | 접수 + 대기번호 발급 |
| 관공서/은행 | 번호표 발급 + 대기 현황 |
| 카페/음식점 | 카운터 주문 (Order System과 연동 가능) |
| 숙박 | 셀프 체크인/체크아웃 |
| 피트니스 | 출입 체크인 + 수업 예약 |

---

## 3. 기능 목록

### 3-1. 고객 화면 (키오스크 터치스크린)

| 기능 | 설명 |
|------|------|
| **예약 체크인** | 예약번호/전화번호/QR로 예약 확인 후 체크인 |
| **워크인 접수** | 예약 없이 현장 방문 → 서비스/스태프 선택 → 대기 등록 |
| **대기번호 발급** | 접수 완료 시 대기번호 표시 (프린터 연동 시 출력) |
| **대기 현황** | 현재 대기 인원, 예상 대기 시간 |
| **서비스 안내** | 서비스 목록, 가격, 소요시간 표시 |
| **다국어 전환** | 터치 한번으로 언어 변경 |

### 3-2. 관리자 (Admin)

| 기능 | 설명 |
|------|------|
| **키오스크 디바이스 관리** | 기기 등록, 이름, 위치 |
| **화면 설정** | 배경 이미지, 로고, 환영 메시지, 컬러 테마 |
| **플로우 설정** | 체크인/워크인/대기 중 사용할 단계 선택 |
| **서비스 노출 설정** | 키오스크에 표시할 서비스/카테고리 선택 |
| **운영시간 설정** | 키오스크 활성 시간대 |
| **대기 현황 모니터** | 관리자 실시간 대기 목록 (호출/완료 처리) |

### 3-3. 대기 현황 디스플레이 (선택)

매장 TV/모니터에 표시하는 대기 현황 화면:
```
현재 대기: 5명 | 예상 대기: 약 15분

  #1023  김** 님  ← 서비스 중
  #1024  이** 님  ← 준비 중
  #1025  박** 님
  #1026  최** 님
```
- URL: `/kiosk/display`
- 자동 새로고침 (Polling)
- 호출 시 알림음 + 번호 강조

---

## 4. 화면 플로우

### 예약 체크인
```
[환영 화면] → [체크인 방법 선택]
              ├→ 예약번호 입력 → 예약 확인 → 체크인 완료
              ├→ 전화번호 입력 → 예약 목록 → 선택 → 체크인 완료
              └→ QR 코드 스캔 → 예약 확인 → 체크인 완료
```

### 워크인 접수 (예약 없이 방문)
```
[환영 화면] → [워크인 접수]
            → 서비스 선택 → 스태프 선택 (선택사항)
            → 인원/연락처 입력
            → 접수 완료 + 대기번호 발급
```

### 자동 복귀
```
체크인/접수 완료 → 5초 후 환영 화면 자동 복귀
무조작 60초 → 환영 화면 자동 복귀
```

---

## 5. 데이터베이스 설계

```sql
-- 키오스크 디바이스
CREATE TABLE rzx_kiosk_devices (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,           -- "로비 키오스크", "2층 입구"
    location VARCHAR(200) DEFAULT NULL,
    device_token VARCHAR(64) NOT NULL,    -- 기기 인증 토큰
    config_json JSON DEFAULT NULL,        -- 화면 설정 (배경, 로고, 테마)
    is_active TINYINT(1) DEFAULT 1,
    last_heartbeat DATETIME DEFAULT NULL, -- 마지막 연결 시각
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_token (device_token)
);

-- 대기열
CREATE TABLE rzx_kiosk_queue (
    id CHAR(36) PRIMARY KEY,
    queue_number INT NOT NULL,            -- 대기번호 (당일 순번)
    type ENUM('checkin','walkin') NOT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    customer_phone VARCHAR(30) DEFAULT NULL,
    party_size INT DEFAULT 1,
    service_id CHAR(36) DEFAULT NULL,
    staff_id CHAR(36) DEFAULT NULL,
    reservation_id CHAR(36) DEFAULT NULL, -- 예약 체크인인 경우
    device_id CHAR(36) DEFAULT NULL,      -- 접수한 키오스크
    status ENUM('waiting','called','serving','completed','cancelled','no_show') DEFAULT 'waiting',
    called_at DATETIME DEFAULT NULL,
    served_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_date (status, created_at),
    INDEX idx_date (created_at)
);

-- 키오스크 설정
-- rzx_settings 테이블에 JSON으로 저장:
--   kiosk_enabled: Y/N
--   kiosk_welcome_message: 환영 메시지
--   kiosk_bg_image: 배경 이미지
--   kiosk_flow: ["checkin","walkin","queue_display"]
--   kiosk_auto_reset_seconds: 60
--   kiosk_operating_hours: {"start":"09:00","end":"21:00"}
```

---

## 6. 파일 구조

```
rzxlib/
  Kiosk/                                # 키오스크 모듈
    Controllers/
      KioskAdminController.php          # 관리자 API
      KioskScreenController.php         # 키오스크 화면 API
    Models/
      Device.php, Queue.php
    Services/
      QueueService.php                  # 대기열 관리 로직

resources/views/
  admin/kiosk/                          # 관리자 페이지
    settings.php                        # 키오스크 설정
    devices.php                         # 디바이스 관리
    queue.php                           # 대기 현황 관리
  kiosk/                                # 키오스크 화면 (터치스크린)
    welcome.php                         # 환영/메인 화면
    checkin.php                         # 예약 체크인
    walkin.php                          # 워크인 접수
    service-select.php                  # 서비스 선택
    staff-select.php                    # 스태프 선택
    confirm.php                         # 접수 확인 + 대기번호
    display.php                         # 대기 현황 디스플레이 (TV용)

resources/lang/{locale}/
    kiosk.php                           # 키오스크 번역 파일

database/migrations/
    031_kiosk_system.sql                # 키오스크 테이블 생성
```

---

## 7. RezlyX 모듈 체계

```
RezlyX Core (기본 패키지)
├── 예약 관리 (Reservation)
├── 회원 관리 (Members)
├── 사이트 관리 (Site/Pages/Board)
├── 설정/다국어/인증
└── 기본 대시보드

별도 모듈 (개별 구매/납품)
├── POS System         — 매장 판매·결제 관리
├── Kiosk System ★     — 셀프 체크인·대기 관리
└── Order System ★     — 테이블 QR·태블릿 주문
```

### 모듈 간 관계

```
            ┌─────────────────┐
            │   RezlyX Core   │
            │  (예약·회원·설정) │
            └──┬─────┬─────┬──┘
               │     │     │
          ┌────┘     │     └────┐
          ▼          ▼          ▼
    ┌──────────┐ ┌────────┐ ┌──────────┐
    │   POS    │ │ Kiosk  │ │  Order   │
    │  매장POS  │ │체크인   │ │ 테이블주문│
    └────┬─────┘ └───┬────┘ └────┬─────┘
         │           │           │
         └─────┬─────┘           │
               ▼                 │
        대기열 연동              │
        (Kiosk 접수 →            │
         POS 호출)               │
                                 │
         POS ←───────────────────┘
         (Order 주문 → POS 수신)
```

| 조합 | 사용 예시 |
|------|----------|
| Core만 | 온라인 예약만 받는 소규모 업체 |
| Core + POS | 예약 + 매장 결제 |
| Core + Kiosk | 예약 + 셀프 체크인 (병원, 관공서) |
| Core + POS + Kiosk | 예약 + 체크인 + 결제 (미용실, 네일샵) |
| Core + POS + Order | 예약 + 매장 결제 + 테이블 주문 (레스토랑) |
| Core + POS + Kiosk + Order | 풀 패키지 |

### 매장 하드웨어 구성

```
고객이 조작 (터치 필요)     → 태블릿
  ├── 키오스크 체크인/접수
  ├── 테이블 오더
  └── 카운터 주문 키오스크

보여주기만 (터치 불필요)    → 일반 TV/모니터
  └── 대기현황 디스플레이
      권장: 스마트 모니터 (삼성 M시리즈, LG 마이뷰 등)
        → WiFi + 내장 브라우저만 있는 제품, TV 튜너 없어 수신료 불필요
        → 27~43인치 15~30만원대, 별도 장비 없이 자체 브라우저로 URL 접속
      대안: 스마트TV 브라우저, Chromecast, Fire TV Stick, HDMI 연결
      → /kiosk/display URL 접속 + 전체화면 (별도 단말기 불필요)

직원이 조작              → PC 또는 태블릿
  └── POS
```

모든 화면이 웹 기반(PWA)이므로 브라우저 접속만 가능하면 하드웨어 제약 없음.

### 모듈 활성화 방식

```php
// rzx_settings 테이블
'modules_enabled' => '["pos","kiosk","order"]'   // JSON 배열

// 코드에서 확인
if (module_enabled('kiosk')) {
    // 사이드바에 키오스크 메뉴 표시
    // 라우트 등록
}
```

---

## 8. 개발 단계

### 1단계: 코어 분리
- [ ] 기존 kiosk 코드를 `rzxlib/Kiosk/`로 이동
- [ ] 기존 라우트를 모듈 활성화 조건 체크로 래핑
- [ ] DB 마이그레이션 (kiosk 전용 테이블)
- [ ] 모듈 ON/OFF 설정 UI

### 2단계: 대기열 시스템
- [ ] 대기번호 발급 로직
- [ ] 대기 현황 관리 (관리자)
- [ ] 대기 현황 디스플레이 (TV용)
- [ ] 호출/완료/노쇼 상태 관리

### 3단계: 화면 개선
- [ ] 터치 최적화 UI (큰 버튼, 직관적 플로우)
- [ ] 화면 커스터마이징 (배경, 로고, 테마)
- [ ] 다국어 즉시 전환 버튼
- [ ] 키오스크 모드 (풀스크린, 자동 복귀)

### 4단계: 연동
- [ ] POS 연동 (체크인 → POS 대기 목록)
- [ ] 프린터 연동 (대기번호 출력)
- [ ] 알림음/진동 (호출 시)

### 5단계: 디바이스 관리
- [ ] 원격 디바이스 모니터링 (온라인/오프라인)
- [ ] 원격 설정 변경
- [ ] 디바이스별 사용 통계
