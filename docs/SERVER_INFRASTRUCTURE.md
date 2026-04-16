# VosCMS 서버 인프라 구축 계획서

**작성일**: 2026-04-15
**최종 수정**: 2026-04-16
**버전**: 1.1

---

## 1. 개요

VosCMS 서비스 신청 시스템의 자동 프로비저닝을 위한 서버 인프라 구축 계획.
고객이 서비스를 신청하고 결제하면, 웹호스팅/메일/도메인이 자동으로 설정되는 구조.

## 2. 서버 구성도

```
┌─────────────────────────────────────────────────────────────┐
│                     Cloudflare (무료)                        │
│              DNS + SSL + CDN + DDoS 방어                    │
└─────────────┬───────────────────────────┬───────────────────┘
              │                           │
    ┌─────────▼─────────┐       ┌─────────▼─────────┐
    │   서버 1 (메인)     │       │   서버 2 (메일)     │
    │  Xeon E5-2683 v4   │◄─────►│  Xeon E5-2630 v4   │
    │  16코어 64GB        │ API   │  10코어 16GB        │
    │  NVMe + SSD 2TB    │       │  NVMe 128G+HDD 4TB │
    │                    │       │                    │
    │ ・VosCMS 본체       │       │ ・Mailcow (메일)    │
    │ ・고객 웹호스팅      │       │ ・서버 1 백업        │
    │ ・고객 MySQL DB     │       │ ・모니터링           │
    │ ・결제/주문 처리     │       │                    │
    │ ・Cloudflare 제어   │       │                    │
    │ ・Cron 작업         │       │                    │
    └────────────────────┘       └────────────────────┘
```

## 3. 서버 1 — 메인 서버 (VosCMS + 고객 호스팅)

### 역할
- VosCMS 웹 애플리케이션 운영
- **고객 웹사이트 호스팅** (nginx + PHP-FPM)
- **고객 MySQL 데이터베이스**
- 주문/결제/구독 관리
- 관리자 대시보드
- Cloudflare DNS API 제어
- 서버 2 메일 서버 제어 (Mailcow API 호출)

### 하드웨어
| 항목 | 스펙 |
|------|------|
| 호스트명 | thevos-X99 |
| CPU | Intel Xeon E5-2683 v4 (16코어 / 32쓰레드 @ 2.10GHz) |
| RAM | 64GB DDR4 ECC |
| 마더보드 | X99 플랫폼 |
| OS 디스크 | NVMe SSD 120GB |
| 데이터 디스크 | SSD 추가 장착 예정 (고객 데이터) |
| 네트워크 | 1Gbps (enp6s0) |

### 디스크 구성
| 디스크 | 마운트 | 용도 | 용량 |
|--------|--------|------|------|
| NVMe | `/` | OS + VosCMS 본체 | 120GB |
| SSD 2TB | `/data` | 고객 웹호스팅 + 고객 DB | 2TB |

### 소프트웨어 (설치 완료)
| 소프트웨어 | 버전 | 용도 |
|-----------|------|------|
| Ubuntu | 22.04+ | OS |
| nginx | latest | VosCMS + 고객 사이트 |
| PHP | 8.3 | VosCMS + 고객 PHP |
| MySQL | 8.0 | VosCMS DB + 고객 DB |
| ionCube | latest | PHP 암호화 |
| Cron | - | 구독 갱신, 가격 크롤링 |
| quota | - | 고객 디스크 용량 제한 |

### Cron 작업 (설정 완료)
```
0 0 * * *  cron-daily-prices.php       # 도메인 가격 크롤링 (매일 자정)
0 9 * * *  cron-subscription-renew.php  # 구독 자동 갱신 (매일 9시)
```

### 추가 개발 필요
- 고객 호스팅 자동 프로비저닝 (vhost + PHP-FPM pool + DB 자동 생성)
- Mailcow API 클라이언트 (`rzxlib/Modules/Server/MailManager.php`)
- Cloudflare API 클라이언트 (`rzxlib/Modules/Server/CloudflareManager.php`)
- 관리자 주문 관리 화면
- 자동 프로비저닝 워크플로우

## 4. 서버 2 — 메일 서버 전용 + 백업

### 역할
- **메일 서버** (Mailcow — 기본 메일 + 비즈니스 메일)
- **서버 1 백업 스토리지** (일일 자동 백업)
- **모니터링** (서버 1 상태 감시)

