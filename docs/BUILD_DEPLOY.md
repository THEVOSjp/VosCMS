# VosCMS 빌드 & 배포 전략

> 작성일: 2026-04-12
> 단일 소스(vos.21ces.com)에서 개발 → 사이트별 배포 패키지 생성 → 테스트 → 프로덕션 업로드

---

## 1. 개발 원칙

```
개발: vos.21ces.com 에서만 (단일 소스)
테스트: dev.21ces.com(포털), salon.21ces.com(살롱) 에서 배포 후 테스트
프로덕션: 테스트 통과 → 프로덕션 서버에 업로드
```

**규칙:**
- 모든 코드 수정은 `/var/www/voscms/` 에서만 수행
- dev, salon 서버에서 직접 코드 수정 금지 (테스트만)
- 배포 스크립트로 동기화 → 수동 편집으로 인한 코드 충돌 방지

---

## 2. 소스 구조 (vos.21ces.com)

```
/var/www/voscms/                    ← 단일 소스 (GitHub: THEVOSjp/VosCMS)
  │
  ├── rzxlib/                       ← 코어 라이브러리 [공통]
  ├── resources/views/              ← 뷰 파일 [공통]
  ├── resources/lang/               ← 다국어 [공통]
  ├── resources/css/                ← CSS [공통]
  ├── assets/                       ← 프론트엔드 에셋 [공통]
  ├── index.php                     ← 라우터 [공통 + 사이트별 라우트]
  ├── install.php                   ← 설치 마법사 [공통]
  ├── database/                     ← 마이그레이션 [공통]
  ├── vendor/                       ← Composer [공통]
  │
  ├── widgets/                      ← 위젯 [배포별 선택]
  │   ├── hero, hero-cta2, features, stats, ...  ← 코어 위젯 (모든 배포에 포함)
  │   ├── booking, lookup, services, staff, ...  ← 비즈니스 위젯 (salon 배포에 포함)
  │   ├── shop-map, shop-ranking, ...            ← 매장 위젯 (포털 배포에 포함)
  │   ├── hair-story, reviews, ...               ← salon 전용 (salon 배포에 포함)
  │   └── stylebook, before-after, ...           ← 확장 위젯 (유료 또는 선택 포함)
  │
  ├── skins/                        ← 스킨 [배포별 선택]
  │   ├── board/default/            ← 기본 스킨 (모든 배포)
  │   ├── board/faq/                ← FAQ 스킨 (모든 배포)
  │   └── layouts/                  ← 레이아웃 (모든 배포)
  │
  ├── plugins/                      ← 플러그인 [배포별 선택]
  │   ├── vos-marketplace/          ← VosCMS 배포에 포함
  │   ├── vos-license-manager/      ← 본사 서버 전용
  │   ├── vos-salon/                ← salon 배포에 포함
  │   ├── vos-pos/                  ← salon 배포에 포함
  │   ├── vos-kiosk/                ← salon 배포에 포함
  │   ├── vos-attendance/           ← salon 배포에 포함
  │   ├── vos-shop/                 ← 포털 배포에 포함
  │   ├── vos-before-after/         ← salon 배포에 포함
  │   ├── vos-hair-story/           ← salon 배포에 포함
  │   └── vos-review/               ← salon 배포에 포함
  │
  └── api/                          ← API [배포별 선택]
      ├── license/                  ← 본사 서버 전용
      ├── developer/                ← 본사 서버 전용
      └── notices.php               ← 본사 서버 전용
```

---

## 3. 배포 패키지 구성

### 3.1 VosCMS 코어 배포 (일반 사이트용)

**대상:** VosCMS를 설치하여 홈페이지/커뮤니티를 만드는 일반 사용자

| 구분 | 포함 항목 |
|------|----------|
| **코어** | rzxlib/, index.php, install.php, database/, vendor/ |
| **뷰** | resources/views/ (admin, customer, components, layouts) |
| **다국어** | resources/lang/ (13개 언어) |
| **플러그인** | vos-marketplace (자동 설치) |
| **위젯 (코어)** | hero, hero-cta2, hero-slider, features, stats, testimonials, cta, text, spacer, board-contents, grid-section, feature-tour |
| **스킨** | board/default, board/faq |
| **레이아웃** | default, minimal, modern |
| **제외** | vos-license-manager, vos-salon, vos-pos 등 모든 업종 플러그인 |
| **제외 위젯** | booking, lookup, services, staff, shop-map, shop-ranking, stylebook, before-after, compare-slider, hair-story, reviews |
| **제외 API** | api/license/, api/developer/, api/notices.php |

