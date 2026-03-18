<?php
/**
 * RezlyX Admin - 게시판 설정: 스킨 탭
 * 선택된 스킨의 정보 표시 + skin.json 기반 동적 설정 폼
 */
require_once BASE_PATH . '/rzxlib/Core/Skin/SkinConfigRenderer.php';
use RzxLib\Core\Skin\SkinConfigRenderer;

$currentSkin = $board['skin'] ?? 'default';
$savedConfig = json_decode($board['skin_config'] ?? '{}', true) ?: [];
$locale = $config['locale'] ?? 'ko';

$skinsDir = BASE_PATH . '/skins';
$currentSkinJson = $skinsDir . '/' . $currentSkin . '/board/skin.json';
$skinRenderer = new SkinConfigRenderer($currentSkinJson, $savedConfig, $locale);
$skinMeta = $skinRenderer->getMeta();
?>
<form id="boardSkinForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update_skin">

    <!-- 스킨 정보 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-start gap-4">
            <div class="w-20 h-20 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center text-zinc-400 shrink-0">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($skinMeta['title'] ?: $currentSkin) ?></h3>
                <?php if ($skinMeta['description']): ?>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($skinMeta['description']) ?></p>
                <?php endif; ?>
                <div class="flex items-center gap-4 mt-2 text-xs text-zinc-400">
                    <?php if ($skinMeta['version']): ?>
                    <span>v<?= htmlspecialchars($skinMeta['version']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($skinMeta['author']['name'])): ?>
                    <span><?= htmlspecialchars($skinMeta['author']['name']) ?></span>
                    <?php endif; ?>
                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full"><?= htmlspecialchars($currentSkin) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 스킨 설정 (skin.json vars) -->
    <?php if ($skinRenderer->hasVars()): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.skin_settings') ?></h3>
        <div id="skinConfigForm">
            <?php $skinRenderer->renderForm(); ?>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex items-center justify-end gap-3">
        <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden"></span>
        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 text-center">
        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('site.boards.skin_no_settings') ?></p>
    </div>
    <?php endif; ?>
</form>

<script>
console.log('[BoardSkin] 스킨 탭 로드됨, skin:', '<?= $currentSkin ?>');

document.getElementById('boardSkinForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const skinConfig = {};
    for (const [key, value] of formData.entries()) {
        const match = key.match(/^skin_config\[(.+)\]$/);
        if (match) skinConfig[match[1]] = value;
    }

    const params = new URLSearchParams({
        action: 'update',
        board_id: '<?= $boardId ?>',
        skin: '<?= $currentSkin ?>',
        skin_config: JSON.stringify(skinConfig)
    });

    try {
        const resp = await fetch('<?= $adminUrl ?>/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: params
        });
        const data = await resp.json();
        const status = document.getElementById('saveStatus');
        status.textContent = data.success ? '<?= __('admin.common.saved') ?>' : (data.message || 'Error');
        status.classList.remove('hidden');
        setTimeout(() => status.classList.add('hidden'), 3000);
    } catch (err) {
        console.error('[BoardSkin] 에러:', err);
        alert('Error: ' + err.message);
    }
});
</script>
