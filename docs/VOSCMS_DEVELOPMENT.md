# VosCMS 개발 계획서

> **VosCMS** — Value Of Style CMS
> 브랜드 계보: THE VOS (THE Value Of Style) → VosCMS
> 도메인: voscms.com
> 작성일: 2026-04-05

---

## 1. 개요

### 1-1. 배경
RezlyX는 예약/POS/키오스크에 특화된 시스템으로 성장하였으나, 그 과정에서 범용 CMS 기능(페이지, 게시판, 회원, 메뉴, 위젯, 다국어, 스킨)이 함께 구축되었다.
이 범용 CMS 기능을 분리하여 독립 CMS 제품으로 만들고, 예약/POS 등 특화 기능은 플러그인으로 배포하는 구조로 전환한다.

### 1-2. 목표
- 일반적인 홈페이지 제작을 위한 범용 CMS 코어 확립
- 플러그인 아키텍처로 무한 기능 확장
- 마켓플레이스를 통한 플러그인/테마 배포 생태계 구축

### 1-3. 브랜드
| 항목 | 값 |
|------|-----|
| 이름 | VosCMS |
| 풀네임 | Value Of Style CMS |
| 도메인 | voscms.com |
| 원래 브랜드 | THE VOS (THE Value Of Style) |
| 의미 | 당신의 스타일에 가치를 더하는 CMS |

---

## 1.5. VosCMS와 RezlyX의 관계

### 현재 vs 미래
```
현재:  RezlyX = CMS + 예약 + POS + 키오스크 + 근태 (전부 하나)

미래:  VosCMS = 범용 CMS 코어 (독립 제품)
       RezlyX = VosCMS + 예약/POS/키오스크 플러그인 (번들 제품)
```

### 포지셔닝
| 제품 | 대상 | 설명 |
|------|------|------|
| **VosCMS** | CMS만 필요한 사용자 | 기업 홈페이지, 블로그, 커뮤니티 등 범용 |
| **RezlyX** | 서비스업 (살롱/클리닉 등) | VosCMS + 예약/POS/키오스크 미리 설치된 패키지 |

### 비유
| 관계 | 예시 |
|------|------|
| VosCMS : RezlyX | WordPress : WooCommerce 포함 WordPress |
| VosCMS : RezlyX | Android OS : Samsung Galaxy |

### 판매 모델
```
VosCMS (무료/오픈소스)              ← 사용자 확보, 생태계 성장
  + 예약 플러그인 (유료)             ← 수익
  + POS 플러그인 (유료)              ← 수익
  + 키오스크 플러그인 (유료)          ← 수익
  + 프리미엄 테마 (유료)             ← 수익
  + RezlyX 번들 (유료 올인원 패키지)  ← 핵심 수익
```

### RezlyX는 사라지지 않는다
- VosCMS는 **엔진**, RezlyX는 **완성차**
- 쇼핑몰 플러그인을 설치하면 쇼핑몰이 되고, 예약 플러그인을 설치하면 RezlyX가 되는 구조
- RezlyX는 "VosCMS 기반 예약 특화 솔루션"이라는 더 강력한 포지션을 갖게 됨

---

## 2. 아키텍처

### 2-1. 전체 구조
```
VosCMS
  ├── 코어 (Core) ─── 제거 불가, 기본 기능
  │     ├── 모듈 (Module) ─── 독립 기능 단위
  │     │     ├── 페이지 모듈
  │     │     ├── 게시판 모듈
  │     │     ├── 회원 모듈
  │     │     ├── 메뉴 모듈
  │     │     └── 위젯 모듈
  │     └── 컴포넌트 (Component) ─── 공유 부품
  │           ├── 파일 업로더
  │           ├── 에디터
  │           ├── 페이지네이션
  │           └── 모달/알림
  │
  ├── 플러그인 (Plugin) ─── 설치/제거 가능, 마켓플레이스 배포
  │     ├── 예약 플러그인
  │     ├── POS 플러그인
  │     ├── 키오스크 플러그인
  │     ├── 근태 관리 플러그인
  │     └── (사용자/서드파티 플러그인)
  │
  ├── 위젯 (Widget) ─── 페이지 배치 UI 블록
  ├── 스킨 (Skin) ─── 모듈/플러그인 프론트 디자인
  ├── 레이아웃 (Layout) ─── 사이트 전체 프레임
  └── 테마 (Theme) ─── 레이아웃 + 스킨 묶음
```

### 2-2. 의존성 방향 (핵심 원칙)
```
플러그인 → 코어    (O) 플러그인이 코어를 호출
코어 → 플러그인    (X) 코어는 절대 플러그인을 참조하지 않음
```
이 원칙을 지키면 물리적 분리는 기계적 작업이 된다.

---

## 3. 용어 정의

> 상세 내용은 [GLOSSARY.md](GLOSSARY.md) 참조

