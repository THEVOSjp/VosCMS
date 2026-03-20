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

    <!-- 스킨 기본정보 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.skin_info') ?></h3>
        </div>
        <?php
            $skinThumbnail = $skinMeta['thumbnail'] ?? '';
            $skinThumbnailUrl = '';
            if ($skinThumbnail) {
                $skinThumbnailUrl = $baseUrl . '/skins/' . $currentSkin . '/board/' . $skinThumbnail;
            }
        ?>
        <div class="flex">
        <div class="flex-1 divide-y divide-zinc-100 dark:divide-zinc-700">
            <!-- 스킨 -->
            <div class="flex px-6 py-3">
                <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_name') ?></span>
                <span class="text-sm text-zinc-800 dark:text-zinc-200 font-medium"><?= htmlspecialchars($skinMeta['title'] ?: $currentSkin) ?>
                    <span class="ml-2 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs rounded-full"><?= htmlspecialchars($currentSkin) ?></span>
                </span>
            </div>
            <!-- 제작자 -->
            <?php if (!empty($skinMeta['author']['name'])): ?>
            <div class="flex px-6 py-3">
                <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_author') ?></span>
                <span class="text-sm text-zinc-800 dark:text-zinc-200">
                    <?php if (!empty($skinMeta['author']['url'])): ?>
                    <a href="<?= htmlspecialchars($skinMeta['author']['url']) ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"><?= htmlspecialchars($skinMeta['author']['name']) ?></a>
                    <svg class="w-3 h-3 inline ml-0.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <span class="text-xs text-zinc-400 ml-2"><?= htmlspecialchars($skinMeta['author']['url']) ?></span>
                    <?php else: ?>
                    <?= htmlspecialchars($skinMeta['author']['name']) ?>
                    <?php endif; ?>
                    <?php if (!empty($skinMeta['author']['email'])): ?>
                    <span class="text-xs text-zinc-400 ml-2">, <?= htmlspecialchars($skinMeta['author']['email']) ?></span>
                    <?php endif; ?>
                </span>
            </div>
            <?php endif; ?>
            <!-- 날짜 -->
            <?php if (!empty($skinMeta['date'])): ?>
            <div class="flex px-6 py-3">
                <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_date') ?></span>
                <span class="text-sm text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($skinMeta['date']) ?></span>
            </div>
            <?php endif; ?>
            <!-- 버전 -->
            <?php if (!empty($skinMeta['version'])): ?>
            <div class="flex px-6 py-3">
                <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_version') ?></span>
                <span class="text-sm text-zinc-800 dark:text-zinc-200">v<?= htmlspecialchars($skinMeta['version']) ?></span>
            </div>
            <?php endif; ?>
            <!-- 설명 -->
            <?php if (!empty($skinMeta['description'])): ?>
            <div class="flex px-6 py-3">
                <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_desc') ?></span>
                <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($skinMeta['description']) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <!-- 썸네일 (오른쪽) -->
        <?php if ($skinThumbnailUrl): ?>
        <div class="w-52 shrink-0 border-l border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-700/30 flex items-center justify-center p-4">
            <img src="<?= htmlspecialchars($skinThumbnailUrl) ?>" alt="<?= htmlspecialchars($skinMeta['title'] ?? '') ?>" class="max-w-full max-h-48 rounded-lg shadow-sm object-contain" onerror="this.parentElement.innerHTML='<span class=\'text-zinc-400 text-xs\'>No preview</span>'">
        </div>
        <?php endif; ?>
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
    // FormData(multipart)로 전송 — 파일 업로드 지원
    const fd = new FormData(this);
    fd.append('action', 'update_skin_config');
    fd.append('board_id', '<?= $boardId ?>');
    fd.append('skin', '<?= $currentSkin ?>');

    try {
        const resp = await fetch('<?= $adminUrl ?>/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
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
