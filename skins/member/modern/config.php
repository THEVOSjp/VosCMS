<?php
/**
 * RezlyX Member Skin - Modern
 * 모던 회원 스킨 설정 파일 (헤더, 다크모드, 언어선택 포함)
 */

return [
    // 스킨 기본 정보
    'name' => 'Modern',
    'version' => '1.0.0',
    'author' => 'RezlyX',
    'description' => [
        'ko' => '모던 회원 스킨 - 헤더, 다크모드, 언어선택 기능 포함',
        'en' => 'Modern member skin - Includes header, dark mode, language selector',
        'ja' => 'モダン会員スキン - ヘッダー、ダークモード、言語選択機能付き',
        'zh_CN' => '现代会员皮肤 - 包含标题栏、暗黑模式、语言选择功能',
        'zh_TW' => '現代會員面板 - 含標題列、暗黑模式、語言選擇功能',
        'de' => 'Modernes Mitglieder-Skin - Mit Header, Dunkelmodus, Sprachauswahl',
        'es' => 'Skin de miembro moderno - Incluye encabezado, modo oscuro, selector de idioma',
        'fr' => 'Skin membre moderne - Avec en-tête, mode sombre, sélecteur de langue',
        'id' => 'Skin anggota modern - Termasuk header, mode gelap, pemilih bahasa',
        'mn' => 'Орчин үеийн гишүүний загвар - Толгой, харанхуй горим, хэл сонгох',
        'ru' => 'Современный скин участника - С шапкой, тёмной темой, выбором языка',
        'tr' => 'Modern üye teması - Başlık, karanlık mod, dil seçici dahil',
        'vi' => 'Giao diện thành viên hiện đại - Bao gồm header, chế độ tối, chọn ngôn ngữ',
    ],

    // 스킨 미리보기 이미지
    'preview' => 'preview.png',
    'thumbnail' => 'thumbnail.png',

    // 지원하는 컬러셋
    'colorsets' => [
        'default' => [
            'name' => '기본 (블루)',
            'primary' => '#3B82F6',
            'primary_hover' => '#2563EB',
            'primary_dark' => '#60A5FA',
            'secondary' => '#6B7280',
            'accent' => '#10B981',
            'background' => '#F9FAFB',
            'background_dark' => '#18181B',
            'card' => '#FFFFFF',
            'card_dark' => '#27272A',
            'text' => '#1F2937',
            'text_dark' => '#F4F4F5',
            'border' => '#E5E7EB',
            'border_dark' => '#3F3F46',
        ],
        'purple' => [
            'name' => '퍼플',
            'primary' => '#8B5CF6',
            'primary_hover' => '#7C3AED',
            'primary_dark' => '#A78BFA',
            'secondary' => '#6B7280',
            'accent' => '#EC4899',
            'background' => '#FAF5FF',
            'background_dark' => '#18181B',
            'card' => '#FFFFFF',
            'card_dark' => '#27272A',
            'text' => '#1F2937',
            'text_dark' => '#F4F4F5',
            'border' => '#E9D5FF',
            'border_dark' => '#3F3F46',
        ],
        'green' => [
            'name' => '그린',
            'primary' => '#10B981',
            'primary_hover' => '#059669',
            'primary_dark' => '#34D399',
            'secondary' => '#6B7280',
            'accent' => '#06B6D4',
            'background' => '#F0FDF4',
            'background_dark' => '#18181B',
            'card' => '#FFFFFF',
            'card_dark' => '#27272A',
            'text' => '#1F2937',
            'text_dark' => '#F4F4F5',
            'border' => '#BBF7D0',
            'border_dark' => '#3F3F46',
        ],
        'orange' => [
            'name' => '오렌지',
            'primary' => '#F97316',
            'primary_hover' => '#EA580C',
            'primary_dark' => '#FB923C',
            'secondary' => '#6B7280',
            'accent' => '#EAB308',
            'background' => '#FFFBEB',
            'background_dark' => '#18181B',
            'card' => '#FFFFFF',
            'card_dark' => '#27272A',
            'text' => '#1F2937',
            'text_dark' => '#F4F4F5',
            'border' => '#FED7AA',
            'border_dark' => '#3F3F46',
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
    ],

    // 컴포넌트
    'components' => [
        'header' => 'components/header.php',
        'social_login' => 'components/social_login.php',
    ],

    // 스킨 옵션
    'options' => [
        'show_header' => true,
        'show_dark_mode' => true,
        'show_language_selector' => true,
        'show_social_login' => true,
        'show_remember_me' => true,
        'show_forgot_password' => true,
        'form_style' => 'card', // card, minimal, bordered
        'animation' => true,
    ],

    // 지원 언어
    'languages' => [
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
    ],
];
