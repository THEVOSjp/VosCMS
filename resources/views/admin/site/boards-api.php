<?php
/**
 * RezlyX Admin - 게시판 API
 * POST 요청 처리: create, update, delete, copy
 */
header('Content-Type: application/json; charset=utf-8');

/**
 * 확장 변수 옵션 정규화 — JSON 배열 또는 줄바꿈 입력을 모두 받아 JSON 배열로 저장
 *  - "[\"a\",\"b\"]"  → "[\"a\",\"b\"]" (그대로)
 *  - "a\nb\nc"        → "[\"a\",\"b\",\"c\"]"
 *  - ""               → null
 */
function ev_normalize_options($raw): ?string {
    $raw = trim((string)$raw);
    if ($raw === '') return null;
    if ($raw[0] === '[') {
        $arr = json_decode($raw, true);
        if (is_array($arr)) return json_encode(array_values(array_filter(array_map('strval', $arr), fn($x) => $x !== '')), JSON_UNESCAPED_UNICODE);
    }
    $lines = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', $raw))));
    return $lines ? json_encode($lines, JSON_UNESCAPED_UNICODE) : null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// GET 요청은 조회 액션만 허용
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if (!in_array($action, ['category_get', 'extra_var_get', 'search_users'])) {
        echo json_encode(['success' => false, 'message' => 'GET not allowed for this action']);
        exit;
    }
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

// JSON body 지원
$_jsonBody = null;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $_jsonBody = json_decode(file_get_contents('php://input'), true) ?: [];
}

