# Changelog

RezlyX 프로젝트 변경 이력입니다.

---

## [1.5.2] - 2026-03-17

### Added
- POS 서비스 내역에서 서비스 삭제 기능 (삭제 버튼 + API + DOM 즉시 갱신)
- 13개 언어 서비스 삭제 관련 번역 키 추가

---

## [1.5.0] - 2026-03-17

### Added
- **키오스크 접수 시스템**: 서비스 확인 → 고객 정보 → 예약 생성 → 대기번호 발행
- **스태프 가용 상태 배지**: 키오스크 스태프 선택에서 이용중/가능 실시간 표시
- **국제전화번호 모듈**: 키오스크 접수 폼에 phone-input 컴포넌트 적용
- **예약 샘플 데이터 시드**: `database/seed_reservations.php` (POS/키오스크/온라인 10건)
- 키오스크 공통 파일 분리 (`_styles.php`, `_bg.php`, `_scripts.php`)
- 13개 언어 키오스크 번역 키 11개 추가

### Changed
- 키오스크 서비스 선택: 스태프 스킬 기반 필터링 (지명모드)
- 키오스크 카테고리 탭 중앙 정렬

### Fixed
- 프로덕션 관리자 로그인 불가 (비밀번호 해시 손상 - bash $ 변수 치환 문제)
- **업데이트 시스템 안정화**: 보존 목록 확장, 헬스체크, DB 버전 동기화
- 버전 단일 소스 통합 (`version.json` → index.php, DB 자동 동기화)

---

## [Unreleased]

### Added - 2026-03-11

#### 마이페이지 공통 사이드바 컴포넌트

**공통 컴포넌트** (`resources/views/components/mypage-sidebar.php`)
- 6개 마이페이지(dashboard, profile, settings, password, reservations, messages, withdraw)에서 공유
- 메뉴 데이터 1곳 중앙 관리 (메뉴 추가/삭제 시 1곳만 수정)
- 스킨 오버라이드: `skins/member/{스킨명}/components/mypage-sidebar.php` 자동 감지
- 스킨이 없으면 기본 디자인 자동 적용

**스킨 오버라이드 샘플** (`skins/member/modern/components/mypage-sidebar.php`)
- 그래디언트 active 상태, 둥근 사각형 프로필, 호버 애니메이션

**사용법**: 각 페이지에서 `$sidebarActive` 설정 후 include
```php
$sidebarActive = 'profile';
include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
```

#### 회원 탈퇴 소프트 삭제 + 개인정보 익명화

**Auth::deleteAccount()** 재구현
- 기존 하드 삭제(DELETE) → 소프트 삭제(UPDATE) + 개인정보 익명화
- 이름 → `탈퇴회원_{hash}`, 이메일 → `withdrawn_{hash}@deleted.local`
- 전화번호, 생년월일, 성별, 프로필 사진, 비밀번호 → NULL
- 예약 기록의 고객 정보도 익명화 (예약 기록 자체는 유지)
- `status` → `withdrawn`, `deleted_at` 기록

**DB 변경**
- `rzx_users.deleted_at` TIMESTAMP 컬럼 추가
- `rzx_users.withdraw_reason` VARCHAR(500) 컬럼 추가

**법적 근거**
- 한국 국세기본법: 결제/매출 기록 5년 보관
- 일본 法人税法: 결제/매출 기록 7년 보관
- 개인정보보호법/個人情報保護法: 목적 달성 후 지체 없이 파기 (익명화로 충족)

