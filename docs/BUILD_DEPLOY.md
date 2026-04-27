# VosCMS 빌드 & 배포 전략

> 최종 수정: 2026-04-27 / VosCMS 2.3.9
> 단일 소스(`vos.21ces.com`) → 빌드 → 배포본 ZIP → 테스트 → 본사 운영 사이트(rsync) / 외부 고객(다운로드)
> 관련 문서: [DISTRIBUTION.md](DISTRIBUTION.md), [PLUGIN_ARCHITECTURE.md](PLUGIN_ARCHITECTURE.md), [LICENSE_MARKETPLACE_SYSTEM.md](LICENSE_MARKETPLACE_SYSTEM.md)

---

## 1. 개발 원칙

```
개발: vos.21ces.com (단일 소스)
       ↓
테스트: test.21ces.com (배포본 ZIP 직접 전개)
       ↓
배포: ① 외부 고객  → vos.21ces.com/download/voscms-latest.zip
      ② 본사 사이트 → voscms.com (rsync + mysqldump 동기화)
```

### 규칙

- 모든 코드 수정은 `/var/www/voscms/` 에서만 수행
- 디버깅은 voscms.com (프로덕션) → 수정은 /var/www/voscms (개발) → 테스트 → 배포
- 다른 서버에서 직접 코드 수정 금지 (코드 충돌 / 동기화 누락 방지)
- `.env` 는 GitHub 에 절대 커밋하지 않음 — 호스팅 고객은 DB+`.env`+`uploads` 3개 세트 백업 필수
- 생산성 보호: 한 번의 빌드로 외부 고객용 ZIP 과 본사 동기화 소스가 모두 동일한 단일 소스에서 파생되어야 한다

---

## 2. 단일 소스 구조 (`/var/www/voscms/`)

GitHub: `git@github.com:THEVOSjp/VosCMS.git` / 메인 브랜치: `main`

```
/var/www/voscms/
├── index.php                   ← 메인 라우터 (배포 시 strip 패치 대상)
├── install.php                 ← 설치 마법사 Step 0~1 (평문 유지)
├── install-core.php            ← 설치 Step 2~5 (ionCube 인코딩 대상)
├── version.json                ← 버전 정보 (빌드 스크립트가 참조)
├── composer.json / .lock
├── package.json
│
├── rzxlib/Core/                ← 코어 라이브러리 [공통]
│   ├── Auth/                   ← AdminAuth/Auth/SessionGuard (인코딩)
│   ├── Database/, Http/, I18n/, Modules/, Routing/, Skin/, Updater/, Helpers/
│   ├── License/                ← LicenseClient/LicenseStatus (인코딩)
│   └── Plugin/                 ← PluginManager/Hook (인코딩)
│
├── api/                        ← API
│   ├── _plugin_dispatch.php    ← 플러그인 활성 확인 후 dispatch
│   ├── exchange-rates.php      ← 환율 조회
│   ├── webhook-payjp.php       ← PAY.JP 웹훅
│   ├── license/                ← 본사 전용 (배포 제외)
│   ├── developer/              ← 본사 전용 (배포 제외)
│   └── notices.php             ← 본사 전용 (배포 제외)
│
├── plugins/
│   ├── vos-autoinstall/        ← 모든 배포에 포함 (필수)
│   ├── vos-salon/              ← 살롱용 (배포 포함, 기본 비활성)
│   ├── vos-license-manager/    ← 본사 전용 (배포 제외)
│   ├── vos-license-server/     ← 본사 전용 — scope: hq_only (배포 제외)
│   ├── vos-developer/          ← 본사 전용 — scope: hq_only (배포 제외)
│   ├── vos-hosting/            ← voscms.com 전용 (배포 제외)
│   ├── vos-market/             ← market.21ces.com 전용 (배포 제외)
│   └── vos-shop/               ← 마켓에서 별도 다운로드 (배포 제외)
│
├── widgets/                    ← 28종 (`bundled` 플래그로 빌드 시 13종 필터링)
│
├── skins/
│   ├── default/                ← 최상위 default 스킨
│   ├── layouts/                ← 레이아웃 (default, minimal, modern + 작업본 modern-copy-* 제외)
│   ├── board/                  ← 게시판 스킨 (default, faq, qna)
│   ├── page/                   ← 페이지 스킨 (default, changelog)
│   └── member/                 ← 회원 스킨 (default, modern)
│
├── resources/
│   ├── views/                  ← 뷰 (admin, customer, components, system)
│   ├── lang/{13 locales}/      ← 다국어 파일
│   └── css/
│
├── config/                     ← 메뉴/시스템 페이지 정의 (Core 업데이트 시 보존)
│   ├── admin-menu.php
│   ├── mypage-menu.php
│   ├── system-pages.php        ← 빌드 시 strip-hosting-service.php 가 본사 전용 slug 제거
│   ├── admin-dropdown-menu.php
│   └── user-dropdown-menu.php
│
├── database/
│   ├── migrations/             ← 코어 + 플러그인 마이그레이션
│   └── seeds/                  ← legal_pages, board_data 시드
│
├── routes/
├── assets/
├── vendor/
├── docs/                       ← 내부 개발 문서 (배포 제외)
├── scripts/                    ← 빌드 스크립트 등 (배포 제외)
└── storage/                    ← 런타임 (배포 시 빈 디렉토리만 포함)
```

