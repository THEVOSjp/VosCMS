<?php
/**
 * VosCMS 시스템 페이지 정의
 *
 * 이 파일 하나로 페이지 관리, 메뉴 관리, 라우팅에서 통합 사용.
 * 플러그인은 plugin.json의 system_pages로 추가 가능.
 * Core 업데이트 시 이 파일은 보존됩니다.
 *
 * 구조:
 *   slug     - URL 경로 (예: 'terms', 'service/order')
 *   title    - 번역 키 또는 기본 제목
 *   icon     - SVG path (페이지 관리용) 또는 이모지 (메뉴 관리용)
 *   emoji    - 메뉴 관리 드롭다운에 표시할 이모지
 *   color    - 페이지 관리 아이콘 색상
 *   type     - 'widget' | 'document' | 'system'
 *   edit     - 편집 URL ({admin} = 관리자 경로 치환)
 *   view     - 시스템 페이지 뷰 파일 (type=system일 때)
 *   settings_view - 관리자 페이지 설정에 추가 탭으로 표시할 뷰 파일 (선택)
 *   settings_tab  - 추가 탭 라벨 번역 키 (선택, 없으면 '서비스 설정')
 */
return [
    // ── 코어 페이지 ──
    [
        'slug' => 'home',
        'title' => 'site.pages.home',
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'emoji' => '🏠',
        'color' => 'blue',
        'type' => 'widget',
        'edit' => '{admin}/site/pages/widget-builder?slug=home',
    ],
    [
        'slug' => 'terms',
        'title' => 'site.pages.terms',
        'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'emoji' => '📄',
        'color' => 'green',
        'type' => 'document',
        'edit' => '{admin}/site/pages/edit?slug=terms',
    ],
    [
        'slug' => 'privacy',
        'title' => 'site.pages.privacy',
        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'emoji' => '🔒',
        'color' => 'purple',
        'type' => 'document',
        'edit' => '{admin}/site/pages/edit?slug=privacy',
    ],
    [
        'slug' => 'data-policy',
        'title' => 'site.pages.data_policy',
        'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'emoji' => '🛡️',
        'color' => 'amber',
        'type' => 'document',
        'edit' => '{admin}/site/pages/compliance',
    ],
    [
        'slug' => 'refund-policy',
        'title' => 'site.pages.refund_policy',
        'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'emoji' => '💳',
        'color' => 'red',
        'type' => 'document',
        'edit' => '{admin}/site/pages/edit?slug=refund-policy',
    ],
    [
        'slug' => 'tokushoho',
        'title' => 'site.pages.tokushoho',
        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'emoji' => '📋',
        'color' => 'orange',
        'type' => 'document',
        'edit' => '{admin}/site/pages/edit?slug=tokushoho',
    ],
    [
        'slug' => 'funds-settlement',
        'title' => 'site.pages.funds_settlement',
        'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        'emoji' => '💰',
        'color' => 'teal',
        'type' => 'document',
        'edit' => '{admin}/site/pages/edit?slug=funds-settlement',
    ],

    // ── 다운로드 ──
    [
        'slug' => 'downloads',
        'title' => 'site.pages.downloads',
        'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
        'emoji' => '📥',
        'color' => 'blue',
        'type' => 'widget',
        'edit' => '{admin}/site/pages/widget-builder?slug=downloads',
    ],

    // ── 시스템 페이지 (본사 전용, 배포 제외 가능) ──
    [
        'slug' => 'service/order',
        'title' => 'site.pages.service_order',
        'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        'emoji' => '🛒',
        'color' => 'indigo',
        'type' => 'system',
        'view' => 'system/service/order.php',
        'edit' => '/service/order',
        'settings_view' => 'system/service/_settings.php',
        'settings_tab' => 'site.pages.tab_service',
    ],
    // 결제 완료 페이지 (페이지 관리 목록에 숨김, 라우팅용)
    [
        'slug' => 'service/order/complete',
        'title' => 'site.pages.service_complete',
        'icon' => 'M5 13l4 4L19 7',
        'emoji' => '✅',
        'color' => 'green',
        'type' => 'system',
        'view' => 'system/service/complete.php',
        'hidden' => true,
    ],
];
