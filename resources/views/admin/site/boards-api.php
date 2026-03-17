<?php
/**
 * RezlyX Admin - 게시판 API
 * POST 요청 처리: create, update, delete, copy
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    exit;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$action = $boardApiAction ?? ($_POST['action'] ?? '');

// === CREATE ===
if ($action === 'create') {
    $slug = trim($_POST['slug'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'board');

    if (!$slug || !$title) {
        echo json_encode(['success' => false, 'message' => 'slug과 제목은 필수입니다.']);
        exit;
    }

    // slug 중복 체크
    $chk = $pdo->prepare("SELECT id FROM {$prefix}boards WHERE slug = ?");
    $chk->execute([$slug]);
    if ($chk->fetch()) {
        echo json_encode(['success' => false, 'message' => '이미 사용 중인 URL입니다: /' . $slug]);
        exit;
    }

    // slug 형식 검증
    if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
        echo json_encode(['success' => false, 'message' => 'URL은 영문 소문자, 숫자, 하이픈, 밑줄만 사용 가능합니다.']);
        exit;
    }

    $fields = [
        'slug', 'title', 'category', 'description',
        'seo_keywords', 'seo_description', 'robots_tag',
        'skin', 'per_page', 'search_per_page', 'page_count',
        'header_content', 'footer_content',
        'sort_field', 'sort_direction', 'except_notice', 'show_category',
        'allow_comment', 'use_anonymous', 'anonymous_name', 'allow_secret',
        'consultation', 'use_trash', 'update_order_on_comment', 'comment_delete_message',
        'doc_length_limit', 'comment_length_limit',
        'protect_content_by_comment', 'protect_by_days', 'admin_mail',
        'perm_list', 'perm_read', 'perm_write', 'perm_comment', 'perm_manage',
    ];

    $setCols = [];
    $setVals = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $setCols[] = $f;
            $setVals[] = $_POST[$f];
        }
    }

    // list_columns
    $setCols[] = 'list_columns';
    $setVals[] = json_encode(['no', 'title', 'nick_name', 'created_at', 'view_count']);

    $placeholders = implode(',', array_fill(0, count($setCols), '?'));
    $colNames = implode(',', $setCols);
    $stmt = $pdo->prepare("INSERT INTO {$prefix}boards ({$colNames}) VALUES ({$placeholders})");
    $stmt->execute($setVals);
    $newId = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'message' => '게시판이 생성되었습니다.', 'board_id' => $newId]);
    exit;
}

// === UPDATE ===
if ($action === 'update') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    if (!$boardId) {
        echo json_encode(['success' => false, 'message' => '게시판 ID가 필요합니다.']);
        exit;
    }

    // slug 중복 체크 (자기 제외)
    $slug = trim($_POST['slug'] ?? '');
    if ($slug) {
        if (!preg_match('/^[a-z0-9_-]+$/', $slug)) {
            echo json_encode(['success' => false, 'message' => 'URL은 영문 소문자, 숫자, 하이픈, 밑줄만 사용 가능합니다.']);
            exit;
        }
        $chk = $pdo->prepare("SELECT id FROM {$prefix}boards WHERE slug = ? AND id != ?");
        $chk->execute([$slug, $boardId]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => '이미 사용 중인 URL입니다: /' . $slug]);
            exit;
        }
    }

    $fields = [
        'slug', 'title', 'category', 'description',
        'seo_keywords', 'seo_description', 'robots_tag',
        'skin', 'per_page', 'search_per_page', 'page_count',
        'header_content', 'footer_content',
        'sort_field', 'sort_direction', 'except_notice', 'show_category',
        'allow_comment', 'use_anonymous', 'anonymous_name', 'allow_secret',
        'consultation', 'use_trash', 'update_order_on_comment', 'comment_delete_message',
        'doc_length_limit', 'comment_length_limit',
        'protect_content_by_comment', 'protect_by_days', 'admin_mail',
        'perm_list', 'perm_read', 'perm_write', 'perm_comment', 'perm_manage',
        'is_active',
    ];

    $sets = [];
    $vals = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $sets[] = "{$f} = ?";
            $vals[] = $_POST[$f];
        }
    }

    // list_columns (JSON)
    if (isset($_POST['list_columns'])) {
        $cols = $_POST['list_columns'];
        if (is_string($cols)) $cols = json_decode($cols, true);
        if (is_array($cols)) {
            $sets[] = "list_columns = ?";
            $vals[] = json_encode($cols);
        }
    }

    if (empty($sets)) {
        echo json_encode(['success' => false, 'message' => '변경할 항목이 없습니다.']);
        exit;
    }

    $vals[] = $boardId;
    $sql = "UPDATE {$prefix}boards SET " . implode(', ', $sets) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($vals);

    echo json_encode(['success' => true, 'message' => '게시판이 수정되었습니다.']);
    exit;
}

// === DELETE ===
if ($action === 'delete') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    if (!$boardId) {
        echo json_encode(['success' => false, 'message' => '게시판 ID가 필요합니다.']);
        exit;
    }

    // 게시글 수 확인
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_posts WHERE board_id = ?");
    try {
        $cntStmt->execute([$boardId]);
        $postCount = (int)$cntStmt->fetchColumn();
        if ($postCount > 0) {
            echo json_encode(['success' => false, 'message' => "게시글이 {$postCount}개 있어 삭제할 수 없습니다. 먼저 게시글을 삭제해주세요."]);
            exit;
        }
    } catch (PDOException $e) {
        // 테이블 없으면 무시 (아직 생성 전)
    }

    // 카테고리 삭제
    $pdo->prepare("DELETE FROM {$prefix}board_categories WHERE board_id = ?")->execute([$boardId]);
    // 게시판 삭제
    $pdo->prepare("DELETE FROM {$prefix}boards WHERE id = ?")->execute([$boardId]);

    echo json_encode(['success' => true, 'message' => '게시판이 삭제되었습니다.']);
    exit;
}

// === COPY ===
if ($action === 'copy') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    if (!$boardId) {
        echo json_encode(['success' => false, 'message' => '게시판 ID가 필요합니다.']);
        exit;
    }

    // 원본 조회
    $src = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE id = ?");
    $src->execute([$boardId]);
    $board = $src->fetch(PDO::FETCH_ASSOC);
    if (!$board) {
        echo json_encode(['success' => false, 'message' => '원본 게시판을 찾을 수 없습니다.']);
        exit;
    }

    // 새 slug 생성
    $baseSlug = $board['slug'];
    $newSlug = $baseSlug . '_copy';
    $i = 1;
    while (true) {
        $chk = $pdo->prepare("SELECT id FROM {$prefix}boards WHERE slug = ?");
        $chk->execute([$newSlug]);
        if (!$chk->fetch()) break;
        $i++;
        $newSlug = $baseSlug . '_copy' . $i;
    }

    // 복사
    unset($board['id'], $board['created_at'], $board['updated_at']);
    $board['slug'] = $newSlug;
    $board['title'] = $board['title'] . ' (복사)';

    $cols = implode(',', array_keys($board));
    $placeholders = implode(',', array_fill(0, count($board), '?'));
    $stmt = $pdo->prepare("INSERT INTO {$prefix}boards ({$cols}) VALUES ({$placeholders})");
    $stmt->execute(array_values($board));
    $newId = $pdo->lastInsertId();

    // 카테고리 복사
    $cats = $pdo->prepare("SELECT * FROM {$prefix}board_categories WHERE board_id = ?");
    $cats->execute([$boardId]);
    while ($cat = $cats->fetch(PDO::FETCH_ASSOC)) {
        unset($cat['id']);
        $cat['board_id'] = $newId;
        $catCols = implode(',', array_keys($cat));
        $catPh = implode(',', array_fill(0, count($cat), '?'));
        $pdo->prepare("INSERT INTO {$prefix}board_categories ({$catCols}) VALUES ({$catPh})")->execute(array_values($cat));
    }

    echo json_encode(['success' => true, 'message' => '게시판이 복사되었습니다.', 'board_id' => $newId]);
    exit;
}

// === CATEGORY ADD ===
if ($action === 'category_add') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$boardId || !$name) {
        echo json_encode(['success' => false, 'message' => '게시판 ID와 분류명은 필수입니다.']);
        exit;
    }
    $slug = trim($_POST['slug'] ?? '');
    if (!$slug) $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '-', $name)));
    $color = $_POST['color'] ?? '#3B82F6';

    // 정렬 순서
    $maxSort = $pdo->prepare("SELECT MAX(sort_order) FROM {$prefix}board_categories WHERE board_id = ?");
    $maxSort->execute([$boardId]);
    $nextSort = ((int)$maxSort->fetchColumn()) + 1;

    $stmt = $pdo->prepare("INSERT INTO {$prefix}board_categories (board_id, name, slug, color, sort_order, is_active) VALUES (?,?,?,?,?,1)");
    $stmt->execute([$boardId, $name, $slug, $color, $nextSort]);

    echo json_encode(['success' => true, 'message' => '분류가 추가되었습니다.', 'category_id' => $pdo->lastInsertId()]);
    exit;
}

// === CATEGORY DELETE ===
if ($action === 'category_delete') {
    $catId = (int)($_POST['category_id'] ?? 0);
    if (!$catId) {
        echo json_encode(['success' => false, 'message' => '분류 ID가 필요합니다.']);
        exit;
    }
    $pdo->prepare("DELETE FROM {$prefix}board_categories WHERE id = ?")->execute([$catId]);
    echo json_encode(['success' => true, 'message' => '분류가 삭제되었습니다.']);
    exit;
}

echo json_encode(['success' => false, 'message' => '알 수 없는 액션: ' . $action]);
