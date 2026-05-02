<?php
/**
 * Grid Section Widget - render.php
 * 다중 컬럼 레이아웃 — 각 셀에 다양한 콘텐츠 배치
 *
 * 셀 타입: board-list, board-card, board-thumb, board-gallery, board-banner,
 *          text, html, image, spacer
 */

$layoutKey = $config['layout'] ?? 'sidebar-right';
$gap       = $config['gap'] ?? '4';
$bgColor   = $config['bg_color'] ?? 'transparent';
$cells     = $config['cells'] ?? [];
if (is_object($cells)) $cells = (array)$cells;
$cells = array_values($cells);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// i18n 헬퍼
$loc = function($val) use ($locale) {
    if (!$val) return '';
    if (is_string($val)) return $val;
    if (is_array($val)) return $val[$locale] ?? $val['en'] ?? $val['ko'] ?? reset($val) ?: '';
    return '';
};

// 레이아웃 → CSS grid 정의
$layoutMap = [
    '1-col'        => 'grid-cols-1',
    '2-equal'      => 'grid-cols-1 md:grid-cols-2',
    '3-equal'      => 'grid-cols-1 md:grid-cols-3',
    'sidebar-right' => 'grid-cols-1 lg:grid-cols-[1fr_340px]',
    'sidebar-left'  => 'grid-cols-1 lg:grid-cols-[340px_1fr]',
    'portal'        => 'grid-cols-1 lg:grid-cols-[1fr_2fr_1fr]',
    'wide-narrow'   => 'grid-cols-1 md:grid-cols-[3fr_2fr]',
];
$gridClass = $layoutMap[$layoutKey] ?? $layoutMap['sidebar-right'];
$gapClass  = 'gap-' . $gap;

// 배경
$bgStyle = ($bgColor && $bgColor !== 'transparent') ? 'background-color:' . htmlspecialchars($bgColor) . ';' : '';

// 데모 셀 (없을 때)
if (empty($cells)) {
    $cells = [
        ['type' => 'board-list', 'board_slug' => 'notice', 'title' => ['ko'=>'공지사항','en'=>'Notice','ja'=>'お知らせ'], 'count' => 5, 'show_more' => true],
        ['type' => 'board-list', 'board_slug' => 'faq', 'title' => ['ko'=>'자주 묻는 질문','en'=>'FAQ','ja'=>'よくある質問'], 'count' => 5, 'show_more' => true],
    ];
}

