# 페이지 시스템 (Page System)

RezlyX의 범용 페이지 관리 시스템. 문서/위젯/외부 3가지 타입을 지원하며, 스킨 기반 렌더링과 다국어 콘텐츠를 제공한다.

## 페이지 타입

| 타입 | 설명 | 렌더링 |
|------|------|--------|
| `document` | HTML 콘텐츠 문서 | 스킨 설정 적용, `board-content` CSS 클래스 |
| `widget` | 위젯 빌더 페이지 | `WidgetRenderer` + `rzx_page_widgets` 테이블 |
| `external` | 외부 페이지 | URL→iframe, PHP/HTML→include |

## 파일 구조

### 프론트 (고객 뷰)

| 파일 | 역할 |
|------|------|
| `customer/page.php` | 페이지 렌더러 (스킨 설정 적용) |
| `customer/_page-title-bg.php` | 제목 배경 공통 partial (이미지/동영상/오버레이) |
| `customer/page-settings.php` | 프론트 레이아웃 내 설정 래퍼 (embed 모드) |
| `customer/page-edit.php` | 프론트 레이아웃 내 편집 래퍼 (embed 모드) |

### 관리자 뷰

| 파일 | 역할 |
|------|------|
| `admin/site/pages-settings.php` | 페이지 환경 설정 (4개 탭) |
| `admin/site/pages-edit-content.php` | 콘텐츠 편집 (로케일별) |
| `admin/site/pages-document.php` | 범용 문서 에디터 |
| `admin/site/pages.php` | 페이지 목록 |

### 스킨

| 파일 | 역할 |
|------|------|
| `skins/page/{skin}/skin.json` | 스킨 정의 (메타 + 확장 변수) |
| `rzxlib/Core/Skin/SkinConfigRenderer.php` | skin.json → 설정 폼 자동 생성 |

## 라우팅

```
관리자:
  /admin/site/pages                → 페이지 목록
  /admin/site/pages/settings       → 환경 설정
  /admin/site/pages/edit-content   → 콘텐츠 편집

프론트 (embed 모드):
  /{slug}/settings                 → 프론트 레이아웃 내 설정
  /{slug}/edit                     → 프론트 레이아웃 내 편집
  /{slug}                          → 페이지 렌더링 (최종 fallback)
```

동적 페이지 라우팅은 게시판 slug 체크 이후의 최종 fallback으로 동작한다.

## DB 구조

### `rzx_page_contents`

| 컬럼 | 설명 |
|------|------|
| `page_slug` | 페이지 식별자 (URL slug) |
| `page_type` | document / widget / external |
| `locale` | 언어 코드 (ko, en, ja 등) |
| `title` | 페이지 제목 |
| `content` | HTML 콘텐츠 또는 URL |
| `is_active` | 활성 상태 |
| `is_system` | 시스템 페이지 여부 (terms, privacy 등) |

**로케일 체인**: `[$currentLocale, 'en', 'ko']` → 일치하는 첫 번째 로케일 사용, 없으면 아무 로케일.

### `rzx_settings` (페이지 설정)

키: `page_config_{slug}`, 값: JSON

```json
{
  "slug": "about",
  "browser_title": "회사 소개",
  "layout": "default",
  "skin": "default",
  "full_width": false,
  "search_index": "yes",
  "meta_title": "",
  "meta_description": "",
  "meta_keywords": "",
  "robots": "index,follow",
  "og_image": "/storage/pages/og_about_1774061295.jpg",
  "perm_access": "all",
  "perm_edit": "admin",
  "perm_manage": "admin",
  "module_admins": [],
  "skin_config": {
    "show_title": "1",
    "content_width": "max-w-5xl",
    "title_bg_type": "image",
    "title_bg_image": "/storage/skins/bg.jpg",
    "title_bg_height": "300",
    "title_bg_overlay": "40",
    "title_text_color": "white",
    "primary_color": "#3B82F6",
    "content_bg": "transparent"
  }
}
```

## 설정 탭 구조

### 1. 모듈 정보 (basic)

- 페이지 타입 (읽기 전용)
- URL / slug
- 브라우저 제목 (다국어 입력)
- 검색엔진 색인 (yes/no)
- SEO: 키워드(다국어), 설명(다국어), Meta Title, OG Image(업로드+URL), Robots
- **레이아웃 선택** — 카드형 그리드 UI, `skins/layouts/*/layout.json` 스캔
- **스킨 선택** — 카드형 그리드 UI, `skins/page/*/skin.json` 스캔
- 전체 너비 토글

레이아웃/스킨 선택은 썸네일 + 이름 + 버전을 카드로 표시하며, 클릭 시 파란 테두리로 선택 상태를 나타낸다.

