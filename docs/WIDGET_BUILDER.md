# RezlyX 위젯 빌더 매뉴얼

## 개요

위젯 빌더는 RezlyX 홈페이지의 콘텐츠를 WYSIWYG 방식으로 구성하는 관리자 도구입니다.
드래그앤드롭으로 위젯을 배치하고, 실시간 미리보기로 결과를 확인하며, 인라인 편집과 그리드 스냅을 지원합니다.

- **접속 경로**: `/theadmin/site/pages/widget-builder?page_id={id}`
- **라우트**: `index.php` → `site/pages/widget-builder`

---

## 아키텍처

### 파일 구조

```
resources/views/admin/site/
├── pages-widget-builder.php          # 메인 HTML (레이아웃, 모달, CDN)
├── pages-widget-builder-js.php       # 코어 JS (WB 전역 객체, SortableJS, 블록 생성)
├── pages-widget-builder-ajax.php     # AJAX 핸들러 (저장, 이미지/비디오 업로드)
├── pages-widget-builder-edit-js.php  # 편집 패널 JS (WBEdit 모듈, 필드 렌더링)
├── pages-widget-builder-edit-items-js.php  # 아이템 JS (hero_images, buttons, video)
├── pages-widget-builder-inline-js.php      # 인라인 편집 JS (WBInline 모듈)
└── pages-widget-builder-grid-js.php        # 그리드 드래그 JS (WBGrid 모듈)

rzxlib/Core/Modules/
└── WidgetRenderer.php                # 위젯 렌더링 엔진 (프론트 + 미리보기 공용)
```

### JS 모듈 구조

```
WB (전역)                   ← pages-widget-builder-js.php
├── WBEdit (편집 패널)       ← pages-widget-builder-edit-js.php
│   ├── hero_images 관리     ← pages-widget-builder-edit-items-js.php
│   ├── buttons 관리         ← (동일 파일)
│   └── video 업로드         ← (동일 파일)
├── WBInline (인라인 편집)   ← pages-widget-builder-inline-js.php
└── WBGrid (그리드 드래그)   ← pages-widget-builder-grid-js.php
```

### 스크립트 로드 순서

```
1. Tailwind CSS CDN
2. jQuery CDN + Summernote CDN (리치텍스트 에디터)
3. SortableJS CDN (드래그앤드롭)
4. 데이터 스크립트 (widgetIconMap, translations, initialWidgets)
5. pages-widget-builder-js.php (WB 코어)
   └── pages-widget-builder-edit-js.php (WBEdit)
       ├── pages-widget-builder-edit-items-js.php (아이템 확장)
       ├── pages-widget-builder-inline-js.php (WBInline)
       └── pages-widget-builder-grid-js.php (WBGrid)
```

> **중요**: Richtext 모달 HTML(`#richtextModal`)은 JS include보다 **앞에** 위치해야 합니다. JS 초기화 시 DOM 요소를 참조하므로 순서가 바뀌면 에러가 발생합니다.

---

## 데이터베이스

### 테이블

| 테이블 | 용도 |
|--------|------|
| `rzx_widgets` | 위젯 레지스트리 (slug, name, type, config_schema, template) |
| `rzx_page_widgets` | 페이지별 위젯 배치 (page_id, widget_id, sort_order, config) |

### 내장 위젯 (8종)

| slug | 이름 | 주요 config 필드 |
|------|------|-----------------|
| `hero` | 히어로 | layout, height, bg_color, overlay_opacity, hero_images[], buttons[], element_positions |
| `features` | 특징 | title, subtitle, items[](icon, title, desc) |
| `services` | 서비스 | title, subtitle, max_items |
| `stats` | 통계 | title, items[](value, label) |
| `testimonials` | 후기 | title, items[](name, role, text) |
| `cta` | CTA | title, subtitle, btn_text, btn_url, bg_color |
| `text` | 텍스트 | content(HTML, i18n), custom_css, custom_js |
| `spacer` | 여백 | height |

### config_schema 구조

```json
{
  "fields": [
    {
      "key": "title",
      "type": "text",
      "label": "제목",
      "i18n": true,
      "default": "Welcome"
    },
    {
      "key": "layout",
      "type": "select",
      "label": "레이아웃",
      "options": [
        { "value": "center", "label": "Center" },
        { "value": "fullscreen", "label": "Fullscreen" }
      ]
    }
  ]
}
```

---

## 필드 타입

편집 패널에서 지원하는 config_schema 필드 타입:

