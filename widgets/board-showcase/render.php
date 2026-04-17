<?php
/**
 * Board Showcase Widget - render.php
 * 게시판 최신글을 가로 슬라이드 카드로 표시 (썸네일 + 제목 + 설명)
 * WidgetHelpers 공통 함수 사용
 */

// 공통 초기화
$ctx = rzx_widget_init($config, 'board-showcase');
$_locale = $ctx['locale'];
$uid = $ctx['uid'];
$prefix = $ctx['prefix'];

$boardSlug = $config['board_slug'] ?? '';
$count     = max(1, min(30, (int)($config['count'] ?? 10)));
$showMore  = ($config['show_more_link'] ?? 1) != 0;

$sTitle   = htmlspecialchars($renderer->t($config, 'title', ''));

// 게시판 정보 + 글 로드
$boardInfo = null;
$posts = [];

if ($boardSlug) {
    try {
        $bStmt = $pdo->prepare("SELECT id, slug, title FROM {$prefix}boards WHERE slug = ? AND is_active = 1 LIMIT 1");
        $bStmt->execute([$boardSlug]);
        $boardInfo = $bStmt->fetch(\PDO::FETCH_ASSOC);

        if ($boardInfo) {
            $pStmt = $pdo->prepare(
                "SELECT id, title, content, nick_name, created_at, view_count
                 FROM {$prefix}board_posts
                 WHERE board_id = ? AND status = 'published'
                 ORDER BY is_notice DESC, created_at DESC
                 LIMIT " . (int)$count
            );
            $pStmt->execute([$boardInfo['id']]);
            $posts = $pStmt->fetchAll(\PDO::FETCH_ASSOC);

            // 첨부 이미지 로드
            $fileMap = [];
            if (!empty($posts)) {
                $postIds = array_column($posts, 'id');
                $ph = implode(',', array_fill(0, count($postIds), '?'));
                try {
                    $fStmt = $pdo->prepare(
                        "SELECT post_id, file_path FROM {$prefix}board_files
                         WHERE post_id IN ({$ph}) AND mime_type LIKE 'image/%'
                         ORDER BY id ASC"
                    );
                    $fStmt->execute($postIds);
                    while ($f = $fStmt->fetch(\PDO::FETCH_ASSOC)) {
                        if (!isset($fileMap[$f['post_id']])) $fileMap[$f['post_id']] = $f['file_path'];
                    }
                } catch (\PDOException $e) {}

                // 다국어 번역
                if (function_exists('db_trans_batch')) {
                    $posts = db_trans_batch($pdo, $posts, $_locale, $prefix);
                }

                // 이미지 추출 + 설명 생성
                foreach ($posts as &$p) {
                    $p['_image'] = $fileMap[$p['id']] ?? '';
                    if (!$p['_image']) {
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)/i', $p['content'] ?? '', $m)) {
                            $p['_image'] = $m[1];
                        }
                    }
                    if ($p['_image'] && !str_starts_with($p['_image'], 'http') && !str_starts_with($p['_image'], '/')) {
                        $p['_image'] = $baseUrl . '/storage/' . $p['_image'];
                    } elseif ($p['_image'] && str_starts_with($p['_image'], '/')) {
                        $p['_image'] = $baseUrl . $p['_image'];
                    }
                    $p['_desc'] = mb_strimwidth(strip_tags($p['content'] ?? ''), 0, 80, '...');
                }
                unset($p);
            }
        }
    } catch (\PDOException $e) {}
}

if (!$sTitle && $boardInfo) $sTitle = htmlspecialchars($boardInfo['title']);
$boardUrl = $baseUrl . '/' . ($boardSlug ?: 'notice');

// 슬라이드 CSS/JS (공통 헬퍼)
$slide = rzx_widget_scroll_slide($uid, 'bs', 280);

// === HTML ===
$html = $slide['css'];
$html .= $ctx['section_open'];
$html .= '<div id="' . $uid . '" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';

// 헤더 (공통 헬퍼)
$html .= rzx_widget_header($sTitle, $showMore ? $boardUrl : '');

if (!$boardSlug) {
    $html .= rzx_widget_empty('no_board', $_locale);
} elseif (empty($posts)) {
    $html .= rzx_widget_empty('no_posts', $_locale);
} else {
    $html .= '<div class="relative">';
    $html .= $slide['nav_prev'] . $slide['nav_next'];
    $html .= '<div class="bs-scroll-container">';

    foreach ($posts as $p) {
        $postUrl = $baseUrl . '/' . $boardSlug . '/' . $p['id'];
        $title   = htmlspecialchars($p['title'] ?? '');
        $desc    = htmlspecialchars($p['_desc'] ?? '');
        $image   = $p['_image'] ?? '';
        $author  = htmlspecialchars($p['nick_name'] ?? '');
        $date    = date('Y.m.d', strtotime($p['created_at']));
        $views   = (int)($p['view_count'] ?? 0);

        $html .= '<a href="' . htmlspecialchars($postUrl) . '" class="bs-card group block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition-all">';
        $html .= '<div class="aspect-[16/10] bg-zinc-100 dark:bg-zinc-700 overflow-hidden">';
        if ($image) {
            $html .= '<img src="' . htmlspecialchars($image) . '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">';
        } else {
            $html .= '<div class="w-full h-full flex items-center justify-center"><svg class="w-10 h-10 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>';
        }
        $html .= '</div>';
        $html .= '<div class="p-3">';
        $html .= '<h3 class="text-sm font-semibold text-zinc-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">' . $title . '</h3>';
        if ($desc) $html .= '<p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2 leading-relaxed">' . $desc . '</p>';
        $html .= '<div class="flex items-center justify-between mt-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-700">';
        $html .= '<span class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate">' . $author . ' · ' . $date . '</span>';
        if ($views > 0) {
            $html .= '<span class="flex items-center text-[10px] text-zinc-400 dark:text-zinc-500 flex-shrink-0"><svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>' . number_format($views) . '</span>';
        }
        $html .= '</div></div></a>';
    }

    $html .= '</div></div>';
}

$html .= '</div>' . $ctx['section_close'];
$html .= $slide['js'];

return $html;
