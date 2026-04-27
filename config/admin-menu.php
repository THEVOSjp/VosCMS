<?php
/**
 * VosCMS 관리자 사이드바 메뉴 정의
 *
 * 이 파일은 Core 업데이트 시 덮어쓰지 않습니다.
 * plugin.json의 menus.admin과 동일한 구조를 사용합니다.
 *
 * 구조:
 *   id         - 메뉴 고유 ID
 *   title      - 다국어 제목 배열
 *   icon       - SVG path (d 속성값)
 *   route      - 관리자 상대 경로 ('' = 관리자 루트)
 *   permission - AdminAuth::can() 권한 키 (null = 항상 표시)
 *   master     - true이면 마스터 관리자만 표시
 *   position   - 정렬 순서 (영역 내 순서)
 *   section    - 'top' (코어 상단) | 'main' (자동 추가/플러그인, 기본) | 'bottom' (코어 하단)
 *   items      - 서브메뉴 배열 [{title, route, permission, master}]
 *   badge      - 뱃지 쿼리 (선택) [{query, color}]
 */
return [
    // ── 대시보드 ──
    [
        'id' => 'dashboard',
        'title' => [
            'ko'=>'대시보드','en'=>'Dashboard','ja'=>'ダッシュボード',
            'de'=>'Dashboard','es'=>'Panel','fr'=>'Tableau de bord',
            'id'=>'Dasbor','mn'=>'Хяналтын самбар','ru'=>'Панель',
            'tr'=>'Gösterge Paneli','vi'=>'Bảng điều khiển',
            'zh_CN'=>'仪表盘','zh_TW'=>'儀表板',
        ],
        'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'route' => '',
        'position' => 0,
        'section' => 'top',
    ],

    // ── 회원 관리 ──
    [
        'id' => 'members',
        'title' => [
            'ko'=>'회원 관리','en'=>'Members','ja'=>'会員管理',
            'de'=>'Mitglieder','es'=>'Miembros','fr'=>'Membres',
            'id'=>'Anggota','mn'=>'Гишүүд','ru'=>'Участники',
            'tr'=>'Üyeler','vi'=>'Thành viên',
            'zh_CN'=>'会员管理','zh_TW'=>'會員管理',
        ],
        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        'permission' => 'members',
        'position' => 10,
        'section' => 'top',
        'items' => [
            [
                'title' => [
                    'ko'=>'회원 목록','en'=>'Member List','ja'=>'会員一覧',
                    'de'=>'Mitgliederliste','es'=>'Lista de miembros','fr'=>'Liste des membres',
                    'id'=>'Daftar Anggota','mn'=>'Гишүүдийн жагсаалт','ru'=>'Список участников',
                    'tr'=>'Üye Listesi','vi'=>'Danh sách thành viên',
                    'zh_CN'=>'会员列表','zh_TW'=>'會員列表',
                ],
                'route' => 'members',
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
            ],
            [
                'title' => [
                    'ko'=>'회원 설정','en'=>'Member Settings','ja'=>'会員設定',
                    'de'=>'Mitgliedereinstellungen','es'=>'Configuración de miembros','fr'=>'Paramètres membres',
                    'id'=>'Pengaturan Anggota','mn'=>'Гишүүдийн тохиргоо','ru'=>'Настройки участников',
                    'tr'=>'Üye Ayarları','vi'=>'Cài đặt thành viên',
                    'zh_CN'=>'会员设置','zh_TW'=>'會員設定',
                ],
                'route' => 'members/settings',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            ],
            [
                'title' => [
                    'ko'=>'그룹 관리','en'=>'Member Groups','ja'=>'グループ管理',
                    'de'=>'Mitgliedsgruppen','es'=>'Grupos de miembros','fr'=>'Groupes de membres',
                    'id'=>'Grup Anggota','mn'=>'Гишүүдийн бүлэг','ru'=>'Группы участников',
                    'tr'=>'Üye Grupları','vi'=>'Nhóm thành viên',
                    'zh_CN'=>'分组管理','zh_TW'=>'群組管理',
                ],
                'route' => 'members/groups',
                'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
            ],
            [
                'title' => [
                    'ko'=>'포인트','en'=>'Points','ja'=>'ポイント',
                    'de'=>'Punkte','es'=>'Puntos','fr'=>'Points',
                    'id'=>'Poin','mn'=>'Оноо','ru'=>'Баллы',
                    'tr'=>'Puanlar','vi'=>'Điểm',
                    'zh_CN'=>'积分','zh_TW'=>'積分',
                ],
                'route' => 'points',
                'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            ],
            [
                'title' => [
                    'ko'=>'관리자 권한','en'=>'Admin Permissions','ja'=>'管理者権限',
                    'de'=>'Adminrechte','es'=>'Permisos de admin','fr'=>'Permissions admin',
                    'id'=>'Izin Admin','mn'=>'Админ эрх','ru'=>'Права администратора',
                    'tr'=>'Yönetici İzinleri','vi'=>'Quyền quản trị',
                    'zh_CN'=>'管理员权限','zh_TW'=>'管理員權限',
                ],
                'route' => 'staff/admins',
                'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'master' => true,
            ],
        ],
    ],

    // 서비스 주문 메뉴 → vos-hosting 플러그인 plugin.json 의 menus.admin 로 이전됨

    // ── 사이트 관리 ──
    [
        'id' => 'site',
        'title' => [
            'ko'=>'사이트 관리','en'=>'Site Management','ja'=>'サイト管理',
            'de'=>'Website-Verwaltung','es'=>'Gestión del sitio','fr'=>'Gestion du site',
            'id'=>'Manajemen Situs','mn'=>'Сайтын удирдлага','ru'=>'Управление сайтом',
            'tr'=>'Site Yönetimi','vi'=>'Quản lý trang web',
            'zh_CN'=>'站点管理','zh_TW'=>'站台管理',
        ],
        'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
        'permission' => 'site',
        'position' => 20,
        'section' => 'top',
        'items' => [
            [
                'title' => ['ko'=>'메뉴 관리','en'=>'Menus','ja'=>'メニュー管理','de'=>'Menüs','es'=>'Menús','fr'=>'Menus','id'=>'Menu','mn'=>'Цэс','ru'=>'Меню','tr'=>'Menüler','vi'=>'Menu','zh_CN'=>'菜单管理','zh_TW'=>'選單管理'],
                'route' => 'site/menus',
                'icon' => 'M4 6h16M4 12h16M4 18h16',
            ],
            [
                'title' => ['ko'=>'디자인','en'=>'Design','ja'=>'デザイン','de'=>'Design','es'=>'Diseño','fr'=>'Design','id'=>'Desain','mn'=>'Дизайн','ru'=>'Дизайн','tr'=>'Tasarım','vi'=>'Thiết kế','zh_CN'=>'设计','zh_TW'=>'設計'],
                'route' => 'site/design',
                'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
            ],
            [
                'title' => ['ko'=>'페이지 관리','en'=>'Pages','ja'=>'ページ管理','de'=>'Seiten','es'=>'Páginas','fr'=>'Pages','id'=>'Halaman','mn'=>'Хуудас','ru'=>'Страницы','tr'=>'Sayfalar','vi'=>'Trang','zh_CN'=>'页面管理','zh_TW'=>'頁面管理'],
                'route' => 'site/pages',
                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            ],
            [
                'title' => ['ko'=>'게시판 관리','en'=>'Boards','ja'=>'掲示板管理','de'=>'Foren','es'=>'Foros','fr'=>'Forums','id'=>'Forum','mn'=>'Самбар','ru'=>'Форумы','tr'=>'Panolar','vi'=>'Diễn đàn','zh_CN'=>'论坛管理','zh_TW'=>'看板管理'],
                'route' => 'site/boards',
                'icon' => 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
            ],
            [
                'title' => ['ko'=>'위젯 관리','en'=>'Widgets','ja'=>'ウィジェット管理','de'=>'Widgets','es'=>'Widgets','fr'=>'Widgets','id'=>'Widget','mn'=>'Виджет','ru'=>'Виджеты','tr'=>'Widget\'lar','vi'=>'Widget','zh_CN'=>'小部件','zh_TW'=>'小工具'],
                'route' => 'site/widgets',
                'icon' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
            ],
        ],
    ],

    // ── 플러그인 ──
    [
        'id' => 'plugins',
        'title' => [
            'ko'=>'플러그인','en'=>'Plugins','ja'=>'プラグイン',
            'de'=>'Plugins','es'=>'Plugins','fr'=>'Plugins',
            'id'=>'Plugin','mn'=>'Плагин','ru'=>'Плагины',
            'tr'=>'Eklentiler','vi'=>'Plugin',
            'zh_CN'=>'插件','zh_TW'=>'外掛',
        ],
        'icon' => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4',
        'route' => 'plugins',
        'position' => 80,
        'section' => 'bottom',
    ],

    // 문의 관리 메뉴 → vos-hosting 플러그인 plugin.json 의 menus.admin 로 이전됨

    // ── 설정 ──
    [
        'id' => 'settings',
        'title' => [
            'ko'=>'설정','en'=>'Settings','ja'=>'設定',
            'de'=>'Einstellungen','es'=>'Configuración','fr'=>'Paramètres',
            'id'=>'Pengaturan','mn'=>'Тохиргоо','ru'=>'Настройки',
            'tr'=>'Ayarlar','vi'=>'Cài đặt',
            'zh_CN'=>'设置','zh_TW'=>'設定',
        ],
        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'permission' => 'settings',
        'position' => 90,
        'section' => 'bottom',
        'route_prefix' => 'settings',
        'items' => [
            [
                'title' => ['ko'=>'일반','en'=>'General','ja'=>'一般','de'=>'Allgemein','es'=>'General','fr'=>'Général','id'=>'Umum','mn'=>'Ерөнхий','ru'=>'Общие','tr'=>'Genel','vi'=>'Chung','zh_CN'=>'常规','zh_TW'=>'一般'],
                'route' => 'settings/general',
                'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
            ],
            [
                'title' => ['ko'=>'SEO','en'=>'SEO','ja'=>'SEO','de'=>'SEO','es'=>'SEO','fr'=>'SEO','id'=>'SEO','mn'=>'SEO','ru'=>'SEO','tr'=>'SEO','vi'=>'SEO','zh_CN'=>'SEO','zh_TW'=>'SEO'],
                'route' => 'settings/seo',
                'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z',
            ],
            [
                'title' => ['ko'=>'PWA','en'=>'PWA','ja'=>'PWA','de'=>'PWA','es'=>'PWA','fr'=>'PWA','id'=>'PWA','mn'=>'PWA','ru'=>'PWA','tr'=>'PWA','vi'=>'PWA','zh_CN'=>'PWA','zh_TW'=>'PWA'],
                'route' => 'settings/pwa',
                'icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
            ],
            [
                'title' => ['ko'=>'시스템','en'=>'System','ja'=>'システム','de'=>'System','es'=>'Sistema','fr'=>'Système','id'=>'Sistem','mn'=>'Систем','ru'=>'Система','tr'=>'Sistem','vi'=>'Hệ thống','zh_CN'=>'系统','zh_TW'=>'系統'],
                'route' => 'settings/system',
                'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
            ],
        ],
    ],
];
