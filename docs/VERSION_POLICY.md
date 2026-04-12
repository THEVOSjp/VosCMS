# 버전 관리 정책

## 버전 규칙

```
a.b.c

a — 대규모 기능 변경 (신규 버전은 0에서 시작)
b — 기능 추가 또는 변경, 간단한 기능 수정
c — 버그 수정, 오류 수정
```

### 예시

| 버전 | 의미 |
|------|------|
| 0.1.0 | 최초 개발, 기본 기능 구현 |
| 0.2.0 | 기능 추가 |
| 0.2.1 | 버그 수정 |
| 1.0.0 | 첫 안정 릴리스 |
| 1.1.0 | 기능 추가 |
| 1.1.1 | 버그 수정 |
| 2.0.0 | 대규모 변경 (아키텍처 변경, 호환성 변경 등) |

### 안정 버전 표시

```
1.0.0 (stable)    — 안정 버전
0.3.0 (dev)       — 개발 중
1.1.0-beta        — 베타
```

---

## 관리 대상

| 구분 | 버전 파일 | 경로 |
|------|----------|------|
| RezlyX Salon (패키지) | version.json | `/version.json` |
| 코어 (VosCMS) | version.json | `/version.json` |
| 플러그인 | plugin.json | `plugins/*/plugin.json` |
| 위젯 | widget.json | `widgets/*/widget.json` |
| 스킨/레이아웃 | skin.json | `skins/layouts/*/skin.json` |

---

## 현재 버전 현황 (2026-04-12)

### 패키지

| 항목 | 버전 | 상태 | 비고 |
|------|------|------|------|
| VosCMS Core | 2.1.1 | dev | 설치 다국어, 메뉴 분리, 위젯 다국어뷰 |
| RezlyX Salon | 0.1.0 | dev | 초기 개발 단계 |

### 플러그인

| 플러그인 | 버전 | 상태 | 주요 내용 |
|----------|------|------|----------|
| vos-salon | 0.1.0 | dev | 서비스, 스태프, 예약, 번들 관리 |
| vos-pos | 0.1.0 | dev | POS 결제, 접수/대기, 매출 리포트 |
| vos-kiosk | 0.2.0 | dev | 키오스크 + 모바일 접수(checkin) 추가 |
| vos-attendance | 0.1.1 | dev | 근태 관리, 다국어 뷰 수정 |
| vos-before-after | 0.1.0 | dev | Before/After 비교 사진 (신규) |
| vos-hair-story | 0.1.0 | dev | 헤어스토리 포트폴리오 (신규) |
| vos-review | 0.1.0 | dev | 고객 후기/별점 (신규) |

### 위젯

| 위젯 | 버전 | 상태 | 비고 |
|------|------|------|------|
| booking | 1.0.0 | stable | 예약 폼 |
| staff | 1.0.1 | stable | 스태프 소개, i18n title 수정 |
| lookup | 1.0.0 | stable | 예약 조회 |
| services | 1.0.0 | stable | 서비스 목록 |
| before-after | 0.1.0 | dev | B/A 비교 슬라이더 (salon용 수정) |
| hair-story | 0.1.0 | dev | 헤어스토리 카드 (신규) |
| reviews | 0.1.0 | dev | 고객 후기 카드 (신규) |
| feature-tour | 0.1.0 | dev | 기능 소개 투어 + 라이트박스 (신규) |
| hero | 1.0.0 | stable | 히어로 배너 |
| hero-slider | 1.1.0 | stable | 히어로 슬라이더 |
| features | 1.0.0 | stable | 기능 소개 그리드 |
| grid-section | 1.0.0 | stable | 그리드 섹션 |
| compare-slider | 1.0.0 | stable | 이미지 비교 |
| cta / cta001 | 1.0.0 | stable | CTA |
| stats | 1.0.0 | stable | 통계 카운터 |
| testimonials | 1.0.0 | stable | 고객 후기 (수동) |
| text | 1.0.0 | stable | 텍스트 블록 |
| spacer | 1.0.0 | stable | 여백 |
| stylebook | 1.0.0 | stable | 스타일 갤러리 |
| board-contents | 1.0.0 | stable | 게시판 최신글 |
| shop-map | 1.0.0 | stable | 주변 사업장 지도 |
| shop-ranking | 1.0.0 | stable | 인기 사업장 |

### 스킨/레이아웃

| 스킨 | 버전 | 상태 |
|------|------|------|
| modern | 1.0.0 | stable |
| default | 1.0.0 | stable |
| minimal | 1.0.0 | stable |

---

## 4/10~4/12 작업 변경 이력

### vos-kiosk 0.1.0 → 0.2.0

- 키오스크 프론트 라우트 이전 (/admin/kiosk/run → /kiosk)
- 모바일 접수 시스템 신규 (/checkin)
- PRG 패턴 중복 접수 방지
- 동행자 접수 누적 대기번호
- 키오스크/모바일 대기번호 공유
- 관리자 QR코드 생성/인쇄/다운로드
- rzx_staff_bundles 테이블 누락 수정

### vos-attendance 0.1.0 → 0.1.1

- 스태프 이름 다국어 뷰(name_i18n) 적용 (전 파일)

### vos-salon 0.1.0

- booking 페이지 번들 시스템 개선 (rzx_service_bundles 쿼리 수정)
- booking 서비스 4열 레이아웃
- 스태프 상세 번들 설명 HTML strip_tags
- 번들 명칭 다국어 표시

### vos-before-after 0.1.0 (신규)

- Before/After 비교 사진 플러그인
- 관리자 CRUD, 프론트 리스트/등록
- 드래그 비교 슬라이더, 좋아요
- before-after 위젯 shop 의존 제거

### vos-hair-story 0.1.0 (신규)

- 헤어스토리 포트폴리오 플러그인
- 관리자/프론트 CRUD, 이미지+동영상 드래그앤드롭
- 스태프 상세 페이지 연동
- 서비스 체크박스 선택, 모델 정보 (영문코드 저장+다국어 표시)
- 슬라이드 뷰 + 라이트박스
- 프론트 편집 (본인/관리자)

### vos-review 0.1.0 (신규)

- 고객 후기/별점 플러그인
- 관리자 승인/반려/답변, 설정 (정책/포인트)
- 프론트 리스트 (별점/스태프 필터), 리뷰 작성 (별점+사진+동영상)
- 도움이 됐어요, 신고
- reviews 위젯
- 스태프 상세 페이지 연동

### staff 위젯 1.0.0 → 1.0.1

- $currentLocale 변수 순서 수정 (Preview failed 버그)
- i18n title 배열 처리

### feature-tour 위젯 0.1.0 (신규)

- 스크린샷 슬라이드 + 다크 패널 기능 소개
- 이미지/동영상 지원, 자동 슬라이드, 스와이프
- 라이트박스 (원본 이미지 슬라이드 뷰)

### 기타

- 페이지 시스템 라우팅 개선 (DB 위젯 페이지 우선)
- 메뉴 드롭다운 포탈 방식 수정
- 위젯 빌더 프론트 AJAX exit 수정
- admin/api/translations.php 추가
- members/settings, members/groups 뷰 추가
- 다국어 키 수십 개 추가 (13개 언어)
