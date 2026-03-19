<?php
/**
 * 적립금 - 회원 포인트 목록
 * points.php 에서 include
 */
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700">
    <div class="p-4 border-b dark:border-zinc-700 flex items-center justify-between flex-wrap gap-2">
        <div class="flex items-center gap-3">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('points.member_list') ?></h2>
            <span id="memberTotalBadge" class="text-xs text-zinc-400"></span>
        </div>
        <div class="flex items-center gap-2">
            <input type="text" id="memberSearch" placeholder="<?= __('points.search_member') ?>" class="px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm w-48" onkeydown="if(event.key==='Enter')loadMembers(1)">
            <button onclick="loadMembers(1)" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"><?= __('points.search') ?></button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-700/50">
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.member_name') ?></th>
                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.member_email') ?></th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.col_point') ?></th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.col_balance') ?></th>
                    <th class="px-4 py-3 text-right font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.col_accumulated') ?></th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.current_level') ?></th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('points.actions_col') ?></th>
                </tr>
            </thead>
            <tbody id="memberListBody" class="divide-y dark:divide-zinc-700">
                <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-400"><?= __('points.loading') ?>...</td></tr>
            </tbody>
        </table>
    </div>
    <!-- 페이지네이션 -->
    <div id="memberPagination" class="p-4 border-t dark:border-zinc-700 flex items-center justify-center gap-2"></div>
</div>

<!-- 포인트 수정 모달 -->
<div id="pointEditModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closePointEdit()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6">
        <h3 class="text-lg font-semibold dark:text-white mb-4"><?= __('points.edit_point') ?></h3>
        <input type="hidden" id="peUserId">
        <div class="mb-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400" id="peUserName"></p>
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('points.point_value') ?></label>
            <input type="text" id="pePointValue" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm" placeholder="100, +50, -30">
            <p class="text-xs text-zinc-400 mt-1"><?= __('points.edit_hint') ?></p>
        </div>
        <div class="flex justify-end gap-2">
            <button onclick="closePointEdit()" class="px-4 py-2 border dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300 text-sm"><?= __('points.cancel') ?></button>
            <button onclick="submitPointEdit()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"><?= __('points.save') ?></button>
        </div>
    </div>
</div>