### 하드웨어
| 항목 | 스펙 |
|------|------|
| CPU | Intel Xeon E5-2630 v4 (10코어 / 20쓰레드 @ 2.20GHz) |
| RAM | 16GB DDR4 ECC |
| OS 디스크 | NVMe 256GB |
| 메일 디스크 | SSD 2TB |
| 백업 디스크 | HDD 8TB |
| 네트워크 | 1Gbps |

### 디스크 구성
| 디스크 | 마운트 | 용도 | 용량 |
|--------|--------|------|------|
| NVMe 256GB | `/` | OS + Docker + Mailcow 엔진 + MariaDB + Redis | 256GB |
| SSD 2TB | `/data/mail` | 메일 본문/첨부/maildir (빠른 I/O) | 2TB |
| HDD 8TB | `/backup` | 메인서버 백업 + 메일 백업 + 아카이브 | 8TB |

### RAM 사용 예측 (16GB)
| 구성 요소 | 사용량 |
|----------|--------|
| OS (Ubuntu) | 0.5GB |
| Docker | 0.3GB |
| Mailcow (Postfix+Dovecot+Rspamd+SOGo+Redis+MySQL) | 4~6GB |
| 백업 프로세스 | 0.5GB |
| 여유 | 4~9GB |
| **합계** | **~7GB / 16GB** |

※ 메일 서버 전용으로 고객 500명 이상까지 운영 가능
※ E5-2680 v4 (14코어)는 향후 서버 증설 시 활용

### 필수 소프트웨어
| 소프트웨어 | 용도 | 비고 |
|-----------|------|------|
| Docker + Docker Compose | Mailcow 실행 | 필수 |
| Mailcow | 메일 서버 | REST API로 계정 관리 |

### 네트워크 회선
| 항목 | 내용 |
|------|------|
| 회선 | NTT 가정용 광회선 (フレッツ光) |
| ISP (추가) | **인터링크 ZOOT NEXT** (月額 ¥1,320~) |
| 고정 IP | ✅ ZOOT NEXT 기본 제공 |
| OP25B | ✅ ZOOT NEXT는 비적용 (25번 포트 열림) |
| 역방향 DNS | ✅ ZOOT NEXT 관리 화면에서 설정 가능 |
| 2개월 무료 | ✅ 먼저 테스트 후 결정 가능 |

### 메일 서버에 필요한 포트
| 포트 | 프로토콜 | 용도 |
|------|---------|------|
| 25 | SMTP | 메일 수신 (다른 서버에서 들어옴) |
| 587 | SMTP Submission | 메일 발송 (사용자 → 서버) |
| 993 | IMAPS | 메일 조회 (IMAP over SSL) |
| 443 | HTTPS | 웹메일 (SOGo) |

### 역방향 DNS 설정 (필수)
```
고정 IP: xxx.xxx.xxx.xxx
역방향 DNS(PTR): mail.thevos.jp
```
※ 역방향 DNS 미설정 시 발송 메일이 스팸 처리됨
※ 인터링크 관리 화면에서 직접 설정 가능

### 서버 1에서 메일 서버 제어 (Mailcow API)
```
# 서버 1 → 서버 2 Mailcow API 호출

# 도메인 추가
POST https://mail.서버2/api/v1/add/domain

# 메일 계정 생성
POST https://mail.서버2/api/v1/add/mailbox

# 비밀번호 변경
POST https://mail.서버2/api/v1/edit/mailbox

# 메일 계정 삭제
POST https://mail.서버2/api/v1/delete/mailbox
```

## 5. 메일 서버 — Mailcow (권장)

### 선정 이유
| 항목 | 내용 |
|------|------|
| REST API | ✅ 완비 — 계정 CRUD, 비밀번호 변경, 도메인 관리 |
| 웹 UI | ✅ SOGo 웹메일 + 관리 UI |
| Docker | ✅ 컨테이너 기반 — 설치/업데이트 간편 |
| 스팸 필터 | ✅ Rspamd 내장 |
| DKIM/SPF/DMARC | ✅ 자동 설정 |
| IMAP/POP3 | ✅ Dovecot |
| SMTP | ✅ Postfix |

### Mailcow API 연동 예시
```
# 도메인 추가
POST /api/v1/add/domain
{ "domain": "example.com", "description": "Customer Site" }

# 메일 계정 생성
POST /api/v1/add/mailbox
{ "local_part": "info", "domain": "example.com", "password": "xxx", "quota": "1024" }

# 비밀번호 변경
POST /api/v1/edit/mailbox
{ "items": ["info@example.com"], "attr": { "password": "newpass" } }

# 메일 계정 삭제
POST /api/v1/delete/mailbox
{ "items": ["info@example.com"] }
```

