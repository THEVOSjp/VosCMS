<?php
/**
 * RezlyX 게시판 - 게시글 API
 * POST: create, update, delete, like
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$action = $_POST['action'] ?? '';
$currentUser = Auth::check() ? Auth::user() : null;

// === CREATE ===
if ($action === 'create') {
    $boardId = (int)($_POST['board_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';

    if (!$boardId || !$title) {
        echo json_encode(['success' => false, 'message' => '제목은 필수입니다.']);
        exit;
    }

    // 게시판 설정 확인
    $boardStmt = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE id = ?");
    $boardStmt->execute([$boardId]);
    $board = $boardStmt->fetch(PDO::FETCH_ASSOC);
    if (!$board) { echo json_encode(['success' => false, 'message' => '게시판을 찾을 수 없습니다.']); exit; }

    // 글자 수 제한
    $docLimit = (int)($board['doc_length_limit'] ?? 0);
    if ($docLimit > 0 && mb_strlen(strip_tags($content)) > $docLimit) {
        echo json_encode(['success' => false, 'message' => "본문은 {$docLimit}자를 초과할 수 없습니다."]);
        exit;
    }

    // 작성자 정보
    $userId = $currentUser['id'] ?? null;
    $nickName = '';
    $password = null;
    $isAnonymous = 0;

    if ($currentUser) {
        if (($board['use_anonymous'] ?? 0)) {
            // 익명 $NUM 패턴: 게시판 내 작성자별 고유 번호 부여
            $anonBase = $board['anonymous_name'] ?? '익명';
            // 같은 user_id로 이전에 받은 번호가 있으면 재사용
            $existNum = $pdo->prepare("SELECT nick_name FROM {$prefix}board_posts WHERE board_id = ? AND user_id = ? AND is_anonymous = 1 AND nick_name LIKE ? ORDER BY id ASC LIMIT 1");
            $existNum->execute([$boardId, $currentUser['id'], $anonBase . '%']);
            $prevNick = $existNum->fetchColumn();
            if ($prevNick) {
                $nickName = $prevNick;
            } else {
                // 새 번호 부여: 현재 최대 번호 + 1
                $maxNum = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(nick_name, ?) AS UNSIGNED)) FROM {$prefix}board_posts WHERE board_id = ? AND is_anonymous = 1 AND nick_name LIKE ?");
                $maxNum->execute([mb_strlen($anonBase) + 1, $boardId, $anonBase . '%']);
                $nextNum = ((int)$maxNum->fetchColumn()) + 1;
                $nickName = $anonBase . $nextNum;
            }
            $isAnonymous = 1;
        } else {
            $nickName = $currentUser['nick_name'] ?? $currentUser['name'] ?? '';
        }
    } else {
        $nickName = trim($_POST['nick_name'] ?? '');
        $pw = $_POST['password'] ?? '';
        if (!$nickName || !$pw) {
            echo json_encode(['success' => false, 'message' => '이름과 비밀번호를 입력해주세요.']);
            exit;
        }
        $password = password_hash($pw, PASSWORD_DEFAULT);
    }

    $isNotice = ($currentUser && !empty($_SESSION['admin_id'])) ? (int)($_POST['is_notice'] ?? 0) : 0;
    $isSecret = (int)($_POST['is_secret'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;

    $now = date('Y-m-d H:i:s');
    $listOrder = time();

    // 현재 로케일
    $currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');

    // 확장 변수 수집
    $extraVarsJson = null;
    $evData = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'extra_') === 0) {
            $evKey = substr($k, 6);
            $evData[$evKey] = is_array($v) ? implode(',', $v) : $v;
        }
    }
    if (!empty($evData)) $extraVarsJson = json_encode($evData, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("INSERT INTO {$prefix}board_posts
        (board_id, category_id, user_id, title, content, password, is_notice, is_secret, is_anonymous, nick_name, list_order, update_order, status, original_locale, source_locale, extra_vars, ip_address, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $boardId, $categoryId, $userId, $title, $content, $password,
        $isNotice, $isSecret, $isAnonymous, $nickName,
        $listOrder, $listOrder, 'published', $currentLocale, $currentLocale, $extraVarsJson, $_SERVER['REMOTE_ADDR'] ?? '', $now, $now
    ]);
    $newId = $pdo->lastInsertId();

    // 파일 업로드 처리
    if (!empty($_FILES['files']['name'][0])) {
        $uploadDir = BASE_PATH . '/storage/board/' . $boardId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileCount = 0;
        foreach ($_FILES['files']['name'] as $i => $fname) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = pathinfo($fname, PATHINFO_EXTENSION);
            $stored = uniqid('bf_') . '.' . $ext;
            $dest = $uploadDir . $stored;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
                $pdo->prepare("INSERT INTO {$prefix}board_files (post_id, board_id, original_name, stored_name, file_path, file_size, mime_type) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$newId, $boardId, $fname, $stored, '/storage/board/' . $boardId . '/' . $stored, $_FILES['files']['size'][$i], $_FILES['files']['type'][$i] ?? '']);
                $fileCount++;
            }
        }
        if ($fileCount > 0) {
            $pdo->prepare("UPDATE {$prefix}board_posts SET file_count = ? WHERE id = ?")->execute([$fileCount, $newId]);
        }
    }

    echo json_encode(['success' => true, 'message' => '게시글이 작성되었습니다.', 'post_id' => $newId]);
    exit;
}

// === UPDATE ===
if ($action === 'update') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $boardId = (int)($_POST['board_id'] ?? 0);
    if (!$postId) { echo json_encode(['success' => false, 'message' => '게시글 ID가 필요합니다.']); exit; }

    $post = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE id = ? AND board_id = ?");
    $post->execute([$postId, $boardId]);
    $post = $post->fetch(PDO::FETCH_ASSOC);
    if (!$post) { echo json_encode(['success' => false, 'message' => '게시글을 찾을 수 없습니다.']); exit; }

    // 권한
    if (!$currentUser || ($currentUser['id'] != $post['user_id'] && empty($_SESSION['admin_id']))) {
        echo json_encode(['success' => false, 'message' => '수정 권한이 없습니다.']);
        exit;
    }

    $title = trim($_POST['title'] ?? $post['title']);
    $content = $_POST['content'] ?? $post['content'];
    $isNotice = !empty($_SESSION['admin_id']) ? (int)($_POST['is_notice'] ?? 0) : $post['is_notice'];
    $isSecret = (int)($_POST['is_secret'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0) ?: null;

    // 확장 변수 수집
    $evData = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'extra_') === 0) {
            $evKey = substr($k, 6);
            $evData[$evKey] = is_array($v) ? implode(',', $v) : $v;
        }
    }
    $extraVarsJson = !empty($evData) ? json_encode($evData, JSON_UNESCAPED_UNICODE) : $post['extra_vars'];

    $currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');
    $originalLocale = $post['original_locale'] ?? 'ko';

    if ($currentLocale === $originalLocale) {
        // 원본 언어로 수정 → board_posts 직접 수정
        $pdo->prepare("UPDATE {$prefix}board_posts SET title=?, content=?, is_notice=?, is_secret=?, category_id=?, extra_vars=?, updated_at=NOW() WHERE id=?")
            ->execute([$title, $content, $isNotice, $isSecret, $categoryId, $extraVarsJson, $postId]);
    } else {
        // 다른 언어로 수정 → rzx_translations에 저장 (원본 유지)
        $trStmt = $pdo->prepare("INSERT INTO {$prefix}translations (lang_key, locale, source_locale, content) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content), source_locale = VALUES(source_locale)");
        $trStmt->execute(["board_post.{$postId}.title", $currentLocale, $originalLocale, $title]);
        $trStmt->execute(["board_post.{$postId}.content", $currentLocale, $originalLocale, $content]);

        // 확장 변수도 해당 언어로 저장
        if (!empty($evData)) {
            $trStmt->execute(["board_post.{$postId}.extra_vars", $currentLocale, $originalLocale, json_encode($evData, JSON_UNESCAPED_UNICODE)]);
        }

        // 원본에 확장변수가 없으면 원본에도 저장 (최초 입력)
        if (!empty($evData) && empty($post['extra_vars'])) {
            $pdo->prepare("UPDATE {$prefix}board_posts SET extra_vars=?, is_notice=?, is_secret=?, category_id=?, updated_at=NOW() WHERE id=?")
                ->execute([$extraVarsJson, $isNotice, $isSecret, $categoryId, $postId]);
        } else {
            // is_notice, is_secret, category 등 메타 필드는 언어 무관하므로 원본에 반영
            $pdo->prepare("UPDATE {$prefix}board_posts SET is_notice=?, is_secret=?, category_id=?, updated_at=NOW() WHERE id=?")
                ->execute([$isNotice, $isSecret, $categoryId, $postId]);
        }
    }

    // 새 파일 업로드
    if (!empty($_FILES['files']['name'][0])) {
        $uploadDir = BASE_PATH . '/storage/board/' . $boardId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        foreach ($_FILES['files']['name'] as $i => $fname) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = pathinfo($fname, PATHINFO_EXTENSION);
            $stored = uniqid('bf_') . '.' . $ext;
            $dest = $uploadDir . $stored;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
                $pdo->prepare("INSERT INTO {$prefix}board_files (post_id, board_id, original_name, stored_name, file_path, file_size, mime_type) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$postId, $boardId, $fname, $stored, '/storage/board/' . $boardId . '/' . $stored, $_FILES['files']['size'][$i], $_FILES['files']['type'][$i] ?? '']);
            }
        }
        // file_count 갱신
        $fc = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_files WHERE post_id = ?");
        $fc->execute([$postId]);
        $pdo->prepare("UPDATE {$prefix}board_posts SET file_count = ? WHERE id = ?")->execute([(int)$fc->fetchColumn(), $postId]);
    }

    echo json_encode(['success' => true, 'message' => '게시글이 수정되었습니다.', 'post_id' => $postId]);
    exit;
}

// === DELETE ===
if ($action === 'delete') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $boardId = (int)($_POST['board_id'] ?? 0);
    if (!$postId) { echo json_encode(['success' => false, 'message' => '게시글 ID가 필요합니다.']); exit; }

    $post = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE id = ?");
    $post->execute([$postId]);
    $post = $post->fetch(PDO::FETCH_ASSOC);
    if (!$post) { echo json_encode(['success' => false, 'message' => '게시글을 찾을 수 없습니다.']); exit; }

    if (!$currentUser || ($currentUser['id'] != $post['user_id'] && empty($_SESSION['admin_id']))) {
        echo json_encode(['success' => false, 'message' => '삭제 권한이 없습니다.']);
        exit;
    }

    // 게시판 설정
    $boardStmt = $pdo->prepare("SELECT use_trash FROM {$prefix}boards WHERE id = ?");
    $boardStmt->execute([$boardId]);
    $boardCfg = $boardStmt->fetch(PDO::FETCH_ASSOC);

    if ($boardCfg && ($boardCfg['use_trash'] ?? 0)) {
        $pdo->prepare("UPDATE {$prefix}board_posts SET status = 'trash' WHERE id = ?")->execute([$postId]);
    } else {
        $pdo->prepare("DELETE FROM {$prefix}board_comments WHERE post_id = ?")->execute([$postId]);
        $pdo->prepare("DELETE FROM {$prefix}board_files WHERE post_id = ?")->execute([$postId]);
        $pdo->prepare("DELETE FROM {$prefix}board_posts WHERE id = ?")->execute([$postId]);
    }

    echo json_encode(['success' => true, 'message' => '게시글이 삭제되었습니다.']);
    exit;
}

// === LIKE ===
if ($action === 'like') {
    $postId = (int)($_POST['post_id'] ?? 0);
    if (!$postId) { echo json_encode(['success' => false, 'message' => 'ID 필요']); exit; }

    $userId = $currentUser['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // 중복 투표 확인 (회원: user_id, 비회원: IP)
    if ($userId) {
        $dupStmt = $pdo->prepare("SELECT id FROM {$prefix}board_votes WHERE post_id = ? AND user_id = ? AND vote_type = 'like'");
        $dupStmt->execute([$postId, $userId]);
    } else {
        $dupStmt = $pdo->prepare("SELECT id FROM {$prefix}board_votes WHERE post_id = ? AND user_id IS NULL AND ip_address = ? AND vote_type = 'like'");
        $dupStmt->execute([$postId, $ip]);
    }

    if ($dupStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => __('board.already_voted')]);
        exit;
    }

    // 자기 글 추천 방지
    $postStmt = $pdo->prepare("SELECT user_id FROM {$prefix}board_posts WHERE id = ?");
    $postStmt->execute([$postId]);
    $postAuthor = $postStmt->fetch(PDO::FETCH_ASSOC);
    if ($userId && $postAuthor && (int)$postAuthor['user_id'] === $userId) {
        echo json_encode(['success' => false, 'message' => __('board.cannot_vote_own')]);
        exit;
    }

    // 투표 기록 + like_count 증가
    $pdo->prepare("INSERT INTO {$prefix}board_votes (post_id, user_id, ip_address, vote_type) VALUES (?, ?, ?, 'like')")
        ->execute([$postId, $userId, $ip]);
    $pdo->prepare("UPDATE {$prefix}board_posts SET like_count = like_count + 1 WHERE id = ?")->execute([$postId]);

    $cnt = $pdo->prepare("SELECT like_count FROM {$prefix}board_posts WHERE id = ?");
    $cnt->execute([$postId]);
    echo json_encode(['success' => true, 'like_count' => (int)$cnt->fetchColumn()]);
    exit;
}

// === DISLIKE ===
if ($action === 'dislike') {
    $postId = (int)($_POST['post_id'] ?? 0);
    if (!$postId) { echo json_encode(['success' => false, 'message' => 'ID 필요']); exit; }

    $userId = $currentUser['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    if ($userId) {
        $dupStmt = $pdo->prepare("SELECT id FROM {$prefix}board_votes WHERE post_id = ? AND user_id = ? AND vote_type = 'dislike'");
        $dupStmt->execute([$postId, $userId]);
    } else {
        $dupStmt = $pdo->prepare("SELECT id FROM {$prefix}board_votes WHERE post_id = ? AND user_id IS NULL AND ip_address = ? AND vote_type = 'dislike'");
        $dupStmt->execute([$postId, $ip]);
    }

    if ($dupStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => __('board.already_voted')]);
        exit;
    }

    $postStmt = $pdo->prepare("SELECT user_id, dislike_count FROM {$prefix}board_posts WHERE id = ?");
    $postStmt->execute([$postId]);
    $postData = $postStmt->fetch(PDO::FETCH_ASSOC);
    if ($userId && $postData && (int)$postData['user_id'] === $userId) {
        echo json_encode(['success' => false, 'message' => __('board.cannot_vote_own')]);
        exit;
    }

    $pdo->prepare("INSERT INTO {$prefix}board_votes (post_id, user_id, ip_address, vote_type) VALUES (?, ?, ?, 'dislike')")
        ->execute([$postId, $userId, $ip]);
    $pdo->prepare("UPDATE {$prefix}board_posts SET dislike_count = dislike_count + 1 WHERE id = ?")->execute([$postId]);

    $cnt = $pdo->prepare("SELECT dislike_count FROM {$prefix}board_posts WHERE id = ?");
    $cnt->execute([$postId]);
    echo json_encode(['success' => true, 'dislike_count' => (int)$cnt->fetchColumn()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