// === 셀 렌더링 함수 ===
function renderGridCell($cell, $pdo, $prefix, $baseUrl, $locale, $loc) {
    $type = $cell['type'] ?? 'text';
    $cellTitle = htmlspecialchars($loc($cell['title'] ?? ''));
    $boardSlug = $cell['board_slug'] ?? '';
    $count = max(1, (int)($cell['count'] ?? 5));
    $showMore = ($cell['show_more'] ?? 0) != 0;
    $showImage = ($cell['show_image'] ?? 1) != 0;
    $showDesc = ($cell['show_desc'] ?? 0) != 0;
    $descLen = (int)($cell['desc_length'] ?? 60);
    $columns = $cell['columns'] ?? '2';
    $moreText = $loc($cell['more_text'] ?? '') ?: (__('common.nav.more') ?? __('common.more') ?? '더보기');

    $h = '';

    // 셀 헤더 (제목 + 더보기)
    if ($cellTitle || $showMore) {
        $h .= '<div class="flex items-center justify-between mb-3">';
        $barColor = $cell['bar_color'] ?? '#3b82f6';
        if ($cellTitle) $h .= '<h3 class="text-base font-bold text-zinc-900 dark:text-white border-l-4 pl-2.5" style="border-color:' . htmlspecialchars($barColor) . '">' . $cellTitle . '</h3>';
        if ($showMore && $boardSlug) {
            $h .= '<a href="' . $baseUrl . '/' . htmlspecialchars($boardSlug) . '" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center">' . htmlspecialchars($moreText) . '<svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
        }
        $h .= '</div>';
    }

    // 게시판 데이터 로드
    $posts = [];
    if (str_starts_with($type, 'board-') && $boardSlug) {
        try {
            $bStmt = $pdo->prepare("SELECT id FROM {$prefix}boards WHERE slug = ? AND is_active = 1 LIMIT 1");
            $bStmt->execute([$boardSlug]);
            $boardId = $bStmt->fetchColumn();
            if ($boardId) {
                $pStmt = $pdo->prepare("SELECT id, nick_name, created_at, is_notice, original_locale FROM {$prefix}board_posts WHERE board_id = ? AND status = 'published' ORDER BY is_notice DESC, created_at DESC LIMIT " . (int)$count);
                $pStmt->execute([$boardId]);
                $posts = $pStmt->fetchAll(\PDO::FETCH_ASSOC);
                // title/content 는 rzx_translations 에서 일괄 로드 (통합 정책)
                if (!empty($posts) && function_exists('board_post_text_bulk_load')) {
                    $_trMap = board_post_text_bulk_load($pdo, $prefix, array_column($posts, 'id'), $locale);
                    foreach ($posts as &$_p) {
                        $_p['title']   = $_trMap[(int)$_p['id']]['title']   ?? '';
                        $_p['content'] = $_trMap[(int)$_p['id']]['content'] ?? '';
                    }
                    unset($_p);
                }

                // 이미지 추출
                foreach ($posts as &$p) {
                    $content = $p['content'] ?? '';
                    $p['_image'] = '';
                    if ($showImage && preg_match('/<img[^>]+src=["\']([^"\']+)/i', $content ?? '', $m)) {
                        $img = $m[1];
                        if (!str_starts_with($img, 'http') && !str_starts_with($img, '/')) $img = $baseUrl . '/storage/' . $img;
                        elseif (str_starts_with($img, '/')) $img = $baseUrl . $img;
                        $p['_image'] = $img;
                    }
                    $p['_desc'] = mb_strimwidth(strip_tags($content ?? ''), 0, $descLen, '...');
                }
                unset($p);
            }
        } catch (\PDOException $e) {}
    }

    // === 타입별 렌더링 ===
    switch ($type) {
        case 'board-list':
            if (empty($posts)) { $h .= '<p class="text-xs text-zinc-400 py-4 text-center">No posts</p>'; break; }
            $h .= '<ul style="border-style:none">';
            foreach ($posts as $p) {
                $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
                $h .= '<li style="border-bottom:1px dashed #e4e4e7"><a href="' . $url . '" class="flex items-center justify-between py-2 hover:text-blue-600 transition text-sm">';
                $h .= '<span class="truncate text-zinc-700 dark:text-zinc-300">';
                if ($p['is_notice']) $h .= '<span class="text-[9px] font-bold text-red-500 mr-1">N</span>';
                $h .= htmlspecialchars($p['title']) . '</span>';
                $h .= '<span class="text-[10px] text-zinc-400 flex-shrink-0 ml-2">' . date('m.d', strtotime($p['created_at'])) . '</span>';
                $h .= '</a></li>';
            }
            $h .= '</ul>';
            break;

        case 'board-card':
            if (empty($posts)) { $h .= '<p class="text-xs text-zinc-400 py-4 text-center">No posts</p>'; break; }
            $colMap = ['2'=>'grid-cols-2','3'=>'grid-cols-3','4'=>'grid-cols-2 lg:grid-cols-4'];
            $h .= '<div class="grid ' . ($colMap[$columns] ?? 'grid-cols-2') . ' gap-3">';
            foreach ($posts as $p) {
                $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
                $h .= '<a href="' . $url . '" class="group block">';
                if ($showImage && $p['_image']) {
                    $h .= '<div class="aspect-[4/3] rounded-lg overflow-hidden mb-2"><img src="' . htmlspecialchars($p['_image']) . '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"></div>';
                }
                $h .= '<p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate group-hover:text-blue-600 transition">' . htmlspecialchars($p['title']) . '</p>';
                if ($showDesc) $h .= '<p class="text-[10px] text-zinc-400 truncate mt-0.5">' . htmlspecialchars($p['_desc']) . '</p>';
                $h .= '</a>';
            }
            $h .= '</div>';
            break;

        case 'board-thumb':
            if (empty($posts)) { $h .= '<p class="text-xs text-zinc-400 py-4 text-center">No posts</p>'; break; }
            $h .= '<div class="space-y-2">';
            foreach ($posts as $p) {
                $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
                $h .= '<a href="' . $url . '" class="group flex gap-3 items-start hover:bg-zinc-50 dark:hover:bg-zinc-700/50 rounded-lg p-1.5 -m-1.5 transition">';
                if ($showImage && $p['_image']) {
                    $h .= '<img src="' . htmlspecialchars($p['_image']) . '" class="w-16 h-16 rounded-lg object-cover flex-shrink-0">';
                }
                $h .= '<div class="min-w-0"><p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 truncate group-hover:text-blue-600 transition">' . htmlspecialchars($p['title']) . '</p>';
                if ($showDesc) $h .= '<p class="text-[11px] text-zinc-400 line-clamp-2 mt-0.5">' . htmlspecialchars($p['_desc']) . '</p>';
                $h .= '</div></a>';
            }
            $h .= '</div>';
            break;

        case 'board-gallery':
            if (empty($posts)) { $h .= '<p class="text-xs text-zinc-400 py-4 text-center">No posts</p>'; break; }
            $colMap = ['2'=>'grid-cols-2','3'=>'grid-cols-3','4'=>'grid-cols-4'];
            $h .= '<div class="grid ' . ($colMap[$columns] ?? 'grid-cols-2') . ' gap-2">';
            foreach ($posts as $p) {
                $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
                $img = $p['_image'] ?: '';
                $h .= '<a href="' . $url . '" class="group relative rounded-lg overflow-hidden aspect-square bg-zinc-100 dark:bg-zinc-800">';
                if ($img) $h .= '<img src="' . htmlspecialchars($img) . '" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">';
                $h .= '<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-2"><p class="text-[11px] font-semibold text-white truncate">' . htmlspecialchars($p['title']) . '</p></div>';
                $h .= '</a>';
            }
            $h .= '</div>';
            break;

        case 'board-banner':
            if (empty($posts)) break;
            foreach ($posts as $p) {
                $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
                if ($p['_image']) {
                    $h .= '<a href="' . $url . '" class="block rounded-lg overflow-hidden mb-2 hover:shadow-md transition"><img src="' . htmlspecialchars($p['_image']) . '" alt="' . htmlspecialchars($p['title']) . '" class="w-full object-cover" style="max-height:200px"></a>';
                }
            }
            break;

        case 'image':
            $imgUrl = $cell['image'] ?? '';
            $imgLink = $cell['link'] ?? '';
            if ($imgUrl) {
                if ($imgLink) $h .= '<a href="' . htmlspecialchars($imgLink) . '" class="block">';
                $h .= '<img src="' . htmlspecialchars($imgUrl) . '" alt="" class="w-full rounded-lg">';
                if ($imgLink) $h .= '</a>';
            }
            break;

        case 'html':
            $h .= '<div class="board-content">' . ($loc($cell['content'] ?? '') ?: '') . '</div>';
            break;

        case 'text':
            $text = htmlspecialchars($loc($cell['content'] ?? ''));
            if ($text) $h .= '<div class="text-sm text-zinc-700 dark:text-zinc-300">' . nl2br($text) . '</div>';
            break;

        case 'spacer':
            $h .= '<div style="height:' . max(10, (int)($cell['height'] ?? 20)) . 'px"></div>';
            break;
    }

    return $h;
}

// === HTML 생성 ===
$html = '<section class="py-8"' . ($bgStyle ? ' style="' . $bgStyle . '"' : '') . '>';
$html .= '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';
$html .= '<div class="grid ' . $gridClass . ' ' . $gapClass . '">';

foreach ($cells as $cell) {
    $cellBg = $cell['bg_color'] ?? '';
    $cellClass = 'min-w-0';
    $cellStyle = '';
    if ($cellBg && $cellBg !== 'transparent') {
        $cellStyle = 'background-color:' . htmlspecialchars($cellBg) . ';';
        $cellClass .= ' p-4 rounded-xl';
    }
    $html .= '<div class="' . $cellClass . '"' . ($cellStyle ? ' style="' . $cellStyle . '"' : '') . '>';
    $html .= renderGridCell($cell, $pdo, $prefix, $baseUrl, $locale, $loc);
    $html .= '</div>';
}

$html .= '</div></div></section>';

return $html;
