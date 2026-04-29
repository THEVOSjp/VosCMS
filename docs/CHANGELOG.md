# Changelog

**VosCMS** 프로젝트 변경 이력입니다.

---

## [VosCMS 2.4.2] - 2026-04-29 — 호스팅 자동화 결제 hook + 활동 로그 i18n

### Added — Phase 4: 결제 완료 hook → HostingProvisioner 자동 호출

[service-order.php](../plugins/vos-hosting/api/service-order.php) 의 결제 완료 직후 호출:

- 신규 함수 `_autoProvisionHosting($pdo, $prefix, $orderId, $orderNumber)` — 결제 transaction 외부 실행 (DB 무결성 격리, 실패해도 주문 자체 영향 없음)
- `domain_option = 'free'` 만 즉시 셋업 (Cloudflare 서브도메인 — DNS 자동 등록 끝났으므로 바로 가능)
- `new` (도메인 구매 후 NS 변경 대기) / `existing` (보유 도메인 NS 변경 대기) 은 **deferred** — 관리자가 도메인 활성화 시점에 별도 트리거
- 'install' addon 의 `install_info` 있으면 voscms 파일 deploy + .env 자동 생성 (install-core.php 사용자 마법사 진행 시 DB 정보 미리 주입됨)
- 호스팅 subscription metadata.server 에 ftp/db/env 정보 자동 저장 → 마이페이지 호스팅 탭에서 자동 표시 (UI 변경 불필요)
- order_logs 액션: `hosting_provisioned` / `hosting_provision_skipped` / `hosting_provision_deferred` / `hosting_provision_failed`

호출 순서: 결제 commit → `_autoProvisionHosting()` → `_autoProvisionMailDomain()`

### Added — 활동 로그 i18n 23개 액션 매핑

관리자 서비스 주문 상세 페이지 ([detail.php:498-528](../plugins/vos-hosting/views/admin/service-orders/detail.php)) 의 `$actionLabels` 가 8개 → 31개로 확장. 활동 로그에서 코드(`mail_account_added` 등) 그대로 보이던 문제 수정.

매핑 추가된 액션:
- 메일: `mail_account_added/deleted`, `mail_address_changed`, `mail_provisioned/_skipped/_failed`, `mail_domain_migrated/activated`, `bizmail_upgrade_request`
- 호스팅: `hosting_provisioned`, `hosting_provision_skipped/deferred/failed`, `hosting_deprovisioned`
- 부가서비스: `admin_add_storage_addon`, `admin_delete_addon`, `storage_addon_paid/requested`
- 시스템: `customer_notified`, `setup_email_sent`, `server_info_updated`, `bulk_extended`, `manual_capacity_period_fix`

각 액션별 색상 의미: green(완료/긍정), amber(보류/경고), red(실패/삭제), blue(정보/알림), violet(관리자/특수), zinc(중립).

i18n 키 23개 — services.admin_orders.act_* — ko/ja/en 3개 언어 추가 (운영자 페이지). 다른 10개 언어는 fallback (영어).

### Changed — install-core.php headless 자동화 분리

호스팅 자동 프로비저닝 시 voscms 자동 설치는 파일 deploy + `.env` 자동 생성까지만 수행. install-core.php 의 step-by-step 세션 흐름 (step 2~5) 자동화는 voscms 의 install 파이프라인 자체를 재설계해야 하는 큰 작업이라 Phase 5 별도 진행. 사용자가 결제 완료 후 `https://<도메인>/install.php` 접근 → 마법사 진행 (DB 정보는 .env 에 미리 주입됨).

`installVoscms()` 반환값 변경 — `install_url` + `note` 안내 메시지.

---

## [VosCMS 2.4.1] - 2026-04-29 — 호스팅 가상화 자동 프로비저닝 (Phase 1~3)

호스팅 신청 → 결제 완료 → 사이트 / SSL / DB / VosCMS 까지 자동 셋업되는 인프라 구축. 1.9TB SSD 통합 (`/var/www`), Linux 사용자 격리, PHP-FPM pool 격리, Cloudflare DNS-01 SSL 자동 발급, MySQL DB 격리, VosCMS 파일 deploy + .env 자동 생성. CLI 도구 + 트랜잭션 롤백 완비. Phase 4 (결제 hook 연동) 진행 예정.

### Added — Phase 1: 기본 셋업

[`HostingProvisioner.php`](../rzxlib/Core/Hosting/HostingProvisioner.php) — 호스팅 신청 시 자동 셋업하는 메인 클래스. 9 단계 트랜잭션 (실패 시 역순 롤백):

- Linux 사용자 생성 (`vos_<order_number>`, 32자 한계 내, 대소문자 보존)
- 디렉토리 구조: `/var/www/customers/<order>/{public_html,logs,tmp}` (chmod 750/2775/750/1770)
- 디스크 쿼터 (`setquota -u <user> <soft> <hard>`, hard = soft × 1.1)
- 디렉토리 충돌 검증: 기존 nginx vhost / `reserved_subdomains` 와 충돌 시 거부
- 1.9TB SSD `/var/www` 마운트 (ext4 + noatime + usrquota,grpquota)
- 중앙 sudoers (`/etc/sudoers.d/voscms-hosting-provisioner`) — www-data 가 useradd/setquota/nginx 등 root 명령 NOPASSWD 실행
- 명령별 인자 패턴 제한 (`vos_*`, `/var/www/customers/*`, `*.conf`) — visudo 검증 통과

CLI 도구 [`scripts/provision-hosting.php`](../scripts/provision-hosting.php):
```bash
php provision-hosting.php SVC260428AE7476 hotel.com 1GB
php provision-hosting.php --order SVC260428AE7476           # DB 자동 조회
php provision-hosting.php --deprovision SVC260428AE7476 hotel.com
php provision-hosting.php --install-voscms <order> <domain> <capacity>
```

템플릿 [`config/templates/`](../config/templates/):
- `nginx-vhost.conf.tpl` — HTTP + ACME challenge 위치 + per-user FPM socket
- `welcome.html.tpl` — 활성화 환영 페이지

### Added — Phase 2: PHP-FPM 격리 + Cloudflare DNS-01 SSL

각 호스팅이 자기 사용자 권한으로 PHP 실행 (사이트 간 침해 방지) + Cloudflare proxy 뒤에서도 SSL 자동 발급.

[`config/templates/php-fpm-pool.conf.tpl`](../config/templates/php-fpm-pool.conf.tpl):
- `user = vos_<order>` / `group = www-data` — uid 격리
- `listen = /var/run/php/<order>.sock` — 사용자별 socket
- `pm = ondemand` — 트래픽 적은 호스팅에 메모리 효율
- `php_admin_value[open_basedir]` — 사이트 디렉토리 + /tmp + /usr/share/php 만 접근
- `disable_functions` — exec, passthru, shell_exec, system, proc_open, popen, pcntl_exec
- `session.save_path` / `upload_tmp_dir` — 사용자 디렉토리로 격리
- env: HOSTING_ORDER / HOSTING_USER / HOSTING_DOMAIN

Let's Encrypt SSL — Cloudflare DNS-01 challenge:
- `python3-certbot-dns-cloudflare` 플러그인
- `/etc/letsencrypt/cloudflare/credentials.ini` (root 0600, .env 의 `CLOUDFLARE_API_TOKEN`)
- Cloudflare proxy 뒤 / 외부 도달성 무관하게 발급
- HTTPS server 블록 자동 추가 (TLS 1.2/1.3, 강한 cipher, HSTS, X-Frame-Options, X-Content-Type-Options)
- HTTP → HTTPS 301 자동 리다이렉트
- `certbot.timer` 매일 2회 자동 갱신 (이미 활성화)

### Added — Phase 3: MySQL DB 격리 + VosCMS 파일 deploy

호스팅 신청 시 자기 DB + 자기 user 격리.

- `hosting_admin` MySQL 계정 (1회 생성, ALL PRIVILEGES + GRANT OPTION) — DB 관리 전용
- DB 명/user = `vos_<order>` (Linux 사용자명과 동일)
- 비밀번호 32 hex 랜덤 (`bin2hex(random_bytes(16))`)
- `GRANT ALL PRIVILEGES ON vos_<order>.* TO vos_<order>@localhost` — 자기 DB 만 접근
- 격리 검증: 다른 호스팅 DB / voscms_prod 접근 시 Access denied 1044

VosCMS 자동 설치 (`installVoscms()` — `--install-voscms` 또는 addon `install_info` 있을 때):
- `voscms-dist/voscms-X.Y.Z.zip` 최신 패키지 압축 해제 → `public_html`
- `.env` 자동 생성: `APP_KEY=base64:<32바이트>` / DB 정보 / `HOSTING_ORDER` 주입
- 권한: vos_<order>:www-data, .env chmod 640
- `install-core.php` headless 호출 (현재 301 redirect 이슈, Phase 4 와 함께 마무리 예정)

### Operations

- 1.9TB SSD ext4 마운트 → `/var/www` (UUID=58914a8f-..., LABEL=hosting)
- usrquota,grpquota,noatime 마운트 옵션
- `/var/www.bak.nvme` (마이그레이션 백업) → 검증 후 삭제 → NVMe 가용 86GB→25GB
- /etc/sudoers.d/voscms-hosting-provisioner — visudo 검증 통과
- /etc/letsencrypt/cloudflare/credentials.ini — root 0600
- voscms-com .env 에 `HOSTING_DB_ADMIN_USER`, `HOSTING_DB_ADMIN_PASS` 추가

### Verified — End-to-end 테스트

`provision DEMO260429FULL demo07.21ces.com 1GB --install-voscms`:
- ✅ 사용자 `vos_DEMO260429FULL` (uid 격리, www-data 그룹)
- ✅ 디스크 쿼터 1GB soft / 1.05GB hard
- ✅ PHP-FPM pool 자기 socket
- ✅ nginx vhost (HTTP + HTTPS, HSTS)
- ✅ Let's Encrypt SSL (`/etc/letsencrypt/live/demo07.21ces.com/`)
- ✅ MySQL DB `vos_DEMO260429FULL` + user (격리 검증)
- ✅ VosCMS 2.3.9 파일 + .env 배포

`deprovision`: 모든 자원 깨끗 정리 (사용자/홈/DB/FPM pool/nginx/SSL).

---

## [VosCMS 2.4.0] - 2026-04-29 — 호스팅 자동화 인프라 + Calendar 청구 + 메일 시스템 완성

호스팅 사업화를 위해 메일 인프라(mx1.voscms.com), Cloudflare DNS 자동화, 도메인 가용성 검증, calendar 월말 마감 + 첫 달 일할 청구 시스템, 마이페이지 메일 관리(웹메일·주소 변경·중복 제거), 부가서비스(웹 용량 추가) 즉시 결제 + 카드 등록·만료 카드 교체 흐름을 구축했다. 호스팅 이용 규약 페이지(13개국어)와 관리자 서비스 주문 관리(4탭 항상 노출 + 부가서비스 부여/삭제)도 정비. 메뉴 ID 재사용 시 옛 다국어 번역이 새 메뉴를 가리던 INSERT IGNORE 버그도 수정했다.

### Added — Calendar 월말 마감 + 첫 달 일할 청구

업계 anniversary 방식(가입일 ~ 1년 뒤 같은 날) 대신 calendar 월말 마감(다음 달 1일 ~ 만료월 말일)으로 변경. 같은 달 가입자는 만료일이 동일해 cron 일괄 처리·회계 마감이 단순해진다. 일본 호스팅 표준(月末締め翌月1日請求) 패턴.

- DB: `rzx_subscriptions.billing_start DATETIME NULL` 컬럼 추가 (정상 청구 시작일)
- 헬퍼: [`_proratedFirstAmount`](../plugins/vos-hosting/api/service-order.php) (월단가÷30 × 잔여일수), [`_calendarBillingStart`](../plugins/vos-hosting/api/service-order.php) (다음달 1일), [`_calendarExpires`](../plugins/vos-hosting/api/service-order.php) (말일 23:59:59)
- 신청서: 호스팅 결제 항목에 `hosting_prorated` 자동 추가 (예: 4/2 가입 12개월 ¥1,000 → 첫 달 ¥967 + 정상 12개월 ¥12,000 = ¥12,967)
- 도메인은 anniversary 유지(1년 단위 등록), 호스팅·메일·메인터넌스·애드온은 calendar
- service-order.php 의 `-1 second` → `-1 day` 처리도 통일
- 부가서비스 (웹 용량 추가) 도 동일 일할 + 정상 N개월 합산. 호스팅 만료일과 자동 동기화

### Added — 메일 인프라 (mx1.voscms.com) + 자동 프로비저닝

ConoHa VPS (133.117.72.149) 에 Postfix + Dovecot + Rspamd + Roundcube + OpenDKIM + Let's Encrypt 셋업. mail-tester 10/10, FCRDNS 검증. 신청 시 도메인 자동 셋업.

- DNS 자동 등록 ([`CloudflareDns.php`](../rzxlib/Core/Dns/CloudflareDns.php)) — A / MX / SPF / DKIM / DMARC 5종 일괄
- DKIM 키 생성 — mx1 SSH `rspamadm dkim_keygen` (전용 ed25519 키 `storage/keys/mailsync_ed25519`, www-data 소유 0700)
- 3-mode 프로비저닝 ([`MailDomainProvisioner.php`](../rzxlib/Core/Mail/MailDomainProvisioner.php)): `free` (서브도메인 즉시) / `new` (도메인 구매 후 관리자 활성화) / `existing` (NS 변경 안내). `existing_pending` / `new_pending` / `active` mode 머신.
- 비밀번호: SHA512-CRYPT (`{SHA512-CRYPT}$6$...`) — Dovecot 호환 단방향 해시. 옛 `enc:` (encrypt 함수) 비번 마이그레이션 스크립트 [`scripts/migrate-mail-passwords.php`](../scripts/migrate-mail-passwords.php)
- mx1 동기화 cron ([`scripts/mail-sync-to-mx1.php`](../scripts/mail-sync-to-mx1.php)) — voscms_prod → mx1 mail_lookup virtual_users 5분 간격. 즉시 트리거(`triggerMailSyncToMx1()`)도 추가해 메일 액션 직후 백그라운드 sync
- cron 자동 등록: `/etc/cron.d/voscms-mail-sync`
- 알림: 관리자(프로비저닝 필요) / 고객(메일 사용 가능) HTML 메일 템플릿 ([`MailNotifier.php`](../rzxlib/Core/Mail/MailNotifier.php))

### Added — 마이페이지 메일 관리 강화

기본 메일 / 비즈니스 메일을 호스팅과 묶어 관리. 같은 주소가 mail subscription + hosting metadata 양쪽에 저장돼 UI 에 중복 표시되던 문제 수정 (single source of truth: mail subscription).

- **웹메일 버튼** — Roundcube `?_user=주소` GET 파라미터로 username prefill, `target="_blank"`. URL 우선순위: `metadata.mail_server.webmail_url` → `WEBMAIL_URL` env → 기본 `https://mail.voscms.com/`
- **이메일 변경 버튼** — 오타 수정용. 도메인은 고정, local part 만 변경. 같은 주문의 모든 mail+hosting metadata 동기화
- **dedup 로직** ([`mail.php`](../plugins/vos-hosting/views/customer/mypage/service-partials/mail.php)) — `_seenAddresses` 로 중복 주소 한 번만 표시, mail subscription 우선
- service-detail.php 가상 hosting → mail 변환은 진짜 mail subscription 이 없을 때만 동작 (옛 데이터 호환)
- service-order.php 가 신규 가입 시 hosting metadata.mail_accounts 저장 안 함 (mail subscription single source)

### Added — 부가서비스 (웹 용량 추가) 즉시 결제 플로우

마이페이지 부가서비스 탭에 호스팅 용량 추가 결제. PAY.JP customer 등록 + chargeCustomer.

- 모달: 용량 선택(1GB/3GB/5GB) + 카드 정보 입력 (payjp.js v2 Elements)
- 카드 등록 / 다른 카드로 결제 / 등록 카드로 돌아가기 토글
- 만료/거절 카드 자동 감지 (`expired_card`, `card_declined`, `invalid_*` 등 PAY.JP 에러 코드) → 카드 폼 자동 노출 + 안내 배너
- 카드 명의 입력 필드 추가 (`addon-card-holder`) — 토큰에는 안 넣고 customer metadata 로 저장 (PAY.JP v2 Elements 는 `name` 옵션 미지원)
- Portal 패턴: `document.body.appendChild()` 로 transform 걸린 컨테이너 벗어나 viewport 전체 덮음 (z-[9999])
- 신규 customer 생성 후 charge 실패 시 자동 환불 + customer 삭제 (orphan 방지)
- 호스팅 metadata.extra_storage[] 누적, hosting 탭 용량 게이지 합산 표시

### Added — 호스팅 이용 규약 페이지 (`/hosting-terms`)

