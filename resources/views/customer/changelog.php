<?php
/**
 * Changelog 공개 페이지
 * docs/CHANGELOG.md + version.json에서 자동 렌더링
 */
$pageTitle = 'Changelog - ' . ($config['app_name'] ?? 'VosCMS');

// version.json
$versionFile = BASE_PATH . '/version.json';
$versionData = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : [];
$currentVersion = $versionData['version'] ?? '2.1.0';

// CHANGELOG.md 파싱
$changelogFile = BASE_PATH . '/docs/CHANGELOG.md';
$changelogMd = file_exists($changelogFile) ? file_get_contents($changelogFile) : '';

// 간단한 Markdown → HTML 변환
function mdToHtml(string $md): string {
    $lines = explode("\n", $md);
    $html = '';
    $inList = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!$trimmed) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            continue;
        }

        // ## 헤딩 → 버전 카드
        if (preg_match('/^## \[(.+?)\]\s*-\s*(.+)$/', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $html .= '</div>' . "\n"; // 이전 카드 닫기
            $ver = htmlspecialchars($m[1]);
            $date = htmlspecialchars(trim($m[2]));
            $html .= '<div class="mb-8 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">';
            $html .= '<div class="flex items-center gap-3 mb-4">';
            $html .= '<span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-bold rounded-full">' . $ver . '</span>';
            $html .= '<span class="text-sm text-zinc-400">' . $date . '</span>';
            $html .= '</div>' . "\n";
            continue;
        }

        // ### 섹션 제목
        if (preg_match('/^### (.+)$/', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $title = htmlspecialchars($m[1]);
            // 색상 분류
            $color = 'zinc';
            if (str_contains($title, 'Added') || str_contains($title, '추가')) $color = 'green';
            elseif (str_contains($title, 'Changed') || str_contains($title, '변경')) $color = 'blue';
            elseif (str_contains($title, 'Fixed') || str_contains($title, '수정')) $color = 'amber';
            elseif (str_contains($title, 'DB') || str_contains($title, 'Docs')) $color = 'purple';
            $html .= '<h3 class="text-sm font-bold text-' . $color . '-600 dark:text-' . $color . '-400 uppercase tracking-wider mt-4 mb-2">' . $title . '</h3>' . "\n";
            continue;
        }

        // - 리스트
        if (preg_match('/^- (.+)$/', $trimmed, $m)) {
            if (!$inList) { $html .= '<ul class="space-y-1 mb-3">' . "\n"; $inList = true; }
            $content = $m[1];
            // **bold** 변환
            $content = preg_replace('/\*\*(.+?)\*\*/', '<strong class="text-zinc-800 dark:text-zinc-200">$1</strong>', $content);
            // `code` 변환
            $content = preg_replace('/`(.+?)`/', '<code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded text-xs font-mono">$1</code>', $content);
            $html .= '<li class="text-sm text-zinc-600 dark:text-zinc-400 flex items-start gap-2"><span class="text-zinc-300 dark:text-zinc-600 mt-1.5">•</span><span>' . $content . '</span></li>' . "\n";
            continue;
        }

        // --- 구분선
        if ($trimmed === '---') {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            continue;
        }

        // # 제목 (건너뛰기)
        if (str_starts_with($trimmed, '# ')) continue;
        // 일반 텍스트
        if ($trimmed) {
            $html .= '<p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">' . htmlspecialchars($trimmed) . '</p>' . "\n";
        }
    }
    if ($inList) $html .= "</ul>\n";
    $html .= '</div>';

    // 첫 번째 빈 </div> 제거
    $html = preg_replace('/^<\/div>/', '', trim($html), 1);

    return $html;
}

$changelogHtml = mdToHtml($changelogMd);
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- 헤더 -->
    <div class="text-center mb-10">
        <div class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 tracking-widest uppercase mb-2">Changelog</div>
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-3">업데이트 내역</h1>
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-full">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">현재 버전: v<?= htmlspecialchars($currentVersion) ?></span>
        </div>
    </div>

    <!-- 타임라인 -->
    <?= $changelogHtml ?>
</div>
