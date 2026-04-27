# VosCMS 배포판 문서

> 최종 수정: 2026-04-27 / VosCMS 2.3.9
> 본 문서는 코어 배포판(`voscms-X.Y.Z.zip`)의 구성, 빌드 흐름, 포함/제외 정책을 정리한다.
> 관련 문서: [PLUGIN_ARCHITECTURE.md](PLUGIN_ARCHITECTURE.md), [LICENSE_MARKETPLACE_SYSTEM.md](LICENSE_MARKETPLACE_SYSTEM.md), [DEPLOYMENT_PLAN.md](DEPLOYMENT_PLAN.md)

---

## 1. 배포판 구성 요약

VosCMS 배포판(`voscms-2.3.9.zip`, 약 58 MB)은 신규 고객 사이트 1개를 처음부터 끝까지 운영할 수 있도록 다음을 포함한다.

| 영역 | 구성 |
|------|------|
| **시스템 코어** | rzxlib (Core 라이브러리 — 인증/DB/라우팅/플러그인/위젯/i18n 등), vendor (composer), index.php, install.php |
| **API** | `_plugin_dispatch.php`, `exchange-rates.php`, `webhook-payjp.php` |
| **레이아웃 스킨 3종** | `default`, `minimal`, `modern` |
| **게시판 스킨 3종** | `default`, `faq`, `qna` |
| **페이지 스킨 2종** | `default`, `changelog` |
| **회원 스킨 2종** | `default`, `modern` |
| **위젯 13종** | board-contents, board-showcase, contact-form, contact-info, cta, features, grid-section, hero, hero-cta2, location-map, spacer, stats, text |
| **번들 플러그인 2개** | `vos-autoinstall` (자동 설치 / 마켓 다운로드), `vos-salon` (살롱용, 비활성 기본) |
| **다국어** | 13개 언어 — ko, en, ja, de, es, fr, id, mn, ru, tr, vi, zh_CN, zh_TW |
| **DB 마이그레이션** | core + plugin migrations |

### 설치 후 화면 구성

**관리자 화면** (좌측 사이드바, 3-영역 구조):

| 영역 | 메뉴 |
|------|------|
| top | 대시보드 / 회원 관리 / 사이트 관리 / 자동 설치 (vos-autoinstall) |
| ─── | 자동 구분선 |
| main | (활성화한 플러그인 메뉴가 자동 누적) |
| ─── | 자동 구분선 |
| bottom | 플러그인 / 설정 |

**프론트 페이지 메뉴**:

```
Home
Community ▾
  ├ Notice
  ├ Free Board
  ├ Q&A
  └ FAQ
Contact Us
```

**Footer 메뉴 (하단 기본 제공 페이지)**:
- Terms of Service / Privacy Policy / Refund Policy / Data Policy / 特定商取引法に基づく表記 / 資金決済法に基づく表示

---

## 2. 디렉토리 구조

