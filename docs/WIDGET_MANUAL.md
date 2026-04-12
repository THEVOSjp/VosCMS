# 위젯 사용 매뉴얼

RezlyX Salon의 위젯 목록과 사용법. 위젯 빌더에서 페이지에 드래그하여 배치합니다.

---

## 1. 예약 시스템 위젯

### booking — 예약하기

온라인 예약 폼. 서비스/스태프 선택 → 날짜/시간 → 고객정보 → 예약 완료.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |
| Show Price | 가격 표시 여부 |
| Show Duration | 소요시간 표시 여부 |

### staff — 스태프 소개

스태프 목록을 카드 형태로 표시. 포지션 필터, 지명비, 지명예약 버튼 포함.

| 설정 | 설명 |
|------|------|
| Title / Subtitle | 섹션 제목/부제 (다국어) |
| Columns | 컬럼 수 (2~6) |
| Show Position Filter | 포지션 필터 탭 표시 |
| Show Designation Fee | 지명비 표시 |
| Show Booking Button | 지명예약 버튼 표시 |
| Show Services | 담당 서비스 표시 |
| Max Staff | 최대 표시 인원 (0=전체) |

### lookup — 예약 조회

전화번호 또는 예약번호로 예약 검색.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |

### services — 서비스 목록

서비스를 카드 형태로 표시. 카테고리 필터 포함.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |

---

## 2. 콘텐츠 위젯

### before-after — Before / After

시술 전후 비교 사진. 나란히 보기 ↔ 드래그 비교 슬라이더 전환.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |
| Source | 데이터 소스 (stylebook: DB 자동 / manual: 수동 입력) |
| Items | 수동 입력 시 Before/After 이미지 세트 |
| Auto Limit | 자동 표시 개수 (3/5/8) |
| Style | 슬라이더 / 그리드 |

**사용법:**
- `source: stylebook` → `rzx_before_afters` 테이블에서 자동 로드
- `source: manual` → Items에 직접 이미지 URL 입력
- 비교 버튼 클릭 → 드래그 슬라이더로 전환

### hair-story — 헤어스토리

스태프(디자이너)의 헤어스타일 포트폴리오를 카드 그리드로 표시.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |
| Limit | 표시 개수 (4/6/8/12) |
| Columns | 컬럼 수 (3/4/5) |

### reviews — 고객 후기 (口コミ)

승인된 고객 리뷰를 카드 형태로 표시. 별점, 후기, 사진, 매장 답변 포함.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |
| Limit | 표시 개수 (3/4/6/8) |
| Show Photos | 리뷰 사진 표시 |
| Show Staff | 담당 스태프 표시 |

### stylebook — 스타일북

고객/스태프의 시술 사진 갤러리.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |

### board-contents — 게시판 최신글

게시판의 최신 글을 표시.

| 설정 | 설명 |
|------|------|
| Board Slug | 게시판 슬러그 (notice, free 등) |

---

## 3. 마케팅 위젯

### feature-tour — 기능 소개 투어

스크린샷 슬라이드 + 다크 패널 기능 소개. Product Tour 스타일.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |
| Panel Subtitle | 우측 패널 소제목 (다국어, 예: SMART EXPERIENCE) |
| Panel Main Copy | 우측 패널 메인 카피 (다국어, 줄바꿈 가능) |
| Panel Description | 우측 패널 설명 (다국어) |
| Bullet Points | 우측 패널 불릿 포인트 (줄바꿈으로 구분, 다국어) |
| Feature Slides | 슬라이드 아이템 (이미지/동영상 + 제목 + 설명) |

**슬라이드 입력 방법:**
1. 편집 패널에서 "Feature Slides" 섹션의 [Add] 클릭
2. 이미지 또는 동영상 업로드
3. 제목, 설명 입력 (다국어 지원)
4. 필요한 만큼 반복 추가
5. 적용 → 프리뷰

**동작:**
- 좌측 스크린샷 카드: fade 전환 슬라이드
- 우측 다크 패널: 프로그레스 바 + 좌우 버튼 + 도트 인디케이터
- 자동 슬라이드 5초 (호버 시 정지)
- 모바일 스와이프 지원
- **이미지 클릭 → 라이트박스 (원본 슬라이드 뷰)**

