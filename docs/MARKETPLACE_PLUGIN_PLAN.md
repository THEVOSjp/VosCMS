# VosCMS 마켓플레이스 플러그인 개발 계획서

## Context

VosCMS는 무료/오픈소스 코어 + 유료 플러그인 판매 모델을 채택하고 있다(VOSCMS_PLUGIN_STRATEGY.md).
현재 플러그인(vos-salon, vos-pos, vos-kiosk, vos-attendance) 설치는 수동(파일 복사 + 관리자 패널에서 활성화)으로만 가능하며, 마켓플레이스가 없어 탐색·구매·자동설치 경로가 없다.

**목표**: 관리자 패널에서 플러그인/테마/위젯/스킨을 탐색·구매·라이선스관리·원클릭설치할 수 있는 `vos-marketplace` 플러그인 구축.

---

## 1. 플러그인 구조

```
plugins/vos-marketplace/
├── plugin.json                          # 매니페스트
├── migrations/
│   ├── 001_create_marketplace_tables.sql  # 아이템, 버전, 카테고리, 주문
│   ├── 002_create_license_tables.sql      # 라이선스, 활성화
│   └── 003_create_review_tables.sql       # 리뷰
├── hooks/
│   ├── dashboard-widget.php              # 대시보드 업데이트 알림
│   └── update-check.php                  # 자동 업데이트 체크
├── views/
│   ├── admin/
│   │   ├── browse.php                    # 카탈로그 탐색 (메인 페이지)
│   │   ├── item-detail.php               # 아이템 상세
│   │   ├── purchases.php                 # 구매 내역
│   │   ├── licenses.php                  # 라이선스 관리
│   │   ├── settings.php                  # 설정
│   │   ├── api.php                       # 내부 AJAX 핸들러
│   │   ├── install-handler.php           # 다운로드+설치 핸들러
│   │   └── _components/
│   │       ├── item-card.php             # 아이템 카드 컴포넌트
│   │       ├── item-filters.php          # 필터 바
│   │       ├── rating-stars.php          # 별점 표시
│   │       └── install-button.php        # 설치/업데이트 버튼
│   └── api/
│       ├── catalog.php                   # 카탈로그 API (GET)
│       ├── item.php                      # 아이템 상세 API (GET)
│       ├── purchase.php                  # 구매 API (POST)
│       ├── license-validate.php          # 라이선스 검증 API (POST)
│       ├── download.php                  # 다운로드 API (GET)
│       └── webhook.php                   # 결제 웹훅
├── src/
│   ├── MarketplaceService.php            # 핵심 비즈니스 로직
│   ├── LicenseService.php                # 라이선스 생성/검증
│   ├── InstallerService.php              # 다운로드/추출/설치
│   ├── CatalogClient.php                 # 원격 마켓플레이스 API 클라이언트
│   └── Models/
│       ├── MarketplaceItem.php
│       ├── ItemVersion.php
│       ├── MarketplaceCategory.php
│       ├── MarketplaceOrder.php
│       ├── OrderItem.php
│       ├── License.php
│       ├── LicenseActivation.php
│       └── Review.php
├── assets/
│   ├── css/marketplace.css
│   └── js/marketplace.js                # Alpine.js 컴포넌트
└── lang/
    ├── en.php
    ├── ko.php
    └── ja.php
```

---

## 2. plugin.json

```json
{
    "id": "vos-marketplace",
    "name": {
        "en": "VosCMS Marketplace",
        "ko": "VosCMS 마켓플레이스",
        "ja": "VosCMS マーケットプレイス"
    },
    "description": {
        "en": "Browse, purchase, and install plugins, themes, widgets, and skins",
        "ko": "플러그인, 테마, 위젯, 스킨을 탐색, 구매, 설치",
        "ja": "プラグイン、テーマ、ウィジェット、スキンの検索・購入・インストール"
    },
    "version": "1.0.0",
    "author": { "name": "VosCMS", "url": "https://voscms.com" },
    "category": "system",
    "requires": { "php": ">=8.0" },
    "migrations": [
        "migrations/001_create_marketplace_tables.sql",
        "migrations/002_create_license_tables.sql",
        "migrations/003_create_review_tables.sql"
    ],
    "routes": {
        "admin": [
            { "path": "marketplace", "view": "admin/browse.php" },
            { "path": "marketplace/item", "view": "admin/item-detail.php" },
            { "path": "marketplace/purchases", "view": "admin/purchases.php" },
            { "path": "marketplace/licenses", "view": "admin/licenses.php" },
            { "path": "marketplace/settings", "view": "admin/settings.php" },
            { "path": "marketplace/api", "view": "admin/api.php", "method": "POST" },
            { "path": "marketplace/install", "view": "admin/install-handler.php", "method": "POST" }
        ],
        "api": [
            { "path": "api/marketplace/catalog", "view": "api/catalog.php" },
            { "path": "api/marketplace/item", "view": "api/item.php" },
            { "path": "api/marketplace/purchase", "view": "api/purchase.php", "method": "POST" },
            { "path": "api/marketplace/license/validate", "view": "api/license-validate.php", "method": "POST" },
            { "path": "api/marketplace/download", "view": "api/download.php" },
            { "path": "api/marketplace/webhook", "view": "api/webhook.php", "method": "POST" }
        ]
    },
    "menus": {
        "admin": [
            {
                "title": { "en": "Marketplace", "ko": "마켓플레이스", "ja": "マーケットプレイス" },
                "icon": "M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z",
                "items": [
                    { "title": { "en": "Browse", "ko": "탐색", "ja": "検索" }, "route": "marketplace" },
                    { "title": { "en": "My Purchases", "ko": "구매 내역", "ja": "購入履歴" }, "route": "marketplace/purchases" },
                    { "title": { "en": "Licenses", "ko": "라이선스", "ja": "ライセンス" }, "route": "marketplace/licenses" },
                    { "title": { "en": "Settings", "ko": "설정", "ja": "設定" }, "route": "marketplace/settings" }
                ]
            }
        ]
    },
    "hooks": {
        "admin.dashboard.render": "hooks/dashboard-widget.php",
        "plugin.update.check": "hooks/update-check.php"
    },
    "settings": {
        "defaults": {
            "marketplace_api_url": "https://marketplace.voscms.com/api",
            "auto_update_check": "1",
            "update_check_interval": "86400"
        }
    }
}
```

