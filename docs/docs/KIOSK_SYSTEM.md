# 키오스크 시스템

## 1. 시스템 개요

키오스크는 RezlyX의 고객 셀프 접수 시스템이다. 매장 내 태블릿/터치스크린에서 고객이 직접 언어 선택, 지명/배정 선택, 스태프 선택, 서비스 선택, 접수를 수행한다.

**핵심 특징:**
- 6단계 화면 흐름 (언어 → 지명/배정 → 스태프 → 서비스 → 확인 → 완료)
- PWA 지원 (fullscreen 모드, manifest-kiosk.json)
- 다국어 지원 (13개 언어, 키오스크별 언어 필터 가능)
- 다크/라이트 테마, 커스텀 배경(그라디언트/이미지/동영상)
- 유휴 타이머로 자동 복귀
- POS 자동 배정 설정 연동

---

## 2. 화면 흐름

```
[1] 언어 선택 (index.php)
     │
     ▼
[2] 지명/배정 선택 (choose.php)
     │
     ├── 지명 ──→ [3] 스태프 선택 (staff.php)
     │                   │
     │                   ▼
     │             [4] 서비스 선택 (service.php, staff별 필터)
     │                   │
     │                   ▼
     │             [5] 접수 확인 (confirm.php → confirm-form.php)
     │                   │
     │                   ▼
     │             [6] 접수 완료 (confirm.php → confirm-done.php)
     │
     └── 배정 ──→ [4] 서비스 선택 (service.php, 전체 서비스)
                       │
                       ▼
                 [5] 접수 확인 (자동 배정 로직 실행)
                       │
                       ▼
                 [6] 접수 완료
```

각 화면에서 [뒤로] 버튼으로 이전 단계로 돌아갈 수 있다. 유휴 시간 초과 시 [1] 언어 선택으로 자동 복귀된다.

---

## 3. 라우팅

| URL | 파일 | 설명 |
|-----|------|------|
| `/theadmin/kiosk/run` | `customer/kiosk/index.php` | 언어 선택 |
| `/theadmin/kiosk/run/choose` | `customer/kiosk/choose.php` | 지명/배정 선택 |
| `/theadmin/kiosk/run/staff` | `customer/kiosk/staff.php` | 스태프 선택 |
| `/theadmin/kiosk/run/service` | `customer/kiosk/service.php` | 서비스 선택 |
| `/theadmin/kiosk/run/confirm` | `customer/kiosk/confirm.php` | 접수 확인/완료 |
| `/theadmin/kiosk` | `admin/reservations/kiosk.php` | 관리자 키오스크 관리 |
| `/theadmin/kiosk/settings` | `admin/reservations/kiosk-settings.php` | 키오스크 설정 |

**URL 파라미터:**
- `lang` — 선택된 언어 코드 (ko, en, ja 등)
- `type` — `designation`(지명) / `assignment`(배정)
- `staff` — 선택된 스태프 ID (지명 모드)
- `services` — 선택된 서비스 ID 목록 (콤마 구분)

---

## 4. 자동 배정 로직

배정(assignment) 모드 선택 시 `confirm.php`에서 자동 배정을 시도한다.

### 조건

- POS 설정 `pos_auto_assign`이 `1`이어야 활성화
- `0`이면 스태프 미배정(null)으로 접수 진행

### 배정 알고리즘

1. `rzx_staff_services` 테이블에서 선택된 **모든 서비스**를 수행 가능한 스태프 후보 조회
2. `HAVING COUNT(DISTINCT ss.service_id) = 선택 서비스 수`로 전체 서비스 커버 가능한 스태프만 필터
3. 각 후보별 당일 예약 건수(`status != cancelled`) 조회
4. 예약 건수가 가장 적은 스태프를 자동 배정

### 실패 처리

자동 배정 실패(후보 없음, DB 오류) 시 스태프 미배정(null)으로 접수 진행. POS에서 수동 배정 가능.

---

## 5. PWA 지원

### manifest-kiosk.json

```json
{
  "name": "RezlyX Kiosk",
  "short_name": "Kiosk",
  "start_url": "/kiosk",
  "display": "fullscreen",
  "background_color": "#000000",
  "theme_color": "#000000",
  "orientation": "portrait-primary"
}
```

- `display: fullscreen` — 브라우저 UI 없이 전체 화면
- `orientation: portrait-primary` — 세로 모드 고정
- 아이콘: `icon-192x192.png`, `icon-512x512.png` (maskable)

### 모바일 웹앱 메타 태그

```html
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
```

### 전체화면 진입

더블클릭 시 `document.documentElement.requestFullscreen()` 호출.

### 보안

- 우클릭 방지 (`contextmenu` 이벤트 차단)
- 확대/축소 비활성 (`user-scalable=no, maximum-scale=1.0`)
- 텍스트 선택 비활성 (`select-none` 클래스)

