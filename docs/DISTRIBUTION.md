# VosCMS 배포 버전 구성 문서

> 작성일: 2026-04-12 / 최종 수정: 2026-04-12
> VosCMS 코어 배포 패키지의 구성, 포함/제외 항목, 설치 요구사항을 정리한 문서.
> 관련 문서: DEPLOYMENT_PLAN.md, LICENSE_MARKETPLACE_SYSTEM.md

---

## 0. 테스트 서버 정보

### test.21ces.com (설치 테스트용)

| 항목 | 값 |
|------|-----|
| **URL** | http://test.21ces.com |
| **서버** | 27.81.39.11 (vos.21ces.com과 동일 서버) |
| **웹루트** | `/var/www/voscms-dist/voscms-2.1.0/` |
| **Nginx** | `/etc/nginx/sites-available/voscms-test` |
| **DB Host** | `127.0.0.1` |
| **DB Port** | `3306` |
| **DB Name** | `voscms_test` |
| **DB User** | `rezlyx` |
| **DB Pass** | `rezlyx2026!` |
| **DB Prefix** | `rzx_` |
| **PHP** | 8.3.6 (FPM) |

### 배포 디렉토리 구조

```
/var/www/voscms-dist/
  ├── voscms-2.1.0/              ← 배포 파일 (test.21ces.com 웹루트)
  ├── voscms-2.1.0.zip           ← 배포 ZIP (난독화 적용)
  ├── download/
  │   ├── voscms-2.1.0.zip       ← 심볼릭 링크
  │   └── voscms-latest.zip      ← 심볼릭 링크 (항상 최신)
  └── obfuscate.php              ← 난독화 빌드 스크립트
```

### 다운로드 URL

```
https://vos.21ces.com/download/voscms-latest.zip    ← 항상 최신 버전
https://vos.21ces.com/download/voscms-2.1.0.zip     ← 특정 버전
```

### 개발 서버에서 다운로드

```bash
# dev.21ces.com 또는 salon.21ces.com에서
wget https://vos.21ces.com/download/voscms-latest.zip
unzip voscms-latest.zip

# rezlyx.com (StarServer)에서
ssh -p 10022 ss819716@sv10005.star.ne.jp
wget https://vos.21ces.com/download/voscms-latest.zip
```

---

## 1. 배포 개요

```
VosCMS v2.1.0 (Marketplace)
├── 무료 오픈소스 CMS 코어
├── 13개국어 기본 지원
├── 22개 위젯 + 2개 게시판 스킨 + 3개 레이아웃
├── 자동 설치 플러그인 (마켓플레이스) 내장
├── 라이선스 클라이언트 내장 (서버 인증)
├── 코드 난독화 적용 (핵심 파일)
└── install.php 설치 마법사
```

---

## 2. 배포 파일 구조

