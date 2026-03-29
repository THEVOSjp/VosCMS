# RezlyX 코딩 컨벤션

## 1. 파일 구조

```
resources/views/
├── admin/          # 관리자 페이지
│   ├── dashboard.php
│   └── settings.php
├── customer/       # 고객 페이지
│   ├── home.php
│   ├── login.php
│   └── ...
└── components/     # 재사용 컴포넌트
```

### 파일 네이밍
- **소문자 + 하이픈**: `forgot-password.php`, `my-reservations.php`
- **단수형 사용**: `service.php` (not `services.php`)
- **404, 500 등 에러 페이지**: `404.php`, `500.php`

---

## 2. PHP 코딩 스타일

### 파일 헤더
```php
<?php
/**
 * RezlyX [페이지명] Page
 */
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - 페이지 제목';
$baseUrl = $config['app_url'] ?? '';
```

### 변수 네이밍
```php
// camelCase 사용
$pageTitle = '제목';
$baseUrl = '/';
$isEmbed = false;

// 배열/객체
$formData = ['name' => '', 'email' => ''];
$services = [];
```

### 출력 이스케이프
```php
// 항상 htmlspecialchars 사용
<?php echo htmlspecialchars($variable); ?>

// 짧은 형태
<?= htmlspecialchars($variable) ?>
```

### 조건문
```php
// 한 줄 조건 - 콜론 구문
<?php if ($error): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

// 복잡한 조건
<?php
if ($condition) {
    // 처리
} elseif ($otherCondition) {
    // 처리
} else {
    // 기본 처리
}
?>
```

---

## 3. HTML 구조

### 기본 템플릿
```html
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <!-- 폰트 -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <!-- 다크 모드 초기화 -->
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
    <!-- Header -->
    <!-- Main Content -->
    <!-- Footer -->
    <script>
        // JavaScript
    </script>
</body>
</html>
```

---

## 4. Tailwind CSS 규칙

### 다크 모드 색상 체계 (Zinc)

| 용도 | 라이트 모드 | 다크 모드 |
|------|------------|----------|
| 페이지 배경 | `bg-zinc-50` | `dark:bg-zinc-900` |
| 카드/컨테이너 | `bg-white` | `dark:bg-zinc-800` |
| 사이드바 (관리자) | `bg-zinc-950` | - |
| 입력 필드 | `bg-white` | `dark:bg-zinc-700` |
| 텍스트 (기본) | `text-zinc-900` | `dark:text-white` |
| 텍스트 (보조) | `text-zinc-600` | `dark:text-zinc-300` |
| 텍스트 (약한) | `text-zinc-500` | `dark:text-zinc-400` |
| 테두리 | `border-zinc-300` | `dark:border-zinc-600` |
| 테두리 (강조) | `border-zinc-200` | `dark:border-zinc-700` |
| 호버 배경 | `hover:bg-zinc-100` | `dark:hover:bg-zinc-700` |

### 클래스 순서
```html
<!-- 권장 순서: 레이아웃 → 크기 → 색상 → 효과 → 다크모드 → 반응형 -->
<div class="flex items-center justify-between
            w-full h-16 px-4 py-2
            bg-white text-zinc-900 border-b border-zinc-200
            shadow-sm transition-colors
            dark:bg-zinc-800 dark:text-white dark:border-zinc-700
            md:px-6 lg:px-8">
```

### 컴포넌트 패턴

#### 버튼
```html
<!-- Primary 버튼 -->
<button class="px-6 py-3 bg-blue-600 hover:bg-blue-700
               dark:bg-blue-500 dark:hover:bg-blue-600
               text-white font-semibold rounded-lg
               transition shadow-lg shadow-blue-500/30">
    버튼 텍스트
</button>

<!-- Secondary 버튼 -->
<button class="px-6 py-3 border border-zinc-300 dark:border-zinc-600
               text-zinc-700 dark:text-zinc-300 font-semibold rounded-lg
               hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
    버튼 텍스트
</button>
```

#### 입력 필드
```html
<input type="text"
       class="w-full px-4 py-3
              border border-zinc-300 dark:border-zinc-600
              dark:bg-zinc-700 dark:text-white
              rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
              transition"
       placeholder="입력">
```

#### 카드
```html
<div class="bg-white dark:bg-zinc-800
            rounded-2xl shadow-lg dark:shadow-zinc-900/50
            p-6 md:p-8
            transition-colors">
    <!-- 내용 -->
</div>
```

#### 헤더
```html
<header class="bg-white dark:bg-zinc-800
               shadow-sm sticky top-0 z-50
               transition-colors duration-200">
```

#### 푸터
```html
<footer class="bg-white dark:bg-zinc-800
               border-t dark:border-zinc-700
               mt-12">
```

---

## 5. JavaScript 규칙