[StarServer 등 일본 호스팅사 표준 약관](https://www.star.ne.jp/rule/rule.php) 참고하여 14개 조항(정의·신청·요금·자동 갱신·해지·서비스·백업·금지·정지·보수·개인정보·면책·변경·관할) 구성. Calendar 청구 방식 + 첫 달 일할 + 해지 시 미사용 개월 환불 정책 명시.

- 시스템 페이지 등록: [`config/system-pages.php`](../config/system-pages.php) `hosting-terms` slug, 🖥️ 인디고
- 페이지 에디터 매핑: [`pages-document.php`](../resources/views/admin/site/pages-document.php) — `pageMetaMap['hosting-terms']`
- DB 시드: ja(메인) / ko / en 3개 언어 본문 (rzx_page_contents)
- i18n: `site.pages.hosting_terms` + `hosting_terms_edit`(editor_title/desc)

### Added — 관리자 서비스 주문 관리 강화

- **4개 탭 항상 노출** ([detail.php](../plugins/vos-hosting/views/admin/service-orders/detail.php)) — hosting/domain/mail/addon. 데이터 없으면 빈 상태 메시지(empty_*)
- **부가서비스 탭** — `+ 용량 추가` 버튼 (관리자 무료 부여 모달, 용량 선택 시 즉시 active addon subscription 생성, billing_amount=0, expires=호스팅 만료일) + 항목별 휴지통 삭제 버튼 (소프트 삭제 status=cancelled, hosting metadata.extra_storage[] 에서 제거)
- **목록 — 신청자 표시 개선** ([index.php](../plugins/vos-hosting/views/admin/service-orders/index.php)) — `supervisor`/`admin` role 인 시스템 관리자 주문은 user.name + 보라색 부제 `슈퍼관리자: webmaster@thevos.jp` 형태로 표시 (옛 암호화 형식 `applicant_name` 의 `FKzGx9SE7xG/2` 복호화 실패 우회)
- **신청서 작성 버튼** (`+ New Order`) — UI 만, 기능 추후
- i18n 13개국어: `role_supervisor`, `role_admin`, `btn_create_order`, `empty_*`, `btn_add_storage_addon`, `btn_delete_addon`, `modal_add_storage_*`, `confirm_*`, `alert_*` 등 20+ 키

### Added — 도메인 가용성 검증 API + 메뉴 ID 재사용 방지

- [`subdomain-check.php`](../plugins/vos-hosting/api/subdomain-check.php) — DB(`reserved_subdomains`) 우선 + Cloudflare API fallback. JS `checkSubdomain()` 이 서버 호출 후 결과 반영
- service-order.php 결제 직전 서버 측 재검증 — 클라이언트 우회 방지
- `reserved_subdomains` 자동 import — Cloudflare zone 의 기존 레코드 11개 (vos/bestatech/bt/dev/ec430/ec431/salon/ssh/test/thevos/ai.21ces.com)
- 메뉴 INSERT IGNORE 버그 수정 ([menus-api.php](../resources/views/admin/site/menus-api.php)):
  - `add_menu_item`: INSERT 직후 stale 번역 정리 + `INSERT ... ON DUPLICATE KEY UPDATE` 로 덮어쓰기
  - `update_menu_item`: title/url 변경 감지 시 옛 번역 13개 모두 삭제 + 시스템 페이지면 재등록
  - `rename_menu_item`: title 변경 시 옛 번역 정리
  - 옛 메뉴(id=38, "VosCMS 소개") 다국어 번역이 새 호스팅 이용 규약 메뉴를 가리던 회귀 수정

### Added — Phase 7-3 자동 셋업 인프라

서비스 신청 → 결제 완료 → 자동 메일 도메인 프로비저닝 → 고객 알림 흐름 구축.

- service-order.php 에 `_autoProvisionMailDomain()` 호출 — 결제 transaction 외부에서 실행 (DB 무결성 격리)
- DB: `rzx_reserved_subdomains` (zone, subdomain, record_type, reserved_by, reason) — 가용성 캐시
- DKIM 키 자동 생성 후 metadata.mail_provision 에 mode/origin/domain/zone/subdomain/provisioned_at 저장
- 프로비저닝 모드별 UI 분기 ([service-detail.php](../plugins/vos-hosting/views/customer/mypage/service-detail.php)): `pending` / `new_pending` / `existing_pending` 모드면 탭 숨기고 모니터링만 표시 ([_setup-monitor.php](../plugins/vos-hosting/views/customer/mypage/service-partials/_setup-monitor.php)), `active` 일 때만 탭 노출

### Fixed — `??` + foreach reference 버그

PHP 의 `$arr['key'] ?? []` 은 임시 값을 반환하므로 `&$ref` 가 원본 array 에 영향을 주지 않음. `change_mail_password` / `change_mail_address` 가 success 응답은 보냈지만 metadata UPDATE 가 무영향이라 비밀번호와 주소 변경이 모두 silent fail.

- 인덱스 기반 직접 갱신 패턴으로 변경: `foreach ($arr as $i => $item) { $arr[$i]['k'] = ... }`
- service-order.php 의 신규 메일 비밀번호 저장도 `encrypt()` (양방향) → `mail_password_hash()` (SHA512-CRYPT) 로 수정

### Fixed — UI 정리

- 결제 요약 녹색 형광색 → 선명한 색상: `text-green-400` → `text-green-600 dark:text-green-400` (7개 위치)
- subdomain 가용성 응답 메시지 i18n 키 누락 (`order.php` 의 PHP→JS i18n 매핑 + `:fqdn` 플레이스홀더 통일)
- 만료일 계산 정확화: `+N months -1 second` (같은 날짜) → `+N months -1 day` (정확히 1일 전) → calendar 방식으로 최종 통일

### Database

- `rzx_subscriptions.billing_start DATETIME NULL` 추가 (dev + prod)
- `rzx_reserved_subdomains` 테이블 (Cloudflare zone 충돌 캐시)
- `rzx_page_contents` 에 hosting-terms (ja/ko/en) 시드

### Operations

- `/etc/cron.d/voscms-mail-sync` 등록 — `*/5 * * * * www-data /usr/bin/php8.3 /var/www/voscms-com/scripts/mail-sync-to-mx1.php voscms_prod`
- voscms-com `.env` 에 `MAIL_SERVER_HOST` / `MAIL_SERVER_USER` / `MAIL_SERVER_MAILSYNC_PASS` 추가 (chmod 640 + chgrp www-data)
- `mailsync_ed25519` 전용 SSH 키 (mx1 root@ 인증)

---

## [VosCMS 2.3.9] - 2026-04-27 — 서비스 신청 다국어 + 3-영역 메뉴 시스템

호스팅 사업자용 서비스 신청 시스템을 13개국어로 완성하고 결제 직전 확인 모달과 설치 지원 서비스를 추가했다. 어드민/마이페이지 사이드바를 3-영역(top/main/bottom) 자동 구분 구조로 재설계해 코어 메뉴와 플러그인 메뉴가 시각적으로 분리되도록 했다. 라이선스/개발자 API는 본사 전용 플러그인으로 분리해 배포판 노출을 차단했다.

### Added — 서비스 신청 페이지 13개국어 (`/service/order`)

도메인·호스팅·부가서비스·신청자·결제·요약 6개 영역과 결제 완료 페이지의 모든 텍스트를 13개국어로 번역. JS 측 alert/모달 텍스트는 `var I18N = {...}` + `t(key, replace)` 헬퍼 패턴으로 PHP에서 주입.

- 위치: [plugins/vos-hosting/lang/{13 locales}/services.php](../plugins/vos-hosting/lang/) — `services.order.*` 200+ 키
- 마이페이지 서비스 상세: `services.detail.*` 70+ 키, [service-detail.php](../plugins/vos-hosting/views/customer/mypage/service-detail.php)
- 어드민 주문 관리: `services.admin_orders.*` 115+ 키, [admin/service-orders/](../plugins/vos-hosting/views/admin/service-orders/)
- DB 저장된 한국어 라벨(호스팅 플랜·메인터넌스·애드온)은 `_localizeLabel($sub)` 클로저로 settings ID 매핑 후 `db_trans()` 적용 → 마이페이지/어드민 양쪽 동일 처리

### Added — 결제 직전 확인 모달

결제 버튼 클릭 시 신청 내역(도메인/플랜/부가서비스/신청자/약관) 검증 후 요약 모달 출력 → 사용자 확인 → 결제 진행. 빈 서브도메인 등록 같은 실수 방지.

- [order.js](../plugins/vos-hosting/views/system/service/order.js) — `showOrderConfirmModal()`, `closeOrderConfirmModal()`, `confirmAndPay()`
- 결제 완료 후 모달 잔여 레이어 제거 강화 (DOM 삭제 + body/documentElement overflow 리셋)

### Added — 설치 지원 서비스 (관리자 정보 입력)

부가서비스에서 "설치 지원"(`_id='install'`) 체크 시 관리자 ID/이메일/비밀번호/사이트명 입력 폼 표시. 비밀번호는 마스킹 + 눈 아이콘 토글 + "설치 후 변경" 안내. 입력값은 subscription metadata 의 `install_info` 로 저장되어 마이페이지/어드민 양쪽에서 표시.

- 입력: [_addons.php](../plugins/vos-hosting/views/system/service/_addons.php)
- 표시: 마이페이지/어드민 partials/addon.php (파란 배경 박스 + 비밀번호 토글)

### Added — 어드민 메모 주문 단위 이동

부가서비스 탭에 묶여 있던 메모를 주문(rzx_orders.admin_notes) 단위로 변경. 부가서비스 유무와 관계없이 우측 활동 로그 위에 항상 7줄 textarea 로 표시. AJAX `update_order_memo` 액션 추가.

### Added — 3-영역 사이드바 메뉴 시스템

코어 메뉴와 플러그인 메뉴 사이 시각적 구분을 위해 `section` 필드 도입 (`top`/`main`/`bottom`, 기본 `main`). 영역 사이에 자동 구분선이 들어가며, 영역에 표시 가능한 메뉴가 없으면 구분선 포함 통째로 스킵.

**어드민 사이드바**:
- top: 대시보드 / 회원 관리 / 사이트 관리 / 자동 설치(`vos-autoinstall`)
- main: VosCMS 라이센스(`vos-license-manager`) / 호스팅 관리(`vos-hosting`)
- bottom: 플러그인 / 설정
- 변경: [config/admin-menu.php](../config/admin-menu.php), [admin-sidebar.php](../resources/views/admin/partials/admin-sidebar.php)

**마이페이지 사이드바**:
- top: 대시보드 / 프로필 / 메시지
- main: 서비스(`vos-hosting` 자동 추가) — 다른 플러그인이 추가하면 자동 누적
- bottom: 설정 / 비밀번호 / 회원탈퇴 / 로그아웃
- 변경: [config/mypage-menu.php](../config/mypage-menu.php), [mypage-sidebar.php](../resources/views/components/mypage-sidebar.php)

**플러그인 메뉴 등록**: `plugin.json` 의 `menus.admin` / `menus.mypage` 항목에 `section` + `position` 으로 영역 지정. `services` 메뉴는 코어 mypage-menu.php → `vos-hosting/plugin.json` 으로 이전 (자동 등록).

### Added — HQ 전용 API 플러그인 분리 (보안)

라이선스 검증 API와 개발자 포털 API가 코어에 있어 모든 voscms 사이트에 배포되면 외부 노출되는 문제 해결. 두 API를 신규 본사 전용 플러그인으로 분리.

- **`vos-license-server`** (`scope: hq_only`): 7개 라이선스 엔드포인트 이전 (`check`, `register`, `verify`, `stats`, `updates`, `register-plugin`, `_init`)
- **`vos-developer`** (`scope: hq_only`): 7개 개발자 포털 엔드포인트 이전 (`register`, `login`, `submit`, `my-items`, `earnings`, `update-version`, `_init`)
- **`api/_plugin_dispatch.php`** — 코어 wrapper 가 `rzx_plugins.is_active` 체크 후 dispatch, 비활성 시 404
- 코어 `/api/license/*.php`, `/api/developer/*.php` 는 wrapper 만 남김 (외부 호환 유지)
- `/api/notices.php` 에 `HQ_HOSTS` env 화이트리스트 추가 (본사 voscms.com 도메인에서만 응답)
- 빌드 스크립트(`build-voscms.sh`)는 `api/license`, `api/developer`, `api/notices.php`, `vos-license-manager` 를 이미 제외

### Fixed

- 호스팅 계약 만료일 +1일 버그 (`+12 months` → `+12 months -1 second`) — subscription/free/paid/bank 4곳 적용
- 비즈니스 메일 2개 이상 추가 안 되는 버그 (selector 가 X 버튼을 잡던 문제)
- 주문 요약에서 추가 용량 미취합 (storage select 의 name 속성 누락)
- 호스팅 플랜 라벨 다국어 미적용 — `service_hosting_plans._id` 와 `db_trans()` 매칭으로 해결

---

## [VosCMS 2.3.8] - 2026-04-25 — 게시판 다국어 완성 + FAQ/Q&A 스킨

이슈 게시판을 13개국어로 완성하면서 게시판 시스템 전반의 다국어 인프라를 정리했다. 게시판 메타·확장변수 정의·옵션 라벨까지 모두 자국어로 노출되며, 색상 배지는 원본 키 매핑을 유지해 시각적 일관성 확보. FAQ/Q&A 아코디언 스킨을 신설하고 댓글 트리·인라인 편집/답글까지 완비했다.

### Added — 게시판 메타 다국어 (`board.{id}.*`)

`_init.php` 가 `rzx_translations` 에서 `board.{id}.{title|description|header_content|footer_content|seo_keywords|seo_description}` 을 직접 조회 (현재 로케일 → en → 원본). list/read/write/settings 어느 페이지에서도 `$board[$field]` 가 자동 번역값으로 채워짐. 게시판 설정 페이지의 "← 돌아가기" 버튼도 같은 메커니즘으로 자동 번역.

이슈 게시판(id=6) 의 `title` + `header_content` 13개국어 시드 (안내문 HTML 구조 유지, 텍스트만 번역, 처리 단계 배지도 각 언어로). 시드 스크립트: [scripts/seed_issue_i18n.php](../scripts/seed_issue_i18n.php) + [scripts/seed_issue_i18n_data.php](../scripts/seed_issue_i18n_data.php).

### Added — 확장 변수 정의 다국어 (`board_ev.{boardId}.{varName}.*`)

`rzx_board_extra_vars` 의 정의(라벨·설명·선택지·기본값)를 13개국어로 번역. 4개 필드 × 13 = 52 row.

- `ExtraVarRenderer::renderAll($vars, $values, $mode, $boardId)` — boardId 인자 추가, `applyDefTranslations()` 가 한 쿼리로 모든 var의 번역을 일괄 로드.
- `ExtraVarRenderer::getOptionLabel($boardId, $varName, $value)` — 목록 뷰용 단일 옵션 번역 라벨 조회 (정적 캐시).
- `renderStatusBadge($value, $size, $displayLabel = null)` — 색상은 원본(ko) 매핑 유지, 텍스트만 번역 → 어느 언어에서 보든 색상 일관 (받음=장미색, 해결됨=에메랄드색).
- select/radio/checkbox: `value=원본 / label=번역` 패턴. 게시글 저장 시 항상 원본 값이 들어가 데이터 호환성 보장.
- 호출부 업데이트: `read.php`, `write.php`, `_list-row.php`, default 스킨의 `_list-card`/`_list-webzine`/`_list-gallery`.

이슈 게시판 status 확장변수 13개국어 시드: [scripts/seed_ev_status_i18n.php](../scripts/seed_ev_status_i18n.php).

### Added — 확장 변수 편집 모달 로케일 인식

`extra_var_get` API가 `db_trans()` 로 현재 로케일 번역값을 `localized` 객체로 함께 반환. JS 모달은 `localized.title || ev.title` 로 우선 표시 → 일본어 모드에서 모달 열면 "処理ステージ"가 자동 표시.

`extra_var_update` 는 로케일에 따라 분기:
- 소스 로케일(ko) → 소스 row 전체 갱신 (기존 동작)
- 비소스 로케일(ja/en/…) → 메타(`var_type`/권한/체크박스 3종)만 소스에 저장, 4개 번역 필드(`title`/`description`/`options`/`default_value`)는 `rzx_translations` 에 현재 로케일로 저장 → **소스 무결성 보장**

확장 변수 일람의 표시 이름도 `db_trans('board_ev.{boardId}.{varName}.title')` 적용.

### Added — 편집 권한 섹션 13개국어

확장 변수 편집 모달의 "편집 권한" 섹션(라벨·드롭다운 옵션·도움말)을 13개국어 lang 파일로 분리. 신규 키: `site.boards.ev_permission`, `ev_perm_all`, `ev_perm_member`, `ev_perm_admin`, `ev_permission_help`.

### Added — 옵션 입력 양식 정규화

확장 변수 편집 모달의 "선택 항목" 필드를 양방향 정규화:
- **모달 로드**: DB의 JSON 배열(`["a","b","c"]`)을 줄바꿈 형식으로 변환해 textarea 에 표시 (안내 문구와 일치)
- **저장 API**: `ev_normalize_options()` 헬퍼가 줄바꿈 또는 JSON 입력 모두 받아 일관되게 JSON 배열로 저장. 기존엔 줄바꿈 입력 시 NULL 로 저장되던 버그 수정.

### Added — FAQ/Q&A 아코디언 스킨

이슈/공지 외 새로운 콘텐츠 패턴을 위한 두 스킨 신설.

**FAQ** (`skins/board/faq/`) — 글 = 질문, 본문 = 답변. 검색창·카테고리 필터·페이지네이션 포함. 타이틀 영역 표시 토글, 타이틀/서브타이틀 다국어, 배경(이미지·동영상·없음) 지원, 오버레이 투명도 조절. v1.0.1.

**Q&A** (`skins/board/qna/`) — 글 = 질문(제목+본문), 댓글 = 답변. 미답변 배지, 첫 항목 자동 펼침, 트리 정렬(parent_id/depth) 댓글 표시. 답글의 답글은 들여쓰기 + "↳ 답글" 라벨로 구분 (최상위는 "A."). 인라인 편집/답글 폼 (작성자/관리자만 편집, perm_comment 권한자만 답글). v1.0.0.

두 스킨 공통: 관리자 설정 톱니 아이콘을 검색창 아래 우측에 배치 (제목 영역에서 분리). `boardSkinFile()` 의 `_list-full.php` 우선순위로 동작.

### Added — 댓글 수정 API

`api-comments.php` 에 `update` 액션 추가. `comment.user_id == 현재 사용자` 또는 관리자만 수정 가능. 글자 수 제한 검증 후 `content` + `updated_at` 갱신.

### Added — 푸터 카피라이트 `{year}` 플레이스홀더

레이아웃 설정의 `copyright` 필드 안에서 `{year}` 토큰을 렌더링 시점에 현재 연도로 자동 치환. DB에는 원본(`{year}` 포함)이 보존되어 매년 손댈 필요 없음. 적용: `default`/`minimal`/`modern`/`base-footer.php`. 4개 layout.json 의 카피라이트 description을 13개국어로 확장하면서 `{year}` 사용 안내도 자국어로 표시.

`default`/`minimal` 푸터에서 `$_lc['copyright']` 를 무시하던 버그도 함께 수정 — 이제 레이아웃 설정값이 실제로 렌더링된다.

### Added — VosCMS 소개 메뉴 13개국어

`menu_item.38.title` 13개국어 시드 (VosCMS 소개 / About VosCMS / VosCMSについて / Über VosCMS / Acerca de VosCMS / À propos de VosCMS / Tentang VosCMS / VosCMS-ийн тухай / О VosCMS / VosCMS Hakkında / Giới thiệu VosCMS / 关于 VosCMS / 關於 VosCMS).

### Added — 이슈 게시판 샘플 글 13개국어

처리 단계별 샘플 1개씩 + 기존 해결됨 1개 = 총 4개. 각 글의 title/content 13개국어 번역 (104 row).

| 단계 | 제목 (ko) | 상태 |
|---|---|---|
| 접수 | [버그] 모바일 햄버거 메뉴 X 버튼이 동작하지 않습니다 | 신규 |
| 확인 중 | [기능 요청] 댓글 작성 시 게시글 작성자에게 메일 알림 | 신규 |
| 진행 중 | [성능] 마켓플레이스 상세 페이지 첫 로딩 속도 개선 | 신규 |
| 해결됨 | 게시판에 첨부한 이미지를 본문에 삽입... | 기존 |

기존 post 34 (Tailwind 인라인 스타일 누적된 테스트글) 삭제.

### Fixed — `_list-full.php` 스킨 헬퍼 로딩

`customer/board/list.php` 가 `rzx_admin_icons` 헬퍼를 line 167에서 require 했는데, line 161에서 스킨 파일을 include 하면서 return 으로 빠져나갔기 때문에 FAQ/Q&A 스킨에서 `rzx_admin_icons()` 호출 시 500 에러 발생. require_once 를 스킨 include 위로 이동.

---

## [VosCMS 2.3.7] - 2026-04-25 — 자동설치(AutoInstall) UX 통합 개편

마켓플레이스 → **자동설치** 리네임 후속 작업. 상세 페이지·목록 페이지·결제·리뷰 흐름을 일관성 있게 다듬고, 마켓 API에서 로케일을 처리하도록 책임을 이관해 클라이언트 코드를 단순화했다.

### Changed — 자동설치 페이지 라우트/링크 정리

이전 이름 `/marketplace` 잔존 참조 제거. 검색 폼이 `/marketplace`로 GET 제출되어 대시보드로 리디렉트되던 버그(검색·정렬·필터 변경 시 페이지 이탈) 해결. `browse.php`, `item-detail.php`, `item-submit.php`, `purchases.php`, `assets/js/marketplace.js` 모두 `/autoinstall`로 통일.

### Added — 설치 마법사 보안 안내 강화

`install.php` Step 4에서 관리자 경로 입력란 아래에 황색 힌트 + URL 실시간 미리보기 + 펼침형 "왜 변경해야 하나요?" 안내 추가. 자동 해킹 시도(브루트포스), 취약점 스캔, 강한 비밀번호의 한계 등 일반인도 이해할 수 있는 언어로 3가지 이유 제시. 13개 언어 모두 적용.

### Added — 번들 플러그인 자동 활성화

`install.php` 설치 완료 단계에서 `vos-autoinstall` 플러그인을 자동 등록·활성화 (`PluginManager::install()` 직접 호출). 사용자가 별도 설치 없이 어드민 진입 즉시 마켓플레이스 사용 가능.

### Added — 마켓 API 로케일 처리

`market.21ces.com/api/market/catalog`, `/item`에 `?locale=xx` 파라미터 지원. `name`, `description`, `short_description`, `cat_name` 다국어 JSON을 서버에서 단일 문자열로 반환 (폴백: 요청 로케일 → en → 첫 값). VosCMS 측 `json_decode + locale 폴백` 중복 로직 모두 제거 (`browse.php`, `item-detail.php`, `_components/item-card.php`).

### Added — 캐시 TTL 환경설정 + 갱신 버튼

자동설치 마켓 API 응답 캐시(파일 기반)의 TTL을 PluginManager 설정으로 통합 관리. 하드코딩 30분 → 기본 5분, 1·5·10·30·60분 옵션. `https://vos.21ces.com/theadmin/settings/general` 마켓플레이스 캐시 섹션과 `/theadmin/autoinstall/settings` 양쪽에서 동일 설정을 읽고/쓰며 자동 동기화. 자동설치 페이지 우상단·일반 설정 페이지 양쪽에 즉시 캐시 갱신 버튼 추가. TTL 변경 시 기존 캐시 자동 무효화.

### Added — 메타 정보 테이블 (상세 페이지)

마켓플레이스 어드민과 동일한 자료 유형 / 간단한 소개 / 슬러그 / Product Key / 최소 VosCMS·PHP 버전 / 파트너 / 가격 / 라이선스 테이블을 헤더 직후에 표시. 좌:우 영역 비율 2:1 → 3:1로 변경해 콘텐츠 영역 확대.

### Added — 리뷰 작성 모달 + 마켓플레이스 저장

상세 페이지 리뷰 탭 우상단에 "리뷰 작성" 버튼. 별점·닉네임·본문 입력 모달 → VosCMS API → 마켓플레이스 `POST /api/market/item/review`로 중계. VosCMS 라이선스 키 + 도메인 자동 첨부, 동일 도메인 1회 작성 제한, 구매자는 `is_verified=1` 자동 설정. 리뷰는 마켓 DB(`rzx_mkt_reviews`)에 `status='pending'`으로 저장 후 운영자 승인 시 공개. `GET /api/market/item/reviews?slug=...` 신설로 승인된 리뷰만 조회. 도메인은 부분 마스킹(`mys***.com`) 표시.

### Added — 가격 필터 (전체/무료/유료)

기존 `무료만` 체크박스 → `전체 / 무료 / 유료` 3-way 토글로 확장. 마켓 catalog API에 `paid=1` 파라미터 추가 (`i.price>0`). 검색창에서 분리해 뷰 토글 영역 앞으로 이동, 정렬 select와 함께 헤더 도구열로 통합.

### Changed — 검색창 디자인 (밝은 녹색 강조)

검색 input 보더 `border-2 border-emerald-300`, 좌측 검색 아이콘에 녹색 배경 박스, 검색 버튼 `bg-emerald-500`. 키워드 입력만 남기고 정렬·필터 기능은 본문 헤더로 이동해 검색창 단순화.

### Changed — 상세 페이지 버튼 분기

| 상태 | 표시 |
| --- | --- |
| 무료 | 설치 + 다운로드 |
| 유료 (미구매) | 구매 (마켓플레이스 PAY.JP 결제 모달) |
| 유료 (구매 완료) | 설치 + 다운로드 |
| 이미 설치됨 | "설치됨" |

목록 페이지 4개 뷰(리스트/웹진/카드/그리드)도 동일 분기. 구매 슬러그 셋을 페이지 진입 시 한 번 조회해 사용. 결제는 마켓 `POST /api/market/item/purchase`에서 PAY.JP 결제 + 라이선스 발급 수행.

### Changed — UI 디테일

- 뷰 스타일 토글: 텍스트 라벨 제거 → 아이콘 only + `title` / `aria-label` (접근성 유지)
- 타입 배지: `bg-<color>-100` 밝은 배경 → `bg-zinc-900/85 backdrop-blur-sm` 검정 반투명 + 컬러 글씨 (이미지 위 가독성 확보)
- 스크린샷 섹션: 헤더 직후 → 탭 콘텐츠 아래로 재배치, 마켓처럼 `(개수)` 카운트 표시
- 상세 페이지 `dark:prose-invert` 작동 안 하던 문제 → CDN Tailwind에 typography 플러그인 추가
- 상세 페이지 설명 영역: 마켓 어드민과 동일한 방식으로 `<script>/<iframe>/<style>/onXX` 등 위험 요소만 제거하고 HTML 마크업은 그대로 렌더링

### Fixed — 다양한 잠재 버그

- **PAY.JP 카드 폼 입력 불가**: 모달 hidden 상태에서 미리 mount되어 iframe이 0px로 그려지던 문제. lazy 초기화 + `requestAnimationFrame` mount로 해결
- **위젯 thumbnail 누락**: 마켓 DB에는 28개 위젯 등록됐으나 `/var/www/market/widgets/`에는 무료 위젯 12개만 복사된 상태. 14개 누락분 동기화. catalog/item API에 `screenshots` 절대 URL 변환 + 비존재 파일 자동 필터 추가
- **번들 플러그인 미인증 경고**: `vos-autoinstall`이 `unauthorizedPlugins`로 잡히던 문제. `LicenseStatus.php`에 `BUNDLE_PLUGINS = ['vos-autoinstall', 'vos-marketplace']` 상수 추가, `active()`/`fromCache()`/`canUsePlugin()` 모두 필터링. 라이선스 서버 `verify.php`에도 동일 처리
- **위젯 중복 추가 가능성**: install.php 시딩이 `INSERT IGNORE`만 사용했지만 `rzx_page_widgets`에 UNIQUE 제약이 없어 중복 가능. 시딩 시 `DELETE FROM page_widgets WHERE page_slug='home'` 후 INSERT로 변경 (DB UNIQUE 제약은 게시판 위젯 같은 의도적 중복 케이스 때문에 추가하지 않음)

### Migration Notes

- `vos-autoinstall` 플러그인 메뉴/라우트/마이그레이션이 새 설치에서 자동 활성화됨 (기존 설치는 어드민에서 한번 활성화 필요)
- 마켓플레이스 API 캐시 기본 TTL 30분 → 5분 (가격·내용 변경 빠른 반영)
- `vos-autoinstall.cache_ttl` 플러그인 설정 신규

---

## [VosCMS 2.3.6] - 2026-04-21 — 마켓플레이스 API 라이브 연동

### Changed — 관리자 마켓플레이스 데이터 소스 전환

- `browse.php`: 로컬 DB 조회 → `market.21ces.com/api/market/catalog` 라이브 API 호출
- `item-detail.php`: 로컬 DB → `/api/market/item`, `/api/market/item/versions` API 호출
- `CatalogClient.php`: API 베이스 URL `/api/v1` → `/api/market` 변경, 응답 필드 `items[]` → `data[]` 정합

### Fixed — market.21ces.com 플러그인 API 라우트 미동작 버그

**문제:** `market.21ces.com/index.php` 레이아웃 적용 블록에 `$__noLayout = true` 케이스 처리 누락으로 플러그인 API 라우트(`api/market/*`) 전체가 HTTP 200 + 빈 응답 반환.

**수정:** `index.php` 말단에 `elseif ($__pageFile && $__noLayout) { include $__pageFile; }` 추가. `api/market/catalog`, `api/market/item`, `api/market/updates/check` 등 정상화.

### Added — 카탈로그 API 파라미터 확장 (market.21ces.com)

`plugins/vos-market/views/api/catalog.php`에 `sort`, `free`, `featured` 파라미터 추가, 응답 필드 확장 (`sale_price`, `rating_avg`, `is_featured` 등).

### Added — 클라이언트 사이드 API 캐싱

`mpApiFetch()` 헬퍼: 30분 TTL 파일 캐시로 마켓 서버 요청 최소화. API 응답 실패 시 오류 배너 표시.

---

## [VosCMS 2.3.5] - 2026-04-21 — 라이선스 자동 등록 로직 완성

### Fixed — LicenseClient::check() 자동 등록 버그 수정

**문제:** AdminRouter에서 `$licenseClient = new LicenseClient()` 인스턴스 생성 후 자동 등록을 시도했으나, `$_ENV['LICENSE_KEY']` 갱신이 인스턴스 내부 `$this->licenseKey`에 반영되지 않아 동일 요청에서 `check()` 호출 시 여전히 `unregistered` 상태를 반환하는 버그.

### Changed — LicenseClient::check() 내부에 자동 등록 로직 통합

- `check()` 진입 시 `$this->licenseKey`가 비어있으면 즉시 `/api/license/register` 호출
- 성공 시 `$this->licenseKey` 갱신 + `$_ENV['LICENSE_KEY']` 갱신 + `.env` 파일 저장 후 verify 단계로 진행
- 실패 시(서버 응답 없음)에만 `unregistered` 반환
- `saveKeyToEnv()` 신규 메서드: 빈 `LICENSE_KEY=` 라인(install.php 실패 케이스)은 실제 키로 교체, 이미 유효한 키가 있으면 스킵

### 효과

- install.php 없이 수동 설치한 사용자 → 관리자 첫 접속 시 자동 라이선스 등록
- 이전 버전에서 업데이트한 사용자(LICENSE_KEY 없음) → 업데이트 후 관리자 접속 시 자동 등록
- 대시보드 "라이선스가 등록되지 않았습니다." 메시지는 라이선스 서버 자체가 응답 불가인 경우에만 표시

---

## [VosCMS 2.3.4] - 2026-04-20 — 콘텐츠 오염 정화 + 카드형 CSS 제거

### Fixed — 시드/DB 의 Tailwind 변수 오염 정화

`database/seeds/legal_pages.php` 시드 파일에 Tailwind CSS 변수(`--tw-scale-x`, `--tw-pan-x` 등 400+) 가 각 `<h1>/<h2>/<p>` 태그의 `style=""` 속성에 인라인으로 박혀 있던 문제. 설치마다 이 오염이 DB 로 전파되어 `terms`, `privacy` 페이지가 비대화 (특히 `privacy/ko` 는 128KB, 정상은 2KB).

- `database/seeds/legal_pages.php`: 442KB → 131KB (70% 감소, 구문 유효)
- 개발 DB 의 이미 오염된 26 행 정화: 1.04MB → 56KB (988KB 제거, -95%)
- `scripts/clean-inline-styles.php` 신규 — `style="... --tw-* ..."` 일괄 정화 도구 (재발시 재사용)

### Changed — `.board-content` 카드형 레이아웃 규칙 전면 제거

`.board-content` CSS 가 모든 문서 페이지에 "h2 를 카드 헤더로, h2+p 를 카드 본문으로" 강제 변환하는 규칙을 포함하고 있었음. 이로 인해 `Brand` 같이 Tailwind 로 직접 디자인된 페이지의 히어로 구조가 깨짐.

- 카드형 블록 103 줄 제거 (149 → 45 줄)
- 남은 규칙: 기본 타이포그래피 (p/h·/blockquote/code/ul/ol/table), OG 링크 카드, 다크모드 텍스트 오버라이드
- 결과: Brand 원본 디자인 복구 (왼쪽 파란 선 h2, 중앙정렬 히어로 등)
- 법적 문서(terms/privacy 등)는 일반 `<h1><h2><p>` 타이포그래피로 충분 — 카드형은 과한 장식이었음

---

## [VosCMS 2.3.3] - 2026-04-20 — 페이지 번역 구조 롤백 (단일 테이블 복귀)

### Changed — 2.3.1/2.3.2 의 2-테이블 설계 원복

**결정 배경**: 2.3.1 에서 `rzx_page_contents` 를 원본 ko + `rzx_translations` 비원본 구조로 분리하고 2.3.2 에서 에디터 저장 분기를 추가했으나, 설계 재검토 결과 **두 테이블의 실질 구조가 동일**하면서 (slug/key + locale + content) 다음 비용을 발생시키는 것으로 확인:

- ko 원문이 두 테이블에 **중복 저장** (db_trans 폴백 체인 미러링 용)
- 에디터 저장 로직이 ko vs non-ko **분기** 필요
- 페이지 관리 UI 에서 locale 상태 표시 시 두 테이블 JOIN
- 원래 명분(“AI 번역 파이프라인 통합 큐”)도 페이지 본문(10KB HTML) 과 UI 문자열(20바이트) 은 청크 분할·태그 보존 등 **다른 워크플로우**가 필요해 통합 가치 미미

**복귀 구조**: `rzx_page_contents` 단일 테이블, locale 당 1행 (원래 설계)

### Migration — DB 역마이그레이션

- `rzx_translations.page.{slug}.title/content` × 7 slug × 12 non-ko locale (168 행) → `rzx_page_contents` 복원
- `rzx_translations` 에서 `page.*` 키 전체 삭제 (234 행)
- 대상: Brand, terms, privacy, refund-policy, tokushoho, funds-settlement, data-policy
- 개발·프로덕션 DB 양쪽 적용

### Changed — 코드 원복

- `resources/views/admin/site/pages-edit-content.php` — ko/non-ko 분기 제거, 단순 UPSERT
- `resources/views/admin/site/pages-document.php` — 동일
- `resources/views/customer/page.php` — `db_trans('page.*.title/content')` 제거, `page_contents` 직접 읽기 (폴백 체인 `locale → en → ko` 유지)
- `resources/views/admin/site/pages.php` — 페이지 목록 제목도 `page_contents.title` 직접 참조

### Kept — rzx_translations 의 다른 용도

`rzx_translations` 자체는 **유지** — 다음 용도로 계속 사용:
- 게시판 (`board_post.{id}.title/content`)
- UI 문자열 (`menu.*`, 설정 라벨)
- 페이지 SEO 메타 (`page.{slug}.browser_title|meta_keywords|meta_description`) — 짧은 문자열이므로 여기 적합

**원칙**: 짧은 UI/메타 문자열 → `rzx_translations`, 긴 페이지 본문 → `rzx_page_contents` 로 데이터 특성에 맞게 저장소 분리.

### Docs

- `PAGE_ADMIN_GUIDE.md` 3.2 절 `rzx_translations — 페이지 번역본` 섹션 제거
- 2.3.1/2.3.2 항목은 이력 보존을 위해 CHANGELOG 에는 유지

---

## [VosCMS 2.3.2] - 2026-04-20 — 페이지 에디터 번역 DB 저장 로직 정합

### Changed — 페이지 에디터 저장·로드 경로 분기

2.3.1 에서 페이지 콘텐츠 저장 구조를 **원본 ko (`rzx_page_contents`) + 번역 DB (`rzx_translations`)** 로 마이그레이션한 뒤, 관리자 에디터가 여전히 모든 로케일을 `rzx_page_contents` 에만 INSERT/UPDATE 하고 있어 — 번역본 저장시 마이그레이션 형식과 어긋나 렌더 쪽 `db_trans()` 에서 조회 실패하는 문제 해결.

**분기 규칙** (원본 로케일 = `$_ENV['DEFAULT_LOCALE'] ?? 'ko'`):

| locale | 저장 경로 |
| --- | --- |
| `ko` (원본) | `rzx_page_contents` + `rzx_translations` 미러링 (`db_trans()` 폴백용) |
| `en/ja/zh_CN/...` | `rzx_translations` (`page.{slug}.title`, `page.{slug}.content`) 만 |

비원본 로케일 저장 시 `rzx_page_contents` 원본 행이 없으면 stub 행을 자동 생성 (메뉴·설정 연결 보존). `is_active` 는 원본 행에 통일 적용.

### Changed — 읽기(load_locale / savedContents) 경로

비원본 로케일 편집 화면을 열 때:
1. `rzx_translations` 에서 `page.{slug}.title/content` + `locale` 먼저 조회
2. 없으면 레거시 `rzx_page_contents` 행 폴백 (마이그레이션 누락 페이지 호환)

원본 로케일은 기존대로 `rzx_page_contents` 만.

### Changed — 삭제 / slug 이름변경 전파

- **페이지 삭제**: `rzx_translations WHERE lang_key LIKE 'page.{slug}.%'` 도 함께 삭제
- **slug 변경**: `rzx_translations.lang_key` 의 `page.{old_slug}.*` → `page.{new_slug}.*` 일괄 UPDATE

### Infrastructure

- `$sourceLocale` 변수를 세션 UI 로케일(`$config['locale']`)과 분리. 관리자가 영어 UI 로 로그인해도 콘텐츠 원본 언어는 `DEFAULT_LOCALE` 기준으로 일관 처리.

**수정 파일**:
- `resources/views/admin/site/pages-edit-content.php` (기본 페이지 설정 에디터)
- `resources/views/admin/site/pages-document.php` (terms, privacy, refund-policy 문서 에디터)

---

## [VosCMS 2.3.1] - 2026-04-20 — 페이지 번역 DB 마이그레이션 + UI 정리 + 로고 자산

### Changed — 페이지 콘텐츠 저장 구조 통일 (번역 DB 방식)

기존 `rzx_page_contents` 에 locale 당 풀 콘텐츠 행으로 저장하던 방식을 **원본 ko + 번역 DB (`rzx_translations`)** 방식으로 통일. 게시판(`board_post.*`) 과 동일한 저장 구조 정합성 확보.

**대상 7개 페이지**: Brand, terms, privacy, refund-policy, tokushoho, funds-settlement, data-policy

**Before**:
```
rzx_page_contents
├── Brand / ko  (풀 콘텐츠)
├── Brand / en  (풀 콘텐츠)
├── ... (13 locale 행)
```

**After**:
```
rzx_page_contents
└── Brand / ko  (원본 1행)

rzx_translations
├── page.Brand.title   × 13 locale (ko 포함)
└── page.Brand.content × 13 locale
```

**마이그레이션 결과**:
- `rzx_page_contents`: 7 slug × 12 non-ko 행 = **84행 삭제**
- `rzx_translations`: 7 slug × 13 locale × (title + content) = **182행 이전**
- 개발·프로덕션 DB 양쪽 적용, 렌더 검증 통과 (ko/en 등 locale 별 정상 표시)

### Changed — 페이지 관리 UI 정리

- **"새 페이지" 헤더 버튼 제거** — 동작하지 않던 placeholder 였음. 페이지는 메뉴 관리에서 생성되는 구조가 단일 진실 원천.
- **"삭제" 🗑 아이콘 + AJAX 핸들러 + `deleteUserPage()` JS 제거** — 메뉴 삭제 시 페이지도 함께 정리되므로 페이지 단독 삭제 경로 불필요. 잘못된 삭제로 깨진 메뉴 발생 방지.
- 유지: 설정 ⚙ · 편집 ✏ · 미리보기 👁 아이콘

### Fixed — 로고 업로드 + 레이아웃 동기화

- **`설정 > 사이트 > 로고 이미지` 업로드 실패**: `storage/logos/` 서브 디렉토리 미존재로 `move_uploaded_file()` 조용히 실패. 업로드 핸들러에 서브 디렉토리 자동 생성 + 개발·프로덕션 서버에 디렉토리 생성 (`www-data:www-data`, 775).
- **레이아웃 로고 우선순위 복원**: 잠시 "사이트 설정 우선" 으로 역전시켰다가 "레이아웃 디자인 특성별 로고" 요구에 맞춰 **레이아웃 > 사이트 fallback** 으로 복원. 레이아웃에 지정 없으면 사이트 로고 자동 사용.
- 대상: `skins/layouts/{default, minimal, modern, modern-copy-260413}/header.php` 및 footer.php

### Fixed — 중복 페이지 레코드 정리

- `bi-guide` / `logo-guide` slug 중복 2행 중 최초 생성본만 유지 (id=186, 187), 중복 id=193, 194 삭제
- 개발·프로덕션 양쪽 적용. 메뉴 API 의 중복 INSERT 버그는 v2.3.0 에서 이미 수정됨

### Added — 로고 브랜드 자산 (별도 파일, 비배포)

`/var/www/_upload/` 에 로고 자산 생성 — 운영 시 필요에 따라 업로드:

**아이콘 (마크만)**
- `voscms-logo-color-{256,512,1024}.png` · 컬러 (deep·ocean·sky·ocean + 중앙 deep)
- `voscms-logo-mono-{256,512,1024}.png` · sky 단색 + opacity 1/0.7/0.48/0.7 그라데이션

**가로 워드마크** (헤더용) — 820×256 비율
- `voscms-wordmark-{128h,256h,512h}.png` · 라이트 (Vos deep + CMS 회색)
- `voscms-wordmark-dark-{128h,256h,512h}.png` · 다크 (Vos sky + CMS slate-300)

**세로 워드마크** (앱아이콘·OG 이미지용) — 360×420 비율
- `voscms-stacked-light-{256h,512h,1024h}.png`
- `voscms-stacked-dark-{256h,512h,1024h}.png`

SVG 원본 전부 보존. `librsvg2-bin` 설치로 `rsvg-convert` CLI 제공. OKLCH 색상 미지원 이슈는 hex 변환본(`voscms-logo-mono-hex.svg`) 으로 해결.

### Infrastructure — 백업

마이그레이션 전 `mysqldump` 로 백업:
- `/home/thevos/backups/pre_migration_20260420_181923.sql.gz` (개발)
- `/home/thevos/backups/prod_pre_migration_20260420_182509.sql.gz` (프로덕션)

롤백 필요 시 두 덤프로 `rzx_page_contents` + `rzx_translations` 복원 가능.

---

## [VosCMS 2.3.0] - 2026-04-20 — 페이지 설정 탭 리팩토링 + Changelog 시스템

### Added — Changelog 시스템 (버전별 다국어 + AI 번역 대비)

- **DB 테이블** `rzx_changelog` 신규 — 버전 × locale 별 독립 레코드, `translation_source`(original/ai/manual) + `source_hash` 추적
- **파서** `RzxLib\Core\Changelog\ChangelogParser` — Keep a Changelog 표준 + 내부 섹션 화이트리스트 (Infrastructure/Internal/Refactor/Chore/Docs/Test 는 비공개)
- **임포터** `ChangelogImporter` — idempotent 병합, 수동 번역 보호, content_hash 로 변경만 감지
- **CLI 스크립트** `scripts/import-changelog.php` — `--file --locale --dry-run --silent` 옵션, SemVer pre-release(`2.1.1-hotfix`) 지원
- **번역 인터페이스** `RzxLib\Core\Translate\{TranslatorInterface, NullTranslator, TranslatorFactory}` — Gemma 4 (ai.21ces.com) GPU 준비 후 `config/translator.php` driver 변경만으로 활성화
- **시스템 페이지** `/changelog` 등록 (`config/system-pages.php`), `resources/views/system/changelog/index.php` 프론트 뷰
- **전용 스킨** `skins/page/changelog/skin.json` — 카드 스타일(filled/outlined/flat), 섹션별 색상, 버전 배지, 날짜 포맷, 상대시간 표시, 내부 섹션 노출 토글
- **편집 페이지** `/changelog/edit` 분리 — MD 업로드 프리뷰, 다국어 커버리지 매트릭스, 버전별 토글·삭제, AI 번역 버튼(준비중)
- **배포 자동화** `deploy-voscms.sh` 에 `1.5/5 Changelog import` 단계 추가 — ko + 12개 locale `.md` 파일 자동 감지·병합
- **번역 키** `site.pages.changelog` 13개 locale 추가

### Changed — 페이지 설정 탭 시스템 리팩토링

- **탭 순서 재배치**: `[기본정보] [권한] [추가설정] [스킨]` — 시스템 페이지는 뒤에 자체 탭 추가 가능
- **`settings_tabs` 스키마 도입** (`config/system-pages.php`) — 시스템 페이지가 최상위 탭 배열 선언, 각 항목 `key/label/icon/view` 로 동적 렌더. 기존 `settings_view` 하단 include 레거시 제거
- **원칙 확립**: **설정 = 모양 + 기능** / **편집 = 데이터** — 업로드·버전·번역 같은 데이터 관리 UI 는 `/changelog/edit` 로 분리
- **MERGE 저장 전략** — `save_settings` 핸들러가 기존 설정과 병합. 탭별로 나뉘어도 다른 탭 데이터가 지워지지 않음
- **`saveSettings()` JS** — DOM 에 존재하는 필드만 전송. 탭 분리 환경에서 일부 필드 미렌더 시 안전
- **레이아웃·스킨 카드 위치** — 기본정보 탭으로 원위치 (사용자 피드백 반영). 스킨 탭은 skin.json 변수만 전담
- **페이지 너비 중복 제거** — 기본정보 탭의 `page_width` 필드 삭제 (스킨의 `content_width` 로 통합)
- **service/order 내부 2차 탭 평탄화** — `config/service-settings-tabs.php` 의 `general/domain/hosting/addons` 를 `settings_tabs` 로 이전, 최상위 탭으로 승격

### Changed — 시스템 페이지 라우팅 확장

- **`/{slug}/settings`, `/{slug}/edit` 가 `config/system-pages.php` 도 인식** — 기존엔 `rzx_page_contents` DB 만 조회해 시스템 페이지가 404. 이제 DB → config 순서로 조회
- **`edit_view` 필드 신설** — 시스템 페이지 전용 편집 뷰 파일 경로 직접 지정. `/{slug}/edit` 클릭 시 해당 뷰 직접 include (관리자 리다이렉트 hop 제거)
- **pages-settings.php fallback** — DB 레코드 없는 시스템 페이지도 `$pageData` 를 config 에서 구성해 "페이지 관리 목록" 리다이렉트 방지
- **admin icon URL 통일** — `/{slug}/settings`, `/{slug}/edit` 프론트 표준 URL 사용

### Fixed — 메뉴 관리 중복 레코드 버그

- `menus-api.php` 외부 페이지(`external`) 타입 **INSERT 전 중복 체크 누락** 수정 — 메뉴 저장 2번 클릭 시 `rzx_page_contents` 중복 row 발생 문제
- 메뉴 삭제 시 **external 타입 + widget 의 page_contents** 정리 누락 수정 — 고아 레코드 방지

### Fixed — Changelog 페이지 스킨 동기화

- 제목 배경(`title_bg_type`, `title_bg_image`, `title_bg_video`, `title_bg_overlay`, `title_text_color`), `show_title`, `show_breadcrumb`, `custom_css`, `custom_header_html`, `custom_footer_html` — 모든 표준 페이지 스킨 변수가 공개 뷰에 반영되도록 수정
- `_page-title-bg.php` partial 이 `$_contentWidth` 변수 수용 — 제목 영역이 콘텐츠 너비를 따라감
- 관리자 아이콘을 제목과 분리 — 콘텐츠 상단 우측으로 독립 배치. `show_title=0` 이어도 항상 표시
- 제목 영역 위 20px 여백 추가 (배경 레이아웃 균형)

### Added — 페이지·번역 정리

- `site.pages.contact` 번역 키 13개 locale 추가 (ko/en/ja/zh_CN/zh_TW/de/es/fr/id/mn/ru/tr/vi) — 기존 누락 해결
- **페이지 관리 시스템 가이드** 문서 `docs/PAGE_ADMIN_GUIDE.md` 신규 — 탭 구조·스키마·라우팅·권한·확장 방법 정리

### Infrastructure — 배포 스크립트

- `deploy-voscms.sh` 에 `1.5/5  Changelog import` 스텝 — mysqldump 이전에 개발 DB 병합 → 프로덕션에 자연 전파
- 파일명 컨벤션 `CHANGELOG.md` (ko 기본) + `CHANGELOG.{locale}.md` (옵션) — locale 자동 감지 업로드 지원

### Refactor — 정리

- `index.php` 하드코딩 `/changelog` 라우트 제거 — 시스템 페이지 라우터로 이관
- `resources/views/customer/changelog.php` 제거 — 시스템 구조로 이전
- Phase 1 (현재) + Phase 2 (AI 번역 활성화) 2단계 로드맵 — Phase 2 는 `GemmaTranslator` 클래스 1개 추가 + config driver 변경만으로 전환 가능

---

## [VosCMS 2.2.2] - 2026-04-19 — stats 위젯 개선 + 관리자 상단 수정

### Added — stats 위젯 CRUD + 카운트업 애니메이션

- 아이템별 **숫자·접미사·라벨(다국어)·아이콘·색상** 필드
- 관리자 위젯 빌더에서 아이템 **추가/수정/삭제** 가능 (`stat_items` 타입 편집 UI 신규)
- 16종 아이콘 (users, chart, star, heart, check-circle, clock, calendar, globe, shield, lightning, trophy, cart, dollar, building, chat) + 10종 색상
- 뷰포트 진입 시 **카운트업 애니메이션** — IntersectionObserver + easeOutCubic 1.4s
- 숫자형만 카운트 대상 (`24/7` 같은 문자열은 그대로 표시), 콤마 서식·소수점 유지
- `widget.json` v1.1.0 → v1.2.0

### Fixed — 관리자 상단 프로필 표시

- `admin_name` 이 `enc:` 접두사로 저장되어 복호화 없이 그대로 노출되던 문제 수정
- 아바타 조회가 존재하지 않는 `staff` 테이블을 LEFT JOIN 하던 문제 — `admin_id = users.id` 통합 구조에 맞게 `rzx_users.profile_image` 로 직접 조회
- 상대 경로 아바타에 baseUrl prefix 자동 부여

### Infrastructure — 배포 스크립트 + 위젯 구조 원칙

- `deploy-voscms.sh` 권한 재설정 단계에 `storage/sessions` 를 `www-data` 소유로 고정하는 줄 추가 — 배포 후 "Session data file is not created by your uid" 경고로 로그인 불가 현상 방지
- **원칙 정립**: 모든 위젯의 렌더 로직은 `widgets/{slug}/render.php` 에 두고 `WidgetRenderer` 내장 메서드로 분산하지 않는다. `stats` 의 내장 메서드 제거.

---

## [VosCMS 2.2.1] - 2026-04-19 — URL 캡처 (썸네일 / 스크린샷)

### Added — 게시판 글 작성 폼 URL 캡처 버튼

- 모든 게시판의 `<input type="url">` (확장변수 `url` 타입 포함) 옆에 `[썸네일 가져오기] [스크린샷 가져오기]` 버튼 자동 삽입
- 썸네일: 1280×960 네이티브 렌더 → **800×600** 출력 (첫 화면 뷰포트)
- 스크린샷: 1280×full_page 네이티브 렌더 → **800×축소** 출력 (전체 스크롤 페이지, 실제 페이지 높이 사용)
- 단계별 프로그레스 바 UI — `서버 연결 → 페이지 로딩 → 화면 캡처 → 이미지 생성 → 파일 저장 → 응답 대기`
- 썸네일 캡처 자동 대표지정 — `board_files.is_primary=1` 로 표식되어 board-showcase 위젯이 우선 사용
- 새 라우트 `/board/api/url-capture?type={thumbnail|screenshot}&url=...` + `api-url-capture.php` 프록시
- `/board/api/files` 에 `set_primary` 액션 추가 — 기존 첨부 중 하나를 대표로 지정

### Changed — board-showcase 위젯 대표 이미지 우선

- 첨부 이미지 쿼리 `ORDER BY is_primary DESC, id ASC` — 캡처된 썸네일 우선 노출
- 게시글 작성 시 `primary_file_pos` 파라미터로 대표 파일 인덱스 서버 전달

### Infrastructure — Puppeteer 기반 캡처

- `api-21ces` 측 `bin/capture.js` 신설 — `puppeteer-core` 사용
- Chrome CLI `--screenshot` 의 onload 시점 캡처 한계 해소 — `networkIdle` + `<video>.readyState` 대기 후 캡처
- Chrome `--virtual-time-budget` / `--force-device-scale-factor` 제거 (rezlyx.com 등 hang 이슈)
- 스크린샷 서버 캐시 비활성 + `Cache-Control: no-store` 응답 헤더
- nginx `fastcgi_read_timeout 60s → 120s` (api.21ces.com), PHP `set_time_limit(120)`
- voscms 프록시 `CURLOPT_HTTP_VERSION=HTTP/1.1` 강제 (HTTP/2 stream not closed cleanly 회피)
- 응답 전송 안정화 — `file_get_contents → unlink → echo` 로 FastCGI 버퍼 cutoff 해결
- Chrome `HOME=$userDir` 강제 — www-data 의 `/var/www/.local` 권한 오류 (code=133) 해소

---

## [VosCMS 2.2.0] - 2026-04-18 — Marketplace 분리

### Changed — 마켓플레이스 아키텍처 (Breaking)

- **마켓플레이스 제품 분리**: 기존 `plugins/vos-marketplace/` 플러그인이 공유하던 기능을 별도 제품으로 분리 → **[github.com/THEVOSjp/Marketplace](https://github.com/THEVOSjp/Marketplace)** 신규 저장소, `market.21ces.com` 독립 배포
- 플러그인은 "고객 사이트 사용자측 UI" (탐색·구매·설치·라이선스) 만 유지, 데이터·판매자·정산은 마켓플레이스 서버에서 전담
- Core 측 API 연동 포인트 갱신

### Added — install.php 백업 안내 모달

- 언어 선택 후 모달 표시 — `.env` + DB + `storage/uploads/` 백업 필요성 + APP_KEY 유실 시 복호화 불가 경고
- 체크박스 + 확인 버튼 2단계 (무조건 설치 진행 금지)
- 13개 언어 전체 번역 추가 (`backup_title/body/item_env/db/uploads/warn/check/confirm/cancel`)

### Added — 위젯 마켓플레이스 등록 스크립트

- `scripts/register-widgets-to-marketplace.php` — 파일 기반 위젯 28개를 `rzx_mp_items` 로 일괄 등록

### Changed

- `plugins/vos-marketplace/src/CatalogClient.php` — 기본 API URL `https://market.21ces.com/api/v1`, `fetchItem()` 경로 변경, `checkUpdates()` 쿼리 포맷 변경
- `plugins/vos-marketplace/plugin.json` — `marketplace_api_url` 기본값 갱신
- `rzxlib/Core/Modules/WidgetLoader.php` — 썸네일 URL 에 `?v={mtime}` 캐시버스터 추가 (Cloudflare immutable 캐시 대응)

### Fixed

- `widgets/feature-tour/widget.json` — 필드 정리
- 일부 위젯 썸네일 갱신 (staff, testimonials, feature-tour, shop-map, shop-ranking, stylebook)

### Infrastructure

- Nginx `default_server` 444 반환 설정 — 미등록 호스트 차단 (와일드카드 터널 대응)
- Cloudflare Tunnel 라우팅: `*.21ces.com` 와일드카드 공개 호스트네임 추가, DNS `CNAME * → 21ces.com` 생성
- 21ces.com 루트 도메인 A 레코드는 NAS 로 유지 (thevos.jp 리뉴얼 완료 후 처리 예정)

---

## [VosCMS 2.1.5] - 2026-04-17

### Added — 위젯 신규
- **contact-info** (v0.1.0) — 연락처 카드 (주소/전화/이메일/영업시간 + 이미지 + 소형 지도)
- **location-map** (v0.1.0) — 가로형 지도 (Google Maps + 하단 3열 정보, 너비 조절)

### Added — 개발자 포털
- **아이템 제출 페이지 전면 리팩토링** — 3탭 (기본 정보/릴리즈/판매)
- 라이선스(GPL/LGPL/BSD/MIT/CC/PD/상용), 저장소 URL, 데모 URL, 의존 플러그인, 배너 이미지
- 릴리즈 히스토리, 판매 정보 (공개/판매 토글, 할인가/종료일), UX 개선

### Added — 다국어
- Developer 메뉴(ID:20) 13개 언어, Hero CTA description/typing_words 12개 언어 보강

### Changed
- 위젯 이름 구분 명확화 (지도/연락처 카드), page.php fullWidthTypes 추가
- contact-form 카테고리 → 라벨만 (코드 제거, 위젯 설정 편집 가능)

### Fixed
- board-contents/board-showcase: file_type → mime_type
- boards-api: is_shown_in_list 누락 + options JSON 제약
- contact-form POST: ob 버퍼 전체 비우기
- Hero CTA: typing_words/description \n 이중 이스케이프

### DB Changes
- rzx_mp_items: license, repo_url, demo_url 추가
- rzx_board_extra_vars: is_shown_in_list 추가
- rzx_contact_messages: 신규 테이블

---

## [VosCMS 2.1.4] - 2026-04-17

### Added
- **download-hero 위젯** — 소프트웨어 다운로드 페이지 (version.json 실시간 연동, 4개 언어 내장, 주요 기능/시스템 요구사항/버전 히스토리)
- **/downloads 페이지** — widget 타입 시스템 페이지, 13개 언어 메뉴 번역, `is_system=1`
- **contact-form 위젯** (v0.1.0) — 비공개 1:1 문의 폼 위젯 (비회원 가능, 분류 선택, 허니팟 스팸 방지, AJAX 전송)
- **contact-info 위젯** — 연락처 정보 표시 위젯
- **location-map 위젯** — 위치 지도 위젯
- **rzx_send_mail() 공용 헬퍼** — `rzxlib/Core/Helpers/mail.php`에 SMTP 메일 발송 함수 신설
- **rzx_contact_messages 테이블** — 문의 저장용 DB 테이블
- **/contact 페이지** — widget 타입 시스템 페이지, 13개 언어 메뉴 번역

### Fixed
- **시스템 페이지 삭제 보호** — 메뉴에서 시스템 페이지 삭제 시 페이지 콘텐츠/위젯 보존 (메뉴 항목만 제거)
  - `menus-api.php`: DB `is_system` + `config/system-pages.php` slug 이중 체크
  - `menus-js.php`: 시스템 페이지 삭제 시 "메뉴에서만 제거됩니다" 안내 메시지
- **시스템 페이지 메뉴 다국어** — 시스템 페이지를 메뉴에 추가 시 `site.pages.*` 번역 키에서 13개 언어 자동 등록
- **다운로드 페이지 번역** — `site.pages.downloads` 13개 언어 번역 추가

### Changed
- **홈페이지 히어로** — Download 버튼 → `/download/voscms-latest.zip` 직접 다운로드, 버전 v2.1.1 업데이트
- **Auth.php** — `sendSmtpMail` → `rzx_send_mail()` 공용 헬퍼 우선 호출 (폴백 유지)
- **index.php** — `mail.php` 헬퍼 자동 로드 추가

### Docs
- **COMPONENT_MANUAL.md** — `rzx_send_mail()` 함수 레퍼런스 추가
- **WIDGET_MANUAL.md** — download-hero, contact-form, contact-info, location-map 위젯 문서 추가

---

## [VosCMS 2.1.3] - 2026-04-17

### Changed — 마이페이지 서비스 상세 리디자인
- **호스팅 탭** — 요약 카드 + 사용량 프로그레스바 + FTP/DB 접속정보 테이블 (관리자 설정 시 자동 표시)
- **도메인 탭** — 도메인 카드 + 메인 설정 + 네임서버 정보 테이블 (관리자 설정 시 자동 표시)
- **메일 탭** — 메일서버 정보(IMAP/SMTP) + 계정별 카드 + 인라인 비밀번호 변경
- **유지보수 탭** — 정보 카드 + 점검 예정일 + 작업 이력 (관리자 기록 시 자동 표시)
- **부가서비스 탭** — 카드 레이아웃 통일
- 관리자 파셜과 동일한 정보 구조로 통일 (관리자가 metadata에 입력 → 마이페이지 자동 반영)

### Changed — 관리자 주문 상세
- 활동 로그 영역 1/3 → 1/5 비율로 축소
- 관리자 도메인 탭 NS 기본값 하드코딩 제거 (Cloudflare API 연동 대비)

### Changed — 서버 인프라 계획
- 프로비저닝 워크플로우 전면 수정: 서브도메인 우선 → 구입 도메인 후속 연결
- 프로비저닝 ID 규칙: 서브도메인명 또는 구입 도메인 SLD 기준
- Cloudflare 와일드카드 터널 활용 (서브도메인 A레코드 추가 불필요)
- 서버 2 디스크 구성 변경: NVMe 256GB + SSD 2TB + HDD 8TB 3디스크 체제

---

## [VosCMS 2.1.2] - 2026-04-16

### Added — 서비스 관리 시스템
- **서비스 분류 체계** — `service_class` 컬럼 추가 (recurring/one_time/free), `completed_at` 1회성 완료일
- **마이페이지 서비스 상세** — `/mypage/services/{order}` 타입별 탭 관리 (호스팅/도메인/메일/유지보수/부가서비스)
- **서비스 관리 API** — `api/service-manage.php` (자동연장 토글, 연장 신청, 메인 도메인 설정, 메일 비밀번호 변경)
- **관리자 주문 관리** — `/admin/service-orders` 주문 목록 + 상세 페이지, 1회성 상태 모달 (접수/진행/보류/취소/완료)
- **관리자 메뉴** — 사이드바에 "서비스 주문" 메뉴 추가 (position: 65)

### Added — 컴포넌트
- **phone-display** — 전화번호 표시 컴포넌트 (서버사이드 `format_phone()` 헬퍼), JS 의존 없음
- **format_phone()** — PHP 국제 전화번호 포맷 함수 (한국/일본/미국/중국/영국/프랑스/독일/호주 등)
- **무료 도메인 설정** — 관리자 설정에서 무료 서브도메인 목록 관리 (`service_free_domains`), 복수 도메인 셀렉트 지원

### Fixed
- **무료 서비스 구독 누락** — 무료 주문 시 도메인/부가서비스/유지보수 등 구독 레코드 미생성 → 전체 서비스 일괄 생성으로 리팩토링
- **결제 섹션 약관 동의** — 무료 주문 시 약관 체크박스가 결제 섹션과 함께 숨겨지는 문제 → 섹션 밖으로 분리
- **updateSubmitButton isFree 판별** — `btn.closest('form')` NULL → `document.querySelector`로 수정
- **관리자 사이드바 활성 표시** — `$adminPath` 미정의로 모든 메뉴 비활성 → 변수 선언 추가
- **마이페이지 아바타** — services.php/service-detail.php에 `use Auth` + `$user = Auth::user()` 누락
- **부가서비스 1회성 체크** — 관리자 설정의 `one_time` 플래그가 주문 시 `service_class`에 반영되도록 수정
- **1회성 서비스 초기 상태** — `active` → `pending`(접수)으로 생성되도록 수정

### Changed
- **주문 API 리팩토링** — 3개 결제 경로(무료/카드/계좌이체)의 구독 생성 코드를 `$subscriptionData` + `_insertSubscriptions()` 공통 함수로 통합
- **서비스 목록 UI** — 인라인 펼침 → 카드 링크 + 상세 페이지 분리
- **전화번호 표시** — 프로필/회원목록/스태프목록에 `format_phone()` 적용
- **무료 도메인 동적화** — `.21ces.net` 하드코딩 → 관리자 설정값 참조

---

## [VosCMS 2.1.1-hotfix] - 2026-04-16

### Fixed
- **푸터 메뉴 404 오류** — `rzx_page_contents` 테이블에 법적 문서 페이지(terms, privacy, refund-policy, data-policy, tokushoho, funds-settlement) 데이터 누락 → test DB에서 원본 복구
- **위젯 빌더 이미지/동영상 업로드 실패** — 프론트 래퍼(`page-widget-builder.php`)에서 `FormData` POST가 AJAX 핸들러로 전달되지 않는 문제 수정 (파일 업로드 조건 추가 + `ob_end_clean`/`exit` 처리)
- **업로드 디렉토리 권한** — `storage/uploads/widgets/` 소유자를 `www-data`로 변경하여 PHP-FPM 쓰기 권한 확보

### Added
- **서비스 신청 메뉴 다국어** — `rzx_translations` 테이블에 `menu_item.19.title` 13개 언어 번역 추가
- **히어로 CTA 위젯 다국어** — Dev 페이지 히어로 배너의 title, subtitle, button text에 13개 언어 번역 적용
- **업로드 상태 프리뷰** — 이미지/동영상 업로드 시 진행 상태(스피너), 성공(체크), 실패(에러 메시지) 시각적 피드백 추가

---

## [VosCMS 2.1.1] - 2026-04-12

### Added — 설치 마법사 다국어
- **Step 0 언어선택 화면 추가** — 설치 시작 전 13개 언어 중 선택, 이후 전체 UI가 해당 언어로 표시
- **설치 번역 파일 분리** — `resources/lang/install.php` (13개 언어 × 45개 문자열)
- **회원 등급 다국어** — Normal/Silver/Gold/VIP 13개국어 번역 자동 생성

### Added — 관리자 메뉴 시스템 분리
- **config/admin-menu.php** — 코어 관리자 메뉴를 설정 파일로 분리 (Core 업데이트 시 보존)
- **admin-sidebar.php 리팩토링** — 731줄 하드코딩 → ~200줄 동적 렌더러 (config + plugin.json 기반)
- **vos-shop plugin.json** — 하드코딩 메뉴를 `menus.admin` 형식으로 이전

### Added — 마이페이지 모바일 대응
- **슬라이드인 메뉴** — 모바일에서 아바타+이름 토글 바 → 좌측 슬라이드인 오버레이 (300ms ease-out)
- **페이드 딤 배경** — 배경 클릭 또는 X 버튼으로 닫기, body 스크롤 잠금

### Added — Translator 언어 감지 개선
- **force_locale** — 기본 언어 강제 적용 (`?lang=xx` 파라미터로 사용자 변경 가능)
- **default_language** — install.php ↔ Translator 설정 키 통일
- **URL 파라미터** — `?lang=xx` 최우선 감지, 세션/쿠키에 저장

### Fixed
- **FAQ 스킨 미적용** — 마이그레이션 SQL의 기본 게시판 INSERT가 skin 없이 먼저 실행되어 install.php의 `INSERT IGNORE`가 무시되는 문제 수정
- **translations 테이블 source_locale 누락** — 코어 마이그레이션에 컬럼 누락 → db_trans() 전체 실패 → 사용자 정보/메뉴/다국어 표시 안 됨
- **설정 탭 사이드바 활성 표시** — `route_prefix` 속성 추가로 settings 하위 모든 라우트에서 설정 메뉴 활성 유지
- **설치 세션 잔류 버그** — .env 없는 새 설치 시 이전 PHP 세션이 남아 Step 0을 건너뛰는 문제 수정
- **nginx 403** — admin/ 물리 디렉토리와 라우트 충돌 수정 (`try_files $uri/` 제거)
- **난독화 스크립트 버전 하드코딩** — obfuscate.php가 version.json에서 동적으로 버전을 읽도록 수정

### Changed
- **그리드/board-contents 위젯 다국어뷰** — rzx_translations 번역 조회 추가 (현재 로케일 → 영어 폴백)
- **사이트 업종 카테고리** — 15개 → 26개 확대 (기업, 쇼핑몰, 법률, 회계, IT, 미디어 등 13개국어)
- **그리드 위젯 설정** — grid-section render.php의 네이티브 셀 타입(`board-list`)으로 변경, 13개국어 제목
- **Step 4 언어 선택** — 5개 → 13개 언어 확대, 설치 언어로 pre-fill
- **timezone 선택** — 13개국어 사용자 대응 시간대 12개로 확대

---

## [1.26.0] - 2026-04-01

### Added — 키오스크 번들 서비스
- **서비스 선택 상단에 번들(세트) 서비스 3열 배치** — 앰버 색상 테마로 개별 서비스와 시각적 구분
  - SET 배지, 이벤트 할인 라벨, 포함 서비스 이름, 번들 가격, 원가 취소선, 할인율 표시
  - 번들 선택 시 포함 서비스 자동 선택 (가격은 번들 가격으로 계산)
  - 번들에 포함된 서비스는 개별 토글 불가 (잘못된 가격 계산 방지)
  - 지명 모드: `rzx_staff_bundles` 테이블로 스태프별 번들 필터링
  - confirm 페이지로 `bundles` 파라미터 전달
- **다국어 지원**: 번들 관련 키오스크 번역 키 13개 언어 추가

### Added — 캘린더 월/주/일 뷰
- **뷰 전환 버튼** — 상단 바에 `[월] [주] [일]` 토글 (활성 상태: 파란색)
- **주간 뷰** — 좌측 세로=요일, 상단 가로=시간축
  - 가로 스크롤, 예약 블록이 시간 바 형태로 표시
  - 레인 알고리즘: 시간 겹침 감지 후 아래 줄 자동 배치, 행 높이 가변
  - 좌측 사이드바 ↔ 그리드 세로 스크롤 동기화
- **일별 뷰** — 가로=시간축, 세로=레인 (주간 뷰와 동일 방식)
- **영업시간 기반 시간 범위** — `business_hour_start`/`business_hour_end` 설정 기반, 범위 외 예약 시 자동 확장
- **빈 칸 클릭 시간 전달** — 주간/일별 뷰에서 클릭 X좌표 → 15분 단위 시간 계산 → 예약 모달 시작 시간 자동 입력
- **상단 네비 바 고정** — `overflow-x-hidden` + `sticky` 적용, 가로 스크롤해도 뷰포트 내 고정

### Added — 설정: 영업 시간
- **관리자 > 설정 > 일반** — 영업 시작/종료 시간 설정 UI 추가
  - `business_hour_start`, `business_hour_end` DB 설정 키
  - 캘린더 주간/일별 뷰의 기본 시간 범위에 적용

### Fixed — 예약 등록/POS 데이터 연동
- **POS `add-service` 누락 필드 수정** — `start_time`, `end_time`, `staff_id`, `designation_fee`, `notes` 전달 추가 (기존: 서버 시간 하드코딩, 스태프/메모 누락)
- **end_time 자정 넘김 방지** — `ReservationHelper`, `start-service` API, 예약 폼 `calcEnd()` 모두 `% 24` → `min(23:59)` 상한 적용
- **POS 이용중 판정 자정 넘김 대응** — `CustomerBasedAdapter`에서 `end_time < start_time` 시 자정 넘김 처리
- **예약 등록 번들 검증** — 번들만 선택하고 개별 서비스 미선택 시에도 예약 생성 가능하도록 수정

### Changed — POS 탭 구조 개편
- **탭 이름 변경**: 접수 / 접수 명단 / 예약자
- **접수 명단**: `walk_in`, `pos`, `kiosk`, `admin` 소스 — 모든 상태 표시
- **예약자**: `phone`, `online` 소스 — 모든 상태 표시
- **정렬 순서**: 대기 → 확정 → 이용중 → 완료 → 노쇼 → 취소, 같은 상태 내 시간순
- **완료/취소/노쇼 행**: `opacity-50`으로 흐리게 처리, 미배정 배지 표시

### Changed — POS 상단 배지 개선
- 완료 배지: 회색 → 초록색으로 변경
- 취소/노쇼 배지 추가 (건수 0이면 숨김)

### Added — POS 노쇼 버튼
- **카드 하단**: 대기 상태에 노쇼 버튼 추가 (금지 아이콘), 취소 버튼 빨간색으로 변경
- **상세 모달**: `pending`, `confirmed` 모두 노쇼 액션 버튼 표시
- **그룹 일괄 노쇼**: `POS.noShowAllServices()` 함수 추가

### Fixed — UI/UX
- **예약 상세 돌아가기** — `<a href="/reservations">` → `history.back()` 변경, 이전 페이지로 복귀
- **다국어**: 캘린더 뷰 전환(월/주/일), 번들, 영업시간 관련 키 13개 언어 추가

---

## [1.25.0] - 2026-04-01

### Architecture — POS 플러그인 분리
- **POS를 독립 플러그인 구조로 분리**
  - `resources/views/admin/reservations/pos-*.php` → `resources/views/admin/pos/` 디렉토리로 이동
  - `pos/` 디렉토리 존재 여부로 POS 기능 자동 활성화/비활성화 (`is_dir()` 체크)
  - 사이드바 메뉴: `pos/` 디렉토리 없으면 POS 메뉴 자동 숨김
  - `index.php` 라우팅: `pos/` 디렉토리 없으면 POS 라우트 비활성화
  - `pos/_init.php` → 예약 공통 `_init.php` 위임 (DB, 인증, 다국어 공유)
  - API 엔드포인트는 `reservations/_api.php`에 유지 (호환성 보장)

### Added — POS 통합 결제 시스템
- **통합 결제 모달** — 적립금 + 현금 + 카드 동시 사용 가능 (복합 결제)
  - 적립금: 잔액 조회, 수동 입력 또는 전액 사용
  - 현금: 금액 입력
  - 카드: 금액 입력 + "잔액 전체" 자동 입력 버튼
  - 할부: 일시불 / 2~12개월 선택
  - 실시간 합계, 거스름돈, 부족 금액 자동 계산
  - Stripe 카드 결제 시 현금/적립금은 결제 성공 후에만 처리 (취소 시 안전)
- **서비스 상세 모달 — 적립금 표시**
  - 헤더 영역에 회원 적립금 잔액 상시 표시 (잔액 0이어도 표시)
- **서비스 상세 모달 — 스태프 배지 개선**
  - 배정/지명 배지를 스태프 이름 앞에 표시 (기존: 이름 아래)
- **영수증/인쇄 버튼 위치 이동**
  - 고객 상세 정보 위로 이동, 영수증(좌) + 인쇄(우) 배치
- **인쇄 기능 개선**
  - 외부 페이지가 아닌 현재 서비스 상세 모달 내용을 인쇄

### Changed — POS UI 개선
- **카드 레이아웃** — grid 기반 → flex-wrap 기반 고정 크기 카드
  - 카드 크기 고정: 기본 320×240px (small: 260×200, large: 380×280)
  - 화면 크기에 따라 카드 크기 변하지 않고 자동 줄바꿈
- **카드 결제 모달** — 폭 1030px, 높이 800px로 확대

### Fixed
- **API cross-origin 오류 해결**
  - `$baseUrl`이 `.env`의 `APP_URL`로 고정되어 다른 호스트/HTTPS 접속 시 fetch 실패
  - 대시보드: fetch URL을 상대 경로(`/update-api.php`)로 변경
  - 예약 `_init.php`: `$baseUrl`을 빈 문자열로 변경하여 모든 fetch가 상대 경로 사용
  - Cloudflare HTTPS 프록시 환경에서 mixed content 문제 완전 해결
- **DB 마이그레이션 실행 오류** — "Failed to fetch" 해결 (cross-origin 문제)
- **Stripe 복합결제 취소 시 현금 선결제 버그**
  - 기존: 현금 먼저 결제 → 카드 결제 취소 시 현금이 이미 처리됨
  - 수정: Stripe 카드 결제 성공 후에만 현금/적립금 처리

### i18n — 다국어 추가 (13개 언어)
- `pos_pay_after_points` — 적립금 적용 후 금액
- `pos_pay_proceed` — 결제 진행
- `pos_pay_remainder` — 잔액 전체
- `pos_pay_installment` — 할부
- `pos_pay_lump_sum` — 일시불
- `pos_pay_months` — 개월
- `pos_pay_total_pay` — 합계
- `pos_pay_short` — 부족
- `show_receipt` — 영수증
- `show_print` — 인쇄

---

## [1.18.0] - 2026-03-29

### Added
- **다국어 날짜 포맷팅 함수** — `formatReservationDate($dateString, $locale)`
  - 13개 언어 지원 (ko, en, ja, zh_CN, zh_TW, de, es, fr, id, mn, ru, tr, vi)
  - 예약 상세 페이지에서 날짜/요일 자동 다국어 표시
  - 폴백: 미지원 로케일 → 영어 형식

### Changed
- **예약 상세 페이지 다국어 처리**
  - 서비스명, 스태프명, 번들명 다국어 적용 (DB translations 테이블 기반)
  - 예약 날짜 포맷팅 함수 통합
  - 결제 상태 라벨 다국어 처리 (payment_status 필드)
- **결제 상태 다국어 지원**
  - 모든 언어 파일(11개)에 `payment` 섹션 추가
  - unpaid(미결제), paid(결제완료), partial(부분결제), refunded(환불) 번역

### Fixed
- 예약 상세 페이지: 다국어 미적용되던 스태프명, 서비스명 정상 표시
- 결제 상태 라벨: 한국어로만 표시되던 문제 해결
- `_tr()` 함수 기반 다국어 처리로 통일

### Layout & UI
- **페이지 폭 통일** (max-w-5xl → max-w-7xl)
  - 게시판 (list, read, write): max-w-5xl → max-w-7xl
  - 외부 페이지 (page.php): 기본값 및 DB 설정값 업데이트
  - 예약 상세/예약 조회/스태프 페이지: DB 설정값 업데이트
  - 결과: 모든 페이지 폭이 예약하기 페이지와 동일하게 통일

### Documentation
- COMPONENT_MANUAL.md에 `formatReservationDate()` 함수 상세 문서 추가
  - 지원 언어 및 형식 테이블
  - 사용 예제 및 내부 구조
  - 주의사항 및 관련 함수

---

## [1.16.0] - 2026-03-23

### Added
- **모바일 하단 메뉴바** — 스태프/예약/홈/로그인(또는 마이)/회원가입/더보기, 현재 페이지 하이라이트
- **모바일 더보기 패널** — 메인 메뉴 + 마이페이지/로그아웃
- **레이아웃/스킨 설정 실제 적용** — site_layout, site_page_skin, site_board_skin, site_member_skin → 렌더링 반영
- **no_layout 파라미터** — 레이아웃 없이 콘텐츠만 (미리보기용)
- **다국어 추가** — common.nav.staff, common.nav.more (13개 언어)

### Fixed
- 회원 페이지(login/register/forgot/reset) 레이아웃 적용: 스킨 `<main>` 추출로 중복 헤더 제거
- 회원 스킨 ImageCropper/ProfilePhotoCropper 중복 선언 에러 수정
- 메뉴 로더 include_once 문제: 스킨 클로저에서 먼저 로드 시 base-header 메뉴 누락 수정
- `apple-mobile-web-app-capable` deprecated → `mobile-web-app-capable` (8개 파일)
- 헤더 로그인/회원가입 버튼 모바일에서 숨김 (하단 메뉴바로 대체)
- iOS safe-area 대응 (하단 메뉴바)

### Changed
- 회원 페이지를 자체 레이아웃에서 기본 레이아웃으로 통합 (login/register/forgot/reset)
- 푸터 하단 여백 제거 (모바일 메뉴바 fixed 방식)

---

## [1.15.3] - 2026-03-22

### Added
- **레이아웃 상세 설정 패널** — SkinConfigRenderer 기반 설정 폼 AJAX 로드
- **레이아웃 메뉴 설정** — layout.json의 menus 섹션 → DB sitemaps 드롭다운 자동 생성
- **SkinConfigRenderer 탭 지원** — vars에 `tab` 속성 추가 시 탭 UI 자동 생성
- **SkinConfigRenderer 리팩토링** — renderVar() 단일 메서드 통합, renderFormFlat/renderFormWithTabs 분리, renderDependsOnJs 공통화
- **layout.json 확장** — menus(GNB/FNB), vars 14개 항목(헤더/로고/색상/푸터/커스텀), 13개 언어
- **레이아웃 미리보기 연동** — "사용 안 함" 선택 시 ?no_layout=1로 레이아웃 없는 미리보기

### Fixed
- 레이아웃 관리 메뉴 조회 `rzx_menus` → `rzx_sitemaps` 테이블명 수정
- 가운데 패널 width 조정 + 액션 링크 whitespace-nowrap

### Pending
- 레이아웃/스킨 설정값의 실제 페이지 렌더링 적용 (다음 세션)

---

## [1.15.2] - 2026-03-22

### Added
- **레이아웃 관리 페이지** — 좌측 미리보기+메뉴, 우측 스킨 목록, 상세 설정 패널 3단 구조
- 각 스킨 항목: 썸네일 + 상세 설정/복사본 생성/삭제 액션 링크
- "사용 안 함" / "다른 스킨 설치" 옵션
- 레이아웃/페이지/게시판/회원 4개 카테고리 저장 (`site_layout`, `site_page_skin`, `site_board_skin`, `site_member_skin`)

### Changed
- "디자인 관리" → "레이아웃 관리" 명칭 변경 (13개 언어 admin.php + site.php)

---

## [1.15.1] - 2026-03-22

### Changed
- 사이트 관리 15개 페이지 → `_head.php` 공용 헤더 적용
  - 게시판: boards, boards-create, boards-edit, boards-trash
  - 메뉴: menus
  - 디자인: design
  - 페이지: pages, pages-settings, pages-edit-content, pages-document, pages-compliance, pages-widget-builder
  - 위젯: widgets, widgets-create, widgets-marketplace
- embed 모드 페이지 (boards-edit, pages-settings, pages-edit-content)는 비embed 시만 _head.php 적용
- 전체 관리자 페이지 헤더/레이아웃 통일 완료

---

## [1.15.0] - 2026-03-22

### Added
- **시스템 위젯 모듈** — staff(스태프 소개), booking(예약하기), lookup(예약 조회), cta001(예약 유도 배너) 4개
- **시스템 페이지** — staff, booking, lookup DB 등록 (is_system=1) + 라우팅 (`/staff`, `/booking`, `/lookup`)
- **위젯 기반 페이지 렌더링** — staff/booking/lookup 페이지가 위젯 배치 시 위젯 렌더링, 미배치 시 기존 폴백
- **위젯 자동 DB 등록** — `syncToDatabase()` 호출로 위젯 디렉토리 추가 시 자동 등록
- **위젯 카테고리 `system`** — 13개 언어 번역 (widgets.categories + widget_builder.cat)
- **페이지 목록 통합** — 시스템 페이지 루프 렌더링 + 기어/수정/미리보기 아이콘 통일
- **관리자 페이지 헤더 통합** — `$pageSubTitle` + `$pageSubDesc` (_head.php 공통 출력)
- **번역 키 추가** — staff.admins, staff.attendance.report/stats, points.description 등

### Fixed
- 위젯 빌더 `page_slug = 'home'` 하드코딩 → `$_GET['slug']` 동적 처리
- 위젯 빌더 AJAX save/preview `home` 하드코딩 → `$pageSlug` 동적
- 위젯 빌더 미리보기 URL → `$pageSlug` 동적 (홈 고정 → 각 페이지)
- 위젯 preview_widget WidgetLoader require 누락 수정
- 서비스 카드 뷰 이미지 경로 (`/storage/` 접두사)
- 키오스크 설정 width `max-w-3xl` → 100% 통일
- POS 헤더 "예약 관리" → "POS" 변경

### Changed
- 스태프 관리 10개 + 회원 관리 4개 페이지 → `_head.php` 공용 헤더 적용
- 키오스크/번들 페이지 `_head.php` 적용 완료
- 키오스크 언어 설정 grid → flex-wrap 반응형
- 페이지 목록 시스템 페이지 데이터 기반 루프 렌더링

---

## [1.14.1] - 2026-03-22

### Added
- **페이지 스킨 탭** — 스킨 기본정보 + 확장 변수 설정 (SkinConfigRenderer 기반)
- **페이지 스킨 전 타입 적용** — document/widget/external 모두 스킨 설정 적용
- **제목 배경 시스템** — 이미지/동영상 배경 + 오버레이 + 텍스트 색상 (공통 partial `_page-title-bg.php`)
- **depends_on 시스템** — skin.json에서 필드 간 의존 관계 선언적 정의, 렌더러 자동 JS 생성 (checkbox/radio 지원)
- **스킨 파일 업로드** — multipart 전송으로 이미지/동영상 업로드 지원 (`storage/skins/page/{skin}/`)
- **카드형 레이아웃/스킨 선택** — 모듈 정보 탭에서 썸네일 카드 UI로 변경
- **SEO 시스템** — SEO 헬퍼, 제목 패턴, OG/Twitter 메타태그, 콘텐츠 추출
- **OG Image 업로드** — URL 입력 + 파일 업로드 + 미리보기
- **공통 결과 모달** — 저장 성공/실패 모달 UI 통합 (13개 언어 지원)
- **서비스 카드 뷰** — 배경 이미지 전체 적용 카드 + 테이블/카드 전환 버튼
- **서비스 드래그 앤 드롭 순서** — 테이블/카드 양쪽 뷰에서 순서 변경 + DB 저장
- **예약 스태프 배정/지명** — 예약 상세에서 배정/지명 버튼 2개
- **예약 경로 UI** — 전화예약/현장접수/온라인 선택
- **전화 예약 설정** — 서비스 기본설정에서 활성/비활성 토글
- **보안 헤더** — X-Frame-Options + CSP frame-ancestors
- **PAGE_SYSTEM.md** — 페이지 시스템 개발 문서
- **PRD_OrderSystem.md** — 테이블 주문 시스템 기획 문서
- **PRD_KioskSystem.md** — 키오스크 시스템 기획 문서

### Fixed
- 파비콘 DB 설정 적용 (프론트/관리자/스킨 레이아웃 4곳)
- 대시보드 마이그레이션 URL `window.location.origin` → `$baseUrl` (서브디렉토리 환경)
- 페이지 설정 embed 모드 API URL → admin 경로 직접 지정
- 결과 모달 다국어: API 응답 대신 페이지 렌더링 시 로케일 메시지 사용
- 스킨 탭 빈 skin 값 폴백 (`""` → `default`)
- pages-settings.php `php://input` 중복 소비 문제 해결

### Changed
- 키오스크/키오스크 설정/번들 페이지 → `_head.php` 공용 헤더 적용
- 스킨 설정 저장 JSON → FormData(multipart) 전송 (파일 업로드 지원)
- SkinConfigRenderer에 `$baseUrl` 파라미터 추가 (이미지/비디오 미리보기)
- 13개 언어 스킨 번역 (skin.json + site.php + common.php)

---

## [1.13.0] - 2026-03-21

### Added
- **페이지 시스템 완성** — 메뉴에서 문서/위젯/외부 페이지 생성 시 자동 페이지 생성
- **동적 페이지 렌더러** — `page.php` (문서→HTML, 위젯→WidgetRenderer, 외부→iframe/include)
- **페이지 환경 설정** — 3탭 (기본/레이아웃·스킨/SEO), `page_config_{slug}` JSON 저장
- **페이지 콘텐츠 편집** — 언어별 제목·콘텐츠 편집, slug 변경, 타입 변경, 삭제
- **페이지 관리 동적 목록** — DB에서 사용자 페이지 자동 로드, 타입별 아이콘·배지
- **프론트 관리자 아이콘** — 기어(환경설정) + 편집(콘텐츠) 분리 표시
- **외부 페이지 iframe** — 로드 실패 시 "새 창에서 열기" 안내
- **내부 PHP/HTML 파일** — include로 RezlyX 레이아웃 안에서 렌더링

### Fixed
- 메뉴 바로가기 URL `#menu_{id}` → 실제 메뉴 URL 저장
- 메뉴 편집 시 바로가기 UI 생성 시와 동일 구조 표시
- 메뉴 저장 시 바로가기 URL 반영 (`formMenuType` → 패널 가시성 기준)
- 메뉴 13개 언어 번역 추가 (12개 메뉴 항목 × 13개 언어)
- 페이지 문서 에디터 번역 키 경로 수정 (`admin.site.pages.` → `site.pages.`)

### Changed
- DB `rzx_page_contents`에 `page_type` 컬럼 추가 (document/widget/external)
- 메뉴 삭제 시 연결 페이지 + 번역 데이터 함께 삭제

---

## [1.12.5] - 2026-03-21

### Fixed
- 메뉴 바로가기 URL 수정 — `#menu_{id}` 대신 실제 메뉴 URL 저장

---

## [1.12.4] - 2026-03-21

### Fixed
- 스킨 설정 목록 유형 미적용 — localStorage 캐시가 스킨 설정을 덮어쓰는 문제 수정
- 스타일 전환 비허용 시 스킨 설정만 사용, 허용 시 localStorage 우선
- checkbox hidden 중복 전송 제거 (JS에서 unchecked 처리)

---

## [1.12.3] - 2026-03-20

### Fixed
- 게시판 영어/일본어 번역 키 누락 (source_locale, reply, style, board_settings 등)
- 카테고리명 다국어 폴백 적용 (로케일 → en → 원본)
- 게시판 목록 본문 다국어 (웹진/카드/갤러리 미리보기)

---

## [1.12.2] - 2026-03-20

### Fixed
- 게시판 목록 본문 다국어 폴백 — 웹진/카드/갤러리 미리보기에 번역 본문 적용 (로케일 → en → 원본)
- 프로덕션 스킨 파일 누락 — `skins/default/board/` 디렉토리 FTP 생성 + skin.json/thumbnail 업로드

---

## [1.12.1] - 2026-03-20

### Fixed
- 프로덕션 스킨 설정 미표시 — 전체 파일 동기화 재정비
- CLAUDE.md + BOARD_SYSTEM.md 파일 구조 전면 업데이트

---

## [1.12.0] - 2026-03-20

### Added
- **스킨 설정 시스템 완성** — skin.json 기반 동적 폼 생성 + 실제 게시판 적용
  - 섹션 구분 (기본/목록/링크/디자인/제목배경/커스텀)
  - radio, video 타입 추가
  - 이미지/동영상 파일 업로드 (FormData multipart)
  - 이미지/동영상 삭제 기능
  - 스킨 기본정보 테이블 (제작자/URL/이메일/날짜/버전/썸네일)
- **제목 배경 이미지/동영상** — 배경 타입, 높이, 오버레이 투명도, 텍스트 색상 설정
- **목록 4가지 스타일** — 목록형/웹진형/갤러리형/카드형, 스킨 파일 분리 + 오버라이드
  - 갤러리형: 풀 이미지 cover + 호버 슬라이드 애니메이션
  - 웹진형: 썸네일+제목+미리보기+메타정보 카드
  - 공지글 상단 목록형 분리 (_list-notices.php)
- **스킨 설정 적용** — primary_color, border_radius, posts_per_row, allow_style_switch, show_category, 링크 게시판, Font Awesome, 커스텀 CSS/HTML
- **skin.json 기본값 자동 병합** — DB 저장 없어도 skin.json default 적용

### Fixed
- skin_config DB 저장 누락 (boards-api.php update 액션에 skin_config 미포함)
- 스킨 저장 URLSearchParams → FormData(multipart) 변경 (파일 업로드 지원)
- $_POST['skin_config'] 배열 자동 파싱 대응

---

## [1.11.3] - 2026-03-20

### Fixed
- 게시글 상세 이전/다음 글 제목 다국어 폴백 적용 (로케일 → en → 원본)

---

## [1.11.2] - 2026-03-20

### Fixed
- 게시판 목록 제목 다국어 폴백 — 모든 로케일에서 번역 조회 (한국어 제한 제거), 원본 언어 고려한 영어 폴백

---

## [1.11.1] - 2026-03-20

### Fixed
- OG 카드 단독 URL 판별 개선 — `<font>/<span>` 등 인라인 래핑 태그 내부 URL도 블록 부모까지 탐색하여 정상 처리

---

## [1.11.0] - 2026-03-20

### Added
- **URL 자동 링크** — 게시글 본문/에디터에서 URL 자동 감지 → `<a>` 태그 변환
- **OG 링크 프리뷰 카드** — SNS 배너 형식 (썸네일+제목+설명+도메인) 카드 자동 생성
  - 에디터: URL 입력 후 엔터/붙여넣기 → OG API 호출 → 카드 삽입
  - 본문: 단독 URL 링크 → OG 카드로 자동 변환
- **OgFetcher 모듈** — OG/Twitter Card 메타데이터 추출, favicon, 인코딩 자동 처리
- **OG API** — `/board/api/og` 엔드포인트
- **board-autolink.js** — 자동 링크 + OG 카드 통합 JS (base-header에서 전역 로드)

---

## [1.10.2] - 2026-03-20

### Fixed
- **에디터/본문 이미지 정렬** — Tailwind preflight의 `img { display: block }` 덮어쓰기
  - `board-content.css` 공통 CSS 생성 (에디터+본문 표시 통합)
  - `base-header.php`에서 전역 로드 (프론트 모든 페이지)
  - `multilang-modal.php`에 전역 에디터 스타일 추가 (관리자 모든 Summernote)
- 게시판 본문 `prose` 클래스 제거, 에디터 인라인 스타일 100% 보존

### Changed
- CLAUDE.md에 게시판 본문 스타일 규칙 명시 (`board-content` 클래스 필수)

---

## [1.10.1] - 2026-03-20

### Fixed
- 게시판 분류 추가 시 다국어 모달에 이전 분류 데이터가 로드되는 버그 수정 (langKey 고유 임시 키 생성)
- 마이그레이션 021에 `comment_max_depth` 기본값 3 설정 추가

---

## [1.10.0] - 2026-03-20

### Added
- **게시판 확장 변수** — ExtraVarRenderer 컴포넌트 모듈 (15종 타입별 input/display 렌더링)
- **확장 변수 다국어** — 언어별 확장 변수 값 독립 저장 (rzx_translations 연동, 폴백 체인)
- **게시글 다국어 분리 저장** — 원본 언어 수정 시 board_posts 직접, 다른 언어 시 rzx_translations에 번역 저장
- **게시글 읽기 폴백 체인** — 현재 로케일 → en → 원본 순으로 제목/본문/확장변수 표시
- **대댓글(답글)** — 3단계 깊이 대댓글 지원, 재귀 트리 정렬, 인라인 답글 폼
- **댓글 추천/비추천** — 댓글별 좋아요 버튼 + API
- **게시판 설정 프론트 접근** — 관리자 기어 아이콘 → /board/{slug}/settings 프론트 레이아웃
- **게시판 관리 제목 링크** — 관리자 게시판 목록에서 제목 클릭 시 해당 게시판 이동

### Fixed
- 게시판 API 관리자 권한 확인 `$currentUser['role']` → `$_SESSION['admin_id']` (4파일 수정)
- 원문 언어 안내 메시지 작성자/관리자에게만 표시
- 게시판 설정 embed 모드 탭 링크 프론트 URL 지원

### Changed
- DB `rzx_board_posts`에 `extra_vars` JSON 컬럼 추가
- `comment_max_depth` 기본값 0 → 3 변경
- 댓글 액션 바 통합 (추천/비추천/답글/삭제 아이콘)

---

## [1.9.4] - 2026-03-20

### Added
- **기본 게시판 데이터** — 공지사항, 질문과 답변, 자주 묻는 질문, 자유게시판 마이그레이션 (020_default_boards_v193.sql)

---

## [1.9.3] - 2026-03-20

### Added
- **회원 그룹 초기화** — 기본 제공 데이터(7개 그룹 + 13개 언어 번역)로 초기화 기능
- **적립금 설정 초기화** — 설정값/레벨 포인트/그룹 연동을 기본값으로 복원 (회원 잔액 미영향)
- **회원 그룹 다국어** — 그룹명/혜택 설명에 rzx_multilang_input() 공통 컴포넌트 적용
- **회원 그룹 드래그앤드롭** — SortableJS 카드 순서 변경 + 자동 저장

### Fixed
- 회원 그룹 카드 로케일별 번역 표시 (카드 뷰 + 편집 모달)
- 회원 그룹 다국어 번역 데이터 깨짐 수정 (PHP UTF-8 재저장)
- 적립금 모듈별 설정 게시판 컬럼명 수정 (board_name → title)
- 적립금 회원 목록 collation 충돌 수정 (utf8mb4_unicode_ci 통일)
- 프로덕션 DB 마이그레이션 (019_point_system_v190.sql)

---

## [1.9.0] - 2026-03-20

### Added
- **적립금(포인트) 관리 시스템** — 3탭 구조 (기본 설정 / 모듈별 설정 / 회원 포인트 목록)
- **적립금 3필드 구조** — point(활동 포인트), balance(결제 적립금), total_accumulated(누적 포인트, 레벨 기준)
- **누적 포인트 가중치** — 결제 가중치 / 활동 가중치 분리, 결제 고객 우대 레벨업
- **적립금 환전** — 포인트 → 적립금 변환 (비율, 단위, 최소 포인트 설정)
- **포인트 부여/차감 17개 항목** — Rhymix 동일 구조 (삭제시 회수, 기간 제한, 공지 제외)
- **모듈별 설정** — 게시판별 15개 포인트 항목 개별 설정
- **회원 포인트 목록** — 포인트/적립금/누적포인트/레벨 4컬럼, 검색, 개별 수정
- **레벨 포인트** — 수식 계산, 최고 레벨 동적 조정, 그룹 연동
- **포인트 시스템 문서** — POINT_SYSTEM.md 상세 설계 문서
- **회원 그룹 드래그앤드롭** — SortableJS로 카드 순서 변경 + 자동 저장
- **회원 그룹 다국어** — 그룹명/혜택 설명에 rzx_multilang_input() 공통 컴포넌트 적용
- **회원 그룹 기본 데이터** — 7개 그룹 × 13개 언어 다국어 번역 데이터

### Changed
- DB `rzx_member_points`에 `balance`, `total_accumulated` 컬럼 추가
- DB `rzx_point_levels.group_id` INT → CHAR(36) 수정 (UUID 호환)
- DB `rzx_point_levels`, `rzx_member_points` collation utf8mb4_unicode_ci 통일
- 번들 페이지 레이아웃 수정 (`sidebar-main-content` → `flex-1 ml-64` 대시보드 패턴)
- 번들 페이지 화폐 단위 `$config['currency']` → `$siteSettings['service_currency']` 수정
- 서비스 소요시간 select → input number + datalist 변경
- CLAUDE.md에 다국어 입력 UI 규칙 명시 (rzx_multilang_input 필수 사용)

---

## [1.8.3] - 2026-03-20

### Added
- **번들 상세 관리 페이지** — 개별 번들 기본정보 수정, 대표 이미지 업로드, 서비스 구성 편집, 스태프 연동, 활성/비활성 토글
- **이벤트 할인** — 번들별 이벤트 가격, 기간(시작/종료), 라벨 설정. 기간 내 자동 활성화
- **번들 목록 카드 디자인** — 그리드 카드 레이아웃, 이미지 헤더, 이벤트/할인 배지, 하단 고정 액션 바
- **추천 패키지 배경 이미지** — 고객 스태프 상세 페이지 번들 카드에 대표 이미지 배경 + 그라데이션 오버레이
- **서비스 소요시간 직접 입력** — select → input number (5~480분, 5분 단위) + datalist 추천 목록

### Changed
- DB `rzx_service_bundles`에 `event_price`, `event_start`, `event_end`, `event_label` 컬럼 추가
- 번들 목록 API에 `total_duration`, 이벤트 컬럼 추가
- 번역 파일(ko, en, ja) 번들 상세 관리 + 이벤트 할인 키 추가

---

## [1.8.1] - 2026-03-19

### Added
- **POS 설정 페이지** — 카드 크기, 이미지/가격/전화 표시, 투명도, 기본 탭, 자동 새로고침, 알림음, 스태프 배정 필수, 자동 배정
- **POS 설정 실적용** — 카드 크기/이미지/가격/전화/투명도/자동 새로고침/기본 탭 모두 POS에 반영
- **스태프 자동 배정** — POS 설정에서 활성화 시 키오스크 접수 시 자동 배정 (서비스 수행 가능 + 당일 예약 최소)
- **POS 카드 스태프 배지** — 배정/미배정 표시, 미배정 시 진행 버튼 비활성화
- **POS 서비스 이미지 배경** — 서비스 내역 모달 헤더+고객 영역에 대표 서비스 이미지 배경
- **스태프 is_visible** — 목록 노출 여부, 비노출 스태프 예약/POS/키오스크 제외
- **스태프 카드 배너** — 관리자 스태프 카드에 배너 이미지 + 상태 배지
- **키오스크 자동 배정** — staff_id=NULL로 접수, 관리자 POS에서 배정

### Fixed
- POS 스태프 배정 후 서비스 목록 즉시 갱신 (캐시 방지)
- 스태프 배정 API 파라미터 수정 (reservation_id → reservation_ids[])
- 프로덕션 누락 테이블/컬럼 다수 수정 (018 마이그레이션 보강)
- 관리자 로그아웃 라우트 추가
- 키오스크 인증 전 라우트 배치

---

## [1.7.0] - 2026-03-18

### Added
- **기본 레이아웃 시스템** — `layouts/base-header.php` + `base-footer.php`, index.php에서 ob_start 자동 적용, 모든 고객 페이지 공통 헤더/푸터
- **게시판 프론트 구현** — 목록(list), 상세(read), 글쓰기/수정(write) 페이지, Summernote 에디터, 드래그&드롭 파일 첨부
- **게시판 단축 URL** — `/free`, `/notice/3` 등 `/board/` 없이 접근 가능, DB slug 자동 매칭
- **예약 조회 페이지** — `/lookup` 단축 URL, 예약번호/이메일/전화번호 검색
- **예약 상세 페이지** — `/booking/detail/{번호}`, 스태프 인사말 말풍선, 서비스/결제/취소 정보
- **예약 취소 페이지** — `/booking/cancel/{번호}`, 취소 사유 입력
- **마이페이지 예약 상세** — `/mypage/reservations/{id}`, 사이드바 + 상세 정보
- **게시글 다국어** — `original_locale` (최초 작성 언어) + `source_locale` (현재 콘텐츠 언어), 폴백 체인 (source → en → original)
- **스태프 인사말** — `greeting_before` (시술 전) + `greeting_after` (시술 후), 다국어 지원, 예약 상태별 표시
- **관리자 프로필 드롭다운** — 프로필 사진/이니셜 + 이름 + 홈페이지/마이페이지/로그아웃 메뉴
- **고객 헤더 관리자 링크** — 관리자 세션 시 드롭다운에 관리자 페이지 링크 표시

### Changed
- 모든 고객 페이지 레이아웃 통합 (자체 HTML 구조 제거, LayoutManager 자동 적용)
- 게시판 분류 표시 조건 `show_category` → `hide_categories` 로 변경
- 게시판 URL `/board/free` → `/free` 단축 URL 기본 사용
- 관리자 topbar "Admin" → 실제 로그인 이름 + 프로필 사진

### Fixed
- `rzx_users.role` 컬럼 없음 → `$_SESSION['admin_id']` 기반 관리자 판별
- `rzx_users.nick_name` 컬럼 없음 → `name` 사용 + Encryption 복호화
- `rzx_staff.profile_image` → `avatar` 컬럼명 수정
- Service 모델 `category_id` int→string 타입 캐스팅
- 예약 조회 이메일/전화번호 평문 검색 (암호화 미적용 데이터)

---

## [1.6.1] - 2026-03-18

### Added
- **게시판 관리자 탭 6개** — 기본 설정, 분류 관리, 확장 변수, 권한 설정, 추가 설정, 스킨
- **기본 설정 탭 통합** — 기본정보 + SEO + 레이아웃 선택 + 스킨 선택 + 표시 + 목록 + 고급 (접기/펼치기)
- **공통 컴포넌트** — `components/board/section-*.php` 8개 (basic, seo, layout-select, skin-select, display, list, advanced, js), 수정/생성 페이지 재사용
- **다국어 입력 컴포넌트** — `rzx_multilang_input()` 함수, text/editor 모드, 인라인 지구본 버튼
- **분류 관리 강화** — 트리 구조(parent_id), 편집 모달(이름/폰트색/설명/허용그룹/펼침/기본분류), SortableJS 드래그 정렬, 회원등급 DB 동적 로드
- **확장 변수 탭** — `rzx_board_extra_vars` 테이블, 15개 입력 타입 (text_multilang, textarea_multilang, textarea_editor 포함), 다국어 지원, 드래그 정렬
- **권한 설정 탭** — 접근 권한 (all/member/grade:*/admin_staff/admin), 모듈 관리자 (공용 사용자 검색, 프로필 사진, 권한 범위), 알림 메일 안내
- **추가 설정 탭** — 통합 게시판, 문서(히스토리/추천/신고), 댓글(수/깊이/승인/추천/신고), 위지윅 에디터(권한 매트릭스), 파일(다운로드 그룹), 피드(RSS 공개/저작권)
- **스킨 시스템** — `skin.json` 포맷, `SkinConfigRenderer`, `"multilang": true` 지원, 스킨 정보 카드
- **레이아웃 시스템** — `layout.json` 포맷, 기본 설정에서 카드형 선택
- **공용 사용자 검색** — `user-search.php` 컴포넌트, 암호화 복호화, 프로필 사진, 어디서든 재사용
- **모듈 관리자** — `rzx_board_admins` 테이블, 문서/댓글/설정 권한, 알림 수신
- **고급 설정 확장** — 관리자 익명 제외, Data URL 제한, 유니코드 오남용, 글/댓글 보호(댓글수/대댓글수/기간), 최고관리자 보호, 발신 메일 주소
- **토글 스위치 UI** — 모든 예/아니오 항목 토글 변환 (기본 + 추가 설정)
- **번역 키** — 약 300개 키, 13개 언어

### Fixed
- **휴지통 SQL 오류** — `LIMIT ?` 바인딩 문자열 → 정수 리터럴
- **Summernote 미렌더링** — jQuery/Summernote JS 로드 + 모달 표시 후 초기화
- **콜레이션 오류** — board_admins JOIN 시 COLLATE utf8mb4_unicode_ci 명시
- **접기/펼치기 미동작** — `toggleSection` 함수를 `<head>`에서 먼저 정의

### Changed
- 독립 탭(목록/고급) → 기본 설정 탭에 접기/펼치기 통합
- 스킨 선택 → 기본 설정 탭으로 이동, 스킨 탭은 설정만 표시
- 관리자 메일 → 고급 설정의 "발신 메일 주소"로 변경
- 모듈 관리자 → 권한 설정 탭으로 이동
- "목록에 분류 표시" → 분류 설정에서 제거, 스킨별 설정으로 이관
- "목록에 분류 표시" → 분류 설정에서 제거, 스킨별 설정으로 이관
- 작성 허용 그룹 → 하드코딩 대신 `rzx_member_grades` 테이블에서 동적 로드

---

## [1.6.0] - 2026-03-17

### Added
- **다국어 폴백 시스템**: `db_trans()` 함수에 내장 폴백 체인 (설정언어 → en → 기본언어 → source_locale → 아무 번역)
- **게시판 설정 다국어 버튼**: 기본 설정 탭 6개 필드에 다국어(지구본) 버튼 추가
- **게시판 프론트 뷰 다국어 폴백**: `_init.php`에서 title, description, SEO, header/footer 다국어 적용
- **위젯 다국어 폴백**: `WidgetRenderer::t()`, `tBtn()` 빈 문자열 처리 개선
- PWA 번역 키 추가 (13개 언어: `admin.pwa.update_available`, `update`, `later`)

### Fixed
- **Features 위젯 빈 카드 문제**: `??` 연산자가 빈 문자열 `""`을 유효값으로 처리 → `!empty()` 기반 폴백으로 수정
- **프로덕션 Mixed Content 오류**: `APP_URL` http→https 수정 + JS 프로토콜 자동 보정

---

## [1.5.2] - 2026-03-17

### Added
- POS 서비스 내역에서 서비스 삭제 기능 (삭제 버튼 + API + DOM 즉시 갱신)
- 13개 언어 서비스 삭제 관련 번역 키 추가

---

## [1.5.0] - 2026-03-17

### Added
- **키오스크 접수 시스템**: 서비스 확인 → 고객 정보 → 예약 생성 → 대기번호 발행
- **스태프 가용 상태 배지**: 키오스크 스태프 선택에서 이용중/가능 실시간 표시
- **국제전화번호 모듈**: 키오스크 접수 폼에 phone-input 컴포넌트 적용
- **예약 샘플 데이터 시드**: `database/seed_reservations.php` (POS/키오스크/온라인 10건)
- 키오스크 공통 파일 분리 (`_styles.php`, `_bg.php`, `_scripts.php`)
- 13개 언어 키오스크 번역 키 11개 추가

### Changed
- 키오스크 서비스 선택: 스태프 스킬 기반 필터링 (지명모드)
- 키오스크 카테고리 탭 중앙 정렬

### Fixed
- 프로덕션 관리자 로그인 불가 (비밀번호 해시 손상 - bash $ 변수 치환 문제)
- **업데이트 시스템 안정화**: 보존 목록 확장, 헬스체크, DB 버전 동기화
- 버전 단일 소스 통합 (`version.json` → index.php, DB 자동 동기화)

---

## [Unreleased]

### Added - 2026-03-11

#### 마이페이지 공통 사이드바 컴포넌트

**공통 컴포넌트** (`resources/views/components/mypage-sidebar.php`)
- 6개 마이페이지(dashboard, profile, settings, password, reservations, messages, withdraw)에서 공유
- 메뉴 데이터 1곳 중앙 관리 (메뉴 추가/삭제 시 1곳만 수정)
- 스킨 오버라이드: `skins/member/{스킨명}/components/mypage-sidebar.php` 자동 감지
- 스킨이 없으면 기본 디자인 자동 적용

**스킨 오버라이드 샘플** (`skins/member/modern/components/mypage-sidebar.php`)
- 그래디언트 active 상태, 둥근 사각형 프로필, 호버 애니메이션

**사용법**: 각 페이지에서 `$sidebarActive` 설정 후 include
```php
$sidebarActive = 'profile';
include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
```

#### 회원 탈퇴 소프트 삭제 + 개인정보 익명화

**Auth::deleteAccount()** 재구현
- 기존 하드 삭제(DELETE) → 소프트 삭제(UPDATE) + 개인정보 익명화
- 이름 → `탈퇴회원_{hash}`, 이메일 → `withdrawn_{hash}@deleted.local`
- 전화번호, 생년월일, 성별, 프로필 사진, 비밀번호 → NULL
- 예약 기록의 고객 정보도 익명화 (예약 기록 자체는 유지)
- `status` → `withdrawn`, `deleted_at` 기록

**DB 변경**
- `rzx_users.deleted_at` TIMESTAMP 컬럼 추가
- `rzx_users.withdraw_reason` VARCHAR(500) 컬럼 추가

**법적 근거**
- 한국 국세기본법: 결제/매출 기록 5년 보관
- 일본 法人税法: 결제/매출 기록 7년 보관
- 개인정보보호법/個人情報保護法: 목적 달성 후 지체 없이 파기 (익명화로 충족)

**탈퇴 안내 문구 상세화** (13개 언어)
- 개인정보 즉시 익명화, 예약 사전 취소, 결제 기록 법적 보관, 계정 복구 불가
- 소셜 로그인 연결 해제, 메시지/알림 삭제, 법정 보관 기간 안내

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `resources/views/components/mypage-sidebar.php` | 신규 | 마이페이지 공통 사이드바 컴포넌트 |
| `skins/member/modern/components/mypage-sidebar.php` | 신규 | modern 스킨 사이드바 오버라이드 |
| `resources/views/customer/mypage/profile.php` | 수정 | 공통 사이드바로 교체 |
| `resources/views/customer/mypage/settings.php` | 수정 | 공통 사이드바로 교체 |
| `resources/views/customer/mypage/withdraw.php` | 수정 | 공통 사이드바로 교체 |
| `resources/views/customer/mypage/password.php` | 수정 | 인라인 사이드바 → 공통 컴포넌트 교체 |
| `resources/views/customer/mypage/reservations.php` | 수정 | 인라인 사이드바 → 공통 컴포넌트 교체 |
| `resources/views/customer/mypage/messages.php` | 수정 | 인라인 사이드바 → 공통 컴포넌트 교체 |
| `rzxlib/Core/Auth/Auth.php` | 수정 | deleteAccount() 소프트 삭제 + 익명화 |
| `resources/lang/*/auth.php` (13개) | 수정 | withdraw 번역 상세화 + retention_notice 추가 |

---

### Added - 2026-03-10

#### 스태프 관리 시스템

**스태프 목록 페이지** (`resources/views/admin/staff.php`)
- 스태프 CRUD API (create/update/delete/toggle)
- 통계 카드 (전체/활성 스태프 수)
- 테이블 리스트 (이름, 연락처, 담당서비스, 활성토글, 수정/삭제)

**스태프 모달 폼** (`resources/views/admin/staff-form.php`)
- 이름(필수), 이메일, 전화, 소개, 담당서비스(체크박스), 활성상태

**스태프 JS** (`resources/views/admin/staff-js.php`)
- openStaffModal, editStaff, saveStaff, deleteStaff, toggleStaff

**스태프 설정** (`resources/views/admin/staff/settings.php`)
- 호칭 선택 (디자이너/스태프/테라피스트/강사/컨설턴트)
- 예약 시 스태프 선택 필수 여부
- 소개/사진 표시 여부
- 자동 배정 (없음/라운드로빈/최소 바쁜)

**사이드바 메뉴** (`resources/views/admin/partials/admin-sidebar.php`)
- 스태프 드롭다운 메뉴 (목록 + 설정 서브메뉴)

**라우팅** (`index.php`)
- `staff/settings` 경로 추가

**13개 언어 번역**: staff 관련 40+ 키 (nav, 목록, 설정, 필드, 에러 등)

#### 서비스 통화/가격 설정 적용

- `service_currency` (KRW/USD/JPY/EUR/CNY) 설정을 전 페이지에 적용
- `service_price_display` (show/hide/contact) 설정을 전 페이지에 적용
- 적용: 관리자 서비스 목록, 고객 서비스 목록/상세, 예약 페이지
- 기본 통화를 JPY(¥)로 변경

#### 서비스 다국어 버튼 수정

- `migrateServiceMultilangKeys`: 잘못된 API 엔드포인트를 `/api/translations?action=rename`으로 수정
- `migrateCategoryMultilangKeys`: 동일 수정

#### 파일 변경 내역 (스태프/서비스)

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `resources/views/admin/staff.php` | 신규 | 스태프 목록 CRUD |
| `resources/views/admin/staff-form.php` | 신규 | 스태프 모달 폼 |
| `resources/views/admin/staff-js.php` | 신규 | 스태프 JS |
| `resources/views/admin/staff/settings.php` | 신규 | 스태프 설정 |
| `resources/views/admin/partials/admin-sidebar.php` | 수정 | 스태프 메뉴 추가 |
| `index.php` | 수정 | staff/settings 라우팅 |
| `resources/views/admin/services.php` | 수정 | 통화/가격 설정 적용 |
| `resources/views/admin/services-js.php` | 수정 | 다국어 API 수정 |
| `resources/views/admin/services/settings-categories.php` | 수정 | 다국어 API 수정 |
| `resources/views/customer/services.php` | 수정 | 통화/가격 설정 적용 |
| `resources/views/customer/services/detail.php` | 수정 | 통화/가격 설정 적용 |
| `resources/views/customer/booking.php` | 수정 | 통화/가격 설정 적용 |
| `resources/lang/*/admin.php` (13개) | 수정 | 스태프 번역 키 추가 |

#### 메뉴 관리 시스템 (Rhymix 호환)

**4단 캐스케이딩 패널 UI** (`resources/views/admin/site/menus.php`)
- Rhymix 메뉴 시스템과 동일한 4단 패널 구조: 트리 | 컨텍스트 | 메뉴타입/편집 | 상세폼
- HTML5 네이티브 Drag & Drop으로 메뉴 순서 변경
- 3구간 드롭 판정: 위(25%), 내부(50%), 아래(25%)
- 트리 접기/펼치기 토글
- 메뉴 검색 기능 (검색 시 트리 자동 펼침)
- Toast 알림 시스템

**메뉴 API** (`resources/views/admin/site/menus-api.php`)
- `add_sitemap` / `rename_sitemap` / `delete_sitemap`: 사이트맵 CRUD
- `add_menu_item` / `update_menu_item` / `rename_menu_item` / `delete_menu_item`: 메뉴 항목 CRUD
- `toggle_home`: 홈 메뉴 지정/해제
- `move_menu_item`: 드래그&드롭 이동
- `copy_menu_item`: 서브트리 재귀 복사 (`copyChildrenRecursive`)
- `reorder_menu_items`: 형제 순서 일괄 업데이트
- `deleteMenuItemRecursive`: 자식 포함 재귀 삭제

**Rhymix 호환 기능**
- 복사 / 잘라내기 / 붙여넣기 (JS 클립보드 기반)
- 사이트맵 편집 (패널3 UI)
- 접근 권한 설정 (`group_srls`)
- 새 창 열기 (`open_window`)
- 하위 메뉴 확장 기본값 (`expand`)
- 홈 메뉴 삭제 방지

**드래그&드롭 분리** (`resources/views/admin/site/menus-dnd.php`)
- dragstart/dragend/dragover/dragleave/drop 이벤트
- 자기 자식으로 드롭 방지 (`isDesc`)
- 형제 순서 재계산 → `reorder_menu_items` API 호출

#### 관리자 TopBar 공유 컴포넌트 적용

- `design.php`, `pages.php`: 인라인 TopBar → 공유 `admin-topbar.php` 컴포넌트로 교체
- 13개 언어 선택기 자동 포함

#### .htaccess 라우팅 수정

- `admin/` 물리 디렉토리 존재 시 rewrite 우회 문제 해결
- `RewriteRule ^admin(/.*)?$ index.php [L,QSA]` 추가

#### 13개 언어 번역 키 추가

메뉴 관리 관련 14개 키를 모든 언어 파일에 추가:
`cut`, `copied`, `cannot_delete_home`, `sitemap_name`, `design_bulk_desc`, `layout`, `select_layout`, `default_layout`, `apply`, `open_window`, `expand_default`, `access_permission`, `help_access`, `access_placeholder`

#### DB 스키마

**rzx_sitemaps**: `id`, `title`, `slug`, `sort_order`, `created_at`, `updated_at`

**rzx_menu_items**: `id`, `sitemap_id`, `parent_id`, `title`, `url`, `target`, `icon`, `css_class`, `description`, `menu_type`, `sort_order`, `is_home`, `is_shortcut`, `open_window`, `expand`, `group_srls`, `is_active`, `created_at`, `updated_at`

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `resources/views/admin/site/menus.php` | 수정 | 4단 패널 UI, DnD, 트리 토글 |
| `resources/views/admin/site/menus-js.php` | 수정 | 클립보드, 사이트맵 편집, 폼 확장 |
| `resources/views/admin/site/menus-dnd.php` | 신규 | 드래그&드롭 전용 JS |
| `resources/views/admin/site/menus-api.php` | 수정 | copy, reorder, expand/group_srls 지원 |
| `resources/views/admin/site/design.php` | 수정 | 공유 TopBar 적용 |
| `resources/views/admin/site/pages.php` | 수정 | 공유 TopBar 적용 |
| `.htaccess` | 수정 | admin 디렉토리 rewrite 강제 |
| `resources/lang/*/admin.php` (13개) | 수정 | 메뉴 관리 번역 키 14개 추가 |

---

### Added - 2026-03-09

#### 모듈 기반 아키텍처 도입

**LanguageModule** (`rzxlib/Core/Modules/LanguageModule.php`)
- admin-topbar.php와 동일한 언어 데이터 구조를 중앙 모듈로 분리
- `getData($siteSettings, $currentLocale)`: DB에서 활성 언어 목록 반환
- 기본 13개 언어 + 커스텀 언어 병합
- 반환값: `languages`, `allLanguages`, `supportedCodes`, `currentLocale`, `defaultLocale`, `currentLangInfo`

**LogoModule** (`rzxlib/Core/Modules/LogoModule.php`)
- 사이트 로고/이름 설정을 중앙 모듈로 분리
- `getData($siteSettings, $appName)`: 로고 타입, 이미지, 사이트명 반환

**SocialLoginModule** (`rzxlib/Core/Modules/SocialLoginModule.php`)
- 소셜 로그인 설정을 중앙 모듈로 분리
- `getData($siteSettings)`: 활성화된 소셜 제공자 목록 반환

**공용 언어 선택기 컴포넌트** (`resources/views/components/language-selector.php`)
- admin-topbar.php와 동일한 UI의 언어 선택 드롭다운
- LanguageModule을 사용하여 DB 활성 언어 표시
- `include BASE_PATH . '/resources/views/components/language-selector.php';` 한 줄로 사용
- HTML + JS 포함, 어느 페이지든 include 가능

**MemberSkinLoader 모듈 통합** (`rzxlib/Core/Skin/MemberSkinLoader.php`)
- `setSiteSettings()`: DB 설정 주입
- `collectModuleData()`: Language/Logo/SocialLogin 모듈 데이터 자동 수집
- `mergeModuleData()`: 모듈 데이터를 스킨 템플릿에 자동 주입
- `$siteSettings`를 스킨 템플릿에 전달 (공용 컴포넌트 지원)
- 로케일 감지에서 하드코딩 3개 언어 제한 제거

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `rzxlib/Core/Modules/LanguageModule.php` | 신규 | 언어 데이터 중앙 모듈 |
| `rzxlib/Core/Modules/LogoModule.php` | 신규 | 로고 데이터 중앙 모듈 |
| `rzxlib/Core/Modules/SocialLoginModule.php` | 신규 | 소셜 로그인 중앙 모듈 |
| `resources/views/components/language-selector.php` | 신규 | 공용 언어 선택기 |
| `rzxlib/Core/Skin/MemberSkinLoader.php` | 수정 | 모듈 통합, siteSettings 전달 |
| `skins/default/components/header.php` | 수정 | 공용 언어 선택기 사용 |
| `skins/member/modern/components/header.php` | 수정 | 공용 언어 선택기 사용 |
| `skins/member/modern/login.php` | 수정 | 헤더 경로 modern 우선 |
| `skins/member/modern/register.php` | 수정 | 헤더 경로 modern 우선 |
| `skins/member/modern/password_reset.php` | 수정 | 헤더 경로 modern 우선 |
| `skins/member/modern/mypage.php` | 수정 | 헤더 경로 modern 우선 |
| `resources/views/customer/login.php` | 수정 | setSiteSettings() 호출 추가 |
| `resources/views/customer/register.php` | 수정 | setSiteSettings() 호출 추가 |
| `resources/views/customer/forgot-password.php` | 수정 | setSiteSettings() 호출 추가 |
| `resources/views/customer/reset-password.php` | 수정 | setSiteSettings() 호출 추가 |

---

### Added - 2026-03-07

#### 다국어 시스템 개선

**Translator 클래스 업데이트** (`rzxlib/Core/I18n/Translator.php`)
- DB에서 언어 설정 로드 (`loadLanguageSettings()`)
  - `supported_languages`: 지원 언어 목록
  - `custom_languages`: 커스텀 언어 정보
  - `default_language`: 기본 언어
  - `language_auto_detect`: 자동 감지 활성화 여부
- 새 메서드 추가:
  - `getAllLanguages()`: 전체 언어 정보 (기본 + 커스텀)
  - `isAutoDetect()`: 자동 감지 활성화 여부
  - `getFallbackLocale()`: 폴백 로케일
  - `hasLanguageFiles($locale)`: 번역 파일 존재 여부
  - `getLanguageFiles($locale)`: 언어별 번역 파일 목록
  - `getTranslationGroups()`: 전체 번역 그룹 목록
  - `reload()`: 설정 다시 로드
- `detectLocale()` 개선:
  - `autoDetect` 설정 반영
  - 지역 코드 포함 언어 지원 (예: `zh-CN` → `zh_CN`)

**관리자 언어 설정 페이지** (`/admin/settings/language`)
- 언어 자동 선택 활성화/비활성화
- 지원 언어 체크박스 선택
- 기본 언어 선택
- 커스텀 언어 추가 (언어 코드 + 네이티브 이름)
- 커스텀 언어 삭제 (기본 제공 언어는 삭제 불가)

**관리자 번역 관리 페이지** (`/admin/settings/translations`)
- 지원 언어별 번역 파일 상태 표시
- 번역 파일 생성 (원본 언어 선택하여 복사)
- 번역 편집 기능 (키-값 편집, 원본 비교)
- 중첩 배열 키 지원

#### 파일 변경 내역

| 파일 | 변경 유형 | 설명 |
|------|----------|------|
| `rzxlib/Core/I18n/Translator.php` | 수정 | DB 기반 언어 설정 로드 |
| `resources/views/admin/settings/language.php` | 수정 | 언어 설정 UI 개선 |
| `resources/views/admin/settings/translations.php` | 신규 | 번역 관리 페이지 |
| `resources/views/admin/settings/_settings_nav.php` | 수정 | 번역 관리 탭 추가 |
| `routes/web.php` | 수정 | 번역 관리 라우트 추가 |
| `rzxlib/Http/Controllers/Admin/SettingsController.php` | 수정 | 번역 관리 메서드 추가 |
| `docs/I18N.md` | 수정 | 새 기능 문서화 |

---

## 관리자 설정 페이지 구조

```
/admin/settings/
├── general        # 관리자 접속 경로
├── site           # 사이트 기본 설정
├── mail           # 메일 설정
├── language       # 언어 설정 (NEW: 커스텀 언어 추가)
├── translations   # 번역 관리 (NEW)
├── seo            # SEO 설정
├── pwa            # PWA 설정
└── system/        # 시스템 설정
    ├── info       # 정보관리
    ├── cache      # 캐시관리
    ├── mode       # 모드관리
    ├── logs       # 로그관리
    └── updates    # 업데이트 관리
```

---

## DB 설정 키 (rzx_settings)

### 언어 관련

| 키 | 타입 | 설명 |
|----|------|------|
| `supported_languages` | JSON | 지원 언어 코드 배열 |
| `custom_languages` | JSON | 커스텀 언어 정보 객체 |
| `default_language` | string | 기본 언어 코드 |
| `language_auto_detect` | string | 자동 감지 (`1`/`0`) |

### 예시 데이터

```sql
-- 지원 언어
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('supported_languages', '["ko","en","ja"]');

-- 커스텀 언어
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('custom_languages', '{"th":{"name":"ไทย","native":"ไทย","builtin":false}}');

-- 기본 언어
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('default_language', 'ko');

-- 자동 감지
INSERT INTO rzx_settings (`key`, `value`)
VALUES ('language_auto_detect', '1');
```

---

## 버전 정보

- **문서 버전**: 1.2.0
- **최종 수정일**: 2026-03-11
