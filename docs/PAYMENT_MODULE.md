# 결제 모듈 개발 문서

## 1. 개요

RezlyX 결제 모듈은 **플러그인 기반 아키텍처**로 다양한 PG사를 유연하게 지원한다.

### 핵심 원칙
- PG사 교체 시 코드 변경 최소화 (인터페이스 기반)
- 결제/환불/적립금을 분리된 서비스로 관리
- POS(현장) + 온라인 결제 통합

### 참조 문서
| 문서 | 내용 |
|------|------|
| `Architecture_CommonLibrary.md` | 모듈 아키텍처, 인터페이스, 플러그인 구조 |
| `FSD_RezlyX.md` | F-PAY-001~003 기능 스펙, BR-030~035 비즈니스 규칙 |
| `POS_SYSTEM.md` | POS 결제 모달, 현장 결제 API |
| `RESERVATION_SYSTEM.md` | 예약 모델 결제 상태/메서드 |
| `POINT_SYSTEM.md` | 적립금 시스템 |

---

## 2. 아키텍처

```
Modules/Payment/
├── Contracts/
│   ├── PaymentGatewayInterface.php  ← PG사 구현 규약
│   ├── RefundableInterface.php      ← 환불 구현 규약
│   └── WebhookHandlerInterface.php  ← 웹훅 처리
├── DTO/
│   ├── PaymentRequest.php
│   ├── PaymentResult.php
│   └── RefundResult.php
├── Services/
│   ├── PaymentService.php           ← 결제 처리
│   └── RefundService.php            ← 환불 처리
├── Events/
│   ├── PaymentCompleted.php
│   ├── PaymentFailed.php
│   └── RefundProcessed.php
└── PaymentManager.php               ← 게이트웨이 관리

Plugins/ (PG사별 플러그인)
├── payment-toss/     ← 토스페이먼츠 (한국)
├── payment-stripe/   ← Stripe (글로벌)
├── payment-payjp/    ← PAY.JP (일본)
└── payment-portone/  ← 포트원 (한국)
```

---

## 3. 핵심 인터페이스

### PaymentGatewayInterface

```php
interface PaymentGatewayInterface {
    public function getName(): string;                     // 'toss', 'stripe' 등
    public function getSupportedCountries(): array;        // ['KR', 'JP']
    public function prepare(PaymentRequest $req): array;   // 결제 준비 → client_key, order_id
    public function confirm(string $paymentKey, array $options = []): PaymentResult;  // 결제 승인
    public function cancel(string $transactionId, string $reason = ''): bool;         // 결제 취소
    public function getTransaction(string $transactionId): ?array;                    // 거래 조회
}
```

### RefundableInterface

```php
interface RefundableInterface {
    public function refund(string $transactionId, int $amount, string $reason = ''): RefundResult;
    public function partialRefund(string $transactionId, int $amount, string $reason = ''): RefundResult;
}
```

---

## 4. DB 스키마

### 4.1 payments (결제)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK | |
| uuid | CHAR(36) UK | |
| reservation_id | CHAR(36) | 연결 예약 |
| user_id | CHAR(36) | 결제자 |
| order_id | VARCHAR(64) UK | 자체 주문번호 |
| payment_key | VARCHAR(200) | PG 결제 키 |
| gateway | VARCHAR(50) | toss, stripe, portone, payjp |
| method | VARCHAR(50) | card, bank_transfer, virtual_account |
| method_detail | JSON | 카드사, 계좌번호 등 |
| amount | DECIMAL(12,2) | 결제 금액 |
| discount_amount | DECIMAL(12,2) | 할인 금액 |
| point_amount | DECIMAL(12,2) | 적립금 사용액 |
| status | ENUM | pending, ready, paid, cancelled, partial_cancelled, failed, refunded |
| paid_at | TIMESTAMP | 결제 완료 시간 |
| receipt_url | VARCHAR(500) | 영수증 URL |
| raw_response | JSON | PG 원본 응답 |

**상태 흐름:**
```
pending → ready → paid → [완료]
                    ↓
               cancelled / partial_cancelled / refunded
```

