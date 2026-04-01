# POS 시스템

## 1. 시스템 개요

POS(Point of Sale)는 RezlyX의 당일 현장 관리 화면이다. 관리자가 매장에서 실시간으로 이용중/대기 고객을 카드 형태로 관리하고, 당일 접수 / 서비스 시작 / 결제 / 완료를 처리한다.

**핵심 특징:**
- **플러그인 구조** — `resources/views/admin/pos/` 디렉토리 존재 시 자동 활성화
- 업종별 어댑터 패턴 (CustomerBasedAdapter / SpaceBasedAdapter)
- 1고객 = 1카드, 다중 서비스 그룹핑
- 서비스 이미지 배경 카드, 회원 등급 할인/적립 연동
- 실시간 시계, 자동 새로고침, 진행률 바, 초과시간 표시
- **통합 결제** — 적립금 + 현금 + 카드 복합 결제, 할부 지원

---

## 1.1 플러그인 구조

POS는 RezlyX의 독립 플러그인으로, `pos/` 디렉토리 유무로 활성화/비활성화된다.

**활성화 조건:** `is_dir(BASE_PATH . '/resources/views/admin/pos')`

| 체크 위치 | 동작 |
|-----------|------|
| `index.php` 라우팅 | `pos/` 없으면 POS 라우트 비활성화 (404) |
| `admin-sidebar.php` | `pos/` 없으면 POS 메뉴 숨김 |

**디렉토리 구조:**
```
resources/views/admin/pos/
├── _init.php              ← 예약 공통 _init.php 위임
├── pos.php                ← POS 메인 화면
├── pos-js.php             ← 핵심 JS (결제, 상태변경, 접수)
├── pos-service-js.php     ← 서비스 상세 모달 JS
├── pos-modals.php         ← 모달 HTML 템플릿
├── pos-card-customer.php  ← 고객 중심 카드 뷰
├── pos-card-space.php     ← 공간 중심 카드 뷰
├── pos-space-js.php       ← 공간 모드 JS
└── pos-settings.php       ← POS 설정 페이지
```

**의존성:**
- `reservations/_init.php` — DB, 인증, CSRF, 다국어, 헬퍼 함수 공유
- `reservations/_head.php`, `_foot.php` — 관리자 레이아웃 공유 (BASE_PATH 기반 참조)
- `reservations/_api.php` — POS API 엔드포인트 (URL: `/admin/reservations/*`)
- `rzxlib/Core/Modules/BusinessType/` — 업종별 어댑터

**설치:** `pos/` 디렉토리를 `resources/views/admin/`에 복사하면 즉시 활성화.
**제거:** `pos/` 디렉토리 삭제 시 POS 기능 완전 비활성화 (본체에 영향 없음).

---

## 2. 라우팅

| URL | 파일 | 설명 |
|-----|------|------|
| `/admin/reservations/pos` | `pos/pos.php` | POS 메인 화면 |
| `/admin/pos/settings` | `pos/pos-settings.php` | POS 설정 페이지 |
| `POST /admin/reservations/store` | `reservations/_api.php` | 예약 생성 |
| `POST /admin/reservations/{id}/start-service` | `reservations/_api.php` | 서비스 시작 |
| `POST /admin/reservations/{id}/payment` | `reservations/_api.php` | 결제 처리 |
| `POST /admin/reservations/{id}/complete` | `reservations/_api.php` | 완료 처리 |
| `POST /admin/reservations/{id}/cancel` | `reservations/_api.php` | 취소 처리 |
| `POST /admin/reservations/{id}/confirm` | `reservations/_api.php` | 확정 처리 |
| `POST /admin/reservations/{id}/no-show` | `reservations/_api.php` | 노쇼 처리 |
| `GET /admin/reservations/customer-services` | `reservations/_api.php` | 고객 서비스 내역 조회 |
| `POST /admin/reservations/assign-staff` | `reservations/_api.php` | 스태프 배정 |
| `POST /admin/reservations/add-service` | `reservations/_api.php` | 서비스 추가 (새 예약) |
| `POST /admin/reservations/append-service` | `reservations/_api.php` | 기존 예약에 서비스 추가 |
| `POST /admin/reservations/remove-service` | `reservations/_api.php` | 서비스 삭제 |
| `GET /admin/reservations/user-points` | `reservations/_api.php` | 회원 적립금 조회 |
| `GET /admin/reservations/search-customers` | `reservations/_api.php` | 고객 검색 (자동완성) |
| `GET /admin/reservations/available-staff` | `reservations/_api.php` | 가용 스태프 조회 |
| `POST /admin/reservations/save-memo` | `reservations/_api.php` | 관리자 메모 저장 |
| `GET /admin/reservations/customer-memos` | `reservations/_api.php` | 고객 메모 목록 조회 |

---

## 3. POS 설정 항목

