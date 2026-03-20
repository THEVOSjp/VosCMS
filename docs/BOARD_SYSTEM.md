# RezlyX 게시판 시스템

> **버전**: 1.10.0 | **최종 업데이트**: 2026-03-20

## 1. 시스템 개요

RezlyX 게시판은 커스텀 MVC 아키텍처 기반의 다기능 게시판 시스템이다. 관리자가 여러 게시판을 생성하고, 각 게시판별로 스킨/레이아웃/권한/확장변수 등을 독립적으로 설정할 수 있다.

### 기술 스택

| 구분 | 기술 |
|------|------|
| 백엔드 | PHP 8.x, PDO (MySQL/MariaDB) |
| 프론트엔드 | Tailwind CSS (CDN), Vanilla JS |
| 에디터 | Summernote (글쓰기/수정) |
| 드래그정렬 | SortableJS (분류/확장변수) |
| 다국어 | 13개 언어, `__()` 함수 + `db_trans()` 폴백 |
| 인증 | `RzxLib\Core\Auth\Auth` + 세션 기반 |

### 주요 파일

| 경로 | 설명 |
|------|------|
| `index.php` | 라우팅 진입점 |
| `resources/views/admin/site/boards*.php` | 관리자 페이지 (목록/생성/편집/API/휴지통) |
| `resources/views/admin/components/board/section-*.php` | 관리자 공통 컴포넌트 8개 |
| `resources/views/customer/board/*.php` | 프론트 페이지 (_init, list, read, write, settings, api-*) |
| `rzxlib/Core/Modules/ExtraVarRenderer.php` | 확장 변수 렌더링 모듈 (15종 타입별 input/display) |
| `rzxlib/Core/Skin/SkinConfigRenderer.php` | skin.json 파싱 + 폼 생성 |
| `rzxlib/Core/Layout/LayoutManager.php` | 레이아웃 자동 적용 |
| `rzxlib/Core/Helpers/multilang.php` | rzx_multilang_input() |
| `skins/{name}/board/skin.json` | 스킨 메타 + 설정 변수 |
| `skins/{name}/layouts/layout.json` | 레이아웃 메타 |
| `database/migrations/017*.sql, 018*.sql` | DB 마이그레이션 |

---

## 2. 라우팅

### 관리자 라우트 (`/admin/site/boards/*`)

| URL | 파일 | 설명 |
|-----|------|------|
| `/admin/site/boards` | `boards.php` | 게시판 목록 |
| `/admin/site/boards/create` | `boards-create.php` | 게시판 생성 |
| `/admin/site/boards/edit?id={id}&tab={tab}` | `boards-edit.php` | 게시판 설정 (탭) |
| `/admin/site/boards/api` | `boards-api.php` | 관리자 AJAX API |
| `/admin/site/boards/trash?id={id}` | `boards-trash.php` | 휴지통 |

### 프론트 라우트 — 명시적 경로 (`/board/...`)

| URL 패턴 | 파일 | 설명 |
|----------|------|------|
| `/board/{slug}` | `list.php` | 글 목록 |
| `/board/{slug}/write` | `write.php` | 글쓰기 |
| `/board/{slug}/{id}` | `read.php` | 글 상세 |
| `/board/{slug}/{id}/edit` | `write.php` | 글 수정 |
| `/board/{slug}/settings` | `settings.php` | 게시판 설정 (관리자, 프론트 레이아웃) |

### 프론트 라우트 — 단축 URL (`/{slug}...`)

다른 뷰 파일과 매칭되지 않으면, DB에서 `rzx_boards.slug`를 조회하여 게시판으로 자동 매핑:

| URL 패턴 | 동작 |
|----------|------|
| `/{slug}` | 글 목록 |
| `/{slug}/write` | 글쓰기 |
| `/{slug}/{id}` | 글 상세 |
| `/{slug}/{id}/edit` | 글 수정 |

### 프론트 API (레이아웃 미적용)