### 사용자에게 보이는 용어
| 용어 | 영어 | 설명 |
|------|------|------|
| 코어 | Core | CMS 본체 |
| 모듈 | Module | 코어 내장 기능 단위 (제거 불가) |
| 플러그인 | Plugin | 설치/제거 가능한 확장 기능 |
| 위젯 | Widget | 페이지 배치 UI 블록 |
| 스킨 | Skin | 프론트 디자인 |
| 레이아웃 | Layout | 사이트 전체 프레임 |
| 테마 | Theme | 레이아웃 + 스킨 묶음 |

### 개발자 전용 용어
컴포넌트, 헬퍼, 서비스, 미들웨어, 훅, 마이그레이션

### 사용하지 않는 용어
~~애드온~~, ~~확장~~, ~~패키지~~, ~~번들~~, ~~앱~~ → 모두 "플러그인"으로 통일

---

## 4. 플러그인 시스템

### 4-1. plugin.json 스펙

```json
{
  "id": "vos-pos",
  "name": {
    "ko": "POS 시스템",
    "en": "POS System",
    "ja": "POSシステム"
  },
  "description": {
    "ko": "매장 판매/결제 관리 시스템",
    "en": "Point of Sale management system"
  },
  "version": "1.0.0",
  "author": {
    "name": "VosCMS",
    "url": "https://voscms.com"
  },
  "license": "proprietary",
  "category": "business",
  "tags": ["pos", "payment", "sales"],
  "thumbnail": "thumbnail.png",

  "requires": {
    "cms": ">=2.0.0",
    "php": ">=8.1",
    "plugins": ["vos-reservation"]
  },

  "routes": {
    "admin": [
      { "path": "pos", "view": "pos.php", "title": "POS" },
      { "path": "pos/settings", "view": "pos-settings.php" }
    ],
    "front": [
      { "path": "pos/kiosk", "view": "kiosk.php" }
    ],
    "api": [
      { "path": "pos/api/checkout", "view": "api-checkout.php", "method": "POST" }
    ]
  },

  "menus": {
    "admin": [
      {
        "section": "business",
        "icon": "cash-register",
        "items": [
          { "title": { "ko": "POS" }, "route": "pos" },
          { "title": { "ko": "POS 설정" }, "route": "pos/settings" }
        ]
      }
    ]
  },

  "migrations": [
    "database/001_create_pos_tables.sql"
  ],

  "hooks": {
    "admin.sidebar.render": "hooks/sidebar.php",
    "admin.dashboard.widgets": "hooks/dashboard-widget.php",
    "member.delete.before": "hooks/cleanup-member.php"
  },

  "settings": {
    "view": "settings.php",
    "defaults": {
      "currency": "JPY",
      "tax_rate": 10
    }
  },

  "assets": {
    "css": ["assets/pos.css"],
    "js": ["assets/pos.js"]
  }
}
```

### 4-2. 플러그인 디렉토리 구조
```
plugins/
  vos-pos/
    plugin.json
    thumbnail.png
    views/
    hooks/
    database/
    assets/
    lang/
```

### 4-3. 플러그인 라이프사이클
1. **설치** — plugin.json 검증 → 의존성 확인 → 마이그레이션 실행 → 활성화
2. **활성화** — 라우트 등록, 메뉴 주입, 훅 연결
3. **비활성화** — 라우트/메뉴/훅 해제 (데이터 유지)
4. **삭제** — DB 테이블 삭제 (선택), 파일 제거

### 4-4. 훅 시스템
초기에는 단순 include 방식으로 시작, 필요 시 이벤트 디스패처로 확장.
```php
// 코어에서 훅 호출
Hook::trigger('admin.sidebar.render', $context);

// 플러그인에서 훅 등록 (plugin.json의 hooks 섹션)
// hooks/sidebar.php가 자동 include됨
```

### 4-5. 의존성 처리
- `requires.plugins`에 명시된 플러그인 없으면 → 설치 차단 + 메시지
- `requires.cms` 버전 미달이면 → 설치 차단

### 4-6. 마이그레이션 추적
- `rzx_plugin_migrations` 테이블에 실행 기록
- 플러그인 업데이트 시 미실행 파일만 순차 실행

---

## 5. 디렉토리 구조

### 5-1. 코어
```
voscms/
  index.php              ← 엔트리 포인트
  rzxlib/                ← 코어 라이브러리
    Core/
      Auth/              ← 인증
      Skin/              ← 스킨 시스템
      Plugin/            ← PluginManager (신규)
        PluginManager.php
        Hook.php
  config/                ← 설정
  database/              ← 마이그레이션
  resources/
    views/
      admin/             ← 관리자 UI
        components/      ← 컴포넌트 (재사용 부품)
        site/            ← 코어 모듈 관리
        members/         ← 회원 모듈
      customer/          ← 프론트
        board/           ← 게시판 모듈
        member/          ← 회원 모듈
    lang/                ← 다국어
  plugins/               ← 플러그인 (설치/제거 가능)
  widgets/               ← 위젯
  skins/                 ← 스킨
  layouts/               ← 레이아웃
  themes/                ← 테마
  storage/               ← 업로드/캐시
```

### 5-2. 플러그인 예시
```
plugins/
  vos-reservation/       ← 예약 플러그인
    plugin.json
    views/
    hooks/
    database/
    assets/
    lang/
  vos-pos/               ← POS 플러그인
    plugin.json
    ...
  vos-kiosk/             ← 키오스크 플러그인
  vos-attendance/        ← 근태 관리 플러그인
```

