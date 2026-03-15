# RezlyX 프로젝트 계획

## 프로젝트 개요

RezlyX는 레거시 PHP 4.x 예약 시스템(view.back.jp)을 현대적으로 재구축한 예약 관리 시스템입니다.

- **기술 스택**: PHP 8.x + MySQL + Tailwind CSS + Vanilla JS
- **다국어**: 13개 언어 (ko, en, ja, zh_CN, zh_TW, de, es, fr, mn, ru, tr, vi, id)
- **개발 서버**: `http://localhost/rezlyx/` → `E:\xampp\htdocs\project\rezlyx`
- **관리자 경로**: `/theadmin` (DB `rzx_settings.admin_path`)

---

## 완료된 작업

### 2026-03-15: POS 현장 관리 시스템 + 예약 API 백엔드

- [x] **POS 페이지** (`/admin/reservations/pos`) — 당일 현장 관리 화면
  - 3:1 레이아웃 (이용중/대기 고객 카드 + 3탭 패널: 당일접수, 대기자, 예약자)
  - 고객 카드: 진행률 바, 시간 표시, 상태 배지, 결제 상태 배지
  - 카드 액션 버튼: 진행(startService) / 결제(openPayment) / 완료(complete) / 취소
  - 실시간 시계 + 60초 자동 새로고침 (모달 열림 시 일시중지)
  - ESC 키 모달 닫기 (결제 → 접수 → 상세 우선순위)
- [x] **당일 접수 모달** — reservation-form.php 공용 컴포넌트 재사용
  - `source = walk_in` hidden 필드로 현장접수 구분
  - `rzxCalCloseAdd()` 호환 함수 (캘린더 취소 버튼 호환)
  - 더블 클릭 방지 (제출 시 버튼 비활성화)
  - 등록 후 POS 페이지로 리다이렉트
- [x] **결제 모달** — 총 금액, 결제완료, 잔액, 결제금액, 결제방법(카드/현금/이체)
- [x] **예약 API 백엔드** (`_api.php`)
  - 예약 생성 POST (`store`) — `service_ids[]` → 개별 예약 생성, source 지원
  - 예약 수정 POST (`update`) — 서비스/일시/고객/금액 수정
  - 상태 변경 POST (`confirm`, `cancel`, `complete`, `no-show`) — AJAX JSON 응답
  - 서비스 시작 POST (`start-service`) — 현재 시간으로 start_time 재설정, duration 기반 end_time 계산
  - 결제 처리 POST (`payment`) — 누적 paid_amount, 자동 payment_status (unpaid/partial/paid)
- [x] **DB 마이그레이션**
  - `013_add_source_to_reservations.sql` — `source VARCHAR(20) DEFAULT 'online'`
  - `014_add_payment_status_to_reservations.sql` — `payment_status`, `paid_amount`
- [x] **서비스 설정 추가** — 당일 예약 가능 여부(`booking_same_day`) 토글
- [x] **캘린더 연동** — 캘린더에서 예약 추가 시 reservation-form.php 공용 컴포넌트 사용
- [x] **대시보드 캘린더** — 대시보드에 미니 캘린더 위젯 추가
- [x] **스태프 번역 누락 수정** — `staff.fields.banner/no_banner/upload_banner/remove` 13개 언어 추가
- [x] **예약 번역** — `reservations.php` POS 관련 40+ 키 13개 언어 추가
- [x] **서비스 번역** — `services.php` 예약 시간 제한 키 13개 언어 추가

### 2026-03-14: 언어 파일 페이지별 분리 (admin.php → 8개 파일)

- [x] **admin.php 분리**: 2190줄 → 공통(~100줄) + 7개 페이지별 파일
  - `admin.php` — 공통 (title, nav, stats, users, buttons, messages, common, languages)
  - `settings.php` — 설정 (사이트, 메일, 언어, SEO, PWA)
  - `system.php` — 시스템 (정보, 캐시, 모드, 로그, 업데이트)
  - `services.php` — 서비스, 카테고리, 시간대
  - `reservations.php` — 예약
  - `members.php` — 회원 (설정, 목록, 그룹, 적립금)
  - `staff.php` — 스태프 (설정, 스케줄, 근태, 사진편집)
  - `site.php` — 사이트 관리 (메뉴, 디자인, 페이지, 위젯)
- [x] **13개 언어 모두 분리** — 104개 파일 생성/수정
- [x] **뷰 파일 __() 호출 일괄 변경** — 1954건 (admin.settings.→settings., admin.staff.→staff. 등)
- [x] **mn/tr 누락 system 섹션 추가** — 전체 system 번역 추가
- [x] **번역 호출 규칙**: `__('파일명.키')` — 첫 번째 점 앞이 파일명

### 2026-03-14: 스태프 상세 페이지 월간 캘린더 + 배너 + 슬롯 조회

- [x] **DB 스키마**: `rzx_staff`에 `banner`, `gallery` 컬럼 추가
- [x] **관리자 스태프 모달**: 배너 이미지 업로드/삭제 UI + 서버 처리 추가
- [x] **staff-detail.php 리뉴얼** — 배너 + 프로필 + 월간 캘린더 + 시간 슬롯
  - 배너 이미지 (풀폭, 아바타 오버랩)
  - 월간 캘린더 AJAX (`get_month_schedule`) — 오버라이드 → 주간 스케줄 → 영업시간 폴백
  - 날짜 클릭 → 슬롯 AJAX (`get_day_slots`) — 예약 충돌 체크 포함
  - 슬롯 클릭 → `/booking?staff=X&date=Y&time=Z` 이동