### 2. 추가 설정 (addition)

- WYSIWYG 에디터 권한 컴포넌트 (`editor-permissions.php` 공용)
- 문서/댓글 분리, HTML 편집/파일 첨부/기본 컴포넌트/확장 컴포넌트 권한

### 3. 권한 관리 (permissions)

- 모듈 관리자 (이메일 추가/삭제)
- 접근 권한 / 페이지 수정 / 관리 권한 — 드롭다운 (모든 방문자/로그인 회원/관리자/등급별)

### 4. 스킨 (skin)

- **스킨 기본정보**: 이름, 제작자(URL+이메일), 날짜, 버전, 설명, 썸네일
- **확장 변수**: `SkinConfigRenderer`가 `skin.json`의 `vars`를 읽어 폼 자동 생성
- 저장 시 `page_config_{slug}.skin_config`에 머지 저장

## 스킨 시스템

### skin.json 구조

```json
{
  "title": { "ko": "기본 페이지 스킨", "en": "Default Page Skin", ... },
  "description": { ... },
  "version": "1.0.0",
  "date": "2026-03-21",
  "thumbnail": "thumbnail.png",
  "author": { "name": "RezlyX", "url": "https://rezlyx.com", "email": "info@rezlyx.com" },
  "vars": [ ... ]
}
```

### 지원 타입

| 타입 | HTML 요소 | 용도 |
|------|----------|------|
| `text` | `<input type="text">` | 짧은 문자열 |
| `textarea` | `<textarea>` | 긴 텍스트, CSS, HTML |
| `checkbox` | 토글 스위치 | on/off 설정 |
| `select` | `<select>` | 드롭다운 선택 |
| `radio` | `<input type="radio">` | 라디오 버튼 선택 |
| `color` | `<input type="color">` | 색상 선택기 |
| `number` | `<input type="number">` | 숫자 입력 (min/max) |
| `image` | 파일 업로드 + 미리보기 | 이미지 업로드 |
| `video` | 파일 업로드 + URL 입력 | 동영상 업로드 |

### 특수 속성

| 속성 | 설명 |
|------|------|
| `section` | 섹션 그룹핑 (다국어 지원) |
| `multilang` | `true`이면 다국어 입력 버튼 표시 |
| `depends_on` | 부모 필드명. 부모 비활성 시 자식 필드 자동 비활성 |
| `default` | 기본값 |
| `min` / `max` | number 타입의 범위 제한 |
| `options` | select/radio 타입의 선택지 배열 |

### depends_on 시스템

`skin.json`에서 필드 간 의존 관계를 선언적으로 정의:

```json
{
  "name": "title_bg_type",
  "depends_on": "show_title",
  "type": "radio",
  ...
}
```

**동작 방식:**

1. `SkinConfigRenderer`가 `depends_on` 속성 감지
2. 해당 `<div>`에 `data-depends-on="show_title"` HTML 속성 추가
3. `renderForm()` 끝에 자동 JS 코드 삽입:
   - 페이지 로드 시 부모 필드 상태에 따라 자식 필드 opacity/pointerEvents 설정
   - 부모 필드 change 이벤트에 리스너 등록

**부모 타입별 활성 조건:**

| 부모 타입 | 활성 | 비활성 |
|----------|------|--------|
| checkbox | checked | unchecked |
| radio | `none`, `0`, `disabled` 이외 값 | `none`, `0`, `disabled` |

게시판/페이지 어디서든 `skin.json`에 `"depends_on"` 한 줄만 추가하면 자동 동작한다.

## 제목 배경 시스템

스킨 설정의 제목 배경 관련 vars:

| 필드 | 타입 | 설명 |
|------|------|------|
| `title_bg_type` | radio | none / image / video |
| `title_bg_image` | image | 배경 이미지 업로드 |
| `title_bg_video` | video | MP4/WebM 업로드 또는 URL |
| `title_bg_height` | number | 배경 높이 (100~600px, 기본 200) |
| `title_bg_overlay` | number | 오버레이 투명도 (0~100%, 기본 40) |
| `title_text_color` | radio | auto / white / dark |

**렌더링 (`page.php`):**

```
제목 표시 OFF → 제목/배경 모두 숨김
제목 표시 ON + 배경 none → 일반 텍스트 제목 (<h1>)
제목 표시 ON + 이미지 → CSS background-image + 오버레이 + 중앙 정렬 제목
제목 표시 ON + 동영상 → <video> autoplay muted loop + 오버레이 + 중앙 정렬 제목
```

배경이 있을 때 브레드크럼은 배경 안에 표시되고, 배경이 없을 때는 콘텐츠 영역 상단에 표시된다.

## 스킨 설정 적용 범위

