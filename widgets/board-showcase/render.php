<?php
/**
 * Board Showcase Widget - render.php
 * 게시판 최신글을 가로 슬라이드 카드로 표시 (썸네일 + 제목 + 설명)
 */

$boardSlug = $config['board_slug'] ?? '';
$count     = max(1, min(30, (int)($config['count'] ?? 10)));
$showMore  = ($config['show_more_link'] ?? 1) != 0;
$bgColor   = $config['bg_color'] ?? 'transparent';

$sTitle   = htmlspecialchars($renderer->t($config, 'title', ''));
$moreText = __('common.nav.more') ?? __('common.more') ?? '더보기';
$_locale  = $locale ?? (function_exists('current_locale') ? current_locale() : 'ko');

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
                } catch (\PDOException $e) {
                    // 첨부파일 테이블 에러 시 무시 (본문 img 추출로 폴백)
                }

                // 다국어 번역
                if (function_exists('db_trans_batch')) {
                    $posts = db_trans_batch($pdo, $posts, $_locale, $prefix);
                }

                // 이미지 추출 + 설명 생성
                foreach ($posts as &$p) {
                    // 1순위: 첨부파일 이미지
                    $p['_image'] = $fileMap[$p['id']] ?? '';
                    // 2순위: 본문 img 태그에서 추출
                    if (!$p['_image']) {
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)/i', $p['content'] ?? '', $m)) {
                            $p['_image'] = $m[1];
                        }
                    }
                    // 경로 보정
                    if ($p['_image'] && !str_starts_with($p['_image'], 'http') && !str_starts_with($p['_image'], '/')) {
                        $p['_image'] = $baseUrl . '/storage/' . $p['_image'];
                    } elseif ($p['_image'] && str_starts_with($p['_image'], '/')) {
                        $p['_image'] = $baseUrl . $p['_image'];
                    }
                    // 설명: HTML 태그 제거 후 80자
                    $p['_desc'] = mb_strimwidth(strip_tags($p['content'] ?? ''), 0, 80, '...');
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
$uid = 'board-showcase-' . mt_rand(1000, 9999);

// 배경 스타일
$sectionStyle = '';
if ($bgColor && $bgColor !== 'transparent') {
    $sectionStyle = 'background-color:' . htmlspecialchars($bgColor) . ';';
}

// === CSS ===
$css = <<<CSS
<style>
#{$uid} .bs-scroll-container {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 4px;
}
#{$uid} .bs-scroll-container::-webkit-scrollbar { display: none; }
#{$uid} .bs-card {
    scroll-snap-align: start;
    flex: 0 0 260px;
    min-width: 260px;
    max-width: 260px;
}
@media (min-width: 640px) {
    #{$uid} .bs-card { flex: 0 0 280px; min-width: 280px; max-width: 280px; }
}
#{$uid} .bs-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.9);
    border: 1px solid #e4e4e7;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: opacity 0.2s, background 0.2s;
}
#{$uid} .bs-nav-btn:hover { background: #fff; }
.dark #{$uid} .bs-nav-btn {
    background: rgba(39,39,42,0.9);
    border-color: #52525b;
    color: #a1a1aa;
}
.dark #{$uid} .bs-nav-btn:hover { background: #3f3f46; color: #fff; }
#{$uid} .bs-nav-prev { left: -12px; }
#{$uid} .bs-nav-next { right: -12px; }
#{$uid} .bs-nav-btn.hidden { opacity: 0; pointer-events: none; }
</style>
CSS;

// === HTML ===
$html = $css;
$html .= '<section class="py-12"' . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
$html .= '<div id="' . $uid . '" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';

// 헤더
if ($sTitle || $showMore) {
    $html .= '<div class="flex items-center justify-between mb-6">';
    if ($sTitle) {
        $html .= '<h2 class="text-xl font-bold text-zinc-900 dark:text-white border-l-4 border-blue-500 pl-3">' . $sTitle . '</h2>';
    }
    if ($showMore) {
        $html .= '<a href="' . htmlspecialchars($boardUrl) . '" class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center">'
            . htmlspecialchars($moreText)
            . '<svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
    }
    $html .= '</div>';
}