### 리소스 요구
- RAM: 4GB+ (Mailcow 전용)
- 디스크: 사용자당 1~10GB
- 포트: 25(SMTP), 143(IMAP), 993(IMAPS), 587(Submission), 443(웹메일)

## 6. Cloudflare DNS (무료)

### 역할
- 네임서버 (자체 구축 불필요)
- SSL 인증서 자동 발급
- CDN (정적 파일 캐싱)
- DDoS 방어

### API 연동
```
# API 토큰 발급: Cloudflare 대시보드 > My Profile > API Tokens

# 도메인(Zone) 추가
POST https://api.cloudflare.com/client/v4/zones
{ "name": "example.com", "type": "full" }

# DNS 레코드 추가 (A 레코드)
POST https://api.cloudflare.com/client/v4/zones/{zone_id}/dns_records
{ "type": "A", "name": "@", "content": "서버2_IP", "proxied": true }

# MX 레코드 추가 (메일)
POST https://api.cloudflare.com/client/v4/zones/{zone_id}/dns_records
{ "type": "MX", "name": "@", "content": "mail.서버2도메인", "priority": 10 }

# CNAME 레코드 (www)
POST https://api.cloudflare.com/client/v4/zones/{zone_id}/dns_records
{ "type": "CNAME", "name": "www", "content": "example.com" }
```

### 비용
- Free 플랜: 무제한 도메인, 무제한 DNS 쿼리
- SSL: 무료 (Universal SSL)
- CDN: 무료 (기본)

## 7. 자동 프로비저닝 워크플로우

### 원칙
- **서브도메인 우선**: 결제 즉시 무료 서브도메인으로 서비스 시작
- **구입 도메인은 후속 작업**: DNS 전파(24~48시간)를 기다리지 않음
- **고객 대기 시간 0**: 결제 완료 → 즉시 사이트 접속 가능

### 프로비저닝 ID 규칙
```
무료 도메인 신청 시: 고객이 입력한 서브도메인명
  예) myshop.21ces.net → ID: myshop

도메인 구입 신청 시: 구입 도메인의 SLD (Second Level Domain)
  예) myshop.com → ID: myshop
  예) neitpat.co.kr → ID: neitpat

프로비저닝 기준:
  - Nginx vhost 이름
  - PHP-FPM pool 이름
  - MySQL DB명 / 유저명
  - Linux 유저명
  - FTP 아이디
  모두 이 ID를 기준으로 생성
```

### Step 1: 즉시 프로비저닝 (결제 완료 직후)
```
1. 고객: 서비스 신청 + 결제 완료
   └→ rzx_orders.status = 'paid'
   └→ 프로비저닝 ID 결정: 신청 서브도메인명 또는 구입 도메인 SLD

2. 무료 서브도메인 기반 자동 설정 (즉시):
   ├→ Cloudflare: 설정 불필요 (*.21ces.net 와일드카드 터널 등록 완료)
   ├→ Nginx: vhost 생성 (server_name {subdomain}.21ces.net)
   ├→ PHP-FPM: 고객 전용 pool 생성 ({id})
   ├→ MySQL: DB + 유저 자동 생성 ({id})
   ├→ VosCMS: 파일 복사 + DB 초기화 + .env 설정
   ├→ Mailcow API: 메일 도메인 + 계정 생성
   ├→ SSL: Cloudflare Universal SSL (서브도메인 즉시 적용)
   └→ rzx_orders.status = 'active'

3. 서버 접속정보 자동 저장:
   ├→ subscription.metadata.server.ftp = { host, ip, user, port }
   ├→ subscription.metadata.server.db = { host, name, user }
   ├→ subscription.metadata.server.env = { php, mysql }
   └→ 마이페이지에 즉시 반영

4. 고객에게 알림 메일 발송:
   ├→ https://{subdomain}.21ces.net 접속 가능
   ├→ 관리자 로그인 정보
   ├→ FTP/DB 접속정보
   ├→ 메일 계정 정보 (웹메일 URL)
   └→ 기본 사용 가이드
```