---

## 6. 키오스크 설정

관리자 페이지 `/theadmin/kiosk/settings`에서 관리. DB `rzx_settings` 테이블에 `kiosk_*` 키로 저장.

### 6.1 기본 설정

| 키 | 기본값 | 설명 |
|----|--------|------|
| `kiosk_enabled` | `0` | 키오스크 활성화 |
| `kiosk_theme` | `dark` | 테마 (dark/light) |
| `kiosk_idle_timeout` | `60` | 유휴 시 언어선택 복귀 시간 (초, 10~600) |

### 6.2 배경 설정

| 키 | 기본값 | 설명 |
|----|--------|------|
| `kiosk_bg_type` | `gradient` | 배경 타입 (gradient/image/video) |
| `kiosk_bg_image` | 빈값 | 배경 이미지 URL |
| `kiosk_bg_video` | 빈값 | 배경 동영상 URL |
| `kiosk_bg_overlay` | `50` | 오버레이 투명도 (0~100%) |

**배경 타입별 동작:**
- `gradient` — CSS 애니메이션 그라디언트 (테마별 색상)
- `image` — 전체 화면 이미지 + 오버레이
- `video` — 자동재생 무음 루프 동영상 + 오버레이

### 6.3 커스텀 텍스트

| 키 | 기본값 | 설명 |
|----|--------|------|
| `kiosk_welcome_text` | `Select your language` | 언어 선택 화면 환영 문구 |
| `kiosk_footer_text` | `Powered by {siteName}` | 하단 푸터 문구 (HTML 허용) |
| `kiosk_logo_override` | 빈값 | 키오스크 전용 로고 URL (미설정 시 사이트 로고) |

**다국어 지원:**
- `kiosk.welcome_text`, `kiosk.footer_text` 키로 `rzx_translations` 테이블에서 로케일별 번역 조회
- 다국어 모달(`multilang-modal.php`)로 언어별 번역 입력 가능

### 6.4 언어 설정

| 키 | 기본값 | 설명 |
|----|--------|------|
| `kiosk_languages` | `[]` (전체) | 키오스크에 표시할 언어 코드 JSON 배열 |

빈 배열이면 사이트 지원 언어 전체 표시. 특정 언어만 선택하면 해당 언어만 언어 선택 화면에 노출.

---

## 7. 다국어 지원

### 7.1 화면별 번역

모든 텍스트는 `__('reservations.kiosk_*')` 번역 함수 사용. `resources/lang/*/reservations.php`에 정의.

### 7.2 서비스/카테고리/스태프 다국어

- **서비스명/설명:** `rzx_translations` 테이블에서 `service.{id}.name`, `service.{id}.description` 키로 조회
- **카테고리명:** `service_category.{id}.name` 키로 조회
- **스태프명/소개:** `rzx_staff.name_i18n`, `bio_i18n` JSON 컬럼 (로케일별 값)
- **스태프 직책:** `rzx_staff_positions.name_i18n` JSON 컬럼

### 7.3 언어 선택 동작

1. 언어 버튼 클릭 시 `locale` 쿠키 설정 (1년 유효)
2. `/lang/{code}` AJAX 호출로 서버 세션 언어 변경
3. `choose` 페이지로 이동 (`?lang=code` 파라미터)

### 7.4 언어 선택 화면 국기

```
ko → KR, en → US, ja → JP, zh_CN → CN, zh_TW → TW,
de → DE, es → ES, fr → FR, id → ID, mn → MN,
ru → RU, tr → TR, vi → VN
```

---

## 8. 접수 처리

### 8.1 접수 흐름 (confirm.php POST)

1. 고객 정보 수집: customer_name (선택), customer_phone (선택, 국제전화 컴포넌트)
2. 전화번호로 회원 자동 매칭 (`rzx_users.phone`)
3. 예약 INSERT:
   - `rzx_reservations`: id(36byte hex), reservation_number(`RZX`+타임스탬프+랜덤4자), status=`pending`, source=`designation`/`walk_in`
   - start_time = 현재 시간
   - end_time = 현재 + 총 duration
   - total_amount = 서비스 합계, final_amount = total + designation_fee
4. 서비스 관계 INSERT: `rzx_reservation_services` (reservation_id, service_id, service_name, price, duration, sort_order)
5. 대기번호 계산: 당일 `source IN ('walk_in','designation','kiosk')` 예약 건수

### 8.2 접수 완료 화면 (confirm-done.php)

- 체크 아이콘 바운스 애니메이션
- 대기번호 (대형 숫자)
- 접수 번호 (reservation_number)
- 지명 스태프명 (지명 모드)
- 10초 카운트다운 후 언어 선택으로 자동 복귀
- 화면 아무 곳 터치 시 즉시 복귀

