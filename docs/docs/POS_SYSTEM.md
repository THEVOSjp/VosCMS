# POS 시스템

## 1. 시스템 개요

POS(Point of Sale)는 RezlyX의 당일 현장 관리 화면이다. 관리자가 매장에서 실시간으로 이용중/대기 고객을 카드 형태로 관리하고, 당일 접수 / 서비스 시작 / 결제 / 완료를 처리한다.

**핵심 특징:**
- 업종별 어댑터 패턴 (CustomerBasedAdapter / SpaceBasedAdapter)
- 1고객 = 1카드, 다중 서비스 그룹핑
- 서비스 이미지 배경 카드, 회원 등급 할인/적립 연동
- 실시간 시계, 자동 새로고침, 진행률 바, 초과시간 표시

---

## 2. 라우팅

| URL | 파일 | 설명 |
|-----|------|------|
| `/admin/reservations/pos` | `pos.php` | POS 메인 화면 |
| `/admin/pos/settings` | `pos-settings.php` | POS 설정 페이지 |
| `POST /admin/reservations/store` | `_api.php` | 예약 생성 |
| `POST /admin/reservations/{id}/start-service` | `_api.php` | 서비스 시작 |
| `POST /admin/reservations/{id}/payment` | `_api.php` | 결제 처리 |
| `POST /admin/reservations/{id}/complete` | `_api.php` | 완료 처리 |
| `POST /admin/reservations/{id}/cancel` | `_api.php` | 취소 처리 |
| `POST /admin/reservations/{id}/confirm` | `_api.php` | 확정 처리 |
| `POST /admin/reservations/{id}/no-show` | `_api.php` | 노쇼 처리 |
| `GET /admin/reservations/customer-services` | `_api.php` | 고객 서비스 내역 조회 |
| `POST /admin/reservations/assign-staff` | `_api.php` | 스태프 배정 |
| `POST /admin/reservations/add-service` | `_api.php` | 서비스 추가 (새 예약) |
| `POST /admin/reservations/append-service` | `_api.php` | 기존 예약에 서비스 추가 |
| `POST /admin/reservations/remove-service` | `_api.php` | 서비스 삭제 |
| `GET /admin/reservations/user-points` | `_api.php` | 회원 적립금 조회 |
| `GET /admin/reservations/search-customers` | `_api.php` | 고객 검색 (자동완성) |
| `GET /admin/reservations/available-staff` | `_api.php` | 가용 스태프 조회 |
| `POST /admin/reservations/save-memo` | `_api.php` | 관리자 메모 저장 |
| `GET /admin/reservations/customer-memos` | `_api.php` | 고객 메모 목록 조회 |

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

**카드 크기별 그리드:**
- small: `grid-cols-2 md:3 lg:4`
- medium: `grid-cols-1 md:2 lg:3`
- large: `grid-cols-1 md:2`

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
- 고객 프로필: 이름, 뱃지(회원/비회원, 등급), 전화번호, 방문통계
- 스태프 헤더: 아바타, 이름, 지명/배정 뱃지, 지명비

**왼쪽 (3/5):**
- 서비스 목록: 각 서비스별 이름, 상태뱃지, 시간, 소요시간, 스태프, 금액, 삭제 버튼
- 합계: 서비스 합계, 지명비, 기결제, 잔액
- [서비스 추가] 버튼 (토글)
- [스태프 배정/변경] 버튼 (토글)

**오른쪽 (2/5):**
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

### 7.1 결제 모달 (posPaymentModal)

그룹 단위(`openGroupPayment`) 또는 개별 건(`openPayment`)으로 열린다.

**표시 내용:**
- 서비스 상세 내역 (비동기 로드)
- 금액 내역: 서비스 합계, 지명비, 등급 할인, 기결제
- 적립금 사용 (회원만, 잔액 표시, 전액 사용 버튼)
- 결제 잔액
- 결제 금액 입력
- 결제 방법 선택 (카드/현금/계좌이체)

### 7.2 적립금 처리

- `GET /admin/reservations/user-points` → 회원 적립금 잔액 조회
- 적립금 입력 시 `POS.recalcPayment()`로 잔액 실시간 재계산
- 결제 시 적립금 차감: `rzx_users.points_balance` 감소, `rzx_point_transactions` 기록

### 7.3 결제 처리 API

`POST /admin/reservations/{id}/payment`
- 파라미터: amount, method, points_used, user_id
- 결제 상태: paid (전액) / partial (부분) / unpaid
- 적립금 사용 시 `final_amount` 재계산

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

| 파일 | 역할 |
|------|------|
| `pos.php` | POS 메인 (데이터 조회, 레이아웃, 어댑터 연동) |
| `pos-js.php` | POS 핵심 JS (탭, 모달, 상태변경, 결제, 접수) |
| `pos-service-js.php` | 서비스 상세 모달 JS (렌더링, 추가/삭제, 배정, 메모) |
| `pos-modals.php` | 모달 HTML (상세, 접수, 결제, 서비스) |
| `pos-card-customer.php` | 고객 중심 카드 뷰 |
| `pos-settings.php` | POS 설정 페이지 |
| `_api.php` | 예약 API 핸들러 |
| `_init.php` | 공통 초기화 (CSRF, 헬퍼, 통화) |
| `CustomerBasedAdapter.php` | 고객 중심 어댑터 (그룹핑, 카드 데이터, 상태 흐름) |
