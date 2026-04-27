# VosCMS 플러그인 아키텍처

작성일: 2026-04-26
대상 버전: VosCMS v2.3.x

VosCMS 는 **코어 + 플러그인** 2계층 구조다. 코어는 모든 VosCMS 인스턴스가 공통으로 갖는 표준 기능, 플러그인은 사이트별로 선택적으로 활성화하는 기능이다.

---

## 1. 코어 vs 플러그인 분류 기준

| 구분 | 기준 | 예시 |
|---|---|---|
| **코어** | 모든 VosCMS 사이트가 공통으로 가져야 하는 기능 | 대시보드, 회원, 게시판, 사이트 관리, 위젯, 페이지, 메뉴, 설정, 시스템 |
| **플러그인** | 일부 사이트만 쓰는 도메인 특화 기능, 또는 외부 연동 | 마켓 클라이언트, 마켓 서버, 호스팅, 살롱 예약, 쇼핑몰 |

**판단 가이드**:
- 일반 CMS 사용자 100명 중 80명 이상이 쓸 기능이면 → 코어
- 특정 비즈니스 도메인 (살롱, 호스팅, 쇼핑몰 등) 에서만 쓰는 기능이면 → 플러그인
- 외부 서비스 연동 (마켓플레이스 클라이언트/서버 등) 이면 → 플러그인

---

## 2. 현재 plugins/ 구조

```
plugins/
├── vos-autoinstall       # 마켓 클라이언트 — 마켓에서 받아서 설치
├── vos-license-manager   # 라이선스 매니저 — 라이선스 키 발급/검증
├── vos-market            # 마켓 서버 — 마켓 운영 (파트너/주문/라이선스)
├── vos-salon             # 살롱 비즈니스 — 사업장 등록·예약·B&A 갤러리·1:1 상담
├── vos-shop              # 쇼핑몰 — 상품·주문·결제·배송 (draft, 작업 예정)
└── vos-hosting           # 호스팅 사업자 — 서비스 주문·구독·도메인·메일 (migrating, 코어→플러그인 이전 중)
```

| 플러그인 | id | status | 역할 | 용도 |
|---|---|---|---|---|
| vos-autoinstall | `vos-autoinstall` | active | 마켓 **클라이언트** | 모든 voscms 인스턴스 |
| vos-license-manager | `vos-license-manager` | active | 라이선스 발급/검증 | 마켓 운영 사이트 |
| vos-market | `vos-market` | active | 마켓 **서버** | market.21ces.com 같은 마켓 운영 사이트 |
| vos-salon | `vos-salon` | active | 살롱 비즈니스 | 미용업 사이트 |
| vos-shop | `vos-shop` | **draft** | 쇼핑몰 | 일반 온라인 쇼핑몰 (작업 예정) |
| vos-hosting | `vos-hosting` | **migrating** | 호스팅 사업자 | voscms.com 본 사이트 |

### 짝 (pair) 플러그인

- **마켓 클라이언트 + 서버**: `vos-autoinstall` (구매하는 쪽) + `vos-market` (판매하는 쪽)
  - 일반 사용자 사이트: `vos-autoinstall` 만 활성
  - 마켓 운영 사이트 (market.21ces.com): 두 개 모두 활성

---

## 3. plugin.json 스펙

플러그인의 모든 메타데이터는 `plugin.json` 한 곳에 모인다. PluginManager 가 이 파일을 스캔해서 자동으로 메뉴/라우트/마이그레이션을 등록한다.