**탈퇴 안내 문구 상세화** (13개 언어)
- 개인정보 즉시 익명화, 예약 사전 취소, 결제 기록 법적 보관, 계정 복구 불가
- 소셜 로그인 연결 해제, 메시지/알림 삭제, 법정 보관 기간 안내

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `resources/views/components/mypage-sidebar.php` | 신규 | 마이페이지 공통 사이드바 컴포넌트 |
| `skins/member/modern/components/mypage-sidebar.php` | 신규 | modern 스킨 사이드바 오버라이드 |
| `resources/views/customer/mypage/profile.php` | 수정 | 공통 사이드바로 교체 |
| `resources/views/customer/mypage/settings.php` | 수정 | 공통 사이드바로 교체 |
| `resources/views/customer/mypage/withdraw.php` | 수정 | 공통 사이드바로 교체 |
| `resources/views/customer/mypage/password.php` | 수정 | 인라인 사이드바 → 공통 컴포넌트 교체 |
| `resources/views/customer/mypage/reservations.php` | 수정 | 인라인 사이드바 → 공통 컴포넌트 교체 |
| `resources/views/customer/mypage/messages.php` | 수정 | 인라인 사이드바 → 공통 컴포넌트 교체 |
| `rzxlib/Core/Auth/Auth.php` | 수정 | deleteAccount() 소프트 삭제 + 익명화 |
| `resources/lang/*/auth.php` (13개) | 수정 | withdraw 번역 상세화 + retention_notice 추가 |

---

### Added - 2026-03-10

#### 스태프 관리 시스템

**스태프 목록 페이지** (`resources/views/admin/staff.php`)
- 스태프 CRUD API (create/update/delete/toggle)
- 통계 카드 (전체/활성 스태프 수)
- 테이블 리스트 (이름, 연락처, 담당서비스, 활성토글, 수정/삭제)

**스태프 모달 폼** (`resources/views/admin/staff-form.php`)
- 이름(필수), 이메일, 전화, 소개, 담당서비스(체크박스), 활성상태

**스태프 JS** (`resources/views/admin/staff-js.php`)
- openStaffModal, editStaff, saveStaff, deleteStaff, toggleStaff

**스태프 설정** (`resources/views/admin/staff/settings.php`)
- 호칭 선택 (디자이너/스태프/테라피스트/강사/컨설턴트)
- 예약 시 스태프 선택 필수 여부
- 소개/사진 표시 여부
- 자동 배정 (없음/라운드로빈/최소 바쁜)

**사이드바 메뉴** (`resources/views/admin/partials/admin-sidebar.php`)
- 스태프 드롭다운 메뉴 (목록 + 설정 서브메뉴)

**라우팅** (`index.php`)
- `staff/settings` 경로 추가

**13개 언어 번역**: staff 관련 40+ 키 (nav, 목록, 설정, 필드, 에러 등)

#### 서비스 통화/가격 설정 적용

- `service_currency` (KRW/USD/JPY/EUR/CNY) 설정을 전 페이지에 적용
- `service_price_display` (show/hide/contact) 설정을 전 페이지에 적용
- 적용: 관리자 서비스 목록, 고객 서비스 목록/상세, 예약 페이지
- 기본 통화를 JPY(¥)로 변경

#### 서비스 다국어 버튼 수정

- `migrateServiceMultilangKeys`: 잘못된 API 엔드포인트를 `/api/translations?action=rename`으로 수정
- `migrateCategoryMultilangKeys`: 동일 수정

#### 파일 변경 내역 (스태프/서비스)

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `resources/views/admin/staff.php` | 신규 | 스태프 목록 CRUD |
| `resources/views/admin/staff-form.php` | 신규 | 스태프 모달 폼 |
| `resources/views/admin/staff-js.php` | 신규 | 스태프 JS |
| `resources/views/admin/staff/settings.php` | 신규 | 스태프 설정 |
| `resources/views/admin/partials/admin-sidebar.php` | 수정 | 스태프 메뉴 추가 |
| `index.php` | 수정 | staff/settings 라우팅 |
| `resources/views/admin/services.php` | 수정 | 통화/가격 설정 적용 |
| `resources/views/admin/services-js.php` | 수정 | 다국어 API 수정 |
| `resources/views/admin/services/settings-categories.php` | 수정 | 다국어 API 수정 |
| `resources/views/customer/services.php` | 수정 | 통화/가격 설정 적용 |
| `resources/views/customer/services/detail.php` | 수정 | 통화/가격 설정 적용 |
| `resources/views/customer/booking.php` | 수정 | 통화/가격 설정 적용 |
| `resources/lang/*/admin.php` (13개) | 수정 | 스태프 번역 키 추가 |

