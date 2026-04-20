<?php
/**
 * VosCMS Changelog — 시스템 페이지 (공개 뷰)
 *
 * 데이터: rzx_changelog 테이블 (locale 체인 적용)
 * 스킨 : skins/page/changelog/skin.json (변수 커스터마이징)
 */

$baseUrl = rtrim($config['app_url'] ?? '', '/');
$prefix  = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $_SESSION['locale'] ?? $_COOKIE['locale'] ?? 'ko';

// ── 스킨 설정 로드 ─────────────────────
$pgCfgKey = 'page_config_changelog';
$pgCfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
$pgCfgStmt->execute([$pgCfgKey]);
$pgCfg = json_decode($pgCfgStmt->fetchColumn() ?: '{}', true) ?: [];
$skinCfg = $pgCfg['skin_config'] ?? [];

// 기본값 (changelog 전용 + 공통 페이지 스킨 변수)
$defaults = [
    // changelog 전용
    'card_variant'     => 'filled',
    'badge_bg'         => '#eef2ff',
    'badge_fg'         => '#4338ca',
    'badge_shape'      => 'pill',
    'color_added'      => '#22c55e',
    'color_changed'    => '#3b82f6',
    'color_fixed'      => '#f59e0b',
    'color_removed'    => '#ef4444',
    'color_security'   => '#dc2626',
    'color_deprecated' => '#eab308',
    'date_format'      => 'iso',
    'show_relative_date'     => true,
    'show_internal_sections' => false,
    'max_versions'     => 0,
    // 공통 페이지 스킨 변수 (skins/page/default/skin.json)
    'content_width'    => 'max-w-4xl',
    'show_title'       => '1',
    'show_breadcrumb'  => '0',
    'title_bg_type'    => 'none',
    'title_bg_image'   => '',
    'title_bg_video'   => '',
    'title_bg_height'  => 200,
    'title_bg_overlay' => 40,
    'title_text_color' => 'auto',
    'content_bg'       => 'transparent',
    'custom_css'       => '',
    'custom_header_html' => '',
    'custom_footer_html' => '',
];
foreach ($defaults as $k => $v) {
    if (!isset($skinCfg[$k]) || $skinCfg[$k] === '') $skinCfg[$k] = $v;
}
// checkbox/boolean 정규화
$skinCfg['show_relative_date']     = filter_var($skinCfg['show_relative_date'], FILTER_VALIDATE_BOOLEAN);
$skinCfg['show_internal_sections'] = filter_var($skinCfg['show_internal_sections'], FILTER_VALIDATE_BOOLEAN);
$skinCfg['max_versions']           = (int)$skinCfg['max_versions'];
$_showTitle      = ($skinCfg['show_title'] ?? '1') !== '0';
$_showBreadcrumb = ($skinCfg['show_breadcrumb'] ?? '0') !== '0';
$_titleBgType    = $skinCfg['title_bg_type'];
$_titleBgImage   = $skinCfg['title_bg_image'];
$_titleBgVideo   = $skinCfg['title_bg_video'];
$_titleBgHeight  = (int)$skinCfg['title_bg_height'];
$_titleBgOverlay = (int)$skinCfg['title_bg_overlay'];
$_titleTextColor = $skinCfg['title_text_color'];
$_hasTitleBg = $_showTitle && $_titleBgType !== 'none'
    && (($_titleBgType === 'image' && $_titleBgImage) || ($_titleBgType === 'video' && $_titleBgVideo));
$_titleTextClass = 'text-zinc-800 dark:text-zinc-100';
if ($_hasTitleBg) {
    $_titleTextClass = $_titleTextColor === 'dark' ? 'text-zinc-900' : 'text-white';
} elseif ($_titleTextColor === 'white') {
    $_titleTextClass = 'text-white';
} elseif ($_titleTextColor === 'dark') {
    $_titleTextClass = 'text-zinc-900';
}

// ── 데이터 로드 (locale 체인) ─────────────────────
$fallbackChain = [$currentLocale, 'en', 'ko'];
$fallbackChain = array_values(array_unique($fallbackChain));

$entries = [];
$sql = "SELECT version, version_label, release_date, content, translation_source, is_internal
          FROM {$prefix}changelog
         WHERE is_active = 1 AND locale = :locale
         ORDER BY release_date DESC, version DESC";