- [x] **staff-detail-js.php 생성** — 캘린더 렌더링, 월 이동, 슬롯 조회 JS
- [x] **booking-js.php 수정** — `date`, `time` URL 파라미터 자동 선택 추가
- [x] **index.php 라우팅 수정** — `staff/{id}` 동적 라우트 패턴 추가 (파일 기반 라우팅)
- [x] **13개 언어 번역** — `schedule_calendar`, `no_available_slots`, `selected_date_slots` 키 추가

### 2026-03-14: 프론트 스태프 페이지 리뉴얼 + 지명예약 플로우

- [x] **Stage 1: 스태프 목록 페이지 (HIRO GINZA 스타일)**
  - `staff.php` 전면 재작성 — 4컬럼 그리드, 세로형 사진(3:4), 직책 필터
  - 이름 + 서브네임(영문/후리가나), 지명료 표시, 담당 서비스
  - "지명예약" + "스케줄" 듀얼 버튼 (booking?staff=ID 연동)
- [x] **Stage 2: 스태프 상세 페이지**
  - `staff-detail.php` 신규 — 프로필 헤더 + 주간 스케줄(7일) + 서비스 메뉴 목록
  - 서비스별 "선택하기" 버튼 → `/booking?staff=ID&service=SVC_ID`
  - 라우트 추가 (`/staff/{id}`)
- [x] **Stage 3: 지명예약 플로우 (URL 파라미터 사전 선택)**
  - `booking-js.php`에 `handleUrlPreselection()` 추가
  - `?staff=X` → 스태프 자동 선택, `?service=Y` → 서비스 자동 선택
  - 서비스+스태프 선택 시 날짜/시간 스텝으로 자동 이동
  - 서비스만 선택 시 스태프 선택 스텝으로 이동
- [x] **13개 언어 번역** — `staff_page.php` 19개 키 추가 (전체 언어)

### 2026-03-14: 프로덕션 데이터 마이그레이션 + 설치 프로그램 수정

- [x] `database/import_data.php` — PHP 기반 데이터 임포트 스크립트 (토큰 인증)
  - ALTER TABLE 스키마 업그레이드 (누락 컬럼 자동 추가)
  - 272개 SQL 문 0 에러로 성공
- [x] `002_create_additional_tables.sql` — 누락 17개 테이블 추가
- [x] `install/steps/process.php` — glob 패턴으로 전체 마이그레이션 파일 자동 실행

### 2026-03-13: 관리자 권한 시스템 Phase 1-6

- [x] `rzx_admins` 테이블 스키마 (user_id, staff_id, role, permissions JSON, status)
- [x] `AdminAuth.php` 정적 클래스 — 인증, 권한 체크, 슈퍼바이저 보호
- [x] 관리자 로그인 페이지 수정 (세션 분리, 리다이렉트 수정, error_log 수정)
- [x] `index.php` 미들웨어 — 인증 체크 → 권한 체크 → 403 페이지
- [x] 사이드바 `AdminAuth::can()` 기반 메뉴 필터링
- [x] 관리자 관리 UI (`staff/admins.php` + `admins-js.php` + `admins-perm-checkboxes.php`)
- [x] 설치 마이그레이션에 `rzx_staff_positions`, `rzx_staff` 테이블 추가
- [x] 슈퍼바이저(dongwhahn@gmail.com) master 등록 완료
- [x] 403 Forbidden 페이지 (`resources/views/admin/403.php`)

### 2026-03-13: 파일 기반 위젯 시스템 Phase 2 + 썸네일 + 양방향 동기화

- [x] **Phase 2-1: 관리자 진입 시 DB 자동 동기화**
  - `WidgetLoader::syncToDatabase` 양방향 동기화 (새 폴더→DB 등록, 삭제된 폴더→DB 제거)
  - builtin 위젯만 자동 제거, custom 위젯은 유지
- [x] **Phase 2-2: 위젯 빌더 팔레트 widget.json 다국어 연동**
- [x] **Phase 2-3: 위젯 관리 페이지 WidgetLoader 통합**
  - 파일 기반 배지, 버전, 썸네일, 삭제 보호
- [x] **Phase 2-4 마켓플레이스**: 추후 진행으로 보류
- [x] **위젯 썸네일 시스템**
  - 각 위젯 폴더에 `thumbnail.png` (320x180) 자동 생성
  - 위젯 관리 페이지: 카드 상단에 큰 프리뷰 이미지 표시
  - 위젯 빌더 팔레트 플라이아웃: 카드에 aspect-video 썸네일 표시
- [x] **양방향 동기화 + 13개 언어 번역 추가** (marketing 카테고리, file_based 타입 등)

### 2026-03-13: 위젯 빌더 — 히어로 비디오 + 리치텍스트 편집 + CSS/JS 관리

