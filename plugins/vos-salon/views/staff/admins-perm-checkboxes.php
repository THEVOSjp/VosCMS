<?php
/**
 * 권한 체크박스 그룹 (add/edit 모달 공용)
 * $permissionGroups, $checkboxPrefix 변수 사용
 */
$pfx = $checkboxPrefix ?? 'add';
?>
<div class="space-y-3" data-perm-group="<?= $pfx ?>">
    <label class="flex items-center">
        <input type="checkbox" class="perm-all-<?= $pfx ?> rounded border-zinc-300 dark:border-zinc-600 text-blue-600 mr-2" onchange="toggleAllPerms('<?= $pfx ?>')">
        <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">전체 선택</span>
    </label>
    <?php foreach ($permissionGroups as $groupName => $perms): ?>
    <div class="pl-2 border-l-2 border-zinc-200 dark:border-zinc-600">
        <p class="text-[10px] font-medium text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars($groupName) ?></p>
        <div class="space-y-1">
            <?php foreach ($perms as $key => $label): ?>
            <label class="flex items-center">
                <input type="checkbox" value="<?= $key ?>" class="perm-cb-<?= $pfx ?> rounded border-zinc-300 dark:border-zinc-600 text-blue-600 mr-2" data-perm="<?= $key ?>">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($label) ?></span>
                <span class="text-[10px] text-zinc-400 ml-1">(<?= $key ?>)</span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
