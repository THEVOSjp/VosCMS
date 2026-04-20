# VosCMS 페이지 관리 시스템 가이드

> **버전**: 2.2.x · **최종 업데이트**: 2026-04-20
> **관련 문서**: [PAGE_SYSTEM.md](PAGE_SYSTEM.md) (개념), [LAYOUT_SYSTEM.md](LAYOUT_SYSTEM.md) (레이아웃), [I18N.md](I18N.md) (다국어)

이 가이드는 VosCMS 페이지 관리 시스템의 **현재 구현된 기능**을 관리자·개발자 관점에서 정리한 실무 문서다. 페이지 타입별 동작 방식, 관리자 UI 구조, DB 스키마, 라우팅·렌더링 흐름, 권한 체계를 다룬다.

---

## 1. 시스템 개요

VosCMS 페이지는 **4가지 타입**으로 분류된다:

| 타입 | 저장 위치 | 렌더링 방식 | 용도 |
|---|---|---|---|
| `document` | `rzx_page_contents.content` (HTML) | `type-document.php` 로 HTML 직접 출력 | 약관, 정책, 회사 소개 등 정적 페이지 |
| `widget` | `rzx_page_widgets` + `rzx_widgets` | `type-widget.php` 에서 위젯 순차 렌더 | 홈, 서비스 소개 등 블록 조합형 |
| `external` | `rzx_page_contents.content` (URL/파일경로) | `type-external.php` 에서 iframe/include | 외부 서비스 연결, 레거시 PHP 포함 |
| `system` | `config/system-pages.php` + 코드 | 라우터에서 직접 `view` 파일 include | 서비스 신청, 결제 완료 등 동적 기능 |

**시스템 페이지 vs 커스텀 페이지**: `is_system=1` 이면 시스템 페이지 — 관리자 UI 에서 삭제 불가 (수정만 가능). `config/system-pages.php` 또는 플러그인 `plugin.json` 에서 정의된다.

---

## 2. 관리자 UI 구조

### 2.1 페이지 목록 (`/admin/site/pages`)

파일: `resources/views/admin/site/site/pages.php`

**구성**:
- **시스템 페이지 섹션** — `load_system_pages()` 헬퍼로 `config/system-pages.php` + 플러그인 `system_pages` 를 로드해 표시. 아이콘/이모지/색상/타입 배지로 시각 구분.
- **커스텀 페이지 섹션** — DB 에서 `is_system=0` 인 `rzx_page_contents` 항목을 `page_slug` UNIQUE 로 그룹핑 (locale 당 1행이므로 대표 locale 만 노출).
- **각 페이지 행**: 설정(⚙) / 편집(✏) / 미리보기(👁) 버튼 + 다국어 상태 표시.

### 2.2 페이지 설정 화면 (`/admin/site/pages/settings?slug=...`)

**설계 원칙**: **설정 = 모양 + 기능** · **편집 = 데이터**
- 설정 페이지에는 스킨/레이아웃/권한/동작 제어 등 **모양과 기능**
- 데이터 관리(업로드·콘텐츠 편집·번역)는 **편집 페이지(`/{slug}/edit`)** 로 분리

**코어 탭 순서** (v2.3.0 재배치):

| 탭 | 내용 |
|---|---|
| **기본정보** (basic) | URL slug · 브라우저 제목 · 레이아웃 선택 카드 · 스킨 선택 카드 · SEO (title/description/keywords) · OG 이미지 · Robots · 검색엔진 색인 |
| **권한 관리** (permissions) | 접근권한 (모든 방문자/회원/관리자) · 페이지 수정 권한 · 관리 권한 · 모듈 관리자 지정 |
| **추가 설정** (addition) | 에디터 권한 (HTML/파일/컴포넌트) |
| **스킨** (skin) | 선택된 스킨의 `skin.json` 변수만 렌더링 (content_width · title_bg · custom_css 등) |
| **[시스템 페이지 전용 탭]** | `config/system-pages.php` 의 `settings_tabs` 배열로 선언 — 예: service/order 의 도메인/호스팅/부가서비스 탭 |

**제거된 것** (v2.3.0):
- 레이아웃 탭 (레이아웃 선택은 기본정보 탭의 카드 UI 로 통합)
- 추가 설정 탭의 페이지 너비 필드 (스킨의 `content_width` 로 통합)
- 스킨 탭 하단에 include 되던 레거시 `settings_view` — `settings_tabs` 배열 방식으로 대체