```
voscms-2.1.0/
│
├── index.php                    ← 메인 엔트리 (라우터)
├── install.php                  ← 설치 마법사 (난독화 대상)
├── version.json                 ← 버전 정보
├── .env.example                 ← 환경변수 템플릿
├── composer.json / composer.lock
├── package.json
│
├── rzxlib/                      ← 코어 라이브러리 (핵심 파일 난독화)
│   ├── Core/
│   │   ├── Auth/                ← 인증 (AdminAuth, Auth, SessionGuard)
│   │   ├── Database/            ← DB (Connection, QueryBuilder)
│   │   ├── Http/                ← 컨트롤러, 요청, 응답
│   │   ├── I18n/                ← 다국어 (Translator)
│   │   ├── License/             ← 라이선스 클라이언트 (난독화 — 비밀키 보호)
│   │   ├── Modules/             ← 위젯 (WidgetLoader, WidgetRenderer)
│   │   ├── Plugin/              ← 플러그인 (PluginManager, Hook)
│   │   ├── Routing/             ← 라우터
│   │   ├── Skin/                ← 스킨 설정
│   │   ├── Updater/             ← 업데이트 체커
│   │   └── Helpers/             ← 유틸리티 함수
│   ├── Modules/Payment/         ← 결제 (Stripe)
│   └── Reservation/Models/      ← 예약 모델
│
├── resources/
│   ├── views/admin/             ← 관리자 패널 뷰 (평문)
│   ├── views/customer/          ← 프론트엔드 뷰 (평문)
│   ├── views/developer/         ← 개발자 포털 뷰
│   ├── views/marketplace/       ← 공개 마켓플레이스 뷰
│   ├── lang/{13개 언어}/         ← 다국어 파일 (평문)
│   └── css/                     ← 스타일시트
│
├── plugins/
│   └── vos-marketplace/         ← 자동 설치 플러그인 (src/ 난독화)
│
├── widgets/ (22개)              ← 위젯 (평문 — 개발 허용)
├── skins/                       ← 스킨 (평문 — 개발 허용)
│   ├── board/default/           ← 기본 게시판 스킨
│   ├── board/faq/               ← FAQ 아코디언 스킨
│   └── layouts/{default,minimal,modern}/
│
├── api/                         ← 라이선스/개발자/공지 API
│   ├── license/                 ← 라이선스 서버 API
│   ├── developer/               ← 개발자 API
│   └── notices.php              ← 공지사항 공개 API
│
├── database/migrations/core/    ← 코어 DB 마이그레이션
├── vendor/                      ← Composer 의존성
├── assets/                      ← 프론트엔드 에셋
└── storage/                     ← 런타임 저장소
```

---

## 3. 배포 포함 / 제외

### 3.1 배포에 포함

| 항목 | 설명 |
|------|------|
| `index.php` | 메인 엔트리 |
| `install.php` | 설치 마법사 (난독화) |
| `version.json` | 버전 정보 |
| `.env.example` | 환경변수 템플릿 |
| `rzxlib/` | 코어 라이브러리 (핵심 파일 난독화) |
| `resources/` | 뷰, 다국어, CSS (평문) |
| `plugins/vos-marketplace/` | 자동 설치 플러그인 |
| `widgets/` | 22개 위젯 (평문) |
| `skins/` | 2개 게시판 스킨 + 3개 레이아웃 (평문) |
| `api/` | 라이선스/개발자/공지 API |
| `database/` | 마이그레이션 + 시드 |
| `vendor/` | Composer 의존성 |

### 3.2 배포에서 제외

| 항목 | 이유 |
|------|------|
| `.env` | 설치 시 자동 생성 |
| `.git/`, `.claude/` | 개발 도구 |
| `node_modules/` | 개발 의존성 |
| `storage/.installed` | 설치 시 생성 |
| `storage/.license_cache` | 런타임 생성 |
| `plugins/vos-license-manager/` | 본사 전용 |
| `plugins/vos-shop/` | 마켓에서 별도 설치 |
| `docs/` | 내부 개발 문서 |

### 3.3 코드 난독화 대상

**방식:** PHP 자체 난독화 (obfuscate.php) — 주석/공백 제거 + 비밀키 base64 인코딩

| 파일 | 이유 |
|------|------|
| `install.php` | localhost 차단 + 라이선스 서버 통신 |
| `rzxlib/Core/License/LicenseClient.php` | 도메인 해시 비밀키 보호 |
| `rzxlib/Core/License/LicenseStatus.php` | 라이선스 상태 로직 |
| `rzxlib/Core/Auth/AdminAuth.php` | 관리자 인증 |
| `rzxlib/Core/Auth/Auth.php` | 회원 인증 + Remember Me |
| `rzxlib/Core/Auth/SessionGuard.php` | 세션 가드 |
| `rzxlib/Core/Plugin/PluginManager.php` | 플러그인 매니저 |
| `rzxlib/Core/Plugin/Hook.php` | 훅 시스템 |
| `plugins/vos-marketplace/src/*.php` | 구매/라이선스/설치 로직 |

