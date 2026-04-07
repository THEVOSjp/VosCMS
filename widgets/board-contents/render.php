<?php
/**
 * Board Contents Widget - render.php
 * 게시판 글을 다양한 레이아웃으로 표시
 *
 * 스타일: list, card, thumb-list, gallery, banner
 */

$boardSlug  = $config['board_slug'] ?? '';
$style      = $config['style'] ?? 'list';
$count      = max(1, (int)($config['count'] ?? 5));
$columns    = $config['columns'] ?? '2';
$showImage  = ($config['show_image'] ?? 1) != 0;
$showDate   = ($config['show_date'] ?? 0) != 0;
$showDesc   = ($config['show_desc'] ?? 1) != 0;
$descLen    = max(20, (int)($config['desc_length'] ?? 80));
$showMore   = ($config['show_more_link'] ?? 1) != 0;
$bgColor    = $config['bg_color'] ?? 'transparent';

$sTitle   = htmlspecialchars($renderer->t($config, 'title', ''));
$moreText = $renderer->t($config, 'more_text', '') ?: (__('common.nav.more') ?? __('common.more') ?? '더보기');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 게시판 정보 + 글 로드
$boardInfo = null;
$posts = [];
if ($boardSlug) {
    try {
        $bStmt = $pdo->prepare("SELECT id, slug, title FROM {$prefix}boards WHERE slug = ? AND is_active = 1 LIMIT 1");
        $bStmt->execute([$boardSlug]);
        $boardInfo = $bStmt->fetch(\PDO::FETCH_ASSOC);

        if ($boardInfo) {
            $pStmt = $pdo->prepare("SELECT id, title, content, nick_name, created_at, view_count, is_notice FROM {$prefix}board_posts WHERE board_id = ? AND status = 'published' ORDER BY is_notice DESC, created_at DESC LIMIT " . (int)$count);
            $pStmt->execute([$boardInfo['id']]);
            $posts = $pStmt->fetchAll(\PDO::FETCH_ASSOC);

            // 첨부 이미지 로드
            if ($showImage && !empty($posts)) {
                $postIds = array_column($posts, 'id');
                $ph = implode(',', array_fill(0, count($postIds), '?'));
                $fStmt = $pdo->prepare("SELECT post_id, file_path FROM {$prefix}board_files WHERE post_id IN ({$ph}) AND file_type LIKE 'image/%' ORDER BY id ASC");
                $fStmt->execute($postIds);
                $fileMap = [];
                while ($f = $fStmt->fetch(\PDO::FETCH_ASSOC)) {
                    if (!isset($fileMap[$f['post_id']])) $fileMap[$f['post_id']] = $f['file_path'];
                }
                // content에서 첫 번째 img src 추출
                foreach ($posts as &$p) {
                    $p['_image'] = $fileMap[$p['id']] ?? '';
                    if (!$p['_image'] && $showImage) {
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)/i', $p['content'] ?? '', $m)) {
                            $p['_image'] = $m[1];
                        }
                    }
                    if ($p['_image'] && !str_starts_with($p['_image'], 'http') && !str_starts_with($p['_image'], '/')) {
                        $p['_image'] = $baseUrl . '/storage/' . $p['_image'];
                    } elseif ($p['_image'] && str_starts_with($p['_image'], '/')) {
                        $p['_image'] = $baseUrl . $p['_image'];
                    }
                    // 설명 텍스트 (HTML 태그 제거)
                    $p['_desc'] = mb_strimwidth(strip_tags($p['content'] ?? ''), 0, $descLen, '...');
                }
                unset($p);
            }
        }
    } catch (\PDOException $e) {
        // 에러 무시
    }
}

// 제목이 없으면 게시판 이름 사용
if (!$sTitle && $boardInfo) $sTitle = htmlspecialchars($boardInfo['title']);

$boardUrl = $baseUrl . '/' . ($boardSlug ?: 'notice');

// 배경 스타일
$sectionStyle = '';
$sectionClass = 'py-12';
if ($bgColor && $bgColor !== 'transparent') {
    $sectionStyle = 'background-color:' . htmlspecialchars($bgColor) . ';';
}

// 컬럼 클래스
$colMap = ['2' => 'md:grid-cols-2', '3' => 'md:grid-cols-3', '4' => 'md:grid-cols-2 lg:grid-cols-4'];
$gridCls = $colMap[$columns] ?? $colMap['2'];

// === 렌더링 ===
$html = '<section class="' . $sectionClass . '"' . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
$html .= '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';

// 헤더
if ($sTitle || $showMore) {
    $html .= '<div class="flex items-center justify-between mb-6">';
    if ($sTitle) {
        $html .= '<h2 class="text-xl font-bold text-zinc-900 dark:text-white border-l-4 border-blue-500 pl-3">' . $sTitle . '</h2>';
    }
    if ($showMore) {
        $html .= '<a href="' . htmlspecialchars($boardUrl) . '" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center">' . htmlspecialchars($moreText) . '<svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
    }
    $html .= '</div>';
}