```
voscms-2.3.9/
├── index.php                ← 메인 라우터
├── install.php              ← 설치 마법사 (평문, ionCube 미적용)
├── install-core.php         ← 설치 핵심 로직 (ionCube 인코딩)
├── version.json             ← 버전 정보
├── .env.example             ← 환경변수 템플릿
├── composer.json
├── package.json
│
├── rzxlib/                  ← Core 라이브러리
│   ├── Core/
│   │   ├── Auth/            ← AdminAuth, Auth, SessionGuard (ionCube)
│   │   ├── Database/        ← Connection, QueryBuilder
│   │   ├── Helpers/         ← 공용 함수 (load_menu, db_trans 등)
│   │   ├── Http/            ← 컨트롤러, 요청/응답
│   │   ├── I18n/            ← Translator
│   │   ├── License/         ← LicenseClient, LicenseStatus (ionCube)
│   │   ├── Modules/         ← WidgetLoader, WidgetRenderer
│   │   ├── Plugin/          ← PluginManager, Hook (ionCube)
│   │   ├── Router/          ← AdminRouter, Router
│   │   ├── Skin/            ← Skin Config
│   │   └── Updater/         ← UpdateChecker
│   └── Modules/Payment/     ← Payment (Stripe, PAY.JP)
│
├── api/
│   ├── _plugin_dispatch.php ← 플러그인 활성 확인 → dispatch
│   ├── exchange-rates.php
│   └── webhook-payjp.php
│
├── plugins/
│   ├── vos-autoinstall/     ← 자동 설치 (src/* ionCube)
│   └── vos-salon/           ← 살롱용 (비활성 기본)
│
├── widgets/                 ← 13종 (bundled=true 만 포함)
│
├── skins/
│   ├── default/             ← 최상위 default 스킨
│   ├── layouts/             ← 레이아웃 스킨 (default, minimal, modern)
│   ├── board/               ← 게시판 스킨 (default, faq, qna)
│   ├── page/                ← 페이지 스킨 (default, changelog)
│   └── member/              ← 회원 스킨 (default, modern)
│
├── resources/
│   ├── views/admin/         ← 어드민 뷰 (평문)
│   ├── views/customer/      ← 프론트 뷰 (평문)
│   ├── views/system/        ← 시스템 페이지 (changelog 등)
│   ├── views/components/    ← 공통 컴포넌트 (header, footer, sidebar)
│   ├── lang/{13 locales}/   ← 다국어 파일
│   └── css/                 ← 스타일시트
│
├── config/                  ← 메뉴/시스템 페이지 정의 (Core 업데이트 시 보존)
│   ├── admin-menu.php       ← 어드민 사이드바 메뉴
│   ├── mypage-menu.php      ← 마이페이지 사이드바 메뉴
│   ├── system-pages.php     ← 시스템 페이지 정의
│   └── ...
│
├── database/
│   ├── migrations/          ← Core 마이그레이션
│   └── seeds/               ← 시드 (legal_pages.php, board_data.php)
│
├── routes/
├── assets/                  ← 프론트 에셋 (css, js, fonts, brand)
├── vendor/                  ← Composer 의존성
└── storage/                 ← 런타임 저장소 (빈 디렉토리)
    ├── cache/, logs/, sessions/, tmp/, uploads/
```

---

## 3. 포함 / 제외 정책

### 3.1 포함

| 항목 | 비고 |
|------|------|
| 시스템 코어 + vendor | 핵심 보안 모듈은 ionCube 인코딩 |
| 레이아웃/게시판/페이지/회원 스킨 | 평문 (사용자 커스터마이즈 허용) |
| 13종 위젯 (`bundled: true`) | `widget.json` 의 플래그로 자동 필터 |
| `vos-autoinstall` | 마켓에서 추가 플러그인/위젯/스킨 다운로드 — 모든 사이트 필수 |
| `vos-salon` | 살롱 비즈니스용. 기본 비활성, 필요한 사이트만 활성화 |

### 3.2 제외 — 본사 전용 플러그인 (`scope: hq_only` 또는 voscms.com 전용)

| 플러그인 | 이유 |
|----------|------|
| `vos-license-manager` | 본사 라이선스 관리 UI (voscms.com 전용) |
| `vos-license-server` | 라이선스 검증 API 서버 (`scope: hq_only`) — 외부 노출 금지 |
| `vos-developer` | 개발자 포털 API (`scope: hq_only`) — 외부 노출 금지 |
| `vos-hosting` | 호스팅 사업자(voscms.com)용 서비스 주문 시스템 |
| `vos-market` | 마켓 운영 사이트(market.21ces.com)용 |
| `vos-marketplace` | (deprecated, vos-autoinstall 로 리네임됨) |
| `vos-shop` | 쇼핑몰 (마켓에서 별도 설치) |

### 3.3 제외 — 본사 전용 API

| API | 이유 |
|-----|------|
| `api/license/` | 라이선스 서버 (vos-license-server 와 짝) |
| `api/developer/` | 개발자 포털 (vos-developer 와 짝) |
| `api/notices.php` | 공지 API (HQ_HOSTS 화이트리스트 필요) |

### 3.4 제외 — 유료/추후 판매 위젯

| 위젯 | 이유 |
|------|------|
| `testimonials` (고객 후기) | vos-salon 유료 판매용 |
| `download-hero` (다운로드 히어로) | 추후 유료 판매 페이지 전용으로 전환 예정 |
| `before-after`, `booking`, `compare-slider`, `cta001`, `feature-tour`, `hero-slider`, `lookup`, `marketplace-showcase`, `services`, `shop-map`, `shop-ranking`, `staff`, `stylebook` | `bundled: false` — 마켓에서 별도 다운로드 |

