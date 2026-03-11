# RezlyX 회원 스킨 시스템

회원 페이지(로그인, 회원가입, 마이페이지, 비밀번호 찾기)의 디자인을 쉽게 변경할 수 있는 스킨 시스템입니다.

---

## 목차

1. [개요](#1-개요)
2. [폴더 구조](#2-폴더-구조)
3. [스킨 설정 (config.php)](#3-스킨-설정-configphp)
4. [MemberSkinLoader 클래스](#4-memberskinloader-클래스)
5. [템플릿 작성](#5-템플릿-작성)
6. [컬러셋 시스템](#6-컬러셋-시스템)
7. [관리자 설정](#7-관리자-설정)
8. [새 스킨 만들기](#8-새-스킨-만들기)

---

## 1. 개요

회원 스킨 시스템은 다음 기능을 제공합니다:

- **스킨 세트**: 로그인, 회원가입, 마이페이지, 비밀번호 찾기 페이지를 하나의 세트로 관리
- **컬러셋**: 각 스킨별 여러 색상 테마 지원
- **관리자 UI**: 미리보기 카드로 스킨 선택
- **다국어 지원**: 번역 데이터 주입 가능

---

## 2. 폴더 구조

```
skins/member/
├── default/                    # 기본 스킨
│   ├── config.php             # 스킨 설정
│   ├── login.php              # 로그인 템플릿
│   ├── register.php           # 회원가입 템플릿
│   ├── mypage.php             # 마이페이지 템플릿
│   ├── password_reset.php     # 비밀번호 찾기 템플릿
│   ├── profile_edit.php       # 프로필 수정 템플릿
│   ├── preview.png            # 스킨 미리보기 이미지
│   ├── thumbnail.png          # 썸네일 이미지
│   └── components/            # 재사용 컴포넌트
│       ├── header.php
│       ├── footer.php
│       ├── sidebar.php
│       ├── form_input.php
│       └── social_login.php
├── modern/                     # 추가 스킨 예시
│   ├── config.php
│   └── ...
└── minimal/
    ├── config.php
    └── ...
```

---

## 3. 스킨 설정 (config.php)

각 스킨 폴더에는 `config.php` 파일이 필요합니다.

```php
<?php
return [
    // 기본 정보
    'name' => 'Default',
    'version' => '1.0.0',
    'author' => 'RezlyX',
    'description' => '기본 회원 스킨 - 깔끔하고 심플한 디자인',

    // 미리보기 이미지
    'preview' => 'preview.png',
    'thumbnail' => 'thumbnail.png',

    // 컬러셋 정의
    'colorsets' => [
        'default' => [
            'name' => '기본',
            'primary' => '#3B82F6',
            'secondary' => '#6B7280',
            'accent' => '#10B981',
            'background' => '#FFFFFF',
            'text' => '#1F2937',
        ],
        'dark' => [
            'name' => '다크',
            'primary' => '#60A5FA',
            'secondary' => '#9CA3AF',
            'accent' => '#34D399',
            'background' => '#1F2937',
            'text' => '#F9FAFB',
        ],
    ],

    // 포함된 페이지
    'pages' => [
        'login' => ['file' => 'login.php', 'name' => '로그인'],
        'register' => ['file' => 'register.php', 'name' => '회원가입'],
        'mypage' => ['file' => 'mypage.php', 'name' => '마이페이지'],
        'password_reset' => ['file' => 'password_reset.php', 'name' => '비밀번호 찾기'],
    ],

    // 컴포넌트
    'components' => [
        'social_login' => 'components/social_login.php',
    ],

    // 스킨 옵션
    'options' => [
        'show_social_login' => true,
        'show_remember_me' => true,
        'form_style' => 'card',
        'animation' => true,
    ],
];
```

---

## 4. MemberSkinLoader 클래스

### 위치
`rzxlib/Core/Skin/MemberSkinLoader.php`

### 기본 사용법

```php
use RzxLib\Core\Skin\MemberSkinLoader;

// 로더 초기화
$skinLoader = new MemberSkinLoader(
    skinBasePath: BASE_PATH . '/skins/member',
    skin: 'modern'
);

// DB 설정 주입 (모듈 데이터 자동 수집에 필요)
$skinLoader->setSiteSettings($siteSettings);

// 페이지 렌더링 (언어, 로고, 소셜 로그인 데이터는 모듈이 자동 주입)
echo $skinLoader->render('login', [
    'errors' => $errors ?? [],
    'oldInput' => $_POST,
    'csrfToken' => $csrfToken,
    'registerUrl' => $baseUrl . '/register',
    'passwordResetUrl' => $baseUrl . '/forgot-password',
    'baseUrl' => $baseUrl,
]);
```

### 모듈 기반 자동 데이터 주입

`setSiteSettings()` 호출 후 `render()`/`renderComponent()` 시 다음 데이터가 자동 주입됩니다:

| 변수 | 모듈 | 설명 |
|------|------|------|
| `$languages` | LanguageModule | 활성 언어 목록 `['ko'=>'한국어', ...]` |
| `$allLanguages` | LanguageModule | 전체 언어 정보 (name+native) |
| `$supportedCodes` | LanguageModule | 활성 언어 코드 배열 |
| `$currentLocale` | LanguageModule | 현재 로케일 |
| `$defaultLocale` | LanguageModule | 기본 로케일 |
| `$currentLangInfo` | LanguageModule | 현재 언어 정보 |
| `$siteName` | LogoModule | 사이트 이름 |
| `$logoType` | LogoModule | 로고 타입 (text/image) |
| `$logoImage` | LogoModule | 로고 이미지 경로 |
| `$socialProviders` | SocialLoginModule | 활성 소셜 로그인 제공자 |
| `$socialEnabled` | SocialLoginModule | 소셜 로그인 활성화 여부 |
| `$siteSettings` | MemberSkinLoader | DB 설정 원본 (공용 컴포넌트용) |

### 주요 메서드

```php
// DB 설정 주입 (필수)
$skinLoader->setSiteSettings($siteSettings);

// 스킨 변경
$skinLoader->setSkin('modern');

// 컬러셋 변경
$skinLoader->setColorset('dark');

// 번역 데이터 설정
$skinLoader->setTranslations([...]);

// 사용 가능한 스킨 목록
$skins = $skinLoader->getAvailableSkins();

// 스킨/페이지 존재 여부
$skinLoader->skinExists('modern');
$skinLoader->pageExists('login');

// 컴포넌트 렌더링
echo $skinLoader->renderComponent('social_login', [...]);

// CSS 변수 생성
$cssVariables = $skinLoader->getCssVariables();
```

---

## 5. 템플릿 작성

### 사용 가능한 변수

모든 템플릿에서 사용할 수 있는 변수:

| 변수 | 설명 |
|------|------|
| `$config` | 스킨 설정 배열 |
| `$colorset` | 현재 컬러셋 |
| `$translations` | 번역 데이터 |
| `$siteSettings` | DB 설정 (공용 컴포넌트용) |
| `$languages` | 활성 언어 목록 (모듈 자동 주입) |
| `$currentLocale` | 현재 로케일 (모듈 자동 주입) |
| `$siteName` | 사이트 이름 (모듈 자동 주입) |
| `$logoImage` | 로고 이미지 (모듈 자동 주입) |
| `$socialProviders` | 소셜 로그인 제공자 (모듈 자동 주입) |
| `$baseUrl` | 기본 URL |

### 페이지별 추가 변수

**로그인 (login.php)**
```php
$errors          // 에러 메시지 배열
$oldInput        // 이전 입력값
$csrfToken       // CSRF 토큰
$registerUrl     // 회원가입 URL
$passwordResetUrl // 비밀번호 찾기 URL
```

**회원가입 (register.php)**
```php
$errors          // 에러 메시지 배열
$oldInput        // 이전 입력값
$csrfToken       // CSRF 토큰
$terms           // 약관 데이터 배열
$loginUrl        // 로그인 URL
```

**마이페이지 (mypage.php)**
```php
$user            // 사용자 정보
$stats           // 통계 (게시글 수, 댓글 수 등)
$profileEditUrl  // 프로필 수정 URL
$logoutUrl       // 로그아웃 URL
```

**비밀번호 찾기 (password_reset.php)**
```php
$step            // 현재 단계 (email, code, reset, complete)
$errors          // 에러 메시지
$email           // 입력된 이메일
$token           // 재설정 토큰
$loginUrl        // 로그인 URL
```

### 템플릿 예시

```php
<?php
// login.php
$colors = $colorset ?? $config['colorsets']['default'];
?>

<style>
:root {
    --skin-primary: <?= $colors['primary'] ?>;
    --skin-secondary: <?= $colors['secondary'] ?>;
    --skin-text: <?= $colors['text'] ?>;
}
</style>

<div class="login-container">
    <h1><?= $translations['login_title'] ?? '로그인' ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <input type="email" name="email" value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>">
        <input type="password" name="password">
        <button type="submit" style="background: var(--skin-primary)">
            <?= $translations['login_button'] ?? '로그인' ?>
        </button>
    </form>
</div>
```

---

## 6. 컬러셋 시스템

### CSS 변수 사용

각 템플릿에서 CSS 변수를 통해 컬러셋을 적용합니다:

```css
:root {
    --skin-primary: #3B82F6;
    --skin-secondary: #6B7280;
    --skin-accent: #10B981;
    --skin-background: #FFFFFF;
    --skin-text: #1F2937;
}

.btn-primary {
    background-color: var(--skin-primary);
    color: white;
}

.text-secondary {
    color: var(--skin-secondary);
}
```

### Tailwind CSS와 함께 사용

```html
<button class="px-4 py-2 rounded-lg text-white"
        style="background-color: var(--skin-primary);">
    로그인
</button>
```

---

## 7. 관리자 설정

### 스킨 선택 UI

관리자 페이지에서 스킨을 카드 형식으로 선택할 수 있습니다.

**위치**: `resources/views/admin/members/settings/design.php`

### 저장되는 설정값

| 키 | 설명 |
|----|------|
| `member_skin` | 선택된 스킨명 |
| `member_colorset` | 선택된 컬러셋 |

### 설정 로드

```php
// _init.php에서 기본값 설정
$defaultMemberSettings = [
    'member_skin' => 'default',
    'member_colorset' => 'default',
    // ...
];
```

---

## 8. 새 스킨 만들기

### 1단계: 폴더 생성

```
skins/member/my-skin/
├── config.php
├── login.php
├── register.php
├── mypage.php
├── password_reset.php
├── preview.png (권장: 800x600)
├── thumbnail.png (권장: 400x300)
└── components/
    ├── social_login.php
    └── mypage-sidebar.php  # (선택) 마이페이지 사이드바 디자인 오버라이드
```

### 2단계: config.php 작성

```php
<?php
return [
    'name' => 'My Custom Skin',
    'version' => '1.0.0',
    'author' => 'Your Name',
    'description' => '나만의 커스텀 스킨',

    'colorsets' => [
        'default' => [
            'name' => '기본',
            'primary' => '#FF6B6B',
            'secondary' => '#4ECDC4',
            'accent' => '#FFE66D',
            'background' => '#FFFFFF',
            'text' => '#2C3E50',
        ],
    ],

    'pages' => [
        'login' => ['file' => 'login.php', 'name' => '로그인'],
        'register' => ['file' => 'register.php', 'name' => '회원가입'],
        'mypage' => ['file' => 'mypage.php', 'name' => '마이페이지'],
        'password_reset' => ['file' => 'password_reset.php', 'name' => '비밀번호 찾기'],
    ],

    'options' => [
        'show_social_login' => true,
        'form_style' => 'modern',
    ],
];
```

### 3단계: 템플릿 작성

기존 default 스킨의 템플릿을 복사하여 수정합니다.

### 4단계: 미리보기 이미지 추가

- `preview.png`: 스킨 상세 미리보기 (권장 800x600px)
- `thumbnail.png`: 관리자 선택 UI용 썸네일 (권장 400x300px)

### 5단계: 관리자에서 선택

관리자 > 회원 설정 > 디자인에서 새 스킨을 선택할 수 있습니다.

---

## 버전 정보

- **문서 버전**: 1.2.0
- **최종 수정일**: 2026-03-11
- **적용 대상**: RezlyX Member Skin System

### 변경 이력

| 버전 | 날짜 | 변경 내용 |
|------|------|-----------|
| 1.2.0 | 2026-03-11 | 마이페이지 공통 사이드바 컴포넌트 + 스킨 오버라이드 지원 |
| 1.1.0 | 2026-03-09 | 모듈 기반 아키텍처, 공용 언어 선택기, 자동 데이터 주입 |
| 1.0.0 | 2026-03-03 | 최초 작성 |