| URL | 파일 | Method | 설명 |
|-----|------|--------|------|
| `/board/api/posts` | `api-posts.php` | POST | 게시글 CRUD + 추천 |
| `/board/api/comments` | `api-comments.php` | POST | 댓글 작성/삭제 |
| `/board/api/files` | `api-files.php` | GET/POST | 파일 다운로드/업로드/삭제 |
| `/board/api/og` | `api-og.php` | POST | URL → OG 메타데이터 (링크 프리뷰 카드용) |

### URL 자동 링크 + OG 카드 프리뷰

**공통 리소스:**
- `resources/css/board-content.css` — 본문+에디터 공통 스타일 (Tailwind preflight 덮어쓰기)
- `resources/js/board-autolink.js` — 자동 링크 + OG 카드 (base-header에서 전역 로드)
- `rzxlib/Core/Modules/OgFetcher.php` — OG/Twitter Card 메타데이터 추출

**동작:**
- 에디터: URL 입력 후 엔터/붙여넣기 → OG API 호출 → 카드형 프리뷰 삽입
- 본문: 단독 URL `<a>` → OG API 호출 → 카드로 교체, 문장 내 URL → `<a>` 링크 변환
- OG 카드: 썸네일+제목+설명+도메인, 가운데 정렬, 다크모드 지원

### LayoutManager 동작

`LayoutManager::render()`는 `$__pageFile`을 레이아웃(`base-header.php` + `base-footer.php`)으로 감싼다.

- **레이아웃 미적용**: `board/api/*`, `api/*`, `logout` 등
- **자체 레이아웃**: `login`, `register`, `kiosk/*` 등
- 페이지에서 `$__layout = false` 설정 시 레이아웃 미적용

---

## 3. DB 스키마

### `rzx_boards`

기본 테이블(017) + 확장 컬럼(018) 합산. 100개 이상의 컬럼. 그룹별 요약:

| 그룹 | 주요 컬럼 | 설명 |
|------|----------|------|
| **PK/기본** | id, slug(UNIQUE), title, category, description, is_active, sort_order | 기본 정보 |
| **SEO** | seo_keywords, seo_description, robots_tag | 검색엔진 최적화 |
| **레이아웃/스킨** | layout, skin, skin_config(JSON) | 레이아웃 + 스킨 + 설정 |
| **표시** | per_page, search_per_page, page_count, header/footer_content | 페이지네이션 + 상하단 HTML |
| **목록** | list_columns(JSON), sort_field, sort_direction, except_notice | 목록 표시/정렬 |
| **하단목록** | show_bottom_list, bottom_skip_old_days, bottom_skip_robot | 최적화 |
| **분류** | show_category, hide_categories, allow_uncategorized | 분류 표시 |
| **익명** | use_anonymous, anonymous_name, admin_anon_exclude | 익명 게시판 |
| **기능** | allow_comment, consultation, allow_secret, allow_public, allow_secret_default, use_trash, update_order_on_comment | 핵심 기능 토글 |
| **제한** | doc_length_limit, comment_length_limit, data_url_limit, unicode_abuse_block | 크기/문자 제한 |
| **글 보호** | protect_edit/delete_comment_count | 댓글 수 기반 수정/삭제 보호 |
| **댓글 보호** | protect_comment_edit/delete_reply | 대댓글 수 기반 보호 |
| **기간 제한** | restrict_edit/comment_days | 기간 기반 보호 |
| **관리자 보호** | admin_protect_delete/edit | 최고관리자 글 보호 |
| **댓글 처리** | comment_delete_message, comment_placeholder_type, use_edit_history | 삭제/수정 표시 |
| **통합** | merge_boards(JSON), merge_period, merge_notice | 통합 게시판 |
| **문서 추천** | use_history, use_vote, use_downvote, vote_same_ip, vote_cancel, vote_non_member | 추천/히스토리 |
| **문서 신고** | report_same_ip, report_cancel, report_notify | 신고 |
| **댓글 설정** | comment_count, comment_page_count, comment_max_depth, comment_default_page, comment_approval | 댓글 페이지/깊이 |
| **댓글 추천/신고** | comment_vote/downvote, comment_vote_*, comment_report_* | 댓글 추천/신고 |
| **에디터** | editor_use_default, editor_html/file/component/ext_component_perm | 에디터 권한 |
| **에디터(댓글)** | comment_editor_html/file/component/ext_component_perm | 댓글 에디터 |
| **파일** | file_use_default, file_image/video_default, file_download_groups | 파일 설정 |
| **피드** | feed_type, feed_include_merged, feed_description, feed_copyright | RSS 피드 |
| **권한** | perm_list/read/write/comment/manage | all/member/grade:*/admin_staff/admin |
| **기타** | admin_mail, created_at, updated_at | 발신메일/타임스탬프 |

