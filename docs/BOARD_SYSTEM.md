# RezlyX 게시판 시스템 설계 문서

## 개요

Rhymix 게시판 모듈(`modules/board/`)을 분석하여 RezlyX에 맞게 재설계한 게시판 시스템.

## Rhymix 게시판 구조 분석

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
'order_target'(list_order), 'order_type'(asc),
'except_notice'(Y), 'use_bottom_list'(Y)

// 기능
'consultation'(N), 'use_anonymous'(N), 'anonymous_name'(anonymous),
'update_log'(N), 'update_order_on_comment'(N),
'comment_delete_message'(no), 'trash_use'(N),
'use_status'(PUBLIC), 'use_category'(N)

// 보호/제한
'document_length_limit'(1024KB), 'comment_length_limit'(128KB),
'inline_data_url_limit'(64KB), 'filter_specialchars'(Y),
'protect_delete_content'(N), 'protect_update_content'(N),
'protect_admin_content_delete'(Y), 'protect_admin_content_update'(Y),
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

## RezlyX 적용 설계

### 설계 원칙

1. Rhymix의 `module_srl` 기반 → RezlyX의 `board_id` 기반으로 단순화
2. Rhymix의 XML 쿼리 → PDO 직접 쿼리
3. Rhymix의 Context/ModuleObject → PHP 변수 + include
4. 모바일 별도 스킨 → 반응형 단일 스킨 (Tailwind)
5. 확장변수(extra_vars) → 1차에서 제외, 필요시 추가

### DB 테이블

#### 1. `rzx_boards` (게시판 설정)

| 컬럼 | 타입 | 기본값 | 설명 | Rhymix 대응 |
|------|------|--------|------|-------------|
| id | INT PK AI | | 게시판 ID | module_srl |
| slug | VARCHAR(50) UNIQUE | | URL (/notice) | mid |
| title | VARCHAR(200) | | 브라우저 제목 | browser_title |
| category | VARCHAR(50) | 'board' | 모듈 분류 | module_category_srl |
| description | TEXT | | 설명 | description |
| **SEO** | | | | |
| seo_keywords | VARCHAR(500) | '' | SEO 키워드 | meta_keywords |
| seo_description | VARCHAR(500) | '' | SEO 설명 | meta_description |
| robots_tag | VARCHAR(10) | 'all' | 검색엔진 색인 | robots_tag |
| **표시** | | | | |
| skin | VARCHAR(50) | 'default' | 스킨 | skin |
| per_page | INT | 20 | 목록 수 | list_count |
| search_per_page | INT | 20 | 검색 목록 수 | search_list_count |
| page_count | INT | 10 | 페이지 수 | page_count |
| header_content | TEXT | | 헤더 HTML | header_text |
| footer_content | TEXT | | 푸터 HTML | footer_text |
| **목록** | | | | |
| list_columns | JSON | (기본배열) | 표시 컬럼 | list config |
| sort_field | VARCHAR(30) | 'created_at' | 정렬 기준 | order_target |
| sort_direction | VARCHAR(4) | 'DESC' | 정렬 방향 | order_type |
| except_notice | TINYINT(1) | 1 | 공지 목록 제외 | except_notice |
| show_category | TINYINT(1) | 0 | 카테고리 표시 | use_category |
| **기능** | | | | |
| allow_comment | TINYINT(1) | 1 | 댓글 허용 | (status) |
| use_anonymous | TINYINT(1) | 0 | 익명 사용 | use_anonymous |
| anonymous_name | VARCHAR(50) | 'anonymous' | 익명 닉네임 | anonymous_name |
| allow_secret | TINYINT(1) | 0 | 비밀글 허용 | use_status |
| consultation | TINYINT(1) | 0 | 상담 기능 | consultation |
| use_trash | TINYINT(1) | 1 | 휴지통 | trash_use |
| update_order_on_comment | TINYINT(1) | 0 | 댓글시 수정시각 갱신 | update_order_on_comment |
| comment_delete_message | VARCHAR(20) | 'no' | 댓글 삭제 처리 | comment_delete_message |
| **제한/보호** | | | | |
| doc_length_limit | INT | 1024 | 문서 크기 제한(KB) | document_length_limit |
| comment_length_limit | INT | 128 | 댓글 크기 제한(KB) | comment_length_limit |
| protect_content_by_comment | TINYINT(1) | 0 | 댓글 있으면 수정/삭제 방지 | protect_content |
| protect_by_days | INT | 0 | N일 후 수정/삭제 방지 | protect_document_regdate |
| admin_mail | VARCHAR(200) | '' | 관리자 알림 메일 | admin_mail |
| **권한** | | | | |
| perm_list | VARCHAR(20) | 'all' | 목록 권한 | grant.list |
| perm_read | VARCHAR(20) | 'all' | 열람 권한 | grant.view |
| perm_write | VARCHAR(20) | 'member' | 글 작성 권한 | grant.write_document |
| perm_comment | VARCHAR(20) | 'member' | 댓글 권한 | grant.write_comment |
| perm_manage | VARCHAR(20) | 'admin' | 관리 권한 | grant.manager |
| **상태** | | | | |
| is_active | TINYINT(1) | 1 | 활성 | |
| sort_order | INT | 0 | 정렬 | |
| created_at | DATETIME | | 생성일 | regdate |
| updated_at | DATETIME | | 수정일 | |

