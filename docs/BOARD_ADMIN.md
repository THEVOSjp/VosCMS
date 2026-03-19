# RezlyX 게시판 관리자 페이지 상세

> **버전**: 1.10.0 | **최종 업데이트**: 2026-03-20
> DB 스키마/API/프론트 동작은 [BOARD_SYSTEM.md](BOARD_SYSTEM.md) 참조

## 1. 탭 구조 (`boards-edit.php`)

탭 컨테이너가 `boards-edit.php`에서 `$currentTab`에 따라 해당 파일을 include.

| 탭 | 파일 | 설명 |
|----|------|------|
| `basic` | `boards-edit-basic.php` | 기본정보 + SEO + 레이아웃 + 스킨선택 + 표시 + 목록 + 고급 (접기/펼치기) |
| `categories` | `boards-edit-categories.php` | 트리 구조, 드래그 정렬, 편집 모달, 분류 설정 |
| `extra_vars` | `boards-edit-extra_vars.php` | 커스텀 필드 15개 타입, 다국어, CRUD |
| `permissions` | `boards-edit-permissions.php` | 접근 권한 + 모듈 관리자 |
| `addition` | `boards-edit-addition.php` | 통합게시판, 문서, 댓글, 에디터, 파일, 피드 |
| `skin` | `boards-edit-skin.php` | 스킨 정보 + skin.json 동적 설정 폼 |

탭 네비게이션은 URL `?tab=` 파라미터로 전환 (SPA가 아닌 서버사이드).

### 공통 동작

- `$board` 존재 → 수정 모드 (다국어 버튼 포함)
- `$board = null` → 생성 모드 (기본 input)
- `$_collapsed` → 섹션 초기 접힘 상태
- `toggleSection(btn)` → 접기/펼치기 애니메이션 (head에 인라인 정의)
- `multilang-modal.php` → 모든 탭에서 공유 (body 하단 include)

---

## 2. 기본 설정 탭 (`boards-edit-basic.php`)

공통 컴포넌트를 순서대로 include:

```php
include "{$_componentDir}/section-basic.php";       // 펼침
include "{$_componentDir}/section-seo.php";          // 펼침
include "{$_componentDir}/section-layout-select.php";// 펼침
include "{$_componentDir}/section-skin-select.php";  // 펼침
include "{$_componentDir}/section-display.php";      // 펼침
include "{$_componentDir}/section-list.php";         // 접힘
include "{$_componentDir}/section-advanced.php";     // 접힘
```

### 2.1 기본 정보 (`section-basic.php`)

| 필드 | 타입 | 다국어 | 설명 |
|------|------|--------|------|
| slug | text | X | URL 식별자 (영소문자/숫자/하이픈/밑줄) |
| title | text | O | 게시판 제목 |
| category | text | X | 게시판 분류 |
| description | textarea | O | 설명 |
| is_active | toggle | X | 활성화 |

### 2.2 SEO 설정 (`section-seo.php`)

| 필드 | 타입 | 다국어 | 설명 |
|------|------|--------|------|
| seo_keywords | text | O | 메타 키워드 |
| seo_description | textarea | O | 메타 설명 |
| robots_tag | select | X | all / noindex / nofollow / noindex,nofollow |

### 2.3 레이아웃 선택 (`section-layout-select.php`)

- `skins/*/layouts/layout.json` 스캔하여 카드형 UI 표시
- 각 레이아웃의 title, description, version 표시
- 라디오 버튼으로 선택 → `rzx_boards.layout`에 저장

### 2.4 스킨 선택 (`section-skin-select.php`)

- `skins/*/board/skin.json` 스캔하여 카드형 UI 표시
- 각 스킨의 title, description, version, author 표시
- 라디오 버튼으로 선택 → `rzx_boards.skin`에 저장

### 2.5 표시 설정 (`section-display.php`)

| 필드 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| per_page | number | 20 | 페이지당 글 수 |
| search_per_page | number | 20 | 검색 시 페이지당 글 수 |
| page_count | number | 10 | 페이지 링크 수 |
| header_content | textarea(editor) | - | 상단 HTML 콘텐츠 (다국어) |
| footer_content | textarea(editor) | - | 하단 HTML 콘텐츠 (다국어) |

### 2.6 목록 설정 (`section-list.php`) — 기본 접힘

| 필드 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| list_columns | 체크박스 그룹 | [no, title, nick_name, created_at, view_count] | 표시 컬럼 선택 |
| sort_field | select | created_at | 정렬 기준 필드 |
| sort_direction | select | DESC | 정렬 방향 |
| except_notice | toggle | 1 | 공지사항 별도 표시 |
| show_bottom_list | toggle | 1 | 하단 글 목록 표시 |
| bottom_skip_old_days | number | 0 | N일 이전 글 제외 |
| bottom_skip_robot | toggle | 1 | 봇 접근 시 미표시 |

