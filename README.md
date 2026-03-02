# RezlyX

**현대적인 예약 관리 시스템**

> *"예약을 넘어, 세계로"*
> *"Beyond reservation. To the world."*

---

## 소개

RezlyX는 PHP 8.0+ 기반의 현대적인 예약 관리 시스템입니다. 레거시 ReservationX 시스템을 완전히 새롭게 재구축하여, 모바일 친화적이고 확장 가능한 아키텍처를 제공합니다.

### 주요 특징

- **PWA 지원**: 모바일 앱처럼 설치 및 오프라인 사용 가능
- **다국어 지원**: 한국어, 영어, 일본어 (추가 언어 확장 가능)
- **반응형 디자인**: 모바일, 태블릿, 데스크톱 완벽 지원
- **실시간 예약**: 즉각적인 예약 확인 및 알림
- **다양한 결제 수단**: 국내외 PG사 연동 (토스, Stripe 등)
- **회원 관리**: 등급 시스템, 적립금, 소셜 로그인
- **쉬운 설치**: EC-CUBE 스타일의 웹 설치 마법사

---

## 시스템 요구사항

### 필수 요구사항

| 항목 | 최소 버전 |
|------|----------|
| PHP | 8.0 이상 |
| MySQL | 5.7 이상 / MariaDB 10.3 이상 |
| Composer | 2.0 이상 |
| Node.js | 18.0 이상 (빌드 시) |

### PHP 확장 모듈

- PDO, PDO_MySQL
- mbstring
- json
- openssl
- curl (권장)
- gd (권장)

---

## 설치 방법

### 1. 파일 업로드

```bash
# Git clone
git clone https://github.com/your-org/rezlyx.git

# 또는 압축 파일 해제 후 웹 서버에 업로드
```

### 2. Composer 패키지 설치

```bash
cd rezlyx
composer install --optimize-autoloader --no-dev
```

### 3. 프론트엔드 빌드 (개발 환경)

```bash
npm install
npm run build
```

### 4. 웹 설치 마법사

브라우저에서 사이트 접속:
```
http://your-domain.com/install/
```

설치 마법사를 따라 진행:
1. 시스템 요구사항 확인
2. 데이터베이스 설정
3. 관리자 계정 생성
4. 설치 완료

### 5. 설치 후 보안 조치

```bash
# install 폴더 삭제 또는 접근 차단
rm -rf install/

# .env 파일 권한 설정
chmod 600 .env
```

---

## 디렉토리 구조

```
rezlyx/
├── database/
│   └── migrations/         # 데이터베이스 마이그레이션
├── docs/                   # 프로젝트 문서
├── routes/                 # 라우트 정의
│   ├── web.php             # 웹 라우트
│   └── api.php             # API 라우트
├── rzxlib/                 # 코어 라이브러리
│   ├── Core/
│   │   ├── Auth/           # 인증 시스템
│   │   ├── Database/       # 데이터베이스 추상화
│   │   ├── Helpers/        # 헬퍼 함수
│   │   ├── Http/           # HTTP Request/Response
│   │   ├── Middleware/     # 미들웨어
│   │   ├── Routing/        # 라우팅 시스템
│   │   └── Validation/     # 유효성 검사
│   ├── Http/
│   │   └── Controllers/    # 컨트롤러
│   │       ├── Admin/      # 관리자 컨트롤러
│   │       └── Api/        # API 컨트롤러
│   └── Reservation/
│       ├── Models/         # 예약 모델
│       └── Services/       # 비즈니스 로직
├── skins/                  # 테마/스킨
│   └── default/            # 기본 테마
├── .env                    # 환경 설정
├── composer.json           # PHP 의존성
└── package.json            # Node.js 의존성
```

---

## 설정

### 환경 설정 (.env)

주요 설정 항목:

```env
APP_NAME="RezlyX"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=127.0.0.1
DB_DATABASE=rezlyx
DB_USERNAME=your_user
DB_PASSWORD=your_password

ADMIN_PATH=admin
```

### 관리자 경로 변경

보안을 위해 관리자 경로를 변경하는 것을 권장합니다:

```env
ADMIN_PATH=your-custom-admin-path
```

---

## 개발 가이드

### 로컬 개발 환경 설정

```bash
# 환경 설정 파일 복사
cp .env.example .env

# 의존성 설치
composer install
npm install

# 개발 서버 실행
php -S localhost:8000 -t public

# CSS 변경 감시 (별도 터미널)
npm run css:watch
```

### 코딩 스타일

```bash
# 코드 스타일 검사
composer run cs-check

# 자동 수정
composer run cs-fix

# 정적 분석
composer run analyse
```

### 테스트 실행

```bash
composer run test
```

---

## 기여하기

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 라이선스

이 프로젝트는 독점 소프트웨어입니다. 자세한 라이선스 정보는 LICENSE 파일을 참조하세요.

---

## 지원

- **문서**: [docs.rezlyx.com](https://docs.rezlyx.com) (준비 중)
- **이슈**: GitHub Issues
- **이메일**: support@rezlyx.com

---

## 문서

| 문서 | 설명 |
|------|------|
| [ROUTING_HTTP.md](docs/ROUTING_HTTP.md) | 라우팅 및 HTTP 시스템 |
| [RESERVATION_SYSTEM.md](docs/RESERVATION_SYSTEM.md) | 예약 시스템 가이드 |
| [API_REFERENCE.md](docs/API_REFERENCE.md) | REST API 레퍼런스 |
| [CODING_CONVENTIONS.md](docs/CODING_CONVENTIONS.md) | 코딩 컨벤션 |

---

## 빠른 시작

### 예약 생성

```php
use RzxLib\Reservation\Services\ReservationService;

$service = new ReservationService();
$reservation = $service->create([
    'service_id' => 1,
    'customer_name' => '홍길동',
    'customer_email' => 'hong@example.com',
    'booking_date' => '2025-03-01',
    'start_time' => '10:00',
]);

echo $reservation->booking_code; // RZ250301ABC123
```

### API 사용

```bash
# 가용 시간 조회
curl "http://localhost/api/services/1/available-slots?date=2025-03-01"

# 예약 생성
curl -X POST "http://localhost/api/reservations" \
  -H "Content-Type: application/json" \
  -d '{"service_id":1,"customer_name":"홍길동","customer_email":"hong@example.com","booking_date":"2025-03-01","start_time":"10:00"}'
```

---

## 버전 이력

| 버전 | 날짜 | 변경 내용 |
|------|------|----------|
| 1.0.0 | 2026-02-27 | 최초 릴리스 |
| 1.1.0 | 2026-03-01 | 라우팅, 예약 시스템, API 추가 |

---

*RezlyX - 예약을 재정의하다*