```json
{
    "id": "vos-{name}",
    "name": { "ko": "...", "en": "...", "ja": "...", ... },
    "description": { "ko": "...", "en": "...", "ja": "..." },
    "version": "1.0.0",
    "status": "active|draft|migrating|deprecated",
    "author": { "name": "VosCMS", "url": "https://voscms.com" },
    "license": "proprietary|MIT|GPLv2|...",
    "category": "system|business|integration|...",
    "tags": ["..."],
    "requires": { "php": ">=8.1", "plugins": ["vos-license-manager"] },
    "icon": "<SVG path d 값>",

    "menus": {
        "admin": [
            {
                "id": "...",
                "title": { "ko": "...", "en": "...", ... },
                "icon": "...",
                "permission": "...",
                "position": 60,
                "items": [
                    { "title": { "ko": "...", "en": "..." }, "route": "..." }
                ]
            }
        ],
        "user_dropdown": [ ... ]
    },

    "routes": {
        "admin": [
            { "path": "...", "view": "admin/.../index.php", "method": "GET" }
        ],
        "front": [ ... ],
        "api":   [ ... ]
    },

    "migrations": [
        "migrations/001_create_xxx.sql"
    ],

    "hooks": {
        "plugin.update.check": "hooks/update-check.php"
    },

    "settings": {
        "defaults": { "key": "value" }
    }
}
```

### 핵심 키
- **`id`** (필수): PluginManager 식별자. 디렉토리명과 일치 권장. **`slug` 사용 금지** (PluginManager 가 인식 못 함)
- **`scope`** (선택): `hq_only` 지정 시 본사(voscms.com) 전용 — 배포판에서 제외
- **`menus.admin`** / **`menus.mypage`**: 사이드바에 자동 추가. `position` 으로 정렬, `section` 으로 영역(top/main/bottom) 지정
- **`routes`**: 어드민/프론트/API 라우트. `view` 는 플러그인 디렉토리 기준 상대 경로
- **`migrations`**: PluginManager::install() 시 순차 실행, `rzx_plugin_migrations` 테이블에 기록되어 멱등성 보장

---

## 4. 자동 메뉴 등록 / 자동 제거 동작 원리

### 등록 흐름 (어드민 / 마이페이지 공통)
1. PluginManager 가 부팅 시 `rzx_plugins WHERE is_active=1` 조회
2. 각 활성 플러그인에 대해 `plugins/{id}/plugin.json` 읽음
3. `plugin.json` 의 `menus.admin` / `menus.mypage` 를 코어 메뉴(`config/admin-menu.php` / `config/mypage-menu.php`)와 병합
4. 사이드바 렌더러가 `position` 순 정렬 후 `section` 별로 분리해 영역 사이에 자동 구분선 출력
5. 헬퍼: `load_menu($type)` ([rzxlib/Core/Helpers/functions.php](../rzxlib/Core/Helpers/functions.php)) — `$type` ∈ `admin`, `admin_dropdown`, `mypage`, `user_dropdown`

### 3-영역 구조 (`section` 필드)

코어 메뉴와 플러그인 메뉴를 시각적으로 분리하기 위해 메뉴 항목에 `section` 필드를 둔다 (2.3.9~).

| section | 의미 | 기본 |
|---------|------|------|
| `top` | 코어 상단 (대시보드/프로필 등) | — |
| `main` | 자동 추가 영역 (플러그인 기본값) | ✅ 미지정 시 |
| `bottom` | 코어 하단 (설정/로그아웃 등) | — |

영역에 표시 가능한 항목이 하나도 없으면 **구분선까지 통째로 스킵**된다.

**어드민 사이드바 예시**:
- top: 대시보드(0) / 회원 관리(10) / 사이트 관리(20) / 자동 설치(30, vos-autoinstall)
- main: VosCMS 라이센스(50, vos-license-manager) / 호스팅 관리(65, vos-hosting)
- bottom: 플러그인(80) / 설정(90)

**마이페이지 사이드바 예시**:
- top: 대시보드(0) / 프로필(10) / 메시지(20)
- main: 서비스(25, vos-hosting) — 다른 플러그인 메뉴가 여기에 자동 누적
- bottom: 설정(70) / 비밀번호(80) / 회원탈퇴(90) / 로그아웃(99)

### plugin.json 메뉴 등록 예시

```json
"menus": {
    "admin": [
        {
            "id": "hosting",
            "title": { "ko": "호스팅 관리", "en": "Hosting Management", ... },
            "icon": "M5 12H3l9-9 ...",
            "position": 65,
            "section": "main",
            "permission": "services",
            "items": [...]
        }
    ],
    "mypage": [
        {
            "key": "services",
            "url": "/mypage/services",
            "label": "auth.mypage.menu.services",
            "icon": "<path .../>",
            "position": 25,
            "section": "main"
        }
    ]
}
```