#### 메뉴 관리 시스템 (Rhymix 호환)

**4단 캐스케이딩 패널 UI** (`resources/views/admin/site/menus.php`)
- Rhymix 메뉴 시스템과 동일한 4단 패널 구조: 트리 | 컨텍스트 | 메뉴타입/편집 | 상세폼
- HTML5 네이티브 Drag & Drop으로 메뉴 순서 변경
- 3구간 드롭 판정: 위(25%), 내부(50%), 아래(25%)
- 트리 접기/펼치기 토글
- 메뉴 검색 기능 (검색 시 트리 자동 펼침)
- Toast 알림 시스템

**메뉴 API** (`resources/views/admin/site/menus-api.php`)
- `add_sitemap` / `rename_sitemap` / `delete_sitemap`: 사이트맵 CRUD
- `add_menu_item` / `update_menu_item` / `rename_menu_item` / `delete_menu_item`: 메뉴 항목 CRUD
- `toggle_home`: 홈 메뉴 지정/해제
- `move_menu_item`: 드래그&드롭 이동
- `copy_menu_item`: 서브트리 재귀 복사 (`copyChildrenRecursive`)
- `reorder_menu_items`: 형제 순서 일괄 업데이트
- `deleteMenuItemRecursive`: 자식 포함 재귀 삭제

**Rhymix 호환 기능**
- 복사 / 잘라내기 / 붙여넣기 (JS 클립보드 기반)
- 사이트맵 편집 (패널3 UI)
- 접근 권한 설정 (`group_srls`)
- 새 창 열기 (`open_window`)
- 하위 메뉴 확장 기본값 (`expand`)
- 홈 메뉴 삭제 방지

**드래그&드롭 분리** (`resources/views/admin/site/menus-dnd.php`)
- dragstart/dragend/dragover/dragleave/drop 이벤트
- 자기 자식으로 드롭 방지 (`isDesc`)
- 형제 순서 재계산 → `reorder_menu_items` API 호출

#### 관리자 TopBar 공유 컴포넌트 적용

- `design.php`, `pages.php`: 인라인 TopBar → 공유 `admin-topbar.php` 컴포넌트로 교체
- 13개 언어 선택기 자동 포함

#### .htaccess 라우팅 수정

- `admin/` 물리 디렉토리 존재 시 rewrite 우회 문제 해결
- `RewriteRule ^admin(/.*)?$ index.php [L,QSA]` 추가

#### 13개 언어 번역 키 추가

메뉴 관리 관련 14개 키를 모든 언어 파일에 추가:
`cut`, `copied`, `cannot_delete_home`, `sitemap_name`, `design_bulk_desc`, `layout`, `select_layout`, `default_layout`, `apply`, `open_window`, `expand_default`, `access_permission`, `help_access`, `access_placeholder`

#### DB 스키마

**rzx_sitemaps**: `id`, `title`, `slug`, `sort_order`, `created_at`, `updated_at`

**rzx_menu_items**: `id`, `sitemap_id`, `parent_id`, `title`, `url`, `target`, `icon`, `css_class`, `description`, `menu_type`, `sort_order`, `is_home`, `is_shortcut`, `open_window`, `expand`, `group_srls`, `is_active`, `created_at`, `updated_at`

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `resources/views/admin/site/menus.php` | 수정 | 4단 패널 UI, DnD, 트리 토글 |
| `resources/views/admin/site/menus-js.php` | 수정 | 클립보드, 사이트맵 편집, 폼 확장 |
| `resources/views/admin/site/menus-dnd.php` | 신규 | 드래그&드롭 전용 JS |
| `resources/views/admin/site/menus-api.php` | 수정 | copy, reorder, expand/group_srls 지원 |
| `resources/views/admin/site/design.php` | 수정 | 공유 TopBar 적용 |
| `resources/views/admin/site/pages.php` | 수정 | 공유 TopBar 적용 |
| `.htaccess` | 수정 | admin 디렉토리 rewrite 강제 |
| `resources/lang/*/admin.php` (13개) | 수정 | 메뉴 관리 번역 키 14개 추가 |

