# VosCMS 페이지 시스템

## 개요

VosCMS의 페이지 시스템은 3가지 유형의 페이지를 지원합니다.
각 유형은 목적과 사용 방식이 다르며, 관리자가 쉽게 관리할 수 있도록 통합된 인터페이스를 제공합니다.

---

## 페이지 유형

### 비유로 이해하기

| 유형 | 비유 | 설명 |
|------|------|------|
| **사용자 페이지** | 자유롭게 꾸미는 방 | 관리자가 직접 만들고 편집하는 페이지 |
| **시스템 페이지** | 건물 안의 특수 목적 방 | 특정 사이트에만 존재하는 고유 페이지 |
| **플러그인** | 어디든 설치 가능한 가구 | 범용 기능으로 누구나 설치/제거 가능 |

---

### 1. 사용자 페이지

관리자가 **사이트 관리 > 페이지 관리**에서 직접 생성하는 페이지.

| 항목 | 내용 |
|------|------|
| 생성 방법 | 관리자 페이지에서 "페이지 추가" |
| 편집 방식 | 위젯 빌더 (드래그 앤 드롭) 또는 문서 편집기 |
| 저장 위치 | DB (`rzx_page_contents` 테이블) |
| URL | `/{page_slug}` (예: `/about`, `/company`) |
| 메뉴 연결 | 메뉴 관리에서 연결 |
| 누가 만드나 | 사이트 관리자 (코딩 불필요) |

**예시**: 회사 소개, 서비스 안내, 이벤트 페이지 등

---

### 2. 시스템 페이지

특정 사이트에만 존재하는 **고유 기능 페이지**. 코드로 구현되며, 사이트별로 다를 수 있음.

| 항목 | 내용 |
|------|------|
| 정의 위치 | `config/system-pages.php` |
| 뷰 파일 | `resources/views/system/{slug}/` |
| DB 등록 | `rzx_page_contents` (`is_system = 1`) |
| URL | `/{slug}` (예: `/service/order`, `/terms`) |
| 관리자 표시 | 페이지 관리 상단 (시스템 페이지 섹션) |
| 메뉴 연결 | 메뉴 관리 > 시스템 페이지 선택 |
| 누가 만드나 | 개발자 |
| 배포 포함 여부 | 선택적 (본사 전용 페이지는 배포 제외) |

**코어 시스템 페이지 (기본 제공)**:
- `home` — 홈페이지 (위젯 빌더)
- `terms` — 이용약관
- `privacy` — 개인정보처리방침
- `data-policy` — 데이터 관리 정책
- `refund-policy` — 취소 환불 규정
- `tokushoho` — 특정상거래법 표기 (일본)
- `funds-settlement` — 자금결제법 표시 (일본)

**본사 전용 시스템 페이지 (배포 미포함)**:
- `service/order` — 서비스 신청 페이지

**고객 맞춤 시스템 페이지 (납품)**:
- 고객 요청에 따라 제작 → 해당 사이트에만 설치
- 고객은 메뉴 관리에서 URL만 연결하면 사용 가능
- 커스터마이징 서비스 수익 모델의 핵심

---

### 3. 플러그인

범용 기능 모듈. 마켓플레이스에서 누구나 설치/제거 가능.

| 항목 | 내용 |
|------|------|
| 정의 위치 | `plugins/{slug}/plugin.json` |
| 구조 | plugin.json + 라우트 + 마이그레이션 + 뷰 + 언어 |
| 관리자 표시 | 플러그인 관리 |
| 설치/삭제 | 마켓플레이스 또는 수동 업로드 |
| 메뉴 자동 등록 | plugin.json의 `menus` 섹션 |
| 시스템 페이지 추가 | plugin.json의 `system_pages` 섹션 |
| 누가 만드나 | 개발자 |
| 배포 | 마켓플레이스에서 범용 배포 |

**플러그인 vs 시스템 페이지 판단 기준**:
- 여러 사이트에서 동일하게 쓸 수 있으면 → **플러그인**
- 특정 사이트 전용이면 → **시스템 페이지**

---

## 시스템 페이지 vs 플러그인 상세 비교

| 구분 | 시스템 페이지 | 플러그인 |
|------|------------|---------|
| **목적** | 특정 사이트 전용 페이지 | 범용 기능 모듈 |
| **설치** | config에 정의 | 마켓플레이스에서 설치 |
| **관리** | 페이지 관리에 표시 | 플러그인 관리에 표시 |
| **배포** | 사이트별 개별 | 누구나 설치 가능 |
| **구조** | 뷰 파일 + config 한 줄 | plugin.json + 라우트 + DB + 뷰 |
| **DB 마이그레이션** | 필요 시 수동 | plugin.json에 정의 → 자동 |
| **메뉴 등록** | config/system-pages.php | plugin.json menus |
| **의존성** | VosCMS 코어 | 코어 + 다른 플러그인 가능 |
| **업데이트** | 수동 | 마켓플레이스 자동 |
| **수익 모델** | 커스터마이징 납품 | 마켓플레이스 판매 |

---

## config/system-pages.php 구조