| type | 설명 | 옵션 |
|------|------|------|
| `text` | 텍스트 입력 | i18n 지원 |
| `textarea` | 여러 줄 텍스트 | i18n 지원 |
| `number` | 숫자 입력 | — |
| `color` | 색상 선택기 | — |
| `toggle` | ON/OFF 스위치 | — |
| `select` | 드롭다운 | `options: [{value, label}]` |
| `range` | 슬라이더 | `min`, `max` |
| `image` | 이미지 업로드 + URL | — |
| `video` | 비디오 업로드 + URL | YouTube/Vimeo 자동 감지 |
| `richtext` | WYSIWYG HTML 편집 | Summernote 모달, i18n 지원 |
| `code` | 코드 에디터 | `lang: "css"` 또는 `"javascript"` |

### 특수 필드 (config_schema의 key 이름 기반)

| key | 처리 방식 |
|-----|----------|
| `hero_images` | edit-items-js.php에서 이미지 슬라이드 편집 UI 자동 생성 |
| `buttons` | edit-items-js.php에서 버튼 목록 편집 UI 자동 생성 |
| `element_positions` | 그리드 드래그 시스템에서 자동 관리 |

### i18n (다국어) 필드

`"i18n": true`인 필드는 언어별 값을 객체로 저장:

```json
{
  "title": {
    "ko": "환영합니다",
    "en": "Welcome",
    "ja": "ようこそ"
  }
}
```

편집 패널에서는:
- 기본 로케일: 상단에 표시
- 기타 언어: 지구본 버튼 클릭 → 접이식 패널 펼침

---

## 리치텍스트 편집 (Summernote)

### 동작 방식

1. `richtext` 타입 필드 → textarea + "편집" 버튼으로 렌더링
2. 편집 버튼 클릭 → Summernote 모달 열림
3. 모달에서 WYSIWYG 편집 → "적용" 클릭 → textarea에 HTML 저장
4. 편집 패널 "적용" → config에 저장 → 미리보기 갱신

### 모달 기능

| 기능 | 조작 |
|------|------|
| 이동 | 헤더 영역 드래그 |
| 리사이즈 | 우하단 모서리 드래그 (최소 400x300) |
| 전체화면 | 최대화 버튼 클릭 또는 헤더 더블클릭 |
| 복원 | 전체화면 상태에서 다시 클릭 → 이전 크기/위치로 복원 |
| 닫기 | X 버튼, 취소 버튼, 배경 클릭 |

### Summernote 툴바

```
스타일 | 글꼴(B,I,U,S) | 크기 | 색상 | 문단(목록,정렬) | 표 | 삽입(링크,이미지,비디오,구분선) | 보기(전체화면,코드뷰)
```

### CDN 의존성 (pages-widget-builder.php)

```html
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
```

---

## CSS/JS 코드 에디터

### 텍스트 위젯 전용

텍스트 위젯의 config_schema에 `custom_css`와 `custom_js` 필드가 포함됩니다.

```json
{
  "fields": [
    { "key": "content", "type": "richtext", "label": "Content (HTML)", "i18n": true },
    { "key": "custom_css", "type": "code", "label": "Custom CSS", "lang": "css" },
    { "key": "custom_js", "type": "code", "label": "Custom JavaScript", "lang": "javascript" }
  ]
}
```

### 에디터 UI

- 다크 배경 (`bg-zinc-900`) + 초록 텍스트 (`text-green-400`)
- Monospace 폰트 (Fira Code, Cascadia Code, JetBrains Mono, Consolas)
- 언어 배지: CSS = 보라색, JavaScript = 노란색
- Tab 키로 2스페이스 들여쓰기

### 렌더링 처리 (WidgetRenderer)

**CSS**: 위젯 ID로 자동 스코핑
```php
// 입력: .title { color: red; }
// 출력: <style>#rzx-text-1234 .title { color: red; }</style>
```

**JavaScript**: IIFE(즉시실행함수)로 래핑, `el` 변수로 위젯 DOM 요소 접근
```php
// 출력: <script>(function(){ var el=document.getElementById("rzx-text-1234"); /* 사용자 JS */ })();</script>
```

### CSS 스코핑 규칙 (`scopeWidgetCss`)

| 원본 셀렉터 | 변환 결과 |
|------------|----------|
| `body { ... }` | `#rzx-text-XXXX { ... }` |
| `html { ... }` | `#rzx-text-XXXX { ... }` |
| `* { ... }` | `#rzx-text-XXXX * { ... }` |
| `:root { ... }` | `#rzx-text-XXXX { ... }` |
| `.my-class { ... }` | `.my-class { ... }` (변경 없음) |

> content 내부의 `<style>` 태그도 동일한 스코핑이 적용됩니다.

---

## 비디오 시스템

### 비디오 URL 자동 감지

WidgetRenderer가 비디오 URL을 분석하여 적절한 태그로 변환:

