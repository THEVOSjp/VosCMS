# RezlyX 게시판 시스템 설계 문서

> **최종 업데이트**: 2026-03-17 | **상태**: Phase 1~4 구현 완료 | **마이그레이션**: 017

## 개요

Rhymix 게시판 모듈(`modules/board/`)을 분석하여 RezlyX에 맞게 재설계한 게시판 시스템.

---

## 1. Rhymix 게시판 구조 분석

### 파일 구조 (MVC 패턴)

```
modules/board/
├── board.class.php          # 기본 클래스 (검색옵션, 정렬옵션, 기본값)
├── board.admin.view.php     # 관리자 뷰 (7개 메서드)
├── board.admin.controller.php # 관리자 컨트롤러 (Insert/Update/Delete/Copy)
├── board.admin.model.php    # 관리자 모델 (SimpleSetup)
├── board.model.php          # 모델 (DEFAULT_MODULE_CONFIG, ListConfig)
├── board.view.php           # 프론트 뷰 (목록/상세/글쓰기)
├── board.controller.php     # 프론트 컨트롤러 (글/댓글 CRUD, 추천, 비밀번호)
├── board.api.php            # API
├── board.mobile.php         # 모바일 뷰
├── conf/module.xml          # 권한 7개 + 액션 30개 정의
├── tpl/                     # 관리자 템플릿 (설정 폼 HTML)
├── skins/                   # 프론트 스킨
├── m.skins/                 # 모바일 스킨
├── queries/                 # SQL 쿼리 XML
└── lang/                    # 10개 언어
```

### 관리자 뷰 메서드 (7개 = 탭 구조)

| 메서드 | 템플릿 | 설명 |
|--------|--------|------|
| `dispBoardAdminContent` | index.html | **게시판 목록** (검색, 페이지네이션) |
| `dispBoardAdminInsertBoard` | board_insert.html | **게시판 설정** (기본+목록+고급 통합 폼) |
| `dispBoardAdminCategoryInfo` | category_list.html | **분류 관리** |
| `dispBoardAdminGrantInfo` | grant_list.html | **권한 관리** |
| `dispBoardAdminExtraVars` | extra_vars.html | **확장변수 관리** |
| `dispBoardAdminSkinInfo` | skin_info.html | **스킨 설정** |
| `dispBoardAdminBoardAdditionSetup` | addition_setup.html | **추가 설정** (통합게시판) |

### 핵심 설정값 (BoardModel::DEFAULT_MODULE_CONFIG)

```php
// SEO
'browser_title', 'meta_keywords', 'meta_description', 'robots_tag'
// PC 표시
'layout_srl', 'skin', 'list_count'(20), 'search_list_count'(20),
'page_count'(10), 'header_text', 'footer_text'
// 모바일
'use_mobile', 'mlayout_srl', 'mskin', 'mobile_list_count'(20),
'mobile_page_count'(5), 'mobile_header_text', 'mobile_footer_text'
// 목록 설정
'order_target'(list_order), 'order_type'(asc), 'except_notice'(Y), 'use_bottom_list'(Y)
// 기능
'consultation'(N), 'use_anonymous'(N), 'anonymous_name'(anonymous),
'update_order_on_comment'(N), 'comment_delete_message'(no), 'trash_use'(N),
'use_status'(PUBLIC), 'use_category'(N)
// 보호/제한
'document_length_limit'(1024KB), 'comment_length_limit'(128KB),
'protect_delete_content'(N), 'protect_update_content'(N),
'protect_document_regdate', 'protect_comment_regdate'
```

### 권한 시스템 (module.xml grants)

| 권한 | 기본값 | 설명 |
|------|--------|------|
| list | guest | 목록 보기 |
| view | guest | 글 열람 |
| write_document | guest | 글 작성 |
| write_comment | guest | 댓글 작성 |
| vote_log_view | member | 추천인 보기 |
| update_view | member | 수정 내역 보기 |
| consultation_read | manager | 상담글 열람 |

### 프론트 컨트롤러 로직 핵심

- **글 작성**: 길이 제한 → 카테고리 체크 → 익명 처리 → 비밀글 처리 → insert/update
- **글 삭제**: 보호(댓글수/기간/관리자글) → 휴지통 or 완전삭제
- **댓글 작성**: 길이 제한 → 익명 처리 → 비밀글 상속 → insert/update
- **댓글 삭제**: 보호(대댓글/기간/관리자) → 자리남김 or 휴지통 or 완전삭제