### 3.5 제외 — 빌드 부산물 / 임시

| 경로 | 이유 |
|------|------|
| `skins/layouts/modern-copy-260413/` | modern 레이아웃 작업본 (개발 임시) |
| `docs/` | 내부 개발 문서 |
| `node_modules/`, `.git/`, `.claude/`, `.gitignore` | 개발 도구 |
| `storage/.installed`, `storage/.license_cache`, `storage/.update_cache`, `storage/.widget_sync`, `storage/.migration_checked`, `storage/cache/*`, `storage/logs/*` | 런타임 생성 파일 |
| `add_*_translations.php`, `delete_test_translations.php`, `update-api.php`, `postcss.config.js`, `tailwind.config.js`, `vite.config.js`, `install/` | 개발 전용 스크립트/설정 |

### 3.6 제외 — 본사 전용 시스템 페이지 (post-build patch)

빌드 후 `strip-hosting-service.php` 가 `config/system-pages.php` 에서 다음 slug 제거:

| Slug | 이유 |
|------|------|
| `service/order`, `service/complete` | 호스팅 서비스 주문 (vos-hosting 전용) |
| `downloads` | 본사 다운로드 페이지 — 추후 유료 판매로 전환 예정 |
| `changelog` | 본사 voscms.com 만 노출 |

### 3.7 dist 의 `system-pages.php` 최종 결과 (8개)

```
home (홈)
terms (이용약관)
privacy (개인정보처리방침)
data-policy (데이터 관리 정책)
refund-policy (환불정책)
tokushoho (특정상거래법)
funds-settlement (자금결제법)
contact (문의하기)
```

---

## 4. 빌드 프로세스 (`scripts/build-voscms.sh`)

### 8 단계 흐름

| # | 단계 | 동작 |
|---|------|------|
| 1 | Cleaning | 기존 `${DIST}/voscms-${VERSION}/` 삭제 |
| 2 | Copying | `rsync -a` 로 SRC → TARGET 복사, exclude 패턴 적용 |
| 3 | Removing dev files | 개발 전용 파일/디렉토리 제거 (`add_*`, `vite.config.js`, `install/` 등) |
| 4 | Filtering widgets | `widget.json` 의 `bundled: true` 인 위젯만 유지, 나머지 제거 |
| 4b | Strip HQ-only | `strip-hosting-service.php` 호출 — system-pages, AdminRouter, index.php, admin-menu 패치 + 호스팅 서비스 뷰 디렉토리 삭제 |
| 5 | storage 디렉토리 | `cache/`, `logs/`, `sessions/`, `tmp/`, `uploads/` 빈 디렉토리 생성 |
| 6 | `.env.example` | 템플릿 작성 (LICENSE_SERVER, DB, JWT 등) |
| 7 | ionCube 인코딩 | 12개 핵심 파일 인코딩 — 인코더 없으면 `obfuscate.php` 폴백 |
| 8 | Packaging | `zip -rq` + `download/voscms-latest.zip` 심볼릭 링크 갱신 |

### 4단계 위젯 필터링 — `bundled` 플래그

각 `widgets/{name}/widget.json` 의 `"bundled": true | false` 로 결정. 이번 빌드 결과:

```
kept: 13   (board-contents, board-showcase, contact-form, contact-info, cta,
            features, grid-section, hero, hero-cta2, location-map, spacer,
            stats, text)
removed: 15 (testimonials, download-hero, before-after, booking, compare-slider,
            cta001, feature-tour, hero-slider, lookup, marketplace-showcase,
            services, shop-map, shop-ranking, staff, stylebook)
```

### 4b단계 — `strip-hosting-service.php` 동작 상세

