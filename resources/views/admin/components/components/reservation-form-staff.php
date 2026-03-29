<?php
/**
 * 예약 폼 - 스태프 지명/배정 섹션
 * reservation-form.php에서 include
 * 변수: $fId, $fMode, $fOld
 */
?>
<!-- 스태프 지명/배정 -->
<div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : '' ?>">
    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.pos_assign_staff') ?></h3>
    <input type="hidden" name="staff_id" id="<?= $fId ?>_staffId" value="<?= htmlspecialchars($fOld['staff_id'] ?? '') ?>">
    <input type="hidden" name="designation_fee" id="<?= $fId ?>_designationFee" value="<?= htmlspecialchars($fOld['designation_fee'] ?? '0') ?>">
    <!-- 선택된 스태프 표시 -->
    <div id="<?= $fId ?>_staffSelected" class="hidden mb-3">
        <div class="flex items-center justify-between p-3 bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800/30 rounded-lg">
            <div class="flex items-center gap-3">
                <div id="<?= $fId ?>_staffAvatar" class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p id="<?= $fId ?>_staffName" class="text-sm font-semibold text-zinc-900 dark:text-white"></p>
                    <p id="<?= $fId ?>_staffType" class="text-xs text-violet-600 dark:text-violet-400"></p>
                </div>
            </div>
            <button type="button" onclick="ResFormStaff.clear('<?= $fId ?>')" class="p-1 text-zinc-400 hover:text-red-500 rounded transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
    <!-- 버튼 2개 -->
    <div id="<?= $fId ?>_staffButtons" class="grid grid-cols-2 gap-2">
        <button type="button" onclick="ResFormStaff.open('<?= $fId ?>', 'designation')"
                class="py-2 text-sm font-medium text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-lg border border-dashed border-violet-300 dark:border-violet-700 transition flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            <?= __('reservations.pos_designation') ?>
        </button>
        <button type="button" onclick="ResFormStaff.open('<?= $fId ?>', 'assignment')"
                class="py-2 text-sm font-medium text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg border border-dashed border-emerald-300 dark:border-emerald-700 transition flex items-center justify-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <?= __('reservations.pos_assignment') ?>
        </button>
    </div>
    <!-- 스태프 리스트 (토글) -->
    <div id="<?= $fId ?>_staffList" class="hidden mt-3 space-y-2 max-h-48 overflow-y-auto"></div>
</div>
