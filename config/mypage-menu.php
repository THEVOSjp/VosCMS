<?php
/**
 * 마이페이지 사이드바 메뉴 정의
 *
 * 이 파일은 Core 업데이트 시 덮어쓰지 않습니다.
 * 플러그인은 plugin.json의 menus.mypage로 메뉴를 추가합니다.
 *
 * 구조:
 *   key      - 메뉴 고유 키 (sidebarActive 매칭용)
 *   url      - baseUrl 기준 상대 경로
 *   label    - 다국어 제목 배열 또는 번역 키
 *   icon     - SVG path 내용
 *   position - 정렬 순서 (0=최상단, 플러그인 메뉴는 50 기본)
 *   section  - 'main' 또는 'bottom' (하단 구분선 아래)
 *   style    - 'danger' (빨간색), 'muted' (회색)
 */
return [
    // ── 메인 메뉴 ──
    [
        'key' => 'dashboard',
        'url' => '/mypage',
        'label' => 'auth.mypage.menu.dashboard',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
        'position' => 0,
        'section' => 'main',
    ],
    [
        'key' => 'profile',
        'url' => '/mypage/profile',
        'label' => 'auth.mypage.menu.profile',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'position' => 20,
        'section' => 'main',
    ],
    [
        'key' => 'messages',
        'url' => '/mypage/messages',
        'label' => 'auth.mypage.menu.messages',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>',
        'position' => 30,
        'section' => 'main',
    ],
    [
        'key' => 'settings',
        'url' => '/mypage/settings',
        'label' => 'auth.mypage.menu.settings',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'position' => 40,
        'section' => 'main',
    ],
    [
        'key' => 'password',
        'url' => '/mypage/password',
        'label' => 'auth.mypage.menu.password',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'position' => 50,
        'section' => 'main',
    ],

    // ── 하단 메뉴 (구분선 아래) ──
    [
        'key' => 'withdraw',
        'url' => '/mypage/withdraw',
        'label' => 'auth.mypage.menu.withdraw',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
        'position' => 90,
        'section' => 'bottom',
        'style' => 'muted',
    ],
    [
        'key' => 'logout',
        'url' => '/logout',
        'label' => 'auth.mypage.menu.logout',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
        'position' => 99,
        'section' => 'bottom',
        'style' => 'danger',
    ],
];