---

### Added - 2026-03-09

#### 모듈 기반 아키텍처 도입

**LanguageModule** (`rzxlib/Core/Modules/LanguageModule.php`)
- admin-topbar.php와 동일한 언어 데이터 구조를 중앙 모듈로 분리
- `getData($siteSettings, $currentLocale)`: DB에서 활성 언어 목록 반환
- 기본 13개 언어 + 커스텀 언어 병합
- 반환값: `languages`, `allLanguages`, `supportedCodes`, `currentLocale`, `defaultLocale`, `currentLangInfo`

**LogoModule** (`rzxlib/Core/Modules/LogoModule.php`)
- 사이트 로고/이름 설정을 중앙 모듈로 분리
- `getData($siteSettings, $appName)`: 로고 타입, 이미지, 사이트명 반환

**SocialLoginModule** (`rzxlib/Core/Modules/SocialLoginModule.php`)
- 소셜 로그인 설정을 중앙 모듈로 분리
- `getData($siteSettings)`: 활성화된 소셜 제공자 목록 반환

**공용 언어 선택기 컴포넌트** (`resources/views/components/language-selector.php`)
- admin-topbar.php와 동일한 UI의 언어 선택 드롭다운
- LanguageModule을 사용하여 DB 활성 언어 표시
- `include BASE_PATH . '/resources/views/components/language-selector.php';` 한 줄로 사용
- HTML + JS 포함, 어느 페이지든 include 가능

**MemberSkinLoader 모듈 통합** (`rzxlib/Core/Skin/MemberSkinLoader.php`)
- `setSiteSettings()`: DB 설정 주입
- `collectModuleData()`: Language/Logo/SocialLogin 모듈 데이터 자동 수집
- `mergeModuleData()`: 모듈 데이터를 스킨 템플릿에 자동 주입
- `$siteSettings`를 스킨 템플릿에 전달 (공용 컴포넌트 지원)
- 로케일 감지에서 하드코딩 3개 언어 제한 제거

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `rzxlib/Core/Modules/LanguageModule.php` | 신규 | 언어 데이터 중앙 모듈 |
| `rzxlib/Core/Modules/LogoModule.php` | 신규 | 로고 데이터 중앙 모듈 |
| `rzxlib/Core/Modules/SocialLoginModule.php` | 신규 | 소셜 로그인 중앙 모듈 |
| `resources/views/components/language-selector.php` | 신규 | 공용 언어 선택기 |
| `rzxlib/Core/Skin/MemberSkinLoader.php` | 수정 | 모듈 통합, siteSettings 전달 |
| `skins/default/components/header.php` | 수정 | 공용 언어 선택기 사용 |
| `skins/member/modern/components/header.php` | 수정 | 공용 언어 선택기 사용 |
| `skins/member/modern/login.php` | 수정 | 헤더 경로 modern 우선 |
| `skins/member/modern/register.php` | 수정 | 헤더 경로 modern 우선 |
| `skins/member/modern/password_reset.php` | 수정 | 헤더 경로 modern 우선 |
| `skins/member/modern/mypage.php` | 수정 | 헤더 경로 modern 우선 |
| `resources/views/customer/login.php` | 수정 | setSiteSettings() 호출 추가 |
| `resources/views/customer/register.php` | 수정 | setSiteSettings() 호출 추가 |
| `resources/views/customer/forgot-password.php` | 수정 | setSiteSettings() 호출 추가 |
| `resources/views/customer/reset-password.php` | 수정 | setSiteSettings() 호출 추가 |