`list_columns` 기본값:
```json
["no", "title", "nick_name", "created_at", "view_count"]
```

#### 2. `rzx_board_categories` (게시판 내 분류)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| board_id | INT FK | 소속 게시판 |
| name | VARCHAR(100) | 분류명 |
| slug | VARCHAR(50) | URL용 슬러그 |
| color | VARCHAR(7) | 색상 (#hex) |
| sort_order | INT DEFAULT 0 | 정렬 |
| is_active | TINYINT(1) DEFAULT 1 | 활성 |

#### 3. `rzx_board_posts` (게시글)

| 컬럼 | 타입 | 설명 | Rhymix 대응 |
|------|------|------|-------------|
| id | INT PK AI | | document_srl |
| board_id | INT FK | 소속 게시판 | module_srl |
| category_id | INT NULL FK | 분류 | category_srl |
| user_id | INT NULL FK | 작성자 | member_srl |
| title | VARCHAR(300) | 제목 | title |
| content | LONGTEXT | 본문 (HTML) | content |
| password | VARCHAR(255) NULL | 비회원 비밀번호 | password |
| is_notice | TINYINT(1) DEFAULT 0 | 공지사항 | is_notice |
| is_secret | TINYINT(1) DEFAULT 0 | 비밀글 | status=SECRET |
| is_anonymous | TINYINT(1) DEFAULT 0 | 익명 | member_srl<0 |
| nick_name | VARCHAR(100) | 작성자명/익명명 | nick_name |
| view_count | INT DEFAULT 0 | 조회수 | readed_count |
| comment_count | INT DEFAULT 0 | 댓글수 | comment_count |
| like_count | INT DEFAULT 0 | 추천수 | voted_count |
| list_order | INT | 목록 정렬키 | list_order |
| update_order | INT | 수정순 정렬키 | update_order |
| status | ENUM('published','draft','trash') | 상태 | status |
| created_at | DATETIME | 작성일 | regdate |
| updated_at | DATETIME | 수정일 | last_update |

#### 4. `rzx_board_comments` (댓글)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| post_id | INT FK | 소속 게시글 |
| board_id | INT FK | 소속 게시판 (Rhymix처럼 document의 module_srl 따름) |
| user_id | INT NULL FK | 작성자 |
| parent_id | INT NULL FK | 부모 댓글 (대댓글) |
| content | TEXT | 내용 |
| password | VARCHAR(255) NULL | 비회원 비밀번호 |
| is_secret | TINYINT(1) DEFAULT 0 | 비밀 댓글 |
| is_anonymous | TINYINT(1) DEFAULT 0 | 익명 |
| nick_name | VARCHAR(100) | 작성자명 |
| like_count | INT DEFAULT 0 | 추천수 |
| status | ENUM('published','deleted','trash') | 상태 |
| created_at | DATETIME | |
| updated_at | DATETIME | |

#### 5. `rzx_board_files` (첨부파일)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK AI | |
| post_id | INT FK | 소속 게시글 |
| original_name | VARCHAR(300) | 원본 파일명 |
| stored_name | VARCHAR(300) | 저장 파일명 |
| file_path | VARCHAR(500) | 저장 경로 |
| file_size | INT | 파일 크기 (bytes) |
| mime_type | VARCHAR(100) | MIME 타입 |
| download_count | INT DEFAULT 0 | 다운로드 수 |
| created_at | DATETIME | |

---

## 관리자 페이지 구조

### URL 라우트

| 경로 | 설명 | Rhymix 대응 |
|------|------|-------------|
| `/theadmin/site/boards` | 게시판 목록 | dispBoardAdminContent |
| `/theadmin/site/boards/create` | 게시판 생성 | dispBoardAdminInsertBoard (새로) |
| `/theadmin/site/boards/edit?id=N` | 게시판 설정 | dispBoardAdminInsertBoard (수정) |
| `/theadmin/site/boards/edit?id=N&tab=categories` | 분류 관리 | dispBoardAdminCategoryInfo |
| `/theadmin/site/boards/edit?id=N&tab=permissions` | 권한 관리 | dispBoardAdminGrantInfo |
| `/theadmin/site/boards/edit?id=N&tab=list` | 목록 설정 | (board_insert.html 중 목록설정 섹션) |
| `/theadmin/site/boards/edit?id=N&tab=advanced` | 고급 설정 | (board_insert.html 중 고급설정 섹션) |

### 설정 페이지 탭 (Rhymix board_insert.html 4개 섹션 → 5탭 분리)

**1. 기본 설정** (tab=basic)
- Rhymix `section: subtitle_primary` 대응
- URL slug, 브라우저 제목, 검색엔진 색인, SEO 키워드/설명
- 스킨 선택, 목록수/검색목록수/페이지수
- 헤더/푸터 HTML

**2. 분류 관리** (tab=categories)
- Rhymix `dispBoardAdminCategoryInfo` 대응
- 게시판 내 카테고리 CRUD (이름, 슬러그, 색상)
- 드래그 정렬, 카테고리 숨기기, 미분류 허용

**3. 권한 관리** (tab=permissions)
- Rhymix `dispBoardAdminGrantInfo` + `module.xml grants` 대응
- 목록/열람/글작성/댓글작성/관리 5가지 권한
- 각 권한별: 전체공개(all) / 회원만(member) / 관리자만(admin)

**4. 목록 설정** (tab=list)
- Rhymix `section: cmd_list_setting` 대응
- 표시 컬럼 선택 + 순서 (대상항목↔표시항목 이동, 위로/아래로/삭제)
- 정렬 기준(list_order/created_at/updated_at/view_count 등) + 방향
- 공지사항 제외, 하단목록 표시

**5. 고급 설정** (tab=advanced)
- Rhymix `section: subtitle_advanced` 대응
- 상담 기능 (자기 글만 보임)
- 익명 사용 + 익명 닉네임 ($NUM 패턴)
- 문서/댓글 길이 제한 (KB)
- 글 보호 (댓글 N개 이상시 수정/삭제 방지)
- 기간 제한 (N일 후 수정/삭제 방지)
- 삭제시 휴지통 사용
- 댓글 삭제시 자리 남김 (안남김/항상/대댓글시만)
- 댓글 작성시 글 수정시각 갱신
- 유니코드 특수문자 필터
- 비밀글 허용 (상태: 공개/비밀)
- 관리자 글 보호 (삭제/수정 방지)
- 관리자 메일 알림

### 고객용 페이지

| 경로 | 설명 | Rhymix 대응 |
|------|------|-------------|
| `/board/{slug}` | 게시판 목록 | dispBoardContent (index) |
| `/board/{slug}/{id}` | 게시글 상세 | dispBoardContent (document_srl) |
| `/board/{slug}/write` | 게시글 작성 | dispBoardWrite |
| `/board/{slug}/{id}/edit` | 게시글 수정 | dispBoardWrite (edit) |
| `/board/{slug}/{id}/delete` | 게시글 삭제 | dispBoardDelete |
| `/board/{slug}/{id}/comment` | 댓글 작성 | procBoardInsertComment |

---

## 파일 구조

```
resources/views/admin/site/
├── boards.php                  # 게시판 목록 (완료)
├── boards-create.php           # 게시판 생성 (=기본설정 폼)
├── boards-edit.php             # 게시판 설정 (탭 컨테이너)
├── boards-edit-basic.php       # 기본 설정 탭
├── boards-edit-categories.php  # 분류 관리 탭
├── boards-edit-permissions.php # 권한 관리 탭
├── boards-edit-list.php        # 목록 설정 탭
├── boards-edit-advanced.php    # 고급 설정 탭
├── boards-edit-js.php          # 설정 페이지 JS
└── boards-api.php              # 게시판 CRUD API

skins/board/
├── default/                    # 기본 게시판 스킨 (Tailwind 반응형)
│   ├── list.php               # 목록
│   ├── view.php               # 상세
│   ├── write.php              # 작성/수정
│   └── comment.php            # 댓글 컴포넌트
└── gallery/                    # 갤러리형 스킨 (추후)

resources/lang/{locale}/
└── boards.php                  # 게시판 번역 (별도 파일, 13개 언어)
```

---

## 개발 단계

### Phase 1: 관리자 - 게시판 CRUD + 기본설정
1. DB 테이블 확장 (`rzx_boards` ALTER + `rzx_board_categories` CREATE)
2. 게시판 생성 페이지 (boards-create.php) — 기본설정 폼
3. 게시판 설정 페이지 — 탭 컨테이너 (boards-edit.php) + 기본설정 탭
4. 게시판 API (생성/수정/삭제/복사) — boards-api.php
5. 번역 파일 분리 (boards.php) 13개 언어

### Phase 2: 관리자 - 나머지 설정 탭
6. 분류 관리 탭 + `rzx_board_categories` CRUD
7. 권한 관리 탭
8. 목록 설정 탭 (컬럼 이동 UI)
9. 고급 설정 탭 (Rhymix 고급설정 전체 반영)

### Phase 3: 고객용 게시판 (프론트)
10. `rzx_board_posts` + `rzx_board_comments` + `rzx_board_files` 테이블
11. 스킨 시스템 + 기본 스킨 (skins/board/default/)
12. 게시글 목록/상세 (조회수, 공지, 비밀글, 카테고리 필터)
13. 게시글 작성/수정/삭제 (길이 제한, 보호 로직)
14. 댓글 CRUD (대댓글, 비밀 댓글, 삭제 메시지 처리)
15. 파일 첨부

### Phase 4: 부가 기능
16. 검색 (제목/내용/작성자/태그)
17. 추천/비추천 + 추천인 목록
18. 익명 글/댓글 ($NUM 패턴 닉네임)
19. 상담 기능 (자기 글만 보임)
20. 휴지통 관리
21. 관리자 메일 알림