`pos-settings.php`에서 관리하며, DB `rzx_settings` 테이블에 `pos_*` 키로 저장된다.

### 3.1 화면 설정

| 키 | 기본값 | 설명 |
|----|--------|------|
| `pos_card_size` | `medium` | 카드 크기 (small/medium/large) |
| `pos_show_service_image` | `1` | 서비스 이미지 배경 표시 |
| `pos_image_opacity` | `60` | 이미지 오버레이 투명도 (10~100%) |
| `pos_show_price` | `1` | 카드에 금액 표시 |
| `pos_show_phone` | `1` | 카드에 전화번호 표시 |
| `pos_default_tab` | `cards` | 기본 탭 (cards/waiting/reservations) |

**카드 크기 (flex-wrap 고정 크기, 자동 줄바꿈):**

| 크기 | 너비 | 높이 |
|------|------|------|
| small | 260px | 200px |
| medium (기본) | 320px | 240px |
| large | 380px | 280px |

### 3.2 자동화 설정

| 키 | 기본값 | 설명 |
|----|--------|------|
| `pos_auto_refresh` | `0` | 자동 새로고침 활성화 |
| `pos_refresh_interval` | `30` | 새로고침 간격 (초, 최소 10) |
| `pos_sound_notification` | `0` | 새 접수 시 알림음 |

### 3.3 운영 설정

| 키 | 기본값 | 설명 |
|----|--------|------|
| `pos_require_staff` | `1` | 서비스 시작 전 스태프 배정 필수 |
| `pos_auto_assign` | `0` | 스태프 자동 배정 (키오스크 연동) |

---

## 4. 카드 뷰 구조

### 4.1 CustomerBasedAdapter 그룹핑

`CustomerBasedAdapter::groupReservations()`에서 당일 예약을 고객별로 그룹핑한다.

**그룹핑 키:** `고객이름|전화번호`

**그룹 데이터 구조:**
```
customer_name, customer_phone, customer_email
services[]         — 해당 고객의 모든 예약 레코드
total_amount       — 서비스 합계
designation_fee    — 지명비 합계
paid_amount        — 기결제 합계
has_in_service     — 이용중 서비스 존재 여부
has_pending        — 대기중 서비스 존재 여부
group_status       — in_service / waiting
payment_status     — paid / partial / unpaid
discount_amount    — 등급 할인 금액
final_amount       — 최종 금액 (합계 - 할인)
expected_points    — 적립 예정 포인트
```

**정렬:** 이용중(in_service) 카드가 먼저, 대기(waiting) 카드가 뒤에 배치된다.

### 4.2 카드 구성요소 (pos-card-customer.php)

1. **배경:** 서비스 이미지가 있으면 카드 배경으로 사용 (투명도 조절 가능)
2. **진행률 바:** 이용중 카드 하단에 녹색(정상)/빨간색(초과) 진행률 바
3. **상단 영역 (클릭 시 서비스 상세 모달):**
   - 프로필 이미지 (회원), 고객명, 회원 등급 뱃지
   - 상태 뱃지 (대기/이용중/초과), 결제 상태, 스태프명
   - 서비스 목록 (최대 3개 + 추가 N건)
   - 할인율, 적립 예정, 적립금 잔액
   - 잔여 시간 / 예정 시작 시간, 금액
4. **하단 버튼:**
   - 대기 상태: [시작] + [취소] (스태프 미배정 시 [배정 필요] disabled)
   - 이용중 상태: [결제] + [완료]

---

## 5. 서비스 내역 모달

카드 클릭 시 `posServiceModal`이 열린다 (`pos-service-js.php`).

### 5.1 구조 (2단 레이아웃, 3:2)

**히어로 영역:**
- 대표 서비스 이미지를 배경으로 표시 (블러 오버레이)
- 고객 프로필: 이름, 뱃지(회원/비회원, 등급), 전화번호, 방문통계, 적립금 잔액
- 스태프 헤더: 아바타, 배정/지명 뱃지 + 이름 (같은 줄), 지명비

**왼쪽 (3/5):**
- 서비스 목록: 각 서비스별 이름, 상태뱃지, 시간, 소요시간, 스태프, 금액, 삭제 버튼
- 합계: 서비스 합계, 지명비, 기결제, 잔액
- [서비스 추가] 버튼 (토글)
- [스태프 배정/변경] 버튼 (토글)

**오른쪽 (2/5):**
- 영수증/인쇄 버튼 (결제 완료 시 표시, 고객 정보 위)
- 고객 정보 (나이, 성별, 할인율, 적립금, 가입일)
- 고객 요구사항 (notes)
- 관리자 메모 (admin_notes)
- 관리자 메모 입력/저장 (회원만)

### 5.2 서비스 추가

