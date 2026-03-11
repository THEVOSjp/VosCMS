# RezlyX 프로젝트 계획

## 프로젝트 개요

RezlyX는 레거시 PHP 4.x 예약 시스템(view.back.jp)을 현대적으로 재구축한 예약 관리 시스템입니다.

- **기술 스택**: PHP 8.x + MySQL + Tailwind CSS + Vanilla JS
- **다국어**: 13개 언어 (ko, en, ja, zh_CN, zh_TW, de, es, fr, mn, ru, tr, vi, id)
- **개발 서버**: `http://localhost/rezlyx/` → `E:\xampp\htdocs\project\rezlyx`
- **관리자 경로**: `/theadmin` (DB `rzx_settings.admin_path`)

---

## 완료된 작업

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
- [ ] 스케줄 관리
- [ ] CSV 내보내기

### 기타 진행 중
- [ ] 사이트 관리 > 디자인 관리 (레이아웃/스킨 편집 기능)
- [ ] 사이트 관리 > 페이지 관리 (커스텀 페이지 CRUD)

---

## 향후 계획

- [ ] 예약 관리 시스템 (서비스별 예약 슬롯, 캘린더 뷰)
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
| 번역 파일 | `resources/lang/{locale}/admin.php` |
| 스태프 목록+모달 | `resources/views/admin/staff/index.php` |
| 스태프 모달+JS | `resources/views/admin/staff/index-js.php` |
| 고객 스태프 페이지 | `resources/views/customer/staff.php` |
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
| 진입점 | `public/index.php` → `index.php` |
| 라우팅 | `.htaccess` + `index.php` (파일 기반) |

---

- **최종 수정일**: 2026-03-12