**동작 원리**:
- `/admin/site/pages/settings?slug={slug}` (admin backend) 또는 `/{slug}/settings` (frontend) 두 경로 모두 동일한 `pages-settings.php` 로드
- DB 에 레코드 없는 시스템 페이지도 `config/system-pages.php` 에서 정보 추출해 렌더 (v2.3.0 fallback)
- 저장은 **MERGE** 방식 — 탭별로 나누어 저장해도 다른 탭 데이터 보존 (v2.3.0)
- JS `saveSettings()` 는 DOM 에 존재하는 필드만 전송 (탭 분리 안전성 확보)

### 2.3 문서 에디터 (`/admin/site/pages/edit?slug=...`)

- `type=document` 페이지 편집용 위지윅 에디터 (Summernote)
- 파일: `resources/views/admin/site/site/pages-edit.php`
- 저장 시 `rzx_page_contents.content` 업데이트, locale 별 분리 저장
- "기본값 불러오기" 버튼 — 약관/개인정보처리방침 등은 법률 템플릿 제공

### 2.4 위젯 빌더 (`/admin/site/pages/widget-builder?slug=...`)

- `type=widget` 페이지 편집용 드래그앤드롭 빌더
- 파일: `resources/views/admin/site/site/pages-widget-builder.php`
- **좌측**: 사용 가능한 위젯 목록 (파일 기반 `/widgets/*/widget.json` 스캔 → `rzx_widgets` 자동 동기화)
- **중앙**: 현재 페이지의 위젯 배치 (SortableJS 로 순서 변경)
- **우측**: 선택한 위젯의 설정 폼 (`config_schema` JSON 기반 자동 생성)
- **하단**: 실시간 미리보기 iframe

### 2.5 데이터 관리 가이드 에디터 (`/admin/site/pages/compliance`)

- `data-policy` 전용 관리 UI
- 운영 국가 + 업종에 따라 법률 요구사항 템플릿 자동 생성
- 커스터마이징해서 저장 가능 (기본 → 커스텀 오버라이드)

---

## 3. DB 스키마

### 3.1 `rzx_page_contents` — 페이지 콘텐츠 (locale 당 1행)

```sql
id              INT UNSIGNED PK AUTO_INCREMENT
page_slug       VARCHAR(100) INDEX       -- URL 경로
page_type       VARCHAR(20)              -- 'document' | 'widget' | 'external' | 'system'
locale          VARCHAR(10) INDEX        -- 'ko', 'en', 'ja', 'zh_CN' ...
title           VARCHAR(500)
content         LONGTEXT                 -- HTML (document 타입)
seo_title       VARCHAR(500)
seo_description VARCHAR(500)
seo_keywords    VARCHAR(500)
og_image        VARCHAR(500)
is_system       TINYINT(1)               -- 시스템 페이지 플래그
is_active       TINYINT(1)               -- 공개 여부
created_at, updated_at
```

**단일 테이블 원칙** (v2.3.3 복귀): 페이지 본문은 locale 당 풀 콘텐츠 1행으로 여기에 저장. 제목·본문 모두 이 테이블만 읽고 씀. 폴백 체인은 현재 locale → `en` → `ko` 순서.

> v2.3.1/2.3.2 에서 한때 비원본 locale 을 `rzx_translations` 로 분리했으나 (slug/key + locale + content 로 실질 구조 동일하면서 ko 중복 저장·분기 코드·UI JOIN 비용 발생), v2.3.3 에서 단일 테이블로 복귀. CHANGELOG 참조.

### 3.2 `rzx_translations` — 짧은 번역 문자열

`rzx_translations` 는 **페이지 본문에는 사용하지 않음**. 아래 용도 전용:

- 게시판 본문 (`board_post.{id}.title/content`)
- UI 라벨 / 메뉴 (`menu.*`, 설정 문자열)
- 페이지 SEO 메타 (`page.{slug}.browser_title` · `meta_keywords` · `meta_description`) — 짧은 문자열

**저장소 선택 원칙**:
- 긴 페이지 본문(수 KB HTML) → `rzx_page_contents`
- 짧은 번역 문자열(라벨·메타) → `rzx_translations`

### 3.3 `rzx_page_widgets` — 위젯 배치

