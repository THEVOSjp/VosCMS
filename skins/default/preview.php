<?php
/**
 * RezlyX Default Theme Preview
 *
 * 테마 샘플 페이지 미리보기
 * 접속: http://localhost/rezlyx/skins/default/preview.php
 */

// 설정 로드
$config = require __DIR__ . '/config.php';
$config['app_name'] = 'RezlyX';
$config['app_url'] = '';

// 현재 페이지 확인
$page = $_GET['page'] ?? 'home';
$validPages = ['home', 'services', 'about', 'contact'];

if (!in_array($page, $validPages)) {
    $page = 'home';
}

// 페이지별 설정
$pageSettings = [
    'home' => [
        'title' => '홈',
        'description' => 'RezlyX 예약 시스템 - 간편하고 스마트한 예약',
    ],
    'services' => [
        'title' => '서비스',
        'description' => '다양한 서비스를 확인하세요',
        'breadcrumbs' => [
            ['label' => '홈', 'url' => '?page=home'],
            ['label' => '서비스'],
        ],
    ],
    'about' => [
        'title' => '소개',
        'description' => 'RezlyX에 대해 알아보세요',
        'breadcrumbs' => [
            ['label' => '홈', 'url' => '?page=home'],
            ['label' => '소개'],
        ],
    ],
    'contact' => [
        'title' => '문의',
        'description' => '문의사항이 있으시면 연락주세요',
        'breadcrumbs' => [
            ['label' => '홈', 'url' => '?page=home'],
            ['label' => '문의'],
        ],
    ],
];

$pageTitle = $pageSettings[$page]['title'] . ' - ' . $config['app_name'];
$metaDescription = $pageSettings[$page]['description'];
$breadcrumbs = $pageSettings[$page]['breadcrumbs'] ?? null;
$baseUrl = '';
$locale = 'ko';

// 페이지 콘텐츠 가져오기
ob_start();
include __DIR__ . '/pages/' . $page . '.php';
$content = ob_get_clean();

// 레이아웃 렌더링
include __DIR__ . '/layouts/main.php';
