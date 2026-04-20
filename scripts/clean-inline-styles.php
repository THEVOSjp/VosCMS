<?php
/**
 * Summernote 에디터가 캡처한 computed-style 인라인 오염 정화
 *
 * 대상: rzx_page_contents.content 의 <tag style="--tw-*..."> 블록
 * 동작: style 속성이 --tw-* CSS 변수를 포함하면 style 속성 전체 제거
 *
 * 사용:
 *   php8.3 scripts/clean-inline-styles.php --dry-run              # 미리보기
 *   php8.3 scripts/clean-inline-styles.php --slug=terms            # 특정 slug 만
 *   php8.3 scripts/clean-inline-styles.php                         # 전체 적용
 */

$opts = getopt('', ['dry-run', 'slug::']);
$dryRun = isset($opts['dry-run']);
$slugFilter = $opts['slug'] ?? null;

$envFile = __DIR__ . '/../.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(ltrim($line), '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \"'");
}

$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
    $env['DB_USERNAME'], $env['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$prefix = $env['DB_PREFIX'] ?? 'rzx_';

// style 속성에 --tw- 가 포함된 경우 style 속성 전체 제거
// 멀티라인 · 큰따옴표 · 작은따옴표 지원
function cleanInlineStyles(string $html): string
{
    // \s*style\s*=\s*"..."  — 따옴표 안에 --tw- 가 있으면 제거
    $html = preg_replace('/\s+style\s*=\s*"[^"]*--tw-[^"]*"/i', '', $html);
    $html = preg_replace("/\s+style\s*=\s*'[^']*--tw-[^']*'/i", '', $html);
    // 속성 제거 후 빈 `<tag >` → `<tag>` 공백 정리 (cosmetic)
    $html = preg_replace('/<(\w+)\s+>/', '<$1>', $html);
    return $html;
}

$sql = "SELECT id, page_slug, locale, content FROM {$prefix}page_contents WHERE content LIKE '%--tw-%'";
$params = [];
if ($slugFilter) {
    $sql .= " AND page_slug = ?";
    $params[] = $slugFilter;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSaved = 0;
$processed = 0;
foreach ($rows as $row) {
    $orig = $row['content'];
    $clean = cleanInlineStyles($orig);
    $saved = strlen($orig) - strlen($clean);
    $totalSaved += $saved;
    $processed++;

    $pct = strlen($orig) > 0 ? round($saved / strlen($orig) * 100) : 0;
    echo sprintf(
        "%s / %s : %s → %s bytes (-%d%%)\n",
        str_pad($row['page_slug'], 16),
        str_pad($row['locale'], 6),
        number_format(strlen($orig)),
        number_format(strlen($clean)),
        $pct
    );

    if (!$dryRun) {
        $up = $pdo->prepare("UPDATE {$prefix}page_contents SET content = ?, updated_at = NOW() WHERE id = ?");
        $up->execute([$clean, $row['id']]);
    }
}

echo str_repeat('-', 60) . "\n";
echo sprintf("%d rows processed — total saved: %s bytes\n", $processed, number_format($totalSaved));
echo $dryRun ? "(DRY RUN — no changes committed)\n" : "✓ committed\n";
