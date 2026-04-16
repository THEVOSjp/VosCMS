# VosCMS 서비스 신청 시스템

## 개요

VosCMS 본사(voscms.com)에서 운영하는 원스톱 서비스 신청 시스템.
도메인 등록, 웹 호스팅, 설치, 유지보수, 커스터마이징을 한 번에 신청하고 결제하면 자동으로 사이트가 생성되는 시스템.

- **페이지 유형**: 시스템 페이지 (배포 버전 미포함, 본사 전용)
- **디자인 샘플**: `/storage/tmp/service-order-sample.html`
- **기획일**: 2026-04-13

---

## 서비스 구성

### 1. 도메인

| 항목 | 내용 |
|------|------|
| 신규 등록 | NameSilo API 자동 등록 |
| 기존 도메인 | 고객 보유 도메인 연결 |
| 무료 서브도메인 | mysite.voscms.com, mysite.rezlyx.com 등 (보유 도메인 20개+ 활용) |
| 검색 방식 | 도메인명만 입력 → 14개 TLD 동시 검색 → 리스트 표시 |
| 다중 선택 | 체크박스로 여러 도메인 선택 가능 → 확인 클릭 시 확정 |
| 가격 | 관리자 설정에서 TLD별 판매가 또는 마진율(%) 자동 계산 |

**검색 TLD**: .com, .net, .org, .io, .co, .dev, .shop, .store, .site, .online, .biz, .info, .xyz, .app

**한국 도메인 (.co.kr, .kr)**: NameSilo 미지원 → 국내 공인 등록대행자(호스팅케이알 등) 별도 연동 필요

### 2. 웹 호스팅

| 플랜 | 용량 | 월 가격 | 비고 |
|------|------|--------|------|
| 무료 | 50MB | 0원 | 하단 광고 포함, 서브도메인 기본 |
| 입문 | 500MB | 3,000원 | |
| 추천 | 1GB | 5,000원 | 기본 선택 |
| 비즈니스 | 3GB | 10,000원 | |
| 프로 | 5GB | 18,000원 | |
| 엔터프라이즈 | 10GB | 30,000원 | |
| 대용량 | 15GB | 45,000원 | |
| 프리미엄 | 20GB | 55,000원 | |
| 맥스 | 30GB | 80,000원 | |

**VosCMS 코어 크기**: ZIP 9.3MB / 설치 후 29MB → 50MB 무료 플랜에서도 운영 가능

**계약 기간 할인**:

| 기간 | 할인율 |
|------|--------|
| 1개월 | 0% |
| 6개월 | 5% |
| 1년 | 10% |
| 2년 | 15% |
| 3년 | 20% |
| 5년 | 30% |

**추가 용량**: 현재 플랜에 용량 추가 가능 (+1GB ~ +50GB)

**기본 포함 사항**: SSL 인증서 무료, 일일 백업, PHP 8.3, MySQL/MariaDB

### 3. 부가 서비스

#### 설치 지원 (무료)
- VosCMS 설치 및 초기 설정 대행
- 도메인 연결, SSL 설정, 기본 환경 구성

#### 기술 지원 (120,000원/년)
- 이메일/채팅 기술 지원
- 버그 수정, 보안 업데이트 적용, 장애 대응
- 영업일 기준 24시간 이내 응답

#### 정기 유지보수

| 플랜 | 가격 | 내용 |
|------|------|------|
| Basic | 10,000원/월 | 보안 업데이트, 월 1회 백업 확인 |
| Standard | 20,000원/월 | + 플러그인/코어 업데이트, 주 1회 백업, 이메일 지원 |
| Pro | 30,000원/월 | + 성능 모니터링, 장애 대응(24h), 일일 백업, 월 리포트 |
| Enterprise | 50,000원/월 | + 전담 매니저, 긴급 대응(4h), 커스텀 월 2건, 트래픽 분석 (포털·쇼핑몰) |

#### 커스터마이징 개발 (별도 견적)
- 맞춤 디자인, 전용 플러그인, 외부 시스템 연동, 데이터 마이그레이션
- 시스템 페이지로 제작 → 고객 사이트에 납품

#### 메일 서비스

| 구분 | 내용 | 가격 |
|------|------|------|
| 기본 메일 | 호스팅 기본 5개 포함, 계정당 1GB, 웹메일/IMAP/POP3 | 무료 |
| 비즈니스 메일 | 대용량 첨부 10GB, 계정당 10GB, 스팸필터, 바이러스 검사 | 5,000원/계정/월 |

### 4. 신청자 정보

- **비회원**: 이름, 이메일, 비밀번호, 연락처 입력 → 결제 시 자동 회원가입
- **회원**: 로그인 → 정보 자동 기입 → 사이트 용도, 요청사항만 입력
- **공통**: 사이트 용도 선택, 요청사항 텍스트

### 5. 결제 방법

| 방법 | 상태 |
|------|------|
| 카드 결제 | 지원 |
| 계좌이체 | 지원 |
| PayPal | 추후 추가 |

- 이용약관 및 개인정보처리방침 동의 필수
- 영수증/세금계산서는 마이페이지에서 발행