---

## 2. RezlyX 설계 원칙

1. Rhymix의 `module_srl` 기반 → RezlyX의 `board_id` 기반으로 단순화
2. Rhymix의 XML 쿼리 → PDO 직접 쿼리
3. Rhymix의 Context/ModuleObject → PHP 변수 + include
4. 모바일 별도 스킨 → 반응형 단일 스킨 (Tailwind)
5. 확장변수(extra_vars) → 1차에서 제외, 필요시 추가

---

## 3. DB 테이블 (6개)

> 마이그레이션: `database/migrations/017_create_board_tables.sql`

### 3-1. `rzx_boards` (게시판 설정) — 44 컬럼

| 컬럼 | 타입 | 기본값 | 설명 |
|------|------|--------|------|
| id | INT PK AI | | 게시판 ID |
| slug | VARCHAR(50) UNIQUE | | URL 슬러그 (/notice) |
| title | VARCHAR(200) | | 브라우저 제목 |
| category | VARCHAR(50) | 'board' | 모듈 분류 (board/notice/qna/faq/gallery) |
| description | TEXT | NULL | 설명 |
| **SEO** |||
| seo_keywords | VARCHAR(500) | '' | SEO 키워드 |
| seo_description | VARCHAR(500) | '' | SEO 설명 |
| robots_tag | VARCHAR(10) | 'all' | 검색엔진 색인 |
| **표시** |||
| skin | VARCHAR(50) | 'default' | 스킨 |
| per_page | INT | 20 | 목록 수 |
| search_per_page | INT | 20 | 검색 목록 수 |
| page_count | INT | 10 | 페이지 수 |
| header_content | TEXT | NULL | 헤더 HTML |
| footer_content | TEXT | NULL | 푸터 HTML |
| **목록** |||
| list_columns | JSON | NULL | 표시 컬럼 배열 |
| sort_field | VARCHAR(30) | 'created_at' | 정렬 기준 |
| sort_direction | VARCHAR(4) | 'DESC' | 정렬 방향 |
| except_notice | TINYINT(1) | 1 | 공지 목록 제외 |
| show_category | TINYINT(1) | 0 | 카테고리 표시 |
| **기능** |||
| allow_comment | TINYINT(1) | 1 | 댓글 허용 |
| use_anonymous | TINYINT(1) | 0 | 익명 사용 |
| anonymous_name | VARCHAR(50) | 'anonymous' | 익명 닉네임 |
| allow_secret | TINYINT(1) | 0 | 비밀글 허용 |
| consultation | TINYINT(1) | 0 | 상담 기능 |
| use_trash | TINYINT(1) | 1 | 휴지통 |
| update_order_on_comment | TINYINT(1) | 0 | 댓글시 수정시각 갱신 |
| comment_delete_message | VARCHAR(20) | 'no' | 댓글 삭제 처리 |
| **제한/보호** |||
| doc_length_limit | INT | 1024 | 문서 크기 제한(KB) |
| comment_length_limit | INT | 128 | 댓글 크기 제한(KB) |
| protect_content_by_comment | TINYINT(1) | 0 | 댓글 있으면 수정/삭제 방지 |
| protect_by_days | INT | 0 | N일 후 수정/삭제 방지 |
| admin_mail | VARCHAR(200) | '' | 관리자 알림 메일 |
| **권한** |||
| perm_list | VARCHAR(20) | 'all' | 목록 권한 |
| perm_read | VARCHAR(20) | 'all' | 열람 권한 |
| perm_write | VARCHAR(20) | 'member' | 글 작성 권한 |
| perm_comment | VARCHAR(20) | 'member' | 댓글 권한 |
| perm_manage | VARCHAR(20) | 'admin' | 관리 권한 |
| **상태** |||
| is_active | TINYINT(1) | 1 | 활성 |
| sort_order | INT | 0 | 정렬 |
| created_at | DATETIME | CURRENT_TIMESTAMP | 생성일 |
| updated_at | DATETIME | ON UPDATE CURRENT_TIMESTAMP | 수정일 |

`list_columns` 기본값: `["no", "title", "nick_name", "created_at", "view_count"]`

**인덱스**: `UNIQUE(slug)`

