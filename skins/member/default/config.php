<?php
/**
 * RezlyX Member Skin - Default
 * 기본 회원 스킨 설정 파일
 */

return [
    // 스킨 기본 정보
    'name' => 'Default',
    'version' => '1.0.0',
    'author' => 'RezlyX',
    'description' => '기본 회원 스킨 - 깔끔하고 심플한 디자인',

    // 스킨 미리보기 이미지
    'preview' => 'preview.png',
    'thumbnail' => 'thumbnail.png',

    // 지원하는 컬러셋
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
        'blue' => [
            'name' => '블루',
            'primary' => '#2563EB',
            'secondary' => '#64748B',
            'accent' => '#0EA5E9',
            'background' => '#F8FAFC',
            'text' => '#0F172A',
        ],
        'green' => [
            'name' => '그린',
            'primary' => '#059669',
            'secondary' => '#6B7280',
            'accent' => '#10B981',
            'background' => '#F0FDF4',
            'text' => '#14532D',
        ],
    ],

    // 포함된 페이지 템플릿
    'pages' => [
        'login' => [
            'file' => 'login.php',
            'name' => '로그인',
        ],
        'register' => [
            'file' => 'register.php',
            'name' => '회원가입',
        ],
        'mypage' => [
            'file' => 'mypage.php',
            'name' => '마이페이지',
        ],
        'password_reset' => [
            'file' => 'password_reset.php',
            'name' => '비밀번호 찾기',
        ],
        'profile_edit' => [
            'file' => 'profile_edit.php',
            'name' => '프로필 수정',
        ],
    ],

    // 컴포넌트
    'components' => [
        'header' => 'components/header.php',
        'footer' => 'components/footer.php',
        'sidebar' => 'components/sidebar.php',
        'form_input' => 'components/form_input.php',
        'social_login' => 'components/social_login.php',
    ],

    // 스킨 옵션
    'options' => [
        'show_social_login' => true,
        'show_remember_me' => true,
        'show_forgot_password' => true,
        'form_style' => 'card', // card, minimal, bordered
        'animation' => true,
    ],
];