### Step 2: 구입 도메인 연결 (비동기, 후속 작업)
```
※ 도메인 구입 신청이 포함된 경우에만 실행

1. Cloudflare API: 새 Zone 추가 (customer.com)
   └→ 응답에서 NS 자동 수신 (예: brett.ns.cloudflare.com, kara.ns.cloudflare.com)

2. NS 정보를 metadata에 자동 저장
   └→ 마이페이지 도메인 탭에 즉시 표시

3. 고객에게 NS 변경 안내 메일 발송
   └→ "도메인 등록처에서 네임서버를 변경해주세요"

4. DNS 전파 대기 (24~48시간)
   └→ Cloudflare Zone 상태 모니터링 (status: pending → active)

5. 전파 완료 후:
   ├→ Cloudflare: A, MX, CNAME, SPF, DKIM, DMARC 레코드 설정
   ├→ Nginx: vhost에 server_name 추가 (기존 서브도메인 + 구입 도메인)
   ├→ Mailcow: 구입 도메인 메일 도메인 추가
   └→ 주문의 메인 도메인 업데이트 (서브도메인 → 구입 도메인)
```

### 도메인 우선순위
```
결제 직후:  {subdomain}.21ces.net (메인) ← 즉시 접속 가능
DNS 전파 후: customer.com (메인) + {subdomain}.21ces.net (보조)
```
서브도메인은 삭제하지 않고 보조 도메인으로 유지 → 구입 도메인 만료 시에도 접속 가능.

### 마이페이지 서비스 변경 (자동)
```
메일 비밀번호 변경:
  → Mailcow API (POST /api/v1/edit/mailbox)
  → DB metadata 업데이트

메일 계정 추가:
  → PAY.JP 추가 결제 (차액)
  → Mailcow API (POST /api/v1/add/mailbox)
  → DB 구독 수량/금액 업데이트

호스팅 용량 추가:
  → PAY.JP 추가 결제 (차액)
  → 서버 quota 변경
  → DB 구독 금액 업데이트

호스팅 플랜 변경:
  → PAY.JP 차액 계산 + 결제/환불
  → vhost + quota + PHP 설정 변경
  → DB 구독 전체 업데이트

도메인 추가 연결:
  → Cloudflare Zone 추가 + NS 안내
  → DNS 전파 후 vhost 업데이트

도메인 연장:
  → PAY.JP 결제
  → DB 만기일 연장
  → 실제 등록은 수동 (hosting.kr 등)
```

## 8. VosCMS 개발 모듈 (서버 제어)

```
rzxlib/Modules/Server/
├── ServerManager.php          — 서버 제어 통합 관리
├── CloudflareManager.php      — Cloudflare DNS API
├── HostingManager.php         — 호스팅 계정 CRUD + quota
├── MailManager.php            — Mailcow API 연동
├── DatabaseManager.php        — 고객 DB 관리
└── ProvisioningService.php    — 자동 프로비저닝 워크플로우

api/
├── service-order.php          — 주문 + 결제 (구현 완료)
├── service-change.php         — 서비스 변경 API (신규)
├── webhook-payjp.php          — PAY.JP 결제 Webhook (구현 완료)
└── webhook-cloudflare.php     — Cloudflare Webhook (선택)

scripts/
├── cron-daily-prices.php      — 도메인 가격 크롤링 (구현 완료)
├── cron-subscription-renew.php — 구독 자동 갱신 (구현 완료)
└── cron-provisioning.php      — 프로비저닝 대기 주문 처리 (신규)
```

## 9. 외부 서비스 연동

| 서비스 | 용도 | 비용 | API |
|--------|------|------|-----|
| Cloudflare | DNS + SSL + CDN | 무료 | REST API |
| PAY.JP | 카드 결제 + 구독 | 수수료 3.6% | REST API |
| Frankfurter | 환율 조회 | 무료 | REST API |
| xdomain.ne.jp | 도메인 가격 참고 | 무료 | 크롤링 |
| NameSilo | 도메인 검색 (WHOIS) | 무료 | - (WHOIS Socket) |
| Mailcow | 메일 서버 관리 | 무료 (OSS) | REST API |

## 10. 구축 순서

### Phase 0: 현재 서버 1대 올인원 (즉시 실행)
1. SSD 2TB + HDD 8TB 장착
2. 파티션 + 마운트 (/data, /backup) — UUID 기반
3. MySQL datadir 이전 → /data/mysql
4. Docker + Mailcow 설치 → /data/mail
5. voscms.com DNS + SSL 설정
6. 백업 크론 설정 → /backup
7. 서비스 운영 시작