**list_columns 선택 가능 항목**: no, title, category, nick_name, created_at, view_count, vote_count, comment_count

### 2.7 고급 설정 (`section-advanced.php` → `boards-edit-basic-advanced.php`)

기본 접힘 상태. 하위 내용이 많아 세부 구분:

#### 상담 / 익명

| 필드 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| consultation | toggle | 0 | 상담 모드 (본인 글 + 공지만 표시) |
| use_anonymous | toggle | 0 | 익명 게시판 |
| admin_anon_exclude | checkbox | 0 | 관리자 익명 제외 |
| anonymous_name | text | 'anonymous' | 익명 닉네임 (다국어) |

#### 크기/문자 제한

| 필드 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| doc_length_limit | number | 1024 | 본문 길이 제한 (KB) |
| comment_length_limit | number | 128 | 댓글 길이 제한 (KB) |
| data_url_limit | number | 64 | Data URL 제한 (KB) |

#### 기타 토글

| 필드 | 기본값 | 설명 |
|------|--------|------|
| use_edit_history | 0 | 게시글 수정 내역 표시 |
| update_order_on_comment | 0 | 댓글 작성 시 글 끌올 |
| use_trash | 1 | 삭제 시 휴지통 사용 |
| unicode_abuse_block | 1 | 유니코드 특수문자 오남용 금지 |

#### 글 보호 기능

| 필드 | 조건 | 설명 |
|------|------|------|
| protect_edit_comment_count | 댓글 N개 이상 | 글 수정 금지 |
| protect_delete_comment_count | 댓글 N개 이상 | 글 삭제 금지 |

#### 댓글 보호 기능

| 필드 | 조건 | 설명 |
|------|------|------|
| protect_comment_edit_reply | 대댓글 N개 이상 | 댓글 수정 금지 |
| protect_comment_delete_reply | 대댓글 N개 이상 | 댓글 삭제 금지 |

#### 기간 제한

| 필드 | 조건 | 설명 |
|------|------|------|
| restrict_edit_days | N일 이후 | 수정 금지 |
| restrict_comment_days | N일 이후 | 댓글 금지 |

#### 관리자 보호

| 필드 | 기본값 | 설명 |
|------|--------|------|
| admin_protect_delete | 1 | 최고관리자 글 삭제 보호 |
| admin_protect_edit | 1 | 최고관리자 글 수정 보호 |

#### 공개/비밀글

| 필드 | 기본값 | 설명 |
|------|--------|------|
| allow_public | 1 | 공개글 허용 |
| allow_secret | 0 | 비밀글 허용 |
| allow_secret_default | 0 | 비밀글을 기본으로 설정 |

#### 댓글 삭제 처리

| 필드 | 기본값 | 설명 |
|------|--------|------|
| comment_delete_message | 'no' | 삭제 메시지 표시 방식 |
| comment_placeholder_type | 'none' | 삭제된 댓글 자리 표시 타입 |

#### 모듈 관리자 + 발신 메일 (`boards-edit-basic-admin.php`)

고급 설정 하단에 include됨.

| 기능 | 설명 |
|------|------|
| 관리자 추가 | 이메일/이름 입력 → `board_admin_add` API → 목록에 추가 |
| 관리자 삭제 | 목록에서 선택 → `board_admin_delete` API |
| 권한 범위 | 문서 관리(perm_document), 댓글 관리(perm_comment), 설정 관리(perm_settings) |
| 발신 메일 | `admin_mail` 필드 (다국어 미지원) |

---

## 3. 분류 관리 탭 (`boards-edit-categories.php`)

### 트리 구조

- `parent_id` 기반 재귀 트리 (`buildCategoryTree()`)
- SortableJS로 드래그 정렬 (순서 + 부모 변경)
- `category_reorder` API로 일괄 저장

### 분류 편집 모달

추가/수정 시 모달 팝업:

| 필드 | 타입 | 설명 |
|------|------|------|
| name | text | 분류명 (필수, 다국어) |
| slug | text | URL 식별자 (자동 생성) |
| description | textarea | 설명 (다국어) |
| color | color | 배경색 |
| font_color | text | 폰트색 |
| allowed_groups | multi-select | 작성 허용 등급 (DB에서 동적 로드) |
| is_expanded | checkbox | 기본 펼침 |
| is_default | checkbox | 기본 분류 |

### 분류 설정