| 패치 대상 | 처리 |
|-----------|------|
| `rzxlib/Core/Router/AdminRouter.php` | `service-orders` 라우트 블록 정규식 제거 |
| `index.php` | `mypage/services` 라우트 2개 elseif 블록 라인 제거 |
| `config/system-pages.php` | `service/*`, `downloads`, `changelog` slug 제거 후 var_export 재작성 |
| `config/admin-menu.php` | `service-orders` 메뉴 블록 정규식 제거 |
| 뷰 디렉토리 | `resources/views/admin/service-orders/`, `resources/views/system/service/`, `resources/views/customer/mypage/services.php`, `service-detail.php`, `service-partials/` 삭제 |
| 마이그레이션 | `030_create_orders_tables.sql`, `031_service_management_expansion.sql` 삭제 |

### 7단계 — ionCube 인코딩 대상 (12개)

| 파일 | 보호 이유 |
|------|----------|
| `rzxlib/Core/License/LicenseClient.php` | 도메인 해시 비밀키 |
| `rzxlib/Core/License/LicenseStatus.php` | 라이선스 상태 검증 로직 |
| `rzxlib/Core/Auth/AdminAuth.php` | 관리자 인증 |
| `rzxlib/Core/Auth/Auth.php` | 회원 인증 + Remember Me |
| `rzxlib/Core/Auth/SessionGuard.php` | 세션 가드 |
| `rzxlib/Core/Plugin/PluginManager.php` | 플러그인 매니저 |
| `rzxlib/Core/Plugin/Hook.php` | 훅 시스템 |
| `plugins/vos-autoinstall/src/MarketplaceService.php` | 마켓 통신 |
| `plugins/vos-autoinstall/src/LicenseService.php` | 라이선스 자동 등록 |
| `plugins/vos-autoinstall/src/InstallerService.php` | 자동 설치기 |
| `plugins/vos-autoinstall/src/CatalogClient.php` | 카탈로그 클라이언트 |
| `install-core.php` | 설치 핵심 로직 (Step 2~5) |

> ⚠ `install.php` 는 평문 유지 — Loader 없는 환경에서도 환경 진단 + 호스팅 안내 UI 가 노출되어야 하기 때문.

### 빌드 산출물

```
/var/www/voscms-dist/
├── build-voscms.sh             ← 빌드 스크립트
├── strip-hosting-service.php   ← post-build 패치
├── obfuscate.php               ← ionCube 미설치 시 폴백
├── ioncube/ioncube_encoder_evaluation/ioncube_encoder.sh
├── voscms-2.3.9/               ← 빌드 결과 디렉토리 (97 MB)
├── voscms-2.3.9.zip            ← ZIP 패키지 (58 MB)
└── download/
    ├── voscms-2.3.9.zip        ← symlink → ../voscms-2.3.9.zip
    └── voscms-latest.zip       ← symlink → ../voscms-2.3.9.zip (항상 최신)
```

### 빌드 실행

```bash
sudo bash /var/www/voscms/scripts/build-voscms.sh

# ionCube 인코딩 건너뛰기 (테스트용)
sudo bash /var/www/voscms/scripts/build-voscms.sh --no-encode
# 또는
NO_ENCODE=1 sudo bash /var/www/voscms/scripts/build-voscms.sh
```

---

## 5. 설치 요구사항

### 서버 환경

| 항목 | 요구사항 |
|------|---------|
| **PHP** | 8.1+ (8.3 권장) |
| **ionCube Loader** | **필수** — 핵심 모듈 인코딩되어 있음 |
| **DB** | MySQL 8.0+ 또는 MariaDB 10.4+ |
| **웹서버** | Nginx 또는 Apache |
| **HTTPS** | 권장 (라이선스 서버 통신 시 SSL 사용) |

### PHP 확장

```
PDO, pdo_mysql, mbstring, json, openssl, curl, fileinfo
```

### 권장 사양

```
CPU: 2 vCPU 이상
RAM: 2 GB 이상
Disk: 10 GB SSD 이상
```

---

## 6. 설치 프로세스 (`install.php`)