// 빈 상태 메시지 (다국어)
$_emptyMessages = [
    'no_board' => [
        'ko'=>'게시판을 선택해 주세요.','en'=>'Please select a board.','ja'=>'掲示板を選択してください。',
        'zh_CN'=>'请选择版块。','zh_TW'=>'請選擇版塊。','de'=>'Bitte wählen Sie ein Board aus.',
        'es'=>'Seleccione un tablero.','fr'=>'Veuillez sélectionner un forum.',
        'id'=>'Silakan pilih papan.','mn'=>'Самбар сонгоно уу.','ru'=>'Выберите доску.',
        'tr'=>'Lütfen bir pano seçin.','vi'=>'Vui lòng chọn bảng.',
    ],
    'no_posts' => [
        'ko'=>'게시글이 없습니다.','en'=>'No posts yet.','ja'=>'投稿がありません。',
        'zh_CN'=>'暂无帖子。','zh_TW'=>'暫無帖子。','de'=>'Noch keine Beiträge.',
        'es'=>'No hay publicaciones.','fr'=>'Aucun article pour le moment.',
        'id'=>'Belum ada postingan.','mn'=>'Бичлэг байхгүй байна.','ru'=>'Записей пока нет.',
        'tr'=>'Henüz gönderi yok.','vi'=>'Chưa có bài viết nào.',
    ],
];

if (!$boardSlug) {
    $html .= '<p class="text-sm text-zinc-400 dark:text-zinc-500 py-8 text-center">' . ($_emptyMessages['no_board'][$_locale] ?? $_emptyMessages['no_board']['en']) . '</p>';
} elseif (empty($posts)) {
    $html .= '<p class="text-sm text-zinc-400 dark:text-zinc-500 py-8 text-center">' . ($_emptyMessages['no_posts'][$_locale] ?? $_emptyMessages['no_posts']['en']) . '</p>';
} else {
    // 슬라이드 컨테이너 + 네비게이션
    $html .= '<div class="relative">';
    $html .= '<button class="bs-nav-btn bs-nav-prev hidden" data-dir="prev" aria-label="Previous"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>';
    $html .= '<button class="bs-nav-btn bs-nav-next" data-dir="next" aria-label="Next"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>';
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

        // 썸네일
        $html .= '<div class="aspect-[16/10] bg-zinc-100 dark:bg-zinc-700 overflow-hidden">';
        if ($image) {
            $html .= '<img src="' . htmlspecialchars($image) . '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">';
        } else {
            // 플레이스홀더
            $html .= '<div class="w-full h-full flex items-center justify-center">';
            $html .= '<svg class="w-10 h-10 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // 카드 본문
        $html .= '<div class="p-3">';

        // 제목
        $html .= '<h3 class="text-sm font-semibold text-zinc-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">' . $title . '</h3>';

        // 설명
        if ($desc) {
            $html .= '<p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2 leading-relaxed">' . $desc . '</p>';
        }

        // 하단: 저자 + 조회수
        $html .= '<div class="flex items-center justify-between mt-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-700">';
        $html .= '<span class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate">' . $author . ' · ' . $date . '</span>';
        if ($views > 0) {
            $html .= '<span class="flex items-center text-[10px] text-zinc-400 dark:text-zinc-500 flex-shrink-0"><svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>' . number_format($views) . '</span>';
        }
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</a>';
    }

    $html .= '</div>'; // bs-scroll-container
    $html .= '</div>'; // relative
}

$html .= '</div></section>';

// === JS: 좌우 스크롤 네비게이션 ===
$html .= <<<JS
<script>
(function() {
    var root = document.getElementById('{$uid}');
    if (!root) return;
    var container = root.querySelector('.bs-scroll-container');
    var prev = root.querySelector('.bs-nav-prev');
    var next = root.querySelector('.bs-nav-next');
    if (!container || !prev || !next) return;

    function updateNav() {
        var sl = container.scrollLeft, sw = container.scrollWidth, cw = container.clientWidth;
        prev.classList.toggle('hidden', sl < 10);
        next.classList.toggle('hidden', sl + cw >= sw - 10);
    }

    prev.addEventListener('click', function() {
        container.scrollBy({ left: -300, behavior: 'smooth' });
    });
    next.addEventListener('click', function() {
        container.scrollBy({ left: 300, behavior: 'smooth' });
    });
    container.addEventListener('scroll', updateNav);
    updateNav();
    setTimeout(updateNav, 300);
})();
</script>
JS;

return $html;
