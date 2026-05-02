<?php
/**
 * FAQ Accordion Skin - 목록 출력
 *
 * 상위 list.php에서 전달되는 변수:
 *   $posts, $notices, $board, $skinConfig, $boardUrl, $currentLocale,
 *   $page, $totalPages, $totalCount, $perPage, $searchKeyword, $searchTarget,
 *   $categories, $categoryFilter, $pdo, $prefix
 */

$primaryColor = $skinConfig['primary_color'] ?? '#22c55e';
$expandFirst = !empty($skinConfig['expand_first']);
$showCategoryFilter = $skinConfig['show_category_filter'] ?? true;
$contentWidth = $skinConfig['content_width'] ?? 'max-w-3xl';

// 타이틀 영역 설정 (v1.0.1)
$showTitle = !isset($skinConfig['show_title']) || $skinConfig['show_title'];

$_resolveLocale = function ($v, $loc) {
    if (is_array($v)) return $v[$loc] ?? $v['en'] ?? reset($v) ?: '';
    return (string)($v ?? '');
};
$titleText    = $_resolveLocale($skinConfig['title_text']    ?? ['ko'=>'FAQ','en'=>'FAQ','ja'=>'FAQ'], $currentLocale ?? 'ko') ?: 'FAQ';
$subtitleText = $_resolveLocale($skinConfig['subtitle_text'] ?? ['ko'=>'자주 묻는 질문','en'=>'Frequently Asked Questions','ja'=>'よくある質問'], $currentLocale ?? 'ko');

// 타이틀 배경
$titleBgType    = $skinConfig['title_bg_type']    ?? 'none';
$titleBgImage   = $skinConfig['title_bg_image']   ?? '';
$titleBgVideo   = $skinConfig['title_bg_video']   ?? '';
$titleBgHeight  = (int)($skinConfig['title_bg_height']  ?? 280);
$titleBgOverlay = (int)($skinConfig['title_bg_overlay'] ?? 40);
$hasBg = ($titleBgType === 'image' && $titleBgImage) || ($titleBgType === 'video' && $titleBgVideo);

// 통합 정책: 항상 rzx_translations 에서 가져옴. helper 사용.
$_faqTr = function(int $postId, string $field, string $original) use ($pdo, $prefix, $currentLocale) {
    if (function_exists('board_post_text')) {
        $v = board_post_text($postId, $field, $currentLocale ?? 'ko', $original);
        return $v !== '' ? $v : $original;
    }
    return $original;
};

$faqLabel = ['ko'=>'자주 묻는 질문','en'=>'Frequently Asked Questions','ja'=>'よくある質問','de'=>'Häufig gestellte Fragen','es'=>'Preguntas frecuentes','fr'=>'Questions fréquentes','id'=>'Pertanyaan Umum','mn'=>'Түгээмэл асуултууд','ru'=>'Часто задаваемые вопросы','tr'=>'Sık Sorulan Sorular','vi'=>'Câu hỏi thường gặp','zh_CN'=>'常见问题','zh_TW'=>'常見問題'];
$searchLabel = ['ko'=>'궁금한 내용을 검색해 보세요','en'=>'Search for answers...','ja'=>'気になる内容を検索','de'=>'Suchen Sie nach Antworten...','es'=>'Busca respuestas...','fr'=>'Recherchez des réponses...','id'=>'Cari jawaban...','mn'=>'Хариулт хайх...','ru'=>'Поиск ответов...','tr'=>'Cevap arayın...','vi'=>'Tìm câu trả lời...','zh_CN'=>'搜索答案...','zh_TW'=>'搜尋答案...'];
$noResultLabel = ['ko'=>'검색 결과가 없습니다.','en'=>'No results found.','ja'=>'検索結果がありません。','de'=>'Keine Ergebnisse.','es'=>'Sin resultados.','fr'=>'Aucun résultat.','id'=>'Tidak ada hasil.','mn'=>'Илэрц олдсонгүй.','ru'=>'Ничего не найдено.','tr'=>'Sonuç bulunamadı.','vi'=>'Không có kết quả.','zh_CN'=>'没有找到结果。','zh_TW'=>'沒有找到結果。'];
$_loc = $currentLocale ?? 'ko';
?>

