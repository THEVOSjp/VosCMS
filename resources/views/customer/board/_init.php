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
$boardUrl = ($config['app_url'] ?? '') . '/' . $board['slug'];

// 현재 사용자 정보
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $userStmt = $pdo->prepare("SELECT id, name, email, grade_id, profile_image FROM {$prefix}users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $currentUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($currentUser) {
        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        $currentUser['name'] = \RzxLib\Core\Helpers\Encryption::decrypt($currentUser['name']) ?: $currentUser['email'];
    }
}

// 카테고리 로드
$catStmt = $pdo->prepare("SELECT * FROM {$prefix}board_categories WHERE board_id = ? AND is_active = 1 ORDER BY sort_order ASC");
$catStmt->execute([$boardId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 카테고리 다국어 (로케일 → en → 원본)
$_catLocale = $currentLocale ?? 'ko';
if (!empty($categories)) {
    $catIds = array_column($categories, 'id');
    $catNameKeys = implode(',', array_map(fn($id) => "'board_category.{$id}.name'", $catIds));
    $_catTr = [];
    // 현재 로케일
    $trRows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$catNameKeys}) AND locale = '{$_catLocale}'")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($trRows as $tr) {
        if (preg_match('/board_category\.(\d+)\.name/', $tr['lang_key'], $m)) $_catTr[(int)$m[1]] = $tr['content'];
    }
    // 영어 폴백
    if ($_catLocale !== 'en') {
        $missCatIds = array_diff($catIds, array_keys($_catTr));
        if (!empty($missCatIds)) {
            $missKeys = implode(',', array_map(fn($id) => "'board_category.{$id}.name'", $missCatIds));
            $enRows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$missKeys}) AND locale = 'en'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($enRows as $tr) {
                if (preg_match('/board_category\.(\d+)\.name/', $tr['lang_key'], $m) && !isset($_catTr[(int)$m[1]])) $_catTr[(int)$m[1]] = $tr['content'];
            }
        }
    }
    // 번역 적용
    foreach ($categories as &$_cat) {
        if (isset($_catTr[$_cat['id']])) $_cat['name'] = $_catTr[$_cat['id']];
    }
    unset($_cat);
}

// 권한 확인 함수
function boardCheckPerm($board, $permName, $currentUser) {
    $level = $board[$permName] ?? 'all';
    if ($level === 'all') return true;
    if (!$currentUser) return false;
    if ($level === 'member') return true;
    // 관리자 여부는 세션에서 확인
    $isAdm = !empty($_SESSION['admin_id']);
    if ($level === 'admin') return $isAdm;
    if ($level === 'admin_staff') return $isAdm || !empty($_SESSION['is_staff']);
    if (str_starts_with($level, 'grade:')) {
        return true; // 추후 등급 체크 구현
    }
    return false;
}

// list_columns 파싱
$listColumns = json_decode($board['list_columns'] ?? '[]', true) ?: ['no', 'title', 'nick_name', 'created_at', 'view_count'];

// 스킨 설정 파싱
$skinConfig = json_decode($board['skin_config'] ?? '{}', true) ?: [];

// skin.json 기본값 병합 (DB에 저장된 값이 없으면 skin.json default 사용)
$boardSkinName = $board['skin'] ?? 'default';
$_skinJsonPath = BASE_PATH . '/skins/board/' . $boardSkinName . '/skin.json';
if (file_exists($_skinJsonPath)) {
    $_skinJson = json_decode(file_get_contents($_skinJsonPath), true) ?: [];
    foreach ($_skinJson['vars'] ?? [] as $_sv) {
        $k = $_sv['name'] ?? '';
        if ($k && !array_key_exists($k, $skinConfig) && isset($_sv['default'])) {
            $skinConfig[$k] = $_sv['default'];
        }
    }
}

// 스킨 파일 경로 (스킨 오버라이드 지원)
$boardSkinPath = BASE_PATH . '/skins/board/' . $boardSkinName;
$boardDefaultPath = __DIR__; // customer/board/

/**
 * 스킨 파일 로드 헬퍼
 * 스킨 디렉토리에 파일이 있으면 스킨 파일, 없으면 기본 파일 사용
 * @param string $filename 파일명 (예: '_list-webzine.php')
 * @return string|null 파일 경로 또는 null
 */
function boardSkinFile(string $filename): ?string {
    global $boardSkinPath, $boardDefaultPath;
    $skinFile = $boardSkinPath . '/' . $filename;
    if (file_exists($skinFile)) return $skinFile;
    $defaultFile = $boardDefaultPath . '/' . $filename;
    if (file_exists($defaultFile)) return $defaultFile;
    return null;
}

// 추천/비추천 사용 여부
$useVote = ($board['use_vote'] ?? 'use') !== 'none';
$useDownvote = ($board['use_downvote'] ?? 'use') !== 'none';

// 댓글 설정
$commentCount = (int)($board['comment_count'] ?? 50);
$commentPageCount = (int)($board['comment_page_count'] ?? 10);
$commentMaxDepth = (int)($board['comment_max_depth'] ?? 0);

// 분류 트리 구조 빌드
function buildCatTreeFront(array $cats, int $parentId = 0): array {
    $tree = [];
    foreach ($cats as $cat) {
        if ((int)($cat['parent_id'] ?? 0) === $parentId) {
            $cat['children'] = buildCatTreeFront($cats, (int)$cat['id']);
            $tree[] = $cat;
        }
    }
    return $tree;
}
$categoryTree = buildCatTreeFront($categories);

// 카테고리 맵
$catMap = [];
foreach ($categories as $cat) $catMap[$cat['id']] = $cat;

// 관리자 여부 (AdminAuth 세션 확인)
$isAdmin = !empty($_SESSION['admin_id']);
$isStaff = !empty($_SESSION['is_staff']);

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
