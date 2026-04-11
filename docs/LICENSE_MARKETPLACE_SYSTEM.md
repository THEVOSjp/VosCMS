# VosCMS 라이선스 & 마켓플레이스 통합 시스템 계획서

> 작성일: 2026-04-10 / 최종 수정: 2026-04-10
> 이 문서는 VosCMS의 라이선스 인증, 마켓플레이스 플러그인, 배포 시스템을 통합 정리한 최종 계획서입니다.
> 관련 문서: DEPLOYMENT_PLAN.md, MARKETPLACE_PLUGIN_PLAN.md, VOSCMS_PLUGIN_STRATEGY.md

---

## 0. 서버 인프라 현황 및 이전 계획

### 0.1 현재 서버 현황

| 서버 | 용도 | 호스팅 | PHP | ionCube | 문제점 |
|------|------|--------|-----|---------|--------|
| **vos.21ces.com** | VosCMS 개발/테스트 | VPS (자체) | 8.3 + 8.5 | ✅ v15.5 설치 완료 | 없음 (개발 서버로 적합) |
| **rezlyx.com** (StarServer) | 사용자 포털 + 데모 | 공유호스팅 | 8.0 (기본), 8.3 사용 가능 | ❌ 설치 불가 | 아래 상세 |

### 0.2 rezlyx.com (StarServer) 문제점

**조사 결과 (2026-04-10):**
- **호스팅:** StarServer 공유호스팅 (sv10005.star.ne.jp)
- **OS:** Rocky Linux 8.10
- **기본 PHP:** 8.0.30 (cli), PHP 8.3.21도 설치되어 있으나 기본값 아님
- **ionCube:** 미설치. extension 디렉토리(`/opt/php-8.3.21/lib/20230831/`)에 쓰기 권한 없음
- **제한 사항:**
  - `sudo` 불가 → 시스템 레벨 패키지 설치 불가
  - PHP extension 직접 설치 불가 (공유호스팅 제약)
  - cron 제한적
  - 프로세스 상시 실행 불가 (큐 워커, 데몬 등)
  - SSL 인증서 자동 갱신은 호스팅 패널에서 관리

### 0.3 서버 변경이 필요한 이유

| # | 이유 | 상세 |
|---|------|------|
| 1 | **ionCube Loader 설치 불가** | 라이선스 서버의 핵심 코드를 ionCube로 보호해야 하나, 공유호스팅에서 Zend Extension 설치 불가. 라이선스 검증 로직이 평문 노출되면 우회 가능. |
| 2 | **PHP 버전 제약** | 기본 PHP 8.0으로 VosCMS 최소 요구사항(8.1+) 미충족. .htaccess로 PHP 버전 변경 가능하나 불안정. |
| 3 | **라이선스 서버 신뢰성** | 공유호스팅은 다른 사용자의 트래픽에 영향 받음. 라이선스 API는 모든 VosCMS 설치본이 주기적으로 호출하므로 안정성이 중요. |
| 4 | **프로세스 제어 불가** | 향후 큐 워커(결제 웹훅 처리), 스케줄러(라이선스 만료 체크), 백그라운드 작업 실행 불가. |
| 5 | **확장성** | 고객 수 증가 시 서버 스케일업 불가. 공유호스팅은 CPU/메모리 상한 고정. |
| 6 | **보안** | 라이선스 DB에 모든 고객의 도메인, 키, 구매 정보가 저장됨. 공유호스팅은 같은 물리 서버의 다른 사용자와 파일시스템 공유. |

### 0.4 추천 서버

#### 추천: **전용 서버** (voscms.com + rezlyx.com 통합 운영)

현재 개발 서버(vos.21ces.com) 사양: **32코어 Xeon E5-2683 v4 / 64GB RAM / 116GB NVMe / Ubuntu 24.04**
동급 이상의 전용 서버를 준비하여 voscms.com + rezlyx.com을 통합 운영.

**전용 서버 장점:**
- 자원 공유 없음 → VPS보다 안정적 성능
- root 완전 권한 → ionCube, PHP, Nginx 자유 설정
- 여러 서비스 통합 운영 (SSL, 백업, DB, 모니터링 한 곳)
- rezlyx.com StarServer 공유호스팅의 ionCube/PHP 제약 완전 해소
- 현재 개발 서버로 성능 이미 검증됨

#### 서버 구성 계획 (전용 서버)

```
새 전용 서버
  ├── OS: Ubuntu 24.04 LTS
  ├── PHP: 8.3 + ionCube Loader
  ├── Nginx + PHP-FPM
  ├── MariaDB 10.11+
  ├── SSL: Let's Encrypt (와일드카드)
  │
  ├── voscms.com                    ← VosCMS 공식 사이트 + 라이선스 서버 + 마켓플레이스
  │     ├── /api/license/           ← 라이선스 API
  │     ├── /api/marketplace/       ← 마켓플레이스 API
  │     ├── /marketplace/           ← 프론트엔드 마켓플레이스
  │     ├── /developer/             ← 개발자 대시보드
  │     └── /admin/                 ← 본사 관리 (라이선스+심사+정산)
  │
  ├── rezlyx.com                    ← 사용자 포털
  ├── salon.rezlyx.com              ← 살롱 데모
  ├── clinic.rezlyx.com             ← 클리닉 데모
  └── ...기타 데모 사이트

현재 서버 (vos.21ces.com)
  └── 개발/테스트 전용 유지
```

#### 전용 서버 이전 절차

서버 준비 후 SSH 접속 정보(IP, 포트, 키)만 제공하면 Claude가 전체 세팅:

```
1. OS 초기 설정 (Ubuntu 24.04 LTS)
   - 사용자 생성, SSH 키 설정, 타임존
2. 서버 스택 설치
   - Nginx + PHP 8.3 FPM + MariaDB 10.11
   - ionCube Loader v15.5
   - Composer, curl, zip 등
3. SSL 인증서
   - Let's Encrypt 와일드카드 (*.voscms.com)
   - certbot + DNS 인증
4. 가상호스트 설정
   - voscms.com → /var/www/voscms.com/
5. 데이터 이전
   - vos.21ces.com에서 DB 덤프 (vcs_* 테이블)
   - 파일 rsync (api/, rzxlib/, plugins/ 등)
   - .env URL 변경 (vos.21ces.com → voscms.com)
6. 보안
   - ufw 방화벽 (22, 80, 443만 오픈)
   - fail2ban
   - PHP opcache 활성화
7. 검증
   - API 엔드포인트 테스트
   - SSL 확인
   - 기존 VosCMS 설치본의 LICENSE_SERVER URL 변경
```