### `rzx_board_categories`

| 컬럼 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| id | INT AUTO_INCREMENT | - | PK |
| board_id | INT | - | 게시판 FK |
| parent_id | INT | 0 | 부모 분류 (0=최상위, 트리 구조) |
| name | VARCHAR(100) | - | 분류명 (다국어 지원) |
| slug | VARCHAR(50) | NULL | URL 식별자 |
| description | TEXT | NULL | 설명 (다국어) |
| color | VARCHAR(7) | '' | 배경색 |
| font_color | VARCHAR(20) | '' | 폰트색 |
| allowed_groups | VARCHAR(200) | '' | 작성 허용 등급 (회원등급 slug) |
| is_expanded | TINYINT(1) | 0 | 기본 펼침 |
| is_default | TINYINT(1) | 0 | 기본 분류 |
| sort_order | INT | 0 | 정렬 순서 |
| is_active | TINYINT(1) | 1 | 활성화 |

### `rzx_board_posts`

주요 컬럼: `id`(PK), `board_id`, `category_id`, `user_id`(NULL=비회원), `title`, `content`(LONGTEXT), `password`(bcrypt), `is_notice`, `is_secret`, `is_anonymous`, `nick_name`, `view_count`, `comment_count`, `like_count`, `dislike_count`, `file_count`, `list_order`(BIGINT, 정렬), `update_order`(BIGINT, 끌올), `status`(published/draft/trash), `original_locale`, `source_locale`, `extra_vars`(JSON, 확장 변수 값), `ip_address`, `created_at`, `updated_at`

인덱스: `idx_board`, `idx_category`, `idx_user`, `idx_list_order(board_id, list_order)`, `idx_status(board_id, status)`

### `rzx_board_comments`

주요 컬럼: `id`(PK), `post_id`, `board_id`, `user_id`, `parent_id`(대댓글), `depth`, `content`, `password`, `is_secret`, `is_anonymous`, `nick_name`, `like_count`, `status`(published/deleted/trash), `ip_address`, `created_at`, `updated_at`

### `rzx_board_files`

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT AUTO_INCREMENT | PK |
| post_id | INT | 게시글 FK |
| board_id | INT | 게시판 FK |
| original_name | VARCHAR(300) | 원본 파일명 |
| stored_name | VARCHAR(300) | 저장 파일명 |
| file_path | VARCHAR(500) | 저장 경로 (`/storage/board/{board_id}/...`) |
| file_size | INT | 파일 크기 (bytes) |
| mime_type | VARCHAR(100) | MIME 타입 |
| download_count | INT | 다운로드 횟수 |
| created_at | DATETIME | 생성일 |

### `rzx_board_votes`

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT AUTO_INCREMENT | PK |
| post_id | INT | 게시글 FK |
| user_id | INT | 사용자 (NULL=비회원) |
| ip_address | VARCHAR(45) | IP |
| vote_type | ENUM('like','dislike') | 투표 유형 |
| created_at | DATETIME | 생성일 |

**UNIQUE**: `uniq_user_vote(post_id, user_id, vote_type)`

