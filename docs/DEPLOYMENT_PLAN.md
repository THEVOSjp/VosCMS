# RezlyX 배포 계획서

## 1. 개요

RezlyX를 상용 제품으로 배포하기 위한 라이선스 인증, 코드 보호, 빌드/패키징, 데모 데이터 전략을 정리한 문서.

## 2. 배포 구조

```
개발 서버 (E:\xampp\htdocs\project\rezlyx\)
  │
  ├─ 빌드 스크립트 실행
  │
  ▼
배포 디렉토리 (E:\Project\Solution\reservation\dist\)
  ├── rezlyx/                    ← 배포용 소스 (평문)
  │   ├── rzxlib/                ← 암호화 대상 (코어)
  │   ├── resources/views/       ← 평문 (커스터마이징 허용)
  │   ├── resources/lang/        ← 평문 (번역 수정 허용)
  │   ├── skins/                 ← 평문 (스킨 개발 허용)
  │   ├── widgets/               ← 평문 (위젯 개발 허용)
  │   ├── database/
  │   │   ├── migrations/        ← DB 마이그레이션
  │   │   └── demo-data.sql      ← 데모 데이터
  │   ├── installer.php          ← 웹 설치 마법사
  │   └── .env.example           ← 환경 변수 템플릿
  │
  └── rezlyx-encrypted/          ← ionCube 암호화 완료본
      └── (위와 동일, rzxlib만 암호화)
```

## 3. 코드 보호 전략

### 3.1 암호화 대상 (ionCube)

코어 비즈니스 로직만 암호화하고, 커스터마이징이 필요한 파일은 평문 유지.

| 디렉토리 | 암호화 | 이유 |
|----------|:------:|------|
| `rzxlib/Core/` | ✅ | 인증, 라이선스, 핵심 로직 |
| `rzxlib/Reservation/` | ✅ | 예약 엔진 |
| `resources/views/` | ❌ | 뷰 커스터마이징 허용 |
| `resources/lang/` | ❌ | 번역 수정 허용 |
| `skins/` | ❌ | 스킨 개발 허용 |
| `widgets/` | ❌ | 위젯 개발 허용 |
| `assets/` | ❌ | 프론트엔드 리소스 |

### 3.2 암호화 도구

- **ionCube Encoder** (권장): 대부분의 호스팅에서 ionCube Loader 지원
- **Zend Guard**: 대안
- 서버 요구사항: ionCube Loader 확장 모듈 설치

## 4. 라이선스 인증 시스템

### 4.1 구성도

```
┌──────────────────┐              ┌──────────────────┐
│   고객 사이트      │    HTTPS     │  RezlyX 본사 서버  │
│   (배포된 RezlyX)  │  ◄──────►   │  (rezlyx.com)     │
│                   │    API       │                   │
│  rzxlib/Core/     │              │  라이선스 관리 API   │
│  License/         │              │  관리자 대시보드     │
│  LicenseClient.php│              │  고객 포털          │
└──────────────────┘              └──────────────────┘
```

### 4.2 본사 라이선스 서버 (rezlyx.com)

**DB 스키마:**

```sql
CREATE TABLE licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(50) UNIQUE NOT NULL,    -- RZX-XXXX-XXXX-XXXX
    customer_name VARCHAR(200),
    customer_email VARCHAR(200),
    domain VARCHAR(255),                         -- 등록된 도메인
    plan ENUM('trial','standard','professional','enterprise'),
    status ENUM('active','suspended','expired','revoked'),
    activated_at DATETIME,
    expires_at DATETIME,
    domain_changes INT DEFAULT 0,                -- 도메인 변경 횟수
    max_domain_changes INT DEFAULT 2,            -- 연간 최대 변경 횟수
    last_verified_at DATETIME,                   -- 마지막 인증 시각
    version VARCHAR(20),                         -- 고객 사이트 버전
    created_at DATETIME DEFAULT NOW(),
    updated_at DATETIME DEFAULT NOW()
);

CREATE TABLE license_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_id INT,
    action VARCHAR(50),          -- activate, verify, suspend, transfer, expire
    domain VARCHAR(255),
    ip_address VARCHAR(45),
    details TEXT,
    created_at DATETIME DEFAULT NOW()
);
```