`POS.toggleAddService()` → 전체 서비스 목록에서 체크박스 선택 → `POS.submitAddService()` → `POST /admin/reservations/add-service` API 호출 → 새 예약 생성 후 목록 갱신

### 5.3 서비스 삭제

`POS.removeService()` → `POST /admin/reservations/remove-service` → 해당 서비스 삭제, 남은 서비스 없으면 예약 자체 삭제, 있으면 금액/시간 재계산

---

## 6. 스태프 배정 시스템

### 6.1 수동 배정

- 상세 모달에서 스태프 미배정 시 선택 드롭다운 표시
- 서비스 내역 모달의 [스태프 배정] 버튼으로 라디오 선택 후 배정
- API: `POST /admin/reservations/assign-staff` (reservation_ids[], staff_id)

### 6.2 자동 배정

**설정**: POS 설정 > 운영 설정 > "스태프 자동 배정" 토글 (`pos_auto_assign`)

**알고리즘** (키오스크 `confirm.php`에서 실행):

1. `rzx_settings`에서 `pos_auto_assign` 값 조회
2. 활성화 시, 선택된 서비스 ID 목록으로 후보 스태프 조회:
   ```sql
   SELECT DISTINCT s.id, s.name FROM rzx_staff s
   INNER JOIN rzx_staff_services ss ON s.id = ss.staff_id
   WHERE s.is_active = 1 AND (s.is_visible = 1 OR s.is_visible IS NULL)
     AND ss.service_id IN (선택된 서비스들)
   GROUP BY s.id
   HAVING COUNT(DISTINCT ss.service_id) = 서비스 수
   ```
3. 모든 선택 서비스를 수행할 수 있는 스태프만 후보로 선정
4. 후보 중 당일 예약 건수 최소인 스태프 선택:
   ```sql
   SELECT COUNT(*) FROM rzx_reservations
   WHERE staff_id = ? AND reservation_date = CURDATE() AND status NOT IN ('cancelled')
   ```
5. 가장 적은 스태프에게 `staff_id` 배정

**흐름 분기**:

| 설정 | 후보 존재 | 결과 |
|------|----------|------|
| 자동 배정 ON | 후보 있음 | `staff_id = 최적 스태프` |
| 자동 배정 ON | 후보 없음 | `staff_id = NULL` (수동 배정 폴백) |
| 자동 배정 OFF | - | `staff_id = NULL` (관리자 수동 배정) |

### 6.3 수동 배정 (POS)

**상세 팝업** (대기 리스트 클릭):
- `assignStaffSelect` 드롭다운 → `assignStaff(reservationId)` 호출
- API: `POST /admin/reservations/assign-staff` (`reservation_ids[]`, `staff_id`)
- 성공 시 `location.reload()`

**서비스 내역 모달** ([스태프 배정] 버튼):
- `toggleAssignStaff()` → 스태프 라디오 목록 렌더링
- `submitAssignStaff()` → 같은 API 호출
- 성공 시 `showServices()` 재호출 (캐시 방지 타임스탬프)

### 6.4 배정 필수 (`pos_require_staff`)

- `pos_require_staff = 1`: 미배정 예약의 [진행] 버튼 비활성화 + "스태프 배정 필요" 표시
- `pos_require_staff = 0`: 미배정이어도 서비스 진행 가능

**카드 표시**:

| 상태 | 배지 | 진행 버튼 |
|------|------|----------|
| 스태프 배정됨 | 보라색 스태프 이름 | 활성 |
| 미배정 + 배정 필수 | 빨간 "미배정" | 비활성 (회색 "스태프 배정 필요") |
| 미배정 + 배정 선택 | 빨간 "미배정" | 활성 |

---

## 7. 결제 시스템

### 7.1 통합 결제 모달 (openUnifiedPay)

서비스 상세 모달의 [결제 진행] 버튼으로 열린다. 적립금 + 현금 + 카드를 동시에 사용할 수 있는 복합 결제를 지원한다.

**결제 수단:**

| 수단 | 설명 |
|------|------|
| 적립금 | 회원 적립금 잔액에서 차감 (전액 사용 버튼) |
| 현금 | 금액 수동 입력 |
| 카드 | 금액 수동 입력 + "잔액 전체" 자동 입력 버튼 |
| 할부 | 카드 결제 시 일시불 / 2~12개월 선택 |

**실시간 계산:**
- 합계: 적립금 + 현금 + 카드 합산
- 거스름돈: 합계 > 결제 금액일 때 (현금이 있는 경우만)
- 부족: 합계 < 결제 금액일 때 → 결제 버튼 비활성화

**결제 method 결정:**
- 현금만 → `cash`
- 카드만 → `card`
- 현금 + 카드 → `mixed`
- 적립금만 → `points`

### 7.2 Stripe 복합 결제 처리