**주의:** ionCube Encoder는 사용하지 않음. ionCube Loader도 불필요.

---

## 4. 코어 모듈

| 모듈 | URL 패턴 | DB 테이블 | 설명 |
|------|----------|----------|------|
| **페이지 관리** | `/page/*` | `rzx_page_contents`, `rzx_page_widgets` | 위젯 빌더로 페이지 구성 |
| **메뉴 관리** | 관리자 전용 | `rzx_sitemaps`, `rzx_menu_items` | 메인/푸터/유틸리티 메뉴 |
| **게시판** | `/board/*` | `rzx_boards`, `rzx_board_posts`, `rzx_board_categories` | 다목적 게시판 (스킨 지원) |
| **회원 관리** | `/member/*` | `rzx_users`, `rzx_member_grades` | 가입/로그인/등급/포인트 |
| **위젯** | 관리자 전용 | `rzx_widgets` | 22개 기본 위젯 |
| **다국어** | 자동 | `rzx_translations` | 13개 언어 |
| **설정** | 관리자 전용 | `rzx_settings` | 사이트 전체 설정 |

---

## 5. 기본 위젯 (22개)

| 위젯 | 슬러그 | 버전 | 카테고리 | 설명 |
|------|--------|------|---------|------|
| 히어로 배너 | hero | 1.0.0 | layout | 메인 배너 (배경 이미지/비디오) |
| 히어로 CTA | hero-cta2 | 1.1.0 | layout | 타이핑 애니메이션 + CTA 버튼 |
| 히어로 슬라이더 | hero-slider | 1.1.0 | layout | 이미지 슬라이드 배너 |
| 기능 카드 | features | 1.1.0 | content | 아이콘 + 설명 카드 그리드 |
| 통계 | stats | 1.1.0 | content | 숫자 카운터 |
| 고객 후기 | testimonials | 1.0.0 | content | 후기 슬라이더 |
| 행동 유도 | cta | 1.0.0 | layout | CTA 배너 |
| 예약 유도 | cta001 | 1.0.0 | layout | 예약 유도 배너 |
| 텍스트 | text | 1.0.0 | content | 자유 텍스트 |
| 여백 | spacer | 1.0.0 | layout | 높이 조절 여백 |
| 게시판 콘텐츠 | board-contents | 1.0.0 | content | 게시판 글 목록 표시 |
| 그리드 섹션 | grid-section | 1.0.0 | layout | 다단 그리드 |
| 인기 서비스 | services | 2.0.0 | business | 서비스 목록 |
| 스태프 소개 | staff | 1.0.0 | business | 스태프 카드 |
| 예약하기 | booking | 1.0.0 | business | 예약 폼 |
| 예약 조회 | lookup | 1.0.0 | business | 예약 조회/취소 |
| Before/After | before-after | 1.0.0 | content | 비포/애프터 비교 |
| 비교 슬라이더 | compare-slider | 1.0.0 | content | 이미지 비교 슬라이더 |
| 매장 지도 | shop-map | 1.0.0 | business | 주변 매장 지도 |
| 매장 랭킹 | shop-ranking | 1.0.0 | business | 인기 사업장 |
| 스타일북 | stylebook | 1.0.0 | content | 포트폴리오 갤러리 |
| 기능 투어 | feature-tour | 1.0.0 | content | 기능 소개 슬라이드 |

---

## 6. 기본 스킨/레이아웃

### 게시판 스킨

| 스킨 | 버전 | 설명 |
|------|------|------|
| default | 2.0.0 | 기본 게시판 (테이블/웹진/갤러리/카드 4가지 모드) |
| faq | 1.0.0 | FAQ 아코디언 (Q/A 펼침, 검색, 카테고리 필터) |

### 레이아웃

| 레이아웃 | 설명 |
|---------|------|
| default | 기본 레이아웃 |
| minimal | 미니멀 레이아웃 |
| modern | 모던 레이아웃 (기본 적용) |