### 0.5 현재 개발/테스트 전략

전용 서버 확보 전까지 **vos.21ces.com에서 모든 개발/테스트**를 진행한다.

```
[현재] vos.21ces.com (32코어 Xeon / 64GB RAM / PHP 8.3 + ionCube v15.5)
  ├── VosCMS 본체 개발/테스트
  ├── 라이선스 서버 API 개발/테스트
  ├── 마켓플레이스 + 개발자 포털
  └── FAQ 스킨 등 UI 개발

[향후] 전용 서버로 이전
  ├── voscms.com — VosCMS 공식 + 라이선스 + 마켓플레이스
  ├── rezlyx.com — 사용자 포털 (StarServer에서 이전)
  └── vos.21ces.com — 개발/테스트 전용 유지
```

**vos.21ces.com에서 라이선스 API 경로:**
```
https://vos.21ces.com/api/license/register
https://vos.21ces.com/api/license/verify
https://vos.21ces.com/api/license/register-plugin
https://vos.21ces.com/api/license/check
```

이후 voscms.com VPS가 준비되면 .env의 `LICENSE_SERVER` URL만 변경하면 전환 완료.

---

## 1. 비즈니스 모델

```
VosCMS 코어 (무료)
  │
  ├── 유료 플러그인 ─── 마켓플레이스에서 구매
  │     ├── vos-salon (살롱 번들)
  │     ├── vos-pos (POS)
  │     ├── vos-kiosk (키오스크)
  │     ├── vos-attendance (근태)
  │     ├── vos-clinic (향후)
  │     ├── vos-restaurant (향후)
  │     └── ...
  │
  ├── 유료 테마/위젯/스킨 ─── 마켓플레이스에서 구매
  │
  ├── 장비 (하드웨어) ─── 마켓플레이스에서 주문
  │     ├── POS 단말기
  │     ├── 키오스크 장비
  │     ├── 영수증 프린터
  │     └── ...
  │
  └── RezlyX 올인원 번들 (유료)
        = VosCMS + salon + pos + kiosk + attendance
```

**핵심 원칙:**
- VosCMS 코어는 무료로 배포. 누구나 설치 가능.
- 수익은 플러그인 판매 + 장비 판매 + 프리미엄 지원.
- 모든 유료 아이템은 라이선스 서버(voscms.com)에서 도메인 단위로 통제.

---

## 2. 서버 구조

```
┌─────────────────────────┐
│  voscms.com              │  배포 서버 + 라이선스 서버
│                          │
│  ├── 라이선스 API         │  /api/license/register, /verify, /check
│  ├── 마켓플레이스 API     │  /api/marketplace/catalog, /purchase, /download
│  ├── 라이선스 관리 DB     │  licenses, license_plugins, license_logs
│  └── 패키지 저장소        │  플러그인/테마 ZIP 파일 호스팅
│                          │
└─────────────────────────┘

┌─────────────────────────┐
│  rezlyx.com              │  사용자 포털 (별도 제작 중)
│                          │
│  ├── 고객 대시보드        │  내 라이선스 조회, 플러그인 관리
│  ├── 구독/결제 관리       │  플랜 업그레이드, 결제 이력
│  ├── 데모 사이트          │  salon.rezlyx.com, clinic.rezlyx.com
│  └── 지원 센터           │  문의, FAQ, 문서
│                          │
└─────────────────────────┘

┌─────────────────────────┐
│  고객 서버 (각 도메인)    │  VosCMS 설치본
│                          │
│  ├── install.php          │  설치 마법사 (ionCube 암호화)
│  ├── LicenseClient.php    │  라이선스 통신 클라이언트 (ionCube 암호화)
│  ├── vos-marketplace      │  마켓플레이스 플러그인 (관리자 패널)
│  └── 구매한 플러그인들     │  vos-salon, vos-pos 등
│                          │
└─────────────────────────┘
```

---

## 3. 라이선스 시스템

### 3.1 전체 흐름

```
[설치 시]
고객이 VosCMS를 서버에 업로드
  → install.php 접속 (ionCube 암호화됨)
  → localhost/127.0.0.1/.local/.test 차단
  → Step 1: 환경 확인 (PHP 8.1+, ionCube Loader, 확장 모듈)
  → Step 2: DB 설정
  → Step 3: 테이블 생성
  → Step 4: 관리자 계정 + 사이트 설정
  → Step 5: 라이선스 키 자동 생성 ★ NEW
      - RZX-XXXX-XXXX-XXXX 형식으로 키 생성
      - 현재 도메인 자동 감지 (https://customer-shop.com)
      - voscms.com/api/license/register로 전송
        { key, domain, version, php_version, server_ip }
      - 서버 응답: { success, plan: "free", registered_at }
      - 로컬 DB(rzx_settings)에 라이선스 정보 저장
  → Step 6: 설치 완료

[플러그인 구매 시]
관리자 패널 → 마켓플레이스 → 플러그인 탐색 → 구매 클릭
  → 결제 처리 (Stripe)
  → 결제 완료 시:
      voscms.com/api/license/register-plugin 으로 전송
        { license_key, domain, plugin_id, order_id }
      - 라이선스 서버에 "이 도메인이 이 플러그인을 구매함" 등록
      - 플러그인 다운로드 + 자동 설치

[일상 운영 시]
관리자 접속 시 → LicenseClient.php가 주기적으로 체크
  → voscms.com/api/license/verify
    { license_key, domain, version, installed_plugins[] }
  → 서버 응답:
    { valid, plan, allowed_plugins[], days_left, latest_version }
  → 허용되지 않은 플러그인 → 비활성화 또는 경고
  → 캐시 갱신 (7일 유효)
```