---

## 3. 배포 채널

### 3.1 외부 고객용 — 다운로드 ZIP (`voscms-${VERSION}.zip`)

**대상**: 자체 호스팅 환경에서 VosCMS 를 설치하는 일반 사용자
**산출물**: `/var/www/voscms-dist/voscms-2.3.9.zip` (58 MB)
**다운로드**: `https://vos.21ces.com/download/voscms-latest.zip`

이 ZIP 1개가 모든 외부 사용자에게 동일하게 배포된다. 살롱·포털·일반 사이트 모두 같은 ZIP을 받아 설치 후 마켓(`vos-autoinstall`)에서 필요한 플러그인/위젯/스킨을 추가 다운로드한다.

> **2.3.9 변경**: 과거에는 Salon/Portal 별 ZIP을 따로 빌드할 계획이었으나, **마켓 기반 다운로드 모델**로 전환되면서 코어 ZIP 1개 + 마켓 추가 설치 구조로 단일화했다.

### 3.2 본사 voscms.com — rsync 동기화

**대상**: 본사 운영 사이트 (`/var/www/voscms-com/` — voscms_prod DB)
**방식**: vos.21ces.com → voscms.com 직접 rsync + mysqldump

본사 사이트는 ZIP 으로 다시 설치하지 않는다. 개발 서버의 `/var/www/voscms/` 와 운영 서버 `/var/www/voscms-com/` 는 동일한 단일 소스를 공유하며, **본사 전용 플러그인/API 가 모두 활성**된 상태로 배포된다.

| 항목 | vos.21ces.com (개발) | voscms.com (본사 운영) |
|------|---------------------|------------------------|
| 디렉토리 | `/var/www/voscms/` | `/var/www/voscms-com/` |
| DB | `voscms` | `voscms_prod` |
| 본사 전용 플러그인 | 활성 | 활성 |
| `api/license/*`, `api/developer/*`, `api/notices.php` | 활성 | 활성 |
| `system-pages.downloads, changelog` | 노출 | 노출 |
| 라이선스 서버 역할 | (개발 검증용) | 실제 라이선스 발급 |

배포 워크플로우는 §6 참조.

### 3.3 test.21ces.com — 배포본 검증

**대상**: 외부 고객용 ZIP 의 신규 설치 시뮬레이션
**디렉토리**: `/var/www/voscms-dist/voscms-${VERSION}/` (빌드 결과 디렉토리 그대로 웹루트)
**Nginx**: `/etc/nginx/sites-available/voscms-test`
**DB**: `voscms_test` (rezlyx 계정)

빌드 직후 매번 ZIP 을 풀지 않고 빌드 결과 디렉토리(`voscms-2.3.9/`)를 그대로 웹루트로 사용 → 신규 설치 마법사부터 메뉴/페이지 시드까지 검증.

---

## 4. 빌드 스크립트 (`scripts/build-voscms.sh`)

### 8 단계 흐름