```
1. 서버에 ZIP 업로드 + 압축 해제
2. https://your-domain.com/install.php 접속
3. Step 0: 언어 선택 (13개 언어)
4. Step 1: 환경 진단 (PHP/확장/ionCube/curl/쓰기 권한)
5. Step 2: DB 연결 설정 (host/db/user/pw/prefix)
6. Step 3: 마이그레이션 실행 (core + plugin)
7. Step 4: 관리자 계정 + 사이트 설정
8. Step 5: 라이선스 등록 (vos.21ces.com → Free 자동 발급)
9. Step 6: 완료
   - .env 자동 생성 (LICENSE_KEY 포함)
   - 메뉴/페이지/게시판/회원등급 시드
   - 홈 페이지 위젯 자동 배치
   - 4 게시판 샘플 게시글 시드 (13개국어 번역 포함)
   - install.php 비활성화 (storage/.installed 플래그)
```

### 설치 시 자동 생성되는 데이터

#### 메인 메뉴 (sitemap_id=1)

| 순서 | 메뉴 | 타입 | URL | 비고 |
|------|------|------|-----|------|
| 1 | Home | page | `home` | 단일 |
| 2 | Community | page | (빈 URL → `#`) | **부모** — 자식 4개 그룹 |
| 2.1 | └ Notice | board | `notice` | 자식 |
| 2.2 | └ Free Board | board | `free` | 자식 |
| 2.3 | └ Q&A | board | `qna` | 자식 |
| 2.4 | └ FAQ | board | `faq` | 자식 |
| 3 | Contact Us | page | `contact` | 단일 |

#### Footer 메뉴 (sitemap_id=3)

Terms / Privacy / Refund Policy / Data Policy / 特定商取引法 / 資金決済法 (6개, 13개국어 번역 + HTML 컨텐츠 시드)

#### 기본 게시판 4개

| 게시판 | slug | 스킨 | 카테고리 (13개국어) |
|--------|------|------|---------------------|
| Notice | notice | default | 공지/업데이트/보안/점검/이벤트 |
| Free Board | free | default | — |
| Q&A | qna | qna | — |
| FAQ | faq | faq (아코디언) | 일반/설치/플러그인/다국어/요금 |

#### 기본 페이지

| Slug | 타입 | 비고 |
|------|------|------|
| `home` | widget | 시스템 페이지 (삭제 불가). hero-cta2 + stats + features + grid-section 자동 배치 |
| `index` | widget | 사용자 페이지 (리뉴얼용, Unlinked 메뉴) |
| `contact` | widget | 시스템. 위젯 빌더로 사용자가 contact-form/contact-info 등 배치 |
| 6 법적 페이지 | document | 13개국어 본문 시드 (`database/seeds/legal_pages.php`) |

#### 회원 등급

| 등급 | Level | 할인 | 포인트 |
|------|-------|------|--------|
| Normal | 0 | 0% | 0% |
| Silver | 1 | 2% | 0.5% |
| Gold | 2 | 3% | 1% |
| VIP | 3 | 5% | 2% |

---

## 7. 라이선스 자동 등록

설치 시 install-core.php 가 라이선스 서버(`https://vos.21ces.com/api/license/register`)에 도메인 정보를 전송하면 서버가 키를 생성해 응답한다.

```json
// Request
{
    "domain": "customer-shop.com",
    "version": "2.3.9",
    "php_version": "8.3.6",
    "server_ip": "..."
}

// Response (신규)
{
    "success": true,
    "key": "RZX-K7M2-P9X4-N3H8",
    "plan": "free",
    "registered_at": "..."
}
```

- **재설치**: 같은 도메인이면 기존 키 반환 (중복 발급 방지)
- **기존 사이트 업그레이드**: `LICENSE_KEY` 없으면 관리자 첫 접속 시 자동 등록
- **localhost 차단**: install.php / LicenseClient 가 호스트 검증

---

## 8. 다운로드

### URL

```
https://voscms.com/download/voscms-latest.zip   ← 항상 최신
https://voscms.com/download/voscms-2.3.9.zip    ← 특정 버전
```

### 외부 환경에서 다운로드

```bash
# 일반 서버
wget https://voscms.com/download/voscms-latest.zip
unzip voscms-latest.zip

# StarServer (rezlyx.com)
ssh -p 10022 ss819716@sv10005.star.ne.jp
wget https://voscms.com/download/voscms-latest.zip
```

### test.21ces.com (설치 테스트 서버)