### 변수 선언
```javascript
// const 우선 사용
const langBtn = document.getElementById('langBtn');
const langDropdown = document.getElementById('langDropdown');

// 변경 필요시 let 사용
let currentStep = 1;

// var 사용 금지
```

### 이벤트 리스너
```javascript
// 화살표 함수 사용
langBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    langDropdown.classList.toggle('hidden');
});

// 외부 클릭 처리
document.addEventListener('click', () => langDropdown.classList.add('hidden'));
```

### 다크 모드 토글
```javascript
const darkModeBtn = document.getElementById('darkModeBtn');
darkModeBtn.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
});
```

### 콘솔 로그 (디버깅용)
```javascript
// 이벤트마다 로그 기록 (개발 중)
console.log('[Booking] 서비스 선택:', serviceName);
console.log('[Login] 폼 제출');

// 프로덕션 배포 전 제거 또는 조건부 처리
if (window.DEBUG) {
    console.log('[Debug]', data);
}
```

---

## 6. 컴포넌트 구조

### 언어 선택 드롭다운
```html
<div class="relative">
    <button id="langBtn" class="flex items-center space-x-1 px-3 py-2
                                text-sm font-medium text-zinc-600 dark:text-zinc-300
                                hover:text-blue-600 dark:hover:text-blue-400
                                rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
        </svg>
        <span>KO</span>
    </button>
    <div id="langDropdown" class="hidden absolute right-0 mt-2 w-32
                                   bg-white dark:bg-zinc-800 rounded-lg shadow-lg
                                   border dark:border-zinc-700 py-1 z-50">
        <a href="?lang=ko" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300
                                  hover:bg-zinc-100 dark:hover:bg-zinc-700">한국어</a>
        <a href="?lang=en" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300
                                  hover:bg-zinc-100 dark:hover:bg-zinc-700">English</a>
        <a href="?lang=ja" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300
                                  hover:bg-zinc-100 dark:hover:bg-zinc-700">日本語</a>
    </div>
</div>
```

### 다크 모드 토글 버튼
```html
<button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300
                                hover:text-blue-600 dark:hover:text-blue-400
                                rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
    <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>
    <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
    </svg>
</button>
```

---

## 7. 알림 메시지

### 성공 메시지
```html
<div class="p-4 bg-green-50 dark:bg-green-900/30
            border border-green-200 dark:border-green-800 rounded-lg">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-green-700 dark:text-green-300 text-sm">성공 메시지</span>
    </div>
</div>
```

### 에러 메시지
```html
<div class="p-4 bg-red-50 dark:bg-red-900/30
            border border-red-200 dark:border-red-800 rounded-lg">
    <div class="flex items-center">
        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-red-700 dark:text-red-300 text-sm">에러 메시지</span>
    </div>
</div>
```

### 경고 메시지
```html
<div class="p-4 bg-amber-50 dark:bg-amber-900/20
            border border-amber-200 dark:border-amber-800 rounded-lg">
    <div class="flex items-start">
        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 mr-2"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <p class="text-sm text-amber-700 dark:text-amber-300">경고 메시지</p>
    </div>
</div>
```

---

## 8. 반응형 디자인

### 브레이크포인트
```
sm: 640px   - 모바일 가로
md: 768px   - 태블릿
lg: 1024px  - 데스크톱
xl: 1280px  - 대형 화면
```

### 그리드 패턴
```html
<!-- 1 → 2 → 4 컬럼 -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

<!-- 1 → 2 컬럼 -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

<!-- 1 → 3 컬럼 -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
```

### 숨김/표시
```html
<!-- 모바일에서 숨김 -->
<nav class="hidden md:flex items-center">

<!-- 데스크톱에서 숨김 -->
<button class="block md:hidden">
```

---

## 9. 접근성

### 포커스 스타일
```html
<input class="... focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
<button class="... focus:outline-none focus:ring-2 focus:ring-blue-500">
```

### 레이블
```html
<label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
    이메일 <span class="text-red-500">*</span>
</label>
<input type="email" id="email" name="email" required>
```

### 아이콘 + 텍스트
```html
<button class="flex items-center">
    <svg class="w-5 h-5 mr-2" ...></svg>
    <span>버튼 텍스트</span>
</button>
```

---

## 10. 성능 최적화

### 트랜지션
```html
<!-- 색상 변화만 트랜지션 -->
<div class="transition-colors">

<!-- 전체 트랜지션 (필요시) -->
<div class="transition-all duration-200">

<!-- 호버 시 변환 -->
<div class="hover:scale-105 transition-transform">
```

### 이미지 최적화
```html
<img src="image.jpg"
     alt="설명"
     loading="lazy"
     class="w-full h-auto object-cover">
```

---

## 버전 정보

- **문서 버전**: 1.0.0
- **최종 수정일**: 2026-02-28
- **적용 대상**: RezlyX 프로젝트 전체