<style>
.faq-skin { --faq-primary: <?= $primaryColor ?>; }
.faq-skin .faq-q { border-left: 4px solid var(--faq-primary); }
.faq-skin .faq-q-icon { color: var(--faq-primary); font-weight: 800; }
.faq-skin .faq-search-input { border-color: var(--faq-primary) !important; }
.faq-skin .faq-search-input:focus { box-shadow: 0 0 0 4px rgba(34,197,94,0.15); }
.faq-skin .faq-search-input::placeholder { color: #a1a1aa; }
.faq-skin .faq-page-active { background: var(--faq-primary); color: #fff; }
.faq-skin .faq-chevron { transition: transform 0.2s; }
.faq-skin .faq-item.open .faq-chevron { transform: rotate(180deg); }
.faq-skin .faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.faq-skin .faq-item.open .faq-answer { max-height: 2000px; }
</style>

<?php if ($showTitle && $hasBg): ?>
<!-- 타이틀 배경 영역 (이미지/동영상) -->
<div class="relative w-full overflow-hidden mb-8" style="height: <?= $titleBgHeight ?>px">
    <?php if ($titleBgType === 'image'): ?>
    <img src="<?= htmlspecialchars($titleBgImage) ?>" alt="" class="absolute inset-0 w-full h-full object-cover">
    <?php elseif ($titleBgType === 'video'): ?>
    <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline>
        <source src="<?= htmlspecialchars($titleBgVideo) ?>">
    </video>
    <?php endif; ?>
    <?php if ($titleBgOverlay > 0): ?>
    <div class="absolute inset-0 bg-black" style="opacity: <?= $titleBgOverlay / 100 ?>"></div>
    <?php endif; ?>
    <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4">
        <?php if ($subtitleText): ?>
        <p class="text-sm font-medium tracking-widest uppercase mb-2 text-white/90"><?= htmlspecialchars($subtitleText) ?></p>
        <?php endif; ?>
        <h1 class="text-5xl font-black text-white">
            <?= htmlspecialchars($titleText) ?>
        </h1>
    </div>
</div>
<?php endif; ?>

<div class="faq-skin <?= $contentWidth ?> mx-auto px-4 py-8">

    <?php if ($showTitle && !$hasBg): ?>
    <!-- 타이틀 (배경 없음) -->
    <div class="text-center mb-10">
        <?php if ($subtitleText): ?>
        <p class="text-sm font-medium tracking-widest uppercase mb-2" style="color: var(--faq-primary);">
            <?= htmlspecialchars($subtitleText) ?>
        </p>
        <?php endif; ?>
        <h1 class="text-5xl font-black text-zinc-900 dark:text-white mb-8">
            <?= htmlspecialchars($titleText) ?>
        </h1>
    </div>
    <?php endif; ?>

    <!-- 검색창 영역 -->
    <div class="text-center mb-4">

        <form method="GET" action="<?= $boardUrl ?>">
            <input type="hidden" name="search_target" value="title_content">
            <div class="relative max-w-xl mx-auto">
                <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5" style="color: var(--faq-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search_keyword" value="<?= htmlspecialchars($searchKeyword) ?>"
                       placeholder="<?= $searchLabel[$_loc] ?? $searchLabel['en'] ?>"
                       class="faq-search-input w-full pl-14 pr-5 py-5 text-base border-2 rounded-2xl bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 focus:outline-none transition"
                       style="border-color: var(--faq-primary);">
            </div>
        </form>
    </div>

    <?php if ($showCategoryFilter && !empty($categories)): ?>
    <!-- 카테고리 필터 -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="<?= $boardUrl ?>" class="px-3 py-1.5 text-sm rounded-lg font-medium <?= !$categoryFilter ? 'text-white' : 'text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?> transition" <?= !$categoryFilter ? 'style="background:var(--faq-primary)"' : '' ?>>
            <?= __('board.all') ?? 'All' ?>
        </a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= $boardUrl ?>?category=<?= $cat['id'] ?>" class="px-3 py-1.5 text-sm rounded-lg font-medium <?= $categoryFilter == $cat['id'] ? 'text-white' : 'text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?> transition" <?= $categoryFilter == $cat['id'] ? 'style="background:var(--faq-primary)"' : '' ?>>
            <?= htmlspecialchars($cat['name']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_id'])): ?>
    <!-- 관리자 설정 아이콘 (검색창 아래, 목록 위) -->
    <div class="flex justify-end mb-4">
        <?= rzx_admin_icons($boardUrl . '/settings', '') ?>
    </div>
    <?php endif; ?>

    <!-- FAQ 아코디언 -->
    <?php if (empty($posts) && empty($notices)): ?>
    <div class="text-center py-16 text-zinc-400 dark:text-zinc-500">
        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p><?= $noResultLabel[$_loc] ?? $noResultLabel['en'] ?></p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php
        $allPosts = array_merge($notices ?? [], $posts ?? []);
        foreach ($allPosts as $idx => $post):
            $postId = (int)$post['id'];
            $title = $_faqTr($postId, 'title', $post['title']);
            $content = $_faqTr($postId, 'content', $post['content']);
            $isOpen = ($expandFirst && $idx === 0) ? 'open' : '';
        ?>
        <div class="faq-item <?= $isOpen ?> bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden" onclick="this.classList.toggle('open')">
            <!-- Q -->
            <div class="faq-q flex items-center gap-4 px-5 py-4 cursor-pointer select-none">
                <span class="faq-q-icon text-lg font-extrabold shrink-0">Q</span>
                <h3 class="flex-1 font-semibold text-zinc-800 dark:text-zinc-200 text-[15px]"><?= htmlspecialchars($title) ?></h3>
                <?php if (!empty($_SESSION['admin_id'])): ?>
                <a href="<?= $boardUrl ?>/<?= $postId ?>/edit" onclick="event.stopPropagation()" class="shrink-0 p-1 text-zinc-300 hover:text-blue-500 dark:text-zinc-600 dark:hover:text-blue-400 transition" title="<?= __('board.edit') ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <button onclick="event.stopPropagation(); if(confirm('<?= __('board.delete_confirm') ?>')) location.href='<?= $boardUrl ?>/<?= $postId ?>?action=delete'" class="shrink-0 p-1 text-zinc-300 hover:text-red-500 dark:text-zinc-600 dark:hover:text-red-400 transition" title="<?= __('board.delete') ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                <?php endif; ?>
                <svg class="faq-chevron w-5 h-5 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
            <!-- A -->
            <div class="faq-answer">
                <div class="px-5 pb-5 pt-0">
                    <div class="flex gap-4">
                        <span class="text-lg font-extrabold text-zinc-400 dark:text-zinc-500 shrink-0">A</span>
                        <div class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed prose dark:prose-invert max-w-none">
                            <?= $content ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
    <nav class="flex justify-center mt-10 gap-1">
        <?php if ($page > 1): ?>
        <a href="<?= $boardUrl ?>?page=<?= $page - 1 ?><?= $searchKeyword ? '&search_target=' . urlencode($searchTarget) . '&search_keyword=' . urlencode($searchKeyword) : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?>"
           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 4);
        $endPage = min($totalPages, $startPage + 9);
        if ($endPage - $startPage < 9) $startPage = max(1, $endPage - 9);
        for ($p = $startPage; $p <= $endPage; $p++):
        ?>
        <a href="<?= $boardUrl ?>?page=<?= $p ?><?= $searchKeyword ? '&search_target=' . urlencode($searchTarget) . '&search_keyword=' . urlencode($searchKeyword) : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?>"
           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-medium transition <?= $p === $page ? 'faq-page-active' : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>">
            <?= $p ?>
        </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
        <a href="<?= $boardUrl ?>?page=<?= $page + 1 ?><?= $searchKeyword ? '&search_target=' . urlencode($searchTarget) . '&search_keyword=' . urlencode($searchKeyword) : '' ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?>"
           class="w-9 h-9 flex items-center justify-center rounded-lg text-sm text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_id'])): ?>
    <div class="flex justify-end mt-6">
        <a href="<?= $boardUrl ?>/write" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white rounded-lg transition" style="background: var(--faq-primary);" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?= __('board.write') ?>
        </a>
    </div>
    <?php endif; ?>
</div>
