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

    // 확장 변수 수집 (권한 검증 — 권한 없는 변수는 무시)
    require_once BASE_PATH . '/rzxlib/Core/Modules/ExtraVarRenderer.php';
    $extraVarsJson = null;
    $evData = [];
    $_evDefStmt = $pdo->prepare("SELECT var_name, permission, default_value FROM {$prefix}board_extra_vars WHERE board_id = ? AND is_active = 1");
    $_evDefStmt->execute([$boardId]);
    $_evDefs = [];
    foreach ($_evDefStmt->fetchAll(PDO::FETCH_ASSOC) as $_d) $_evDefs[$_d['var_name']] = $_d;

    foreach ($_POST as $k => $v) {
        if (strpos($k, 'extra_') !== 0) continue;
        $evKey = substr($k, 6);
        // 정의된 변수만 + 권한 있는 사용자만
        if (!isset($_evDefs[$evKey])) continue;
        if (!\RzxLib\Core\Modules\ExtraVarRenderer::canEdit($_evDefs[$evKey])) continue;
        $evData[$evKey] = is_array($v) ? implode(',', $v) : $v;
    }
    // 권한 없어 입력 못 한 변수는 default_value로 채움 (신규 글)
    foreach ($_evDefs as $evKey => $_d) {
        if (!isset($evData[$evKey]) && !empty($_d['default_value'])) {
            $evData[$evKey] = $_d['default_value'];
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

    // 통합 정책: title/content 는 rzx_translations 에 항상 저장
    // (rzx_board_posts.title/content 는 backward compat 미러, Phase 5 에서 폐기 예정)
    board_post_text_save($pdo, $prefix, (int)$newId, 'title', $currentLocale, $title, $currentLocale);
    board_post_text_save($pdo, $prefix, (int)$newId, 'content', $currentLocale, $content, $currentLocale);
    if (!empty($extraVarsJson)) {
        board_post_text_save($pdo, $prefix, (int)$newId, 'extra_vars', $currentLocale, $extraVarsJson, $currentLocale);
    }

    // 파일 업로드 처리
    if (!empty($_FILES['files']['name'][0])) {
        $uploadDir = BASE_PATH . '/storage/board/' . $boardId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $fileCount = 0;
        $uploadedFileIds = []; // $_FILES 인덱스 → board_files.id
        foreach ($_FILES['files']['name'] as $i => $fname) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = pathinfo($fname, PATHINFO_EXTENSION);
            $stored = uniqid('bf_') . '.' . $ext;
            $dest = $uploadDir . $stored;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
                $pdo->prepare("INSERT INTO {$prefix}board_files (post_id, board_id, original_name, stored_name, file_path, file_size, mime_type) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$newId, $boardId, $fname, $stored, '/storage/board/' . $boardId . '/' . $stored, $_FILES['files']['size'][$i], $_FILES['files']['type'][$i] ?? '']);
                $uploadedFileIds[$i] = (int)$pdo->lastInsertId();
                $fileCount++;
            }
        }
        if ($fileCount > 0) {
            $pdo->prepare("UPDATE {$prefix}board_posts SET file_count = ? WHERE id = ?")->execute([$fileCount, $newId]);

            // 클라이언트가 지정한 대표 파일이 있으면 우선
            $primaryPos = isset($_POST['primary_file_pos']) ? (int)$_POST['primary_file_pos'] : -1;
            $targetPrimaryId = ($primaryPos >= 0 && isset($uploadedFileIds[$primaryPos])) ? $uploadedFileIds[$primaryPos] : null;

            // 명시 지정 없으면 첫 번째 이미지 파일을 자동 대표로
            if (!$targetPrimaryId) {
                foreach ($uploadedFileIds as $i => $fid) {
                    if (str_starts_with($_FILES['files']['type'][$i] ?? '', 'image/')) {
                        $targetPrimaryId = $fid;
                        break;
                    }
                }
            }
            if ($targetPrimaryId) {
                $pdo->prepare("UPDATE {$prefix}board_files SET is_primary = 1 WHERE id = ?")
                    ->execute([$targetPrimaryId]);
            }
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

    // 확장 변수 수집 (권한 검증 — 권한 없는 변수는 기존값 유지)
    require_once BASE_PATH . '/rzxlib/Core/Modules/ExtraVarRenderer.php';
    $_evDefStmt = $pdo->prepare("SELECT var_name, permission FROM {$prefix}board_extra_vars WHERE board_id = ? AND is_active = 1");
    $_evDefStmt->execute([$boardId]);
    $_evDefs = [];
    foreach ($_evDefStmt->fetchAll(PDO::FETCH_ASSOC) as $_d) $_evDefs[$_d['var_name']] = $_d;

    $_existingEv = !empty($post['extra_vars']) ? (json_decode($post['extra_vars'], true) ?: []) : [];
    $evData = $_existingEv; // 시작점은 기존값
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'extra_') !== 0) continue;
        $evKey = substr($k, 6);
        if (!isset($_evDefs[$evKey])) continue;
        if (!\RzxLib\Core\Modules\ExtraVarRenderer::canEdit($_evDefs[$evKey])) continue;
        $evData[$evKey] = is_array($v) ? implode(',', $v) : $v;
    }
    $extraVarsJson = !empty($evData) ? json_encode($evData, JSON_UNESCAPED_UNICODE) : $post['extra_vars'];

    $currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');
    $originalLocale = $post['original_locale'] ?? 'ko';

    // 통합 정책: title/content 는 항상 rzx_translations 에 저장 (locale 별)
    board_post_text_save($pdo, $prefix, $postId, 'title', $currentLocale, $title, $originalLocale);
    board_post_text_save($pdo, $prefix, $postId, 'content', $currentLocale, $content, $originalLocale);
    if (!empty($evData)) {
        board_post_text_save($pdo, $prefix, $postId, 'extra_vars', $currentLocale, $extraVarsJson, $originalLocale);
    }

    // 메타 필드 (is_notice, is_secret, category) 는 언어 무관 → base table 에 반영
    // 원본 언어 수정 시 base.title/content/extra_vars 도 미러 (Phase 5 까지 backward compat)
    if ($currentLocale === $originalLocale) {
        $pdo->prepare("UPDATE {$prefix}board_posts SET title=?, content=?, is_notice=?, is_secret=?, category_id=?, extra_vars=?, updated_at=NOW() WHERE id=?")
            ->execute([$title, $content, $isNotice, $isSecret, $categoryId, $extraVarsJson, $postId]);
    } else {
        $pdo->prepare("UPDATE {$prefix}board_posts SET is_notice=?, is_secret=?, category_id=?, updated_at=NOW() WHERE id=?")
            ->execute([$isNotice, $isSecret, $categoryId, $postId]);
    }

    // 새 파일 업로드
    if (!empty($_FILES['files']['name'][0])) {
        $uploadDir = BASE_PATH . '/storage/board/' . $boardId . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $uploadedFileIds = [];
        foreach ($_FILES['files']['name'] as $i => $fname) {
            if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = pathinfo($fname, PATHINFO_EXTENSION);
            $stored = uniqid('bf_') . '.' . $ext;
            $dest = $uploadDir . $stored;
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest)) {
                $pdo->prepare("INSERT INTO {$prefix}board_files (post_id, board_id, original_name, stored_name, file_path, file_size, mime_type) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$postId, $boardId, $fname, $stored, '/storage/board/' . $boardId . '/' . $stored, $_FILES['files']['size'][$i], $_FILES['files']['type'][$i] ?? '']);
                $uploadedFileIds[$i] = (int)$pdo->lastInsertId();
            }
        }
        // file_count 갱신
        $fc = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_files WHERE post_id = ?");
        $fc->execute([$postId]);
        $pdo->prepare("UPDATE {$prefix}board_posts SET file_count = ? WHERE id = ?")->execute([(int)$fc->fetchColumn(), $postId]);

        // 클라이언트가 지정한 대표 파일 (있으면 우선)
        $primaryPos = isset($_POST['primary_file_pos']) ? (int)$_POST['primary_file_pos'] : -1;
        $targetPrimaryId = ($primaryPos >= 0 && isset($uploadedFileIds[$primaryPos])) ? $uploadedFileIds[$primaryPos] : null;
        if ($targetPrimaryId) {
            $pdo->prepare("UPDATE {$prefix}board_files SET is_primary = 0 WHERE post_id = ?")->execute([$postId]);
            $pdo->prepare("UPDATE {$prefix}board_files SET is_primary = 1 WHERE id = ?")->execute([$targetPrimaryId]);
        } else {
            // 명시 지정 없고 현재 글에 대표 이미지가 없는 경우만 첫 신규 이미지를 자동 대표로
            $hasPrimary = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_files WHERE post_id = ? AND is_primary = 1");
            $hasPrimary->execute([$postId]);
            if ((int)$hasPrimary->fetchColumn() === 0) {
                foreach ($uploadedFileIds as $i => $fid) {
                    if (str_starts_with($_FILES['files']['type'][$i] ?? '', 'image/')) {
                        $pdo->prepare("UPDATE {$prefix}board_files SET is_primary = 1 WHERE id = ?")->execute([$fid]);
                        break;
                    }
                }
            }
        }
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
        // 다국어 번역 row 도 함께 정리
        board_post_text_delete_all($pdo, $prefix, $postId);
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
