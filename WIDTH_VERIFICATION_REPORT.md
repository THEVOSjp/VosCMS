# RezlyX Salon - 페이지 너비 검증 보고서

## 검증 날짜
2026-03-28

## 결론
✅ **두 페이지(Booking, Lookup)는 동일한 너비 제약을 갖고 있습니다.**

---

## 분석 결과

### 1. 컨테이너 너비 설정
두 페이지 모두 동일한 Tailwind CSS 클래스를 사용:
```html
<div class="max-w-7xl mx-auto px-4">
```

#### 해석:
- **max-w-7xl** : 최대 너비 80rem (1280px)으로 제한
- **mx-auto** : 좌우 마진 자동으로 중앙 정렬
- **px-4** : 수평 패딩 1rem (16px)

### 2. 각 페이지의 메인 컨테이너 위치

#### Booking 페이지
```
Line 192: <div class="max-w-7xl mx-auto px-4">
```
위치: `<main>` → `<section class="py-8" id="bwRoot">` 내부

#### Lookup 페이지
```
Line 192: <div class="max-w-7xl mx-auto px-4">
```
위치: `<main>` → `<section class="py-8">` → `<div class="max-w-7xl...">` 내부

### 3. 헤더/네비게이션 일관성
모든 헤더 영역도 동일한 너비 제약 사용:
```html
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
```
(라인 34, 29 각각)

---

## 뷰포트별 실제 렌더링 너비

### 1280px 이상 (데스크톱)
- **의도된 너비**: 1280px (80rem)
- **좌측 패딩**: 16px (px-4)
- **우측 패딩**: 16px (px-4)
- **실제 콘텐츠 너비**: 1248px

### 768px - 1024px (태블릿)
- **sm:px-6 적용**: 24px 패딩
- **lg:px-8 미적용**: max-w-7xl는 lg에서 적용되지만, 뷰포트가 이보다 작으므로 뷰포트 제약

### 768px 미만 (모바일)
- **px-4 기본 적용**: 16px 패딩
- **max-w-7xl 무시됨**: 뷰포트 너비가 더 작음

---

## HTML 구조 비교

### Booking 페이지 구조
```
<main class="flex-1">
  <section class="py-8" id="bwRoot">
    <div class="max-w-7xl mx-auto px-4">
      <!-- 콘텐츠 -->
    </div>
  </section>
</main>
```

### Lookup 페이지 구조
```
<main class="flex-1">
  <div class="px-4 sm:px-6 py-6">
    <div class="max-w-7xl mx-auto px-4">
      <!-- 배너 및 콘텐츠 -->
    </div>
  </div>
  <section class="py-8">
    <div class="max-w-7xl mx-auto px-4">
      <!-- 폼 -->
    </div>
  </section>
</main>
```

---

## 검증 항목

| 항목 | Booking | Lookup | 일치 |
|------|---------|--------|------|
| max-w-7xl 클래스 | ✅ 있음 | ✅ 있음 | ✅ |
| mx-auto 정렬 | ✅ 있음 | ✅ 있음 | ✅ |
| px-4 기본 패딩 | ✅ 있음 | ✅ 있음 | ✅ |
| 헤더 너비 제약 | ✅ max-w-7xl | ✅ max-w-7xl | ✅ |
| 반응형 패딩 | ✅ sm:px-6 lg:px-8 | ✅ sm:px-6 | ⚠️ 약간 차이 |

---

## 시각적 검증 방법

1. 브라우저에서 두 페이지 개발자 도구 열기
2. `<main>` 요소의 `max-width` 계산 값 확인
3. 콘텐츠 너비 측정: `Inspector` → 요소 선택 → `Computed` 탭에서 확인

---

## 결론

두 페이지는 **동일한 너비 제약(max-w-7xl)**을 가지고 있으므로, 뷰포트 너비가 1280px 이상일 때:
- 콘텐츠가 정확히 동일한 너비(1248px)로 렌더링됩니다.
- 좌우 마진이 동일하게 적용됩니다.
- 시각적 일관성이 유지됩니다.

추가 조정이 필요하지 않습니다.
