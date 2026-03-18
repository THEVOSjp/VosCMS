# RezlyX 게시판 시스템

> **최종 업데이트**: 2026-03-18 | **버전**: 1.6.1

## 관리자 탭 구조

| 탭 | 파일 | 설명 |
|---|---|---|
| 기본 설정 | `boards-edit-basic.php` | 기본정보 + SEO + 레이아웃 + 스킨선택 + 표시 + 목록 + 고급 (접기/펼치기) |
| 분류 관리 | `boards-edit-categories.php` | 트리 구조, 드래그 정렬, 편집 모달, 분류 설정 |
| 확장 변수 | `boards-edit-extra_vars.php` | 커스텀 필드 정의 (15개 타입), 다국어 지원 |
| 권한 설정 | `boards-edit-permissions.php` | 접근 권한 + 모듈 관리자 (회원 검색) |
| 추가 설정 | `boards-edit-addition.php` | 통합게시판, 문서, 댓글, 에디터, 파일, 피드 |
| 스킨 | `boards-edit-skin.php` | 선택된 스킨 정보 + skin.json 동적 설정 폼 |

## 공통 컴포넌트

```
resources/views/admin/components/board/
├── section-basic.php          # 기본 정보
├── section-seo.php            # SEO 설정
├── section-layout-select.php  # 레이아웃 선택 (카드형)
├── section-skin-select.php    # 스킨 선택 (카드형)
├── section-display.php        # 표시 설정
├── section-list.php           # 목록 설정
├── section-advanced.php       # 고급 설정
└── section-js.php             # 공통 JS (접기/펼치기, 컬럼 관리)

resources/views/admin/components/
├── user-search.php            # 공용 사용자 검색 (자동완성, 암호화 복호화)
├── multilang-modal.php        # 다국어 입력 모달 (text + editor)
└── language-selector.php      # 공용 언어 선택기
```

- `$board` 존재 → 수정 모드 (다국어 버튼 포함)
- `$board = null` → 생성 모드 (기본 input)
- `$_collapsed` → 섹션 초기 접힘 상태

## DB 스키마

### `rzx_boards` (100+ 컬럼)

| 그룹 | 주요 컬럼 | 설명 |
|---|---|---|
| **기본** | slug, title, category, description, is_active | 기본 정보 |
| **SEO** | seo_keywords, seo_description, robots_tag | 검색엔진 |
| **레이아웃/스킨** | layout, skin, skin_config(JSON) | 레이아웃 + 스킨 + 설정 |
| **표시** | per_page, search_per_page, page_count, header/footer_content | 표시 |
| **목록** | list_columns(JSON), sort_field, sort_direction, except_notice | 목록 |
| **하단목록** | show_bottom_list, bottom_skip_old_days, bottom_skip_robot | 최적화 |
| **분류** | show_category, hide_categories, allow_uncategorized | 분류 |
| **익명** | use_anonymous, anonymous_name, admin_anon_exclude | 익명 |
| **기능** | allow_comment, consultation, allow_secret, use_trash | 기능 |
| **제한** | doc_length_limit, comment_length_limit, data_url_limit, unicode_abuse_block | 크기/문자 |
| **글 보호** | protect_edit/delete_comment_count | 댓글수 보호 |
| **댓글 보호** | protect_comment_edit/delete_reply | 대댓글수 보호 |
| **기간 제한** | restrict_edit/comment_days | 기간 보호 |
| **관리자 보호** | admin_protect_delete/edit | 최고관리자 보호 |
| **상태** | allow_public, allow_secret_default | 공개/비밀 |
| **댓글 처리** | comment_delete_message, comment_placeholder_type, use_edit_history | 삭제/수정 |
| **통합 게시판** | merge_boards(JSON), merge_period, merge_notice | 통합 |
| **문서 추천** | use_history, use_vote, use_downvote, vote_same_ip, vote_cancel, vote_non_member | 추천/히스토리 |
| **문서 신고** | report_same_ip, report_cancel, report_notify | 신고 |
| **댓글 설정** | comment_count, comment_page_count, comment_max_depth, comment_default_page, comment_approval | 댓글 |
| **댓글 추천/신고** | comment_vote/downvote, comment_vote_*, comment_report_* | 댓글 추천/신고 |
| **에디터** | editor_use_default, editor_html/file/component/ext_component_perm | 에디터 권한 |
| **에디터(댓글)** | comment_editor_html/file/component/ext_component_perm | 댓글 에디터 |
| **파일** | file_use_default, file_image/video_default, file_download_groups | 파일 |
| **피드** | feed_type, feed_include_merged, feed_description, feed_copyright | RSS 피드 |
| **권한** | perm_list/read/write/comment/manage | all/member/grade:*/admin_staff/admin |
| **기타** | admin_mail, sort_order | 발신메일/정렬 |