---

## 3. 데이터베이스 스키마

### 3.1 핵심 테이블 (001_create_marketplace_tables.sql)

| 테이블 | 용도 |
|--------|------|
| `rzx_mp_items` | 마켓 아이템 (플러그인/테마/위젯/스킨) |
| `rzx_mp_item_versions` | 버전 이력 (변경로그, 다운로드URL, 해시) |
| `rzx_mp_categories` | 카테고리 (다국어 JSON name) |
| `rzx_mp_orders` | 구매 주문 (결제 연동) |
| `rzx_mp_order_items` | 주문 항목 (스냅샷 가격) |

#### rzx_mp_items 주요 컬럼
- `type` ENUM('plugin','theme','widget','skin')
- `name`, `description` — JSON 다국어
- `price`, `currency`, `sale_price`, `sale_ends_at` — 가격 정보
- `latest_version`, `min_voscms_version`, `min_php_version` — 호환성
- `download_count`, `rating_avg`, `rating_count` — 통계
- `is_featured`, `is_verified`, `status` — 관리

### 3.2 라이선스 테이블 (002_create_license_tables.sql)

| 테이블 | 용도 |
|--------|------|
| `rzx_mp_licenses` | 라이선스 키 (UUID), 유형, 최대활성화수 |
| `rzx_mp_license_activations` | 도메인별 활성화 기록 |

### 3.3 리뷰 테이블 (003_create_review_tables.sql)

| 테이블 | 용도 |
|--------|------|
| `rzx_mp_reviews` | 별점(1-5) + 리뷰 텍스트, 구매 인증 여부 |

---

## 4. 핵심 기능 설계

### 4.1 구매 플로우 (PaymentService 재사용)

```
[탐색] → [구매 클릭] → [PaymentRequest 생성] → [Stripe Checkout]
  → [webhook 수신] → [주문 상태 paid 업데이트]
  → [라이선스 키 생성] → [다운로드 가능]
```

- 기존 `rzxlib/Modules/Payment/Services/PaymentService.php`의 `prepare()` 재사용
- `PaymentRequest` DTO 구성 시 `metadata.type = 'marketplace'`로 구분
- 별도 웹훅 엔드포인트(`api/marketplace/webhook`)에서 마켓 결제만 처리 (코어 미수정)
- 무료 아이템은 결제 건너뛰고 바로 라이선스 발급

### 4.2 라이선스 검증 (도메인 기반)

```
[설치된 VosCMS 인스턴스] → POST /api/marketplace/license/validate
  { license_key, domain, instance_id, action: "activate"|"heartbeat"|"deactivate" }

→ 서버 검증:
  1. license_key 존재 + status=active 확인
  2. expires_at 만료 확인
  3. 활성화 수 < max_activations 확인
  4. 도메인 정규화 (www 제거, 포트 제거)
  5. rzx_mp_license_activations에 기록
```

- `instance_id` = sha256(DB_HOST + DB_DATABASE + APP_URL) — 재설치 구분
- 일일 heartbeat로 라이선스 유효성 지속 확인

### 4.3 자동 설치 (InstallerService)

```
[다운로드] → [SHA-256 해시 검증] → [storage/tmp/ 에 압축 해제]
  → [매니페스트 검증] → [타입별 디렉토리에 복사]
    - plugin → plugins/{id}/
    - theme → themes/{id}/
    - widget → widgets/{id}/
    - skin → skins/{type}/{id}/
  → [PluginManager::install() 호출] → [임시파일 정리]
```

