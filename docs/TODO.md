# VosCMS TODO — 부족한 부분 정리

> 2026-04-17 기준

---

## ~~1. 다국어 콘텐츠 미완성~~ ✅ 완료 (2026-04-18)

### ~~법적 문서 페이지~~ ✅ 13/13 완료
| 페이지 | 상태 | 비고 |
|--------|------|------|
| `terms` (이용약관) | ✅ 13개 언어 | 섹션 제목 번역 + 영어 본문 |
| `privacy` (개인정보처리방침) | ✅ 13개 언어 | 섹션 제목 번역 + 영어 본문 |
| `refund-policy` (환불정책) | ✅ 13개 언어 | 전문 번역 |

### 기타 페이지
| 페이지 | 현재 | 비고 |
|--------|------|------|
| `withholding-tax-jp` | ja만 | 일본 전용 (번역 불필요) |
| `withholding-tax-kr` | ko만 | 한국 전용 (번역 불필요) |

---

## 2. 개발자 포털

### ~~관리자 아이템 등록~~ ✅ 완료 (2026-04-18)
- [x] 관리자 페이지에서 마켓플레이스 아이템 등록/편집 (`/theadmin/marketplace/submit`)
- [x] 공통 폼 컴포넌트 분리 (`_components/item-form.php`) — 개발자/관리자 공유
- [x] 관리자 API에 submit_item 액션 추가 (등록/수정, 파일 업로드, 버전 관리)

### 재사용 함수 분리
- [ ] 폼 입력 컴포넌트 (라벨+인풋+힌트) → 공용 헬퍼
- [ ] 탭 시스템 → JS 공용 모듈
- [ ] 파일 업로드 UI → 드래그 영역 공용 컴포넌트

### ~~제출 API 보강~~ ✅ 완료 (2026-04-18)
- [x] `api/developer/submit.php` — license, repo_url, demo_url, banner, requires_plugins 처리
- [x] 배너 이미지 업로드 지원
- [x] 임시 저장 (draft) — save_draft=1 파라미터, 패키지 미필수
- [x] vcs_review_queue에 license, repo_url, demo_url 컬럼 추가, status에 draft 추가
- [ ] 제출 전 미리보기 (향후)

---

## 3. 위젯 시스템

### 위젯 문서 (WIDGET_MANUAL.md)
- [ ] contact-info, location-map 위젯 문서 추가
- [ ] 전체 위젯 목록 최신화 (28개)

### 위젯 개선
- [ ] 위젯 썸네일 자동 생성 또는 기본 아이콘 개선
- [ ] 위젯 복제 기능 (위젯 빌더)
- [ ] 위젯 내보내기/가져오기

---

## 4. 인프라/시스템

### 메일 시스템
- [x] 관리자 문의 목록 페이지 ✅ (`/theadmin/contact-messages`)
- [ ] 메일 발송 이력 로깅
- [ ] 메일 템플릿 시스템 (HTML 메일)

### 세션 관리
- [x] 전용 세션 디렉토리 (7일 유지) — 완료
- [ ] 세션 만료 시 자동 remember 토큰 갱신 검증
- [ ] 관리자 세션과 사용자 세션 분리 검토

### 보안
- [ ] CSRF 토큰 — contact-form 위젯에 적용
- [ ] Rate limiting — 문의 폼 스팸 방지 강화
- [ ] Content Security Policy 헤더 강화

---

## 5. 관리자 패널

### ~~반복 에러~~ ✅ 수정 완료 (2026-04-18)
- [x] `Undefined array key "label"` — load_menu()에서 label/title 둘 다 지원 + items 하위 메뉴도 처리
- [x] `Undefined variable $adminPath` — $_ENV['ADMIN_PATH'] 폴백 추가
- [x] `Undefined variable $pageTitle` — null 합병 연산자로 폴백

### 마켓플레이스 관리
- [ ] 아이템 심사/승인 워크플로우
- [ ] 판매 통계 대시보드
- [ ] 개발자 정산 시스템

---

## 6. 프론트엔드

### 페이지
- [ ] 가격 정책 (Pricing) 페이지 — 플랜별 비교표
- [ ] 업데이트 로그 (Changelog) 공개 페이지

### 게시판
- [ ] 게시판 확장 변수 — 프론트 렌더링 (글쓰기/읽기에 표시)
- [ ] 게시판 스킨 다양화

### SEO
- [ ] sitemap.xml 자동 생성
- [ ] robots.txt 관리자 편집
- [ ] 구조화 데이터 (Schema.org)

---

## 7. 코드 품질

### 리팩토링 대상
- [ ] `index.php` — 800줄+ 라우팅 → 라우터 클래스 분리
- [ ] `page.php` — 300줄+ → 렌더러 분리
- [ ] 위젯 render.php — 공통 패턴 추출 (CSS/JS 스코핑, 다국어 로드)

### 테스트
- [ ] 유닛 테스트 프레임워크 도입
- [ ] 위젯 렌더링 테스트
- [ ] API 엔드포인트 테스트

---

## 우선순위 (제안)

1. **법적 문서 다국어** — terms, privacy, refund-policy 10개 언어 추가
2. **관리자 아이템 등록** — 제출 페이지 공유
3. **제출 API 보강** — 신규 필드 처리
4. **관리자 에러 수정** — label, adminPath, pageTitle
5. **문의 관리 페이지** — 접수된 문의 조회/답변
6. **코드 리팩토링** — 재사용 함수 분리
