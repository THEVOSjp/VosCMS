<?php
/**
 * VosCMS Public API - 공지사항 (본사 전용)
 * GET /api/notices
 *
 * 각 VosCMS 사이트의 대시보드에서 본사 공지사항을 가져갈 때 사용.
 * 요청 시 locale 파라미터로 해당 언어 번역을 반환.
 *
 * 보안: 본사 voscms.com 도메인에서만 응답. .env 의 HQ_HOSTS 로 화이트리스트 변경 가능.
 */

// .env 로드 (HQ_HOSTS 사용)
if (empty($_ENV['DB_HOST']) && file_exists(dirname(__DIR__) . '/.env')) {
    foreach (file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, "\"' ");
    }
}

$_hqHosts = array_filter(array_map('trim', explode(',', $_ENV['HQ_HOSTS'] ?? 'voscms.com,www.voscms.com')));
if (!in_array($_SERVER['HTTP_HOST'] ?? '', $_hqHosts, true)) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$locale = $_GET['locale'] ?? 'ko';
$limit = min((int)($_GET['limit'] ?? 5), 20);

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    echo json_encode(['notices' => []]);
    exit;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// notice 게시판 ID
$boardStmt = $pdo->prepare("SELECT id FROM {$prefix}boards WHERE slug = 'notice' LIMIT 1");
$boardStmt->execute();
$boardId = (int)$boardStmt->fetchColumn();

if (!$boardId) {
    echo json_encode(['notices' => []]);
    exit;
}

// 공지글 가져오기 (is_notice=1 우선, 최신순)
$limit = (int)$limit;
$stmt = $pdo->prepare(
    "SELECT p.id, p.title, p.content, p.nick_name, p.created_at, p.is_notice, p.original_locale,
            c.slug as category_slug
     FROM {$prefix}board_posts p
     LEFT JOIN {$prefix}board_categories c ON c.id = p.category_id
     WHERE p.board_id = ? AND p.status = 'published'
     ORDER BY p.is_notice DESC, p.created_at DESC
     LIMIT {$limit}"
);
$stmt->execute([$boardId]);
$posts = $stmt->fetchAll();

// 번역 적용
$notices = [];
foreach ($posts as $post) {
    $postId = (int)$post['id'];
    $title = $post['title'];
    $content = $post['content'];

    // 원본 언어와 다르면 번역 조회
    if ($locale !== ($post['original_locale'] ?? 'ko')) {
        $trStmt = $pdo->prepare(
            "SELECT lang_key, content FROM {$prefix}translations
             WHERE lang_key IN (?, ?) AND locale = ?"
        );
        $trStmt->execute([
            "board_post.{$postId}.title",
            "board_post.{$postId}.content",
            $locale,
        ]);
        $translations = $trStmt->fetchAll();
        foreach ($translations as $tr) {
            if (str_ends_with($tr['lang_key'], '.title')) $title = $tr['content'];
            if (str_ends_with($tr['lang_key'], '.content')) $content = $tr['content'];
        }

        // 영어 폴백
        if ($title === $post['title'] && $locale !== 'en') {
            $trStmt->execute([
                "board_post.{$postId}.title",
                "board_post.{$postId}.content",
                'en',
            ]);
            $enTr = $trStmt->fetchAll();
            foreach ($enTr as $tr) {
                if (str_ends_with($tr['lang_key'], '.title')) $title = $tr['content'];
                if (str_ends_with($tr['lang_key'], '.content')) $content = $tr['content'];
            }
        }
    }

    // type 판별 — 카테고리 slug 기반
    $catSlug = $post['category_slug'] ?? '';
    $type = match($catSlug) {
        'update' => 'release',
        'security' => 'security',
        'event' => 'feature',
        'maintenance' => 'maintenance',
        'notice' => 'info',
        default => 'info',
    };

    $notices[] = [
        'title' => strip_tags($title),
        'content' => mb_substr(strip_tags($content), 0, 200),
        'date' => date('Y-m-d', strtotime($post['created_at'])),
        'type' => $type,
        'url' => ($_ENV['APP_URL'] ?? 'https://vos.21ces.com') . '/board/notice/' . $postId,
    ];
}

echo json_encode(['notices' => $notices], JSON_UNESCAPED_UNICODE);
