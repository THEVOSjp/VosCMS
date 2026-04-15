# VosCMS 결제 시스템

## 개요

서비스 신청 → 결제 → 구독 관리 → 자동 갱신의 전체 흐름을 구현.
기존 예약 시스템용 결제 인프라(PaymentManager, Gateway)를 재활용.

## 아키텍처

```
[프론트엔드]                    [백엔드]                      [PG사]
order.js                       api/service-order.php          PAY.JP / Stripe
  ├─ 주문 요약 계산               ├─ 금액 재계산 (서버)
  ├─ payjp.js 토큰 생성           ├─ rzx_orders INSERT
  ├─ submitOrder()                ├─ PaymentManager.gateway()
  │   └─ POST /api/service-order  │   └─ PayjpGateway.confirm()  → Charge API
  └─ 완료 페이지 이동             ├─ rzx_payments INSERT
                                  ├─ rzx_subscriptions INSERT
                                  └─ Customer 생성 (자동갱신용)
```

## DB 테이블

### rzx_orders (주문)
| 컬럼 | 설명 |
|------|------|
| uuid, order_number | 고유 식별자 |
| user_id | 주문자 |
| status | pending → paid → active → expired/cancelled |
| items | JSON — 주문 항목 상세 |
| subtotal, tax, total | 금액 (서버 계산) |
| contract_months | 계약 기간 |
| started_at, expires_at | 서비스 기간 |
| payment_id, payment_method, payment_gateway | 결제 연결 |
| domain, hosting_plan | 서비스 정보 |
| applicant_* | 신청자 정보 |

### rzx_subscriptions (구독)
| 컬럼 | 설명 |
|------|------|
| order_id, user_id | 주문/사용자 연결 |
| type | hosting, domain, maintenance, mail, support |
| unit_price, billing_amount | 단가, 갱신 금액 |
| billing_months | 갱신 주기 (개월) |
| auto_renew | 자동연장 ON/OFF (기본 ON) |
| payment_customer_id | PG Customer ID (카드 저장) |
| status | active, expired, cancelled, suspended |
| retry_count | 결제 재시도 횟수 (최대 3) |

### rzx_order_logs (주문 로그)
| 컬럼 | 설명 |
|------|------|
| order_id, action | 이력 (created, paid, renewed, failed, suspended) |
| actor_type | system, user, admin, cron |

## 결제 흐름

### 카드 결제
```
1. 사용자: 서비스 선택 → 결제하기 클릭
2. 프론트: payjp.js createToken() → 카드 토큰 생성
3. 프론트: POST /api/service-order.php { payment_token, items, ... }
4. 서버: 금액 재계산 (프론트 값 불신)
5. 서버: rzx_orders INSERT (status: pending)
6. 서버: PayjpGateway.confirm(token|amount|currency|orderId)
7. 성공:
   - rzx_payments INSERT (status: paid)
   - rzx_orders UPDATE (status: paid, payment_id)
   - PAY.JP Customer 생성 (카드 저장)
   - rzx_subscriptions INSERT (서비스별)
   - 응답: { success: true, order_number }
8. 프론트: /service/order/complete?order=SVC... 이동
```

### 계좌이체
```
1. 서버: rzx_orders INSERT (status: pending)
2. TODO: 입금 안내 메일 발송
3. 관리자: 입금 확인 후 수동 상태 변경
```

## 구독 자동 갱신

**Cron**: `scripts/cron-subscription-renew.php` (매일 09:00 JST)

```
1. 만기 7일 전 (auto_renew=1):
   → 안내 메일 발송 (TODO)
   → renew_notified_at 업데이트

2. 만기일 당일 (auto_renew=1, payment_customer_id 있음):
   → PayjpGateway.chargeCustomer(customerId, amount)
   → 성공: expires_at 연장, rzx_payments 기록
   → 실패: retry_count++

3. 재시도 3회 실패:
   → status = 'suspended'
   → 서비스 정지 안내 메일 (TODO)

4. auto_renew=0 + 만기 경과:
   → status = 'expired'
```

## 마이페이지 서비스 관리

**URL**: `/mypage/services`

- 구독 서비스 목록 (호스팅/도메인/유지보수/메일)
- 서비스별 자동연장 토글 (AJAX)
- 만기 7일 전 상단 알림 배너
- 상태 뱃지: 활성/만료/해지/정지

## PG사 모듈

### 지원 PG사
| PG사 | 파일 | 상태 |
|------|------|------|
| PAY.JP | `Gateways/PayjpGateway.php` | ✅ 완료 (Charge + Customer) |
| Stripe | `Gateways/StripeGateway.php` | ✅ 완료 (Checkout Session) |
| Toss Payments | — | 미구현 |
| PortOne | — | 미구현 |

### PaymentManager 설정 로드
```
payment_config (JSON) → rzx_settings
├── gateway: "payjp"
├── enabled: "1"
└── gateways:
    └── payjp:
        ├── public_key: "pk_test_..."
        ├── secret_key: "sk_test_..."
        └── test_mode: "1"
```

### PAY.JP Customer API (구독용)
```php
$gateway->createCustomer($token, $email);   // Customer 생성
$gateway->chargeCustomer($custId, $amount); // 저장 카드로 결제
$gateway->deleteCustomer($custId);          // Customer 삭제
```

## 주요 파일

| 파일 | 역할 |
|------|------|
| `database/migrations/migrations/030_create_orders_tables.sql` | DB 스키마 |
| `api/service-order.php` | 주문 제출 + 결제 처리 API |
| `rzxlib/Modules/Payment/PaymentManager.php` | PG사 관리 |
| `rzxlib/Modules/Payment/Gateways/PayjpGateway.php` | PAY.JP 구현 |
| `rzxlib/Modules/Payment/Services/PaymentService.php` | 결제 서비스 |
| `scripts/cron-subscription-renew.php` | 구독 자동 갱신 |
| `resources/views/system/service/order.js` | 프론트 결제 로직 |
| `resources/views/system/service/_payment.php` | 결제 폼 (PG별 동적) |
| `resources/views/system/service/complete.php` | 결제 완료 페이지 |
| `resources/views/customer/mypage/services.php` | 마이페이지 서비스 관리 |

## Cron 등록

```
# 도메인 가격 크롤링 (매일 자정)
0 0 * * * /usr/bin/php /var/www/voscms/scripts/cron-daily-prices.php

# 구독 자동 갱신 (매일 오전 9시)
0 9 * * * /usr/bin/php /var/www/voscms/scripts/cron-subscription-renew.php
```

## 테스트

- PAY.JP 테스트 카드: `4242 4242 4242 4242` (유효기간: 미래, CVC: 아무 3자리)
- 관리자 설정 > 온라인 결제 > 테스트 모드 ON
- 결제 후 DB 확인: `SELECT * FROM rzx_orders ORDER BY id DESC LIMIT 1`

## TODO

- [ ] 이메일 알림 연동 (메일 발송 헬퍼)
- [ ] 관리자 주문 관리 화면
- [ ] 관리자 입금 확인 (계좌이체) 처리
- [ ] Toss Payments / PortOne 게이트웨이 구현
- [ ] 3D Secure 강제 적용 옵션
- [ ] 영수증/청구서 PDF 생성