### 3.2 라이선스 키

**형식:** `RZX-XXXX-XXXX-XXXX` (16자리 영숫자, 하이픈 구분)

**생성 시점:** install.php Step 5에서 자동 생성 (고객이 입력하는 게 아님)

**생성 로직 (ionCube로 암호화됨):**
```php
function generateLicenseKey(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 혼동 문자 제외 (0,O,1,I)
    $segments = [];
    for ($i = 0; $i < 3; $i++) {
        $seg = '';
        for ($j = 0; $j < 4; $j++) {
            $seg .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $segments[] = $seg;
    }
    return 'RZX-' . implode('-', $segments);
}
// 결과 예: RZX-K7M2-P9X4-N3H8
```

### 3.3 localhost 차단

install.php 실행 초반에 도메인 검증:

```php
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$host = preg_replace('/:\d+$/', '', $host); // 포트 제거

$blocked = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
$blockedSuffixes = ['.local', '.test', '.example', '.invalid'];

$isBlocked = in_array($host, $blocked);
foreach ($blockedSuffixes as $suffix) {
    if (str_ends_with($host, $suffix)) $isBlocked = true;
}
if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false
    && filter_var($host, FILTER_VALIDATE_IP) !== false) {
    $isBlocked = true; // 사설 IP 차단 (192.168.x.x, 10.x.x.x 등)
}

if ($isBlocked) {
    die('VosCMS는 실제 도메인에서만 설치할 수 있습니다. 
         공개 도메인과 SSL 인증서가 필요합니다.');
}
```

### 3.4 ionCube 암호화 대상

| 파일/디렉토리 | 암호화 | 이유 |
|---------------|:------:|------|
| `install.php` | ✅ | 키 생성 로직 + API 통신 보호 |
| `rzxlib/Core/License/LicenseClient.php` | ✅ | 라이선스 검증 로직 보호 |
| `rzxlib/Core/` | ✅ | 인증, 핵심 로직 |
| `rzxlib/Reservation/` | ✅ | 예약 엔진 |
| `plugins/vos-marketplace/src/` | ✅ | 구매/라이선스 로직 |
| `resources/views/` | ❌ | 뷰 커스터마이징 허용 |
| `resources/lang/` | ❌ | 번역 수정 허용 |
| `skins/`, `widgets/` | ❌ | 개발 허용 |

---

## 4. 라이선스 서버 (voscms.com)

### 4.1 DB 스키마

```sql
-- 설치된 VosCMS 인스턴스 (코어 라이선스)
CREATE TABLE vcs_licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(20) UNIQUE NOT NULL,       -- RZX-XXXX-XXXX-XXXX
    domain VARCHAR(255) NOT NULL,                   -- customer-shop.com
    domain_hash VARCHAR(64) NOT NULL,               -- SHA-256(domain + secret)
    plan ENUM('free','standard','professional','enterprise') DEFAULT 'free',
    status ENUM('active','suspended','revoked') DEFAULT 'active',
    voscms_version VARCHAR(20),                     -- 고객 사이트 VosCMS 버전
    php_version VARCHAR(20),
    server_ip VARCHAR(45),
    last_verified_at DATETIME,
    registered_at DATETIME DEFAULT NOW(),
    expires_at DATETIME DEFAULT NULL,               -- NULL = 영구 (free 플랜)
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
);

-- 플러그인 구매 기록 (도메인별 허용 플러그인)
CREATE TABLE vcs_license_plugins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_id INT NOT NULL,
    plugin_id VARCHAR(50) NOT NULL,                 -- 'vos-salon', 'vos-pos' 등
    order_id VARCHAR(64) DEFAULT NULL,              -- 마켓플레이스 주문번호
    status ENUM('active','expired','revoked') DEFAULT 'active',
    purchased_at DATETIME DEFAULT NOW(),
    expires_at DATETIME DEFAULT NULL,               -- NULL = 영구
    UNIQUE KEY uk_license_plugin (license_id, plugin_id),
    FOREIGN KEY (license_id) REFERENCES vcs_licenses(id)
);

-- 인증/활동 로그
CREATE TABLE vcs_license_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_id INT,
    action VARCHAR(50),           -- register, verify, plugin_purchase, suspend, revoke
    domain VARCHAR(255),
    ip_address VARCHAR(45),
    details JSON,
    created_at DATETIME DEFAULT NOW()
);

-- 장비 주문 (마켓플레이스 하드웨어)
CREATE TABLE vcs_hardware_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_id INT DEFAULT NULL,
    order_number VARCHAR(64) NOT NULL,
    customer_name VARCHAR(200),
    customer_email VARCHAR(200),
    customer_phone VARCHAR(50),
    shipping_address TEXT,
    items JSON,                    -- [{name, qty, price}]
    subtotal DECIMAL(10,2),
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'JPY',
    status ENUM('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(100),
    notes TEXT,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
);
```

### 4.2 API 엔드포인트 (voscms.com)

| 메서드 | 경로 | 설명 | 호출 시점 |
|--------|------|------|----------|
| POST | `/api/license/register` | 새 설치 등록 | install.php Step 5 |
| POST | `/api/license/verify` | 주기적 인증 확인 | 관리자 접속 시 (24h 주기) |
| POST | `/api/license/register-plugin` | 플러그인 구매 등록 | 마켓플레이스 구매 완료 시 |
| GET | `/api/license/check` | 라이선스 상태 조회 | 관리자 설정 페이지 |
| POST | `/api/license/deactivate` | 라이선스 해제 | 도메인 이전 시 (본사만) |

**등록 API (install.php → voscms.com):**
```json
// Request
POST /api/license/register
{
    "key": "RZX-K7M2-P9X4-N3H8",
    "domain": "customer-shop.com",
    "version": "2.1.0",
    "php_version": "8.3",
    "server_ip": "123.45.67.89"
}

// Response (성공)
{
    "success": true,
    "plan": "free",
    "registered_at": "2026-04-10T12:00:00+09:00",
    "message": "License registered successfully"
}

// Response (도메인 중복)
{
    "success": false,
    "error": "domain_exists",
    "message": "This domain is already registered with another license key"
}
```