| URL 패턴 | 렌더링 방식 |
|----------|------------|
| `youtube.com/watch?v=XXX` 또는 `youtu.be/XXX` | `<iframe>` (YouTube embed) |
| `vimeo.com/XXXXX` | `<iframe>` (Vimeo embed) |
| `.mp4`, `.webm`, `.ogg` (직접 링크) | `<video>` 태그 |
| 업로드 파일 (`/storage/widgets/videos/`) | `<video>` 태그 |

### 비디오 크기 조절

영역을 완전히 채우는 CSS:
```css
position: absolute;
top: 50%; left: 50%;
width: max(100%, 177.78vh);   /* 16:9 가로 커버 */
height: max(100%, 56.25vw);   /* 16:9 세로 커버 */
transform: translate(-50%, -50%);
```

> 세로 영상이든 가로 영상이든 컨테이너를 빈틈없이 채웁니다.

### 편집 패널 비디오 필드

두 영역으로 분리:
1. **파일 업로드**: mp4/webm/ogg, 최대 50MB, 드래그앤드롭 스타일
2. **URL 입력**: YouTube/Vimeo/직접 링크

> 두 곳 모두 데이터가 있으면 **업로드 파일 우선** 적용 (`data-uploaded-url` 추적)

---

## 히어로 위젯 상세

### 레이아웃 옵션

| 값 | 설명 |
|----|------|
| `center` | 중앙 정렬 (텍스트 center) |
| `fullscreen` | 전체화면 (뷰포트 100vh) |
| `left-image` | 좌측 이미지 + 우측 텍스트 |
| `right-image` | 우측 이미지 + 좌측 텍스트 |

### 높이 옵션

| 값 | 설명 |
|----|------|
| `full` | 전체 화면 (min-h-screen) |
| `large` | 큰 (h-[600px]) |
| `medium` | 중간 (h-[400px]) |
| `small` | 작은 (h-[300px]) |

### z-index 레이어

```
z-[1]  : 배경 비디오/이미지
z-[5]  : 오버레이 (반투명 검정)
z-20   : 콘텐츠 (텍스트, 버튼)
```

### hero_images (이미지 슬라이드)

```json
{
  "hero_images": [
    {
      "url": "/storage/widgets/images/hero1.jpg",
      "position": "center center"
    }
  ]
}
```

- 최대 5장
- `position`: `object-position` CSS 값 (9방향 선택기 제공)
- 여러 장이면 자동 슬라이드 (5초 간격, 크로스페이드)

### element_positions (그리드 스냅)

```json
{
  "element_positions": {
    "title": "top-center",
    "subtitle": "center",
    "buttons": "bottom-center"
  }
}
```

9방향: `top-left`, `top-center`, `top-right`, `center-left`, `center`, `center-right`, `bottom-left`, `bottom-center`, `bottom-right`

---

## 편집 패널 (WBEdit)

### 동작 흐름

```
톱니바퀴(btn-config) 클릭
  → openEditPanel(block)
    → config_schema 파싱
    → 필드 렌더링 (renderEditFields)
    → 오른쪽 사이드 패널 표시
    → 해당 블록 파란색 하이라이트

"적용" 클릭
  → saveEditFieldsToTemp() (모든 .edit-field 값 수집)
  → block.dataset.config에 JSON 저장
  → loadPreview() → iframe 새로고침
  → 패널 닫기 + 성공 메시지

"취소" / ESC
  → 변경 사항 버림 + 패널 닫기
```

### i18n 필드 렌더링

```
[기본 로케일 입력] [🌐 지구본 버튼]
  ├─ 펼침 시:
  │   [en] 입력 필드
  │   [ja] 입력 필드
  │   [zh_CN] 입력 필드
  │   ...
```

---

## 인라인 편집 (WBInline)

### 동작 흐름

```
연필(btn-edit) 클릭
  → WBInline.enterInlineMode(block)
    → iframe 숨김
    → 서버에서 위젯 HTML 가져오기 (AJAX render)
    → 페이지에 직접 삽입
    → data-widget-field 속성이 있는 요소에 contenteditable 적용
    → 하단 Save/Cancel 오버레이 바 표시

Save 클릭
  → 각 data-widget-field 요소의 textContent 수집
  → config에 반영
  → iframe 복원 + loadPreview()

Cancel / ESC
  → 인라인 컨테이너 제거
  → iframe 복원 (변경 없음)
```

### WidgetRenderer의 data-widget-field

```html
<!-- hero 위젯 -->
<h1 data-widget-field="title">제목</h1>
<p data-widget-field="subtitle">부제목</p>
<a data-widget-field="btn_text">버튼</a>

<!-- text 위젯 -->
<div data-widget-field="content">HTML 콘텐츠</div>
```