| # | 단계 | 동작 |
|---|------|------|
| 1 | Cleaning | `${DIST}/voscms-${VERSION}/` 삭제 후 재생성 |
| 2 | Copying | `rsync -a` + exclude 패턴으로 SRC → TARGET |
| 3 | Removing dev files | `add_*_translations.php`, `vite.config.js`, `tailwind.config.js`, `postcss.config.js`, `update-api.php`, `install/` 디렉토리 등 제거 |
| 4 | Filtering widgets | `widget.json` 의 `bundled: true` 인 위젯만 유지 |
| 4b | Strip HQ-only | `strip-hosting-service.php` 호출 — 호스팅/다운로드/변경이력 잔존 코드 제거 |
| 5 | storage 디렉토리 | `cache/`, `logs/`, `sessions/`, `tmp/`, `uploads/` 빈 디렉토리 생성 |
| 6 | `.env.example` | 템플릿 작성 |
| 7 | ionCube 인코딩 | 12개 핵심 파일 인코딩 (인코더 없으면 `obfuscate.php` 폴백) |
| 8 | Packaging | `zip -rq` + `download/voscms-latest.zip` 심볼릭 링크 갱신 |

### 4.1 rsync exclude 정책 (2단계)

```bash
rsync -a \
  --exclude='.env' --exclude='.git' --exclude='.claude' \
  --exclude='node_modules' \
  --exclude='storage/logs/*' --exclude='storage/.installed' \
  --exclude='storage/.license_cache' --exclude='storage/.update_cache' \
  --exclude='storage/.widget_sync' --exclude='storage/.migration_checked' \
  --exclude='storage/cache/*' \
  --exclude='docs' \
  --exclude='plugins/vos-license-manager' \
  --exclude='plugins/vos-license-server' \
  --exclude='plugins/vos-developer' \
  --exclude='plugins/vos-hosting' \
  --exclude='plugins/vos-market' \
  --exclude='plugins/vos-shop' \
  --exclude='plugins/vos-marketplace' \
  --exclude='skins/layouts/modern-copy-260413' \
  --exclude='api/license' \
  --exclude='api/developer' \
  --exclude='api/notices.php' \
  "${SRC}/" "${TARGET}/"
```

### 4.2 위젯 필터링 (4단계) — `bundled` 플래그

각 `widgets/{name}/widget.json` 의 `"bundled": true | false` 가 빌드 포함 여부를 결정.

**현재 상태 (28개 → 13개 유지)**:

| 유지 (13) | 제외 (15) |
|---|---|
| board-contents, board-showcase, contact-form, contact-info, cta, features, grid-section, hero, hero-cta2, location-map, spacer, stats, text | testimonials¹, download-hero², before-after, booking, compare-slider, cta001, feature-tour, hero-slider, lookup, marketplace-showcase, services, shop-map, shop-ranking, staff, stylebook |

¹ vos-salon 유료 판매용
² 추후 유료 판매 페이지 전용 위젯으로 전환 예정

### 4.3 Strip HQ-only 패치 (4b 단계) — `strip-hosting-service.php`

빌드 후 다음 파일/디렉토리를 패치한다.

| 패치 대상 | 처리 내용 |
|-----------|-----------|
| `rzxlib/Core/Router/AdminRouter.php` | `service-orders` 라우트 블록 정규식 제거 |
| `index.php` | `mypage/services` 라우트 elseif 블록 라인 제거 |
| `config/system-pages.php` | `service/*`, `downloads`, `changelog` slug 제거 후 `var_export` 재작성 |
| `config/admin-menu.php` | `service-orders` 메뉴 블록 정규식 제거 |
| 뷰 디렉토리 삭제 | `resources/views/admin/service-orders/`, `resources/views/system/service/`, `resources/views/customer/mypage/services.php`, `service-detail.php`, `service-partials/` |
| 마이그레이션 삭제 | `030_create_orders_tables.sql`, `031_service_management_expansion.sql` |

> **2.3.9 추가**: `downloads`, `changelog` 페이지가 system-pages 제거 대상에 포함됨 (이전엔 `service/*` 만 처리).

### 4.4 ionCube 인코딩 (7단계)

12개 핵심 파일을 ionCube 바이트코드로 인코딩 → 고객 사이트는 ionCube Loader 필수.