### 3-2. `rzx_board_categories` (게시판 내 분류) — 7 컬럼

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| board_id | INT NOT NULL | 소속 게시판 |
| name | VARCHAR(100) | 분류명 |
| slug | VARCHAR(50) NULL | URL용 슬러그 |
| color | VARCHAR(7) | 색상 (#hex) |
| sort_order | INT DEFAULT 0 | 정렬 |
| is_active | TINYINT(1) DEFAULT 1 | 활성 |

**인덱스**: `idx_board_id(board_id)`

### 3-3. `rzx_board_posts` (게시글) — 22 컬럼

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| board_id | INT NOT NULL | 소속 게시판 |
| category_id | INT NULL | 분류 |
| user_id | INT NULL | 작성자 |
| title | VARCHAR(300) | 제목 |
| content | LONGTEXT NULL | 본문 (HTML) |
| password | VARCHAR(255) NULL | 비회원 비밀번호 |
| is_notice | TINYINT(1) DEFAULT 0 | 공지사항 |
| is_secret | TINYINT(1) DEFAULT 0 | 비밀글 |
| is_anonymous | TINYINT(1) DEFAULT 0 | 익명 |
| nick_name | VARCHAR(100) DEFAULT '' | 작성자명/익명명 |
| view_count | INT DEFAULT 0 | 조회수 |
| comment_count | INT DEFAULT 0 | 댓글수 |
| like_count | INT DEFAULT 0 | 추천수 |
| **dislike_count** | **INT NOT NULL DEFAULT 0** | **비추천수** |
| **file_count** | **INT DEFAULT 0** | **첨부파일수** |
| list_order | BIGINT DEFAULT 0 | 목록 정렬키 |
| update_order | BIGINT DEFAULT 0 | 수정순 정렬키 |
| status | ENUM('published','draft','trash') DEFAULT 'published' | 상태 |
| **ip_address** | **VARCHAR(45) NULL** | **작성자 IP** |
| created_at | DATETIME | 작성일 |
| updated_at | DATETIME | 수정일 |

**인덱스**: `idx_board(board_id)`, `idx_category(category_id)`, `idx_user(user_id)`, `idx_list_order(board_id, list_order)`, `idx_status(board_id, status)`

### 3-4. `rzx_board_comments` (댓글) — 16 컬럼

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| post_id | INT NOT NULL | 소속 게시글 |
| board_id | INT NOT NULL | 소속 게시판 |
| user_id | INT NULL | 작성자 |
| parent_id | INT NULL | 부모 댓글 (대댓글) |
| **depth** | **TINYINT DEFAULT 0** | **대댓글 깊이** (0=댓글, 1=대댓글) |
| content | TEXT NOT NULL | 내용 |
| password | VARCHAR(255) NULL | 비회원 비밀번호 |
| is_secret | TINYINT(1) DEFAULT 0 | 비밀 댓글 |
| is_anonymous | TINYINT(1) DEFAULT 0 | 익명 |
| nick_name | VARCHAR(100) DEFAULT '' | 작성자명 |
| like_count | INT DEFAULT 0 | 추천수 |
| status | ENUM('published','deleted','trash') DEFAULT 'published' | 상태 |
| **ip_address** | **VARCHAR(45) NULL** | **작성자 IP** |
| created_at | DATETIME | |
| updated_at | DATETIME | |

**인덱스**: `idx_post(post_id)`, `idx_board(board_id)`, `idx_parent(parent_id)`

### 3-5. `rzx_board_files` (첨부파일) — 9 컬럼

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| post_id | INT NOT NULL | 소속 게시글 |
| **board_id** | **INT NOT NULL** | **소속 게시판** |
| original_name | VARCHAR(300) | 원본 파일명 |
| stored_name | VARCHAR(300) | 저장 파일명 (bf_{UNIQID}.확장자) |
| file_path | VARCHAR(500) | 저장 경로 (/storage/board/{boardId}/) |
| file_size | INT DEFAULT 0 | 파일 크기 (bytes) |
| mime_type | VARCHAR(100) DEFAULT '' | MIME 타입 |
| download_count | INT DEFAULT 0 | 다운로드 수 |
| created_at | DATETIME | |

**인덱스**: `idx_post(post_id)`, `idx_board(board_id)`

### 3-6. `rzx_board_votes` (추천/비추천) — 6 컬럼

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| post_id | INT NOT NULL | 대상 게시글 |
| user_id | INT NULL | 투표한 회원 (비회원 NULL) |
| ip_address | VARCHAR(45) NOT NULL DEFAULT '' | 투표자 IP (비회원 중복 방지) |
| vote_type | ENUM('like','dislike') NOT NULL DEFAULT 'like' | 투표 유형 |
| created_at | DATETIME NOT NULL | |

**인덱스**: `UNIQUE uniq_user_vote(post_id, user_id, vote_type)`, `idx_post(post_id)`, `idx_ip(post_id, ip_address)`

---

## 4. 라우트

### 관리자 라우트

| 경로 | 파일 | 설명 |
|------|------|------|
| `/admin/site/boards` | boards.php | 게시판 목록 |
| `/admin/site/boards/create` | boards-create.php | 게시판 생성 |
| `/admin/site/boards/edit?id=N` | boards-edit.php | 게시판 설정 (탭 컨테이너) |
| `/admin/site/boards/edit?id=N&tab=basic` | boards-edit-basic.php | 기본 설정 탭 |
| `/admin/site/boards/edit?id=N&tab=categories` | boards-edit-categories.php | 분류 관리 탭 |
| `/admin/site/boards/edit?id=N&tab=permissions` | boards-edit-permissions.php | 권한 관리 탭 |
| `/admin/site/boards/edit?id=N&tab=list` | boards-edit-list.php | 목록 설정 탭 |
| `/admin/site/boards/edit?id=N&tab=advanced` | boards-edit-advanced.php | 고급 설정 탭 |
| `/admin/site/boards/api` | boards-api.php | 게시판 CRUD API |
| `/admin/site/boards/trash` | boards-trash.php | 휴지통 관리 |

### 고객용 라우트

| 경로 | 파일 | 설명 |
|------|------|------|
| `/board/{slug}` | list.php | 게시판 목록 |
| `/board/{slug}/write` | write.php | 게시글 작성 |
| `/board/{slug}/{postId}` | read.php | 게시글 상세 |
| `/board/{slug}/{postId}/edit` | write.php (수정 모드) | 게시글 수정 |
| `/board/api/posts` | api-posts.php | 게시글 API |
| `/board/api/comments` | api-comments.php | 댓글 API |
| `/board/api/files` | api-files.php | 파일 API |

---

## 5. 파일 구조

### 관리자 뷰 (11개 파일, ~1,840줄)

```
resources/views/admin/site/
├── boards.php                  # 게시판 목록 (307줄)
├── boards-create.php           # 게시판 생성 - 기본설정 폼 (212줄)
├── boards-edit.php             # 게시판 설정 - 탭 컨테이너 (163줄)
├── boards-edit-basic.php       # 기본 설정 탭 (153줄)
├── boards-edit-categories.php  # 분류 관리 탭 (146줄)
├── boards-edit-permissions.php # 권한 설정 탭 (63줄)
├── boards-edit-list.php        # 목록 설정 탭 (145줄)
├── boards-edit-advanced.php    # 고급 설정 탭 (112줄)
├── boards-edit-js.php          # 설정 페이지 JS (51줄)
├── boards-api.php              # 게시판 CRUD API (279줄)
└── boards-trash.php            # 휴지통 관리 (209줄)
```

### 고객용 뷰 (10개 파일, ~1,476줄)

```
resources/views/customer/board/
├── _init.php                   # 공통 초기화 (54줄)
│                               #   DB 연결, 게시판 로드, 권한 체크, 카테고리 로드
├── _header.php                 # HTML 헤더/네비게이션 (71줄)
├── _footer.php                 # HTML 푸터 (8줄)
├── _list-row.php               # 목록 행 렌더링 컴포넌트 (81줄)
├── list.php                    # 게시판 목록 (211줄)
│                               #   페이지네이션, 검색, 카테고리 필터, 공지 고정, 동적 컬럼
├── read.php                    # 게시글 상세 (329줄)
│                               #   비밀글 비번, 상담모드, 조회수, 좋아요, 첨부파일, 이전/다음글, 댓글
├── write.php                   # 게시글 작성/수정 (199줄)
│                               #   비회원 필드, 카테고리, 공지/비밀, 파일 첨부, 글자수 제한
├── api-posts.php               # 게시글 API (288줄)
│                               #   create, update, delete, like, dislike
├── api-comments.php            # 댓글 API (161줄)
│                               #   create, delete (대댓글 depth 지원)
└── api-files.php               # 파일 API (74줄)
                                #   download (GET), delete (POST)
```

### 번역 파일 (13개 언어)

```
resources/lang/{locale}/
├── board.php                   # 고객용 게시판 번역 (~36키, 평면 구조)
└── site.php → boards 섹션      # 관리자 게시판 번역 (~83키, 중첩 구조)
```

**지원 언어**: ko, en, ja, zh_CN, zh_TW, de, es, fr, id, mn, ru, tr, vi

### 마이그레이션

```
database/migrations/
└── 017_create_board_tables.sql  # 6개 테이블 CREATE IF NOT EXISTS (142줄)
```

---

## 6. 관리자 설정 탭 상세

### Tab 1: 기본 설정 (tab=basic)

- URL slug, 브라우저 제목, 모듈 분류 (board/notice/qna/faq/gallery)
- 검색엔진 색인 (robots_tag), SEO 키워드/설명
- 스킨 선택, 목록수/검색목록수/페이지수
- 헤더/푸터 커스텀 HTML
- 활성/비활성 토글

### Tab 2: 분류 관리 (tab=categories)

- 게시판 내 카테고리 CRUD (이름, 슬러그, 색상)
- 정렬 순서 지정
- 카테고리 활성/비활성

### Tab 3: 권한 관리 (tab=permissions)

| 권한 | 컬럼 | 옵션 |
|------|------|------|
| 목록 보기 | perm_list | all / member / admin |
| 글 읽기 | perm_read | all / member / admin |
| 글 쓰기 | perm_write | all / member / admin |
| 댓글 쓰기 | perm_comment | all / member / admin |
| 관리 | perm_manage | admin |

### Tab 4: 목록 설정 (tab=list)

- 표시 컬럼 선택 + 순서 (대상항목 ↔ 표시항목 이동)
- 사용 가능 컬럼: no, title, category, nick_name, created_at, view_count, comment_count, like_count
- 정렬 기준 (created_at / updated_at / view_count / list_order) + 방향 (ASC/DESC)
- 공지사항 목록에서 제외 옵션

### Tab 5: 고급 설정 (tab=advanced)

- **댓글**: 허용 여부, 댓글시 글 수정시각 갱신, 댓글 삭제시 자리 남김 (no/always/reply)
- **익명**: 익명 사용, 익명 닉네임 패턴 ($NUM → '익명1', '익명2')
- **비밀글**: 비밀글 허용
- **상담**: 상담 모드 (자기 글 + 공지만 보임, 관리자는 전체 표시)
- **제한**: 문서 크기 제한(KB), 댓글 크기 제한(KB)
- **보호**: 댓글 N개 이상시 수정/삭제 방지, N일 후 수정/삭제 방지
- **삭제**: 휴지통 사용 여부
- **알림**: 관리자 메일 알림 주소

---

## 7. API 엔드포인트 상세

### 7-1. 관리자 API (boards-api.php)

| 액션 | 메서드 | 설명 |
|------|--------|------|
| create | POST | 게시판 생성 (slug 중복 체크) |
| update | POST | 게시판 설정 수정 |
| delete | POST | 게시판 삭제 (관련 데이터 포함) |
| copy | POST | 게시판 복사 (설정만, 게시글 제외) |
| category_add | POST | 분류 추가 |
| category_update | POST | 분류 수정 |
| category_delete | POST | 분류 삭제 |
| trash_restore | POST | 휴지통 → 게시글 복원 |
| trash_delete | POST | 휴지통 영구 삭제 |

### 7-2. 게시글 API (api-posts.php)

| 액션 | 메서드 | 설명 |
|------|--------|------|
| create | POST | 글 작성 (파일 업로드 포함) |
| update | POST | 글 수정 (파일 추가/제거) |
| delete | POST | 글 삭제 (use_trash → 소프트삭제, 아니면 완전삭제) |
| like | POST | 추천 (회원: user_id, 비회원: IP 기반 중복 방지) |
| dislike | POST | 비추천 (like와 동일한 중복 방지 로직) |

**글 작성 처리 흐름**:
1. 권한 체크 (perm_write)
2. 글자 수 제한 체크 (doc_length_limit)
3. 익명 처리 (use_anonymous → 게시판별 고유 번호 부여)
4. 비밀글 처리 (allow_secret)
5. INSERT + 파일 업로드
6. list_order / update_order 계산

**추천/비추천 로직**:
1. 자기 글 추천 방지 (user_id === post.user_id)
2. 회원: `rzx_board_votes`에서 user_id + post_id + vote_type 중복 체크
3. 비회원: ip_address + post_id + vote_type 중복 체크
4. 투표 기록 INSERT → posts 테이블 like_count/dislike_count 업데이트

### 7-3. 댓글 API (api-comments.php)

| 액션 | 메서드 | 설명 |
|------|--------|------|
| create | POST | 댓글 작성 (대댓글: parent_id, depth 자동 계산) |
| delete | POST | 댓글 삭제 (대댓글 있으면 status='deleted', 없으면 완전삭제) |

**댓글 작성 처리 흐름**:
1. 권한 체크 (perm_comment)
2. 글자 수 제한 체크 (comment_length_limit)
3. 대댓글이면 depth = parent.depth + 1
4. 익명 처리 (글과 동일한 번호 재사용)
5. INSERT → post의 comment_count 갱신
6. update_order_on_comment 설정 시 post의 update_order 갱신

### 7-4. 파일 API (api-files.php)

| 액션 | 메서드 | 설명 |
|------|--------|------|
| download | GET | 파일 다운로드 (download_count 증가) |
| delete | POST | 파일 삭제 (물리파일 + DB 기록 + post의 file_count 갱신) |

**파일 저장 규칙**:
- 저장 경로: `/storage/board/{boardId}/`
- 파일명 패턴: `bf_{UNIQID}.{확장자}`

---

## 8. 주요 기능 구현 상세

### 8-1. 익명 시스템 ($NUM 패턴)

- 게시판에서 `use_anonymous = 1` 설정 시 활성화
- 같은 게시판 내에서 작성자(user_id)별 고유 번호 자동 부여
- 표시: "익명1", "익명2" 등 (`anonymous_name` + 번호)
- 댓글에서도 동일한 번호 재사용 (같은 사람이 같은 게시판에서 쓰면 항상 같은 번호)
- `is_anonymous = 1`로 저장, 원본 user_id는 DB에 보존 (관리자 추적 가능)

### 8-2. 상담 모드 (consultation)

- 게시판에서 `consultation = 1` 설정 시 활성화
- **일반 회원**: 목록에서 자기 글 + 공지사항만 표시
- **관리자**: 전체 글 표시
- **비회원**: 비밀번호로 작성한 글은 조회 불가 (글 상세에서 비번 입력 필요)

### 8-3. 비밀글

- 게시판에서 `allow_secret = 1` 설정 시 활성화
- 작성 시 "비밀글" 체크 → `is_secret = 1`
- 목록에서 자물쇠 아이콘 표시, 제목 클릭 시 비밀번호 입력 (비회원) 또는 본인/관리자만 열람

### 8-4. 글 보호

- `protect_content_by_comment`: 댓글이 1개 이상 달리면 수정/삭제 불가
- `protect_by_days`: N일 경과 후 수정/삭제 불가 (0이면 무제한)

### 8-5. 휴지통

- 게시판에서 `use_trash = 1` 설정 시 활성화
- 삭제 시 `status = 'trash'`로 변경 (소프트 삭제)
- 관리자: `/admin/site/boards/trash?board_id=N`에서 관리
- 체크박스 선택 → 복원 또는 영구 삭제
- 게시판 설정에 "휴지통" 바로가기 버튼 (삭제 건수 배지 표시)

### 8-6. 댓글 삭제 처리 (comment_delete_message)

| 값 | 동작 |
|------|------|
| 'no' | 즉시 완전 삭제 |
| 'always' | "삭제된 댓글입니다" 자리 남김 |
| 'reply' | 대댓글이 있을 때만 자리 남김, 없으면 완전 삭제 |

---

## 9. 번역 키 구조

### 9-1. board.php (고객용, ~36키, 평면 구조)

```
목록: all, no_posts, col_no, col_title, col_category, col_author, col_date, col_views,
      col_votes, col_comments, notice, write
검색: search, search_all, search_title, search_content, search_author, search_placeholder
상세: attachments, like, dislike, already_voted, cannot_vote_own,
      list, prev, next, edit, delete, delete_post, delete_confirm,
      secret_post, enter_password, wrong_password, confirm
댓글: comments, submit_comment, comment_placeholder, comment_deleted, comment_delete_confirm
작성: write_post, edit_post, title, title_placeholder, content, content_placeholder,
      category, no_category, nickname, password,
      set_notice, set_secret, file_attach, remove, cancel, submit, update, char_limit
```

### 9-2. site.php → boards 섹션 (관리자용, ~83키, 중첩 구조)

```
기본: title, description, add, total, page_info, col_*, settings, copy, delete, ...
탭:   tab_basic, tab_categories, tab_permissions, tab_list, tab_advanced
필드: field_url, field_title, field_category, field_description, field_is_active, ...
분류: cat_board, cat_notice, cat_qna, cat_faq, cat_gallery, cat_mgmt_*, cat_add, ...
권한: perm_title, perm_all, perm_member, perm_admin, perm_list_label, ...
목록: list_columns_title, list_selected, list_available, lcol_*, sort_*, except_notice, ...
고급: adv_comment_title, adv_allow_comment, adv_anon_title, ...
휴지통: trash, trash_count, trash_restore, trash_permanent_delete, ...
```

---

## 10. 개발 단계 (완료 현황)

### Phase 1: 관리자 - 게시판 CRUD + 기본설정 ✅

1. DB 테이블 확장 (`rzx_boards` ALTER 30+ 컬럼 + `rzx_board_categories` CREATE)
2. 게시판 생성 페이지 (boards-create.php) — 기본설정 폼
3. 게시판 설정 페이지 — 탭 컨테이너 (boards-edit.php) + 기본설정 탭
4. 게시판 API (생성/수정/삭제/복사) — boards-api.php
5. 번역 파일 분리 (site.php boards 섹션) 13개 언어

### Phase 2: 관리자 - 나머지 설정 탭 ✅

6. 분류 관리 탭 + `rzx_board_categories` CRUD
7. 권한 관리 탭 (5단계: 목록/읽기/쓰기/댓글/관리)
8. 목록 설정 탭 (컬럼 선택/순서 + 정렬 설정)
9. 고급 설정 탭 (댓글/익명/비밀/상담/제한/보호/휴지통)

### Phase 3: 고객용 게시판 (프론트) ✅

10. DB 테이블: `rzx_board_posts`(22컬럼), `rzx_board_comments`(16컬럼), `rzx_board_files`(9컬럼)
11. 공통 초기화 (_init.php: 게시판 로드, 권한 체크, 카테고리)
12. 공통 헤더/푸터 (_header.php, _footer.php)
13. 게시글 목록 (list.php + _list-row.php: 페이지네이션, 검색, 카테고리 필터, 공지 고정, 동적 컬럼)
14. 게시글 상세 (read.php: 비밀글 비번, 상담모드, 조회수, 첨부파일, 이전/다음글, 댓글)
15. 게시글 작성/수정 (write.php: 비회원 필드, 카테고리, 공지/비밀, 파일 첨부, 글자수 제한)
16. 게시글 API (api-posts.php: create/update/delete/like)
17. 댓글 API (api-comments.php: create/delete, 대댓글 depth)
18. 파일 API (api-files.php: download/delete, 다운로드 카운트)
19. 13개 언어 board.php 번역 파일 생성

### Phase 4: 부가 기능 ✅

20. `rzx_board_votes` 테이블 생성 — 추천/비추천 중복 방지 (회원 user_id / 비회원 IP 기반)
21. 비추천 기능 — `dislike_count` 컬럼, 비추천 버튼 UI + API
22. 상담모드 목록 필터 — 본인 글 + 공지만 표시 (관리자 전체)
23. 익명글 $NUM 패턴 — 게시판 내 작성자별 고유 번호, 댓글에서도 동일 번호 재사용
24. 관리자 휴지통 페이지 — boards-trash.php (목록/체크박스 선택/복원/영구삭제)
25. 게시판 설정에 휴지통 바로가기 버튼 (삭제 건수 배지)
26. 13개 언어 Phase 4 번역 키 추가

---

## 11. 향후 계획 (미구현)

- [ ] 갤러리형 스킨 (skins/board/gallery/)
- [ ] 확장변수 (extra_vars) 시스템
- [ ] 관리자 메일 알림 발송 (현재 admin_mail 필드만 존재)
- [ ] 검색 고도화 (태그 검색, 전문 검색)
- [ ] 게시글 임시 저장 (draft 상태 활용)
- [ ] Board 모듈 클래스 분리 (현재 뷰 파일 내 직접 쿼리 → Module/Service 패턴)
- [ ] 에디터 통합 (현재 textarea → WYSIWYG 에디터)