분류 트리 아래에 게시판 레벨 분류 설정:

| 필드 | 기본값 | 설명 |
|------|--------|------|
| show_category | 0 | 목록에 분류 표시 |
| hide_categories | 0 | 분류 필터 UI 숨김 |
| allow_uncategorized | 1 | 미분류 허용 |

---

## 4. 확장 변수 탭 (`boards-edit-extra_vars.php`)

### 변수 목록

- 테이블 형태로 표시 (변수명, 타입, 제목, 필수, 검색, 목록표시)
- SortableJS 드래그 정렬 → `extra_var_reorder` API
- 추가/수정 버튼 → 모달 팝업

### 15개 입력 타입

| 타입 | 설명 | 다국어 |
|------|------|--------|
| `text` | 단일행 텍스트 | X |
| `text_multilang` | 단일행 텍스트 (다국어) | O |
| `textarea` | 여러줄 텍스트 | X |
| `textarea_multilang` | 여러줄 텍스트 (다국어) | O |
| `textarea_editor` | 에디터 (HTML) | X |
| `number` | 숫자 | X |
| `select` | 드롭다운 선택 | O (options) |
| `checkbox` | 체크박스 그룹 | O (options) |
| `radio` | 라디오 그룹 | O (options) |
| `date` | 날짜 선택 | X |
| `email` | 이메일 | X |
| `url` | URL | X |
| `tel` | 전화번호 | X |
| `color` | 색상 선택 | X |
| `file` | 파일 업로드 | X |

### 모달 필드

| 필드 | 설명 |
|------|------|
| var_name | 변수명 (영소문자/숫자/밑줄, 수정 불가) |
| var_type | 입력 타입 (15종) |
| title | 표시명 (다국어 지원) |
| description | 설명 (다국어 지원) |
| options | 선택 항목 (select/checkbox/radio용, 다국어) |
| default_value | 기본값 (다국어 지원) |
| is_required | 필수 여부 |
| is_searchable | 검색 가능 |
| is_shown_in_list | 목록에 표시 |

---

## 5. 권한 설정 탭 (`boards-edit-permissions.php`)

### 접근 권한

5개 권한 필드를 드롭다운으로 설정:

| 필드 | 기본값 | 설명 |
|------|--------|------|
| perm_list | all | 목록 보기 |
| perm_read | all | 글 읽기 |
| perm_write | member | 글쓰기 |
| perm_comment | member | 댓글 작성 |
| perm_manage | admin | 관리 |

**선택 옵션**: all → member → grade:{slug} (등급별) → admin_staff → admin
- 회원등급은 `rzx_member_grades` 테이블에서 동적 로드

### 모듈 관리자

권한 탭 하단에 `boards-edit-basic-admin.php`와 동일한 UI 표시:
- 등록된 관리자 목록 (이름 암호화 → `Encryption::decrypt()` 복호화 표시)
- 관리자 추가/삭제
- 권한 범위: 문서/댓글/설정

---

## 6. 추가 설정 탭 (`boards-edit-addition.php`)

6개 섹션, 모두 접기/펼치기 가능:

### 6.1 통합 게시판

| 필드 | 타입 | 설명 |
|------|------|------|
| merge_boards | multi-select | 통합할 다른 게시판 선택 (JSON 배열) |
| merge_period | number | 통합 기간 (일, 0=무제한) |
| merge_notice | toggle | 통합 게시판에 공지 포함 |

### 6.2 문서 (추천/비추천/히스토리/신고)

| 필드 | 기본값 | 설명 |
|------|--------|------|
| use_history | 'none' | 히스토리 사용 (none/use) |
| use_vote | 'use' | 추천 사용 (none/use) |
| use_downvote | 'use' | 비추천 사용 (none/use) |
| vote_same_ip | 0 | 같은 IP 추천 허용 |
| vote_cancel | 0 | 추천 취소 허용 |
| vote_non_member | 0 | 비회원 추천 허용 |
| report_same_ip | 0 | 같은 IP 신고 허용 |
| report_cancel | 0 | 신고 취소 |
| report_notify | '' | 신고 알림 대상 |

### 6.3 댓글

| 필드 | 기본값 | 설명 |
|------|--------|------|
| comment_count | 50 | 페이지당 댓글 수 |
| comment_page_count | 10 | 댓글 페이지 링크 수 |
| comment_max_depth | 0 | 대댓글 최대 깊이 (0=무제한) |
| comment_default_page | 'last' | 댓글 기본 페이지 (first/last) |
| comment_approval | 0 | 댓글 승인제 |

