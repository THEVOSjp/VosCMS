# 레이아웃 시스템 (Layout System)

RezlyX의 사이트 전체 레이아웃과 스킨을 관리하는 시스템.

## 개요

레이아웃 관리 페이지에서 4개 카테고리의 스킨/레이아웃을 선택하여 사이트 전체에 적용한다.

| 카테고리 | DB 키 | 스킨 디렉토리 | 설명 |
|---------|-------|-------------|------|
| 레이아웃 | `site_layout` | `skins/layouts/{name}/` | 사이트 전체 레이아웃 (헤더+콘텐츠+푸터) |
| 페이지 | `site_page_skin` | `skins/page/{name}/` | 문서/위젯/외부 페이지 스킨 |
| 게시판 | `site_board_skin` | `skins/board/{name}/` | 게시판 목록/읽기/쓰기 스킨 |
| 회원 | `site_member_skin` | `skins/member/{name}/` | 로그인/회원가입/마이페이지 스킨 |

## 디렉토리 구조

```
skins/
├── layouts/
│   └── default/
│       ├── layout.json      # 레이아웃 메타 정보
│       ├── main.php         # 레이아웃 템플릿
│       └── thumbnail.png    # 미리보기 썸네일
├── page/
│   └── default/
│       ├── skin.json        # 페이지 스킨 메타 + 확장 변수
│       └── thumbnail.png
├── board/
│   └── default/
│       ├── skin.json
│       ├── _list-table.php  # 목록형
│       ├── _list-card.php   # 카드형
│       ├── _list-gallery.php
│       ├── _list-webzine.php
│       ├── _list-notices.php
│       └── thumbnail.png
└── member/
    ├── default/
    └── modern/
```

## 설정 파일

### layout.json (레이아웃)

```json
{
  "title": { "ko": "기본 레이아웃", "en": "Default Layout" },
  "description": { "ko": "헤더 + 콘텐츠 + 푸터 기본 구조", ... },
  "version": "1.0.0",
  "author": { "name": "RezlyX" }
}
```

### skin.json (스킨)

```json
{
  "title": { "ko": "기본 페이지 스킨", ... },
  "description": { ... },
  "version": "1.0.0",
  "date": "2026-03-21",
  "thumbnail": "thumbnail.png",
  "author": { "name": "RezlyX", "url": "...", "email": "..." },
  "vars": [ ... ]
}
```

`vars`의 상세 스펙은 `PAGE_SYSTEM.md`의 스킨 시스템 섹션 참조.

## 관리자 페이지

### 레이아웃 관리 (`admin/site/design.php`)

3단 구조:

```
┌──────────────┬───────────────┬──────────────┐
│ 사이트 미리보기│ 스킨 목록      │ 상세 설정     │
│ (iframe 축소) │               │ (준비 중)     │
├──────────────┤ ○ 사용 안 함   │              │
│ 레이아웃 [..] │ ＋ 다른 설치   │              │
│ 페이지 [..]  │               │              │
│ 게시판 [..]  │ ◉ 기본 레이아웃│              │
│ 회원 [..]    │   [썸네일]     │              │
├──────────────┤   상세 설정    │              │
│ [레이아웃 저장]│   복사본 생성  │              │
│              │   삭제        │              │
└──────────────┴───────────────┴──────────────┘
```

**좌측**: 사이트 미리보기 + 4개 메뉴
- 초기 상태: 미리보기 + 메뉴만 표시
- 메뉴 클릭: 우측에 해당 스킨 목록 표시
- 같은 메뉴 재클릭: 패널 닫기

**중앙 (스킨 목록)**:
- 사용 안 함
- 다른 스킨 설치 (준비 중)
- 설치된 스킨: 라디오 + 썸네일 + 액션 링크
- 선택 시 노란 배경 + 좌측 메뉴 현재값 업데이트

**우측 (상세 설정)**:
- 스킨의 상세 설정 클릭 시 표시
- SkinConfigRenderer 기반 설정 폼 (향후 구현)

### 각 스킨 항목 액션

| 액션 | 설명 | 상태 |
|------|------|------|
| 상세 설정 | 우측에 스킨 설정 패널 표시 | 준비 중 |
| 복사본 생성 | 스킨 디렉토리 복제 | 준비 중 |
| 삭제 | 스킨 디렉토리 삭제 (default 제외) | 준비 중 |

## DB 저장

`rzx_settings` 테이블:

| 키 | 값 | 설명 |
|----|---|------|
| `site_layout` | `default` | 사이트 레이아웃 |
| `site_page_skin` | `default` | 페이지 기본 스킨 |
| `site_board_skin` | `default` | 게시판 기본 스킨 |
| `site_member_skin` | `default` | 회원 기본 스킨 |

값이 `none`이면 해당 스킨을 사용하지 않음.

## 스킨 적용 우선순위

개별 페이지/게시판에서 스킨을 지정하면 해당 설정이 우선.
미지정 시 레이아웃 관리의 전체 설정값 사용.

```
개별 설정 (page_config_{slug}.skin) > 전체 설정 (site_page_skin) > 기본값 (default)
```

## 스킨 스캔

관리자 페이지 진입 시 `skins/` 디렉토리를 스캔하여 설치된 스킨 목록을 자동 로드.

```php
function scanSkins($dir, $type, $baseUrl, $locale) {
    // layout.json 또는 skin.json 읽어서 메타 정보 추출
    // thumbnail.png 존재 시 썸네일 URL 생성
}
```

## SkinConfigRenderer

`skin.json`의 `vars` 배열을 읽어 설정 폼을 자동 생성하는 공용 렌더러.

- 지원 타입: text, textarea, checkbox, select, radio, color, number, image, video
- `depends_on`: 필드 간 의존 관계 선언적 정의
- `$baseUrl` 파라미터로 이미지/비디오 미리보기 경로 지원

상세 스펙은 `PAGE_SYSTEM.md` 참조.

## 향후 계획

- [ ] 상세 설정 패널: SkinConfigRenderer 기반 인라인 설정
- [ ] 복사본 생성: 스킨 디렉토리 복제 + 자동 slug 생성
- [ ] 삭제: 확인 모달 + 디렉토리 삭제
- [ ] 스킨 마켓플레이스: 외부 스킨 설치/업데이트
- [ ] 스킨 내보내기/가져오기: ZIP 패키징