| 파일 | 보호 이유 |
|------|----------|
| `rzxlib/Core/License/LicenseClient.php` | 도메인 해시 비밀키 |
| `rzxlib/Core/License/LicenseStatus.php` | 라이선스 검증 로직 |
| `rzxlib/Core/Auth/AdminAuth.php` | 관리자 인증 |
| `rzxlib/Core/Auth/Auth.php` | 회원 인증 + Remember Me |
| `rzxlib/Core/Auth/SessionGuard.php` | 세션 가드 |
| `rzxlib/Core/Plugin/PluginManager.php` | 플러그인 매니저 |
| `rzxlib/Core/Plugin/Hook.php` | 훅 시스템 |
| `plugins/vos-autoinstall/src/MarketplaceService.php` | 마켓 통신 |
| `plugins/vos-autoinstall/src/LicenseService.php` | 라이선스 자동 등록 |
| `plugins/vos-autoinstall/src/InstallerService.php` | 자동 설치기 |
| `plugins/vos-autoinstall/src/CatalogClient.php` | 카탈로그 클라이언트 |
| `install-core.php` | 설치 핵심 로직 |

**중요**: `install.php` 는 평문 유지 — Loader 없는 환경에서도 환경 진단 + 호스팅 안내 UI 가 노출되어야 하기 때문.

### 4.5 빌드 명령

```bash
# 일반 빌드 (ionCube 인코딩 포함)
sudo bash /var/www/voscms/scripts/build-voscms.sh

# 인코딩 건너뛰기 (테스트용)
sudo bash /var/www/voscms/scripts/build-voscms.sh --no-encode
NO_ENCODE=1 sudo bash /var/www/voscms/scripts/build-voscms.sh
```

### 4.6 빌드 산출물

```
/var/www/voscms-dist/
├── build-voscms.sh                     ← (구버전 사본, scripts/ 으로 이전됨)
├── strip-hosting-service.php           ← post-build 패치 스크립트
├── obfuscate.php                       ← ionCube 미설치 시 폴백
├── ioncube/ioncube_encoder_evaluation/ioncube_encoder.sh
├── voscms-2.3.9/                       ← 빌드 결과 디렉토리 (97 MB) — test.21ces.com 웹루트
├── voscms-2.3.9.zip                    ← ZIP 패키지 (58 MB)
└── download/
    ├── voscms-2.3.9.zip                ← symlink → ../voscms-2.3.9.zip
    └── voscms-latest.zip               ← symlink → ../voscms-2.3.9.zip
```

> ⚠ `/var/www/voscms-dist/build-voscms.sh` 는 옛 사본. 정식 스크립트는 `/var/www/voscms/scripts/build-voscms.sh` (소스 관리됨).

---

## 5. 배포별 매트릭스

### 5.1 위젯 매트릭스 (28종)

| 위젯 | bundled | 외부 ZIP | voscms.com | 비고 |
|------|:-------:|:--------:|:----------:|------|
| hero | ✅ | ✅ | ✅ | |
| hero-cta2 | ✅ | ✅ | ✅ | |
| features | ✅ | ✅ | ✅ | |
| stats | ✅ | ✅ | ✅ | |
| cta | ✅ | ✅ | ✅ | |
| text | ✅ | ✅ | ✅ | "자유 콘텐츠" |
| spacer | ✅ | ✅ | ✅ | |
| board-contents | ✅ | ✅ | ✅ | |
| board-showcase | ✅ | ✅ | ✅ | |
| grid-section | ✅ | ✅ | ✅ | |
| contact-form | ✅ | ✅ | ✅ | |
| contact-info | ✅ | ✅ | ✅ | |
| location-map | ✅ | ✅ | ✅ | |
| **testimonials** | ❌ | ❌ | ✅ | vos-salon 유료 판매용 |
| **download-hero** | ❌ | ❌ | ✅ | 추후 유료 페이지 전용 |
| hero-slider | ❌ | ❌ | ✅ | 마켓 별도 |
| feature-tour | ❌ | ❌ | ✅ | 마켓 별도 |
| cta001 | ❌ | ❌ | (vos-salon) | |
| booking | ❌ | ❌ | (vos-salon) | |
| lookup | ❌ | ❌ | (vos-salon) | |
| services | ❌ | ❌ | (vos-salon) | |
| staff | ❌ | ❌ | (vos-salon) | |
| before-after | ❌ | ❌ | (vos-salon) | |
| compare-slider | ❌ | ❌ | (vos-salon) | |
| stylebook | ❌ | ❌ | (vos-salon) | |
| shop-map | ❌ | ❌ | (vos-shop) | |
| shop-ranking | ❌ | ❌ | (vos-shop) | |
| marketplace-showcase | ❌ | ❌ | ✅ | 본사 마켓 홍보 |