### Phase 1: 서버 2 분리 (고객 50+ 이후)
1. OS 설치 (Ubuntu 24.04 LTS)
2. nginx + PHP-FPM + MySQL 설치
3. Docker + Docker Compose 설치
4. Mailcow 설치 및 설정
5. 방화벽 설정 (ufw)
6. 서버 1 ↔ 서버 2 통신 확인 (SSH 키 교환)

### Phase 2: 제어 API 개발
1. 서버 2에 제어 API 설치 (PHP)
2. 호스팅 계정 CRUD API
3. Mailcow 연동 API
4. DB 관리 API
5. API 토큰 인증

### Phase 3: Cloudflare 연동
1. Cloudflare 계정 + API 토큰 발급
2. CloudflareManager 개발
3. DNS 자동 설정 테스트

### Phase 4: 자동 프로비저닝
1. ProvisioningService 개발
2. 주문 완료 → 자동 설치 워크플로우
3. 마이페이지 서비스 변경 API
4. 테스트 (전체 흐름)

### Phase 5: 운영
1. 모니터링 설정
2. 백업 자동화
3. 보안 점검
4. 문서화

## 11. 성능 예측 및 확장 계획

### 용어 정의
| 용어 | 의미 |
|------|------|
| 고객 (사이트) | VosCMS가 설치된 사이트 수 |
| 동시 접속자 | 같은 시간에 접속한 방문자 수 |

### 서버 1 처리 능력 (16코어 / 64GB)

| 사이트 수 | 사이트당 동시 접속 | 전체 동시 접속 | 가능 여부 |
|----------|:---:|:---:|:---:|
| 100 사이트 | 5명 | 500명 | ✅ 여유 |
| 100 사이트 | 20명 | 2,000명 | ✅ 가능 |
| 100 사이트 | 50명 | 5,000명 | ⚠ 한계 |
| 50 사이트 | 100명 | 5,000명 | ⚠ 한계 |

※ 대부분의 소규모 사이트: 동시 접속 5~10명 수준
※ 트래픽 과다 시 Cloudflare CDN 캐싱으로 서버 부하 경감 가능

### 서버 2 처리 능력 (10코어 / 16GB)

| 메일 계정 수 | 동시 IMAP 접속 | 가능 여부 |
|:---:|:---:|:---:|
| ~500 계정 | ~100 | ✅ 여유 |
| ~2,000 계정 | ~500 | ✅ 가능 |
| 5,000+ 계정 | 1,000+ | ⚠ 증설 검토 |

### 확장 계획

```
[Phase 1] 고객 ~100 사이트 (현재)
  서버 1: VosCMS + 웹호스팅 + DB (전부)
  서버 2: 메일 전용 + 백업

[Phase 2] 고객 100~500 사이트
  서버 1: VosCMS + 웹호스팅
  서버 2: 메일 전용 + 백업
  서버 3: DB 전용 (예비 E5-2680 v4 활용) ← DB 분리

[Phase 3] 고객 500 사이트 이상
  서버 1: VosCMS 본체 (관리 전용)
  서버 2: 메일 전용
  서버 3: DB 전용
  서버 4+: 웹호스팅 (로드밸런싱)
```

### 병목 예측 및 대응
| 병목 지점 | 증상 | 대응 |
|----------|------|------|
| CPU | 응답 느림, PHP 처리 지연 | PHP-FPM 워커 조정, OPcache 최적화 |
| RAM | 스왑 발생, OOM Killer | MySQL 버퍼 조정, PHP 메모리 제한 |
| 디스크 I/O | DB 쿼리 느림 | DB 서버 분리 (Phase 2) |
| 네트워크 | 대역폭 포화 | Cloudflare CDN 활용 |
| 메일 큐 | 발송 지연 | Rspamd 튜닝, 큐 모니터링 |

## 12. 단계별 구축 실행 계획 (2026-04-16 확정)

### Phase 0: 현재 서버 1대 운영 (즉시)

SSD 2TB + HDD 8TB를 현재 서버에 장착하여 올인원 운영 시작.
서버 2(메일/백업 전용)는 추후 분리.

#### 디스크 구성