```sql
id            INT UNSIGNED PK
page_slug     VARCHAR(100) INDEX        -- 해당 페이지
widget_id     INT UNSIGNED              -- rzx_widgets.id 참조
widget_slug   VARCHAR(100)              -- 'hero', 'grid-section' 등
sort_order    INT                       -- 렌더링 순서
config        LONGTEXT                  -- JSON 위젯 설정 (다국어 필드 포함)
is_active     TINYINT(1)
```

### 3.4 `rzx_widgets` — 위젯 레지스트리

```sql
id            INT UNSIGNED PK
slug          VARCHAR(100) UNIQUE        -- 'hero', 'cta', 'grid-section'
name          VARCHAR(255)
description   TEXT
category      VARCHAR(50)                -- 'content' | 'layout' | 'marketing' | ...
icon          VARCHAR(255)
config_schema LONGTEXT                   -- JSON 스키마 (관리자 폼 자동 생성용)
template      LONGTEXT                   -- 커스텀 위젯의 HTML 템플릿
css, js       LONGTEXT                   -- 커스텀 위젯용
is_active     TINYINT(1)
```

**파일 기반 위젯**: `/widgets/{slug}/` 디렉터리에 `widget.json` + `render.php` 가 있으면 자동 스캔·동기화. DB 는 캐시 역할.

### 3.5 `rzx_settings` — 페이지별 설정 (JSON)

`key = 'page_config_{slug}'` 로 저장되는 JSON 구조:

```json
{
  "layout": "default",
  "skin": "default",
  "skin_settings": { ... },
  "show_title": true,
  "title_bg_type": "none",
  "title_bg_image": "",
  "title_bg_video": "",
  "show_breadcrumb": false,
  "full_width": false,
  "custom_css": "",
  "custom_header_html": "",
  "custom_footer_html": "",
  "mobile_layout": "",
  "mobile_skin": "",
  "perm_access": "all",
  "perm_edit": "admin",
  "perm_manage": "admin",
  "module_admins": []
}
```

상속 체계: **사이트 기본값 → 페이지 설정**. `layout: "inherit"` 이면 사이트 기본값을 따른다.

---

## 4. 페이지 렌더링 흐름

### 4.1 라우팅 우선순위 (`index.php`)

```
1. 고정 라우트 (/login, /register, /mypage, /admin, /api/*)
2. 게시판 slug (rzx_boards)
3. {slug}/edit, {slug}/settings (프론트 편집)
4. 시스템 페이지 (config/system-pages.php → view 파일)
5. 동적 페이지 (rzx_page_contents)
6. 플러그인 프론트 라우트
7. 404
```

### 4.2 페이지 렌더러 (`resources/views/customer/page.php`)

1. slug 로 `rzx_page_contents` 조회 → **locale 체인** 적용:
   - 현재 locale → `en` 폴백 → `ko` 폴백 → 아무 locale
2. `page_type` 에 따라 partial 분기:
   - `document` → `_page-partials/type-document.php`
   - `widget` → `_page-partials/type-widget.php`
   - `external` → `_page-partials/type-external.php`
   - `system` → 라우터가 직접 `view` include (이 단계로 오지 않음)
3. `page_config_{slug}` 설정 적용 (레이아웃/스킨/제목표시/커스텀HTML)
4. SEO 메타 태그 주입 (`$_seo['meta_tags']`)

### 4.3 Document partial

`type-document.php`:
- `.board-content` 래퍼로 감싸서 HTML 출력
- 스킨 설정의 `_bgClass`, `_showTitle`, `_hasTitleBg`, `_showBreadcrumb` 반영
- 제목 배경(이미지/비디오)은 `_page-title-bg.php` 로 위임
- 커스텀 헤더/푸터 HTML 삽입

### 4.4 Widget partial

`type-widget.php`:
1. `rzx_page_widgets WHERE page_slug=? AND is_active=1 ORDER BY sort_order`
2. 각 행마다:
   - `widget_slug` 로 `/widgets/{slug}/render.php` 동적 include
   - `config` JSON 을 `$widget_config` 로 전달
   - 다국어 필드는 현재 locale 로 변환
3. 홈 페이지는 특별 처리: `slug='home'` 으로 동일 로직

### 4.5 External partial

