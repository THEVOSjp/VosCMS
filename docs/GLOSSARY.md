# VosCMS 용어 사전 (Glossary)

> **VosCMS** — Value Of Style CMS
> 브랜드 계보: THE VOS (THE Value Of Style) → VosCMS
> 도메인: voscms.com
>
> 코드, 문서, UI, 대화에서 이 용어를 일관되게 사용합니다.
> 최종 업데이트: 2026-04-05

---

## 사용자에게 보이는 용어 (UI/문서)

| 용어 | 영어 | 일본어 | 설명 |
|------|------|--------|------|
| **코어** | Core | コア | CMS 본체. 설치하면 바로 동작하는 기본 시스템 |
| **모듈** | Module | モジュール | 코어에 내장된 기능 단위. 제거 불가. 자체 URL/DB 보유 |
| **플러그인** | Plugin | プラグイン | 코어 위에 설치/제거 가능한 확장 기능. `plugins/`에 설치 |
| **위젯** | Widget | ウィジェット | 페이지에 배치하는 UI 블록. 드래그&드롭 배치 |
| **스킨** | Skin | スキン | 모듈/플러그인의 프론트 디자인. 기능은 그대로, 외형만 변경 |
| **레이아웃** | Layout | レイアウト | 사이트 전체 프레임 (헤더/사이드바/푸터) |
| **테마** | Theme | テーマ | 레이아웃 + 스킨 + 컬러셋 묶음. 마켓플레이스 배포 단위 |

---

## 개발자만 사용하는 용어 (코드 내부)

| 용어 | 설명 | 예시 |
|------|------|------|
| **컴포넌트** (Component) | 재사용 가능한 UI 부품. 단독 동작 안 함 | 파일 업로더, 에디터, 페이지네이션 |
| **헬퍼** (Helper) | 유틸리티 함수 모음 | `formatDate()`, `escape()` |
| **서비스** (Service) | 비즈니스 로직 클래스 | `MailService`, `CompanyService` |
| **미들웨어** (Middleware) | 요청 전/후 처리 | 인증 체크, CSRF 검증 |
| **훅** (Hook) | 코어 이벤트에 플러그인이 개입하는 포인트 | `admin.sidebar.render` |
| **마이그레이션** (Migration) | DB 스키마 변경 SQL 파일 | `001_create_tables.sql` |

---

## 사용하지 않는 용어

| 금지 용어 | 대신 사용 | 이유 |
|-----------|----------|------|
| ~~애드온 (Addon)~~ | 플러그인 | 용어 통일 |
| ~~확장 (Extension)~~ | 플러그인 | 용어 통일 |
| ~~패키지 (Package)~~ | 사용 안 함 | composer와 혼동 |
| ~~번들 (Bundle)~~ | 사용 안 함 | webpack과 혼동 |
| ~~앱 (App)~~ | 사용 안 함 | 모바일 앱과 혼동 |

---

## 코어 내부 구조

```
코어 (Core) ─── 설치 시 기본 포함, 제거 불가
  │
  ├── 모듈 (Module) ─── 독립 기능 단위, 사용자가 인식
  │     ├── 페이지 모듈    /page/*     rzx_page_*
  │     ├── 게시판 모듈    /board/*    rzx_board_*
  │     ├── 회원 모듈      /member/*   rzx_users
  │     ├── 메뉴 모듈      (관리자)     rzx_menu_*
  │     └── 위젯 모듈      (관리자)     rzx_widgets
  │
  └── 컴포넌트 (Component) ─── 공유 부품, 개발자만 인식
        ├── 파일 업로더
        ├── 에디터 (Summernote)
        ├── 페이지네이션
        ├── 다국어 셀렉터
        └── 알림/모달 (tmsAlert/tmsConfirm)
```

> **모듈과 컴포넌트 모두 코어의 일부.** 사용자에게는 그냥 "코어 기능"이고,
> 개발자가 코드 관리를 위해 내부적으로 구분하는 개념.

---

## 모듈 vs 플러그인 구분법

| | 모듈 | 플러그인 |
|---|---|---|
| 코어에 포함 | O | X |
| 제거 가능 | X | O |
| 마켓플레이스 배포 | X | O |
| 독자 버전 관리 | X (코어와 동일) | O (`plugin.json`) |
| 위치 | 코어 내부 | `plugins/` |
| 예시 | 페이지, 게시판, 회원 | POS, 예약, 근태, 키오스크 |

---

## 모듈 vs 컴포넌트 구분법

| | 모듈 | 컴포넌트 |
|---|---|---|
| 자체 URL | O (`/board/*`) | X |
| 자체 DB 테이블 | O | X |
| 단독 동작 | O | X |
| 여러 곳에서 재사용 | X | O |
| 사용자에게 보임 | O (메뉴 표시) | X (내부 부품) |
| 제거 시 영향 | 해당 기능 사라짐 | 여러 곳 동시에 깨짐 |

---

## 디렉토리 구조와 용어 매핑

```
rezlyx/
  rzxlib/              ← 코어 라이브러리 (서비스, 헬퍼, 미들웨어)
  resources/
    views/
      admin/
        components/    ← 컴포넌트 (재사용 UI 부품)
        site/          ← 코어 모듈 관리 (페이지, 메뉴, 게시판, 위젯)
        members/       ← 코어 모듈 (회원)
        pos/           ← → 플러그인으로 이전 예정
  plugins/             ← 플러그인 (설치/제거 가능)
    rezlyx-pos/
      plugin.json
    rezlyx-reservation/
      plugin.json
  widgets/             ← 위젯
  skins/               ← 스킨
    default/
      board/
      member/
  layouts/             ← 레이아웃
  themes/              ← 테마 (레이아웃+스킨 묶음)
```

---

## 마켓플레이스 카테고리

```
마켓플레이스
  ├── 플러그인    (기능 확장)
  ├── 테마       (디자인 묶음)
  ├── 위젯       (페이지 블록)
  └── 스킨       (개별 디자인)
```

---

## DB 테이블 매핑

| 용어 | 테이블 |
|------|--------|
| 플러그인 | `rzx_plugins` |
| 플러그인 설정 | `rzx_plugin_settings` |
| 플러그인 마이그레이션 | `rzx_plugin_migrations` |
| 위젯 | `rzx_widgets`, `rzx_page_widgets` |
| 스킨 | 테이블 없음 (파일 기반) |
| 레이아웃 | 테이블 없음 (파일 기반) |
| 테마 | `rzx_themes` (향후) |

---

## 코드 네이밍 규칙

```php
// 모듈 — 코어에 직접 작성
$router->get('/board/{slug}', ...);

// 플러그인 — PluginManager 통해 등록
Plugin::load('rezlyx-pos');
Plugin::isActive('rezlyx-pos');

// 컴포넌트 — include로 재사용
include __DIR__ . '/components/file-uploader.php';

// 서비스 — 클래스로 호출
CompanyService::get();
MailService::send(...);

// 훅 — 이벤트 포인트
Hook::trigger('admin.sidebar.render');
```