$stmt = $pdo->prepare($sql);
foreach ($fallbackChain as $loc) {
    $stmt->execute([':locale' => $loc]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $entries = $rows;
        $usedLocale = $loc;
        break;
    }
}

// 내부용 섹션 필터 (is_internal=1 전체 숨김은 show_internal_sections=false 일 때만)
if (!$skinCfg['show_internal_sections']) {
    $entries = array_values(array_filter($entries, fn($e) => !$e['is_internal']));
}

// SemVer 기준 내림차순 + release_date DESC 복합 정렬
usort($entries, function ($a, $b) {
    $dateCmp = strcmp($b['release_date'], $a['release_date']);
    if ($dateCmp !== 0) return $dateCmp;
    return version_compare($b['version'], $a['version']);
});

// 최신 N개만
if ($skinCfg['max_versions'] > 0) {
    $entries = array_slice($entries, 0, $skinCfg['max_versions']);
}

// ── 버전 정보 ─────────────────────
$versionFile = BASE_PATH . '/version.json';
$versionData = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : [];
$currentVersion = $versionData['version'] ?? ($entries[0]['version'] ?? '');

// ── 헬퍼 함수 ─────────────────────
$escape = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

/** Markdown 본문(블록 내부)을 HTML 로 변환. 섹션 필터 포함. */
$renderBlockContent = function (string $md, array $skinCfg) use ($escape): string {
    $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $md));
    $html = '';
    $inList = false;
    $currentSectionPublic = true;  // 섹션 헤더 나타나기 전엔 공개로 가정

    $internalSections = \RzxLib\Core\Changelog\ChangelogParser::INTERNAL_SECTIONS;
    $sectionColors = [
        'Added' => $skinCfg['color_added'],
        'Changed' => $skinCfg['color_changed'],
        'Fixed' => $skinCfg['color_fixed'],
        'Removed' => $skinCfg['color_removed'],
        'Security' => $skinCfg['color_security'],
        'Deprecated' => $skinCfg['color_deprecated'],
    ];

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            continue;
        }

        // ### 섹션 헤더
        if (preg_match('/^### (.+?)(?:\s*[—–-]\s*(.+))?\s*$/u', $trimmed, $m)) {
            if ($inList) { $html .= "</ul>\n"; $inList = false; }
            $sectionTitle = trim($m[1]);
            $subtitle = isset($m[2]) ? trim($m[2]) : '';
            $sectionName = explode(' ', $sectionTitle)[0];

            $isInternal = in_array($sectionName, $internalSections, true);
            $currentSectionPublic = !$isInternal;

            // show_internal=false 이면 내부 섹션 전체 스킵
            if (!$currentSectionPublic && !$skinCfg['show_internal_sections']) continue;

            $color = $sectionColors[$sectionName] ?? '#71717a';
            $html .= sprintf(
                '<h3 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wider mt-5 mb-2" style="color:%s"><span class="w-1.5 h-1.5 rounded-full" style="background:%s"></span>%s%s</h3>' . "\n",
                $escape($color),
                $escape($color),
                $escape($sectionTitle),
                $subtitle ? ' <span class="text-zinc-400 font-normal normal-case tracking-normal text-xs">— ' . $escape($subtitle) . '</span>' : ''
            );
            continue;
        }

        // 내부 섹션 내용 스킵
        if (!$currentSectionPublic && !$skinCfg['show_internal_sections']) continue;

        // - 불릿 (중첩 들여쓰기 일단 최상위만)
        if (preg_match('/^(\s*)[-*]\s+(.+)$/u', $line, $m)) {
            $content = $m[2];
            // 인라인 변환
            $content = preg_replace('/\*\*(.+?)\*\*/u', '<strong class="text-zinc-800 dark:text-zinc-200">$1</strong>', $content);
            $content = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/u', '<em>$1</em>', $content);
            $content = preg_replace_callback('/`([^`]+)`/u', fn($m) => '<code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded text-xs font-mono">' . $escape($m[1]) . '</code>', $content);
            if (!$inList) { $html .= '<ul class="space-y-1.5 mb-3">' . "\n"; $inList = true; }
            $html .= '<li class="text-sm text-zinc-600 dark:text-zinc-400 flex items-start gap-2"><span class="text-zinc-300 dark:text-zinc-600 mt-1.5 select-none">•</span><span>' . $content . '</span></li>' . "\n";
            continue;
        }

        if ($inList) { $html .= "</ul>\n"; $inList = false; }

        // 인용구
        if (preg_match('/^>\s+(.+)$/u', $trimmed, $m)) {
            $html .= '<blockquote class="border-l-2 border-zinc-300 dark:border-zinc-600 pl-3 text-sm text-zinc-500 dark:text-zinc-400 italic mb-3">' . $escape($m[1]) . '</blockquote>' . "\n";
            continue;
        }

        // 일반 단락
        $p = $trimmed;
        $p = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $p);
        $p = preg_replace_callback('/`([^`]+)`/u', fn($m) => '<code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded text-xs font-mono">' . $escape($m[1]) . '</code>', $p);
        $html .= '<p class="text-sm text-zinc-600 dark:text-zinc-400 mb-2">' . $p . '</p>' . "\n";
    }
    if ($inList) $html .= "</ul>\n";
    return $html;
};