`type-external.php`:
- `content` 가 `http://` / `https://` 로 시작 → `<iframe>` 삽입
- `.php` / `.html` 파일 경로 → PHP `include` (서버 경로 검증)
- 그 외 → HTML 직접 출력 (document 와 유사)

---

## 5. 다국어 처리

### 5.1 콘텐츠 다국어

- **저장**: `(page_slug, locale)` 쌍으로 행 분리 → 각 locale 별 `title`/`content`/SEO 필드 독립
- **조회**: `page.php` 의 locale 체인 (현재 → en → ko → 아무거나)
- **편집**: 설정 화면의 "다국어" 탭에서 locale 별 편집, `rzx_multilang_input` 헬퍼 사용

### 5.2 위젯 다국어

위젯 `config` JSON 안의 다국어 필드는 `{"ko": "...", "en": "..."}` 형식으로 저장. `WidgetRenderer` 가 현재 locale 로 추출해 템플릿에 전달.

### 5.3 번역 키 방식 (시스템 페이지 제목)

`config/system-pages.php` 의 `title` 이 `site.pages.contact` 같은 키면 `resources/lang/{locale}/site.php` 에서 조회. 키가 없으면 문자열 그대로 사용.

---

## 6. SEO/메타

각 페이지 행마다 독립적으로:
- `seo_title` — `<title>` 태그용 (비면 `title` 사용)
- `seo_description` — `<meta description>` / OG description
- `seo_keywords` — `<meta keywords>` (레거시)
- `og_image` — Open Graph 이미지 URL

레이아웃 헤더(`skins/layouts/*/header.php`)에서 `$_seo['meta_tags']` 로 자동 삽입.

검색엔진 색인 제어: 설정의 `search_index` 체크박스 → `<meta robots="noindex">` 추가.

---

## 7. 스킨·레이아웃

### 7.1 레이아웃 선택 우선순위

```
사이트 기본 (settings.site_layout)
  ↓ (override)
페이지 설정 (page_config_{slug}.layout)
```

`layout = "none"` 이면 헤더/푸터 없는 생 콘텐츠만 출력. `layout = "inherit"` 이면 사이트 기본 따름.

### 7.2 스킨 시스템

- 스킨 메타: `/skins/{name}/skin.json` 에 `name`, `version`, `description`, `vars` (설정 스키마), `sections` 정의
- `SkinConfigRenderer` 가 `vars` 를 읽어 관리자 설정 폼 자동 생성 (radio, select, image, video, color, text 타입 지원)
- 스킨별 CSS 는 `/skins/{name}/style.css` 자동 로드

### 7.3 모바일 별도 레이아웃

`page_config.mobile_layout` 과 `mobile_skin` 설정 시 User-Agent 감지로 모바일 전용 레이아웃 적용.

---

## 8. 권한 시스템

### 8.1 페이지 설정의 권한 레벨

| 값 | 의미 |
|---|---|
| `all` | 모든 방문자 (비로그인 포함) |
| `member` | 로그인 회원 |
| `admin` | 관리자만 |
| `above` | 특정 회원 등급 이상 |
| `module_admin` | 지정된 모듈 관리자 |

각 동작별로 설정:
- **접근 권한** (`perm_access`) — 페이지를 볼 수 있는가
- **수정 권한** (`perm_edit`) — 프론트에서 콘텐츠 수정 가능한가
- **관리 권한** (`perm_manage`) — 설정 변경 가능한가

### 8.2 모듈 관리자

특정 회원에게 **이 페이지만** 관리 권한 부여. 이메일로 지정하며, 범위는:
- 문서 관리
- 댓글 관리 (게시판형에만 해당)
- 모듈 설정 변경

### 8.3 관리자 라우트 보호

`AdminRouter` (`rzxlib/Core/Router/AdminRouter.php`):
```php
$perm = AdminAuth::getRequiredPermission($route);
if (!AdminAuth::can($perm)) redirect('/admin/login');
```

페이지 관리는 `site` 권한이 필요.

---

## 9. 시스템 페이지 스키마 (v2.3.0)

### 9.1 `config/system-pages.php` 항목 구조

