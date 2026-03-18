<?php
/**
 * RezlyX Admin - 게시판 설정: 모듈 관리자 + 관리자 메일 섹션
 * boards-edit-basic-advanced.php에서 include됨
 * 사용 변수: $board, $boardId, $pdo, $prefix, $adminUrl, $inp, $lbl, $hint, $sep
 */
?>
<div class="<?= $sep ?>"></div>

<!-- 모듈 관리자 -->
<div class="mb-6">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_module_admin_title') ?></label>

    <!-- 관리자 ID 추가 -->
    <div class="flex gap-2 mb-2">
        <input type="text" id="boardAdminInput" class="flex-1 <?= $inp ?>" placeholder="user@example.com">
        <button type="button" onclick="addBoardAdmin()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('site.boards.adv_module_admin_add') ?></button>
    </div>

    <!-- 등록된 관리자 목록 -->
    <div class="flex gap-2 mb-2">
        <select id="boardAdminList" multiple size="4" class="flex-1 <?= $inp ?>">
            <?php
            if (isset($pdo) && isset($boardId)) {
                $admStmt = $pdo->prepare("SELECT ba.*, u.email, u.name, u.nick_name FROM {$prefix}board_admins ba JOIN {$prefix}users u ON ba.user_id = u.id WHERE ba.board_id = ? ORDER BY ba.created_at ASC");
                $admStmt->execute([$boardId]);
                $boardAdmins = $admStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($boardAdmins as $adm):
            ?>
            <option value="<?= $adm['user_id'] ?>"><?= htmlspecialchars($adm['nick_name'] ?: $adm['name'] ?: $adm['email']) ?></option>
            <?php endforeach; } ?>
        </select>
        <button type="button" onclick="removeBoardAdmin()" class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition self-start"><?= __('site.boards.adv_module_admin_delete') ?></button>
    </div>
    <p class="<?= $hint ?> mb-3"><?= __('site.boards.adv_module_admin_help') ?></p>

    <!-- 관리자 권한 범위 -->
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_module_admin_scope') ?></label>
    <div class="flex gap-4 ml-2">
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" id="adminPermDoc" checked class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_module_admin_perm_doc') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" id="adminPermComment" checked class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_module_admin_perm_comment') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" id="adminPermSettings" class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_module_admin_perm_settings') ?>
        </label>
    </div>
</div>

<!-- 관리자 메일 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_admin_mail') ?></label>
    <input type="text" name="admin_mail" value="<?= htmlspecialchars($board['admin_mail'] ?? '') ?>"
           class="w-full <?= $inp ?>" placeholder="admin@example.com">
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_admin_mail_help') ?></p>
</div>

<script>
async function addBoardAdmin() {
    const input = document.getElementById('boardAdminInput');
    const val = input.value.trim();
    if (!val) return;
    console.log('[BoardAdmin] 추가:', val);
    try {
        const resp = await fetch('<?= $adminUrl ?? '' ?>/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'board_admin_add',
                board_id: '<?= $boardId ?? 0 ?>',
                identifier: val,
                perm_document: document.getElementById('adminPermDoc').checked ? 1 : 0,
                perm_comment: document.getElementById('adminPermComment').checked ? 1 : 0,
                perm_settings: document.getElementById('adminPermSettings').checked ? 1 : 0,
            })
        });
        const data = await resp.json();
        console.log('[BoardAdmin] 응답:', data);
        if (data.success) {
            const opt = document.createElement('option');
            opt.value = data.user_id;
            opt.textContent = data.display_name;
            document.getElementById('boardAdminList').appendChild(opt);
            input.value = '';
        } else {
            alert(data.message || 'Error');
        }
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
}

async function removeBoardAdmin() {
    const sel = document.getElementById('boardAdminList');
    const selected = [...sel.selectedOptions];
    if (!selected.length) return;
    for (const opt of selected) {
        console.log('[BoardAdmin] 삭제:', opt.value);
        try {
            await fetch('<?= $adminUrl ?? '' ?>/site/boards/api', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'board_admin_delete', board_id: '<?= $boardId ?? 0 ?>', user_id: opt.value })
            });
            opt.remove();
        } catch (err) { console.error(err); }
    }
}

document.getElementById('boardAdminInput')?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); addBoardAdmin(); }
});
</script>