### 5.2 플러그인 매트릭스

| 플러그인 | scope | 외부 ZIP | voscms.com | 기본 활성 |
|----------|-------|:--------:|:----------:|:---------:|
| vos-autoinstall | — | ✅ | ✅ | ✅ |
| vos-salon | — | ✅ | (필요 시) | ❌ |
| vos-license-manager | — | ❌ | ✅ | ✅ |
| vos-license-server | hq_only | ❌ | ✅ | ✅ |
| vos-developer | hq_only | ❌ | ✅ | ✅ |
| vos-hosting | — | ❌ | ✅ | ✅ |
| vos-market | — | ❌ | (market.21ces.com) | ✅ |
| vos-shop | — | ❌ (마켓에서 다운로드) | (필요 시) | ❌ |

### 5.3 API 매트릭스

| API | 외부 ZIP | voscms.com | 보안 |
|-----|:--------:|:----------:|------|
| `_plugin_dispatch.php` | ✅ | ✅ | wrapper, 플러그인 활성 체크 |
| `exchange-rates.php` | ✅ | ✅ | 환율 캐시 |
| `webhook-payjp.php` | ✅ | ✅ | PAY.JP 서명 검증 |
| `license/*` (7종) | ❌ | ✅ | vos-license-server 와 짝, host 화이트리스트 |
| `developer/*` (7종) | ❌ | ✅ | vos-developer 와 짝, host 화이트리스트 |
| `notices.php` | ❌ | ✅ | HQ_HOSTS env 화이트리스트 |

### 5.4 시스템 페이지 매트릭스

| Slug | 외부 ZIP | voscms.com | 비고 |
|------|:--------:|:----------:|------|
| home | ✅ | ✅ | 홈 |
| terms, privacy, refund-policy, data-policy, tokushoho, funds-settlement | ✅ | ✅ | 하단 기본 제공 (footer) |
| contact | ✅ | ✅ | 메인 메뉴 |
| downloads | ❌ | ✅ | 본사 다운로드 페이지 |
| changelog | ❌ | ✅ | 본사 변경이력 |
| service/order, service/complete | ❌ | ✅ | vos-hosting 전용 |

---

## 6. 본사 사이트 배포 워크플로우 (rsync)

vos.21ces.com (`/var/www/voscms/`) → voscms.com (`/var/www/voscms-com/`)

### 6.1 배포 명령 (참조)

```bash
# 1. DB 백업 (롤백 대비)
ssh voscms.com 'mysqldump voscms_prod | gzip > /var/backups/voscms_prod_$(date +%F).sql.gz'

# 2. 코드 동기화 (.env, storage 제외)
rsync -avz --delete \
  --exclude='.env' \
  --exclude='storage/' \
  --exclude='.git' \
  --exclude='.claude' \
  /var/www/voscms/ voscms.com:/var/www/voscms-com/

# 3. 마이그레이션 실행
ssh voscms.com 'cd /var/www/voscms-com && php8.3 scripts/migrate.php'

# 4. 캐시 클리어
ssh voscms.com 'rm -f /var/www/voscms-com/storage/.{license_cache,update_cache,widget_sync,migration_checked}'
```

### 6.2 첫 배포 (2026-04-18 완료)

본사 voscms.com 첫 배포는 mysqldump → import + rsync 방식으로 수행되었다. 이후 부터는 위 워크플로우의 증분 동기화로 진행.

### 6.3 배포 시 주의