모든 스킨 설정은 3가지 페이지 타입 모두에 적용된다.

| 스킨 설정 | external | widget | document | 비고 |
|----------|:--------:|:------:|:--------:|------|
| `content_width` | ✅ | ✅ | ✅ | 콘텐츠 영역 최대 너비 |
| `show_title` + 배경 | ✅ | ✅ | ✅ | `_page-title-bg.php` partial 공용 |
| `show_breadcrumb` | ✅ | ✅ | ✅ | 배경 있으면 배경 안, 없으면 상단 |
| `primary_color` | ✅ | ✅ | ✅ | CSS 변수 `--page-primary` |
| `custom_css` | ✅ | ✅ | ✅ | `<style>` 태그로 출력 |
| `custom_header_html` | ✅ | ✅ | ✅ | 제목 위에 표시 |
| `custom_footer_html` | ✅ | ✅ | ✅ | 콘텐츠 아래에 표시 |
| `content_bg` | — | — | ✅ | 문서 타입 전용 (투명/흰색 카드) |
| `title_text_color` | ✅ | ✅ | ✅ | auto/white/dark |

### 렌더링 구조 (`page.php`)

```
┌─ 공통 (모든 타입) ─────────────────────────┐
│  custom_css 출력                           │
│  primary_color CSS 변수 출력               │
├─────────────────────────────────────────────┤
│  <div class="{content_width}">             │
│    breadcrumb (배경 없을 때)                │
│    custom_header_html                      │
│    ┌─ 제목 영역 ──────────────────────┐    │
│    │  배경 있음 → _page-title-bg.php  │    │
│    │  배경 없음 → 일반 <h1> 제목      │    │
│    └──────────────────────────────────┘    │
│    ┌─ 콘텐츠 영역 ────────────────────┐    │
│    │  external → iframe / include     │    │
│    │  widget  → WidgetRenderer        │    │
│    │  document → HTML + content_bg    │    │
│    └──────────────────────────────────┘    │
│    custom_footer_html                      │
│  </div>                                    │
└─────────────────────────────────────────────┘
```

### 파일 업로드 경로

| 대상 | 저장 경로 |
|------|----------|
| 스킨 배경 이미지/동영상 | `storage/skins/page/{skin}/{varName}_{timestamp}.{ext}` |
| OG Image | `storage/pages/og_{slug}_{timestamp}.{ext}` |

## API 엔드포인트

### pages-settings.php

| 메서드 | 액션 | 설명 |
|--------|------|------|
| POST (multipart) | `save_skin_config_multipart` | 스킨 설정 저장 (파일 업로드 포함) → `storage/skins/page/` |
| POST (multipart) | — | OG 이미지 파일 업로드 → `storage/pages/` |
| POST (JSON) | `save_settings` | 전체 설정 저장 → `page_config_{slug}` |
| POST (JSON) | `save_skin_config` | 기존 config의 `skin_config`만 머지 저장 (파일 없음) |
| POST (JSON) | `delete_og_image` | OG 이미지 파일 삭제 |

### pages-edit-content.php

| 메서드 | 액션 | 설명 |
|--------|------|------|
| POST (JSON) | `save` | 제목/타입/콘텐츠 저장 (slug 변경 시 관련 테이블 일괄 업데이트) |
| POST (JSON) | `delete` | 페이지 완전 삭제 (page_contents + page_widgets + menu_items) |
| POST (JSON) | `load_locale` | 특정 로케일의 제목/콘텐츠 로드 |

## Embed 모드

관리자가 프론트 페이지에서 설정/편집할 수 있도록 embed 모드를 지원한다.

```
/{slug}/settings → page-settings.php
  └── $_GET['embed'] = '1' 설정
  └── pages-settings.php include (admin sidebar/topbar 생략)
  └── 프론트 레이아웃 내에 렌더링
```

- embed 모드: admin UI가 프론트 레이아웃 안에 표시
- 비 embed 모드: 일반 관리자 페이지로 표시
- API URL은 항상 admin 경로 (`$adminUrl/site/pages/settings`)로 POST (프론트 URL로 POST하면 HTML 반환 문제)

## 저장 모달

저장 결과는 `result-modal.php` + `result-modal.js` 공통 모달로 표시:

- 성공 시: 현재 로케일의 `common.msg.saved` 메시지 표시 (data-saved 속성에서 읽음)
- 실패 시: API 에러 메시지 표시
- 13개 언어 지원

## 메뉴 시스템 연동

- 메뉴 아이템 생성 시 페이지 자동 생성 (`menus-api.php`)
- 메뉴 삭제 시 페이지 + 번역 정리
- slug 변경 시 menu_items URL 자동 업데이트