카드 결제가 있고 Stripe가 활성화된 경우:
1. Stripe checkout iframe 표시 (카드 금액만)
2. Stripe 결제 **성공 후** 현금/적립금 처리 (API 호출)
3. Stripe 결제 **취소 시** 현금/적립금도 처리되지 않음 (안전)

### 7.3 적립금 처리

- `GET /admin/reservations/user-points` → 회원 적립금 잔액 조회
- 서비스 상세 모달 헤더에 적립금 잔액 상시 표시 (잔액 0이어도 표시)
- 결제 시 적립금 차감: `rzx_users.points_balance` 감소, `rzx_point_transactions` 기록

### 7.4 결제 처리 API

`POST /admin/reservations/{id}/payment`

| 파라미터 | 설명 |
|----------|------|
| `amount` | 현금+카드 결제 금액 |
| `method` | cash / card / mixed / points |
| `cash_amount` | 현금 금액 (mixed 시) |
| `card_amount` | 카드 금액 (mixed 시) |
| `installment` | 할부 개월 수 (1=일시불) |
| `points_used` | 적립금 사용액 |
| `user_id` | 회원 ID (적립금 차감용) |

결제 상태: `paid` (전액) / `partial` (부분) / `unpaid` (미결제)

---

## 8. 우측 패널

3:1 레이아웃의 오른쪽 1/4 영역. 3개 탭으로 구성.

### 8.1 당일 접수 (checkin)

- 대형 [+] 버튼 → 접수 모달 (`posCheckinModal`) 오픈
- 접수 모달: `reservation-form.php` 컴포넌트 재사용 (mode=modal, source=walk_in)
- 오늘 요약: 이용중 / 대기 / 완료 / 전체 건수

### 8.2 대기자 명단 (waiting)

- `source = 'walk_in'`이고 `status = pending|confirmed`인 예약 표시
- 순번, 고객명, 시간, 서비스명, 미배정 뱃지
- 클릭 시 상세 모달

### 8.3 예약자 리스트 (reservations)

- `source != 'walk_in'`이고 `status != cancelled|no_show`인 예약 표시
- 시간순 정렬, 이용중 표시 (녹색 점 애니메이션)
- 클릭 시 상세 모달

---

## 9. API 엔드포인트 상세

### 9.1 예약 생성 (store)

DB 구조: `rzx_reservations` 1건 + `rzx_reservation_services` N건 (junction table)

**처리 흐름:**
1. 서비스 조회 및 금액/시간 합산
2. 회원 자동 매칭 (user_id 우선, 없으면 전화번호로 매칭)
3. 예약 INSERT (id=36바이트 hex, reservation_number=RZX+타임스탬프+랜덤)
4. reservation_services INSERT (service_name, price, duration, sort_order)
5. walk_in 소스면 POS로 리다이렉트

### 9.2 서비스 시작 (start-service)

- status를 `confirmed`로 변경
- start_time을 현재 시간으로, end_time을 현재+총 duration으로 재계산

### 9.3 상태 변경 (confirm/cancel/complete/no-show)

- AJAX 요청 시 JSON 응답, 일반 요청 시 리다이렉트
- cancel: cancel_reason + cancelled_at 기록

### 9.4 고객 서비스 내역 (customer-services)

- 예약 ID 배열로 조회 → 서비스 목록 + 고객 상세(등급/할인/적립금/방문통계) + 관리자 메모 반환
- 스태프 아바타, 서비스 이미지 포함

---

## 10. 관련 파일

### POS 플러그인 (`resources/views/admin/pos/`)

| 파일 | 역할 |
|------|------|
| `_init.php` | POS 초기화 (예약 공통 _init.php 위임) |
| `pos.php` | POS 메인 (데이터 조회, 레이아웃, 어댑터 연동) |
| `pos-js.php` | POS 핵심 JS (탭, 통합결제, 상태변경, 접수) |
| `pos-service-js.php` | 서비스 상세 모달 JS (렌더링, 추가/삭제, 배정, 메모) |
| `pos-modals.php` | 모달 HTML (상세, 접수, 결제, 서비스) |
| `pos-card-customer.php` | 고객 중심 카드 뷰 (고정 크기, flex-wrap) |
| `pos-card-space.php` | 공간 중심 카드 뷰 |
| `pos-space-js.php` | 공간 모드 JS |
| `pos-settings.php` | POS 설정 페이지 |

### 공유 파일 (본체)

| 파일 | 역할 |
|------|------|
| `reservations/_api.php` | 예약/POS 공용 API 핸들러 |
| `reservations/_init.php` | 공통 초기화 (DB, 인증, CSRF, 헬퍼, 통화) |
| `reservations/_head.php`, `_foot.php` | 관리자 레이아웃 |
| `rzxlib/Core/Modules/BusinessType/` | 업종별 어댑터 (CustomerBased, SpaceBased) |
