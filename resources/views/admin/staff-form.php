<?php
/**
 * RezlyX Admin - 스태프 추가/수정 모달 폼
 */
?>

<div id="staffModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeStaffModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
            <!-- 헤더 -->
            <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-t-2xl z-10">
                <h2 id="staffModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.create') ?></h2>
                <button onclick="closeStaffModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- 폼 -->
            <form id="staffForm" class="p-6 space-y-4">
                <input type="hidden" id="staffId" name="id" value="">
                <input type="hidden" id="staffAction" name="action" value="create_staff">
                <input type="hidden" id="staffUserId" name="user_id" value="">

                <!-- 회원 연동 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.linked_member') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('staff.link_member_desc') ?></p>
                    <div class="relative">
                        <div id="linkedMemberDisplay" class="hidden items-center gap-2 px-3 py-2 mb-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            <span id="linkedMemberName" class="text-sm text-purple-800 dark:text-purple-300 flex-1"></span>
                            <button type="button" onclick="unlinkMember()" class="p-0.5 text-purple-400 hover:text-purple-600 dark:hover:text-purple-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div id="memberSearchWrap">
                            <input type="text" id="memberSearch" autocomplete="off"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm"
                                   placeholder="<?= __('staff.search_member_placeholder') ?>">
                            <div id="memberSearchResults" class="hidden absolute z-20 w-full mt-1 bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                        </div>
                    </div>
                </div>

                <!-- 이름 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.name') ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="staffName" name="name" required
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="<?= __('staff.placeholder.name') ?>">
                </div>

                <!-- 이메일 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.email') ?></label>
                    <input type="email" id="staffEmail" name="email"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="<?= __('staff.placeholder.email') ?>">
                </div>

                <!-- 전화번호 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.phone') ?></label>
                    <input type="tel" id="staffPhone" name="phone"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="<?= __('staff.placeholder.phone') ?>">
                </div>

                <!-- 카드번호 (RFID/NFC) -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.card_number') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.card_number_desc') ?></p>
                    <div class="relative">
                        <input type="text" id="staffCardNumber" name="card_number"
                               class="w-full px-3 py-2 pl-9 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm font-mono"
                               placeholder="<?= __('staff.placeholder.card_number') ?>">
                        <svg class="w-4 h-4 text-purple-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    </div>
                </div>

                <!-- 소개 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.fields.bio') ?></label>
                    <textarea id="staffBio" name="bio" rows="3"
                              class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                              placeholder="<?= __('staff.placeholder.bio') ?>"></textarea>
                </div>

                <!-- 담당 서비스 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('staff.fields.services') ?></label>
                    <div class="max-h-40 overflow-y-auto border border-zinc-200 dark:border-zinc-600 rounded-lg p-3 space-y-2">
                        <?php if (empty($allServices)): ?>
                        <p class="text-xs text-zinc-400"><?= __('staff.no_services') ?></p>
                        <?php else: ?>
                        <?php foreach ($allServices as $svc): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="service_ids[]" value="<?= htmlspecialchars($svc['id']) ?>"
                                   class="staff-svc-checkbox rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($svc['name']) ?></span>
                        </label>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 활성 상태 -->
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="staffActive" name="is_active" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('staff.fields.is_active') ?></span>
                </div>
            </form>

            <!-- 하단 버튼 -->
            <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3 rounded-b-2xl">
                <button onclick="closeStaffModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button onclick="saveStaff()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <?= __('common.buttons.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>
