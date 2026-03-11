<?php
/**
 * RezlyX Admin - 서비스 관리 모달 폼
 * services.php에서 include
 */
?>

<!-- ═══ 서비스 추가/수정 모달 ═══ -->
<div id="serviceModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeServiceModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
            <!-- 헤더 -->
            <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-t-2xl z-10">
                <h2 id="serviceModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.services.create') ?></h2>
                <button onclick="closeServiceModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- 폼 -->
            <form id="serviceForm" class="p-6 space-y-4">
                <input type="hidden" id="svcId" name="id" value="">
                <input type="hidden" id="svcAction" name="action" value="create_service">

                <!-- 서비스명 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.services.fields.name') ?> <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" id="svcName" name="name" required
                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="<?= __('admin.services.placeholder_name') ?>">
                        <button type="button" onclick="openServiceMultilang('name')" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg" title="<?= __('admin.settings.site.multilang_title') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </button>
                    </div>
                </div>

                <!-- 슬러그 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.services.fields.slug') ?></label>
                    <input type="text" id="svcSlug" name="slug"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="<?= __('admin.services.placeholder_slug') ?>">
                </div>

                <!-- 카테고리 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.services.fields.category') ?></label>
                    <select id="svcCategory" name="category_id"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value=""><?= __('admin.services.select_none') ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars(getCategoryTranslated($cat['id'], 'name', $cat['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 가격 + 소요시간 -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.services.fields.price') ?> (<?= $currency ?>)</label>
                        <input type="number" id="svcPrice" name="price" min="0" step="100" value="0"
                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.services.fields.duration') ?></label>
                        <select id="svcDuration" name="duration"
                                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                            <?php foreach ([10, 15, 20, 30, 40, 45, 60, 90, 120, 150, 180] as $min): ?>
                            <option value="<?= $min ?>" <?= $min === 30 ? 'selected' : '' ?>><?= $min ?><?= __('admin.services.minute') ?><?= $min >= 60 ? ' (' . floor($min/60) . 'h' . ($min%60 ? $min%60 . 'm' : '') . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- 버퍼 시간 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.services.fields.buffer_time') ?></label>
                    <select id="svcBuffer" name="buffer_time"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value="0"><?= __('admin.services.no_buffer') ?></option>
                        <option value="5">5<?= __('admin.services.minute') ?></option>
                        <option value="10">10<?= __('admin.services.minute') ?></option>
                        <option value="15">15<?= __('admin.services.minute') ?></option>
                        <option value="30">30<?= __('admin.services.minute') ?></option>
                    </select>
                </div>

                <!-- 설명 -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.services.fields.description') ?></label>
                        <button type="button" onclick="openServiceMultilang('description')" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg" title="<?= __('admin.settings.site.multilang_title') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </button>
                    </div>
                    <textarea id="svcDescription" name="description" rows="3"
                              class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                              placeholder="<?= __('admin.services.placeholder_description') ?>"></textarea>
                </div>

                <!-- 활성 상태 -->
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="svcActive" name="is_active" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.services.fields.is_active') ?></span>
                </div>
            </form>

            <!-- 하단 버튼 -->
            <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3 rounded-b-2xl">
                <button onclick="closeServiceModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button onclick="saveService()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <?= __('common.buttons.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>