- **`.env` 절대 동기화 금지** — 운영 DB 정보, APP_KEY, JWT_SECRET, LICENSE_KEY 등 호스트마다 다름
- **`storage/` 절대 덮어쓰기 금지** — 업로드 파일/캐시 보존
- **`.env` 권한**: `chmod 640 + chgrp www-data` 필수 (600 으로 설정 시 PHP-FPM 500 에러)
- **DB 마이그레이션 충돌**: legacy 환경은 errno 150 (FK) 충돌 가능 — `install/legacy_compat.sql` 처리 흐름 따름

---

## 7. 외부 고객 배포 워크플로우 (ZIP)

### 7.1 신규 설치

```
1. vos.21ces.com/download/voscms-latest.zip 다운로드
2. 서버에 업로드 + 압축 해제
3. .env 권한 설정 (chmod 640, chgrp www-data)
4. 브라우저에서 install.php 접속
5. 설치 마법사 6단계 진행 (언어 → 환경 진단 → DB → 마이그레이션 → 관리자 → 라이선스)
6. 설치 완료 → install.php 자동 비활성화 (storage/.installed 플래그)
7. 마켓에서 추가 플러그인/위젯/스킨 설치 (vos-autoinstall)
```

### 7.2 업데이트

```
1. 새 버전 ZIP 다운로드
2. 압축 해제 → 기존 디렉토리에 덮어쓰기 (storage, .env 보존)
3. 관리자 페이지 접속 → 자동 마이그레이션 실행 (LicenseClient::checkUpdate)
4. 캐시 클리어
```

### 7.3 마켓 기반 추가 설치

`vos-autoinstall` 이 본사 `market.21ces.com` API 와 통신해서 플러그인/위젯/스킨 카탈로그를 가져오고, 원클릭 설치 + 라이선스 자동 등록을 처리한다. 자세한 내용은 [PLUGIN_ARCHITECTURE.md](PLUGIN_ARCHITECTURE.md) §자동 설치 섹션 참조.

---

## 8. 설치 직후 자동 생성되는 컨텐츠

`install-core.php` 가 다음을 자동 시드한다 (2.3.9 기준).

### 8.1 사이트맵

| ID | 이름 | 위치 |
|----|------|------|
| 1 | Main Menu | 헤더 |
| 2 | Utility Menu | 헤더 우측 |
| 3 | Footer Menu | 푸터 |
| 4 | Unlinked | 메뉴 미연결 페이지 관리용 |

### 8.2 메인 메뉴 (3개 항목, 게시판은 Community 자식으로 묶임)

```
Home          (page → home)
Community ▾  (parent, URL 비움 → '#' — 자식 4개 그룹)
  ├ Notice       (board → notice)
  ├ Free Board   (board → free)
  ├ Q&A          (board → qna)
  └ FAQ          (board → faq)
Contact Us    (page → contact)
```

> **2.3.9 변경**: 이전엔 게시판 4개를 메인 메뉴에 평탄하게 등록 → "Community" 부모 메뉴 + 4개 자식 구조로 개편. lastInsertId() 캡처로 ID 의존성 제거.

### 8.3 Footer 메뉴 (하단 기본 제공 페이지 6개)

Terms / Privacy / Refund Policy / Data Policy / 特定商取引法 / 資金決済法 — 13개국어 번역 + HTML 본문 시드 (`database/seeds/legal_pages.php`)

### 8.4 기본 게시판 4개 (13개국어 카테고리 + 샘플 게시글)

| 게시판 | slug | 스킨 | 카테고리 |
|--------|------|------|---------|
| Notice | notice | default | 공지/업데이트/보안/점검/이벤트 |
| Free Board | free | default | — |
| Q&A | qna | qna | — |
| FAQ | faq | faq | 일반/설치/플러그인/다국어/요금 |

### 8.5 기본 페이지

| Slug | 타입 | 비고 |
|------|------|------|
| `home` | widget | 시스템 (삭제 불가). hero-cta2 + stats + features + grid-section 자동 배치 |
| `index` | widget | 사용자 (Unlinked, 리뉴얼용) |
| `contact` | widget | 시스템. 사용자가 위젯 빌더로 contact-form/contact-info 등 배치 |
| 6 법적 페이지 | document | 13개국어 본문 시드 |

### 8.6 회원 등급

Normal(0) / Silver(1, 할인2%) / Gold(2, 3%) / VIP(3, 5%)

---

## 9. 버전 정책 & 빌드 트리거