**검증 API (LicenseClient → voscms.com):**
```json
// Request
POST /api/license/verify
{
    "key": "RZX-K7M2-P9X4-N3H8",
    "domain": "customer-shop.com",
    "version": "2.1.0",
    "installed_plugins": ["vos-salon", "vos-pos"]
}

// Response
{
    "valid": true,
    "plan": "free",
    "allowed_plugins": ["vos-salon"],
    "unauthorized_plugins": ["vos-pos"],
    "latest_version": "2.2.0",
    "update_available": true,
    "cache_ttl": 604800
}
```

**플러그인 구매 등록 API:**
```json
// Request
POST /api/license/register-plugin
{
    "key": "RZX-K7M2-P9X4-N3H8",
    "domain": "customer-shop.com",
    "plugin_id": "vos-pos",
    "order_id": "MKT-20260410-A1B2"
}

// Response
{
    "success": true,
    "allowed_plugins": ["vos-salon", "vos-pos"]
}
```

---

## 5. 고객 사이트 컴포넌트

### 5.1 install.php 수정 (기존 Step 4 → Step 5 추가)

현재 install.php 스텝:
```
Step 1: 환경 확인
Step 2: DB 연결 테스트
Step 3: 테이블 생성
Step 4: 관리자 계정 + 사이트 설정 → .env 생성 → 설치 완료
```

변경 후:
```
Step 1: 환경 확인 (+ ionCube Loader, + localhost 차단)
Step 2: DB 연결 테스트
Step 3: 테이블 생성
Step 4: 관리자 계정 + 사이트 설정
Step 5: 라이선스 등록 ★ NEW
         - 키 자동 생성
         - 도메인 자동 감지
         - voscms.com에 전송
         - .env에 LICENSE_KEY 추가
         - 설치 완료
```

**.env에 추가되는 항목:**
```
LICENSE_KEY=RZX-K7M2-P9X4-N3H8
LICENSE_DOMAIN=customer-shop.com
LICENSE_REGISTERED_AT=2026-04-10T12:00:00
LICENSE_SERVER=https://voscms.com/api
```

### 5.2 LicenseClient.php (새로 생성)

**위치:** `rzxlib/Core/License/LicenseClient.php` (ionCube 암호화 대상)

**역할:**
- 관리자 접속 시 24시간 주기로 voscms.com에 인증 확인
- 응답을 로컬 캐시에 저장 (7일 유효)
- 네트워크 장애 시 캐시로 동작
- 허용되지 않은 플러그인 감지 → 경고

**캐시 구조:** `storage/.license_cache` (JSON, 암호화)
```json
{
    "license_key": "RZX-K7M2-P9X4-N3H8",
    "domain_hash": "sha256(domain+secret)",
    "plan": "free",
    "allowed_plugins": ["vos-salon"],
    "verified_at": "2026-04-10T12:00:00",
    "cache_expires_at": "2026-04-17T12:00:00",
    "latest_version": "2.2.0",
    "checksum": "sha256(위 데이터 + secret)"
}
```

**도메인 해시로 복사 방지:**
- 캐시 파일에 도메인 해시 포함
- 다른 도메인에서 캐시 파일을 복사해와도 해시 불일치 → 무효
- 해시 비밀키는 LicenseClient.php 내부에 하드코딩 (ionCube로 보호)

**동작 흐름:**
```php
class LicenseClient {
    public function check(): LicenseStatus {
        // 1. 캐시 확인
        $cache = $this->loadCache();
        if ($cache && !$cache->isExpired() && $cache->domainMatches()) {
            return $cache->toStatus();
        }

        // 2. 서버 검증
        $response = $this->callApi('/api/license/verify', [...]);
        if ($response->success) {
            $this->saveCache($response);
            return LicenseStatus::active($response);
        }

        // 3. 네트워크 실패 → 캐시 기간 내면 OK
        if ($cache && !$cache->isCacheExpired()) {
            return $cache->toStatus(); // 경고 포함
        }

        // 4. 캐시도 만료 → 미인증 상태
        return LicenseStatus::unverified();
    }
}
```

### 5.3 라이선스 체크 통합 (index.php)

관리자 접속 시 LicenseClient를 호출하여 상태 확인:

```php
// index.php 관리자 라우트 진입 시
if (str_starts_with($path, $config['admin_path'])) {
    // ... 인증 체크 후 ...
    
    $licenseClient = new LicenseClient();
    $licenseStatus = $licenseClient->check();
    
    // 뷰에서 사용할 수 있도록 전달
    $licenseInfo = [
        'plan' => $licenseStatus->plan,
        'allowed_plugins' => $licenseStatus->allowedPlugins,
        'warning' => $licenseStatus->warning,      // 경고 메시지 (있으면)
        'days_left' => $licenseStatus->daysLeft,
        'update_available' => $licenseStatus->updateAvailable,
    ];
}
```

### 5.4 경고/제한 단계

**관리자 대시보드에 표시되는 상태:**

| 상태 | 표시 | 기능 제한 |
|------|------|----------|
| 정상 (active) | 없음 | 없음 |
| 미인증 플러그인 있음 | 노란 배너 "1개 플러그인 미인증" | 해당 플러그인만 경고 |
| 라이선스 서버 연결 불가 | 회색 배너 "인증 서버 연결 불가 (캐시 N일 남음)" | 없음 (캐시 기간 내) |
| 캐시 만료 + 서버 불가 | 빨간 배너 "라이선스 인증 필요" | 플러그인 설치/업데이트 차단 |
| 라이선스 revoked | 빨간 배너 "라이선스가 취소되었습니다" | 관리자 설정 변경 불가 |

**중요 원칙: 고객의 프론트엔드 서비스(예약, 홈페이지 등)는 절대 중단하지 않음.**
라이선스 문제는 관리자 패널에서만 경고/제한.

---

## 6. 마켓플레이스 플러그인 (vos-marketplace)

### 6.1 현재 구현 상태

Phase 1A 완료 — 40개 파일 생성:

