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

// 위젯 자체 번역
$_wtLang = @include(__DIR__ . '/lang/' . $locale . '.php');
if (!is_array($_wtLang)) $_wtLang = @include(__DIR__ . '/lang/ko.php') ?: [];
$_wt = function($key) use ($_wtLang) { return $_wtLang[$key] ?? $key; };

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
if (!function_exists('renderGridCell')) {
function renderGridCell($cell, $pdo, $prefix, $baseUrl, $locale, $loc, $_wt) {
    $type = $cell['type'] ?? 'text';
    $cellTitle = htmlspecialchars($loc($cell['title'] ?? ''));
    $boardSlug = $cell['board_slug'] ?? '';
    $count = max(1, (int)($cell['count'] ?? 5));
    $showMoreDefault = in_array($type, ['new-shops', 'events', 'qa']) ? 1 : 0;
    $showMore = ($cell['show_more'] ?? $showMoreDefault) != 0;
    $showImage = ($cell['show_image'] ?? 1) != 0;
    $showDesc = ($cell['show_desc'] ?? 0) != 0;
    $descLen = (int)($cell['desc_length'] ?? 60);
    $columns = $cell['columns'] ?? '2';
    $moreText = $loc($cell['more_text'] ?? '') ?: $_wt('more');

    $h = '';

    // 셀 헤더 (제목 + 더보기)
    if ($cellTitle || $showMore) {
        $h .= '<div class="flex items-center justify-between mb-3">';
        $barColor = $cell['bar_color'] ?? '#3b82f6';
        if ($cellTitle) $h .= '<h3 class="text-base font-bold text-zinc-900 dark:text-white border-l-4 pl-2.5" style="border-color:' . htmlspecialchars($barColor) . '">' . $cellTitle . '</h3>';
        $moreUrl = '';
        if ($boardSlug) {
            $moreUrl = $baseUrl . '/' . htmlspecialchars($boardSlug);
        } elseif ($type === 'new-shops' || $type === 'events' || $type === 'qa') {
            $moreUrl = $baseUrl . '/shops';
        }
        if ($showMore && $moreUrl) {
            $h .= '<a href="' . $moreUrl . '" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center">' . htmlspecialchars($moreText) . '<svg class="w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
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
                $pStmt = $pdo->prepare("SELECT id, title, content, nick_name, created_at, is_notice FROM {$prefix}board_posts WHERE board_id = ? AND status = 'published' ORDER BY is_notice DESC, created_at DESC LIMIT " . (int)$count);
                $pStmt->execute([$boardId]);
                $posts = $pStmt->fetchAll(\PDO::FETCH_ASSOC);
                // 이미지 추출
                foreach ($posts as &$p) {
                    $p['_image'] = '';
                    if ($showImage && preg_match('/<img[^>]+src=["\']([^"\']+)/i', $p['content'] ?? '', $m)) {
                        $img = $m[1];
                        if (!str_starts_with($img, 'http') && !str_starts_with($img, '/')) $img = $baseUrl . '/storage/' . $img;
                        elseif (str_starts_with($img, '/')) $img = $baseUrl . $img;
                        $p['_image'] = $img;
                    }
                    $p['_desc'] = mb_strimwidth(strip_tags($p['content'] ?? ''), 0, $descLen, '...');
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

        case 'new-shops':
            // 신착 매장
            try {
                $nsStmt = $pdo->query("SELECT s.name, s.slug, s.cover_image, s.images, s.address, s.created_at FROM {$prefix}shops s WHERE s.status = 'active' ORDER BY s.created_at DESC LIMIT {$count}");
                $nsItems = $nsStmt->fetchAll(\PDO::FETCH_ASSOC);
                if (empty($nsItems)) {
                    $h .= '<p class="text-sm text-zinc-400 py-4 text-center">' . $_wt('no_shops') . '</p>';
                } else {
                    $h .= '<div class="space-y-2">';
                    foreach ($nsItems as $ns) {
                        $nsImg = $ns['cover_image'] ?: '';
                        if (!$nsImg) { $nsImgs = json_decode($ns['images'] ?? '[]', true) ?: []; $nsImg = $nsImgs[0] ?? ''; }
                        $h .= '<a href="' . $baseUrl . '/shop/' . htmlspecialchars($ns['slug']) . '" class="flex items-center gap-3 p-2 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">';
                        $h .= '<div class="w-12 h-12 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 flex-shrink-0">';
                        if ($nsImg) { $h .= '<img src="' . $baseUrl . '/' . ltrim($nsImg, '/') . '" class="w-full h-full object-cover">'; }
                        else { $h .= '<div class="w-full h-full flex items-center justify-center text-zinc-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg></div>'; }
                        $h .= '</div>';
                        $h .= '<div class="flex-1 min-w-0"><p class="text-sm font-medium text-zinc-900 dark:text-white truncate">' . htmlspecialchars($ns['name']) . '</p>';
                        $h .= '<p class="text-[11px] text-zinc-400 truncate">' . htmlspecialchars($ns['address'] ?? '') . '</p>';
                        $h .= '<p class="text-[10px] text-zinc-400">' . date('Y.m.d', strtotime($ns['created_at'])) . '</p></div></a>';
                    }
                    $h .= '</div>';
                }
            } catch (\Throwable $e) { $h .= '<p class="text-sm text-zinc-400 py-4 text-center">-</p>'; }
            break;

        case 'events':
            // 이벤트/프로모션
            try {
                $evStmt = $pdo->query("SELECT e.*, s.name as shop_name, s.slug as shop_slug FROM {$prefix}shop_events e JOIN {$prefix}shops s ON e.shop_id = s.id WHERE e.is_active = 1 AND (e.end_date IS NULL OR e.end_date > NOW()) ORDER BY e.start_date DESC LIMIT {$count}");
                $evItems = $evStmt->fetchAll(\PDO::FETCH_ASSOC);
                if (empty($evItems)) {
                    $h .= '<p class="text-sm text-zinc-400 py-4 text-center">' . $_wt('no_events') . '</p>';
                } else {
                    $h .= '<div class="space-y-2">';
                    foreach ($evItems as $ev) {
                        $h .= '<a href="' . $baseUrl . '/shop/' . htmlspecialchars($ev['shop_slug']) . '" class="block p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">';
                        $h .= '<p class="text-sm font-semibold text-amber-800 dark:text-amber-300">' . htmlspecialchars($ev['title']) . '</p>';
                        $h .= '<p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">' . htmlspecialchars($ev['shop_name']);
                        if ($ev['discount_info']) $h .= ' · ' . htmlspecialchars($ev['discount_info']);
                        $h .= '</p>';
                        if ($ev['end_date']) $h .= '<p class="text-[10px] text-amber-500 mt-0.5">~' . date('Y.m.d', strtotime($ev['end_date'])) . '</p>';
                        $h .= '</a>';
                    }
                    $h .= '</div>';
                }
            } catch (\Throwable $e) { $h .= '<p class="text-sm text-zinc-400 py-4 text-center">' . $_wt('no_events') . '</p>'; }
            break;

        case 'qa':
            // 공개 Q&A
            try {
                $qaStmt = $pdo->query("SELECT i.question, i.answer, i.created_at, s.name as shop_name, s.slug as shop_slug FROM {$prefix}shop_inquiries i JOIN {$prefix}shops s ON i.shop_id = s.id WHERE i.is_public = 1 AND i.status = 'answered' ORDER BY i.created_at DESC LIMIT {$count}");
                $qaItems = $qaStmt->fetchAll(\PDO::FETCH_ASSOC);
                if (empty($qaItems)) {
                    $h .= '<p class="text-sm text-zinc-400 py-4 text-center">' . $_wt('no_qa') . '</p>';
                } else {
                    $h .= '<div class="space-y-3">';
                    foreach ($qaItems as $qa) {
                        $h .= '<div class="border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-3 last:border-0 last:pb-0">';
                        $h .= '<div class="flex items-start gap-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 text-[10px] font-bold flex-shrink-0 mt-0.5">Q</span>';
                        $h .= '<p class="text-sm text-zinc-800 dark:text-zinc-200 line-clamp-2">' . htmlspecialchars(mb_substr($qa['question'], 0, 80)) . '</p></div>';
                        if ($qa['answer']) {
                            $h .= '<div class="flex items-start gap-2 mt-1.5 ml-7"><span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 text-[10px] font-bold flex-shrink-0 mt-0.5">A</span>';
                            $h .= '<p class="text-sm text-zinc-600 dark:text-zinc-400 line-clamp-2">' . htmlspecialchars(mb_substr($qa['answer'], 0, 80)) . '</p></div>';
                        }
                        $h .= '<p class="text-[10px] text-zinc-400 mt-1 ml-7">' . htmlspecialchars($qa['shop_name']) . ' · ' . date('Y.m.d', strtotime($qa['created_at'])) . '</p>';
                        $h .= '</div>';
                    }
                    $h .= '</div>';
                }
            } catch (\Throwable $e) { $h .= '<p class="text-sm text-zinc-400 py-4 text-center">' . $_wt('no_qa') . '</p>'; }
            break;
    }

    return $h;
}
} // end if !function_exists

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
    $html .= renderGridCell($cell, $pdo, $prefix, $baseUrl, $locale, $loc, $_wt);
    $html .= '</div>';
}

$html .= '</div></div></section>';

return $html;
