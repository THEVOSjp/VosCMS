<?php
/**
 * 관리자 헤더 프로필 드롭다운 메뉴 정의
 *
 * 이 파일은 Core 업데이트 시 덮어쓰지 않습니다.
 * 플러그인은 plugin.json의 menus.admin_dropdown으로 메뉴를 추가합니다.
 *
 * 구조:
 *   label    - 번역 키 또는 다국어 배열
 *   url      - baseUrl 기준 상대 경로 ({admin_path} 치환 지원)
 *   icon     - SVG path d 속성
 *   position - 정렬 순서
 *   type     - 'link' (기본), 'danger' (빨간색/로그아웃)
 */
return [
    [
        'label' => 'common.nav.home',
        'url' => '/',
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'position' => 0,
    ],
    [
        'label' => 'common.nav.mypage',
        'url' => '/mypage',
        'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        'position' => 10,
    ],
    [
        'label' => 'common.buttons.logout',
        'url' => '/{admin_path}/logout',
        'icon' => 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
        'position' => 99,
        'type' => 'danger',
    ],
];
