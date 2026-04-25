<?php
/**
 * 게시판 설정 - 레이아웃 선택 섹션 (공통 컴포넌트)
 * 기본 설정 탭의 SEO 아래, 스킨 선택 위에 배치
 */
$_b = $board ?? [];
$_collapsed = $_collapsed ?? false;
$_currentLayout = $_b['layout'] ?? 'default';
$_locale = $config['locale'] ?? 'ko';

// 사용 가능한 레이아웃 목록
//   표준 위치: /skins/layouts/{name}/layout.json
//   레거시: /skins/{name}/layouts/layout.json
$_skinsDir = BASE_PATH . '/skins';
$_availableLayouts = [];

// 표준: /skins/layouts/*/layout.json
foreach (glob($_skinsDir . '/layouts/*/layout.json') as $_ljf) {
    $_slug = basename(dirname($_ljf));
    $_lData = json_decode(file_get_contents($_ljf), true) ?: [];
    $_lTitle = $_lData['title'][$_locale] ?? $_lData['title']['en'] ?? $_lData['title']['ko'] ?? $_slug;
    $_lDesc = $_lData['description'][$_locale] ?? $_lData['description']['en'] ?? $_lData['description']['ko'] ?? '';
    $_lVer = $_lData['version'] ?? '';
    $_lThumb = !empty($_lData['thumbnail']) ? $_lData['thumbnail'] : 'thumbnail.png';
    $_availableLayouts[$_slug] = [
        'title' => $_lTitle, 'description' => $_lDesc, 'version' => $_lVer,
        'thumbnail' => '/skins/layouts/' . $_slug . '/' . $_lThumb,
        'thumbnail_file' => $_skinsDir . '/layouts/' . $_slug . '/' . $_lThumb,
    ];
}

// 레거시: /skins/*/layouts/layout.json (skin 안에 layout이 함께 있는 경우)
foreach (glob($_skinsDir . '/*/layouts/layout.json') as $_ljf) {
    $_slug = basename(dirname(dirname($_ljf)));
    if ($_slug === 'layouts') continue; // 위 표준 검색과 중복 방지
    if (isset($_availableLayouts[$_slug])) continue;
    $_lData = json_decode(file_get_contents($_ljf), true) ?: [];
    $_lTitle = $_lData['title'][$_locale] ?? $_lData['title']['en'] ?? $_lData['title']['ko'] ?? $_slug;
    $_lDesc = $_lData['description'][$_locale] ?? $_lData['description']['en'] ?? $_lData['description']['ko'] ?? '';
    $_lVer = $_lData['version'] ?? '';
    $_lThumb = !empty($_lData['thumbnail']) ? $_lData['thumbnail'] : 'thumbnail.png';
    $_availableLayouts[$_slug] = [
        'title' => $_lTitle, 'description' => $_lDesc, 'version' => $_lVer,
        'thumbnail' => '/skins/' . $_slug . '/layouts/' . $_lThumb,
        'thumbnail_file' => $_skinsDir . '/' . $_slug . '/layouts/' . $_lThumb,
    ];
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="layout-select">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.layout_select') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200 <?= $_collapsed ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
    <div class="section-body px-6 pb-6 <?= $_collapsed ? 'hidden' : '' ?>">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($_availableLayouts as $_slug => $_lMeta): ?>
            <label class="relative cursor-pointer">
                <input type="radio" name="layout" value="<?= $_slug ?>" <?= $_slug === $_currentLayout ? 'checked' : '' ?>
                       class="sr-only peer" onchange="onLayoutSelectChange(this.value)">
                <div class="p-4 rounded-xl border-2 transition
                    peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20
                    border-zinc-200 dark:border-zinc-600 hover:border-zinc-300 dark:hover:border-zinc-500">
                    <?php if (!empty($_lMeta['thumbnail_file']) && file_exists($_lMeta['thumbnail_file'])): ?>
                    <div class="h-24 bg-zinc-100 dark:bg-zinc-700 rounded-lg mb-2 overflow-hidden">
                        <img src="<?= ($config['app_url'] ?? '') . htmlspecialchars($_lMeta['thumbnail']) ?>" alt="" class="w-full h-full object-cover">
                    </div>
                    <?php else: ?>
                    <div class="h-24 bg-zinc-100 dark:bg-zinc-700 rounded-lg mb-2 flex items-center justify-center text-zinc-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                    </div>
                    <?php endif; ?>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_lMeta['title']) ?></p>
                    <?php if (!empty($_lMeta['description'])): ?>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-2"><?= htmlspecialchars($_lMeta['description']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($_lMeta['version'])): ?>
                    <span class="text-xs text-zinc-400">v<?= htmlspecialchars($_lMeta['version']) ?></span>
                    <?php endif; ?>
                </div>
            </label>
            <?php endforeach; ?>

            <?php if (empty($_availableLayouts)): ?>
            <div class="col-span-full text-center py-6 text-zinc-400">
                <p><?= __('site.boards.layout_none') ?></p>
            </div>
            <?php endif; ?>
        </div>
        <p class="text-xs text-zinc-500 mt-3"><?= __('site.boards.layout_select_help') ?></p>
    </div>
</div>

<script>
function onLayoutSelectChange(layout) {
    console.log('[BoardEdit] 레이아웃 변경:', layout);
    const form = new URLSearchParams({ action: 'update', board_id: '<?= $boardId ?? 0 ?>', layout: layout });
    fetch('<?= ($adminUrl ?? '') ?>/site/boards/api', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    }).then(() => {
        // 현재 페이지를 그대로 reload — 어드민이든 프론트엔드 settings든 같은 레이아웃 유지
        location.reload();
    });
}
</script>
