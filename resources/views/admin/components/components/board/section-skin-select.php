<?php
/**
 * 게시판 설정 - 스킨 선택 섹션 (공통 컴포넌트)
 * 기본 설정 탭의 SEO 아래에 배치
 */
require_once BASE_PATH . '/rzxlib/Core/Skin/SkinConfigRenderer.php';
use RzxLib\Core\Skin\SkinConfigRenderer;

$_b = $board ?? [];
$_collapsed = $_collapsed ?? false;
$_currentSkin = $_b['skin'] ?? 'default';
$_locale = $config['locale'] ?? 'ko';

// 사용 가능한 스킨 목록
$_skinsDir = BASE_PATH . '/skins';
$_availableSkins = [];
foreach (glob($_skinsDir . '/*/board/skin.json') as $_sjf) {
    $_slug = basename(dirname(dirname($_sjf)));
    $_r = new SkinConfigRenderer($_sjf, [], $_locale);
    $_availableSkins[$_slug] = $_r->getMeta();
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="skin-select">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.skin_select') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200 <?= $_collapsed ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
    <div class="section-body px-6 pb-6 <?= $_collapsed ? 'hidden' : '' ?>">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($_availableSkins as $_slug => $_meta): ?>
            <label class="relative cursor-pointer">
                <input type="radio" name="skin" value="<?= $_slug ?>" <?= $_slug === $_currentSkin ? 'checked' : '' ?>
                       class="sr-only peer" onchange="onSkinSelectChange(this.value)">
                <div class="p-4 rounded-xl border-2 transition
                    peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20
                    border-zinc-200 dark:border-zinc-600 hover:border-zinc-300 dark:hover:border-zinc-500">
                    <div class="h-16 bg-zinc-100 dark:bg-zinc-700 rounded-lg mb-2 flex items-center justify-center text-zinc-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                    </div>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_meta['title']) ?></p>
                    <?php if ($_meta['version']): ?>
                    <span class="text-xs text-zinc-400">v<?= htmlspecialchars($_meta['version']) ?></span>
                    <?php endif; ?>
                </div>
            </label>
            <?php endforeach; ?>

            <?php if (empty($_availableSkins)): ?>
            <div class="col-span-full text-center py-6 text-zinc-400">
                <p><?= __('site.boards.skin_none') ?></p>
            </div>
            <?php endif; ?>
        </div>
        <p class="text-xs text-zinc-500 mt-3"><?= __('site.boards.skin_select_help') ?></p>
    </div>
</div>

<script>
function onSkinSelectChange(skin) {
    console.log('[BoardEdit] 스킨 변경:', skin);
    const form = new URLSearchParams({ action: 'update', board_id: '<?= $boardId ?? 0 ?>', skin: skin });
    fetch('<?= ($adminUrl ?? '') ?>/site/boards/api', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: form
    }).then(() => {
        location.href = '<?= ($adminUrl ?? '') ?>/site/boards/edit?id=<?= $boardId ?? 0 ?>&tab=basic';
    });
}
</script>