**코어 위젯 (12개) — 무료 배포:**
1. hero — 히어로 배너
2. hero-cta2 — 히어로 CTA (타이핑)
3. hero-slider — 히어로 슬라이더
4. features — 기능 카드
5. stats — 통계
6. testimonials — 고객 후기
7. cta — 행동 유도
8. text — 텍스트
9. spacer — 여백
10. board-contents — 게시판 콘텐츠
11. grid-section — 그리드 섹션
12. feature-tour — 기능 소개 투어

**유료 위젯 (마켓플레이스에서 별도 구매):**
- cta001, booking, lookup, services, staff, shop-map, shop-ranking, stylebook, before-after, compare-slider

### 3.2 RezlyX Salon 배포 (미용실/살롱용)

**대상:** 미용실, 네일샵, 에스테틱 등 뷰티 업종

| 구분 | 포함 항목 |
|------|----------|
| **코어** | VosCMS 코어 전체 |
| **플러그인** | vos-marketplace + vos-salon + vos-pos + vos-kiosk + vos-attendance + vos-before-after + vos-hair-story + vos-review |
| **위젯 (전체)** | 코어 12개 + booking, lookup, services, staff, before-after, compare-slider, stylebook, hair-story, reviews, cta001 = 22개 |
| **스킨** | board/default, board/faq |
| **레이아웃** | default, minimal, modern |

### 3.3 RezlyX Portal 배포 (사업장 포털용)

**대상:** rezlyx.com 등 사업장 검색/리뷰 포털 사이트

| 구분 | 포함 항목 |
|------|----------|
| **코어** | VosCMS 코어 전체 |
| **플러그인** | vos-marketplace + vos-shop |
| **위젯** | 코어 12개 + shop-map, shop-ranking, stylebook = 15개 |
| **스킨** | board/default, board/faq |
| **레이아웃** | default, minimal, modern |

### 3.4 본사 서버 (voscms.com / vos.21ces.com)

**대상:** VosCMS 공식 사이트 — 라이선스 서버 + 마켓플레이스 + 개발자 포털

| 구분 | 포함 항목 |
|------|----------|
| **코어** | VosCMS 코어 전체 |
| **플러그인** | vos-marketplace + vos-license-manager |
| **API** | api/license/, api/developer/, api/notices.php |
| **뷰** | developer/, marketplace/ 페이지 포함 |
| **위젯** | 코어 12개 |
| **DB** | vcs_licenses, vcs_developers, vcs_review_queue 등 라이선스/개발자 테이블 |

---

## 4. 배포별 위젯 매트릭스

| 위젯 | VosCMS | Salon | Portal | 본사 |
|------|:------:|:-----:|:------:|:----:|
| hero | ✅ | ✅ | ✅ | ✅ |
| hero-cta2 | ✅ | ✅ | ✅ | ✅ |
| hero-slider | ✅ | ✅ | ✅ | ✅ |
| features | ✅ | ✅ | ✅ | ✅ |
| stats | ✅ | ✅ | ✅ | ✅ |
| testimonials | ✅ | ✅ | ✅ | ✅ |
| cta | ✅ | ✅ | ✅ | ✅ |
| text | ✅ | ✅ | ✅ | ✅ |
| spacer | ✅ | ✅ | ✅ | ✅ |
| board-contents | ✅ | ✅ | ✅ | ✅ |
| grid-section | ✅ | ✅ | ✅ | ✅ |
| feature-tour | ✅ | ✅ | ✅ | ✅ |
| booking | ❌ | ✅ | ❌ | ❌ |
| lookup | ❌ | ✅ | ❌ | ❌ |
| services | ❌ | ✅ | ❌ | ❌ |
| staff | ❌ | ✅ | ❌ | ❌ |
| cta001 | ❌ | ✅ | ❌ | ❌ |
| before-after | ❌ | ✅ | ❌ | ❌ |
| compare-slider | ❌ | ✅ | ❌ | ❌ |
| stylebook | ❌ | ✅ | ✅ | ❌ |
| hair-story | ❌ | ✅ | ❌ | ❌ |
| reviews | ❌ | ✅ | ❌ | ❌ |
| shop-map | ❌ | ❌ | ✅ | ❌ |
| shop-ranking | ❌ | ❌ | ✅ | ❌ |

---

## 5. 배포별 플러그인 매트릭스