### `rzx_board_extra_vars`

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT AUTO_INCREMENT | PK |
| board_id | INT | 게시판 FK |
| var_name | VARCHAR(50) | 변수명 (게시판 내 UNIQUE) |
| var_type | VARCHAR(20) | 입력 타입 (15종) |
| title | VARCHAR(200) | 표시명 (다국어) |
| description | TEXT | 설명 (다국어) |
| options | TEXT | 선택 항목 (다국어) |
| default_value | VARCHAR(500) | 기본값 (다국어) |
| is_required | TINYINT(1) | 필수 여부 |
| is_searchable | TINYINT(1) | 검색 가능 |
| is_shown_in_list | TINYINT(1) | 목록 표시 |
| sort_order | INT | 정렬 순서 |
| is_active | TINYINT(1) | 활성화 |
| created_at | TIMESTAMP | 생성일 |

**var_type 15종**: `text`, `text_multilang`, `textarea`, `textarea_multilang`, `textarea_editor`, `number`, `select`, `checkbox`, `radio`, `date`, `email`, `url`, `tel`, `color`, `file`

**확장 변수 값 저장**: `rzx_board_posts.extra_vars` JSON 컬럼 (`{var_name: value, ...}`)
- 다국어: `rzx_translations`에 `board_post.{id}.extra_vars` JSON으로 언어별 저장

**ExtraVarRenderer 모듈** (`rzxlib/Core/Modules/ExtraVarRenderer.php`):
```php
// 전체 렌더링
ExtraVarRenderer::renderAll($extraVarDefs, $values, 'input');   // write 폼
ExtraVarRenderer::renderAll($extraVarDefs, $values, 'display'); // read 표시

// 개별 렌더링 (스킨 커스텀)
ExtraVarRenderer::renderInput($var, $value);    // 입력 폼
ExtraVarRenderer::renderDisplay($var, $value);  // 표시
```

### `rzx_board_admins`

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT AUTO_INCREMENT | PK |
| board_id | INT | 게시판 FK |
| user_id | CHAR(36) | 사용자 FK |
| perm_document | TINYINT(1) | 문서 관리 권한 |
| perm_comment | TINYINT(1) | 댓글 관리 권한 |
| perm_settings | TINYINT(1) | 설정 관리 권한 |
| created_at | TIMESTAMP | 생성일 |

**UNIQUE**: `uk_board_user(board_id, user_id)`

---

## 4. API 엔드포인트

### 관리자 API (`/admin/site/boards/api`)

| action | Method | 설명 |
|--------|--------|------|
| `create` | POST | 게시판 생성 (slug 중복/형식 검증) |
| `update` | POST | 게시판 수정 (list_columns JSON 지원) |
| `delete` | POST | 게시판 삭제 (게시글 있으면 거부) |
| `copy` | POST | 게시판 복사 (설정 + 카테고리, slug에 `_copy` 접미) |
| `search_users` | GET | 회원+스태프 검색 (email/name LIKE, 상위 10건) |
| `category_get` | GET | 분류 단건 조회 |
| `category_add` | POST | 분류 추가 (parent_id, slug 자동생성, color, font_color, allowed_groups, is_expanded, is_default) |
| `category_update` | POST | 분류 수정 |
| `category_delete` | POST | 분류 삭제 (하위 분류 함께 삭제) |
| `category_reorder` | POST(JSON) | 분류 순서+부모 일괄 변경 |
| `extra_var_get` | GET | 확장변수 단건 조회 |
| `extra_var_add` | POST | 확장변수 추가 |
| `extra_var_update` | POST | 확장변수 수정 |
| `extra_var_delete` | POST | 확장변수 삭제 |
| `extra_var_reorder` | POST(JSON) | 확장변수 순서 일괄 변경 |
| `board_admin_add` | POST | 모듈 관리자 추가 (사용자 검색 → 중복 체크 → INSERT) |
| `board_admin_delete` | POST | 모듈 관리자 삭제 |

### 프론트 게시글 API (`/board/api/posts`)

