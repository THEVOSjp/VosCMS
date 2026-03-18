<?php
/**
 * RezlyX Admin - 게시판 설정: 권한 설정 탭
 */
// 회원 등급 로드
$_gradeStmt = $pdo->prepare("SELECT slug, name, color FROM {$prefix}member_grades ORDER BY sort_order ASC");
$_gradeStmt->execute();
$_grades = $_gradeStmt->fetchAll(PDO::FETCH_ASSOC);

$permLevels = [
    'all' => __('site.boards.perm_all'),
    'member' => __('site.boards.perm_member'),
];
// 회원 등급별 추가 (등급만)
foreach ($_grades as $_g) {
    $permLevels['grade:' . $_g['slug']] = $_g['name'] . __('site.boards.perm_only');
}
$permLevels['admin_staff'] = __('site.boards.perm_admin_staff');
$permLevels['admin'] = __('site.boards.perm_admin');
$permFields = [
    'perm_list' => ['label' => __('site.boards.perm_list_label'), 'desc' => __('site.boards.perm_list_desc'), 'default' => 'all'],
    'perm_read' => ['label' => __('site.boards.perm_read_label'), 'desc' => __('site.boards.perm_read_desc'), 'default' => 'all'],
    'perm_write' => ['label' => __('site.boards.perm_write_label'), 'desc' => __('site.boards.perm_write_desc'), 'default' => 'member'],
    'perm_comment' => ['label' => __('site.boards.perm_comment_label'), 'desc' => __('site.boards.perm_comment_desc'), 'default' => 'member'],
    'perm_manage' => ['label' => __('site.boards.perm_manage_label'), 'desc' => __('site.boards.perm_manage_desc'), 'default' => 'admin'],
];
$inp = 'w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
$lbl = 'text-sm text-zinc-700 dark:text-zinc-300';
$hint = 'text-xs text-zinc-500 dark:text-zinc-400';

