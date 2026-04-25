# 레이아웃 시스템 (Layout System)

RezlyX의 사이트 전체 레이아웃과 스킨을 관리하는 시스템.

## 핵심 개념

### 레이아웃이란?
헤더(메뉴) + 콘텐츠 + 푸터를 감싸는 **껍데기**. 각 페이지는 독립적으로 동작하며, 레이아웃이 없어도 콘텐츠만 출력 가능.

### 레이아웃 적용 흐름 (index.php)

```
요청 → index.php → 페이지 콘텐츠 렌더링 → 레이아웃 적용

1. ?no_layout=1 파라미터 있으면:
   → 콘텐츠만 출력 (외부 iframe 삽입용)

2. 그 외:
   → 항상 사이트 레이아웃 적용
   → skins/layouts/{name}/header.php + 콘텐츠 + footer.php
```

### 4가지 사용 시나리오

| 시나리오 | 설정 | 결과 |
|---------|------|------|
| 일반 페이지 | 사이트 전체 레이아웃 | default/header.php + 콘텐츠 + default/footer.php |
| 특별한 페이지 | 개별 레이아웃 지정 | minimal/header.php + 콘텐츠 + minimal/footer.php |
| 레이아웃 "사용 안 함" | 내 사이트 접속 | **사이트 전체 레이아웃으로 폴백** (헤더/푸터 유지) |
| 외부 제공용 | ?no_layout=1 | 콘텐츠만 출력 (iframe 삽입 가능) |

**"사용 안 함"은 외부 제공 설정이지, 내 사이트에서 헤더/푸터를 제거하는 것이 아님.**

## 레이아웃 파일 구조

```
skins/layouts/{name}/
├── layout.json      # 메타 정보 + 확장 변수 (vars)
├── header.php       # 필수 — 없으면 헤더 없이 출력
├── footer.php       # 필수 — 없으면 푸터 없이 출력
├── main.php         # 레거시 (현재 미사용)
└── thumbnail.png    # 미리보기 썸네일
```

### header.php 역할
- `<html>`, `<head>`, 다크모드, Tailwind CDN, 파비콘, PWA
- PC 상단 메뉴 (네비게이션)
- 언어 선택기, 다크모드 토글, 로그인/회원가입 버튼

### footer.php 역할
- 푸터 (카피라이트, 약관 링크)
- 모바일 하단 메뉴바 + 더보기 메뉴
- JS (다크모드, 모바일 메뉴, PWA 등)
- `</body>`, `</html>`

## 기본 제공 레이아웃

| 이름 | 설명 | 모바일 메뉴 |
|------|------|-----------|
| **default** | 기본 레이아웃 | 드롭업 (하단 바 위에 팝업) |
| **modern** | default 기반 (디자인 변경용) | 드롭업 |
| **minimal** | 간소화 레이아웃 | Bottom Sheet (하단에서 올라옴) |

## 카테고리별 스킨

| 카테고리 | DB 키 | 스킨 디렉토리 | 설명 |
|---------|-------|-------------|------|
| 레이아웃 | `site_layout` | `skins/layouts/{name}/` | 헤더+콘텐츠+푸터 |
| 페이지 | `site_page_skin` | `skins/page/{name}/` | 문서/위젯/외부 페이지 |
| 게시판 | `site_board_skin` | `skins/board/{name}/` | 목록/읽기/쓰기 |
| 회원 | `site_member_skin` | `skins/member/{name}/` | 로그인/가입/마이페이지 |

## 스킨 적용 우선순위

```
개별 설정 (page_config_{slug}.skin) > 전체 설정 (site_page_skin) > 기본값 (default)
```

## 설정 파일

### layout.json

```json
{
  "title": { "ko": "기본 레이아웃", "en": "Default Layout" },
  "description": { "ko": "헤더 + 콘텐츠 + 푸터 기본 구조" },
  "version": "1.0.0",
  "author": { "name": "RezlyX", "url": "https://rezlyx.com" },
  "menus": {
    "main_menu": { "name": { "ko": "메인 메뉴", "en": "Main Menu" } }
  },
  "vars": [
    {
      "name": "point_color",
      "title": { "ko": "포인트 컬러" },
      "type": "color",
      "default": "#3B82F6",
      "tab": "basic"
    },
    {
      "name": "show_title",
      "title": { "ko": "제목 표시" },
      "type": "checkbox",
      "default": "1",
      "tab": "basic"
    },
    {
      "name": "title_bg_type",
      "title": { "ko": "배경 타입" },
      "type": "radio",
      "options": ["none", "image", "video"],
      "default": "none",
      "depends_on": "show_title",
      "tab": "basic"
    }
  ],
  "tabs": {
    "basic": { "ko": "기본 설정", "en": "Basic" },
    "header": { "ko": "헤더", "en": "Header" },
    "footer": { "ko": "푸터", "en": "Footer" }
  }
}
```