댓글 추천/비추천/신고도 문서와 동일한 구조:
- `comment_vote`, `comment_downvote`, `comment_vote_same_ip`, `comment_vote_cancel`, `comment_vote_non_member`
- `comment_report_same_ip`, `comment_report_cancel`, `comment_report_notify`

### 6.4 에디터

| 필드 | 기본값 | 설명 |
|------|--------|------|
| editor_use_default | 1 | 기본 에디터 설정 사용 |
| editor_html_perm | '' | HTML 사용 권한 |
| editor_file_perm | '' | 파일 업로드 권한 |
| editor_component_perm | '' | 컴포넌트 사용 권한 |
| editor_ext_component_perm | '' | 확장 컴포넌트 권한 |

댓글 에디터도 동일한 구조:
- `comment_editor_html_perm`, `comment_editor_file_perm`, `comment_editor_component_perm`, `comment_editor_ext_component_perm`

### 6.5 파일

| 필드 | 기본값 | 설명 |
|------|--------|------|
| file_use_default | 1 | 기본 파일 설정 사용 |
| file_image_default | 1 | 이미지 기본 표시 |
| file_video_default | 1 | 비디오 기본 표시 |
| file_download_groups | '' | 다운로드 허용 그룹 |

### 6.6 피드 (RSS)

| 필드 | 기본값 | 설명 |
|------|--------|------|
| feed_type | 'none' | 피드 유형 (none/rss2/atom) |
| feed_include_merged | 1 | 통합 게시판 포함 |
| feed_description | NULL | 피드 설명 (다국어) |
| feed_copyright | NULL | 피드 저작권 (다국어) |

---

## 7. 스킨 탭 (`boards-edit-skin.php`)

### 스킨 정보

선택된 스킨의 메타 정보 카드:
- 제목, 설명, 버전, 작성자
- 스킨 식별자 배지

### 스킨 설정 (skin.json vars)

`SkinConfigRenderer`가 `skin.json`의 `vars` 배열을 읽어 설정 폼을 자동 생성:

| var type | 렌더링 |
|----------|--------|
| text | 텍스트 입력 |
| textarea | 여러줄 입력 |
| checkbox | 토글 스위치 |
| select | 드롭다운 (options 필요) |
| color | 색상 선택기 |
| image | 이미지 업로드 |
| number | 숫자 입력 (min/max 지원) |

- `multilang: true` 지정 시 → 다국어 지구본 버튼 자동 표시
- 저장: `rzx_boards.skin_config` (JSON)
- action: `update_skin` → 관리자 API

---

## 8. 공통 컴포넌트

### 게시판 전용 (`admin/components/board/`)

| 파일 | 설명 |
|------|------|
| `section-basic.php` | 기본 정보 (slug, title, category, description, is_active) |
| `section-seo.php` | SEO (keywords, description, robots) |
| `section-layout-select.php` | 레이아웃 카드 선택 UI |
| `section-skin-select.php` | 스킨 카드 선택 UI |
| `section-display.php` | 표시 설정 (per_page, header/footer) |
| `section-list.php` | 목록 설정 (columns, sort, bottom_list) |
| `section-advanced.php` | 고급 설정 래퍼 → `boards-edit-basic-advanced.php` include |
| `section-js.php` | 공통 JS (접기/펼치기, 컬럼 관리, 폼 제출) |

### 공용 (`admin/components/`)

| 파일 | 설명 |
|------|------|
| `user-search.php` | 사용자 검색 자동완성 (이메일/이름, 암호화 복호화) |
| `multilang-modal.php` | 다국어 입력 모달 (text + editor 모드) |
| `language-selector.php` | 공용 언어 선택기 |
| `settings-header.php` | 설정 페이지 공통 헤더 (아이콘, 제목, 설명, 액션 버튼) |

---

## 9. 폼 제출 흐름

### 기본 설정 탭

1. `boardEditForm` 제출 → `/admin/site/boards/api` POST
2. action = `update`, board_id 포함
3. 각 컴포넌트의 필드가 하나의 form에 포함
4. list_columns는 JSON 문자열로 전달
5. 성공 시 `saveStatus` 텍스트 표시

### 분류/확장변수

- 개별 API 호출 (AJAX)
- 추가/수정: 모달 → API → 페이지 새로고침 또는 DOM 갱신
- 순서 변경: 드래그 종료 시 자동 저장 (`_reorder` API)

### 권한/추가설정

- 개별 form으로 감싸서 AJAX 제출
- 통합 게시판의 merge_boards는 multi-select → JSON 배열 변환

### 스킨

- `boardSkinForm` 제출 → action = `update_skin`
- skin_config를 JSON으로 직렬화하여 전달