```php
[
    'slug'          => 'changelog',                    // URL 경로 (필수)
    'title'         => 'site.pages.changelog',         // 번역 키 (필수)
    'type'          => 'system',                       // document | widget | external | system
    'view'          => 'system/changelog/index.php',   // 공개 뷰 파일 (type=system)
    'edit_view'     => 'system/changelog/edit.php',    // 전용 편집 뷰 (v2.3.0 신규)
    'edit'          => '/changelog/edit',              // 편집 URL (edit_view 있으면 redirect 용 fallback)
    'icon'          => 'M12 8v4l3 3...',               // SVG path
    'emoji'         => '📋',                           // 관리자 UI 아이콘
    'color'         => 'indigo',
    'settings_tabs' => [                               // v2.3.0 — 최상위 설정 탭 배열
        [
            'key'   => 'hosting',
            'label' => '웹 호스팅',
            'icon'  => 'M5 12h14...',
            'view'  => 'system/service/settings/hosting.php',
        ],
        // 여러 개 자유 추가
    ],
]
```

### 9.2 신규 필드 (v2.3.0)

| 필드 | 용도 |
|---|---|
| `settings_tabs` | 설정 페이지의 **최상위 탭** 을 시스템 페이지가 선언. 각 탭은 `{key, label, icon, view}` 형식. 여러 개 가능. |
| `edit_view` | `/{slug}/edit` 프론트 라우트가 직접 include 할 **전용 편집 뷰 파일**. 데이터 관리 UI (업로드·버전·번역 등) 에 사용. 없으면 `edit` URL 로 리다이렉트. |

### 9.3 제거된 레거시 필드

- `settings_view` — 스킨 탭 하단에 include 되던 단일 뷰. v2.3.0 에서 `settings_tabs` 로 대체.
- `settings_tab` — 레거시 탭 라벨. `settings_tabs[N].label` 로 대체.

### 9.4 원칙: 설정 = 모양 + 기능 / 편집 = 데이터

| 구분 | URL | 목적 |
|---|---|---|
| **설정** | `/{slug}/settings` | 페이지 **모양**(스킨·레이아웃·배경) + **기능**(권한·에디터 옵션) 제어 |
| **편집** | `/{slug}/edit` | **데이터** 관리 (업로드·콘텐츠 CRUD·번역) |

### 9.5 플러그인 통합

`plugin.json`:
```json
{
  "system_pages": [
    {
      "slug": "staff",
      "title": "...",
      "type": "system",
      "view": "plugins/vos-salon/views/staff.php",
      "settings_tabs": [
        { "key": "roster", "label": "명부", "view": "plugins/vos-salon/views/staff-roster.php" }
      ]
    }
  ]
}
```

플러그인 활성화 시 `PluginManager::register()` 가 `config/system-pages.php` 와 병합 → 페이지 관리·메뉴 관리·라우터에 자동 반영.

**위젯 추가**: `/plugins/{slug}/widgets/{widget}/` 에 `widget.json` + `render.php` 배치 → 파일 기반 위젯으로 자동 스캔.

---

## 10. 실무 가이드

### 10.1 새 커스텀 페이지 만들기

1. 페이지 관리 → "새 페이지" 클릭
2. URL slug 입력 (영문·숫자·하이픈), 타입 선택 (`document` 또는 `widget`)
3. 브라우저 제목 + SEO 정보 입력
4. 저장 → 편집 버튼으로 콘텐츠 작성
5. (선택) 메뉴 관리에서 메뉴 등록 → 네비게이션에 노출

### 10.2 시스템 페이지 추가 (개발자)

1. `resources/views/system/{slug}/index.php` 뷰 파일 작성
2. `config/system-pages.php` 에 한 줄 추가:
   ```php
   [ 'slug' => 'my-page', 'title' => 'site.pages.my_page', 'type' => 'system',
     'view' => 'system/my-page/index.php', ... ]
   ```
3. (필요시) `resources/lang/{locale}/site.php` 의 `pages` 배열에 번역 키 추가
4. 코드 수정 끝 — 관리자 UI + 메뉴 관리 + 라우팅 자동 반영

### 10.3 다국어 번역 일괄 추가

```bash
# 예: site.pages.xxx 키를 모든 locale 에 추가
for loc in ko en ja zh_CN zh_TW de es fr id mn ru tr vi; do
  sed -i "s|\('service_order' => '[^']*',\)|\1\n        'my_key' => '번역',|" \
    /var/www/voscms/resources/lang/$loc/site.php
done
```

### 10.4 페이지 콘텐츠 직접 DB 편집