| action | Method | 설명 |
|--------|--------|------|
| `create` | POST | 게시글 작성 (파일 업로드 포함, 익명번호 자동부여, original/source_locale) |
| `update` | POST | 게시글 수정 (소유자/관리자만, 원본 언어→직접 수정, 다른 언어→rzx_translations에 번역 저장) |
| `delete` | POST | 게시글 삭제 (use_trash시 soft delete) |
| `like` | POST | 추천 (중복/자추 방지, like_count 증가) |
| `dislike` | POST | 비추천 (중복/자추 방지, dislike_count 증가) |

### 프론트 댓글 API (`/board/api/comments`)

| action | Method | 설명 |
|--------|--------|------|
| `create` | POST | 댓글 작성 (대댓글 depth 계산, 익명번호 자동부여, comment_count/update_order 갱신) |
| `delete` | POST | 댓글 삭제 (대댓글 존재 시 status='deleted', 없으면 물리 삭제) |
| `like` | POST | 댓글 추천/비추천 (comment_id, type=like/dislike) |

### 프론트 파일 API (`/board/api/files`)

| action | Method | 설명 |
|--------|--------|------|
| `download` | GET | 파일 다운로드 (download_count 증가, Content-Disposition) |
| `upload_image` | POST | 에디터 본문 이미지 삽입 (`/storage/board/images/YYYY/mm/`) |
| `delete` | POST | 첨부파일 삭제 (물리파일 + DB, file_count 갱신) |

---

## 5. 다국어 시스템

### 게시글 다국어 (v1.10.0)

- `original_locale`: 최초 작성 언어 (변경 불가)
- `board_posts` 테이블: 원본 언어의 title, content, extra_vars 저장
- `rzx_translations` 테이블: 다른 언어의 번역 저장 (`board_post.{id}.title`, `board_post.{id}.content`, `board_post.{id}.extra_vars`)

**저장 규칙:**
- 원본 언어로 수정 → `board_posts` 직접 수정
- 다른 언어로 수정 → `rzx_translations`에 번역 저장 (원본 유지)
- 확장 변수도 동일 규칙 적용 (언어별 독립 값 가능)

**읽기 폴백 체인:**
1. 현재 로케일의 `rzx_translations` 번역 있으면 → 번역 표시
2. 없으면 영어(en) 번역 시도
3. 영어도 없으면 → 원본(`board_posts`) 표시
4. 확장 변수도 동일 폴백 (현재 로케일 → en → 원본)

**목록 폴백:** 게시글 목록에서도 제목을 현재 로케일 → en → 원본 순으로 표시

**수정 폼:** 다른 언어로 수정 시 안내 배너 표시 ("현재 XX 언어로 번역을 편집합니다. 원본은 유지됩니다.")
- 원문 언어 안내는 작성자/관리자에게만 표시

### 게시판 설정 다국어

`_init.php`에서 `db_trans('board.{id}.{field}')` 으로 폴백:
- `title`, `description`, `seo_keywords`, `seo_description`, `header_content`, `footer_content`

### 다국어 입력 컴포넌트

```php
// 정적 폼 (langKey 고정)
rzx_multilang_input('title', $value, 'board.1.title', ['required' => true]);
rzx_multilang_input('desc', $value, 'board.1.desc', ['type' => 'textarea', 'modal_type' => 'editor']);

// 동적 모달 (JS에서 langKey 결정)
// → 직접 HTML + RZX_MULTILANG_SVG + getEvLangKey() 방식
```

---

## 6. 권한 시스템

### 접근 권한 레벨

| 값 | 설명 |
|----|------|
| `all` | 전체 공개 (비회원 포함) |
| `member` | 로그인 회원 |
| `grade:{slug}` | 특정 회원등급 (DB `rzx_member_grades`에서 동적 로드) |
| `admin_staff` | 관리자 + 스태프 |
| `admin` | 관리자만 |

5개 권한 필드: `perm_list`, `perm_read`, `perm_write`, `perm_comment`, `perm_manage`