---

### Added - 2026-03-07

#### 다국어 시스템 개선

**Translator 클래스 업데이트** (`rzxlib/Core/I18n/Translator.php`)
- DB에서 언어 설정 로드 (`loadLanguageSettings()`)
  - `supported_languages`: 지원 언어 목록
  - `custom_languages`: 커스텀 언어 정보
  - `default_language`: 기본 언어
  - `language_auto_detect`: 자동 감지 활성화 여부
- 새 메서드 추가:
  - `getAllLanguages()`: 전체 언어 정보 (기본 + 커스텀)
  - `isAutoDetect()`: 자동 감지 활성화 여부
  - `getFallbackLocale()`: 폴백 로케일
  - `hasLanguageFiles($locale)`: 번역 파일 존재 여부
  - `getLanguageFiles($locale)`: 언어별 번역 파일 목록
  - `getTranslationGroups()`: 전체 번역 그룹 목록
  - `reload()`: 설정 다시 로드
- `detectLocale()` 개선:
  - `autoDetect` 설정 반영
  - 지역 코드 포함 언어 지원 (예: `zh-CN` → `zh_CN`)

**관리자 언어 설정 페이지** (`/admin/settings/language`)
- 언어 자동 선택 활성화/비활성화
- 지원 언어 체크박스 선택
- 기본 언어 선택
- 커스텀 언어 추가 (언어 코드 + 네이티브 이름)
- 커스텀 언어 삭제 (기본 제공 언어는 삭제 불가)

**관리자 번역 관리 페이지** (`/admin/settings/translations`)
- 지원 언어별 번역 파일 상태 표시
- 번역 파일 생성 (원본 언어 선택하여 복사)
- 번역 편집 기능 (키-값 편집, 원본 비교)
- 중첩 배열 키 지원

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `rzxlib/Core/I18n/Translator.php` | 수정 | DB 기반 언어 설정 로드 |
| `resources/views/admin/settings/language.php` | 수정 | 언어 설정 UI 개선 |
| `resources/views/admin/settings/translations.php` | 신규 | 번역 관리 페이지 |
| `resources/views/admin/settings/_settings_nav.php` | 수정 | 번역 관리 탭 추가 |
| `routes/web.php` | 수정 | 번역 관리 라우트 추가 |
| `rzxlib/Http/Controllers/Admin/SettingsController.php` | 수정 | 번역 관리 메서드 추가 |
| `docs/I18N.md` | 수정 | 새 기능 문서화 |

---

## 관리자 설정 페이지 구조

```
/admin/settings/
├── general        # 관리자 접속 경로
├── site           # 사이트 기본 설정
├── mail           # 메일 설정
├── language       # 언어 설정 (NEW: 커스텀 언어 추가)
├── translations   # 번역 관리 (NEW)
├── seo            # SEO 설정
├── pwa            # PWA 설정
└── system/        # 시스템 설정
    ├── info       # 정보관리
    ├── cache      # 캐시관리
    ├── mode       # 모드관리
    ├── logs       # 로그관리
    └── updates    # 업데이트 관리
```

---

## DB 설정 키 (rzx_settings)

### 언어 관련

| 키 | 타입 | 설명 |
|----|------|------|
| `supported_languages` | JSON | 지원 언어 코드 배열 |
| `custom_languages` | JSON | 커스텀 언어 정보 객체 |
| `default_language` | string | 기본 언어 코드 |
| `language_auto_detect` | string | 자동 감지 (`1`/`0`) |

### 예시 데이터

```sql
-- 지원 언어
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('supported_languages', '["ko","en","ja"]');

-- 커스텀 언어
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('custom_languages', '{"th":{"name":"ไทย","native":"ไทย","builtin":false}}');

-- 기본 언어
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('default_language', 'ko');

-- 자동 감지
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('language_auto_detect', '1');
```

---

## 버전 정보

- **문서 버전**: 1.2.0
- **최종 수정일**: 2026-03-11
