<?php
/**
 * 이슈 게시판 13개국어 시드
 *  - post 34 삭제 (첨부파일 + DB)
 *  - 3개 신규 샘플 글 생성 (접수, 확인 중, 진행 중)
 *  - board.6.title, board.6.header_content 13개국어 번역
 *  - post 35 + 신규 3개 글의 title/content 13개국어 번역
 */

define('BASE_PATH', '/var/www/voscms');

// .env 로드
foreach (file(BASE_PATH . '/.env') as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    [$k, $v] = array_map('trim', explode('=', $line, 2) + [1 => '']);
    $_ENV[$k] = trim($v, '"\'');
}
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO(
    'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'] . ';charset=utf8mb4',
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$LOCALES = ['ko','en','ja','de','es','fr','id','mn','ru','tr','vi','zh_CN','zh_TW'];

// ─── 1. post 34 + 첨부파일 삭제 ───────────────────
echo "[1] Deleting post 34 + attachments...\n";
$file = $pdo->prepare("SELECT file_path FROM {$prefix}board_files WHERE post_id=34");
$file->execute();
foreach ($file->fetchAll(PDO::FETCH_COLUMN) as $fp) {
    $abs = BASE_PATH . $fp;
    if (file_exists($abs)) { @unlink($abs); echo "  rm $abs\n"; }
}
$pdo->exec("DELETE FROM {$prefix}board_files WHERE post_id=34");
$pdo->exec("DELETE FROM {$prefix}board_posts WHERE id=34");
echo "  done.\n";

// ─── 2. 3개 신규 샘플 글 생성 ──────────────────────
$now = time();
$samples = [
    [
        'status' => '접수',
        'title_ko' => '[버그] 모바일 햄버거 메뉴 X 버튼이 동작하지 않습니다',
    ],
    [
        'status' => '확인 중',
        'title_ko' => '[기능 요청] 댓글 작성 시 게시글 작성자에게 메일 알림',
    ],
    [
        'status' => '진행 중',
        'title_ko' => '[성능] 마켓플레이스 상세 페이지 첫 로딩 속도 개선',
    ],
];

$userId = 'f0a9fa3b-5309-a00e-5330-632de0839ff5'; // 기존 35번과 동일
$nick   = 'VosCMS';