| 카테고리 | 상태 | 주요 파일 |
|---------|------|----------|
| plugin.json | ✅ 완료 | 라우트, 메뉴, 훅 정의 |
| DB 마이그레이션 | ✅ 완료 | 8개 테이블 (mp_items, mp_orders, mp_licenses 등) |
| 모델 8개 | ✅ 완료 | Active Record 패턴 |
| 서비스 4개 | ✅ 완료 | MarketplaceService, LicenseService, CatalogClient, InstallerService |
| Admin 뷰 | ✅ 완료 | browse, item-detail, purchases, licenses, settings |
| API 6개 | ✅ 완료 | catalog, item, purchase, license-validate, download, webhook |
| 다국어 | ✅ 완료 | ko, en, ja (100+ 키) |
| 훅 2개 | ✅ 완료 | dashboard-widget, update-check |

### 6.2 마켓플레이스 아이템 타입

현재:
```
plugin | theme | widget | skin
```

확장 (장비 추가):
```
plugin | theme | widget | skin | hardware
```

**hardware 타입 특성:**
- 디지털 다운로드 없음 (다운로드 URL 불필요)
- 물리적 배송 필요 (배송 주소, 배송비, 추적번호)
- 라이선스 키 발급 없음
- 주문 후 본사에서 수동 처리 (초기) → 자동화 (향후)

**마켓플레이스 DB 수정 필요:**
```sql
-- rzx_mp_items.type에 hardware 추가
ALTER TABLE rzx_mp_items 
    MODIFY type ENUM('plugin','theme','widget','skin','hardware') NOT NULL;

-- rzx_mp_orders에 배송 정보 추가
ALTER TABLE rzx_mp_orders
    ADD shipping_name VARCHAR(200) DEFAULT NULL AFTER metadata,
    ADD shipping_phone VARCHAR(50) DEFAULT NULL AFTER shipping_name,
    ADD shipping_address TEXT DEFAULT NULL AFTER shipping_phone,
    ADD shipping_fee DECIMAL(10,2) DEFAULT 0 AFTER shipping_address,
    ADD tracking_number VARCHAR(100) DEFAULT NULL AFTER shipping_fee;
```

### 6.3 마켓플레이스 ↔ 라이선스 서버 연동

플러그인 구매 완료 시:
```
1. 마켓플레이스에서 결제 완료 (Stripe)
2. rzx_mp_orders 상태 → paid
3. rzx_mp_licenses에 마켓플레이스 라이선스 생성
4. voscms.com/api/license/register-plugin 호출 ★
     { license_key (VosCMS 키), domain, plugin_id, order_id }
5. 라이선스 서버가 해당 도메인의 allowed_plugins에 추가
6. 다음 verify 호출 시 → allowed_plugins에 새 플러그인 포함
7. 플러그인 다운로드 + 자동 설치
```

---

## 7. 코드 보호 (ionCube)

### 7.1 암호화 대상 요약

```
암호화 O:
  ├── install.php                          ← 키 생성 + API 통신
  ├── rzxlib/Core/License/LicenseClient.php ← 라이선스 검증
  ├── rzxlib/Core/Auth/                     ← 인증 시스템
  ├── rzxlib/Core/Database/                 ← DB 레이어
  ├── rzxlib/Reservation/                   ← 예약 엔진
  └── plugins/vos-marketplace/src/          ← 구매/라이선스 로직

암호화 X:
  ├── resources/views/                      ← 뷰 커스터마이징
  ├── resources/lang/                       ← 번역 수정
  ├── skins/                                ← 스킨 개발
  ├── widgets/                              ← 위젯 개발
  ├── plugins/*/views/                      ← 플러그인 뷰
  └── assets/                               ← 프론트엔드
```

### 7.2 서버 요구사항

```
PHP 8.1+
ionCube Loader (PHP 버전에 맞는)
MySQL 8.0+ 또는 MariaDB 10.4+
HTTPS (SSL 인증서 필수 — localhost 차단과 연계)
```

---

## 8. 도메인 변경 정책

| 항목 | 정책 |
|------|------|
| 변경 방법 | **본사 관리자만 가능** (셀프 변경 API 없음) |
| 변경 횟수 | 연간 2회 무료 |
| 변경 시 | 구 도메인 즉시 revoke → 다음 verify에서 차단 |
| 캐시 무효화 | 오프라인 캐시에 도메인 해시 포함 → 타 도메인에서 무효 |
| 추가 변경 | 수수료 또는 별도 협의 |

---

## 9. 구현 우선순위

### Phase 1: 라이선스 기반 (최우선)

```
1-1. install.php 수정
     - localhost 차단 추가
     - Step 5 라이선스 등록 추가
     - 키 자동 생성 + voscms.com 전송
     - .env에 LICENSE_KEY 기록

1-2. LicenseClient.php 생성
     - rzxlib/Core/License/LicenseClient.php
     - verify API 호출 + 캐시 관리
     - 도메인 해시 검증

1-3. index.php 연동
     - 관리자 접속 시 LicenseClient::check() 호출
     - 경고 배너 표시 로직

1-4. 라이선스 서버 API (voscms.com)
     - /api/license/register
     - /api/license/verify
     - /api/license/register-plugin
     - DB 테이블 생성
```

### Phase 2: 마켓플레이스 보강

```
2-1. hardware 타입 추가
     - DB 스키마 수정 (type ENUM + 배송 필드)
     - 장비 카드 UI (배송 정보 입력 폼)
     - 주문 후 본사 알림

2-2. 마켓플레이스 ↔ 라이선스 서버 연동
     - 구매 완료 시 register-plugin API 호출
     - verify 응답의 allowed_plugins 체크

2-3. 미인증 플러그인 경고
     - 관리자 대시보드 배너
     - 플러그인 목록에서 "미인증" 표시
```

### Phase 3: 배포 준비

```
3-1. ionCube 암호화 테스트
3-2. 빌드 스크립트 작성
3-3. 외부 서버 클린 설치 테스트
3-4. 데모 데이터 SQL 정비
```

### Phase 4: 개발자 마켓플레이스 (서드파티 판매자 시스템)

아래 섹션 12에 상세 기술.

---

## 10. 현재 파일 위치 참고

