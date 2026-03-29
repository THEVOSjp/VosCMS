<?php
/**
 * 게시판 설정 - 프론트 레이아웃 래퍼
 * 관리자만 접근 가능. boards-edit.php를 embed 모드로 include.
 */

// 관리자 확인
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo '<p>관리자 권한이 필요합니다.</p>';
    exit;
}

// 게시판 조회
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$boardStmt = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE slug = ?");
$boardStmt->execute([$boardSlug]);
$board = $boardStmt->fetch(PDO::FETCH_ASSOC);
if (!$board) {
    http_response_code(404);
    echo '<p>게시판을 찾을 수 없습니다.</p>';
    exit;
}

// embed 모드로 boards-edit.php에 전달
$_GET['id'] = $board['id'];
$_GET['embed'] = '1';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <!-- 돌아가기 링크 -->
    <div class="mb-4">
        <a href="<?= $baseUrl ?>/board/<?= htmlspecialchars($boardSlug) ?>" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <?= htmlspecialchars($board['title']) ?>
        </a>
    </div>

    <?php
    // boards-edit.php를 embed 모드로 include (사이드바/탑바 없이 콘텐츠만)
    include BASE_PATH . '/resources/views/admin/site/boards-edit.php';
    ?>
</div>