`label` 이 점(`.`)을 포함하면 `__()` 로 번역, `title` 이 배열이면 현재 로케일로 추출.

### 자동 제거
**플러그인 디렉토리를 삭제하면**:
- `plugin.json` 못 읽음 → `loadPlugin()` 이 false 반환 → `$adminMenus` 에 추가 안 됨 → **메뉴 자동 사라짐**
- `rzx_plugins` 테이블의 row 는 남아있지만 manifest 없으니 비활성화 상태와 같음
- DB 데이터 (예: 주문, 회원 정보 등) 는 **보존됨**. 디렉토리 다시 넣으면 복구

### 권장 패턴
- **임시 비활성화**: `is_active = 0` (메뉴 숨김, 데이터 보존)
- **완전 제거**: 디렉토리 삭제 + `PluginManager::uninstall()` (`rzx_plugins`, `rzx_plugin_migrations`, `rzx_plugin_settings` 정리)
- **배포판에서 제외**: 빌드 스크립트에서 `--exclude=plugins/{id}` (디렉토리 자체가 빠지므로 위 자동 제거 효과)

---

## 5. 배포 정책

| 플러그인 | 코어 배포 (build-voscms.sh) | voscms.com 본 사이트 | 비고 |
|---|---|---|---|
| vos-autoinstall | ✅ 포함 | ✅ 활성 | 모든 voscms 사이트가 마켓에서 받기 위해 필요 |
| vos-license-manager | ❌ 제외 | ✅ 활성 | 마켓 운영 사이트(voscms.com, market.21ces.com)만 필요 |
| vos-market | ❌ 제외 | ✅ 활성 (voscms.com) | 마켓 서버 운영용 |
| vos-salon | ✅ 포함 | ❌ 비활성 | 살롱 사이트가 필요 시 활성화 |
| vos-shop | ✅ 포함 | ❌ 비활성 | 쇼핑몰 사이트가 필요 시 활성화 |
| vos-hosting | ❌ 제외 | ✅ 활성 (voscms.com) | 호스팅 사업자(voscms.com)만 필요 |
| vos-license-server | ❌ 제외 | ✅ 활성 (voscms.com) | 라이선스 검증 API (`scope: hq_only`) — 외부 노출 금지 |
| vos-developer | ❌ 제외 | ✅ 활성 (voscms.com) | 개발자 포털 API (`scope: hq_only`) — 외부 노출 금지 |

### build-voscms.sh 의 exclude 패턴
```bash
rsync -a \
  --exclude=plugins/vos-license-manager \
  --exclude=plugins/vos-license-server \
  --exclude=plugins/vos-developer \
  --exclude=plugins/vos-marketplace \
  --exclude=plugins/vos-hosting \
  --exclude=api/license \
  --exclude=api/developer \
  --exclude=api/notices.php \
  ...
```

### HQ 전용 API 보안 (2.3.9~)

라이선스/개발자 API는 본사(voscms.com) 전용이므로 **세 단계로 노출 차단**:

1. **코드 레이어**: `api/_plugin_dispatch.php` 가 `rzx_plugins.is_active` 체크 → 플러그인 활성 안 되어 있으면 404
2. **빌드 레이어**: `build-voscms.sh` 가 `api/license/`, `api/developer/`, `api/notices.php`, `vos-license-server`, `vos-developer` 디렉토리 제외
3. **호스트 레이어**: `api/notices.php` 가 `HQ_HOSTS` env 화이트리스트 체크

`vos-hosting` 이전 완료 후, `strip-hosting-service.php` 의 정규식 패치 (AdminRouter, index.php, system-pages, admin-menu) 는 모두 **불필요해진다**. 디렉토리만 빼면 자동 정리되기 때문.

---

## 6. vos-hosting 이전 작업 계획 (진행 중)

코어에 박혀있는 호스팅 관련 코드를 단계적으로 `plugins/vos-hosting/` 으로 이전한다. 자세한 항목은 [plugins/vos-hosting/README.md](../plugins/vos-hosting/README.md) 참고.