```php
// 관리자 UI 우회하고 스크립트로 콘텐츠 업데이트
$m = new mysqli('127.0.0.1', '...', '...', 'voscms_dev');
$m->set_charset('utf8mb4');
$s = $m->prepare('UPDATE rzx_page_contents SET content=? WHERE page_slug=? AND locale=?');
$s->bind_param('sss', $content, $slug, $locale);
$s->execute();
```

### 10.5 캐시 버스팅

CSS/JS 수정 후 Cloudflare 캐시 이슈 시:
- 스킨 헤더의 CSS 링크에 `?v=<?= filemtime($cssPath) ?>` 쿼리 추가
- 페이지 HTML 응답은 `cache-control: no-store` 이므로 즉시 반영
- Service Worker 캐시는 DevTools → Application → Clear site data

### 10.6 is_active = 0 활용

페이지 임시 비공개: 콘텐츠 작성 중이거나 검토 중일 때 `is_active=0` → 고객은 404, 관리자는 미리보기 가능.

---

## 11. 주요 파일·경로 요약

| 경로 | 역할 |
|---|---|
| `config/system-pages.php` | 시스템 페이지 정의 |
| `resources/views/admin/site/site/pages.php` | 관리자 페이지 목록 |
| `resources/views/admin/site/site/pages-settings.php` | 페이지 설정 탭 UI |
| `resources/views/admin/site/site/pages-edit.php` | 문서 에디터 |
| `resources/views/admin/site/site/pages-widget-builder.php` | 위젯 빌더 |
| `resources/views/customer/page.php` | 프론트 페이지 렌더러 |
| `resources/views/customer/_page-partials/type-*.php` | 타입별 렌더링 파트 |
| `resources/views/system/*/` | 시스템 페이지 뷰 파일 |
| `widgets/*/render.php` | 파일 기반 위젯 |
| `rzxlib/Core/Router/AdminRouter.php` | 관리자 라우팅 |
| `rzxlib/Core/Modules/WidgetRenderer.php` | 위젯 렌더 로직 |
| `rzxlib/Core/Skin/SkinConfigRenderer.php` | 스킨 설정 폼 생성 |
| `database/migrations/core/002_core_cms.sql` | 페이지 관련 테이블 스키마 |

---

## 12. 알려진 제약·향후 개선 여지

현재 시스템을 분석하며 파악된 주요 이슈를 심각도 순으로 정리한다. 개발 로드맵 우선순위 판단용.

### 12.1 [HIGH] `.board-content` CSS 가 파괴적 (스타일 오염)

**파일**: `resources/css/board-content.css`

**증상**: `.board-content p`, `.board-content h2`, `.board-content h3`, `.board-content table` 규칙이 **중첩된 모든 하위 요소까지** 적용된다. Tailwind 로 구조화된 현대적 페이지(LogoGuide, BiGuide, Brand 등) 를 `page_contents.content` 에 넣으면 카드 레이아웃·배경·보더가 강제로 덮어씌워져 디자인이 무너진다.

```css
/* 현재 — 모든 nested p 에 영향 */
.board-content p { padding-left: 1.5rem; background: #fff; border-left: 1px solid ... }

/* 권장 — 직계 자식 + class 없는 것만 */
.board-content > p:not([class]) { ... }
```

**더 심각**: 다크모드 `.dark .board-content * { color: ... !important; background-color: transparent !important }` — `*` 선택자 + `!important` 로 모든 인라인 `style="background:#..."` 까지 무효화. 다크모드에서 컬러 팔레트 swatch 가 전부 투명 배경으로 깨진다.

**임시 우회**: 해당 규칙 블록 주석 처리 (2026-04-20 세션에서 `margin` / `padding`/`bg`/`border` 블록 주석 처리).

**근본 해결**: 직계 자식(`>`) + `:not([class])` 스코핑 + 다크 `*` 규칙 제거.

### 12.2 [HIGH] CSS Cloudflare 30일 immutable 캐시

**증상**: 스킨·컴포넌트 CSS 응답 헤더가 `cache-control: public, max-age=2592000, immutable` 로 설정되어 Cloudflare 가 30일간 캐시. 수정 후 CF 퍼지 전까지 `cf-cache-status: HIT` 로 구버전 계속 서빙 → 개발 중 "수정했는데 반영 안 됨" 이슈.