| 항목 | 값 |
|------|-----|
| URL | https://test.21ces.com |
| 웹루트 | `/var/www/voscms-dist/voscms-${VERSION}/` |
| Nginx | `/etc/nginx/sites-available/voscms-test` |
| DB | `voscms_test` (rezlyx 계정) |

---

## 9. 본사 운영 사이트 vs 배포판 비교

| 항목 | voscms.com (본사) | 배포판 |
|------|-------------------|--------|
| `vos-license-manager` | ✅ 활성 | ❌ 제외 |
| `vos-license-server` | ✅ 활성 | ❌ 제외 |
| `vos-developer` | ✅ 활성 | ❌ 제외 |
| `vos-hosting` | ✅ 활성 | ❌ 제외 |
| `vos-market` | (market.21ces.com) | ❌ 제외 |
| `vos-autoinstall` | ✅ 활성 | ✅ 활성 |
| `vos-salon` | ❌ 비활성 | ✅ 포함 (필요 시 활성) |
| `api/license/*`, `api/developer/*`, `api/notices.php` | ✅ | ❌ 제외 |
| `system-pages: downloads, changelog` | ✅ | ❌ 제거 |
| 위젯 `testimonials, download-hero` | ✅ | ❌ 제외 (유료 판매용) |
| 위젯 25+ (마켓) | ✅ | 마켓에서 다운로드 |

---

## 10. 버전 이력

| 버전 | 날짜 | 코드네임 | 주요 변경 |
|------|------|---------|----------|
| **2.3.9** | 2026-04-27 | Service Order i18n & 3-Section Menu | 서비스 신청 13개국어, 3-영역 사이드바, HQ 전용 플러그인 분리, 13종 위젯, 커뮤니티 부모 메뉴 + Contact Us, downloads/changelog dist 제외 |
| 2.3.8 | 2026-04-25 | Board i18n & FAQ/Q&A Skin | 게시판 13개국어, FAQ/Q&A 아코디언 스킨, 댓글 트리 |
| 2.3.7 | 2026-04-22 | Autoinstall UX | vos-marketplace → vos-autoinstall 리네임, 설치 플로우 재작성 |
| 2.3.6 | 2026-04-19 | — | 마켓플레이스 PAY.JP 결제, 웹진형 뷰 |
| 2.3.4 | 2026-04-15 | — | 마켓플레이스 카테고리 필터 분리 |
| 2.1.0 | 2026-04-11 | Marketplace | 마켓 + 라이선스 시스템, 개발자 포털, FAQ 스킨, 22 위젯 |
| 2.0.0 | 2026-04-05 | — | VosCMS 코어 분리, 플러그인 아키텍처, 13개국어 |

---

## 11. 트러블슈팅

### ionCube Loader 미설치

증상: `install.php` 접속 시 install-core.php 가 동작하지 않음 (Step 2 이후 멈춤)
해결: 호스팅에 ionCube Loader 설치 안내 — install.php Step 1 환경 진단 화면에서 안내 메시지 자동 표시

### 라이선스 등록 실패

증상: Step 5 에서 라이선스 서버 통신 실패
원인:
- 호스트가 `localhost` / 사설 IP — 차단됨
- 방화벽 / curl 미설치
- vos.21ces.com 일시 장애

### 빌드 시 ionCube 인코더 없음

`build-voscms.sh` 가 자동으로 `obfuscate.php` (PHP 자체 난독화 폴백)로 전환. ionCube Loader 없이도 동작하지만 보안 강도는 낮음.

### dist 의 system-pages 가 패치 안 됨

`strip-hosting-service.php` 의 정규식이 원본 admin-menu.php 구조 변경에 따라 깨질 수 있음. 빌드 로그에서 `total patches: N` 이 0 이면 정규식 점검 필요.

---

## 12. 향후 작업

- vos-hosting 코어 분리 후 `strip-hosting-service.php` 전체 폐기 (디렉토리 exclude 만으로 정리)
- ionCube 평가판 → 정식 라이선스 전환
- dist 의 changelog 페이지 스킨 (`skins/page/changelog/`) — 페이지 자체가 빠지므로 skin 도 향후 제외 검토
- 다운로드 페이지 유료 판매 전환 시 별도 플러그인(`vos-downloads`)으로 분리
