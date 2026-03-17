<?php
/**
 * RezlyX 게시판 - 댓글 API
 * POST: create, delete
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
    $postId = (int)($_POST['post_id'] ?? 0);
    $boardId = (int)($_POST['board_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    $parentId = (int)($_POST['parent_id'] ?? 0) ?: null;

    if (!$postId || !$content) {
        echo json_encode(['success' => false, 'message' => '내용을 입력해주세요.']);
        exit;
    }

    // 게시판 설정
    $boardStmt = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE id = ?");
    $boardStmt->execute([$boardId]);
    $board = $boardStmt->fetch(PDO::FETCH_ASSOC);

    // 글자 수 제한
    $commentLimit = (int)($board['comment_length_limit'] ?? 0);
    if ($commentLimit > 0 && mb_strlen($content) > $commentLimit) {
        echo json_encode(['success' => false, 'message' => "댓글은 {$commentLimit}자를 초과할 수 없습니다."]);
        exit;
    }

    // 작성자
    $userId = $currentUser['id'] ?? null;
    $nickName = '';
    $password = null;
    $isAnonymous = 0;

    if ($currentUser) {
        if (($board['use_anonymous'] ?? 0)) {
            // 익명 $NUM 패턴: 같은 게시글 내 작성자별 고유 번호
            $anonBase = $board['anonymous_name'] ?? '익명';
            // 같은 user_id로 해당 글에서 받은 번호 재사용
            $existNum = $pdo->prepare("SELECT nick_name FROM {$prefix}board_comments WHERE post_id = ? AND user_id = ? AND is_anonymous = 1 AND nick_name LIKE ? ORDER BY id ASC LIMIT 1");
            $existNum->execute([$postId, $currentUser['id'], $anonBase . '%']);
            $prevNick = $existNum->fetchColumn();
            if ($prevNick) {
                $nickName = $prevNick;
            } else {
                // 글 작성자와 동일 번호 확인 (게시글의 익명 번호 재사용)
                $postNick = $pdo->prepare("SELECT nick_name FROM {$prefix}board_posts WHERE id = ? AND user_id = ? AND is_anonymous = 1 AND nick_name LIKE ?");
                $postNick->execute([$postId, $currentUser['id'], $anonBase . '%']);
                $postNickVal = $postNick->fetchColumn();
                if ($postNickVal) {
                    $nickName = $postNickVal;
                } else {
                    // 해당 글의 댓글 중 최대 번호 + 1
                    $maxNum = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(nick_name, ?) AS UNSIGNED)) FROM {$prefix}board_comments WHERE post_id = ? AND is_anonymous = 1 AND nick_name LIKE ?");
                    $maxNum->execute([mb_strlen($anonBase) + 1, $postId, $anonBase . '%']);
                    $fromComments = (int)$maxNum->fetchColumn();
                    // 글 작성자 번호도 확인
                    $maxPost = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(nick_name, ?) AS UNSIGNED)) FROM {$prefix}board_posts WHERE id = ? AND is_anonymous = 1 AND nick_name LIKE ?");
                    $maxPost->execute([mb_strlen($anonBase) + 1, $postId, $anonBase . '%']);
                    $fromPost = (int)$maxPost->fetchColumn();
                    $nextNum = max($fromComments, $fromPost) + 1;
                    $nickName = $anonBase . $nextNum;
                }
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

    $isSecret = (int)($_POST['is_secret'] ?? 0);

    // depth 계산
    $depth = 0;
    if ($parentId) {
        $parentStmt = $pdo->prepare("SELECT depth FROM {$prefix}board_comments WHERE id = ?");
        $parentStmt->execute([$parentId]);
        $parentDepth = $parentStmt->fetchColumn();
        $depth = $parentDepth !== false ? (int)$parentDepth + 1 : 0;
    }

    $stmt = $pdo->prepare("INSERT INTO {$prefix}board_comments
        (post_id, board_id, user_id, parent_id, depth, content, password, is_secret, is_anonymous, nick_name, status, ip_address, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())");
    $stmt->execute([
        $postId, $boardId, $userId, $parentId, $depth,
        $content, $password, $isSecret, $isAnonymous, $nickName,
        'published', $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    // comment_count 갱신
    $cc = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_comments WHERE post_id = ? AND status = 'published'");
    $cc->execute([$postId]);
    $pdo->prepare("UPDATE {$prefix}board_posts SET comment_count = ?, updated_at = NOW() WHERE id = ?")->execute([(int)$cc->fetchColumn(), $postId]);

    // update_order 갱신 (댓글 작성 시 글 순서 갱신)
    if ($board['update_order_on_comment'] ?? 0) {
        $pdo->prepare("UPDATE {$prefix}board_posts SET update_order = ? WHERE id = ?")->execute([time(), $postId]);
    }

    echo json_encode(['success' => true, 'message' => '댓글이 작성되었습니다.']);
    exit;
}

// === DELETE ===
if ($action === 'delete') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if (!$commentId) { echo json_encode(['success' => false, 'message' => '댓글 ID가 필요합니다.']); exit; }

    $comment = $pdo->prepare("SELECT * FROM {$prefix}board_comments WHERE id = ?");
    $comment->execute([$commentId]);
    $comment = $comment->fetch(PDO::FETCH_ASSOC);
    if (!$comment) { echo json_encode(['success' => false, 'message' => '댓글을 찾을 수 없습니다.']); exit; }

    if (!$currentUser || ($currentUser['id'] != $comment['user_id'] && ($currentUser['role'] ?? '') !== 'admin')) {
        echo json_encode(['success' => false, 'message' => '삭제 권한이 없습니다.']);
        exit;
    }

    // 대댓글이 있으면 status만 변경, 없으면 삭제
    $hasChild = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_comments WHERE parent_id = ? AND status = 'published'");
    $hasChild->execute([$commentId]);
    if ((int)$hasChild->fetchColumn() > 0) {
        $pdo->prepare("UPDATE {$prefix}board_comments SET status = 'deleted' WHERE id = ?")->execute([$commentId]);
    } else {
        $pdo->prepare("DELETE FROM {$prefix}board_comments WHERE id = ?")->execute([$commentId]);
    }

    // comment_count 갱신
    $cc = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_comments WHERE post_id = ? AND status = 'published'");
    $cc->execute([$comment['post_id']]);
    $pdo->prepare("UPDATE {$prefix}board_posts SET comment_count = ? WHERE id = ?")->execute([(int)$cc->fetchColumn(), $comment['post_id']]);

    echo json_encode(['success' => true, 'message' => '댓글이 삭제되었습니다.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