```php
return [
    [
        'slug'  => 'service/order',           // URL 경로
        'title' => 'site.pages.service_order', // 번역 키 또는 문자열
        'icon'  => 'M16 11V7a4...',           // SVG path (페이지 관리용)
        'emoji' => '🛒',                       // 이모지 (메뉴 관리용)
        'color' => 'indigo',                   // 아이콘 색상
        'type'  => 'system',                   // widget | document | system
        'view'  => 'system/service/order.php', // 뷰 파일 경로
        'edit'  => '/service/order',           // 편집 URL
    ],
];
```

### 필드 설명

| 필드 | 필수 | 설명 |
|------|------|------|
| `slug` | O | URL 경로. `/`로 접속 시 이 slug로 매칭 |
| `title` | O | 페이지 제목. 번역 키(`site.pages.xxx`) 또는 직접 문자열 |
| `icon` | - | SVG path d 속성. 페이지 관리 목록에 표시 |
| `emoji` | - | 이모지. 메뉴 관리 선택 드롭다운에 표시 |
| `color` | - | 아이콘 배경 색상 (blue, green, purple, amber, red, orange, teal, indigo) |
| `type` | O | 페이지 타입. `widget`(위젯빌더), `document`(문서), `system`(코드) |
| `view` | - | type=system일 때 뷰 파일 경로 (`resources/views/` 기준) |
| `edit` | - | 편집 버튼 클릭 시 이동할 URL. `{admin}`은 관리자 경로로 치환 |

---

## 시스템 페이지 자동 연동

`config/system-pages.php`에 페이지를 정의하면 3곳에 **자동으로 반영**됩니다:

```
config/system-pages.php (정의)
        ↓
  load_system_pages() 헬퍼 함수
        ↓
  ┌─ 1. 페이지 관리 — 시스템 페이지 섹션에 자동 표시
  ├─ 2. 메뉴 관리 — 시스템 페이지 선택 드롭다운에 자동 표시
  └─ 3. 프론트 라우팅 — URL 접속 시 자동 뷰 파일 로드
```

**코드 수정 없이 config 한 줄로 시스템 페이지 추가 가능.**

---

## 플러그인에서 시스템 페이지 추가

플러그인도 `plugin.json`의 `system_pages` 섹션으로 시스템 페이지를 등록할 수 있습니다.

```json
{
    "slug": "vos-salon",
    "system_pages": [
        {
            "slug": "staff",
            "title": {"ko": "스태프 소개", "en": "Staff"},
            "emoji": "👥",
            "type": "system",
            "view": "plugins/vos-salon/views/staff.php"
        }
    ]
}
```

플러그인 설치 시 자동으로 페이지 관리 + 메뉴 관리에 등록되고,
플러그인 삭제 시 자동으로 제거됩니다.

---

## 디렉토리 구조

```
VosCMS/
├── config/
│   └── system-pages.php          ← 시스템 페이지 정의
│
├── resources/views/
│   ├── system/                   ← 시스템 페이지 뷰 파일
│   │   └── service/
│   │       ├── order.php
│   │       ├── _addons.php
│   │       └── ...
│   │
│   ├── customer/                 ← 사용자 페이지 뷰
│   └── admin/site/
│       ├── pages.php             ← 페이지 관리 (load_system_pages() 사용)
│       └── menus.php             ← 메뉴 관리 (load_system_pages() 사용)
│
└── plugins/                      ← 플러그인 (system_pages로 페이지 추가 가능)
```

---

## 라우팅 우선순위

```
1. 고정 라우트 (login, register, mypage, admin 등)
2. 게시판 라우트 (/{board_slug})
3. 시스템 페이지 (config/system-pages.php → view 파일)
4. 동적 페이지 (rzx_page_contents 테이블)
5. 플러그인 프론트 라우트 (plugin.json routes)
6. 404 Not Found
```

---

## 새 시스템 페이지 추가 방법

### 1단계: 뷰 파일 생성

```
resources/views/system/{slug}/
├── index.php       ← 메인 페이지
└── _part.php       ← 파트별 분리 (선택)
```

### 2단계: config에 등록

`config/system-pages.php`에 한 줄 추가.

### 3단계: 완료

- 페이지 관리에 자동 표시
- 메뉴 관리에서 선택 가능
- `/{slug}`로 접속 가능

**코드 수정 불필요.**

---

## 고객 맞춤 시스템 페이지 납품

```
1. 고객 요구사항 접수
2. 시스템 페이지 개발 (뷰 파일 생성)
3. 고객 서버에 뷰 파일 업로드
4. config/system-pages.php에 등록
5. 고객이 메뉴 관리에서 연결
6. 완료
```

고객은 코드를 전혀 모르더라도 **메뉴 관리에서 클릭 한 번**으로 사용 가능합니다.

---

## VosCMS config 분리 체계 (전체)

| config 파일 | 용도 | 헬퍼 함수 |
|------------|------|----------|
| `admin-menu.php` | 관리자 사이드바 메뉴 | `load_menu('admin')` |
| `admin-dropdown-menu.php` | 관리자 드롭다운 메뉴 | `load_menu('admin_dropdown')` |
| `mypage-menu.php` | 마이페이지 사이드바 메뉴 | `load_menu('mypage')` |
| `user-dropdown-menu.php` | 프론트 사용자 드롭다운 | `load_menu('user_dropdown')` |
| `system-pages.php` | 시스템 페이지 + 라우팅 | `load_system_pages()` |

모든 config는 **플러그인 확장 가능**, **Core 업데이트 시 보존**.