### `rzx_board_categories`

| 컬럼 | 설명 |
|---|---|
| id, board_id, parent_id | PK + 게시판 + 부모 (트리 구조) |
| name, slug, description | 기본 정보 (다국어 지원) |
| color, font_color | 배경색, 폰트색 |
| allowed_groups | 작성 허용 그룹 (회원등급 DB에서 동적 로드) |
| is_expanded, is_default | 펼침, 기본 분류 |

### `rzx_board_extra_vars`

| 컬럼 | 설명 |
|---|---|
| id, board_id, var_name(UNIQUE) | PK + 게시판 + 변수명 |
| var_type | 15개: text, text_multilang, textarea, textarea_multilang, textarea_editor, number, select, checkbox, radio, date, email, url, tel, color, file |
| title, description | 표시 이름, 설명 (다국어 지원) |
| options, default_value | 선택 항목, 기본값 (다국어 지원) |
| is_required, is_searchable, is_shown_in_list | 필수/검색/목록표시 |

### `rzx_board_admins`

| 컬럼 | 설명 |
|---|---|
| id, board_id, user_id | PK + 게시판 + 사용자 |
| perm_document, perm_comment, perm_settings | 문서/댓글/설정 관리 권한 |

### `rzx_board_posts` / `rzx_board_comments` / `rzx_board_files` / `rzx_board_votes`

기존 테이블 (게시글, 댓글, 첨부파일, 추천)

## 레이아웃 시스템

```
skins/{name}/layouts/layout.json    # 레이아웃 메타 정보
skins/{name}/layouts/main.php       # 레이아웃 템플릿
```

저장: `rzx_boards.layout`

## 스킨 시스템

### 구조
```
skins/{name}/board/skin.json        # 스킨 메타 + 설정 변수 정의
```

### skin.json 포맷
```json
{
  "title": { "ko": "스킨명" },
  "version": "1.0.0",
  "vars": [
    {
      "name": "변수명",
      "type": "text|textarea|checkbox|select|color|image|number",
      "multilang": true,
      "title": { "ko": "표시명" },
      "default": "기본값"
    }
  ]
}
```

- `"multilang": true` → 다국어 지구본 버튼 자동 표시
- `SkinConfigRenderer` → skin.json 읽어 설정 폼 자동 생성
- 저장: `rzx_boards.skin_config` (JSON)

## 권한 시스템

### 접근 권한 레벨
| 값 | 설명 |
|---|---|
| `all` | 전체 공개 |
| `member` | 회원만 |
| `grade:{slug}` | 특정 회원등급만 (DB 동적 로드) |
| `admin_staff` | 관리자와 스태프만 |
| `admin` | 관리자만 |

### 모듈 관리자
- 공용 사용자 검색 컴포넌트 (`user-search.php`) 사용
- 회원 이름 암호화 → `Encryption::decrypt()` 복호화
- 스태프 API의 `search_members` 활용 (전체 로드 → 클라이언트 필터링)
- 등록된 관리자에게 알림 메일 발송 (발신 주소: 고급 설정의 발신 메일)

## 다국어 입력 컴포넌트

```php
// 정적 폼 (langKey 고정)
rzx_multilang_input('title', $value, 'board.1.title', ['required' => true]);
rzx_multilang_input('desc', $value, 'board.1.desc', ['type' => 'textarea', 'modal_type' => 'editor']);

// 동적 모달 (langKey가 JS에서 결정)
// → 직접 HTML + RZX_MULTILANG_SVG + getEvLangKey() 방식
```

## API 엔드포인트

**URL**: `/admin/site/boards/api` (GET + POST)

| action | method | 설명 |
|---|---|---|
| create / update / delete | POST | 게시판 CRUD |
| search_users | GET | 회원+스태프 검색 (암호화 복호화) |
| category_get | GET | 분류 조회 |
| category_add / update / delete | POST | 분류 CUD |
| category_reorder | POST(JSON) | 분류 순서+부모 변경 |
| extra_var_get | GET | 확장변수 조회 |
| extra_var_add / update / delete | POST | 확장변수 CUD |
| extra_var_reorder | POST(JSON) | 확장변수 순서 변경 |
| board_admin_add / delete | POST | 모듈 관리자 추가/삭제 |
