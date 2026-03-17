<?php
/**
 * RezlyX 게시판 - 공통 초기화
 * 모든 고객용 게시판 페이지에서 include
 * 제공 변수: $board, $pdo, $prefix, $baseUrl, $boardUrl, $currentUser, $categories
 */

// DB 연결
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 게시판 로드
if (!isset($boardSlug) || !$boardSlug) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    exit;
}

$boardStmt = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE slug = ? AND is_active = 1");
$boardStmt->execute([$boardSlug]);
$board = $boardStmt->fetch(PDO::FETCH_ASSOC);

if (!$board) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    exit;
}

$boardId = (int)$board['id'];
$boardUrl = ($config['app_url'] ?? '') . '/board/' . $board['slug'];

// 현재 사용자 정보
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $userStmt = $pdo->prepare("SELECT id, name, email, nick_name, role FROM {$prefix}users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
}

// 카테고리 로드
$catStmt = $pdo->prepare("SELECT * FROM {$prefix}board_categories WHERE board_id = ? AND is_active = 1 ORDER BY sort_order ASC");
$catStmt->execute([$boardId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 권한 확인 함수
function boardCheckPerm($board, $permName, $currentUser) {
    $level = $board[$permName] ?? 'all';
    if ($level === 'all') return true;
    if ($level === 'member') return $currentUser !== null;
    if ($level === 'admin') return ($currentUser['role'] ?? '') === 'admin';
    return false;
}

// list_columns 파싱
$listColumns = json_decode($board['list_columns'] ?? '[]', true) ?: ['no', 'title', 'nick_name', 'created_at', 'view_count'];

// 다국어 폴백 적용 (db_trans 내장 폴백: 설정언어 → 영어 → 기본언어)
$helpersPath = BASE_PATH . '/rzxlib/Core/Helpers/functions.php';
if (file_exists($helpersPath) && !function_exists('db_trans')) {
    require_once $helpersPath;
}
if (function_exists('db_trans')) {
    $multilangFields = ['title', 'description', 'seo_keywords', 'seo_description', 'header_content', 'footer_content'];
    foreach ($multilangFields as $field) {
        $translated = db_trans('board.' . $boardId . '.' . $field, null, '');
        if (!empty($translated)) {
            $board[$field] = $translated;
        }
    }
}