### 4.4 카탈로그 동기화 (CatalogClient)

- 원격 마켓플레이스 API(marketplace.voscms.com)에서 카탈로그 가져오기
- `rzx_mp_items`에 로컬 캐시 (`remote_id`로 연결)
- 설정 가능한 동기화 주기 (기본 24시간)

---

## 5. 관리자 UI 페이지

| 페이지 | 설명 | 스택 |
|--------|------|------|
| **탐색 (browse.php)** | 카드 그리드, 카테고리/타입/가격 필터, 검색 | Tailwind + Alpine.js |
| **상세 (item-detail.php)** | 스크린샷, 설명, 버전정보, 리뷰, 구매버튼 | Tailwind + Alpine.js |
| **구매내역 (purchases.php)** | 주문 목록, 상태, 다운로드 링크 | Tailwind 테이블 |
| **라이선스 (licenses.php)** | 라이선스 키, 활성화 도메인, 활성화/비활성화 | Tailwind + HTMX |
| **설정 (settings.php)** | API URL, 자동업데이트 토글, 캐시 초기화 | Tailwind 폼 |

모든 UI는 다크모드 호환 (`dark:` prefix), 기존 admin 레이아웃 사용.

---

## 6. 기존 코드 재사용

| 항목 | 파일 | 용도 |
|------|------|------|
| PluginManager | `rzxlib/Core/Plugin/PluginManager.php` | install/activate/deactivate 호출 |
| PaymentService | `rzxlib/Modules/Payment/Services/PaymentService.php` | Stripe 결제 처리 |
| PaymentRequest DTO | `rzxlib/Modules/Payment/DTO/PaymentRequest.php` | 결제 요청 구성 |
| Hook | `rzxlib/Core/Plugin/Hook.php` | 이벤트 훅 등록/트리거 |
| QueryBuilder | `rzxlib/rzxlib/Core/Database/QueryBuilder.php` | DB 쿼리 |
| Connection | `rzxlib/rzxlib/Core/Database/Connection.php` | PDO 싱글턴 |
| AdminAuth | `rzxlib/Core/Auth/AdminAuth.php` | 관리자 인증 확인 |
| admin-sidebar.php | `resources/views/admin/partials/admin-sidebar.php` | 자동 메뉴 주입 (수정 불필요) |
| widgets-marketplace.php | `resources/views/admin/site/widgets-marketplace.php` | → marketplace?type=widget 리다이렉트 |

---

## 7. 구현 순서

### Phase 1A: 기반 구조 (최우선)
1. `plugin.json` 작성
2. 3개 마이그레이션 SQL 작성
3. 7개 모델 클래스 작성 (Active Record 패턴)
4. `MarketplaceService.php`, `LicenseService.php` 작성
5. 관리자 탐색 페이지 (`browse.php`) + 카드 컴포넌트
6. 관리자 API 핸들러 (`api.php`)
7. 설정 페이지 (`settings.php`)
8. 다국어 파일 (ko, en, ja)

### Phase 1B: 구매 플로우
1. `CatalogClient.php` — 원격 API 클라이언트
2. PaymentService 연동 구매 플로우
3. 웹훅 핸들러 (`webhook.php`)
4. 라이선스 생성 로직
5. 구매내역 페이지, 라이선스 페이지

### Phase 1C: 설치 시스템
1. `InstallerService.php` — 다운로드/추출/설치
2. 설치 핸들러 엔드포인트
3. SHA-256 해시 검증
4. 타입별 설치 (plugin/theme/widget/skin)
5. Alpine.js 설치 진행률 UI

### Phase 1D: 상세 + 리뷰
1. 아이템 상세 페이지 (스크린샷, 설명, 버전정보)
2. 리뷰 작성/표시
3. 별점 컴포넌트

### Phase 2 (향후): 판매자 시스템
- 판매자 등록, 대시보드, 상품 업로드
- Stripe Connect 정산
- 관리자 승인 큐

---

## 8. 검증 방법

1. **플러그인 설치**: 관리자 패널 > 플러그인 관리에서 vos-marketplace 설치 → 마이그레이션 실행 확인 → 사이드바에 "마켓플레이스" 메뉴 표시 확인
2. **탐색**: 마켓플레이스 탐색 페이지에서 카테고리/타입 필터, 검색 동작 확인
3. **구매**: 테스트 모드 Stripe로 구매 플로우 전체 테스트 (결제 → 웹훅 → 라이선스 발급)
4. **라이선스**: 라이선스 키로 도메인 활성화/비활성화, 만료 처리 확인
5. **설치**: 구매 후 원클릭 설치 → PluginManager 통해 정상 등록 확인
6. **다크모드**: 모든 UI 페이지 라이트/다크 모드 전환 확인
7. **다국어**: ko/en/ja 전환 시 UI 텍스트 정상 표시 확인