### 9.1 SemVer 적용

```
2.3.9
└─┬─┘
  ├ 2: Major — 호환성 깨지는 변경 (DB 스키마 대규모 개편 등)
  ├ 3: Minor — 기능 추가 (다국어 확장, 새 위젯/플러그인 등)
  └ 9: Patch — 버그 픽스, UI 개선
```

### 9.2 버전 파일 갱신

```bash
# version.json 직접 편집
vim /var/www/voscms/version.json
# {
#     "version": "2.3.10",
#     "name": "VosCMS",
#     "codename": "Next Codename",
#     "release_date": "2026-MM-DD",
#     ...
# }

# 빌드 → 자동으로 새 버전 ZIP 생성
sudo bash /var/www/voscms/scripts/build-voscms.sh
```

### 9.3 ZIP 명명 규칙

`voscms-${VERSION}.zip` (예: `voscms-2.3.9.zip`)
다운로드 심볼릭 링크: `voscms-latest.zip` 은 항상 최신 버전을 가리킴.

---

## 10. 향후 작업

### 10.1 단기

- ❌ vos-shop 마켓 다운로드 모델 검증 (현재는 README.md만 남음)
- ❌ ionCube 평가판 → 정식 라이선스 전환
- ❌ `dist/build-voscms.sh` 옛 사본 제거 (혼란 방지)
- ❌ skins/page/changelog/ — changelog 페이지 dist 제외에 맞춰 skin 도 향후 제외 검토

### 10.2 중기

- vos-hosting 코어 분리 후 `strip-hosting-service.php` 폐기 (디렉토리 exclude 만으로 정리)
- 다운로드 페이지 유료 판매 전환 시 별도 플러그인 `vos-downloads` 신설
- `download-hero` 위젯 → `vos-downloads` 플러그인에 포함

### 10.3 장기 — 자동 빌드 파이프라인

```
GitHub push → Actions (test) → 빌드 → S3 / GitHub Releases 업로드 → CDN 배포
                                  └→ test.21ces.com 자동 배포
                                  └→ voscms.com 수동 승인 후 배포
```

현재는 수동 빌드 + rsync. 고객 수가 늘면 CDN 다운로드 (`download.voscms.com`) 분리 필요.

---

## 11. 트러블슈팅

| 증상 | 원인 | 해결 |
|------|------|------|
| `install.php` 가 Step 2 이후 멈춤 | ionCube Loader 미설치 | install.php Step 1 환경 진단 메시지 따라 Loader 설치 |
| 라이선스 등록 실패 | localhost / 사설 IP / curl 미설치 / vos.21ces.com 장애 | 호스트 검증, curl 설치, 네트워크 확인 |
| 빌드 시 ionCube 인코더 없음 | encoder 경로 불일치 | 자동으로 `obfuscate.php` 로 폴백 — 정식 인코더 설치 권장 |
| 빌드 후 dist 의 system-pages 가 안 패치됨 | strip-hosting-service.php 정규식 깨짐 | 빌드 로그의 `total patches: N` 확인 — 0 이면 정규식 점검 |
| 본사 동기화 후 PHP-FPM 500 | `.env` 권한 600 | `chmod 640 + chgrp www-data` |
| 본사 동기화 시 마이그레이션 충돌 (errno 150) | legacy FK | `install/legacy_compat.sql` 처리 |

---

## 12. 관련 문서 / 인프라

- 배포판 상세: [DISTRIBUTION.md](DISTRIBUTION.md)
- 플러그인 아키텍처: [PLUGIN_ARCHITECTURE.md](PLUGIN_ARCHITECTURE.md)
- 라이선스 시스템: [LICENSE_MARKETPLACE_SYSTEM.md](LICENSE_MARKETPLACE_SYSTEM.md)
- 다국어: [I18N.md](I18N.md)
- 서비스 신청: [SERVICE_ORDER_SYSTEM.md](SERVICE_ORDER_SYSTEM.md)
- 서버 인프라: [SERVER_INFRASTRUCTURE.md](SERVER_INFRASTRUCTURE.md)
- VosCMS 개발 계획: [VOSCMS_DEVELOPMENT.md](VOSCMS_DEVELOPMENT.md)