### 8.3 DB 테이블 구조

**rzx_reservations:**
```
id, reservation_number, user_id, staff_id, designation_fee,
customer_name, customer_phone, reservation_date,
start_time, end_time, total_amount, final_amount,
status, source, notes, created_at, updated_at
```

**rzx_reservation_services:**
```
reservation_id, service_id, service_name, price, duration, sort_order
```

---

## 9. 각 화면 상세

### 9.1 언어 선택 (index.php)

- 로고 또는 사이트명 표시
- 환영 문구 (다국어 DB 조회)
- 언어 그리드: 국기 이모지 + 네이티브 언어명
- `kiosk_languages` 설정으로 표시 언어 필터

### 9.2 지명/배정 선택 (choose.php)

- 두 개의 대형 카드 버튼
- **지명 (designation):** 스태프를 직접 선택 → staff.php로 이동
- **배정 (assignment):** 스태프 선택 건너뜀 → service.php로 이동 (`type=assignment`)
- 유휴 타이머 작동 (설정된 초 후 언어 선택으로 복귀)

### 9.3 스태프 선택 (staff.php)

- 활성 스태프 목록 (is_active=1, is_visible=1)
- 카드형: 아바타, 이름(다국어), 직책, 지명비
- 현재 이용중 스태프 표시 (오렌지색 뱃지, 아바타 반투명)
- 가용 스태프 녹색 뱃지
- 선택 시 `service.php?type=designation&staff={id}`로 이동

### 9.4 서비스 선택 (service.php)

- 카테고리 탭 필터 (전체 + 카테고리별)
- 서비스 카드: 이미지, 이름(다국어), 가격, 소요시간
- 복수 선택 가능 (체크 표시 토글)
- 하단 선택 바: 선택 수, 합계 금액, 총 소요시간, [다음] 버튼
- **지명 모드:** `staff_services` 테이블로 해당 스태프가 수행 가능한 서비스만 표시
- **배정 모드:** 전체 활성 서비스 표시

### 9.5 접수 확인 (confirm-form.php)

- 스태프 정보 (지명일 때: 이름, 지명비)
- 서비스 목록 (이름, 소요시간, 가격)
- 합계 (서비스 합계 + 지명비)
- 고객 정보 입력: 이름(선택), 전화번호(선택, 국제전화 컴포넌트)
- [접수] 버튼 (중복 제출 방지)

### 9.6 접수 완료 (confirm-done.php)

- 완료 체크 애니메이션
- 대기번호, 접수 번호, 스태프명
- 10초 자동 복귀 카운트다운

---

## 10. 공통 기능

### 10.1 유휴 타이머 (_scripts.php)

`mousemove`, `touchstart`, `keydown`, `click`, `scroll` 이벤트 감지. 설정된 `kiosk_idle_timeout` 초 동안 입력 없으면 `/theadmin/kiosk/run`으로 자동 복귀.

### 10.2 테마 시스템 (_init.php)

| 테마 | 텍스트 | 서브텍스트 | 버튼 배경 |
|------|--------|-----------|----------|
| dark | text-white | text-zinc-400 | bg-white/10 border-white/20 |
| light | text-zinc-900 | text-zinc-500 | bg-black/5 border-black/10 |

### 10.3 배경 스타일 (_styles.php)

- 그라디언트: CSS `gradientShift` 애니메이션 (15초 주기)
- 이미지/동영상: fixed 위치, object-fit: cover
- 오버레이: rgba 배경색 (테마별 흑/백, 투명도 조절)

---

## 11. 관련 파일

| 파일 | 역할 |
|------|------|
| `customer/kiosk/index.php` | 언어 선택 화면 |
| `customer/kiosk/choose.php` | 지명/배정 선택 화면 |
| `customer/kiosk/staff.php` | 스태프 선택 화면 |
| `customer/kiosk/service.php` | 서비스 선택 화면 |
| `customer/kiosk/confirm.php` | 접수 확인/완료 컨테이너 |
| `customer/kiosk/confirm-form.php` | 접수 확인 폼 |
| `customer/kiosk/confirm-done.php` | 접수 완료 화면 |
| `customer/kiosk/_init.php` | 공통 초기화 (설정, 테마, 색상) |
| `customer/kiosk/_styles.php` | 공통 CSS (배경, 애니메이션) |
| `customer/kiosk/_scripts.php` | 공통 JS (전체화면, 우클릭방지, 유휴타이머) |
| `admin/reservations/kiosk.php` | 관리자 키오스크 관리 페이지 |
| `admin/reservations/kiosk-settings.php` | 키오스크 설정 페이지 |
| `manifest-kiosk.json` | PWA 매니페스트 |