---

## 7. 설치 요구사항

### 서버 환경

| 항목 | 요구사항 |
|------|---------|
| **PHP** | 8.1 이상 (8.3 권장) |
| **DB** | MySQL 8.0+ 또는 MariaDB 10.4+ |
| **웹서버** | Nginx 또는 Apache |
| **SSL** | HTTPS 필수 (localhost 설치 불가) |

### 필수 PHP 확장

```
PDO, pdo_mysql, mbstring, json, openssl, curl, fileinfo
```

### 권장 사양

```
CPU: 2 vCPU 이상
RAM: 2GB 이상
Disk: 10GB SSD 이상
```

---

## 8. 설치 프로세스

```
1. 서버에 파일 업로드 (ZIP 다운로드 → 압축 해제)
2. https://your-domain.com/install.php 접속
3. Step 1: 환경 확인 (PHP, 확장모듈, 쓰기 권한)
4. Step 2: DB 연결 설정 (호스트, DB명, 사용자, 비밀번호, 접두사)
5. Step 3: 테이블 생성 (코어 마이그레이션 실행)
6. Step 4: 관리자 계정 + 사이트 설정 (이름, URL, 언어, 시간대)
7. Step 5: 라이선스 등록 (라이선스 서버에서 키 자동 발급)
8. Step 6: 설치 완료
   - .env 파일 자동 생성 (LICENSE_KEY 포함)
   - 기본 메뉴/페이지/게시판 생성
   - 홈 페이지 위젯 배치 (hero-cta2 + stats + features)
   - 샘플 게시글 생성 (공지/자유/Q&A/FAQ — 13개국어 번역 포함)
   - install.php 비활성화 (storage/.installed 플래그)
```

### 라이선스 자동 등록

- 설치 시: install.php → 라이선스 서버에 도메인 전송 → 서버에서 키 생성 → .env에 저장
- 기존 사이트 업그레이드 시: 관리자 접속 시 LICENSE_KEY 없으면 자동으로 Free 라이선스 등록
- 재설치 시: 같은 도메인이면 기존 키 반환 (새 키 발급 안 함)

---

## 9. 설치 시 자동 생성 데이터

### 기본 메뉴

| 메뉴 그룹 | 항목 |
|----------|------|
| Main Menu | Home, 마켓플레이스, 개발자 포털, 커뮤니티, Notice, Free Board, Q&A, FAQ |
| Footer Menu | 이용약관, 개인정보처리방침, 데이터 관리 정책, 취소 환불 규정, 특정상거래법, 자금결제법 |

### 기본 게시판 (4개)

| 게시판 | slug | 스킨 | 카테고리 |
|--------|------|------|---------|
| Notice | notice | default | 공지/업데이트/보안/점검/이벤트 (13개국어) |
| Free Board | free | default | — |
| Q&A | qna | default | — |
| FAQ | faq | faq (아코디언) | 일반/설치·설정/플러그인·위젯/다국어/요금·라이선스 (13개국어) |

### 기본 페이지

| 페이지 | slug | 타입 | 비고 |
|--------|------|------|------|
| 홈 | home | widget | 시스템 (삭제 불가), 기본 위젯 배치 |
| VosCMS | index | widget | 사용자 (리뉴얼용) |
| 법적 페이지 6개 | terms, privacy 등 | document | 13개국어 번역 포함 |

### 기본 회원 등급

| 등급 | Level | 할인 | 포인트 |
|------|-------|------|--------|
| Normal | 0 | 0% | 0% |
| Silver | 1 | 2% | 0.5% |
| Gold | 2 | 3% | 1% |
| VIP | 3 | 5% | 2% |

---

## 10. 빌드 프로세스

### 빌드 순서

