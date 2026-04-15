<?php
/**
 * Default Skin Configuration
 *
 * RezlyX 기본 테마 설정 파일
 * 이 파일을 수정하여 테마의 동작을 커스터마이즈할 수 있습니다.
 *
 * @package RezlyX\Skins\Default
 * @version 1.0.0
 */

return [
    // =========================================================================
    // 테마 기본 정보
    // =========================================================================
    'name' => 'Default',
    'version' => '1.0.0',
    'author' => 'RezlyX Team',
    'description' => 'RezlyX 기본 스킨 - Zinc 다크모드 지원',
    'homepage' => 'https://rezlyx.com',
    'license' => 'MIT',

    // =========================================================================
    // 지원 언어
    // =========================================================================
    'locales' => ['ko', 'en', 'ja'],
    'default_locale' => 'ko',

    // =========================================================================
    // 색상 설정
    // =========================================================================
    'colors' => [
        // 메인 컬러 (브랜드 색상)
        'primary' => [
            'light' => '#3B82F6',  // blue-500
            'dark' => '#60A5FA',   // blue-400
        ],
        // 보조 컬러
        'secondary' => [
            'light' => '#64748B',  // slate-500
            'dark' => '#94A3B8',   // slate-400
        ],
        // 강조 컬러
        'accent' => [
            'light' => '#F59E0B',  // amber-500
            'dark' => '#FBBF24',   // amber-400
        ],
        // 배경색 (Zinc 컬러 스킴)
        'background' => [
            'light' => '#FAFAFA',  // zinc-50
            'dark' => '#18181B',   // zinc-900
        ],
        // 카드/컴포넌트 배경
        'surface' => [
            'light' => '#FFFFFF',  // white
            'dark' => '#27272A',   // zinc-800
        ],
        // 텍스트 색상
        'text' => [
            'light' => '#18181B',  // zinc-900
            'dark' => '#FFFFFF',   // white
        ],
        // 보조 텍스트
        'text_muted' => [
            'light' => '#71717A',  // zinc-500
            'dark' => '#A1A1AA',   // zinc-400
        ],
    ],

    // =========================================================================
    // 레이아웃 설정
    // =========================================================================
    'layout' => [
        'header' => true,
        'footer' => true,
        'sidebar' => false,
        'sidebar_position' => 'left',  // 'left' 또는 'right'
        'container_max_width' => '7xl', // Tailwind max-w 클래스
        'sticky_header' => true,
    ],

    // =========================================================================
    // 기능 설정
    // =========================================================================
    'features' => [
        // 다크 모드
        'dark_mode' => true,
        'dark_mode_default' => 'system', // 'light', 'dark', 'system'

        // 언어 선택기
        'language_selector' => true,

        // 브레드크럼
        'breadcrumbs' => true,

        // 스크롤 투 탑 버튼
        'scroll_to_top' => true,

        // 토스트 알림
        'toast_notifications' => true,
        'toast_position' => 'bottom-right', // 'top-right', 'bottom-right', etc.
        'toast_duration' => 3000, // ms

        // PWA 지원
        'pwa' => true,

        // 접근성
        'skip_to_content' => true,

        // 애니메이션
        'animations' => true,
        'reduced_motion' => false, // prefers-reduced-motion 지원
    ],

    // =========================================================================
    // 네비게이션 설정
    // =========================================================================
    'navigation' => [
        // 메인 네비게이션 메뉴
        'main' => [
            ['label' => '홈', 'url' => '/', 'icon' => 'home'],
            ['label' => '서비스', 'url' => '/services', 'icon' => 'briefcase'],
            ['label' => '예약하기', 'url' => '/booking', 'icon' => 'calendar'],
            ['label' => '소개', 'url' => '/about', 'icon' => 'info'],
            ['label' => '문의', 'url' => '/contact', 'icon' => 'mail'],
        ],
        // 푸터 퀵 링크
        'footer_links' => [
            ['label' => '이용약관', 'url' => '/terms'],
            ['label' => '개인정보처리방침', 'url' => '/privacy'],
            ['label' => '자주 묻는 질문', 'url' => '/faq'],
        ],
    ],

    // =========================================================================
    // 소셜 링크
    // =========================================================================
    'social' => [
        'facebook' => null,
        'twitter' => null,
        'instagram' => null,
        'youtube' => null,
        'linkedin' => null,
        'github' => null,
    ],

    // =========================================================================
    // 폰트 설정
    // =========================================================================
    'fonts' => [
        'family' => 'Pretendard',
        'cdn' => 'https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css',
        'fallback' => ['-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
    ],

    // =========================================================================
    // 외부 리소스
    // =========================================================================
    'assets' => [
        'tailwind_cdn' => true,
        'alpinejs_cdn' => true,
        'alpinejs_version' => '3.x.x',
    ],

    // =========================================================================
    // 컴포넌트 오버라이드
    // =========================================================================
    // 기본 컴포넌트 대신 커스텀 컴포넌트를 사용하려면 경로 지정
    'components' => [
        // 'header' => 'custom/header',
        // 'footer' => 'custom/footer',
        // 'breadcrumbs' => 'custom/breadcrumbs',
    ],

    // =========================================================================
    // 페이지별 설정
    // =========================================================================
    'pages' => [
        'home' => [
            'show_hero' => true,
            'show_features' => true,
            'show_services' => true,
            'show_testimonials' => true,
            'show_cta' => true,
        ],
        'services' => [
            'show_filter' => true,
            'show_search' => true,
            'items_per_page' => 9,
        ],
        'contact' => [
            'show_map' => true,
            'show_faq' => true,
        ],
    ],

    // =========================================================================
    // SEO 설정
    // =========================================================================
    'seo' => [
        'title_separator' => ' - ',
        'default_description' => 'RezlyX - 간편하고 스마트한 예약 시스템',
        'robots' => 'index, follow',
    ],

    // =========================================================================
    // 개발/디버그 설정
    // =========================================================================
    'debug' => [
        'show_grid' => false,
        'show_breakpoints' => false,
    ],
];
