# vos-hosting

VosCMS 호스팅 사업자용 플러그인. **voscms.com 본 사이트 전용**.

## 상태
**migrating** — 코어에서 호스팅 관련 자원을 단계적으로 이전 중.

진행 단계:
- ✅ Step A (이번): 코어 → vos-hosting **복사** (코어는 그대로 유지, 영향 없음)
- ⏳ Step B (다음): include 경로 수정 + 코어 라우트 정리
- ⏳ Step C: 검증 (vos-hosting 활성화 vs 비활성화 양쪽 동작 확인)
- ⏳ Step D: 코어에서 호스팅 관련 파일 제거 + `strip-hosting-service.php` 폐기 + `build-voscms.sh` 단순화

## 목적
VosCMS 자체 웹호스팅 서비스를 운영하기 위한 모듈.
일반 VosCMS 다운로드 사용자에게는 불필요하므로 배포 빌드에서는 제외됨 (`build-voscms.sh` 의 `--exclude=plugins/vos-hosting`).

## 디렉토리 구조
```
plugins/vos-hosting/
├── plugin.json                    # 메뉴/라우트/마이그레이션 정의
├── README.md
├── views/
│   ├── admin/
│   │   ├── service-orders/        # 주문 목록 + 상세 (12 파일)
│   │   ├── services/              # 서비스 정의 관리
│   │   └── contact-messages.php   # 문의 관리
│   ├── customer/
│   │   ├── mypage/
│   │   │   ├── services.php       # 마이페이지 서비스 목록
│   │   │   └── service-detail.php # 서비스 상세
│   │   └── services.php           # 서비스 일람
│   └── system/
│       └── service/               # 호스팅 신청 + 결제 (14 파일)
│           ├── order.php          # 신청 메인 (https://voscms.com/service/order)
│           ├── complete.php       # 신청 완료
│           ├── _summary.php / _settings.php / _payment.php / _applicant.php / _addons.php
│           ├── _admin_settings.php / order.js
│           └── settings/
│               ├── general.php
│               ├── domain.php
│               ├── hosting.php
│               ├── addons.php
│               └── _footer.php
├── api/
│   ├── service-order.php          # 호스팅 주문 API
│   ├── service-manage.php         # 호스팅 관리 API
│   ├── domain-check.php           # 도메인 가용성 체크 (WHOIS)
│   ├── domain-prices-crawl.php    # 도메인 가격 크롤링
│   ├── namesilo-prices.php        # NameSilo 가격 캐시
│   ├── webhook-payjp.php          # PAY.JP 결제 webhook
│   └── exchange-rates.php         # 환율 (호스팅 다국적 가격 표시)
├── migrations/
│   ├── 001_create_orders_tables.sql        # ← 코어 030 에서 이전
│   └── 002_service_management_expansion.sql # ← 코어 031 에서 이전
├── config/
│   └── service-settings-tabs.php  # 호스팅 설정 탭 정의 (general/domain/hosting/addons)
└── lang/
    └── {13 locale}/
        └── services.php
```

## 어드민 메뉴 (자동 등록)
plugin.json `menus.admin` 정의에 따라 PluginManager 가 자동 등록:
- **서비스 주문** (route: `service-orders`, position: 65)
- **문의 관리** (route: `contact-messages`, position: 75)

vos-hosting 비활성화 시 두 메뉴 모두 사이드바에서 사라짐.

## 라우트 (자동 등록)
| 종류 | 경로 | 메서드 | 뷰 |
|---|---|---|---|
| admin | `service-orders` | GET | `views/admin/service-orders/index.php` |
| admin | `service-orders/show` | GET | `views/admin/service-orders/detail.php` |
| admin | `contact-messages` | GET | `views/admin/contact-messages.php` |
| front | `service/order` | GET, POST | `views/system/service/order.php` |
| front | `service/complete` | GET | `views/system/service/complete.php` |
| front | `mypage/services` | GET | `views/customer/mypage/services.php` |
| front | `mypage/services/show` | GET | `views/customer/mypage/service-detail.php` |
| api | `service-order` | POST | `api/service-order.php` |
| api | `service-manage` | POST | `api/service-manage.php` |
| api | `domain-check` | GET | `api/domain-check.php` |
| api | `domain-prices-crawl` | GET | `api/domain-prices-crawl.php` |
| api | `namesilo-prices` | GET | `api/namesilo-prices.php` |
| api | `webhook-payjp` | POST | `api/webhook-payjp.php` |
| api | `exchange-rates` | GET | `api/exchange-rates.php` |

프론트 페이지: <https://voscms.com/service/order> (호스팅 신청)

## 마이그레이션
| 파일 | 내용 |
|---|---|
| `migrations/001_create_orders_tables.sql` | `rzx_orders`, `rzx_order_logs`, `rzx_subscriptions` 생성 |
| `migrations/002_service_management_expansion.sql` | 1회성 서비스 관리 확장 (`service_class`, `completed_at` 컬럼 추가) |

PluginManager::install() 가 순차 실행. `rzx_plugin_migrations` 테이블에 기록되어 멱등성 보장.

## DB 테이블 (참고용 — 마이그레이션이 정의)
- `rzx_orders` — 호스팅 주문
- `rzx_order_logs` — 주문 처리 로그
- `rzx_subscriptions` — 정기 구독 (호스팅, 도메인, 메일 등)
- `rzx_contact_messages` — 문의 메시지

## 코어와의 관계 (Step A 시점)

### 현재 상태 (Step A 완료)
- 코어에 호스팅 코드 **그대로 존재**: `resources/views/admin/service-orders/`, `api/service-order.php`, `database/migrations/migrations/030*.sql` 등
- 코어 라우트가 호스팅 처리: `AdminRouter.php` 라인 116-120, `index.php` 라인 407-409
- voscms.com 본 사이트는 **코어 코드로 동작 중** (vos-hosting 활성화 안 한 상태)
- vos-hosting 디렉토리는 **사본 보관 + 메타 정의** 상태

### 다음 단계 (Step B 예정)
- vos-hosting 안의 PHP 파일들의 `BASE_PATH . '/resources/...'` 같은 코어 경로 include 를 플러그인 상대 경로로 수정
- AdminRouter.php / index.php 의 호스팅 라우트 코어 정의 제거 (PluginManager 가 plugin.json `routes` 로 처리)
- config/admin-menu.php 에서 service-orders, contact-messages 블록 제거 (이미 빌드 산출물에서는 제거됨)
- voscms.com 에 vos-hosting 활성화 (`PluginManager::install('vos-hosting')` 호출 또는 어드민 UI)

### 마지막 단계 (Step D 예정)
- 코어에서 호스팅 관련 파일 모두 제거
- `strip-hosting-service.php` 폐기 (vos-hosting 디렉토리 자체 빠지므로 정규식 패치 불필요)
- `build-voscms.sh` 에 `--exclude=plugins/vos-hosting` 추가하고 다른 패치 제거

## 배포 정책
- **포함**: voscms.com 본 사이트 (자체 호스팅 운영)
- **제외**: 일반 다운로드 배포 (`build-voscms.sh` 가 `plugins/vos-hosting/` 디렉토리 자체 제외)

## 커밋 분리 권장
- 1차: 이번 Step A (이 README + plugin.json + 복사된 파일들). voscms.com 영향 없음
- 2차: Step B (include 경로 수정 + 코어 라우트 제거)
- 3차: Step C 검증 후 Step D (코어 파일 제거 + 빌드 스크립트 정리)