/** 날짜 포맷 */
$formatDate = function (string $isoDate, string $format, bool $showRelative) use ($currentLocale): string {
    $ts = strtotime($isoDate);
    if ($ts === false) return $isoDate;

    $main = match ($format) {
        'ko_long' => date('Y년 n월 j일', $ts),
        'en_long' => date('F j, Y', $ts),
        'dmy'     => date('d/m/Y', $ts),
        default   => date('Y-m-d', $ts),
    };

    if (!$showRelative) return $main;

    $diff = time() - $ts;
    $days = (int)floor($diff / 86400);

    $rel = match (true) {
        $days === 0  => $currentLocale === 'ko' ? '오늘' : 'today',
        $days === 1  => $currentLocale === 'ko' ? '어제' : 'yesterday',
        $days < 30   => $currentLocale === 'ko' ? "{$days}일 전" : "$days days ago",
        $days < 365  => $currentLocale === 'ko' ? round($days / 30) . '개월 전' : round($days / 30) . ' months ago',
        default      => $currentLocale === 'ko' ? round($days / 365) . '년 전' : round($days / 365) . ' years ago',
    };

    return $main . ' · ' . $rel;
};

// 버전 라벨에서 헤드라인 추출 ("VosCMS 2.2.2 — 제목" → "제목")
$extractHeadline = function (string $label): string {
    if (preg_match('/\s[—–-]\s(.+)$/u', $label, $m)) return trim($m[1]);
    return '';
};

$pageTitle = __('site.pages.changelog', [], $currentLocale) ?: '변경 이력';

// 관리자 바 — 제목 영역과 독립된 상단 고정 bar (page.php 와 동일 패턴)
// show_title=0 이어도 아이콘은 표시됨
$settingsUrl = $baseUrl . '/changelog/settings';
$editUrl     = $baseUrl . '/changelog/edit';
$adminIcons  = '';  // 제목 옆 inline 아이콘은 사용 안 함 — 상단 bar 로 대체

// 카드 스타일
$cardClass = match ($skinCfg['card_variant']) {
    'outlined' => 'border border-zinc-200 dark:border-zinc-700 rounded-xl',
    'flat'     => 'border-b border-zinc-200 dark:border-zinc-700',
    default    => 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl shadow-sm',
};
$cardPad = $skinCfg['card_variant'] === 'flat' ? 'py-6' : 'p-6';

$badgeShapeClass = match ($skinCfg['badge_shape']) {
    'rounded' => 'rounded-md',
    'square'  => 'rounded-none',
    default   => 'rounded-full',
};

// ── 레이아웃 헤더 ─────────────────────
$headExtra = '<style>';
if (!empty($skinCfg['custom_css'])) $headExtra .= $skinCfg['custom_css'];
$headExtra .= '</style>';
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/header.php';
?>

<?php
// 커스텀 헤더 HTML (스킨 설정)
if (!empty($skinCfg['custom_header_html'])) echo $skinCfg['custom_header_html'];

// 제목 영역 — 배경 이미지/비디오 있으면 _page-title-bg partial (content_width 따라감)
$_adminIcons  = '';  // 아이콘은 콘텐츠 상단 우측으로 독립 렌더
$_contentWidth = $skinCfg['content_width'];
if ($_showTitle && $_hasTitleBg):
    echo '<div class="pt-5">';  // 제목 영역 위 20px 여백
    include BASE_PATH . '/resources/views/customer/_page-title-bg.php';
    echo '</div>';