### 4.2 refunds (환불)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| id | INT PK | |
| payment_id | INT FK | 원 결제 |
| refund_key | VARCHAR(200) | PG 환불 키 |
| amount | DECIMAL(12,2) | 환불 금액 |
| reason | VARCHAR(500) | 환불 사유 |
| status | ENUM | pending, processing, completed, failed |
| refunded_at | TIMESTAMP | 완료 시간 |
| requested_by | INT | 요청자 |
| processed_by | INT | 처리자 (관리자) |

### 4.3 payment_settings (PG 설정)

| 컬럼 | 타입 | 설명 |
|------|------|------|
| gateway | VARCHAR(50) | PG사 식별자 |
| api_key | TEXT | 클라이언트 키 (암호화) |
| secret_key | TEXT | 시크릿 키 (암호화) |
| supported_methods | JSON | ["card", "bank_transfer"] |
| is_test_mode | TINYINT | 테스트 모드 |
| is_active | TINYINT | 활성 여부 |

### 4.4 기존 테이블 변경

`rzx_reservations`에 이미 존재하는 결제 관련 컬럼:
- `payment_status` (unpaid/partial/paid) — 014 마이그레이션
- `paid_amount` — 014 마이그레이션
- `bundle_id`, `bundle_price` — 024 마이그레이션

추가 필요:
- `payment_id` (FK → payments) — 결제 연결

---

## 5. 결제 흐름

### 5.1 온라인 결제 (고객)

```
① 예약 완료 → ② 결제 준비(prepare) → ③ PG 결제창 오픈
→ ④ 고객 결제 → ⑤ 결제 승인(confirm) → ⑥ 금액 검증(BR-030)
→ ⑦ 예약 confirmed(BR-031) → ⑧ 적립금 적립(BR-032)
```

### 5.2 POS 결제 (현장)

```
① POS 카드에서 [결제] 클릭 → ② 결제 모달 오픈
→ ③ 적립금 사용 입력 → ④ 결제 방법 선택 (카드/현금/계좌이체)
→ ⑤ POST /admin/reservations/{id}/payment
→ ⑥ 상태 업데이트 → ⑦ 적립금 처리
```

### 5.3 환불

```
① 취소 요청 → ② 취소 정책 확인(BR-033)
→ ③ 환불률 계산 → ④ PG 환불 요청
→ ⑤ 부분환불 지원(BR-034) → ⑥ 적립금 복원(BR-035)
```

---

## 6. 비즈니스 규칙

| 규칙 | 내용 |
|------|------|
| **BR-030** | 금액 불일치 시 PG 거래 취소 후 실패 처리 |
| **BR-031** | 결제 완료 → 예약 상태 `confirmed` 변경 |
| **BR-032** | 결제 완료 → 적립금 적립 (기본률 + 등급 보너스) |
| **BR-033** | 취소 정책별 환불률: 24h전 100%, 12~24h 50%, 12h내 0% |
| **BR-034** | 부분 환불 지원 |
| **BR-035** | 환불 시 사용 적립금 비례 복원 |

---

## 7. 적립금 연동

### 적립금 계산

```
적립액 = 결제금액 × (기본률 + 등급보너스) / 100

예: 결제 50,000원, 기본률 3%, 골드 보너스 +2%
→ 50,000 × 5% = 2,500원 적립
```

### 사용 조건

| 설정 | 기본값 | 설명 |
|------|--------|------|
| min_use_amount | ¥10,000 | 최소 결제 금액 |
| max_use_rate | 30% | 결제금액 대비 최대 사용 |
| min_use_point | ¥1,000 | 최소 사용 단위 |
| expire_months | 12 | 유효기간 (개월) |

### 환불 시 복원

```
복원액 = 사용 적립금 × 환불률 / 100
→ point_transactions type='refund'로 기록
→ user_points.balance 증가
```

---

## 8. PG사별 지원