---

## 6. DB 테이블

### 6-1. 플러그인 관리 (신규)
```sql
-- 설치된 플러그인
CREATE TABLE rzx_plugins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plugin_id VARCHAR(100) NOT NULL UNIQUE,  -- 'vos-pos'
  title VARCHAR(200),
  version VARCHAR(20),
  is_active TINYINT(1) DEFAULT 1,
  installed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 플러그인 마이그레이션 추적
CREATE TABLE rzx_plugin_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plugin_id VARCHAR(100) NOT NULL,
  migration_file VARCHAR(255) NOT NULL,
  executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (plugin_id, migration_file)
);

-- 플러그인 설정
CREATE TABLE rzx_plugin_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plugin_id VARCHAR(100) NOT NULL,
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT,
  UNIQUE KEY (plugin_id, setting_key)
);
```

---

## 7. 개발 로드맵

### Phase 1: 플러그인 인프라 구축
- [ ] `PluginManager` 클래스 (로드/활성화/비활성화/설치/삭제)
- [ ] `Hook` 클래스 (이벤트 트리거/리스너)
- [ ] `rzx_plugins` + `rzx_plugin_migrations` + `rzx_plugin_settings` 테이블
- [ ] 코어 라우터에 플러그인 라우트 자동 등록
- [ ] 관리자 사이드바에 플러그인 메뉴 자동 주입
- [ ] 관리자 > 플러그인 관리 페이지

### Phase 2: 첫 번째 플러그인 전환
- [ ] POS를 `plugins/vos-pos/`로 이전 (가장 독립적)
- [ ] plugin.json 작성
- [ ] 기존 코드에서 POS 직접 참조 제거
- [ ] 플러그인 설치/제거 테스트

### Phase 3: 나머지 플러그인 전환
- [ ] 근태 관리 → `plugins/vos-attendance/`
- [ ] 예약 시스템 → `plugins/vos-reservation/` (코어와 가장 밀접, 마지막)
- [ ] 키오스크 → `plugins/vos-kiosk/`

### Phase 4: 마켓플레이스
- [ ] 플러그인 패키징 (zip)
- [ ] 마켓플레이스 API (목록/검색/다운로드/설치/업데이트)
- [ ] 테마 마켓플레이스
- [ ] 라이선스/과금 (무료/유료/구독)
- [ ] 리뷰/평점

### Phase 5: 코어 분리
- [ ] VosCMS 코어를 별도 Git 레포로 분리
- [ ] RezlyX = VosCMS + 예약/POS/키오스크 플러그인 번들
- [ ] voscms.com 사이트 구축

---

## 8. 개발 관리 전략

### 8-1. 당분간 모노레포 유지
지금 바로 물리적 분리하면 개발 속도 저하. 대신:
- 새 기능 추가 시 "코어인가 플러그인인가" 항상 판단
- 의존성 방향 준수 (플러그인 → 코어 O, 코어 → 플러그인 X)
- 플러그인 인터페이스 확정 후 점진적 이전

### 8-2. Git 브랜치 전략
- `main` — 현재 RezlyX (통합)
- `cms-core` — CMS 코어 분리 준비 브랜치
- 플러그인은 분리 시점에 별도 레포로 이동

### 8-3. 플러그인 전환 우선순위
| 순위 | 플러그인 | 이유 |
|------|----------|------|
| 1 | POS | 가장 독립적, `_init.php`로 격리됨 |
| 2 | 근태 관리 | 스태프 모듈에 종속되나 분리 가능 |
| 3 | 키오스크 | POS 의존 |
| 4 | 예약 시스템 | 코어와 가장 밀접, 마지막 |

---

## 9. 마켓플레이스 카테고리

```
VosCMS 마켓플레이스
  ├── 플러그인
  │     ├── 비즈니스 (예약, POS, 쇼핑몰, 결제)
  │     ├── 커뮤니티 (포럼, SNS, 채팅)
  │     ├── 마케팅 (SEO, 분석, 메일링)
  │     └── 유틸리티 (백업, 보안, 캐시)
  ├── 테마
  │     ├── 기업/비즈니스
  │     ├── 쇼핑몰
  │     ├── 블로그/매거진
  │     └── 포트폴리오
  ├── 위젯
  └── 스킨
```

---

## 10. 참고 문서

- [GLOSSARY.md](GLOSSARY.md) — 용어 사전
- [BOARD_SYSTEM.md](BOARD_SYSTEM.md) — 게시판 시스템
- [WIDGET_BUILDER.md](WIDGET_BUILDER.md) — 위젯 빌더
- [PAGE_SYSTEM.md](PAGE_SYSTEM.md) — 페이지 시스템
- [POS_SYSTEM.md](POS_SYSTEM.md) — POS 시스템
- [RESERVATION_SYSTEM.md](RESERVATION_SYSTEM.md) — 예약 시스템
- [ATTENDANCE_SYSTEM.md](ATTENDANCE_SYSTEM.md) — 근태 관리