### vars 속성

| 속성 | 타입 | 설명 |
|------|------|------|
| `name` | string | 변수명 (고유) |
| `title` | object | 다국어 라벨 |
| `type` | string | text, textarea, checkbox, select, radio, color, number, image, video |
| `default` | string | 기본값 |
| `description` | object | 다국어 설명 |
| `options` | array | select/radio의 선택지 |
| `depends_on` | string | 이 필드가 활성일 때만 표시 (checkbox name) |
| `tab` | string | 소속 탭 (tabs에 정의된 키) |
| `section` | object | 섹션 제목 (구분선 + 제목) |

### 푸터 카피라이트 `{year}` 플레이스홀더 (v2.3.8)

레이아웃 설정의 `copyright` 필드는 텍스트 안의 `{year}` 토큰을 렌더링 시점에 현재 연도로 자동 치환합니다.

- 입력 예: `© 2009-{year} <strong>MyCompany</strong>. All rights reserved.`
- 렌더 결과: `© 2009-2026 MyCompany. All rights reserved.`

DB에는 원본(`{year}` 포함)이 저장되므로 매년 손댈 필요 없음. 다국어 값(배열)인 경우 `Translator::getLocale()` 로 현재 로케일 → en → 첫 값 폴백 후 치환. 적용 푸터: `default`, `minimal`, `modern`(+ backup), `resources/views/layouts/base-footer.php`.

레이아웃 설정 모달의 카피라이트 필드 description에 13개국어 사용 안내 자동 표시 (예: 영어 → "Use {year} to auto-substitute the current year. (e.g., © 2009-{year} MyCompany)").

## 레이아웃 제작 가이드

### 새 레이아웃 만들기

1. `skins/layouts/{name}/` 디렉토리 생성
2. `layout.json` 작성 (메타 + vars)
3. `header.php` 작성 (HTML head + 메뉴)
4. `footer.php` 작성 (푸터 + JS + `</body></html>`)
5. `thumbnail.png` 추가 (선택)

### header.php 필수 요소

```php
<?php
// 사용 가능한 변수:
// $baseUrl, $config, $siteSettings, $currentLocale
// $isLoggedIn, $currentUser, $isAdmin
// $mainMenu (메뉴 배열), $siteName
// $__layoutConfig (layout.json vars의 저장된 값)
?>
<!DOCTYPE html>
<html lang="<?= $currentLocale ?>">
<head>
    <!-- 필수: Tailwind, 파비콘, SEO 메타 등 -->
</head>
<body>
    <!-- 헤더/메뉴 HTML -->
```

### footer.php 필수 요소

```php
    <!-- 푸터 HTML -->
    <!-- 모바일 메뉴 (선택) -->
    <!-- JS -->
</body>
</html>
```

### 콘텐츠 출력

header.php와 footer.php 사이에 `$content` 변수가 자동으로 echo됩니다. 레이아웃 파일에서 콘텐츠를 직접 echo할 필요 없음.

## 외부 콘텐츠 제공 (iframe)

게시판/페이지를 다른 사이트에 제공할 때:

```
내 사이트:
  https://mysite.com/board/notice
  → 헤더 + 게시판 + 푸터 (정상 표시)

외부 사이트에서 iframe:
  <iframe src="https://mysite.com/board/notice?no_layout=1">
  → 콘텐츠만 출력 (헤더/푸터 없음)
  → 외부 사이트의 디자인에 자연스럽게 삽입
```

## 관리자 페이지 (레이아웃 관리)

경로: `admin/site/design.php`

3단 구조: 좌측(미리보기+메뉴) → 중앙(스킨 목록) → 우측(상세 설정)

## DB 저장

`rzx_settings` 테이블:

| 키 | 값 | 설명 |
|----|---|------|
| `site_layout` | `default` | 사이트 레이아웃 |
| `site_page_skin` | `default` | 페이지 기본 스킨 |
| `site_board_skin` | `default` | 게시판 기본 스킨 |
| `site_member_skin` | `default` | 회원 기본 스킨 |
| `skin_detail_layout_{name}` | JSON | 레이아웃 상세 설정값 |

## SkinConfigRenderer

`skin.json`/`layout.json`의 `vars`를 읽어 설정 폼을 자동 생성하는 공용 렌더러.

- 지원 타입: text, textarea, checkbox, select, radio, color, number, image, video
- `depends_on`: 필드 간 의존 관계 (체크박스 연동)
- `tab`: 탭 구조 지원
- 파일: `rzxlib/Core/Skin/SkinConfigRenderer.php`