| 파일 | 경로 | 상태 |
|------|------|------|
| 설치 마법사 | `/var/www/voscms/install.php` | 기존 (수정 예정) |
| 라이선스 클라이언트 | `rzxlib/Core/License/LicenseClient.php` | 미생성 |
| 마켓플레이스 플러그인 | `plugins/vos-marketplace/` | ✅ 40파일 완료 |
| 플러그인 매니저 | `rzxlib/Core/Plugin/PluginManager.php` | 기존 |
| 관리자 인증 | `rzxlib/Core/Auth/AdminAuth.php` | 기존 (remember me 추가 완료) |
| 프론트 인증 | `rzxlib/Core/Auth/Auth.php` | 기존 (secure 쿠키 수정 완료) |
| 배포 계획서 | `docs/DEPLOYMENT_PLAN.md` | 기존 참고 문서 |
| 플러그인 전략 | `docs/VOSCMS_PLUGIN_STRATEGY.md` | 기존 참고 문서 |
| 마켓 계획서 | `docs/MARKETPLACE_PLUGIN_PLAN.md` | 기존 참고 문서 |
| **이 문서** | `docs/LICENSE_MARKETPLACE_SYSTEM.md` | 통합 계획서 |

---

## 12. 개발자 마켓플레이스 (서드파티 판매자 시스템)

> 외부 개발자가 플러그인/위젯을 제작 → 마켓플레이스에 등록 (유료/무료)
> → 심사/승인 → 공개 → 사용자 구매 → 개발자에게 수익 정산

### 12.1 전체 구조

```
┌──────────────────┐         ┌──────────────────┐         ┌──────────────────┐
│  개발자            │         │  VosCMS 마켓      │         │  구매자 (고객)     │
│                   │         │  (voscms.com)     │         │                   │
│ 1. 개발자 등록     │───►     │                   │    ◄────│ 4. 마켓 탐색       │
│ 2. 플러그인 업로드  │───►     │ 3. 심사/승인       │    ◄────│ 5. 구매/설치       │
│ 6. 매출 확인       │◄───     │    정산 관리       │───►     │                   │
│ 7. 출금 요청       │───►     │    수수료 계산     │         │                   │
│ 8. 입금 수령       │◄───     │    지급 처리       │         │                   │
└──────────────────┘         └──────────────────┘         └──────────────────┘
```

### 12.2 개발자 계정

**개발자 등록 방법:**
- 개발자 포털에서 누구나 가입 가능 (이메일 + 비밀번호 + 이름)
- 가입 즉시 유료/무료 아이템 업로드 가능
- 개발자 자격 심사 없음 — **아이템 단위 심사**로 품질 통제

**핵심 원칙:** 누구나 개발자가 될 수 있으며, 편하게 업로드할 수 있게 하는 것이 중요.
어차피 업로드된 아이템은 본사가 설치 테스트 후 심사하므로 품질은 보장됨.

### 12.3 아이템 등록 프로세스

```
[개발자]
1. 개발자 대시보드 로그인
2. "새 아이템 등록" 클릭
3. 기본 정보 입력:
   - 아이템 유형: 플러그인 / 위젯 / 테마 / 스킨
   - 이름 (다국어 지원: ko, en, ja)
   - 설명 (마크다운)
   - 카테고리 선택
   - 태그 (최대 5개)
4. 파일 업로드:
   - ZIP 패키지 (plugin.json 또는 widget.json 포함 필수)
   - 최대 50MB
   - 자동 검증: 매니페스트 파싱, 악성코드 스캔, 구조 확인
5. 미디어:
   - 아이콘 (512x512 PNG)
   - 배너 이미지 (1200x600)
   - 스크린샷 (최대 5장)
6. 가격 설정:
   - 무료 / 유료
   - 유료: 가격 + 통화 (USD, JPY, KRW)
   - 세일 가격 + 세일 기간 (선택)
7. 버전 정보:
   - 버전 번호 (SemVer)
   - 변경 로그
   - 최소 VosCMS 버전 / PHP 버전
   - 의존 플러그인
8. 제출 → 심사 큐 진입

[본사 관리자]
9. 심사 큐에서 아이템 확인:
   - 코드 리뷰 (보안, 품질)
   - 매니페스트 검증
   - 스크린샷/설명 적절성
   - 라이선스 호환성
10. 승인 → 마켓플레이스에 공개
    or 반려 → 사유와 함께 개발자에게 통보
```

### 12.4 수익 모델 (수수료 정책)

| 항목 | 비율 |
|------|------|
| **개발자 몫** | 70% |
| **VosCMS 수수료** | 30% |
| **최소 정산 금액** | $50 (또는 ¥5,000) |
| **정산 주기** | 월 1회 (매월 15일) |
| **정산 방법** | Stripe Connect / 은행 이체 |

**예시:**
```
플러그인 가격: $100
  → 개발자: $70
  → VosCMS: $30
```

### 12.5 정산 시스템

**방식 A: Stripe Connect (추천)**
```
구매자 결제 → Stripe → 자동 분배 (70% 개발자 / 30% VosCMS)
                        → 개발자 Stripe 계정으로 직접 입금
```
- 장점: 자동화, 글로벌, 세금 처리 간편
- 단점: Stripe Connect 수수료 추가 (~0.25%)

**방식 B: 수동 정산 (초기)**
```
구매자 결제 → VosCMS 계정으로 전액 수금
              → 월말 정산 → 개발자별 매출 집계
              → 수수료 차감 후 은행 이체
```
- 장점: 즉시 시작 가능, Stripe Connect 설정 불필요
- 단점: 수동 작업, 확장 어려움

**추천: 초기에는 방식 B로 시작 → 개발자 수 증가 시 방식 A로 전환**

### 12.6 DB 스키마 (voscms.com 라이선스 서버)