### 6. 주문 요약

- 선택 항목별 금액 표시
- 장기 할인 적용
- **통화 전환**: 🇰🇷 KRW, 🇺🇸 USD, 🇯🇵 JPY, 🇨🇳 CNY, 🇪🇺 EUR (5개)
- 표시만 전환, 실제 결제는 KRW 또는 USD

---

## 관리자 환경 설정

서비스 신청 페이지 헤더의 기어 아이콘으로 접근. 관리자만 표시.

### 환율 설정
- USD → KRW 환율 (수동 설정 또는 자동 업데이트)
- 자동: 매일/매주 한국은행 API

### 도메인 마진 설정
- 기본 마진율 (%) — NameSilo 원가 × 환율 × 마진율 = 판매가
- 가격 반올림 단위 (100원/500원/1,000원)
- TLD별 개별 고정 가격 (빈칸이면 마진율 자동 적용)

### 호스팅 가격 설정
- 플랜별 월 가격 직접 설정

### 장기 할인율
- 기간별 할인율 (%) 설정

### NameSilo API
- API Key 설정
- 샌드박스 모드 (테스트)
- 연결 테스트 버튼

---

## 자동 프로비저닝 (결제 후 자동 처리)

```
고객 결제 완료
    ↓ (즉시, 약 1~3분)
① 도메인 등록 ← NameSilo API
    ↓
② 서버 계정 생성 ← nginx vhost + DB + FTP 자동 생성 (shell script)
    ↓
③ DNS 설정 ← NameSilo API (A 레코드 → 호스팅 서버 IP)
    ↓
④ SSL 발급 ← Let's Encrypt certbot --nginx
    ↓
⑤ VosCMS 설치 ← PHP CLI install (자동 설치)
    ↓
⑥ 완료 메일 발송 ← SMTP (접속 정보: URL, 관리자 계정, FTP)
    ↓
고객 사이트 접속 가능
```

### 각 단계별 구현

| 단계 | 기술 | 소요 시간 |
|------|------|----------|
| 도메인 등록 | NameSilo REST API (`registerDomain`) | 수 초 |
| 서버 계정 | PHP exec → bash script (nginx vhost, mysql CREATE DB, vsftpd user) | 수 초 |
| DNS 설정 | NameSilo REST API (`dnsAddRecord` A/CNAME) | 수 초 |
| SSL 발급 | certbot --nginx -d domain.com --non-interactive | 10~30초 |
| VosCMS 설치 | 배포 ZIP 해제 → PHP CLI install.php 실행 | 수 초 |
| 메일 발송 | Postfix 또는 외부 SMTP | 즉시 |

### 무료 서브도메인 프로비저닝

서브도메인(mysite.voscms.com)은 NameSilo API 불필요:
1. nginx vhost 생성 (server_name: mysite.voscms.com)
2. DNS: *.voscms.com 와일드카드 A 레코드 → 서버 IP
3. SSL: 와일드카드 인증서 또는 개별 certbot
4. VosCMS 자동 설치

---

## 무료 플랜 마케팅

### 홍보 문구
> **도메인, 웹호스팅, CMS 무료 제공!**
> **무료 홈페이지 제작 — 1분이면 완성!**

### 무료 플랜 구성

| 항목 | 내용 |
|------|------|
| 도메인 | 서브도메인 무료 (보유 도메인 20개+ 중 선택) |
| 호스팅 | 50MB |
| VosCMS | 코어 + 위젯 12개 |
| SSL | Let's Encrypt |
| 설치 | 자동 (회원가입 → 사이트명 입력 → 끝) |
| 메일 | 미제공 |
| 제한 | 하단 "Powered by VosCMS" 광고 |

### 수익 전환 모델

```
무료 체험 → 자기 도메인 구매 → 유료 호스팅 전환
                ↓
         용량 부족 → 플랜 업그레이드
                ↓
         부가 서비스 (유지보수, 메일, 커스터마이징)
                ↓
         광고 제거 → 유료 전환
```

---

## NameSilo API 연동

별도 문서: `/docs/NAMESILO_API.md`

### 핵심 API

| 용도 | 엔드포인트 |
|------|-----------|
| 도메인 검색 | `checkRegisterAvailability` |
| 도메인 등록 | `registerDomain` |
| DNS 추가 | `dnsAddRecord` |
| DNS 조회 | `dnsListRecords` |
| 가격 조회 | `getPrices` |
| 도메인 갱신 | `renewDomain` |
| 도메인 정보 | `getDomainInfo` |

### API 키
- .env: `NAMESILO_API_KEY`
- 발급: NameSilo → My Account → API Manager

---

## 시스템 페이지 구조

```
시스템 페이지 (배포 제외)
├── /service/order          — 서비스 신청 (메인 페이지)
├── /service/order/confirm  — 주문 확인
├── /service/order/complete — 결제 완료 + 프로비저닝 진행 상태
└── /service/admin/settings — 관리자 환경 설정 (환율, 마진, API)

마이페이지 (기존 시스템 활용)
├── /mypage/services        — 내 서비스 목록
├── /mypage/services/{id}   — 서비스 상세 (도메인, 호스팅 관리)
├── /mypage/billing         — 결제 내역 / 영수증 / 세금계산서
└── /mypage/domains         — 도메인 관리 (DNS, 갱신)
```