endif;
?>

<div class="<?= $escape($skinCfg['content_width']) ?> mx-auto px-4 py-10">
    <?php if ($_showTitle && !$_hasTitleBg): ?>
    <!-- 헤더 (배경 없음) -->
    <div class="text-center mb-10">
        <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 tracking-widest uppercase mb-3">Changelog</div>
        <h1 class="text-3xl md:text-4xl font-bold <?= $escape($_titleTextClass) ?> mb-3"><?= $escape($pageTitle) ?></h1>
        <?php if ($_showBreadcrumb): ?>
        <nav class="text-sm text-zinc-500 dark:text-zinc-400 mb-3">
            <a href="<?= $escape($baseUrl) ?>/" class="hover:text-blue-600"><?= __('common.home') ?? '홈' ?></a>
            <span class="mx-1">/</span>
            <span><?= $escape($pageTitle) ?></span>
        </nav>
        <?php endif; ?>
        <?php if ($currentVersion): ?>
        <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-indigo-50 dark:bg-indigo-900/20 rounded-full">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            <span class="text-sm font-medium text-indigo-700 dark:text-indigo-300">v<?= $escape($currentVersion) ?></span>
        </div>
        <?php endif; ?>
        <?php if (isset($usedLocale) && $usedLocale !== $currentLocale): ?>
        <div class="mt-3 text-xs text-zinc-400"><?= $escape($currentLocale) ?> 번역 없음 — <?= $escape($usedLocale) ?> 로 표시</div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php // 관리자 아이콘 — 제목 아래 콘텐츠 상단 우측
    if (!empty($_SESSION['admin_id'])): ?>
    <div class="flex justify-end gap-2 mb-4">
        <a href="<?= $escape($editUrl) ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-[11px] font-medium rounded-full transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <?= __('common.edit') ?? '편집' ?>
        </a>
        <a href="<?= $escape($settingsUrl) ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-zinc-600 hover:bg-zinc-500 text-white text-[11px] font-medium rounded-full transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <?= __('common.settings') ?? '설정' ?>
        </a>
    </div>
    <?php endif; ?>

    <?php if (empty($entries)): ?>
    <div class="text-center py-16 text-zinc-400 dark:text-zinc-500">
        <p>변경 이력이 아직 없습니다.</p>
    </div>
    <?php else: ?>
    <div class="space-y-6">
        <?php foreach ($entries as $entry): ?>
        <?php $headline = $extractHeadline($entry['version_label']); ?>
        <article class="<?= $escape($cardClass) ?> <?= $escape($cardPad) ?>">
            <header class="flex items-start flex-wrap gap-3 mb-4">
                <span class="inline-flex items-center px-3 py-1 text-sm font-bold <?= $escape($badgeShapeClass) ?>"
                      style="background:<?= $escape($skinCfg['badge_bg']) ?>;color:<?= $escape($skinCfg['badge_fg']) ?>">
                    v<?= $escape($entry['version']) ?>
                </span>
                <span class="text-sm text-zinc-400 dark:text-zinc-500 mt-1.5">
                    <?= $escape($formatDate($entry['release_date'], $skinCfg['date_format'], $skinCfg['show_relative_date'])) ?>
                </span>
                <?php if ($entry['translation_source'] === 'ai'): ?>
                <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400 rounded-full mt-1.5" title="AI 번역">🤖 AI</span>
                <?php endif; ?>
                <?php if ($headline): ?>
                <div class="w-full text-base text-zinc-700 dark:text-zinc-300 mt-1"><?= $escape($headline) ?></div>
                <?php endif; ?>
            </header>
            <?= $renderBlockContent($entry['content'], $skinCfg) ?>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="mt-12 text-center text-xs text-zinc-400">
        <?php if ($skinCfg['max_versions'] > 0 && count($entries) >= $skinCfg['max_versions']): ?>
            최신 <?= (int)$skinCfg['max_versions'] ?>개만 표시
        <?php endif; ?>
    </div>
</div>

<?php
// 커스텀 푸터 HTML (스킨 설정)
if (!empty($skinCfg['custom_footer_html'])) echo $skinCfg['custom_footer_html'];
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php';
?>