```sql
-- 개발자 계정
CREATE TABLE vcs_developers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,               -- voscms.com 회원 연동
    name VARCHAR(200) NOT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    company VARCHAR(200) DEFAULT NULL,
    website VARCHAR(500) DEFAULT NULL,
    github VARCHAR(200) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    avatar VARCHAR(500) DEFAULT NULL,
    type ENUM('general','verified','partner') DEFAULT 'general',
    status ENUM('pending','active','suspended','banned') DEFAULT 'pending',
    verified_at DATETIME DEFAULT NULL,
    payout_method ENUM('stripe','bank','paypal') DEFAULT NULL,
    payout_details JSON DEFAULT NULL,       -- Stripe account ID 또는 은행 정보 (암호화)
    total_earnings DECIMAL(12,2) DEFAULT 0,
    total_paid DECIMAL(12,2) DEFAULT 0,
    pending_balance DECIMAL(12,2) DEFAULT 0,
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW() ON UPDATE NOW()
);

-- 아이템 심사 큐
CREATE TABLE vcs_review_queue (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT DEFAULT NULL,               -- rzx_mp_items.id (승인 후 생성)
    developer_id INT NOT NULL,
    item_type ENUM('plugin','widget','theme','skin') NOT NULL,
    name JSON NOT NULL,
    description JSON DEFAULT NULL,
    version VARCHAR(20) NOT NULL,
    package_url VARCHAR(500) NOT NULL,       -- 업로드된 ZIP 경로
    package_hash VARCHAR(64) NOT NULL,       -- SHA-256
    package_size INT UNSIGNED DEFAULT 0,
    price DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    screenshots JSON DEFAULT NULL,
    icon VARCHAR(500) DEFAULT NULL,
    banner VARCHAR(500) DEFAULT NULL,
    category_id INT DEFAULT NULL,
    tags JSON DEFAULT NULL,
    min_voscms VARCHAR(20) DEFAULT NULL,
    min_php VARCHAR(20) DEFAULT NULL,
    requires_plugins JSON DEFAULT NULL,
    status ENUM('pending','reviewing','approved','rejected','revision') DEFAULT 'pending',
    reviewer_id INT DEFAULT NULL,           -- 심사 담당자
    reviewer_notes TEXT DEFAULT NULL,        -- 심사 코멘트
    rejection_reason TEXT DEFAULT NULL,
    submitted_at DATETIME DEFAULT NOW(),
    reviewed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NOW()
);

-- 개발자 매출/정산
CREATE TABLE vcs_developer_earnings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    developer_id INT NOT NULL,
    order_id INT DEFAULT NULL,              -- 마켓플레이스 주문 ID
    item_id INT NOT NULL,
    buyer_domain VARCHAR(255) DEFAULT NULL,
    gross_amount DECIMAL(10,2) NOT NULL,     -- 판매 금액
    commission_rate DECIMAL(4,2) DEFAULT 30, -- 수수료율 (%)
    commission DECIMAL(10,2) NOT NULL,       -- 수수료 금액
    net_amount DECIMAL(10,2) NOT NULL,       -- 개발자 수익
    currency VARCHAR(3) DEFAULT 'USD',
    status ENUM('pending','settled','paid') DEFAULT 'pending',
    settled_at DATETIME DEFAULT NULL,        -- 정산 확정일
    paid_at DATETIME DEFAULT NULL,           -- 지급일
    created_at DATETIME DEFAULT NOW()
);

-- 지급 내역
CREATE TABLE vcs_developer_payouts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    developer_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    method ENUM('stripe','bank','paypal') NOT NULL,
    reference VARCHAR(200) DEFAULT NULL,     -- 송금 참조번호
    status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
    period_start DATE NOT NULL,              -- 정산 기간 시작
    period_end DATE NOT NULL,                -- 정산 기간 끝
    items_count INT DEFAULT 0,               -- 건수
    notes TEXT DEFAULT NULL,
    processed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT NOW()
);
```

### 12.7 개발자 대시보드 (voscms.com)

```
개발자 로그인 → /developer/dashboard

├── 대시보드
│   ├── 총 매출 / 이번 달 매출 / 대기 잔액
│   ├── 최근 판매 내역
│   └── 아이템별 다운로드/매출 차트
│
├── 내 아이템
│   ├── 등록된 아이템 목록 (상태: 공개/심사중/반려)
│   ├── 새 아이템 등록
│   └── 기존 아이템 수정 / 새 버전 업로드
│
├── 매출 관리
│   ├── 매출 내역 (일/월 단위)
│   ├── 정산 내역
│   └── 출금 요청
│
├── 설정
│   ├── 프로필 편집
│   ├── 정산 정보 (Stripe / 은행 계좌)
│   └── API 키 관리 (향후)
│
└── 고객 지원
    ├── 리뷰/평가 관리
    └── 문의 내역
```

### 12.8 본사 관리자 기능 (voscms.com/admin)

```
관리자 → /admin/marketplace

├── 심사 큐
│   ├── 대기중 아이템 목록
│   ├── 코드/패키지 검토
│   └── 승인 / 반려 (사유 작성)
│
├── 개발자 관리
│   ├── 개발자 목록 (상태, 매출, 아이템 수)
│   ├── 인증 승인 / 정지 / 차단
│   └── 파트너 지정
│
├── 정산 관리
│   ├── 월별 정산 집계
│   ├── 개발자별 미지급 잔액
│   ├── 일괄 지급 처리
│   └── 지급 이력
│
├── 아이템 관리
│   ├── 전체 아이템 목록 (검색, 필터)
│   ├── 추천/Featured 관리
│   ├── 카테고리 관리
│   └── 신고/불량 아이템 처리
│
└── 통계
    ├── 매출 대시보드 (수수료 수익)
    ├── 인기 아이템 랭킹
    ├── 개발자별 매출 순위
    └── 월별 성장 추이
```

### 12.9 패키지 검증 자동화

아이템 제출 시 자동 검증 항목:

```
1. 구조 검증
   - plugin.json 또는 widget.json 존재 여부
   - 필수 필드 확인 (id, name, version)
   - 파일 크기 제한 (50MB)

2. 보안 스캔
   - eval(), exec(), system(), passthru() 등 위험 함수 검출
   - 외부 URL 하드코딩 검출 (데이터 유출 방지)
   - base64_decode + eval 패턴 검출
   - .htaccess, .env 파일 포함 여부

3. 호환성 체크
   - PHP 버전 호환성 (선언된 최소 버전 확인)
   - VosCMS 버전 호환성
   - 의존 플러그인 존재 여부

4. 품질 체크
   - PHP 문법 오류 (php -l)
   - 파일 인코딩 (UTF-8 확인)
   - 라이선스 파일 포함 여부 (LICENSE, LICENSE.md)
```