**임시 우회**: `board-content.css` 링크에 `?v=<?= filemtime(...) ?>` 쿼리 부착 (2026-04-20 세션).

**근본 해결**: 전역 `asset()` 헬퍼 함수 추가. 예시:
```php
function asset(string $path): string {
    $full = __DIR__ . '/../../' . $path;
    return $path . (file_exists($full) ? '?v=' . filemtime($full) : '?v=' . time());
}
```
모든 CSS/JS 링크를 `asset('/resources/css/board-content.css')` 형태로 일괄 교체.

### 12.3 [HIGH] 권한 체계 구현 공백

**증상**: 페이지 설정 UI 에 접근 권한·수정 권한·관리 권한 5단계(`all`/`member`/`admin`/`above`/`module_admin`)가 있고 `page_config.perm_*` 필드에 저장되지만, 프론트엔드 렌더러(`resources/views/customer/page.php`) 에서 **실제로 이 값을 검사하는 코드가 일관되게 없다**. "설정은 되는데 안 걸리는" 상태 — 페이지 비공개 의도가 실효성 없음.

**영향**: `is_active=0` 으로만 숨길 수 있고, 회원/관리자 전용 페이지는 추가 코드 없이 구현 불가.

**해결**: `page.php` 에 `AccessControl::canAccess($page_config)` 검사 추가. 권한 부족 시 로그인 리다이렉트 또는 404.

### 12.4 [MEDIUM] External 페이지 타입 iframe 에러 핸들링 부재

**파일**: `resources/views/customer/_page-partials/type-external.php`

**증상**: 외부 URL 을 `<iframe src="...">` 로 삽입할 때 대상 사이트의 `X-Frame-Options: DENY/SAMEORIGIN` 또는 `Content-Security-Policy: frame-ancestors` 헤더를 검증하지 않는다. iframe 이 차단돼도 빈 화면만 표시. 특히 자체실행 JS 번들(Figma Make 등) 과는 script sandbox 충돌로 동작 실패.

**해결**:
- 서버사이드에서 `curl -I` 로 대상 헤더 선검사 → 차단되면 "새 창으로 열기" 버튼 fallback
- 또는 external 타입에 `mode` 필드 추가 (`iframe`/`redirect`/`link`)

### 12.5 [MEDIUM] 고아 레코드 + Slug 네이밍 컨벤션 부재

**증상**:
```
rzx_page_contents:
  id=187, page_slug='bi-guide'  ← 사용 안 됨, 고아
  id=189, page_slug='BiGuide'   ← 실사용
```
- camelCase 와 lowercase-hyphen 이 혼재: `LogoGuide`, `BiGuide` ↔ `terms`, `privacy`, `refund-policy`, `data-policy`
- URL 대소문자 혼용 → SEO 상 중복 URL 위험 (`/biguide`, `/BiGuide`, `/bi-guide` 가 다른 페이지로 인식될 수 있음)
- 관리자 UI 에서 slug 중복/고아 정리 기능 없음

**해결**:
- 컨벤션 문서화 (권장: **lowercase-hyphen**)
- 기존 camelCase slug 는 301 리다이렉트로 마이그레이션
- 관리자 UI 에 "사용 안 함" 필터 + 고아 레코드 정리 배치

### 12.6 [MEDIUM] 번역 키 누락 검증 체계 없음

**증상**: `config/system-pages.php` 가 `site.pages.contact` 를 참조했지만 13개 locale `resources/lang/*/site.php` 중 **어디에도 정의되지 않았다** (2026-04-20 수정). 관리자 UI 에 키가 raw string 으로 노출됐을 것.

**영향**: 같은 패턴으로 다른 번역 키도 누락됐을 가능성 → 검증 스크립트 필요.

**해결**: `scripts/verify-lang-keys.php` 배치:
```php
$pages = require 'config/system-pages.php';
foreach ($pages as $p) {
    $key = $p['title'];
    if (!preg_match('/^site\.pages\.[a-z_]+$/', $key)) continue;
    foreach (['ko','en','ja',...] as $loc) {
        $lang = require "resources/lang/$loc/site.php";
        [$ns, $path] = explode('.', $key, 2);
        if (!array_get($lang, str_replace('pages.', 'pages.', $path))) {
            echo "MISSING: $loc / $key\n";
        }
    }
}
```

### 12.7 [MEDIUM] 에디터 상태 관리 취약