**API 엔드포인트:**

| 메서드 | 경로 | 설명 |
|--------|------|------|
| POST | `/api/license/activate` | 라이선스 활성화 (설치 시) |
| POST | `/api/license/verify` | 주기적 인증 확인 |
| POST | `/api/license/deactivate` | 라이선스 해제 |

**활성화 요청/응답:**
```json
// Request
{
    "key": "RZX-XXXX-XXXX-XXXX",
    "domain": "customer-shop.com",
    "version": "1.16.0",
    "php_version": "8.2",
    "server_ip": "123.456.789.0"
}

// Response (성공)
{
    "valid": true,
    "plan": "standard",
    "expires": "2027-03-23",
    "features": ["core", "pos", "kiosk"]
}

// Response (실패)
{
    "valid": false,
    "error": "domain_mismatch",
    "message": "이 라이선스 키는 다른 도메인에 등록되어 있습니다."
}
```

**인증 확인 요청/응답:**
```json
// Request
{
    "key": "RZX-XXXX-XXXX-XXXX",
    "domain": "customer-shop.com",
    "version": "1.16.0"
}

// Response
{
    "valid": true,
    "days_left": 320,
    "plan": "standard",
    "latest_version": "1.17.0",
    "update_available": true
}
```

### 4.3 고객 사이트 라이선스 클라이언트

**파일 위치:** `rzxlib/Core/License/LicenseClient.php` (암호화 대상)

**동작 흐름:**

```
설치 시:
1. installer.php에서 라이선스 키 입력
2. LicenseClient → 본사 API activate 호출
3. 성공 → DB에 라이선스 정보 저장 + installed_at 기록
4. 실패 → 체험판으로 설치 (30일)

일일 확인 (관리자 접속 시):
1. 마지막 확인 후 24시간 경과 체크
2. LicenseClient → 본사 API verify 호출
3. 성공 → 캐시 갱신 (7일 유효)
4. 실패 (네트워크) → 캐시 기간 내 정상 동작
5. 실패 (라이선스 무효) → 경고 단계 진입

도메인 검증:
- API 요청 시 현재 도메인과 등록 도메인 비교
- 불일치 → 즉시 거부
- 오프라인 캐시에도 도메인 해시 포함 → 타 도메인 사용 불가
```

### 4.4 도메인 변경 정책

| 항목 | 정책 |
|------|------|
| 변경 방법 | **본사 관리자 승인만 가능** (셀프 변경 불가) |
| 변경 횟수 | 연간 2회 무료 |
| 변경 시 | 구 도메인 즉시 무효화 + 신 도메인 활성화 |
| 추가 변경 | 수수료 또는 별도 협의 |

**악용 방지:**
- 셀프 도메인 변경 API 없음 (본사만 가능)
- 변경 즉시 구 도메인 revoke → 다음 API 확인 시 차단
- 오프라인 캐시에 도메인 해시 포함 → 다른 도메인에서 캐시 무효

## 5. 경고 및 제한 단계

### 5.1 체험판 (라이선스 미등록)

| 기간 | 동작 |
|------|------|
| 1~30일 | 전체 기능 정상 사용 |
| 31~60일 | 관리자 대시보드에 경고 배너 |
| 61~90일 | 관리자 + 프론트 하단에 배너 표시 |
| 91일~ | 관리자 페이지 기능 일부 제한 (설정 변경 불가) |

### 5.2 라이선스 만료

| 단계 | 동작 |
|------|------|
| 만료 30일 전 | 관리자에 갱신 안내 배너 |
| 만료 후 1~30일 | 관리자 경고 배너 (기능 정상) |
| 만료 후 31일~ | 프론트 배너 + 업데이트 차단 |

### 5.3 라이선스 무효 (revoked/domain mismatch)

- 관리자 페이지에 즉시 경고
- 7일 후 프론트 배너 표시
- 고객 예약 기능은 유지 (사업 중단 방지)

## 6. 라이선스 플랜

| 플랜 | 가격 | 기간 | 포함 기능 |
|------|------|------|----------|
| Trial | 무료 | 30일 | 전체 기능 |
| Standard | - | 1년 | 코어 + 예약 + 게시판 + 페이지 + 업데이트 |
| Professional | - | 1년 | Standard + POS + 키오스크 + 주문 시스템 |
| Enterprise | - | 1년 | Professional + 멀티 매장 + SaaS + 전용 지원 |