| PG사 | 지역 | 결제수단 | 우선순위 |
|------|------|---------|---------|
| 토스페이먼츠 | 한국 | 카드, 계좌이체, 가상계좌, 간편결제 | 1순위 |
| 포트원 | 한국 | 다양한 결제수단 통합 | 2순위 |
| Stripe | 글로벌 | 카드, 국제 결제 | 글로벌용 |
| PAY.JP | 일본 | 카드, 콘비니 결제 | 일본용 |

---

## 9. API 엔드포인트

### 고객용

| 메서드 | 경로 | 설명 |
|--------|------|------|
| POST | `/payments/prepare` | 결제 준비 (order_id, client_key 발급) |
| POST | `/payments/confirm` | 결제 승인 (금액 검증 + PG 확인) |
| GET | `/payments/{id}` | 결제 상세 조회 |
| POST | `/payments/{id}/refund` | 환불 요청 |
| POST | `/webhooks/payment` | PG사 웹훅 수신 |

### 관리자용 (POS)

| 메서드 | 경로 | 설명 |
|--------|------|------|
| POST | `/admin/reservations/{id}/payment` | 현장 결제 처리 |
| GET | `/admin/reservations/user-points` | 회원 적립금 조회 |
| GET | `/admin/settings/payments` | 결제 설정 페이지 |
| POST | `/admin/members/{id}/points` | 적립금 수동 지급/차감 |

---

## 10. 관리자 설정 항목

### 결제 설정 (`/admin/settings/payments`)

- PG사 선택 (라디오)
- API 키 / 시크릿 키 (암호화 저장)
- 테스트 모드 토글
- 결제 수단 활성화 (체크박스)
- Webhook URL 표시

### 적립금 설정 (`/admin/settings/points`)

- 기본 적립률 (%)
- 적립 시점 (결제 완료 / 예약 완료)
- 유효기간 (개월)
- 최소 결제금액, 최대 사용비율, 최소 사용단위
- 이벤트 보너스 (회원가입, 리뷰, 추천, 생일)

### 환불 정책 (`/admin/settings/refund-policy`)

- 시간대별 환불률 (편집 가능)
- 노쇼 블랙리스트 자동 등록
- 취소 시 알림 발송
- 적립금 자동 복원

---

## 11. 개발 단계

### Phase 1: 결제 코어 (1주)
- [ ] DB 마이그레이션 (payments, refunds, payment_settings)
- [ ] PaymentGatewayInterface, PaymentManager
- [ ] PaymentService, RefundService
- [ ] DTO 클래스 (PaymentRequest, PaymentResult, RefundResult)

### Phase 2: PG사 연동 (2주)
- [ ] 토스페이먼츠 플러그인 (1순위)
- [ ] Webhook 핸들러
- [ ] 결제 준비/승인/취소 API
- [ ] 테스트 모드 연동

### Phase 3: 적립금 고도화 (1주)
- [ ] 등급별 차등 적립
- [ ] FIFO 만료 처리
- [ ] 환불 시 복원 로직
- [ ] 포인트 환전 (선택)

### Phase 4: 환불 기능 (1주)
- [ ] 취소 정책 엔진
- [ ] 부분 환불
- [ ] 적립금 복원
- [ ] 환불 처리 기간 안내

### Phase 5: 관리자 UI (1주)
- [ ] 결제 설정 페이지
- [ ] 적립금 정책 페이지
- [ ] 환불 관리 페이지

### Phase 6: POS 연동 (1주)
- [ ] POS 결제 모달 고도화
- [ ] 현장 결제 + 온라인 결제 통합
- [ ] 적립금 UI 연동

---

## 12. 현재 상태

### 이미 구현됨
- `rzx_reservations.payment_status` (unpaid/partial/paid)
- `rzx_reservations.paid_amount`
- POS 결제 모달 (현장 카드/현금/계좌이체)
- 적립금 시스템 (point/balance/total_accumulated)
- `rzx_point_transactions` 테이블
- POS API: `POST /admin/reservations/{id}/payment`

### 미구현
- 온라인 PG 결제 (토스/Stripe 등)
- payments 테이블 (결제 이력 독립 관리)
- refunds 테이블 (환불 이력)
- Webhook 수신
- 취소 정책 엔진
- 등급별 차등 적립

---

*최종 수정: 2026-03-29*