**증상**:
- **페이지 버전 관리 없음**: 수정 시 이전 버전 보관 불가. 실수 삭제 복구 수단 없음 (게시판만 `board_post_history` 있음)
- **동시 편집 잠금 없음**: 두 관리자가 같은 페이지를 열어 각자 저장하면 나중 저장자가 앞 저장자 변경을 덮어씀 (last-write-wins). Optimistic lock (`updated_at` 충돌 검사) 없음
- **13개 locale 일괄 편집 UI 없음**: locale 별 개별 진입 필수 → 10개 페이지 × 13 locale = 130번 저장 클릭 (실무 비효율)

**해결**:
- `rzx_page_history` 테이블 도입 (`page_slug`, `locale`, `content`, `revised_at`, `revised_by`)
- 저장 시 `updated_at` 비교로 충돌 감지 → 경고 + merge UI
- 다국어 일괄 편집 모달 (테이블 UI 로 13 locale 한 화면에)

### 12.8 [LOW] 파일 권한 불일치

**증상**: 경로별 소유자·권한이 제각각:
```
resources/       → thevos:thevos   (개발자 직접 편집 OK)
skins/layouts/   → www-data:www-data 775 (sudo 필요)
assets/brand/    → thevos:thevos   (개발자 직접 편집 OK)
plugins/         → www-data:www-data (sudo 필요)
storage/         → www-data:www-data (PHP 쓰기 필요)
```

**영향**:
- 개발 시 sudo 혼용 → 스크립트 자동화 어려움
- 보안상 www-data 가 코드(skins, plugins)에 쓰기 권한 있으면 XSS/RCE 시 코드 변조 가능성

**해결**:
- 코드 경로(skins, plugins, resources)는 `thevos:www-data` + `g+w` 로 통일 → 개발자 쓰기 + PHP 읽기만
- 데이터 경로(storage, assets/uploads)만 www-data 쓰기
- 배포 스크립트에 `chown/chmod` 정규화 단계 추가

### 12.9 [LOW] 기타 자잘한 제약

- **위젯 간 의존성 관리 없음**: 위젯 `config_schema` 가 다른 위젯 설정을 참조할 수 없음 (예: 헤더 위젯의 로고 URL 을 다른 위젯이 공유)
- **페이지 복제 기능 없음**: 유사 페이지 제작 시 DB 직접 INSERT 필요
- **OG image 미리보기 없음**: 관리자 UI 에서 업로드 후 실제 렌더링 확인 불가
- **is_active 일괄 토글 없음**: 시즌 이벤트 페이지 일괄 공개/비공개 처리 시 페이지별 수동

---

## 13. 우선 처리 권장 순위

| 순위 | 이슈 | 난이도 | 영향 |
|---|---|---|---|
| 1 | `.board-content` CSS 리팩토링 (12.1) | 중 | 모든 Tailwind 페이지 정상화 |
| 2 | CSS 자동 cache-busting `asset()` 헬퍼 (12.2) | 낮음 | 개발 사이클 단축 |
| 3 | 권한 적용 로직 완성 (12.3) | 중 | 보안·기능성 확보 |
| 4 | 번역 키 검증 스크립트 (12.6) | 낮음 | 다국어 품질 보증 |
| 5 | Slug 네이밍 통일 + 고아 정리 (12.5) | 낮음 | SEO + UX |
| 6 | External iframe 에러 핸들링 (12.4) | 낮음 | 외부 연동 안정성 |
| 7 | 페이지 히스토리·동시편집 락 (12.7) | 높음 | 운영 안정성 |
| 8 | 파일 권한 정규화 (12.8) | 낮음 | 보안·개발 편의성 |

---

## 참고 문서

- [PAGE_SYSTEM.md](PAGE_SYSTEM.md) — 페이지 시스템 개념·플러그인 vs 시스템 페이지 구분
- [BOARD_SYSTEM.md](BOARD_SYSTEM.md) — 게시판 모듈 (페이지 시스템과 별개)
- [LAYOUT_SYSTEM.md](LAYOUT_SYSTEM.md) — 레이아웃·스킨 시스템 상세
- [I18N.md](I18N.md) — 다국어 헬퍼 (`__()`, `db_trans()`, `rzx_multilang_input`)
- [ADMIN_PERMISSION.md](ADMIN_PERMISSION.md) — 관리자 권한 체계
