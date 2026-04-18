<?php
/**
 * 위젯 일괄 마켓플레이스 등록 스크립트
 *
 * 사용법: php scripts/register-widgets-to-marketplace.php
 *
 * 각 위젯의 widget.json을 읽어 rzx_mp_items + rzx_mp_item_versions에 등록
 * 이미 등록된 위젯은 버전 비교 후 업데이트
 */

if (php_sapi_name() !== 'cli') {
    die('CLI 전용 스크립트입니다.');
}

define('BASE_PATH', dirname(__DIR__));

// .env 로드
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 위젯 카테고리 → 마켓플레이스 카테고리 slug 매핑
$categoryMap = [
    'content' => 'content',
    'marketing' => 'marketing',
    'layout' => 'design',
    'design' => 'design',
    'business' => 'business',
    'social' => 'social',
    'utility' => 'utility',
    'ecommerce' => 'ecommerce',
    'analytics' => 'analytics',
    'system' => 'utility',
    'general' => 'utility',
];

// 카테고리 slug → id 조회
$catMap = [];
$catStmt = $pdo->query("SELECT id, slug FROM {$prefix}mp_categories");
while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
    $catMap[$row['slug']] = (int)$row['id'];
}

// 위젯 스캔
$widgetDirs = glob(BASE_PATH . '/widgets/*/widget.json');
$stats = ['new' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

foreach ($widgetDirs as $jsonPath) {
    $slug = basename(dirname($jsonPath));
    $data = json_decode(file_get_contents($jsonPath), true);

    if (!$data || empty($data['slug'])) {
        echo "[SKIP] {$slug}: invalid widget.json\n";
        $stats['skipped']++;
        continue;
    }

    try {
        $wSlug = $data['slug'];
        $name = json_encode($data['name'] ?? [$wSlug], JSON_UNESCAPED_UNICODE);
        $desc = json_encode($data['description'] ?? [], JSON_UNESCAPED_UNICODE);
        $shortDesc = json_encode($data['description'] ?? [], JSON_UNESCAPED_UNICODE);
        $author = $data['author'] ?? 'VosCMS';
        $version = $data['version'] ?? '1.0.0';
        $icon = $data['icon'] ?? 'cube';
        $wCat = $data['category'] ?? 'general';
        $mpCat = $categoryMap[$wCat] ?? 'utility';
        $catId = $catMap[$mpCat] ?? null;

        // 썸네일
        $thumbFile = $data['thumbnail'] ?? 'thumbnail.png';
        $thumbPath = dirname($jsonPath) . '/' . $thumbFile;
        $bannerImage = file_exists($thumbPath)
            ? '/widgets/' . $slug . '/' . $thumbFile . '?v=' . filemtime($thumbPath)
            : '';

        // 이미 등록된 아이템 확인
        $existStmt = $pdo->prepare("SELECT id, latest_version FROM {$prefix}mp_items WHERE slug = ? AND type = 'widget'");
        $existStmt->execute([$wSlug]);
        $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $itemId = (int)$existing['id'];
            // 버전/정보 업데이트
            $pdo->prepare("UPDATE {$prefix}mp_items SET
                name=?, description=?, short_description=?, author_name=?,
                category_id=?, icon=?, banner_image=?, latest_version=?, updated_at=NOW()
                WHERE id=?")
                ->execute([$name, $desc, $shortDesc, $author, $catId, $icon, $bannerImage, $version, $itemId]);

            if ($existing['latest_version'] !== $version) {
                $stats['updated']++;
                echo "[UPDATE] {$wSlug} v{$existing['latest_version']} → v{$version}\n";
            } else {
                $stats['skipped']++;
                echo "[SKIP]   {$wSlug} v{$version} (최신)\n";
            }
        } else {
            // 새 등록
            $pdo->prepare("INSERT INTO {$prefix}mp_items
                (slug, type, name, description, short_description, author_name, category_id,
                 icon, banner_image, price, currency, latest_version, status, is_verified, created_at, updated_at)
                VALUES (?, 'widget', ?, ?, ?, ?, ?, ?, ?, 0, 'JPY', ?, 'active', 1, NOW(), NOW())")
                ->execute([$wSlug, $name, $desc, $shortDesc, $author, $catId, $icon, $bannerImage, $version]);
            $itemId = (int)$pdo->lastInsertId();
            $stats['new']++;
            echo "[NEW]    {$wSlug} v{$version} (cat: {$mpCat})\n";
        }

        // 버전 레코드 등록/업데이트
        $verStmt = $pdo->prepare("SELECT id FROM {$prefix}mp_item_versions WHERE item_id = ? AND version = ?");
        $verStmt->execute([$itemId, $version]);
        if (!$verStmt->fetchColumn()) {
            $pdo->prepare("INSERT INTO {$prefix}mp_item_versions
                (item_id, version, changelog, download_url, status, released_at, created_at)
                VALUES (?, ?, ?, ?, 'active', NOW(), NOW())")
                ->execute([$itemId, $version, '자동 등록', '']);
        }

    } catch (\Throwable $e) {
        echo "[ERROR]  {$slug}: " . $e->getMessage() . "\n";
        $stats['errors']++;
    }
}

echo "\n=== 결과 ===\n";
echo "신규 등록: {$stats['new']}개\n";
echo "업데이트: {$stats['updated']}개\n";
echo "건너뜀:   {$stats['skipped']}개\n";
echo "에러:     {$stats['errors']}개\n";
echo "전체:     " . count($widgetDirs) . "개\n";