**라이트박스:**
- 클릭 → 현재 슬라이드 위치에서 열림
- 좌우 화살표 → 슬라이드 전환
- 키보드 ←→ 화살표, ESC 닫기
- 모바일 스와이프
- 동영상 → controls 표시, 자동재생
- 닫기 → X 버튼, 배경 클릭, ESC
- 하단 → 기능명 + 설명 표시
- 좌상단 → 카운터 (1/7)

---

## 4. 레이아웃/디자인 위젯

### hero — 히어로 배너

단일 이미지/동영상 히어로 배너.

| 설정 | 설명 |
|------|------|
| Title / Subtitle | 오버레이 텍스트 (다국어) |
| Image / Video | 배경 미디어 |
| CTA Button | 버튼 텍스트 + 링크 |
| Overlay | 오버레이 투명도 |

### hero-slider — 히어로 슬라이더

복수 이미지/동영상 슬라이드 히어로 배너 + 사이드 네비게이션.

| 설정 | 설명 |
|------|------|
| Layout | slide-nav / slide-only / slide-text |
| Height | sm/md/lg/xl |
| Auto Play | 자동 재생 |
| Interval | 전환 간격 (ms) |
| Show Dots / Arrows | 인디케이터 표시 |
| Overlay | 오버레이 투명도 |
| Slider Items | 슬라이드 세트 (이미지/동영상 + 제목 + 설명 + 링크) |

**슬라이드 입력 방법:**
1. [Add] 클릭 → 슬라이드 추가
2. Image 또는 Video 선택 → 업로드
3. 제목, 설명, 링크 입력 (다국어)
4. 반복 추가, 드래그로 순서 변경

### features — 기능 소개 그리드

아이콘 + 제목 + 설명 카드를 그리드로 배치.

| 설정 | 설명 |
|------|------|
| Title | 섹션 제목 (다국어) |
| Items | 기능 카드 (아이콘, 제목, 설명) |

### grid-section — 그리드 섹션

다중 컬럼 레이아웃. 각 셀에 게시판/텍스트/이미지/HTML 배치.

| 설정 | 설명 |
|------|------|
| Layout | 2-equal, 3-equal, sidebar-right/left, portal 등 |
| Gap | 셀 간격 (none/small/medium/large) |
| Background | 배경색 |
| Grid Cells | 셀 타입별 콘텐츠 |

**Cell 타입:**
- Board: List / Card Grid / Thumbnail / Gallery / Banner
- New Shops (신착 매장)
- Events (이벤트)
- Q&A
- Text / HTML / Image / Spacer

### compare-slider — 이미지 비교 슬라이더

두 이미지를 드래그로 비교하는 슬라이더.

### cta / cta001 — Call to Action

행동 유도 배너.

### stats — 통계 카운터

숫자 카운팅 애니메이션.

### testimonials — 고객 후기 (수동)

관리자가 직접 입력하는 후기 카드. (자동 리뷰는 `reviews` 위젯 사용)

### text — 텍스트 블록

자유 텍스트.

### spacer — 여백

세로 여백.

---

## 5. 사업장 위젯 (RezlyX 포털용)

### shop-map — 주변 사업장 지도

지도 기반 주변 사업장 표시. vos-shop 플러그인 필요.

### shop-ranking — 인기 사업장 랭킹

인기 사업장 순위. vos-shop 플러그인 필요.

---

## 위젯 배치 방법

1. 관리자 → 페이지 관리 → 원하는 페이지의 [수정] 클릭
2. 좌측 위젯 팔레트에서 카테고리 선택
3. 위젯 클릭 → 캔버스에 추가
4. 위젯 클릭 → 우측 편집 패널에서 설정
5. [적용] → 프리뷰 확인
6. [저장] → 배포

## 위젯 순서 변경

캔버스에서 위젯 상단의 ↑↓ 버튼으로 순서 변경.

## 위젯 삭제

위젯 상단의 × 버튼 클릭.
