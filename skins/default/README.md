# RezlyX Default Theme

RezlyX 기본 테마입니다. Zinc 컬러 스킴을 사용한 모던하고 깔끔한 디자인으로, 라이트/다크 모드를 지원합니다.

## 특징

- **Zinc 컬러 스킴**: 눈의 피로를 줄이는 뉴트럴 톤 컬러
- **다크 모드**: 시스템 설정 연동 및 수동 전환 지원
- **반응형 디자인**: 모바일, 태블릿, 데스크톱 완벽 대응
- **AlpineJS**: 경량 인터랙티브 컴포넌트
- **Tailwind CSS**: 유틸리티 퍼스트 CSS 프레임워크
- **다국어 지원**: 한국어, 영어, 일본어
- **접근성**: WCAG 가이드라인 준수

## 디렉토리 구조

```
skins/default/
├── config.php          # 테마 설정 파일
├── README.md           # 이 문서
├── layouts/            # 레이아웃 템플릿
│   ├── main.php        # 메인 레이아웃 (헤더+콘텐츠+푸터)
│   ├── blank.php       # 빈 레이아웃 (콘텐츠만)
│   └── auth.php        # 인증 페이지 레이아웃
├── components/         # 재사용 컴포넌트
│   ├── header.php      # 헤더 네비게이션
│   ├── footer.php      # 푸터
│   ├── breadcrumbs.php # 브레드크럼
│   ├── alerts.php      # 알림 컴포넌트
│   └── cards.php       # 카드 컴포넌트
└── pages/              # 페이지 템플릿
    ├── home.php        # 홈페이지
    ├── services.php    # 서비스 목록
    ├── about.php       # 소개 페이지
    └── contact.php     # 문의 페이지
```

## 설정 (config.php)

### 기본 정보

```php
'name' => 'Default',
'version' => '1.0.0',
'description' => 'RezlyX 기본 스킨',
```

### 색상 설정

Zinc 컬러 스킴을 기본으로 사용합니다:

```php
'colors' => [
    'background' => [
        'light' => '#FAFAFA',  // zinc-50
        'dark' => '#18181B',   // zinc-900
    ],
    'surface' => [
        'light' => '#FFFFFF',
        'dark' => '#27272A',   // zinc-800
    ],
    // ...
],
```

### 레이아웃 설정

```php
'layout' => [
    'header' => true,
    'footer' => true,
    'sidebar' => false,
    'sticky_header' => true,
],
```

### 기능 토글

```php
'features' => [
    'dark_mode' => true,
    'language_selector' => true,
    'breadcrumbs' => true,
    'scroll_to_top' => true,
    'toast_notifications' => true,
],
```

## 레이아웃 사용법

### 메인 레이아웃 (main.php)

표준 페이지에 사용되는 기본 레이아웃입니다.

```php
<?php
$pageTitle = '페이지 제목';
$metaDescription = '페이지 설명';
$breadcrumbs = [
    ['label' => '홈', 'url' => '/'],
    ['label' => '현재 페이지'],
];

ob_start();
?>
<!-- 페이지 콘텐츠 -->
<?php
$content = ob_get_clean();
include __DIR__ . '/layouts/main.php';
?>
```

### 변수 목록

| 변수 | 타입 | 설명 | 기본값 |
|------|------|------|--------|
| `$pageTitle` | string | 페이지 제목 | 앱 이름 |
| `$metaDescription` | string | 메타 설명 | '' |
| `$bodyClass` | string | body 추가 클래스 | '' |
| `$breadcrumbs` | array | 브레드크럼 배열 | null |
| `$headScripts` | string | head 추가 스크립트 | null |
| `$footerScripts` | string | footer 추가 스크립트 | null |

## 컴포넌트 사용법

### 알림 (alerts.php)

```php
<?php include __DIR__ . '/components/alerts.php'; ?>

<?php renderAlert('success', '저장되었습니다!'); ?>
<?php renderAlert('error', '오류가 발생했습니다.'); ?>
<?php renderAlert('warning', '주의가 필요합니다.'); ?>
<?php renderAlert('info', '참고 정보입니다.'); ?>
```

### 카드 (cards.php)

```php
<?php include __DIR__ . '/components/cards.php'; ?>

<!-- 기본 카드 -->
<?php echo renderCard('제목', '내용', '/link', '자세히 보기'); ?>

<!-- 서비스 카드 -->
<?php echo renderServiceCard(
    '서비스명',
    '설명',
    '50,000',
    '/booking',
    '<svg>...</svg>'
); ?>

<!-- 기능 카드 -->
<?php echo renderFeatureCard(
    '기능명',
    '기능 설명',
    '<svg>...</svg>'
); ?>

<!-- 통계 카드 -->
<?php echo renderStatCard('1,000+', '고객 수', '<svg>...</svg>'); ?>

<!-- 후기 카드 -->
<?php echo renderTestimonialCard(
    '정말 좋은 서비스입니다.',
    '김철수',
    '고객'
); ?>
```

## 다크 모드

### 자동 감지

시스템 설정에 따라 자동으로 다크 모드가 적용됩니다:

```javascript
if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.classList.add('dark');
}
```

### 수동 전환

```javascript
function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
}
```

### CSS 작성 규칙

```html
<!-- 라이트 모드와 다크 모드 클래스 동시 지정 -->
<div class="bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white">
    콘텐츠
</div>
```

## 반응형 브레이크포인트

Tailwind CSS 기본 브레이크포인트를 사용합니다:

| 접두사 | 최소 너비 | CSS |
|--------|----------|-----|
| `sm` | 640px | `@media (min-width: 640px)` |
| `md` | 768px | `@media (min-width: 768px)` |
| `lg` | 1024px | `@media (min-width: 1024px)` |
| `xl` | 1280px | `@media (min-width: 1280px)` |
| `2xl` | 1536px | `@media (min-width: 1536px)` |

## 커스터마이징

### 컴포넌트 오버라이드

config.php에서 커스텀 컴포넌트 경로를 지정할 수 있습니다:

```php
'components' => [
    'header' => 'custom/header',  // components/custom/header.php 사용
],
```

### 새 레이아웃 추가

1. `layouts/` 디렉토리에 새 PHP 파일 생성
2. 기존 레이아웃 구조 참고하여 작성
3. 컨트롤러에서 레이아웃 지정

### 색상 변경

config.php의 colors 섹션을 수정하거나, Tailwind 클래스를 직접 변경합니다.

## 접근성

- Skip to content 링크
- 키보드 네비게이션 지원
- ARIA 레이블 적용
- 충분한 색상 대비
- 포커스 스타일 제공

## 브라우저 지원

- Chrome (최신)
- Firefox (최신)
- Safari (최신)
- Edge (최신)

## 라이선스

MIT License