- [x] **비디오 표시 개선**
  - YouTube/Vimeo URL 자동 감지 → iframe embed 변환 (`extractYouTubeId`, `extractVimeoId`)
  - 비디오 영역 꽉차게 표시 (`width:max(100%,177.78vh); height:max(100%,56.25vw)`)
  - z-index 레이어링: z-[1] 비디오 → z-[5] 오버레이 → z-20 콘텐츠
  - 비디오 업로드 영역과 URL 영역 분리, 업로드 우선 적용
- [x] **히어로 레이아웃 수정**
  - 가운데 정렬 버그 수정 (max-w 제거, flex 자식에 w-full 추가)
  - 히어로 하단 그라데이션 제거
  - 위젯 CSS 스코핑 (`scopeWidgetCss`) — body/html/*/`:root` 셀렉터 자동 치환
- [x] **리치텍스트 편집 (Summernote 모달)**
  - 텍스트 위젯 content 필드 → `richtext` 타입 변경
  - 모든 언어(기본 로케일 포함) textarea + 편집 버튼 → 모달 Summernote 에디터
  - 모달 드래그 이동 / 리사이즈 / 더블클릭 전체화면 토글
  - jQuery + Summernote CDN 로드, 다크 모드 지원
- [x] **CSS/JS 코드 에디터**
  - 텍스트 위젯에 `custom_css`, `custom_js` 필드 추가 (type: `code`)
  - 다크 배경 monospace textarea, 언어 배지(CSS/JS), Tab 들여쓰기 지원
  - WidgetRenderer: CSS는 위젯 ID로 자동 스코핑, JS는 IIFE 래핑

### 2026-03-13: 위젯 빌더 리팩토링 — 패널 통합 + 인라인 편집 + 그리드 드래그

- [x] **Stage 1: 패널 재구성**
  - `#editSidePanel` 왼쪽→오른쪽 이동, `#configPanel` 제거
  - btn-config(톱니바퀴) → 오른쪽 편집 패널, btn-edit(연필) → 인라인 편집
  - configPanel 관련 JS 코드 전부 제거 (openConfigPanel, closeConfigPanel 등)
  - 팔레트 항상 표시 (showEditPanel/hideEditPanel에서 팔레트 토글 제거)
  - AJAX 핸들러를 `pages-widget-builder-ajax.php`로 분리
- [x] **Stage 2: 인라인 텍스트 편집**
  - `pages-widget-builder-inline-js.php` 생성 (WBInline 모듈)
  - 연필 클릭 → iframe 숨기고 위젯 HTML 직접 렌더링 → contenteditable 적용
  - WidgetRenderer에 `data-widget-field` 속성 추가 (hero, cta, text 위젯)
  - 하단 Save/Cancel 오버레이 바, ESC 취소
- [x] **Stage 3: 그리드 스냅 드래그**
  - `pages-widget-builder-grid-js.php` 생성 (WBGrid 모듈)
  - 3x3 그리드 오버레이 + 파란 원형 드래그 핸들
  - 마우스 드래그 → 가장 가까운 셀 하이라이트 → 드롭 시 위치 저장
  - `element_positions` config 필드에 위치 저장 (하위 호환)
  - WidgetRenderer에 `elementPositionStyle()` 메서드 추가, hero center 레이아웃에 적용

### 2026-03-13: 통합 다국어 버튼 컴포넌트

- [x] `multilang-button.php` 생성 — `rzx_multilang_btn()` PHP 함수 + `RZX_MULTILANG_BTN()` JS 함수
- [x] 글로브(지구본) 아이콘 `w-4 h-4`로 통일, 한 곳만 수정하면 전체 적용
- [x] 기존 3종(번역 아이콘/글로브/대륙) 버튼을 통합 컴포넌트로 교체
  - settings.php (4개), settings/site.php (4개), services-form.php (2개)
  - category-modal.php (2개), terms.php (2개), menus.php (2개)
  - staff/index-js.php (2개), staff/settings.php (1개)
- [x] `multilang-modal.php`에서 자동 include (기존 include 지점 활용)
- [x] `iCls` 변수 자기참조 버그 수정 (widget-builder-edit-js.php)

### 2026-03-13: WYSIWYG 위젯 빌더 업그레이드

- [x] 위젯 빌더를 WYSIWYG 방식으로 전면 리팩토링
  - iframe 기반 실시간 미리보기 (WidgetRenderer AJAX 렌더링)
  - 브라우저 뷰포트 스타일 캔버스 (실제 페이지와 동일한 모습)
  - 호버 오버레이 툴바 (드래그, 위/아래 이동, 설정, 삭제)
  - config_schema 기반 설정 패널 (text, textarea, number, color, toggle, select 지원)
  - 설정 변경 시 즉시 미리보기 갱신
- [x] 내장 위젯 8종 config_schema DB 업데이트 (hero, services, cta, text, spacer 등)

### 2026-03-12: 위젯 시스템 (드래그앤드롭 페이지 빌더 + 위젯 관리)

- [x] DB: `rzx_widgets` (위젯 레지스트리), `rzx_page_widgets` (배치 인스턴스), `rzx_widget_marketplace` (마켓) 테이블 생성
- [x] 내장 위젯 8종 시드: hero, features, services, stats, testimonials, cta, text, spacer
- [x] 위젯 빌더 (`/admin/site/pages/widget-builder`) — SortableJS 드래그앤드롭, AJAX 레이아웃 저장
- [x] 위젯 관리 (`/admin/site/widgets`) — 목록, 활성/비활성 토글, 삭제 (내장 위젯 삭제 방지)
- [x] 커스텀 위젯 생성 (`/admin/site/widgets/create`) — HTML/CSS/JS 템플릿 에디터, config_schema JSON
- [x] 위젯 마켓플레이스 (`/admin/site/widgets/marketplace`) — 향후 API 연동 준비 UI
- [x] `WidgetRenderer` 공통 모듈 (`rzxlib/Core/Modules/WidgetRenderer.php`) — 모든 위젯 페이지에서 재사용
  - 내장 위젯: slug → `render{Slug}()` 메서드 디스패치
  - 커스텀 위젯: `{{변수}}` 치환 기반 template 렌더링
  - 다국어: `t()` 메서드 (locale → ko → en 폴백)
- [x] 홈페이지 (`home.php`) — WidgetRenderer 통합, 위젯 없을 시 기본 콘텐츠 폴백
- [x] 사이드바에 "위젯 관리" 메뉴 추가, 라우팅 4개 추가
- [x] 13개 언어 번역 키 추가 (widget_management, site.widgets, site.widget_builder)

### 2026-03-12: 문서 페이지 시스템 (이용약관/개인정보/환불규정/데이터정책)

- [x] 문서 페이지 타입 정의 — Summernote WYSIWYG 에디터 기반 편집
- [x] 범용 문서 에디터 (`pages-document.php` + `pages-document-js.php`) — slug 기반 확장
- [x] 이용약관(`/terms`) 편집 가능 — DB 커스텀 콘텐츠 우선, 없으면 "준비 중" 표시
- [x] 개인정보처리방침(`/privacy`) 편집 가능 — 동일 패턴
- [x] 취소 환불 규정(`/refund-policy`) 편집 가능 — "기본값 불러오기" 지원
- [x] `RefundPolicyData.php` 생성 — 미용실 기준 환불 규정 샘플 (13개 언어)
- [x] 페이지 제목 → SEO `<title>` 태그에 적용 (미리보기에서는 제외)
- [x] 페이지 관리 목록에 "문서" 타입 배지 + 편집 버튼 추가
- [x] 13개 언어 번역: admin.php (terms_edit, privacy_edit, refund, document 키), customer.php (terms, privacy, refund_policy 키)

### 2026-03-12: 데이터 관리 정책 (컴플라이언스) 페이지

- [x] DB: `rzx_page_contents` 테이블 생성 (page_slug, locale, title, content, is_system, is_active)
- [x] `ComplianceData.php` 생성 - KR/JP 국가별 + 업종별 데이터 관리 가이드 데이터
- [x] 업종 그룹 분류: beauty(미용), medical(의료), food(식당), accommodation(숙박), general(기타)
- [x] 관리자 페이지 관리에 "데이터 관리 정책" 시스템 페이지 추가 (삭제 불가, 수정 가능)
- [x] 관리자 편집 UI (`/admin/site/pages/compliance`) - 기본 가이드 미리보기 + 커스텀 콘텐츠 편집
- [x] 다국어 탭 전환, 기본값 불러오기, 공개/비공개 토글
- [x] 고객용 페이지 (`/data-policy`) - 커스텀 콘텐츠 우선, 없으면 기본 데이터 자동 표시
- [x] 라우팅 연결 (index.php)
- [x] 번역 파일: `compliance.php` (13개 언어), `customer.php` (13개 언어), admin.php pages.compliance 키 추가
- [x] KR/JP 상세 지원, 기타 국가는 기본 템플릿 제공

### 2026-03-12: 시스템 설정 > 운영 국가 + 통화 단위

- [x] 시스템 설정 > 일반(`/admin/settings/general`)에 운영 국가(17개국) + 통화 단위(15개 통화) 추가
- [x] 서비스 설정 > 기본설정에서 통화 단위 제거 (시스템 설정으로 이동)

### 2026-03-12: 스태프 스케줄 관리 + 지명비 기능

- [x] DB: `rzx_staff_schedules` (주간), `rzx_staff_schedule_overrides` (날짜별) 테이블 생성
- [x] DB: `rzx_staff.designation_fee`, `rzx_reservations.designation_fee` 컬럼 추가
- [x] 설정: 스케줄 ON/OFF, 지명비 ON/OFF, 슬롯 간격(15/30/60분) 토글
- [x] 스태프 모달에 지명비 입력 필드 추가 (설정 활성 시만)
- [x] 스태프 카드에 지명비 배지 표시
- [x] 스케줄 관리 페이지 신규 (`/admin/staff/schedule`) - 주간 스케줄 + 날짜별 오버라이드
- [x] 사이드바에 스케줄 관리 메뉴 추가
- [x] 예약 페이지: `get_available_slots` AJAX API (스케줄/오버라이드/영업시간 폴백/기존 예약 충돌 제외)
- [x] 예약 페이지: 지명비 표시, 합계 반영, 저장
- [x] 13개 언어 번역 키 추가 (schedule, designation_fee 관련)

### 2026-03-12: 고객 스태프 페이지 + 스태프-회원 연동 개선

- [x] 고객용 스태프 목록 페이지 (`/staff`) - 직책 필터, 카드 UI, 예약 버튼
- [x] 스태프 페이지 13개 언어 번역 (`resources/lang/*/staff_page.php`)
- [x] 스태프-회원 연동 검색 개선 (암호화된 이름 복호화 후 클라이언트 필터링)
- [x] 회원 검색 UX 개선 (포커스 시 목록 표시, 프로필 사진, 하이라이트)
- [x] 회원 프로필 사진 → 스태프 아바타 자동 저장 (`member_avatar_url` hidden 필드)
- [x] 스태프 관리 모달을 `staff/index.php` + `staff/index-js.php`로 리팩토링

### 2026-03-11: 마이페이지 공통 사이드바 + 회원 탈퇴 개선

- [x] 마이페이지 공통 사이드바 컴포넌트 (`resources/views/components/mypage-sidebar.php`)
- [x] 6개 마이페이지 인라인 사이드바를 공통 컴포넌트로 통일
- [x] 스킨 오버라이드 지원 (`skins/member/{스킨}/components/mypage-sidebar.php`)
- [x] modern 스킨 사이드바 오버라이드 샘플 작성
- [x] 회원 탈퇴 소프트 삭제 + 개인정보 익명화 (기존 하드 삭제 대체)
- [x] `rzx_users`에 `deleted_at`, `withdraw_reason` 컬럼 추가
- [x] 예약 기록 고객 정보 익명화 (기록 자체는 유지)
- [x] 탈퇴 안내 문구 상세화 (법적 근거 포함, 13개 언어)
- [x] `retention_notice` 번역 키 추가 (13개 언어)

### 2026-03-11: 출퇴근 근태 관리 시스템

- [x] `rzx_attendance` 테이블 생성 (id, staff_id, clock_in/out, work_hours, status, source, memo)
- [x] `rzx_staff`에 `card_number` 컬럼 추가 (RFID/NFC 카드번호)
- [x] 근태 현황 페이지 (`/admin/staff/attendance`) - 오늘의 출퇴근 카드 UI, 통계
- [x] 근태 기록 페이지 (`/admin/staff/attendance/history`) - 기간별 조회, 수정/삭제, 페이지네이션
- [x] 근태 대시보드 (`/admin/staff/attendance/dashboard`) - 월간 통계, 스태프별 요약
- [x] 카드리더 키오스크 (`/admin/staff/attendance/kiosk`) - 전체화면, HID 카드 입력 감지
- [x] 스태프 폼에 카드번호 필드 추가
- [x] 사이드바 근태 관리 메뉴 + 라우팅 추가
- [x] 13개 언어 번역 키 추가 (staff.attendance 70+ 키)

### 2026-03-10: 회원 목록 관리

- [x] 회원 목록 페이지 (`/admin/members`) - 검색/필터/페이지네이션
- [x] 회원 수정 모달 (이름, 이메일, 전화, 등급, 상태)
- [x] 회원 삭제 시 연동 스태프 비활성화
- [x] 등급 변경 시 StaffSync 자동 동기화
- [x] 통계 카드 (전체/활성/비활성 회원 수)
- [x] 13개 언어 번역 키 추가 (members.list 30+ 키)

### 2026-03-10: 회원 그룹(등급) 관리

- [x] 회원 그룹 관리 페이지 (`/admin/members/groups`) - 카드형 UI
- [x] 그룹 CRUD API (생성/수정/삭제/기본 설정)
- [x] 등급별 회원 수 표시, 할인율/적립율/승급 조건
- [x] 기본 등급 삭제 방지, 삭제 시 회원 기본 등급 이동
- [x] 13개 언어 번역 키 추가 (members.groups 30+ 키)

### 2026-03-10: 스태프 관리 시스템

- [x] 스태프 목록 페이지 (`/admin/staff`) - CRUD API, 통계 카드, 테이블
- [x] 스태프 추가/수정 모달 폼 (이름, 이메일, 전화, 소개, 담당서비스, 활성상태)
- [x] 스태프 설정 페이지 (`/admin/staff/settings`) - 호칭, 선택필수, 소개/사진표시, 자동배정
- [x] 사이드바 스태프 드롭다운 메뉴 (목록 + 설정)
- [x] 13개 언어 번역 키 추가 (staff 관련 40+ 키)
- [x] `index.php` 라우팅 추가 (`staff/settings`)

### 2026-03-10: 서비스 통화/가격 설정 적용

- [x] 서비스 설정 > 기본 설정의 통화 단위(`service_currency`) 전 페이지 적용
- [x] 가격 표시 설정(`service_price_display`: show/hide/contact) 전 페이지 적용
- [x] 적용 페이지: 관리자 서비스 목록, 고객 서비스 목록/상세, 예약 페이지
- [x] 기본 통화 JPY(¥)로 변경, 서비스 가격 조정

### 2026-03-10: 서비스 다국어 버튼 수정

- [x] `migrateServiceMultilangKeys` API 엔드포인트 수정 (`/api/translations?action=rename`)
- [x] `migrateCategoryMultilangKeys` API 엔드포인트 수정

### 2026-03-10: 메뉴 관리 시스템 (Rhymix 호환)

- [x] 4단 캐스케이딩 패널 UI (트리 | 컨텍스트 | 메뉴타입 | 상세폼)
- [x] HTML5 드래그&드롭 메뉴 순서 변경 (3구간 판정)
- [x] 복사 / 잘라내기 / 붙여넣기 (JS 클립보드)
- [x] 서브트리 재귀 복사 (`copy_menu_item` API)
- [x] 트리 접기/펼치기 토글
- [x] 메뉴 검색 (자동 트리 펼침)
- [x] 사이트맵 CRUD + 편집 UI
- [x] Rhymix 호환 필드: `open_window`, `expand`, `group_srls`
- [x] 홈 메뉴 삭제 방지
- [x] Toast 알림 시스템
- [x] 13개 언어 번역 키 14개 추가

### 2026-03-10: 관리자 TopBar 공유 컴포넌트

- [x] `design.php` 인라인 TopBar → 공유 컴포넌트 교체
- [x] `pages.php` 인라인 TopBar → 공유 컴포넌트 교체
- [x] `.htaccess` admin 디렉토리 rewrite 강제 규칙 추가

### 2026-03-09: 모듈 기반 아키텍처

- [x] LanguageModule / LogoModule / SocialLoginModule
- [x] 공용 언어 선택기 (`language-selector.php`)
- [x] MemberSkinLoader 모듈 통합 (`setSiteSettings`)
- [x] 프론트/회원 스킨 헤더에 공용 언어 선택기 적용

### 2026-03-07: 다국어 시스템 개선

- [x] Translator 클래스 DB 연동
- [x] 관리자 언어 설정 페이지
- [x] 관리자 번역 관리 페이지
- [x] 커스텀 언어 추가/삭제

### 이전 작업

- [x] 관리자 대시보드 (`/theadmin`)
- [x] 사이트 설정 / 메일 설정 / SEO 설정 / PWA 설정
- [x] 시스템 설정 (정보, 캐시, 모드, 로그)
- [x] 회원 관리 (목록, 설정)
- [x] 전화번호 입력 컴포넌트 (180개+ 국가)
- [x] 이미지 크롭 컴포넌트 (Cropper.js)
- [x] 서비스 목록/상세 페이지
- [x] 회원가입/로그인/비밀번호 재설정
- [x] 다크모드 지원

---

## 진행 중

### 관리자 권한 시스템 (슈퍼바이저 + 스태프 권한)

**설계 문서**: `docs/ADMIN_PERMISSION.md`

**핵심 구조**: 회원(rzx_users) ↔ 스태프(rzx_staff) ↔ 관리자(rzx_admins) 3중 연동
- 슈퍼바이저(master): 설치 시 생성, 삭제/해제/비활성화 완전 차단
- 스태프 권한: 14개 권한 단위로 개별 지정 (dashboard, reservations, counter, services, staff, staff.schedule, staff.attendance, members, site, site.pages, site.widgets, site.design, site.menus, settings)
- 계층적 권한 매칭: `staff` 권한이 `staff.schedule`, `staff.attendance` 접근 허용

- [x] Phase 1: DB 스키마 수정 (`rzx_admins`에 user_id, staff_id, permissions JSON 추가)
- [x] Phase 2: AdminAuth 클래스 (`rzxlib/Core/Auth/AdminAuth.php`) — init, attempt, logout, check, current, isMaster, can, 슈퍼바이저 보호
- [x] Phase 3: 관리자 로그인 페이지 (세션 분리 `$_SESSION['admin_id']`, 기존 고객 세션과 독립)
- [x] Phase 4: 미들웨어 적용 (`index.php`에서 인증 + 권한 체크, 403 페이지)
- [x] Phase 5: 사이드바 권한 필터 (`AdminAuth::can()` 기반 메뉴 표시/숨김)
- [x] Phase 6: 관리자 관리 UI (`/admin/staff/admins`) — 목록, 추가, 권한 편집, 상태 토글, 해제
  - 슈퍼바이저 보호: UI에서 편집/삭제/비활성화 버튼 숨김 + AJAX 핸들러에서 거부
  - master 전용 접근 (사이드바에서 master만 메뉴 표시)
- [ ] Phase 7: 슈퍼바이저 보호 (회원/스태프/관리자 API에 보호 로직) — 추후
- [ ] Phase 8: 설치 위자드 통합 — 추후

### 기능 카드 위젯 설정

- [x] widget.json config_schema 확장 (columns, bg_color, feature_items)
- [x] render.php 동적 렌더링 (16개 아이콘, 8개 색상, 2/3/4 컬럼)
- [x] edit-features-js.php 편집 UI (카드 추가/삭제, 아이콘·색상·제목·설명 i18n)
- [x] WidgetLoader syncToDatabase 개선 (config_schema 내용 변경도 자동 감지)
- [x] 위젯 빌더에서 파일 기반 config_schema 우선 사용

### 스태프-회원 연동 및 근태 시스템 (3단계)

**1단계: 회원-스태프 연동** (완료)
- [x] `rzx_staff`에 `user_id` 컬럼 추가 (nullable, 인덱스)
- [x] 스태프 모달에 "회원 연동" 검색/선택 기능 (실시간 검색, 자동 채움)
- [x] 스태프 설정에 "스태프 연동 등급" 옵션
- [x] 등급 변경 시 자동 동기화 (`StaffSync.php`: 등급 부여 → 스태프 생성, 등급 해제 → 비활성)

**2단계: 출퇴근 근태 기록** (완료)
- [x] `rzx_attendance` 테이블 + `rzx_staff.card_number` 컬럼
- [x] 근태 현황 / 기록 / 대시보드 페이지
- [x] 카드리더 키오스크 모드 (HID 방식)

**3단계: 리포트 및 통계**
- [ ] 월별 근무 리포트
- [x] 스케줄 관리
- [ ] CSV 내보내기

### 예약 관리 시스템 (관리자)

**뷰 파일**: `resources/views/admin/reservations/`
**공통 초기화**: `_init.php` (statusBadge, getServices, formatPrice, 통화설정)

**완료된 뷰 (GET 라우트 연결됨)**:
- [x] 예약 목록 (`index.php` + `index-js.php`) — 검색/필터/상태변경, 페이지네이션
- [x] 예약 상세 (`show.php`) — 예약/고객/결제 정보, 상태변경 버튼, 관리자 메모
- [x] 예약 생성 (`create.php` + `create-js.php`) — 2컬럼 레이아웃, 다중 서비스 카드 선택
- [x] 예약 수정 (`edit.php`) — 서비스/일시/고객/금액 수정 폼
- [x] 캘린더 (`calendar.php` + `calendar-js.php`) — 풀스크린 월간 캘린더, 상세 모달
- [x] 통계 (`statistics.php`) — 기간별 요약, 상태/서비스/시간대/일별 차트
- [x] 사이드바 드롭다운 메뉴 (목록, 캘린더, 통계, 생성)
- [x] 통화 단위 동적 적용 (`formatPrice()` + JS `formatCurrency()`)

**완료 (백엔드 POST 라우트)**:
- [x] 예약 생성 POST (`store`) — `service_ids[]` 배열 → 개별 예약 생성, source 구분
- [x] 예약 수정 POST (`update`) — 서비스/일시/고객/금액 수정
- [x] 상태 변경 POST (`confirm`, `cancel`, `complete`, `no-show`) — AJAX JSON 응답
- [x] 서비스 시작 POST (`start-service`) — POS 전용
- [x] 결제 처리 POST (`payment`) — POS 전용

**미완료**:
- [ ] 관리자 메모 저장 POST (`admin_notes`)

### 기타 진행 중
- [ ] 사이트 관리 > 디자인 관리 (레이아웃/스킨 편집 기능)
- [ ] 사이트 관리 > 페이지 관리 — 커스텀 페이지 CRUD (사용자 생성 페이지)

---

## 향후 계획

- [ ] POS 워크인 접수 시 confirmed 상태로 바로 생성 (현재 pending)
- [ ] 결제 연동
- [ ] React PWA 프론트엔드 완성
- [ ] 레거시 시스템 데이터 마이그레이션

---

## 주요 파일 경로

| 구분 | 경로 |
|------|------|
| 메뉴 관리 뷰 | `resources/views/admin/site/menus.php` |
| 메뉴 JS | `resources/views/admin/site/menus-js.php` |
| 메뉴 DnD | `resources/views/admin/site/menus-dnd.php` |
| 메뉴 API | `resources/views/admin/site/menus-api.php` |
| 공유 TopBar | `resources/views/admin/partials/admin-topbar.php` |
| 공용 언어 선택기 | `resources/views/components/language-selector.php` |
| 공용 마이페이지 사이드바 | `resources/views/components/mypage-sidebar.php` |
| 모듈 디렉토리 | `rzxlib/Core/Modules/` |
| 번역 파일 (공통) | `resources/lang/{locale}/admin.php` |
| 번역 파일 (설정) | `resources/lang/{locale}/settings.php` |
| 번역 파일 (시스템) | `resources/lang/{locale}/system.php` |
| 번역 파일 (서비스) | `resources/lang/{locale}/services.php` |
| 번역 파일 (예약) | `resources/lang/{locale}/reservations.php` |
| 번역 파일 (회원) | `resources/lang/{locale}/members.php` |
| 번역 파일 (스태프) | `resources/lang/{locale}/staff.php` |
| 번역 파일 (사이트) | `resources/lang/{locale}/site.php` |
| 스태프 목록+모달 | `resources/views/admin/staff/index.php` |
| 스태프 모달+JS | `resources/views/admin/staff/index-js.php` |
| 고객 스태프 목록 | `resources/views/customer/staff.php` |
| 고객 스태프 상세 | `resources/views/customer/staff-detail.php` |
| 고객 스태프 상세 JS | `resources/views/customer/staff-detail-js.php` |
| 스태프 번역 | `resources/lang/{locale}/staff_page.php` |
| 스태프 설정 | `resources/views/admin/staff/settings.php` |
| 서비스 JS | `resources/views/admin/services-js.php` |
| 서비스 카테고리 설정 | `resources/views/admin/services/settings-categories.php` |
| 회원 목록 | `resources/views/admin/members.php` |
| 회원 폼 모달 | `resources/views/admin/members-form.php` |
| 회원 JS | `resources/views/admin/members-js.php` |
| 회원 그룹 | `resources/views/admin/members/groups.php` |
| 회원 그룹 폼 | `resources/views/admin/members/groups-form.php` |
| 회원 그룹 JS | `resources/views/admin/members/groups-js.php` |
| 근태 현황 | `resources/views/admin/staff/attendance.php` |
| 근태 JS | `resources/views/admin/staff/attendance-js.php` |
| 근태 기록 | `resources/views/admin/staff/attendance-history.php` |
| 근태 대시보드 | `resources/views/admin/staff/attendance-dashboard.php` |
| 키오스크 | `resources/views/admin/staff/attendance-kiosk.php` |
| 키오스크 JS | `resources/views/admin/staff/attendance-kiosk-js.php` |
| StaffSync | `rzxlib/Core/Helpers/StaffSync.php` |
| 범용 문서 에디터 | `resources/views/admin/site/pages-document.php` |
| 문서 에디터 JS | `resources/views/admin/site/pages-document-js.php` |
| 페이지 관리 | `resources/views/admin/site/pages.php` |
| 컴플라이언스 데이터 | `rzxlib/Core/Data/ComplianceData.php` |
| 환불규정 기본 데이터 | `rzxlib/Core/Data/RefundPolicyData.php` |
| 데이터관리 편집 | `resources/views/admin/site/pages-compliance.php` |
| 데이터관리 JS | `resources/views/admin/site/pages-compliance-js.php` |
| 고객 이용약관 | `resources/views/customer/terms.php` |
| 고객 개인정보 | `resources/views/customer/privacy.php` |
| 고객 환불규정 | `resources/views/customer/refund-policy.php` |
| 고객 데이터정책 | `resources/views/customer/data-policy.php` |
| 컴플라이언스 번역 | `resources/lang/{locale}/compliance.php` |
| 고객 번역 | `resources/lang/{locale}/customer.php` |
| 위젯 렌더러 모듈 | `rzxlib/Core/Modules/WidgetRenderer.php` |
| 위젯 관리 | `resources/views/admin/site/widgets.php` |
| 위젯 생성 | `resources/views/admin/site/widgets-create.php` |
| 위젯 마켓플레이스 | `resources/views/admin/site/widgets-marketplace.php` |
| 위젯 빌더 | `resources/views/admin/site/pages-widget-builder.php` |
| 위젯 빌더 JS (코어) | `resources/views/admin/site/pages-widget-builder-js.php` |
| 위젯 빌더 AJAX | `resources/views/admin/site/pages-widget-builder-ajax.php` |
| 위젯 편집 패널 JS | `resources/views/admin/site/pages-widget-builder-edit-js.php` |
| 위젯 편집 아이템 JS | `resources/views/admin/site/pages-widget-builder-edit-items-js.php` |
| 위젯 인라인 편집 JS | `resources/views/admin/site/pages-widget-builder-inline-js.php` |
| 위젯 그리드 드래그 JS | `resources/views/admin/site/pages-widget-builder-grid-js.php` |
| WidgetLoader | `rzxlib/Core/Modules/WidgetLoader.php` |
| 위젯 폴더 | `widgets/{slug}/` (widget.json, render.php, thumbnail.png) |
| AdminAuth 클래스 | `rzxlib/Core/Auth/AdminAuth.php` |
| 관리자 관리 UI | `resources/views/admin/staff/admins.php` |
| 관리자 관리 JS | `resources/views/admin/staff/admins-js.php` |
| 권한 체크박스 | `resources/views/admin/staff/admins-perm-checkboxes.php` |
| 403 페이지 | `resources/views/admin/403.php` |
| 예약 공통 초기화 | `resources/views/admin/reservations/_init.php` |
| 예약 목록 | `resources/views/admin/reservations/index.php` + `index-js.php` |
| 예약 상세 | `resources/views/admin/reservations/show.php` |
| 예약 생성 | `resources/views/admin/reservations/create.php` + `create-js.php` |
| 예약 수정 | `resources/views/admin/reservations/edit.php` |
| 예약 캘린더 | `resources/views/admin/reservations/calendar.php` + `calendar-js.php` |
| 예약 통계 | `resources/views/admin/reservations/statistics.php` |
| 예약 POS | `resources/views/admin/reservations/pos.php` + `pos-js.php` |
| 예약 API | `resources/views/admin/reservations/_api.php` |
| 예약 공용 폼 | `resources/views/admin/components/reservation-form.php` + `-js.php` |
| 예약 캘린더 그리드 | `resources/views/admin/components/calendar-grid.php` |
| 예약 캘린더 모달 | `resources/views/admin/components/calendar-modals.php` |
| 예약 캘린더 JS | `resources/views/admin/components/calendar-js.php` |
| 예약 컨트롤러 | `rzxlib/Http/Controllers/Admin/ReservationController.php` |
| 진입점 | `public/index.php` → `index.php` |
| 라우팅 | `.htaccess` + `index.php` (파일 기반) |

---

- **최종 수정일**: 2026-03-15 (POS 현장관리 + 예약 API 백엔드 + 결제 처리)
