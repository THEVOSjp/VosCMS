<?php
/**
 * VosCMS - sitemap.xml 동적 생성
 * /sitemap.xml → nginx rewrite → /sitemap.php
 *
 * 포함: 페이지, 게시판 목록, 게시글, 마켓플레이스 아이템
 */

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

// 캐시 (1시간)
$cacheFile = __DIR__ . '/storage/cache/sitemap.xml';
if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 3600) {
    readfile($cacheFile);
    exit;
}

// .env 로드
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}
require_once __DIR__ . '/vendor/autoload.php';

$baseUrl = rtrim($_ENV['APP_URL'] ?? 'https://voscms.com', '/');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

try {
    $pdo = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . ";dbname=" . ($_ENV['DB_DATABASE'] ?? '') . ";charset=utf8mb4",
        $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>';
    exit;
}

$urls = [];

// 1. 홈
$urls[] = ['loc' => $baseUrl . '/', 'changefreq' => 'daily', 'priority' => '1.0'];

// 2. 정적 페이지 (rzx_page_contents)
$pages = $pdo->query("SELECT DISTINCT page_slug FROM {$prefix}page_contents WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
$skipSlugs = ['home', 'index']; // 홈은 이미 추가
foreach ($pages as $slug) {
    if (in_array($slug, $skipSlugs)) continue;
    $priority = in_array($slug, ['voscms', 'Brand', 'contact']) ? '0.8' : '0.6';
    $urls[] = ['loc' => $baseUrl . '/' . $slug, 'changefreq' => 'weekly', 'priority' => $priority];
}

// 3. 게시판 목록 + 게시글
try {
    $boards = $pdo->query("SELECT slug FROM {$prefix}boards WHERE is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($boards as $boardSlug) {
        $urls[] = ['loc' => $baseUrl . '/' . $boardSlug, 'changefreq' => 'daily', 'priority' => '0.7'];

        // 최근 게시글 (최대 100개)
        $bStmt = $pdo->prepare("SELECT bp.id, bp.updated_at FROM {$prefix}boards b JOIN {$prefix}board_posts bp ON b.id = bp.board_id WHERE b.slug = ? AND bp.status = 'published' ORDER BY bp.created_at DESC LIMIT 100");
        $bStmt->execute([$boardSlug]);
        while ($post = $bStmt->fetch(PDO::FETCH_ASSOC)) {
            $urls[] = [
                'loc' => $baseUrl . '/' . $boardSlug . '/' . $post['id'],
                'lastmod' => date('Y-m-d', strtotime($post['updated_at'])),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }
    }
} catch (PDOException $e) {}

// 4. 마켓플레이스 아이템
try {
    $items = $pdo->query("SELECT slug, updated_at FROM {$prefix}mp_items WHERE status = 'active' ORDER BY updated_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as $item) {
        $urls[] = [
            'loc' => $baseUrl . '/marketplace/' . $item['slug'],
            'lastmod' => date('Y-m-d', strtotime($item['updated_at'])),
            'changefreq' => 'weekly',
            'priority' => '0.6',
        ];
    }
} catch (PDOException $e) {}

// 5. 고정 경로
$staticRoutes = ['marketplace', 'developer', 'downloads'];
foreach ($staticRoutes as $r) {
    $urls[] = ['loc' => $baseUrl . '/' . $r, 'changefreq' => 'weekly', 'priority' => '0.7'];
}

// XML 생성
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u) {
    $xml .= "  <url>\n";
    $xml .= "    <loc>" . htmlspecialchars($u['loc']) . "</loc>\n";
    if (!empty($u['lastmod'])) $xml .= "    <lastmod>" . $u['lastmod'] . "</lastmod>\n";
    $xml .= "    <changefreq>" . ($u['changefreq'] ?? 'weekly') . "</changefreq>\n";
    $xml .= "    <priority>" . ($u['priority'] ?? '0.5') . "</priority>\n";
    $xml .= "  </url>\n";
}
$xml .= '</urlset>';

// 캐시 저장
@file_put_contents($cacheFile, $xml);

echo $xml;