$action = $boardApiAction ?? ($_POST['action'] ?? $_GET['action'] ?? $_jsonBody['action'] ?? '');

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
        'slug', 'title', 'category', 'description', 'layout',
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

    // 실제 DB에 존재하는 컬럼만 사용 (스키마 누락 컬럼은 자동 무시)
    $existingCols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM {$prefix}boards") as $colRow) {
        $existingCols[$colRow['Field']] = true;
    }

    $sets = [];
    $vals = [];
    foreach ($fields as $f) {
        if (isset($_POST[$f]) && isset($existingCols[$f])) {
            $sets[] = "{$f} = ?";
            $vals[] = $_POST[$f];
        }
    }

    // skin_config (JSON)
    if (isset($_POST['skin_config'])) {
        $sc = $_POST['skin_config'];
        if (is_string($sc)) {
            // 유효한 JSON인지 확인
            $decoded = json_decode($sc, true);
            if ($decoded !== null || $sc === 'null') {
                $sets[] = "skin_config = ?";
                $vals[] = $sc;
            }
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
    try {
        $pdo->prepare($sql)->execute($vals);
        echo json_encode(['success' => true, 'message' => '게시판이 수정되었습니다.']);
    } catch (\Throwable $e) {
        error_log('[boards-api update] ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'DB 오류: ' . $e->getMessage()]);
    }
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

// === SEARCH USERS (회원 + 스태프 검색) ===
if ($action === 'search_users') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['success' => false, 'users' => []]);
        exit;
    }
    $like = '%' . $q . '%';
    $stmt = $pdo->prepare("SELECT id, email, name FROM {$prefix}users WHERE email LIKE ? OR name LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->execute([$like, $like]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 스태프도 검색 (nick_name 없음)
    try {
        $staffStmt = $pdo->prepare("SELECT id, email, name FROM {$prefix}staff WHERE email LIKE ? OR name LIKE ? ORDER BY name ASC LIMIT 10");
        $staffStmt->execute([$like, $like]);
        $staffUsers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        $staffUsers = [];
    }

    // 중복 제거 (id 기준)
    $ids = array_column($users, 'id');
    foreach ($staffUsers as $s) {
        if (!in_array($s['id'], $ids)) {
            $s['nick_name'] = '';
            $users[] = $s;
        }
    }

    $result = array_map(function($u) {
        return [
            'id' => $u['id'],
            'email' => $u['email'] ?? '',
            'display_name' => $u['name'] ?? $u['email'] ?? '',
        ];
    }, $users);

    echo json_encode(['success' => true, 'users' => $result]);
    exit;
}

// === CATEGORY GET ===
if ($action === 'category_get') {
    $catId = (int)($_GET['category_id'] ?? $_POST['category_id'] ?? 0);
    if (!$catId) {
        echo json_encode(['success' => false, 'message' => '분류 ID가 필요합니다.']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}board_categories WHERE id = ?");
    $stmt->execute([$catId]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => (bool)$cat, 'category' => $cat ?: null]);
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
    $parentId = (int)($_POST['parent_id'] ?? 0);
    $slug = trim($_POST['slug'] ?? '');
    if (!$slug) $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '-', $name)));
    $color = $_POST['color'] ?? '#3B82F6';
    $fontColor = trim($_POST['font_color'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $allowedGroups = trim($_POST['allowed_groups'] ?? '');
    $isExpanded = (int)($_POST['is_expanded'] ?? 0);
    $isDefault = (int)($_POST['is_default'] ?? 0);

    $maxSort = $pdo->prepare("SELECT MAX(sort_order) FROM {$prefix}board_categories WHERE board_id = ?");
    $maxSort->execute([$boardId]);
    $nextSort = ((int)$maxSort->fetchColumn()) + 1;

    $stmt = $pdo->prepare("INSERT INTO {$prefix}board_categories (board_id, parent_id, name, slug, description, color, font_color, allowed_groups, is_expanded, is_default, sort_order, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?,1)");
    $stmt->execute([$boardId, $parentId, $name, $slug, $description, $color, $fontColor, $allowedGroups, $isExpanded, $isDefault, $nextSort]);

    echo json_encode(['success' => true, 'message' => '분류가 추가되었습니다.', 'category_id' => $pdo->lastInsertId()]);
    exit;
}

// === CATEGORY UPDATE ===
if ($action === 'category_update') {
    $catId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    if (!$catId || !$name) {
        echo json_encode(['success' => false, 'message' => '분류 ID와 분류명은 필수입니다.']);
        exit;
    }
    $fontColor = trim($_POST['font_color'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $allowedGroups = trim($_POST['allowed_groups'] ?? '');
    $isExpanded = (int)($_POST['is_expanded'] ?? 0);
    $isDefault = (int)($_POST['is_default'] ?? 0);

    $stmt = $pdo->prepare("UPDATE {$prefix}board_categories SET name=?, description=?, font_color=?, allowed_groups=?, is_expanded=?, is_default=? WHERE id=?");
    $stmt->execute([$name, $description, $fontColor, $allowedGroups, $isExpanded, $isDefault, $catId]);

    echo json_encode(['success' => true, 'message' => '분류가 수정되었습니다.']);
    exit;
}

// === CATEGORY DELETE ===
if ($action === 'category_delete') {
    $catId = (int)($_POST['category_id'] ?? 0);
    if (!$catId) {
        echo json_encode(['success' => false, 'message' => '분류 ID가 필요합니다.']);
        exit;
    }
    // 하위 분류도 삭제
    $pdo->prepare("DELETE FROM {$prefix}board_categories WHERE parent_id = ?")->execute([$catId]);
    $pdo->prepare("DELETE FROM {$prefix}board_categories WHERE id = ?")->execute([$catId]);
    echo json_encode(['success' => true, 'message' => '분류가 삭제되었습니다.']);
    exit;
}

// === CATEGORY REORDER (JSON body) ===
if ($action === 'category_reorder') {
    $order = $_jsonBody['order'] ?? [];
    if (empty($order)) {
        echo json_encode(['success' => false, 'message' => '순서 데이터가 없습니다.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE {$prefix}board_categories SET parent_id = ?, sort_order = ? WHERE id = ?");
    foreach ($order as $item) {
        $stmt->execute([(int)$item['parent_id'], (int)$item['sort_order'], (int)$item['id']]);
    }
    echo json_encode(['success' => true, 'message' => '순서가 저장되었습니다.']);
    exit;
}

// === EXTRA VAR GET ===
if ($action === 'extra_var_get') {
    $evId = (int)($_GET['ev_id'] ?? $_POST['ev_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}board_extra_vars WHERE id = ?");
    $stmt->execute([$evId]);
    $ev = $stmt->fetch(PDO::FETCH_ASSOC);

    // 현재 로케일 번역값 (있으면 모달에서 우선 표시)
    $localized = null;
    if ($ev && function_exists('db_trans')) {
        $bid = (int)$ev['board_id'];
        $vn  = $ev['var_name'];
        $localized = [
            'title'         => db_trans("board_ev.{$bid}.{$vn}.title",         null, ''),
            'description'   => db_trans("board_ev.{$bid}.{$vn}.description",   null, ''),
            'options'       => db_trans("board_ev.{$bid}.{$vn}.options",       null, ''),
            'default_value' => db_trans("board_ev.{$bid}.{$vn}.default_value", null, ''),
        ];
    }

    echo json_encode(['success' => (bool)$ev, 'extra_var' => $ev ?: null, 'localized' => $localized]);
    exit;
}

// === EXTRA VAR ADD ===
if ($action === 'extra_var_add') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $varName = trim($_POST['var_name'] ?? '');
    $title = trim($_POST['title'] ?? '');
    if (!$boardId || !$varName || !$title) {
        echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
        exit;
    }
    try {
        $maxSort = $pdo->prepare("SELECT MAX(sort_order) FROM {$prefix}board_extra_vars WHERE board_id = ?");
        $maxSort->execute([$boardId]);
        $nextSort = ((int)$maxSort->fetchColumn()) + 1;

        $optionsJson = ev_normalize_options($_POST['options'] ?? '');

        $perm = in_array($_POST['permission'] ?? 'all', ['all','member','admin'], true) ? $_POST['permission'] : 'all';
        $stmt = $pdo->prepare("INSERT INTO {$prefix}board_extra_vars (board_id, var_name, var_type, title, description, options, default_value, is_required, is_searchable, is_shown_in_list, permission, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([
            $boardId, $varName, $_POST['var_type'] ?? 'text', $title,
            $_POST['description'] ?? '', $optionsJson, $_POST['default_value'] ?? '',
            (int)($_POST['is_required'] ?? 0), (int)($_POST['is_searchable'] ?? 0), (int)($_POST['is_shown_in_list'] ?? 0),
            $perm, $nextSort
        ]);
        echo json_encode(['success' => true, 'message' => '확장 변수가 추가되었습니다.', 'ev_id' => $pdo->lastInsertId()]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
    exit;
}

// === EXTRA VAR UPDATE ===
if ($action === 'extra_var_update') {
    $evId = (int)($_POST['ev_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    if (!$evId || !$title) {
        echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
        exit;
    }
    try {
        // 변수 정보 조회 (board_id, var_name)
        $info = $pdo->prepare("SELECT board_id, var_name FROM {$prefix}board_extra_vars WHERE id = ?");
        $info->execute([$evId]);
        $info = $info->fetch(PDO::FETCH_ASSOC);
        if (!$info) {
            echo json_encode(['success' => false, 'message' => '확장 변수를 찾을 수 없습니다.']);
            exit;
        }
        $bid = (int)$info['board_id'];
        $vn  = $info['var_name'];

        $optionsJson = ev_normalize_options($_POST['options'] ?? '');
        $perm = in_array($_POST['permission'] ?? 'all', ['all','member','admin'], true) ? $_POST['permission'] : 'all';

        $curLoc = function_exists('current_locale') ? current_locale() : 'ko';
        $defLoc = $_ENV['DEFAULT_LOCALE'] ?? 'ko';

        if ($curLoc === $defLoc) {
            // 소스 로케일: 소스 row에 모든 필드 저장 (기존 동작)
            $stmt = $pdo->prepare("UPDATE {$prefix}board_extra_vars SET var_type=?, title=?, description=?, options=?, default_value=?, is_required=?, is_searchable=?, is_shown_in_list=?, permission=? WHERE id=?");
            $stmt->execute([
                $_POST['var_type'] ?? 'text', $title,
                $_POST['description'] ?? '', $optionsJson, $_POST['default_value'] ?? '',
                (int)($_POST['is_required'] ?? 0), (int)($_POST['is_searchable'] ?? 0), (int)($_POST['is_shown_in_list'] ?? 0),
                $perm, $evId
            ]);
        } else {
            // 비소스 로케일: 메타(변경 불필요한 공통 속성)만 소스에 저장, 번역 가능 필드는 translations에 저장
            $stmt = $pdo->prepare("UPDATE {$prefix}board_extra_vars SET var_type=?, is_required=?, is_searchable=?, is_shown_in_list=?, permission=? WHERE id=?");
            $stmt->execute([
                $_POST['var_type'] ?? 'text',
                (int)($_POST['is_required'] ?? 0), (int)($_POST['is_searchable'] ?? 0), (int)($_POST['is_shown_in_list'] ?? 0),
                $perm, $evId
            ]);
            $trIns = $pdo->prepare("INSERT INTO {$prefix}translations (lang_key, locale, source_locale, content, created_at, updated_at)
                                    VALUES (?, ?, ?, ?, NOW(), NOW())
                                    ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()");
            $trIns->execute(["board_ev.{$bid}.{$vn}.title", $curLoc, $defLoc, $title]);
            $trIns->execute(["board_ev.{$bid}.{$vn}.description", $curLoc, $defLoc, $_POST['description'] ?? '']);
            $trIns->execute(["board_ev.{$bid}.{$vn}.options", $curLoc, $defLoc, $optionsJson ?? '']);
            $trIns->execute(["board_ev.{$bid}.{$vn}.default_value", $curLoc, $defLoc, $_POST['default_value'] ?? '']);
        }
        echo json_encode(['success' => true, 'message' => '확장 변수가 수정되었습니다.']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
    exit;
}

// === EXTRA VAR DELETE ===
if ($action === 'extra_var_delete') {
    $evId = (int)($_POST['ev_id'] ?? 0);
    if (!$evId) { echo json_encode(['success' => false, 'message' => 'ID가 필요합니다.']); exit; }
    $pdo->prepare("DELETE FROM {$prefix}board_extra_vars WHERE id = ?")->execute([$evId]);
    echo json_encode(['success' => true, 'message' => '확장 변수가 삭제되었습니다.']);
    exit;
}

// === EXTRA VAR REORDER ===
if ($action === 'extra_var_reorder') {
    $order = $_jsonBody['order'] ?? [];
    if (empty($order)) { echo json_encode(['success' => false, 'message' => '순서 데이터가 없습니다.']); exit; }
    $stmt = $pdo->prepare("UPDATE {$prefix}board_extra_vars SET sort_order = ? WHERE id = ?");
    foreach ($order as $item) {
        $stmt->execute([(int)$item['sort_order'], (int)$item['id']]);
    }
    echo json_encode(['success' => true, 'message' => '순서가 저장되었습니다.']);
    exit;
}

// === BOARD ADMIN ADD ===
if ($action === 'board_admin_add') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $identifier = trim($_POST['identifier'] ?? '');
    if (!$boardId || !$identifier) {
        echo json_encode(['success' => false, 'message' => '필수 항목을 입력해주세요.']);
        exit;
    }
    // 검색에서 직접 선택한 경우 user_id로 바로 조회
    $directId = trim($_POST['user_id_direct'] ?? '');
    if ($directId) {
        $userStmt = $pdo->prepare("SELECT id, email, name FROM {$prefix}users WHERE id = ? LIMIT 1");
        $userStmt->execute([$directId]);
    } else {
        $userStmt = $pdo->prepare("SELECT id, email, name FROM {$prefix}users WHERE email = ? OR name = ? LIMIT 1");
        $userStmt->execute([$identifier, $identifier]);
    }
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'message' => __('site.boards.adv_module_admin_not_found')]);
        exit;
    }
    // 중복 체크
    $dupStmt = $pdo->prepare("SELECT id FROM {$prefix}board_admins WHERE board_id = ? AND user_id = ?");
    $dupStmt->execute([$boardId, $user['id']]);
    if ($dupStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => __('site.boards.adv_module_admin_already')]);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO {$prefix}board_admins (board_id, user_id, perm_document, perm_comment, perm_settings) VALUES (?,?,?,?,?)");
    $stmt->execute([$boardId, $user['id'], (int)($_POST['perm_document'] ?? 1), (int)($_POST['perm_comment'] ?? 1), (int)($_POST['perm_settings'] ?? 0)]);
    require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
    $decName = \RzxLib\Core\Helpers\Encryption::decrypt($user['name']);
    echo json_encode([
        'success' => true,
        'user_id' => $user['id'],
        'display_name' => $decName ?: $user['email'],
    ]);
    exit;
}

// === BOARD ADMIN DELETE ===
if ($action === 'board_admin_delete') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $userId = trim($_POST['user_id'] ?? '');
    if (!$boardId || !$userId) {
        echo json_encode(['success' => false, 'message' => '필수 항목이 누락되었습니다.']);
        exit;
    }
    $pdo->prepare("DELETE FROM {$prefix}board_admins WHERE board_id = ? AND user_id = ?")->execute([$boardId, $userId]);
    echo json_encode(['success' => true]);
    exit;
}

// === SKIN CONFIG 저장 (파일 업로드 지원) ===
if ($action === 'update_skin_config') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $skin = $_POST['skin'] ?? 'default';
    if (!$boardId) { echo json_encode(['success' => false, 'message' => 'Board ID required']); exit; }

    // skin_config 값 수집 (PHP가 skin_config[key]를 배열로 자동 파싱)
    $skinConfig = $_POST['skin_config'] ?? [];
    if (is_string($skinConfig)) {
        $skinConfig = json_decode($skinConfig, true) ?: [];
    }

    // 삭제 처리 (skin_delete[name]=1 → 파일 삭제 + 값 제거)
    $skinDeletes = $_POST['skin_delete'] ?? [];
    foreach ($skinDeletes as $delName => $delFlag) {
        if ($delFlag === '1' && !empty($skinConfig[$delName])) {
            // 로컬 파일이면 물리 삭제
            $oldUrl = $skinConfig[$delName];
            $localPath = str_replace($config['app_url'] ?? '', BASE_PATH, $oldUrl);
            if (file_exists($localPath)) @unlink($localPath);
            $skinConfig[$delName] = '';
        }
    }

    // 파일 업로드 처리 (skin_file_{name} → skin_config[{name}])
    $uploadDir = BASE_PATH . '/storage/uploads/skins/' . $boardId . '/';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

    foreach ($_FILES as $fileKey => $fileInfo) {
        if (strpos($fileKey, 'skin_file_') !== 0) continue;
        if ($fileInfo['error'] !== UPLOAD_ERR_OK || $fileInfo['size'] === 0) continue;

        $varName = substr($fileKey, 10); // skin_file_title_bg_image → title_bg_image
        $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm'])) continue;

        $filename = $varName . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $filename;
        if (move_uploaded_file($fileInfo['tmp_name'], $dest)) {
            $baseUrl = $config['app_url'] ?? '';
            $skinConfig[$varName] = $baseUrl . '/storage/uploads/skins/' . $boardId . '/' . $filename;
        }
    }

    // DB 저장
    $configJson = json_encode($skinConfig, JSON_UNESCAPED_UNICODE);
    $pdo->prepare("UPDATE {$prefix}boards SET skin = ?, skin_config = ? WHERE id = ?")->execute([$skin, $configJson, $boardId]);

    echo json_encode(['success' => true, 'message' => '스킨 설정이 저장되었습니다.']);
    exit;
}

echo json_encode(['success' => false, 'message' => '알 수 없는 액션: ' . $action]);