$ins = $pdo->prepare("INSERT INTO {$prefix}board_posts
    (board_id, user_id, title, content, nick_name, is_anonymous, status, original_locale, source_locale, list_order, update_order, extra_vars, created_at, updated_at)
    VALUES (6, ?, ?, ?, ?, 0, 'published', 'ko', 'ko', ?, ?, ?, NOW(), NOW())");

$createdIds = [];
foreach ($samples as $i => $s) {
    $contentKo = render_post_content_ko($s['status']);
    $order = $now + $i + 1;
    $ins->execute([$userId, $s['title_ko'], $contentKo, $nick, $order, $order, json_encode(['status' => $s['status']], JSON_UNESCAPED_UNICODE)]);
    $newId = (int)$pdo->lastInsertId();
    $createdIds[] = $newId;
    echo "[2] Created post $newId ({$s['status']}): {$s['title_ko']}\n";
}

// ─── 3. 번역 데이터 ────────────────────────────────
$T = require __DIR__ . '/seed_issue_i18n_data.php';

// ─── 4. translations 테이블에 저장 ────────────────
$trIns = $pdo->prepare("INSERT INTO {$prefix}translations (lang_key, locale, content, created_at, updated_at)
                       VALUES (?, ?, ?, NOW(), NOW())
                       ON DUPLICATE KEY UPDATE content=VALUES(content), updated_at=NOW()");

// 4-1. 게시판 자체
echo "[3] Inserting board.6.title × 13...\n";
foreach ($LOCALES as $loc) {
    $trIns->execute(['board.6.title', $loc, $T['board_title'][$loc]]);
}

echo "[3] Inserting board.6.header_content × 13...\n";
foreach ($LOCALES as $loc) {
    $html = render_header_html($T['header'][$loc], $T['status_badges'][$loc]);
    $trIns->execute(['board.6.header_content', $loc, $html]);
}

// 4-2. 게시글 (35 + 신규 3개)
$allPosts = [
    35 => 'post35',
    $createdIds[0] => 'sample_received',
    $createdIds[1] => 'sample_verifying',
    $createdIds[2] => 'sample_in_progress',
];

foreach ($allPosts as $pid => $key) {
    echo "[3] Inserting board_post.$pid.title/content × 13 ($key)...\n";
    foreach ($LOCALES as $loc) {
        $trIns->execute(["board_post.{$pid}.title", $loc, $T['posts'][$key]['title'][$loc]]);
        $trIns->execute(["board_post.{$pid}.content", $loc, $T['posts'][$key]['content'][$loc]]);
    }
}

echo "\n✅ DONE. created post ids: " . implode(',', $createdIds) . "\n";
echo "Total translations: " . (13 * 2 /* board */ + 13 * 2 * 4 /* posts */) . " rows\n";


// ─── 함수 ──────────────────────────────────────────
function render_post_content_ko(string $status): string {
    // 상태별 ko 본문은 데이터 파일에서 가져온 것을 그대로 INSERT 시 사용 (post35 제외)
    static $bodies = null;
    if ($bodies === null) {
        $T = require __DIR__ . '/seed_issue_i18n_data.php';
        $bodies = [
            '접수'    => $T['posts']['sample_received']['content']['ko'],
            '확인 중' => $T['posts']['sample_verifying']['content']['ko'],
            '진행 중' => $T['posts']['sample_in_progress']['content']['ko'],
        ];
    }
    return $bodies[$status] ?? '';
}

function render_header_html(array $h, array $badges): string {
    return '<div class="not-prose mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm leading-relaxed text-zinc-700 dark:border-amber-700/50 dark:bg-amber-900/20 dark:text-zinc-200">' . "\n"
        . '    <div class="mb-3 flex items-center gap-2">' . "\n"
        . '        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>' . "\n"
        . '        <strong class="text-base text-zinc-900 dark:text-zinc-50">' . $h['guide_title'] . '</strong>' . "\n"
        . '    </div>' . "\n"
        . '    <p class="mb-3">' . $h['intro'] . '</p>' . "\n\n"
        . '    <div class="mb-3">' . "\n"
        . '        <p class="mb-1 font-semibold text-zinc-800 dark:text-zinc-100">📌 ' . $h['before_post_label'] . '</p>' . "\n"
        . '        <ul class="list-disc space-y-1 pl-5">' . "\n"
        . '            <li>' . $h['before_post_1'] . '</li>' . "\n"
        . '            <li>' . $h['before_post_2'] . '</li>' . "\n"
        . '            <li>' . $h['before_post_3'] . '</li>' . "\n"
        . '        </ul>' . "\n"
        . '    </div>' . "\n\n"
        . '    <div class="mb-3">' . "\n"
        . '        <p class="mb-1 font-semibold text-zinc-800 dark:text-zinc-100">✍️ ' . $h['format_label'] . '</p>' . "\n"
        . '        <ul class="list-disc space-y-1 pl-5">' . "\n"
        . '            <li>' . $h['format_1'] . '</li>' . "\n"
        . '            <li>' . $h['format_2'] . '</li>' . "\n"
        . '            <li>' . $h['format_3'] . '</li>' . "\n"
        . '            <li>' . $h['format_4'] . '</li>' . "\n"
        . '        </ul>' . "\n"
        . '    </div>' . "\n\n"
        . '    <div class="mb-3">' . "\n"
        . '        <p class="mb-1 font-semibold text-zinc-800 dark:text-zinc-100">🔒 ' . $h['private_label'] . '</p>' . "\n"
        . '        <ul class="list-disc space-y-1 pl-5">' . "\n"
        . '            <li>' . $h['private_1'] . '</li>' . "\n"
        . '            <li>' . $h['private_2'] . '</li>' . "\n"
        . '        </ul>' . "\n"
        . '    </div>' . "\n\n"
        . '    <div>' . "\n"
        . '        <p class="mb-1 font-semibold text-zinc-800 dark:text-zinc-100">🏷️ ' . $h['stages_label'] . '</p>' . "\n"
        . '        <p class="mb-2">' . $h['stages_desc'] . '</p>' . "\n"
        . '        <div class="flex flex-wrap items-center gap-2">' . "\n"
        . '            <span class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-xs bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">' . $badges[0] . '</span>' . "\n"
        . '            <span class="text-zinc-400">→</span>' . "\n"
        . '            <span class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">' . $badges[1] . '</span>' . "\n"
        . '            <span class="text-zinc-400">→</span>' . "\n"
        . '            <span class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">' . $badges[2] . '</span>' . "\n"
        . '            <span class="text-zinc-400">→</span>' . "\n"
        . '            <span class="inline-flex items-center font-medium rounded-full px-2 py-0.5 text-xs bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">' . $badges[3] . '</span>' . "\n"
        . '        </div>' . "\n"
        . '    </div>' . "\n"
        . '</div>';
}