### 이전 후 효과
1. **`strip-hosting-service.php` 폐기** — 정규식 패치 5종 모두 불필요
2. **build-voscms.sh 단순화** — `--exclude=plugins/vos-hosting` 한 줄로 대체
3. **사장님이 의도한 플러그인 아키텍처 복구** — 호스팅이 처음부터 플러그인이었어야 했던 위치로 정리

---

## 7. 향후 ionCube 인코딩 전략 (선별 인코딩)

다운로드 라이선스 매출 모델에서 보호 가치가 있는 파일만 인코딩한다 (모든 파일 통째 인코딩 금지).

### 인코딩 대상 (5~6개)
| 파일 | 보호 이유 |
|---|---|
| `rzxlib/Core/License/LicenseClient.php` | 라이선스 검증 우회 차단 |
| `rzxlib/Core/License/LicenseStatus.php` | 검증 결과 캐시 보호 |
| `plugins/vos-autoinstall/src/MarketplaceService.php` | 마켓 결제 검증 |
| `plugins/vos-autoinstall/src/LicenseService.php` | 라이선스 등록 |
| `plugins/vos-autoinstall/src/InstallerService.php` | 다운로드 토큰 |
| `install-core.php` | 신규 설치 시 라이선스 등록 호출 |

### 인코딩 제외 (평문 유지)
- `install.php` — Loader 미설치 환경에서 환경 체크 + 호스팅 안내가 노출되어야 함
- Auth, PluginManager, Hook 등 표준 패턴 코드 — 보호 효과 없음
- 코어 표준 기능 — 보호 가치 없음

### 평가판 vs 정식
- **ionCube 평가판**: 인코딩 후 **36시간** 만료. 만료된 코드는 무한 루프 / segfault 발생 (실제로 504 사건 원인이었음). 배포에 사용 불가
- **정식 ionCube** (Basic Encoder ~$199): 만료 없음, 도메인 락 가능 (Cerberus 모듈)
- 도입 시점: 첫 라이선스 매출 발생 후

---

## 8. 참고 — 해결된 문제 사례

### 504 Bad Gateway / 무한 루프 (2026-04-26)
**증상**: Step 4 "설치 완료" 클릭 시 100초 후 504, PHP-FPM 워커 99.9% CPU 32분
**원인**: ionCube 평가판으로 인코딩한 PluginManager.php 가 36시간 한도 초과 (428시간 경과) → 디코딩 시 무한 루프
**해결**: 만료된 인코딩 파일 11개를 평문으로 복구. 추가로 PHP-FPM `max_children` 5→15, `request_terminate_timeout` 120 설정 보강. Step 4 INSERT 1,400건 트랜잭션 미사용 문제도 함께 해결 (트랜잭션 묶음 → 17초 → 1초 미만)

### service-orders 메뉴 잔존 (2026-04-26)
**증상**: 배포판 어드민 사이드바에 voscms.com 호스팅 전용인 "서비스 주문" 메뉴가 그대로 표시됨
**원인**: `strip-hosting-service.php` 가 AdminRouter / index.php / system-pages 만 패치하고 `config/admin-menu.php` 는 빠짐
**해결**: 즉시 `admin-menu.php` 패치 + `strip-hosting-service.php` 에 admin-menu 정규식 패치 추가. 근본 해결은 `vos-hosting` 플러그인 이전 (이전 완료 시 strip 자체 폐기)

### vos-shop 죽은 코드 (2026-04-26)
**증상**: `plugins/vos-shop/` 에 살롱 사업장 관리 코드가 들어있었으나 어디서도 동작하지 않음
**원인**: `plugin.json` 의 식별자 키가 `slug` 로 되어있어서 PluginManager (`id` 만 인식) 가 무시. v2.1.0 commit 시 rezlyx/salon 코드가 잘못 혼입
**해결**: `vos-shop` → `vos-salon` 으로 rename + `slug` → `id` 수정. 새 `vos-shop` 은 쇼핑몰 골격으로 신규 작성