모듈별 활성화는 라이선스 응답의 `features` 배열로 제어:
```json
{
    "features": ["core", "pos", "kiosk", "order", "multi_store"]
}
```

## 7. 배포 빌드 프로세스

### 7.1 빌드 순서

```
1. 개발 서버에서 dist/ 디렉토리로 복사
   - .env, node_modules, storage/logs, .git 제외
   - vendor/ 포함 (composer install 불필요)

2. 데모 데이터 SQL 생성
   - 기본 메뉴 (Home, 스태프, 예약, 커뮤니티)
   - 데모 서비스 3~5개
   - 데모 스태프 2~3명
   - 기본 페이지 (이용약관, 개인정보처리방침, 환불규정)
   - 기본 위젯 배치 (홈페이지)

3. installer.php 포함
   - DB 연결 설정
   - 관리자 계정 생성
   - 라이선스 키 입력 (선택)
   - 마이그레이션 실행
   - 데모 데이터 import

4. ionCube 암호화
   - rzxlib/Core/ 전체
   - rzxlib/Reservation/ 전체
   - ionCube Loader 버전 호환 확인

5. 패키징
   - rezlyx-v1.16.0-standard.zip
   - rezlyx-v1.16.0-professional.zip
   - README.md + 설치 가이드 포함
```

### 7.2 빌드 스크립트 (향후 구현)

```bash
# build.sh
VERSION=$(cat version.json | jq -r '.version')
DIST_DIR="dist/rezlyx-${VERSION}"

# 1. 복사
rsync -av --exclude='.env' --exclude='.git' --exclude='storage/logs' ./rezlyx/ $DIST_DIR/

# 2. 데모 데이터
php scripts/generate-demo-sql.php > $DIST_DIR/database/demo-data.sql

# 3. ionCube 암호화
ioncube_encoder --target $DIST_DIR/rzxlib/ $DIST_DIR/rzxlib/

# 4. ZIP
cd dist && zip -r "rezlyx-${VERSION}.zip" "rezlyx-${VERSION}/"
```

## 8. 설치 마법사 (installer.php)

```
Step 1: 환경 확인
  - PHP 버전 (8.1+)
  - 필수 확장 (PDO, mbstring, json, openssl, curl)
  - ionCube Loader 설치 여부
  - 디렉토리 쓰기 권한

Step 2: DB 설정
  - 호스트, 데이터베이스명, 사용자, 비밀번호
  - 테이블 접두사

Step 3: 관리자 계정
  - 이메일, 비밀번호, 이름

Step 4: 라이선스 (선택)
  - 라이선스 키 입력 → 인증
  - "나중에 등록" → 30일 체험판

Step 5: 설치 완료
  - 마이그레이션 실행
  - 데모 데이터 import (선택)
  - .env 파일 생성
  - installer.php 삭제 안내
```

## 9. 업데이트 시스템

```
관리자 대시보드:
  "새 버전 v1.17.0이 있습니다" 알림

업데이트 방법:
  1. 자동 업데이트 (라이선스 유효 시)
     - 본사 서버에서 패치 다운로드
     - 파일 교체 + 마이그레이션 실행

  2. 수동 업데이트
     - ZIP 다운로드 → FTP 업로드
     - /update-api.php 실행
```

## 10. 구현 우선순위

| 순서 | 작업 | 난이도 |
|------|------|--------|
| 1 | 라이선스 클라이언트 (고객 사이트) | 중 |
| 2 | 라이선스 API (본사 서버) | 중 |
| 3 | 설치 마법사 (installer.php) | 중 |
| 4 | 데모 데이터 SQL 생성 | 하 |
| 5 | 빌드 스크립트 | 하 |
| 6 | ionCube 암호화 테스트 | 중 |
| 7 | 업데이트 시스템 | 상 |

## 11. 참고사항

- 고객 예약 기능은 라이선스 상태와 무관하게 항상 동작 (사업 중단 방지)
- ionCube 미지원 호스팅을 위한 대안 검토 필요
- SaaS 전환 시 라이선스 시스템이 멀티테넌트 과금으로 전환