---

## 그리드 드래그 (WBGrid)

### 동작 흐름

```
인라인 편집 모드 진입 시 자동 활성화

data-widget-field 요소 위에 파란 원형 드래그 핸들 표시
  → 핸들 mousedown → 드래그 시작
  → 3x3 그리드 오버레이 표시 (점선 격자)
  → 마우스 이동 → 가장 가까운 셀 하이라이트 (파란 반투명)
  → mouseup → element_positions에 위치 저장

CSS 위치 적용:
  WidgetRenderer.elementPositionStyle($position) →
    top-left: "align-items:flex-start; justify-content:flex-start; text-align:left"
    center: "align-items:center; justify-content:center; text-align:center"
    bottom-right: "align-items:flex-end; justify-content:flex-end; text-align:right"
```

---

## AJAX 핸들러

**파일**: `pages-widget-builder-ajax.php`

| action | 메서드 | 설명 |
|--------|--------|------|
| `save_widget_layout` | POST | 위젯 배치 순서 + config 일괄 저장 |
| `upload_widget_image` | POST | 이미지 업로드 (jpg/png/gif/webp/svg, 5MB) |
| `upload_widget_video` | POST | 비디오 업로드 (mp4/webm/ogg, 50MB) |
| `render_widget` | POST | 위젯 단일 렌더링 (미리보기 iframe용) |

### 저장 데이터 형식

```json
{
  "action": "save_widget_layout",
  "page_id": 1,
  "widgets": [
    {
      "widget_id": 1,
      "sort_order": 0,
      "config": "{\"title\":{\"ko\":\"환영합니다\"},\"layout\":\"center\"}"
    }
  ]
}
```

---

## 커스텀 위젯 생성

### 관리 페이지

- **목록**: `/theadmin/site/widgets`
- **생성**: `/theadmin/site/widgets/create`

### 커스텀 위젯 구조

```json
{
  "name": "My Widget",
  "slug": "my-widget",
  "type": "custom",
  "template": "<div class=\"my-widget\"><h2>{{title}}</h2><p>{{description}}</p></div>",
  "config_schema": {
    "fields": [
      { "key": "title", "type": "text", "label": "Title", "i18n": true },
      { "key": "description", "type": "textarea", "label": "Description" }
    ]
  }
}
```

- `{{변수}}` 치환 기반 렌더링
- i18n 필드는 현재 로케일 → ko → en 순으로 폴백
- 커스텀 위젯도 CSS 스코핑 적용 (`scopeWidgetCss`)

---

## WidgetRenderer 핵심 메서드

| 메서드 | 설명 |
|--------|------|
| `render(array $pageWidgets)` | 전체 위젯 목록 렌더링 |
| `renderSingle(array $widget, array $config)` | 단일 위젯 렌더링 |
| `t(array $config, string $key, string $default)` | 다국어 값 추출 (locale → ko → en) |
| `extractYouTubeId(string $url)` | YouTube URL에서 ID 추출 |
| `extractVimeoId(string $url)` | Vimeo URL에서 ID 추출 |
| `scopeWidgetCss(string $css, string $scope)` | CSS 전역 셀렉터 스코핑 |
| `elementPositionStyle(string $position)` | 9방향 위치 → CSS 스타일 변환 |

---

## 트러블슈팅

### 톱니바퀴 클릭이 동작하지 않음
- **원인**: Richtext 모달 HTML이 JS보다 뒤에 위치하면 `getElementById`가 null 반환 → WBEdit IIFE 중단
- **해결**: 모달 HTML을 JS include 앞에 배치

### 위젯 CSS가 페이지 전체에 영향
- **원인**: content 내 `<style>body { ... }</style>` 등 전역 셀렉터
- **해결**: `scopeWidgetCss()`가 body/html/*/`:root`를 위젯 ID로 자동 치환

### 비디오가 영역을 채우지 못함
- **원인**: 고정 크기(120vw/120vh) 사용 시 세로 영상에서 빈 공간 발생
- **해결**: `max(100%, 177.78vh)` / `max(100%, 56.25vw)` 사용

### 가운데 정렬이 좌측으로 치우침
- **원인**: `max-w-5xl`, `flex items-center`에서 자식에 `w-full` 누락, `px-4` 패딩
- **해결**: max-w 제거 + w-full 추가 + 패딩 제거

### richtext 저장 시 JS 에러
- **원인**: `saveRichtextToTemp()` 호출이 남아있으나 함수 제거됨
- **해결**: richtext textarea는 `.edit-field` 셀렉터로 자동 수집되므로 별도 함수 불필요