| 플러그인 | VosCMS | Salon | Portal | 본사 |
|---------|:------:|:-----:|:------:|:----:|
| vos-marketplace | ✅ | ✅ | ✅ | ✅ |
| vos-license-manager | ❌ | ❌ | ❌ | ✅ |
| vos-salon | ❌ | ✅ | ❌ | ❌ |
| vos-pos | ❌ | ✅ | ❌ | ❌ |
| vos-kiosk | ❌ | ✅ | ❌ | ❌ |
| vos-attendance | ❌ | ✅ | ❌ | ❌ |
| vos-before-after | ❌ | ✅ | ❌ | ❌ |
| vos-hair-story | ❌ | ✅ | ❌ | ❌ |
| vos-review | ❌ | ✅ | ❌ | ❌ |
| vos-shop | ❌ | ❌ | ✅ | ❌ |

---

## 6. 배포 프로세스

### 6.1 빌드 스크립트

```
/var/www/voscms-dist/
  ├── build-voscms.sh       ← VosCMS 코어 배포 빌드
  ├── build-salon.sh        ← RezlyX Salon 배포 빌드
  ├── build-portal.sh       ← RezlyX Portal 배포 빌드
  ├── obfuscate.php         ← 코드 난독화 (공통)
  ├── voscms-2.1.0/         ← VosCMS 배포본
  ├── voscms-2.1.0.zip      ← VosCMS 배포 ZIP
  └── download/             ← 다운로드 심볼릭 링크
```

### 6.2 공통 빌드 단계

```
1. /var/www/voscms/ 에서 파일 복사 (rsync, 제외 항목 적용)
2. 개발용 파일 제거
3. .env.example 생성
4. storage 디렉토리 생성
5. 코드 난독화 (obfuscate.php)
6. ZIP 패키징
7. 다운로드 링크 업데이트
```

### 6.3 테스트 서버 동기화

```bash
# salon.21ces.com 동기화 (코어만, 플러그인은 그대로)
rsync -av --delete \
  /var/www/voscms/rzxlib/ /var/www/salon/rzxlib/
rsync -av \
  /var/www/voscms/resources/views/ /var/www/salon/resources/views/
rsync -av \
  /var/www/voscms/resources/lang/ /var/www/salon/resources/lang/

# dev.21ces.com 동기화
rsync -av --delete \
  /var/www/voscms/rzxlib/ /var/www/rezlyx/rzxlib/
rsync -av \
  /var/www/voscms/resources/views/ /var/www/rezlyx/resources/views/
rsync -av \
  /var/www/voscms/resources/lang/ /var/www/rezlyx/resources/lang/
```

---

## 7. salon 전용 플러그인 통합 계획

현재 salon 플러그인은 `/var/www/salon/plugins/`에만 있습니다. 단일 소스 원칙에 따라 vos에 통합해야 합니다:

```
/var/www/salon/plugins/vos-salon/      → /var/www/voscms/plugins/vos-salon/
/var/www/salon/plugins/vos-pos/        → /var/www/voscms/plugins/vos-pos/
/var/www/salon/plugins/vos-kiosk/      → /var/www/voscms/plugins/vos-kiosk/
/var/www/salon/plugins/vos-attendance/ → /var/www/voscms/plugins/vos-attendance/
/var/www/salon/plugins/vos-before-after/ → /var/www/voscms/plugins/vos-before-after/
/var/www/salon/plugins/vos-hair-story/ → /var/www/voscms/plugins/vos-hair-story/
/var/www/salon/plugins/vos-review/     → /var/www/voscms/plugins/vos-review/

/var/www/salon/widgets/hair-story/     → /var/www/voscms/widgets/hair-story/
/var/www/salon/widgets/reviews/        → /var/www/voscms/widgets/reviews/
```

**주의:** 통합 후 salon에서 직접 수정하지 않고, vos에서 수정 → 배포 스크립트로 동기화

---

## 8. index.php 라우트 분리 (v2.2.0 예정)

현재 index.php에 모든 라우트가 하드코딩되어 사이트별 덮어쓰기가 어려움.

**v2.2.0 목표:**
```php
// index.php (최소한의 부트스트랩)
include 'routes/core.php';      // 코어 라우트 — 업데이트 시 덮어쓰기 OK
include 'routes/plugins.php';   // 플러그인 라우트 — PluginManager가 자동 로드
if (file_exists('routes/custom.php')) {
    include 'routes/custom.php'; // 사이트별 커스텀 — 건드리지 않음
}
```

이렇게 분리하면:
- 코어 업데이트: `routes/core.php` 덮어쓰기 → 안전
- 사이트 커스텀: `routes/custom.php` → 유지
- index.php: 거의 변경 없음 → 덮어쓰기 가능