```bash
# 1. 배포 디렉토리에 파일 복사
rsync -a \
  --exclude='.env' --exclude='.git' --exclude='.claude' \
  --exclude='node_modules' --exclude='storage/logs/*' \
  --exclude='storage/.installed' --exclude='storage/.license_cache' \
  --exclude='plugins/vos-license-manager' --exclude='plugins/vos-shop' \
  --exclude='docs' \
  /var/www/voscms/ /var/www/voscms-dist/voscms-{VERSION}/

# 2. 개발용 파일 제거
rm -f add_source_locale.php delete_test_translations.php ...

# 3. .env.example 생성

# 4. 코드 난독화 (obfuscate.php)
php8.3 /var/www/voscms-dist/obfuscate.php

# 5. ZIP 패키징
zip -rq voscms-{VERSION}.zip voscms-{VERSION}/

# 6. 다운로드 심볼릭 링크 업데이트
ln -sf voscms-{VERSION}.zip download/voscms-latest.zip
```

### 난독화 스크립트 (obfuscate.php)

위치: `/var/www/voscms-dist/obfuscate.php`

처리 내용:
- PHP 주석 제거
- 공백/개행 최소화
- 비밀키 문자열 base64 인코딩
- 보호 헤더 추가

대상 파일: 12개 (LicenseClient, Auth, PluginManager, install.php, Marketplace src 등)

**원본 소스(`/var/www/voscms/`)는 절대 건드리지 않음.**

---

## 11. 대시보드 원격 연동

### 공지사항 API

각 VosCMS 사이트의 대시보드에서 본사(vos.21ces.com) 공지사항을 표시.

```
GET /api/notices?locale=ko&limit=5
→ { "notices": [{ "title", "content", "date", "type", "url" }] }
```

- type 판별: 게시판 카테고리 slug 기반 (notice→info, update→release, security, event→feature, maintenance)
- 캐시: 1시간 (로케일별)
- 다국어: rzx_translations 폴백 (현재 로케일 → en → 원본)

### 설치 수 집계

대시보드 "Total Installs" — 라이선스 서버 stats API에서 가져옴.

```
GET /api/license/stats
→ { "total_installs", "active_installs", "total_plugin_purchases", "plans" }
```

- 캐시: 1시간
- 각 VosCMS 사이트에서 동일한 수치 표시

---

## 12. 버전 이력

| 버전 | 코드네임 | 날짜 | 주요 변경 |
|------|---------|------|----------|
| 2.1.0 | Marketplace | 2026-04-11 | 마켓플레이스, 라이선스 시스템, 개발자 포털, FAQ 스킨, 22개 위젯, 코드 난독화, 공지사항 API, 라이선스 관리 플러그인 |
| 2.0.0 | — | 2026-04-05 | VosCMS 코어 분리, 플러그인 아키텍처, 13개국어 |

---

## 13. 변경 이력 (2026-04-12)

- **코드 보호 방식 변경**: ionCube Encoder → PHP 자체 난독화 (obfuscate.php)
  - ionCube Encoder는 유료($199~), ionCube Loader도 불필요
  - 비밀키는 base64 인코딩으로 보호
  - 라이선스 키 생성은 서버 측에서 처리하므로 클라이언트 코드 보호 부담 감소
- **install.php 환경 체크**: ionCube Loader 항목 제거, curl 항목 추가
- **install.php 푸터**: `Powered by THEVOS` 추가
- **배포 ZIP 다운로드 경로**: `/download/voscms-latest.zip` 추가
- **공지사항 API**: `/api/notices` 신규 (카테고리 slug 기반 type 판별)
- **대시보드 Total Installs**: 로컬 DB → 라이선스 서버 stats API로 변경
- **공지 게시판 카테고리**: 공지/업데이트/보안/점검/이벤트 (13개국어) 추가
- **라이선스 자동 등록**: LICENSE_KEY 없는 기존 사이트 → 관리자 접속 시 Free 자동 등록
- **라이선스 키 생성**: install.php 클라이언트 → 서버 측으로 이전 (중복 100% 방지)