### 12.10 프론트엔드 마켓플레이스 (voscms.com)

voscms.com에 공개 마켓플레이스 페이지:

```
https://voscms.com/marketplace              ← 카탈로그 메인
https://voscms.com/marketplace/plugins      ← 플러그인 목록
https://voscms.com/marketplace/widgets      ← 위젯 목록
https://voscms.com/marketplace/themes       ← 테마 목록
https://voscms.com/marketplace/item/{slug}  ← 아이템 상세
https://voscms.com/developer                ← 개발자 대시보드
https://voscms.com/developer/submit         ← 아이템 등록
```

각 VosCMS 사이트의 관리자 마켓플레이스(`/admin/marketplace`)는 이 카탈로그 API를 호출하여 아이템을 표시.

### 12.11 구현 순서

```
Phase 4-1: 개발자 등록 + 아이템 제출 (MVP)
  - vcs_developers 테이블
  - vcs_review_queue 테이블
  - 개발자 가입/프로필 페이지
  - ZIP 업로드 + 자동 검증
  - 관리자 심사 큐 UI

Phase 4-2: 유료 판매 + 정산
  - vcs_developer_earnings 테이블
  - vcs_developer_payouts 테이블
  - 구매 시 매출 자동 기록
  - 수수료 계산 (70/30)
  - 관리자 정산 대시보드
  - 수동 지급 처리

Phase 4-3: Stripe Connect 자동화
  - Stripe Connect 연동
  - 자동 분배 결제
  - 개발자 Stripe 계정 연결
  - 자동 정산/지급

Phase 4-4: 프론트엔드 마켓플레이스 (voscms.com)
  - 공개 카탈로그 페이지
  - 검색/필터/랭킹
  - 아이템 상세 (스크린샷, 리뷰, 설명)
  - SEO 최적화
```

---

## 11. 세션에서 수정 완료한 사항

| 항목 | 변경 내용 |
|------|----------|
| 세션 수명 | `gc_maxlifetime` 24분 → 7일, `cookie_lifetime` 0 → 7일 |
| .env SESSION_LIFETIME | 120분 → 10,080분 (7일) |
| 관리자 Remember Me | AdminAuth에 30일 remember 토큰 추가 |
| 관리자 로그인 폼 | "로그인 상태 유지" 체크박스 추가 |
| 프론트 Remember Me | secure 쿠키 플래그 리버스 프록시 대응 |
| 마켓플레이스 browse.php | __mp() 함수 호출 순서 수정 (500 에러 해결) |
| 마켓플레이스 _head.php | 존재하지 않는 lang.php require 제거 |
| ionCube Loader | vos.21ces.com에 v15.5 설치 (PHP 8.3) |
| 라이선스 서버 DB | vcs_licenses, vcs_license_plugins, vcs_license_logs, vcs_hardware_orders 생성 |
| 라이선스 API | api/license/ (register, verify, register-plugin, check, stats, updates) |
| LicenseClient.php | rzxlib/Core/License/ (LicenseClient + LicenseStatus) |
| install.php | localhost 차단 + Step 5 라이선스 키 생성/등록 |
| index.php | api/license 라우트 + api/developer 라우트 + /developer 페이지 + /marketplace 페이지 + 관리자 라이선스 체크 연동 + 홈페이지 위젯 페이지 렌더링 |
| 대시보드 | 라이선스 정보 카드 + Total Installs 통계 카드 추가 |
| admin-topbar.php | 라이선스 경고 배너 연동 |
| .env | LICENSE_KEY, LICENSE_DOMAIN, LICENSE_SERVER 추가 |
| 개발자 DB | vcs_developers, vcs_review_queue (is_update, previous_version), vcs_developer_earnings, vcs_developer_payouts 생성 |
| 개발자 API | api/developer/ (register, login, submit, update-version, my-items, earnings) |
| 개발자 포털 | /developer/ (login, dashboard, submit, my-items, earnings, logout) — 다국어 13개국어 + 다크모드 + 언어변환기 |
| 공개 마켓플레이스 | /marketplace (index, item) — 다국어 + 다크모드 + 언어변환기 |
| 심사 큐 | /theadmin/review-queue — 승인/반려(사유 작성), UPDATE 배지, 신규/업데이트 구분 처리 |
| 버전 업데이트 | 개발자 my-items에서 새 버전 업로드 → 심사 → 승인 시 mp_item_versions + latest_version 갱신 |
| 플러그인 업데이트 알림 | 관리자 플러그인 목록에서 "v1.1.0 available" 배지 + Update 버튼 |
| 다국어 marketplace.php | 13개 언어 × 121키 (ko, en, ja, de, es, fr, id, mn, ru, tr, vi, zh_CN, zh_TW) |
| 개발자 정책 변경 | 누구나 가입 즉시 유료/무료 업로드 가능 (개발자 자격 심사 없음, 아이템 단위 심사) |
| 언어변환기 | pointer-events-none 추가 (오버레이 클릭 차단 방지), 중복 스크립트 방지 |
| WidgetLoader | ob_start/ob_get_clean 추가 (echo 출력 위젯 렌더링 지원) |
| features 위젯 | Section Subtitle 줄바꿈(\\n → br) 적용 |
| hero-cta2 위젯 | Rhymix 스타일 타이핑 애니메이션 + CTA 버튼 (신규 제작) |
| 홈페이지 라우트 | / 접속 시 home_page 설정의 위젯 페이지 렌더링 (home.php 하드코딩 제거) |
| 홈 (/home) 페이지 | 시스템 위젯 페이지 생성 (삭제 불가), hero-cta2 + stats + features 기본 배치 |
| salon 위젯 동기화 | 21개 위젯 + vos-shop 플러그인 salon.rezlyx.com에서 복사 |
| version.json | 2.0.0 → 2.1.0, codename: Marketplace |
