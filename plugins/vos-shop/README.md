# vos-shop

VosCMS 쇼핑몰 플러그인 (작업 예정).

## 상태
**draft** — 골격만 존재. 구현 시작 전.

## 목적
일반 온라인 쇼핑몰 — 상품, 카테고리, 장바구니, 주문, 결제, 배송, 회원 등급, 쿠폰.

`vos-salon` (살롱 비즈니스 특화 사업장 등록) 과 분리됨.

## 향후 추가 예정
- `migrations/` — 상품/카테고리/주문/배송 테이블
- `views/admin/` — 상품 관리, 주문 관리, 카테고리, 쿠폰
- `views/customer/` — 상품 목록/상세, 장바구니, 주문/결제, 마이페이지 주문 내역
- `src/` — OrderService, CartService, PaymentService
- `hooks/` — 결제 webhook 등
- `lang/` — 13개국어
- 결제 게이트웨이: PAY.JP, Stripe, KakaoPay, etc.

## plugin.json
- `id`: `vos-shop`
- `status`: `draft`
- 메뉴/라우트/마이그레이션은 비어있음 (활성화해도 동작 없음)