// 등록된 관리자 목록 로드
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
$admStmt = $pdo->prepare("SELECT ba.*, u.email, u.name, u.profile_image FROM {$prefix}board_admins ba JOIN {$prefix}users u ON ba.user_id COLLATE utf8mb4_unicode_ci = u.id COLLATE utf8mb4_unicode_ci WHERE ba.board_id = ? ORDER BY ba.created_at ASC");
$admStmt->execute([$boardId]);
$boardAdmins = $admStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<form id="boardPermForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update">

    <!-- 접근 권한 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.perm_title') ?></h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('site.boards.perm_desc') ?></p>
        <div class="space-y-4">
            <?php foreach ($permFields as $field => $info): ?>
            <div class="flex items-center justify-between py-3 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                <div>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= $info['label'] ?></p>
                    <p class="<?= $hint ?> mt-0.5"><?= $info['desc'] ?></p>
                </div>
                <select name="<?= $field ?>" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 min-w-[140px]">
                    <?php foreach ($permLevels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($board[$field] ?? $info['default']) === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 모듈 관리자 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.adv_module_admin_title') ?></h3>

        <!-- 검색 + 추가 -->
        <div class="flex gap-2 mb-3">
            <div class="flex-1">
                <?php
                $userSearchId = 'boardAdmin';
                $userSearchPlaceholder = __('site.boards.adv_module_admin_search_placeholder');
                $userSearchOnSelect = 'onBoardAdminSelected';
                include __DIR__ . '/../components/user-search.php';
                ?>
            </div>
            <button type="button" onclick="addBoardAdmin()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition self-start"><?= __('site.boards.adv_module_admin_add') ?></button>
        </div>

        <!-- 등록된 관리자 목록 -->
        <div class="mb-3">
            <div id="boardAdminListWrap" class="space-y-1">
                <?php foreach ($boardAdmins as $adm):
                    $decName = \RzxLib\Core\Helpers\Encryption::decrypt($adm['name']);
                    $displayName = $decName ?: $adm['email'];
                    $profileImg = $adm['profile_image'] ?? '';
                    if ($profileImg && !str_starts_with($profileImg, 'http')) {
                        $profileImg = ($config['app_url'] ?? '') . $profileImg;
                    }
                ?>
                <div class="board-admin-item flex items-center gap-3 p-2 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg group" data-user-id="<?= $adm['user_id'] ?>">
                    <?php if ($profileImg): ?>
                    <img src="<?= htmlspecialchars($profileImg) ?>" class="w-7 h-7 rounded-full object-cover shrink-0" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-semibold shrink-0" style="display:none"><?= mb_substr($displayName, 0, 1) ?></div>
                    <?php else: ?>
                    <div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-semibold shrink-0"><?= mb_substr($displayName, 0, 1) ?></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($displayName) ?></span>
                        <span class="text-xs text-zinc-400 ml-2"><?= htmlspecialchars($adm['email']) ?></span>
                    </div>
                    <button type="button" onclick="removeBoardAdminItem(this)" class="p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($boardAdmins)): ?>
            <p id="boardAdminEmpty" class="text-sm text-zinc-400 py-2"><?= __('site.boards.ev_empty') ?></p>
            <?php endif; ?>
        </div>
        <p class="<?= $hint ?> mb-4"><?= __('site.boards.adv_module_admin_help') ?></p>

        <!-- 관리자 권한 범위 -->
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_module_admin_scope') ?></label>
        <div class="flex gap-4">
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

        <!-- 알림 안내 -->
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <p class="text-xs text-blue-700 dark:text-blue-300"><?= __('site.boards.adv_module_admin_notify_help') ?></p>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex items-center justify-end gap-3">
        <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden"></span>
        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
    </div>
</form>

<script>
let selectedAdminUser = null;

// 공용 검색 컴포넌트에서 선택 시 콜백
function onBoardAdminSelected(user) {
    selectedAdminUser = user;
    console.log('[BoardAdmin] 사용자 선택:', user);
}

async function addBoardAdmin() {
    if (!selectedAdminUser) {
        alert('<?= __('site.boards.adv_module_admin_not_found') ?>');
        return;
    }
    console.log('[BoardAdmin] 추가:', selectedAdminUser.id);
    try {
        const resp = await fetch('<?= $adminUrl ?>/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'board_admin_add', board_id: '<?= $boardId ?>',
                user_id_direct: selectedAdminUser.id,
                identifier: selectedAdminUser.email || selectedAdminUser.name,
                perm_document: document.getElementById('adminPermDoc').checked ? 1 : 0,
                perm_comment: document.getElementById('adminPermComment').checked ? 1 : 0,
                perm_settings: document.getElementById('adminPermSettings').checked ? 1 : 0,
            })
        });
        const data = await resp.json();
        if (data.success) {
            // 목록에 추가
            const wrap = document.getElementById('boardAdminListWrap');
            const emptyEl = document.getElementById('boardAdminEmpty');
            if (emptyEl) emptyEl.remove();
            const initial = (selectedAdminUser.name || '?').charAt(0);
            const avatar = selectedAdminUser.avatar
                ? `<img src="${selectedAdminUser.avatar}" class="w-7 h-7 rounded-full object-cover shrink-0" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
                  + `<div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-semibold shrink-0" style="display:none">${initial}</div>`
                : `<div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-semibold shrink-0">${initial}</div>`;
            const div = document.createElement('div');
            div.className = 'board-admin-item flex items-center gap-3 p-2 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg group';
            div.dataset.userId = data.user_id;
            div.innerHTML = `${avatar}
                <div class="flex-1 min-w-0"><span class="text-sm font-medium text-zinc-800 dark:text-zinc-200">${data.display_name}</span><span class="text-xs text-zinc-400 ml-2">${selectedAdminUser.email || ''}</span></div>
                <button type="button" onclick="removeBoardAdminItem(this)" class="p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
            wrap.appendChild(div);
            document.getElementById('boardAdminInput').value = '';
            selectedAdminUser = null;
        } else { alert(data.message || 'Error'); }
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
}

async function removeBoardAdminItem(btn) {
    const item = btn.closest('.board-admin-item');
    const userId = item.dataset.userId;
    console.log('[BoardAdmin] 삭제:', userId);
    try {
        await fetch('<?= $adminUrl ?>/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'board_admin_delete', board_id: '<?= $boardId ?>', user_id: userId })
        });
        item.remove();
    } catch (err) { console.error(err); }
}
</script>

<?php include __DIR__ . '/boards-edit-js.php'; ?>