```
/dev/nvme0n1 (116GB)  — OS + 시스템
/dev/sda (2TB SSD)    — 실시간 데이터 (빠른 I/O)
  ├── /data/www/          ← 고객 웹사이트
  ├── /data/mysql/        ← MySQL datadir 이전
  └── /data/mail/         ← Mailcow Docker 데이터

/dev/sdb (8TB HDD)    — 백업 + 아카이브
  ├── /backup/daily/      ← 일일 백업 (최근 7일)
  ├── /backup/weekly/     ← 주간 백업 (최근 4주)
  ├── /backup/monthly/    ← 월간 백업 (최근 12개월)
  └── /backup/archive/    ← 해지 고객 데이터 보관
```

#### 마운트 설정 (UUID 기반 — 디스크 이동 대비)

```bash
# UUID 확인
blkid /dev/sda1
blkid /dev/sdb1

# /etc/fstab (장치명이 아닌 UUID 사용)
UUID=xxxx-xxxx  /data    ext4  defaults,noatime  0  2
UUID=yyyy-yyyy  /backup  ext4  defaults,noatime  0  2
```

> **중요**: UUID 기반 마운트이므로 HDD를 물리적으로 다른 서버에 장착해도
> 포맷 없이 마운트만 하면 데이터 그대로 사용 가능.

#### 현재 서버 리소스 현황

| 항목 | 사양 | 사용량 |
|---|---|---|
| CPU | Xeon E5-2683 v4 (32쓰레드) | 여유 |
| RAM | 64GB | 7GB 사용 / 55GB 여유 |
| OS 디스크 | NVMe 116GB | 80GB 사용 / 31GB 잔여 |
| 사이트 | 10개 | ~2.4GB |
| MySQL | - | 185MB |

#### 용량 예측 (SSD 2TB)

| 용도 | 예상 용량 |
|---|---|
| 고객 사이트 100개 × 1GB | ~100GB |
| Mailcow + 메일 계정 50개 × 10GB | ~500GB |
| MySQL DB 전체 | ~50GB |
| 여유 | ~1.3TB |

#### 용량 예측 (HDD 8TB)

| 용도 | 예상 용량 |
|---|---|
| 일일 백업 7일 × 200GB | ~1.4TB |
| 주간 백업 4주 × 200GB | ~800GB |
| 월간 백업 12개월 × 200GB | ~2.4TB |
| 해지 고객 아카이브 | ~1TB |
| 여유 | ~2.4TB |

### 실행 순서

```
Step 1. SSD 2TB + HDD 8TB 장착
Step 2. 파티션 생성 + ext4 포맷 + UUID 마운트
Step 3. MySQL datadir → /data/mysql 이전
Step 4. 고객 www → /data/www 심볼릭 링크 또는 이전
Step 5. Docker 설치 + Mailcow → /data/mail
Step 6. voscms.com DNS → 현재 서버 IP
Step 7. Nginx vhost + Let's Encrypt SSL
Step 8. 메일 DNS 설정 (MX, SPF, DKIM, DMARC)
Step 9. 백업 크론 설정 (→ /backup)
```

### 디스크 분리 이동 계획 (추후 서버 2 투입 시)

```
현재 서버 (1대 운영)              →  서버 2 투입 후
┌──────────────────────┐         ┌──────────────┐  ┌──────────────────┐
│ NVMe 116GB (OS)      │         │ 서버 1       │  │ 서버 2           │
│ SSD 2TB (/data)      │   →     │ NVMe (OS)    │  │ NVMe 256GB (OS)  │
│ HDD 8TB (/backup)    │         │ SSD 2TB      │  │ SSD 2TB (신규)   │ ← 메일 데이터
└──────────────────────┘         │ (웹/DB)      │  │ HDD 8TB          │ ← 물리 이동
                                 └──────────────┘  │ (백업)           │
                                                    └──────────────────┘
```

- HDD 8TB → 서버 2에 물리 이동 → UUID로 마운트 (포맷 불필요)
- Mailcow Docker 데이터 → 서버 2 SSD로 마이그레이션
- 서버 1은 SSD 2TB (웹/DB 전용)으로 경량화

---

## 13. 보안 고려사항

- 서버 간 통신: API 토큰 + IP 화이트리스트
- 고객 데이터: 암호화 저장 (AES-256, 기존 encrypt/decrypt 활용)
- 메일 비밀번호: 암호화 저장 + Mailcow에만 평문 전달
- 서버 2 접근: 서버 1 IP만 허용 (방화벽)
- SSH: 키 기반 인증만 (비밀번호 로그인 비활성화)
- DB: 로컬 접속만 허용
- 백업: 일일 자동 백업 (서버 1 ↔ 서버 2 교차)