### 권한 확인 (`_init.php`의 `boardCheckPerm()`)

```php
function boardCheckPerm($board, $permName, $currentUser) {
    // all → true, member → 로그인 여부, admin → session admin_id,
    // admin_staff → admin 또는 is_staff, grade:* → 추후 구현
}
```

### 모듈 관리자 (`rzx_board_admins`)

- 게시판별 관리자 지정 (문서/댓글/설정 권한 세분화)
- `user-search.php` 컴포넌트로 사용자 검색 (이메일/이름, `Encryption::decrypt()` 복호화)

---

## 7. 스킨 시스템

### skin.json 포맷

```json
{
  "title": { "ko": "스킨명", "en": "Skin Name" },
  "description": { "ko": "설명" },
  "version": "1.0.0",
  "author": { "name": "작성자", "url": "https://..." },
  "vars": [
    {
      "name": "변수명",
      "type": "text|textarea|checkbox|select|color|image|number",
      "multilang": true,
      "title": { "ko": "표시명" },
      "description": { "ko": "설명" },
      "options": [{ "value": "v", "label": { "ko": "라벨" } }],
      "default": "기본값",
      "min": 1, "max": 6
    }
  ]
}
```

### SkinConfigRenderer

- `skins/{name}/board/skin.json`을 파싱하여 설정 폼 자동 생성
- `getMeta()`: 메타 정보 (title, description, version, author)
- `renderForm()`: vars 기반 HTML 폼 출력
- `hasVars()`: vars 존재 여부
- `multilang: true` → 다국어 지구본 버튼 자동 표시
- 저장: `rzx_boards.skin_config` (JSON)

### 레이아웃 시스템

- `skins/{name}/layouts/layout.json`: 레이아웃 메타 (title, description, version, author)
- 저장: `rzx_boards.layout`
- 관리자에서 카드형 UI로 선택

---

## 8. 프론트 페이지 동작

### 공통 초기화 (`_init.php`)

모든 프론트 페이지에서 include. 제공 변수:
- `$board`, `$boardId`, `$boardUrl`, `$pdo`, `$prefix`
- `$currentUser` (세션 기반, 이름 복호화)
- `$categories`, `$categoryTree`, `$catMap`
- `$listColumns`, `$skinConfig`, `$useVote`, `$useDownvote`
- `$commentCount`, `$commentPageCount`, `$commentMaxDepth`
- `boardCheckPerm()` 함수

### 목록 (`list.php`)

- 공지 별도 조회 (`except_notice` 설정 시)
- 상담 모드: 본인 글 + 공지만 표시 (비로그인은 공지만)
- 검색: title / content / nick_name / 전체 (title+content)
- 카테고리 필터: 라운드 버튼 UI
- 정렬: `sort_field` + `sort_direction` (허용: created_at, updated_at, view_count, vote_count)
- list_columns 기반 동적 테이블 헤더 (no, title, category, nick_name, created_at, view_count, vote_count, comment_count)

### 상세 (`read.php`)

- 비밀글: 비밀번호 입력 폼 (작성자/관리자는 바로 열람)
- 상담 모드: 본인 글만 열람 가능
- 조회수 자동 증가
- 다국어 폴백 (source_locale → en → original_locale)
- 댓글 목록 (created_at ASC)
- 추천/비추천 (중복 방지, 자추 방지)

### 글쓰기/수정 (`write.php`)

- 수정 시 `source_locale != currentLocale` 경고 배너
- 비회원: 닉네임 + 비밀번호 필수
- 분류 선택 (allow_uncategorized 설정)
- 공지/비밀글 옵션 (관리자만 공지 가능)
- 파일 첨부 (multipart/form-data)
- 수정 시 기존 첨부파일 표시

### 익명 게시판

- 닉네임 패턴: `{anonymous_name}{번호}` (예: 익명1, 익명2)
- 같은 user_id는 게시판/게시글 내에서 동일 번호 유지
- 관리자 제외 옵션 (`admin_anon_exclude`)
