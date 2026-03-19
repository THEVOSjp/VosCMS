<?php
/**
 * RezlyX Admin - 회원 그룹 추가/수정 모달 폼
 */
?>

<div id="gradeModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeGradeModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
            <!-- 헤더 -->
            <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-t-2xl z-10">
                <h2 id="gradeModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('members.groups.create') ?></h2>
                <button onclick="closeGradeModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- 폼 -->
            <form id="gradeForm" class="p-6 space-y-4">
                <input type="hidden" id="gradeId" name="id" value="">
                <input type="hidden" id="gradeAction" name="action" value="create_grade">

                <!-- 이름 + 색상 -->
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.name') ?> <span class="text-red-500">*</span></label>
                        <?php rzx_multilang_input('gradeName', '', 'member_grade.new.name', [
                            'required' => true,
                            'placeholder' => __('members.groups.placeholder.name'),
                        ]); ?>
                    </div>
                    <div class="w-20">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.color') ?></label>
                        <input type="color" id="gradeColor" name="color" value="#6B7280"
                               class="w-full h-[38px] border border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer">
                    </div>
                </div>

                <!-- 슬러그 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.slug') ?></label>
                    <input type="text" id="gradeSlug" name="slug"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm"
                           placeholder="<?= __('members.groups.placeholder.slug') ?>">
                    <p class="text-xs text-zinc-400 mt-1"><?= __('members.groups.slug_desc') ?></p>
                </div>

                <!-- 할인율 / 포인트율 -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.discount_rate') ?> (%)</label>
                        <input type="number" id="gradeDiscount" name="discount_rate" step="0.1" min="0" max="100" value="0"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.point_rate') ?> (%)</label>
                        <input type="number" id="gradePoint" name="point_rate" step="0.1" min="0" max="100" value="0"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <!-- 승급 조건 -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.min_reservations') ?></label>
                        <input type="number" id="gradeMinRes" name="min_reservations" min="0" value="0"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.min_spent') ?></label>
                        <input type="number" id="gradeMinSpent" name="min_spent" step="1" min="0" value="0"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <!-- 혜택 설명 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('members.groups.fields.benefits') ?></label>
                    <?php rzx_multilang_input('gradeBenefits', '', 'member_grade.new.benefits', [
                        'type' => 'textarea',
                        'rows' => 3,
                        'placeholder' => __('members.groups.placeholder.benefits'),
                        'modal_type' => 'editor',
                    ]); ?>
                </div>
            </form>

            <!-- 하단 버튼 -->
            <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3 rounded-b-2xl">
                <button onclick="closeGradeModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button onclick="saveGrade()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <?= __('common.buttons.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>