---

## DB 테이블 (예정)

```sql
-- 서비스 주문
rzx_service_orders (
    id, user_id, status, total_amount, currency,
    payment_method, payment_status, paid_at,
    domain_option, hosting_plan, hosting_period,
    extra_storage, maintenance_plan,
    notes, created_at, updated_at
)

-- 주문 항목
rzx_service_order_items (
    id, order_id, item_type, item_name,
    quantity, unit_price, subtotal,
    config_json, created_at
)

-- 호스팅 계정
rzx_hosting_accounts (
    id, user_id, order_id, domain,
    plan, storage_mb, status,
    server_ip, ftp_user, db_name,
    expires_at, created_at
)

-- 도메인 관리
rzx_domains (
    id, user_id, order_id, domain_name,
    registrar, registration_date, expiry_date,
    auto_renew, nameservers, status,
    registrar_order_id, created_at
)

-- 서비스 가격 설정
rzx_service_pricing (
    id, item_type, item_key,
    price, currency, margin_rate,
    is_active, created_at, updated_at
)
```

---

## 서비스 분류 체계 (2026-04-16)

### service_class

| 값 | 설명 | 자동연장 | 예시 |
|---|---|---|---|
| `recurring` | 유료 반복 | O (토글) | 웹 호스팅, 기술 지원, 비즈니스 메일 |
| `free` | 무료 | X (연장 신청) | 무료 호스팅, 무료 서브도메인 |
| `one_time` | 1회성 | X | 설치 지원, 커스터마이징 개발 |

### 1회성 서비스 상태

| status | completed_at | 표시 |
|---|---|---|
| pending | - | 접수 (파랑) |
| active | NULL | 진행 (주황) |
| suspended | - | 보류 (회색) |
| cancelled | - | 취소 (빨강) |
| any | 값 있음 | 완료 (녹색) |

### DB 컬럼 (031 마이그레이션)

```sql
ALTER TABLE rzx_subscriptions
  ADD COLUMN service_class VARCHAR(15) DEFAULT 'recurring' AFTER type,
  ADD COLUMN completed_at DATETIME NULL AFTER next_billing_at;
```

---

## 마이페이지 서비스 관리

### 서비스 목록 (`/mypage/services`)
- 주문번호별 요약 카드 (도메인, 서비스 태그, 기간, 금액)
- 카드 클릭 → 상세 페이지 이동

### 서비스 상세 (`/mypage/services/{order_number}`)
- 주문 정보 섹션 (자동연장 토글/연장 신청 포함)
- 서비스 타입별 탭: 웹 호스팅, 도메인, 메일, 유지보수, 부가서비스
- 파셜 뷰: `resources/views/customer/mypage/service-partials/{type}.php`

### 서비스 관리 API (`/api/service-manage.php`)
| action | 설명 |
|---|---|
| `toggle_auto_renew` | 자동연장 토글 (recurring만) |
| `request_renewal` | 무료 서비스 연장 신청 |
| `set_primary_domain` | 메인 도메인 설정 |
| `change_mail_password` | 메일 비밀번호 변경 |
| `mark_complete` | 1회성 서비스 완료 (관리자) |

---

## 관리자 서비스 주문 관리

### 주문 목록 (`/admin/service-orders`)
- 통계 카드: 전체/결제완료/대기/실패/취소 + 1회성 접수 대기
- 검색: 주문번호, 도메인, 이름, 이메일
- 상태 필터링, 페이지네이션

### 주문 상세 (`/admin/service-orders/{order_number}`)
- 주문 상태 변경 (셀렉트)
- 서비스 타입별 탭 (마이페이지와 동일 구조)
- 1회성 서비스: 상태 배지 클릭 → 모달에서 접수/진행/보류/취소/완료 선택
- 활동 로그 사이드바

---

## 무료 도메인 설정

관리자 설정 (`service_free_domains`)에서 무료 서브도메인 목록 관리.
- 복수 도메인 등록 가능 (예: `21ces.net`, `voscms.com`)
- 1개면 고정 표시, 2개 이상이면 셀렉트 드롭다운
- 설정 키: `service_free_domains` (JSON 배열)

---

## 개발 우선순위

1. ~~**시스템 페이지 기본 구현**~~ ✅ 서비스 신청 폼
2. ~~**NameSilo API 연동**~~ ✅ 도메인 검색 (WHOIS)
3. ~~**결제 연동**~~ ✅ PAY.JP 카드 결제 + 계좌이체 + 무료
4. **자동 프로비저닝** — nginx vhost + DB + VosCMS 자동 설치
5. ~~**마이페이지 서비스 관리**~~ ✅ 서비스 목록/상세/탭/API
6. ~~**무료 플랜**~~ ✅ 서브도메인 + 즉시 활성화
7. ~~**관리자 대시보드**~~ ✅ 주문 목록/상세/1회성 상태 관리