if (empty($posts)) {
    $html .= '<p class="text-sm text-zinc-400 dark:text-zinc-500 py-8 text-center">' . (__('common.no_posts') ?? 'No posts yet.') . '</p>';
} else {
    // ─── List 스타일 (제목만) ───
    if ($style === 'list') {
        $html .= '<ul class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">';
        foreach ($posts as $p) {
            $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
            $html .= '<li style="border-bottom:1px dashed #e4e4e7">';
            $html .= '<a href="' . $url . '" class="flex items-center justify-between px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">';
            $html .= '<div class="flex items-center gap-2 min-w-0">';
            if ($p['is_notice']) $html .= '<span class="text-[10px] font-bold text-red-500 bg-red-50 dark:bg-red-900/30 px-1.5 py-0.5 rounded flex-shrink-0">N</span>';
            $html .= '<span class="text-sm text-zinc-800 dark:text-zinc-200 truncate">' . htmlspecialchars($p['title']) . '</span>';
            $html .= '</div>';
            if ($showDate) $html .= '<span class="text-xs text-zinc-400 flex-shrink-0 ml-3">' . date('Y.m.d', strtotime($p['created_at'])) . '</span>';
            $html .= '</a></li>';
        }
        $html .= '</ul>';
    }

    // ─── Card 스타일 (이미지 + 제목) ───
    elseif ($style === 'card') {
        $html .= '<div class="grid grid-cols-1 ' . $gridCls . ' gap-4">';
        foreach ($posts as $p) {
            $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
            $html .= '<a href="' . $url . '" class="group bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-all">';
            if ($showImage && !empty($p['_image'])) {
                $html .= '<div class="aspect-video overflow-hidden"><img src="' . htmlspecialchars($p['_image']) . '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"></div>';
            }
            $html .= '<div class="p-4">';
            $html .= '<h3 class="font-semibold text-sm text-zinc-900 dark:text-white truncate group-hover:text-blue-600 transition">' . htmlspecialchars($p['title']) . '</h3>';
            if ($showDesc && !empty($p['_desc'])) {
                $html .= '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">' . htmlspecialchars($p['_desc']) . '</p>';
            }
            if ($showDate) $html .= '<p class="text-[10px] text-zinc-400 mt-2">' . date('Y.m.d', strtotime($p['created_at'])) . '</p>';
            $html .= '</div></a>';
        }
        $html .= '</div>';
    }

    // ─── Thumbnail List 스타일 (이미지 + 제목 + 설명) ───
    elseif ($style === 'thumb-list') {
        $html .= '<div class="space-y-3">';
        foreach ($posts as $p) {
            $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
            $html .= '<a href="' . $url . '" class="group flex gap-4 items-start bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-3 hover:shadow-md transition">';
            if ($showImage && !empty($p['_image'])) {
                $html .= '<img src="' . htmlspecialchars($p['_image']) . '" alt="" class="w-20 h-20 rounded-lg object-cover flex-shrink-0">';
            }
            $html .= '<div class="min-w-0 flex-1">';
            $html .= '<h3 class="font-semibold text-sm text-zinc-900 dark:text-white truncate group-hover:text-blue-600 transition">' . htmlspecialchars($p['title']) . '</h3>';
            if ($showDesc && !empty($p['_desc'])) {
                $html .= '<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2">' . htmlspecialchars($p['_desc']) . '</p>';
            }
            if ($showDate) $html .= '<p class="text-[10px] text-zinc-400 mt-1">' . date('Y.m.d', strtotime($p['created_at'])) . '</p>';
            $html .= '</div></a>';
        }
        $html .= '</div>';
    }

    // ─── Gallery 스타일 (이미지 그리드) ───
    elseif ($style === 'gallery') {
        $html .= '<div class="grid grid-cols-2 ' . $gridCls . ' gap-3">';
        foreach ($posts as $p) {
            $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
            $img = !empty($p['_image']) ? $p['_image'] : '';
            $html .= '<a href="' . $url . '" class="group relative rounded-xl overflow-hidden aspect-square bg-zinc-100 dark:bg-zinc-800">';
            if ($img) {
                $html .= '<img src="' . htmlspecialchars($img) . '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">';
            }
            $html .= '<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-3">';
            $html .= '<p class="text-sm font-semibold text-white truncate">' . htmlspecialchars($p['title']) . '</p>';
            if ($showDesc && !empty($p['_desc'])) {
                $html .= '<p class="text-[11px] text-white/70 truncate mt-0.5">' . htmlspecialchars($p['_desc']) . '</p>';
            }
            $html .= '</div></a>';
        }
        $html .= '</div>';
    }

    // ─── Banner 스타일 (전체폭 이미지) ───
    elseif ($style === 'banner') {
        $html .= '<div class="space-y-4">';
        foreach ($posts as $p) {
            $url = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
            $img = !empty($p['_image']) ? $p['_image'] : '';
            if ($img) {
                $html .= '<a href="' . $url . '" class="block rounded-xl overflow-hidden hover:shadow-lg transition"><img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($p['title']) . '" class="w-full object-cover" style="max-height:300px"></a>';
            } else {
                $html .= '<a href="' . $url . '" class="block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 hover:shadow-md transition">';
                $html .= '<h3 class="font-bold text-lg text-zinc-900 dark:text-white">' . htmlspecialchars($p['title']) . '</h3>';
                if ($showDesc && !empty($p['_desc'])) $html .= '<p class="text-sm text-zinc-500 mt-2">' . htmlspecialchars($p['_desc']) . '</p>';
                $html .= '</a>';
            }
        }
        $html .= '</div>';
    }
}

$html .= '</div></section>';

return $html;
