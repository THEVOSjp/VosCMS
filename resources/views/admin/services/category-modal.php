<?php
/**
 * 카테고리 추가/수정 모달
 * settings-categories.php에서 include
 */
?>
<div id="categoryModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCategoryModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto relative">
            <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between rounded-t-2xl z-10">
                <h2 id="catModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.categories.create') ?></h2>
                <button onclick="closeCategoryModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form id="categoryForm" class="p-6 space-y-4">
                <input type="hidden" id="catId" name="id" value="">

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.categories.fields.name') ?> <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" id="catName" name="name" required
                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                               placeholder="<?= __('admin.categories.placeholder_name') ?>">
                        <button type="button" onclick="openCategoryMultilang('name')" class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg" title="<?= __('admin.settings.site.multilang_title') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.categories.fields.slug') ?></label>
                    <input type="text" id="catSlug" name="slug"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                           placeholder="<?= __('admin.categories.placeholder_slug') ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.categories.fields.parent') ?></label>
                    <select id="catParentId" name="parent_id"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <option value=""><?= __('admin.categories.parent_none') ?></option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars(getCategoryTranslated($cat['id'], 'name', $cat['name'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.categories.fields.description') ?></label>
                        <button type="button" onclick="openCategoryMultilang('description')" class="p-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg" title="<?= __('admin.settings.site.multilang_title') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                        </button>
                    </div>
                    <textarea id="catDescription" name="description" rows="2"
                              class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm resize-none"
                              placeholder="<?= __('admin.categories.placeholder_description') ?>"></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="catIsActive" name="is_active" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                    </label>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.categories.fields.is_active') ?></span>
                </div>
            </form>

            <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-end gap-3 rounded-b-2xl">
                <button onclick="closeCategoryModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= __('common.buttons.cancel') ?>
                </button>
                <button onclick="saveCategory()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                    <?= __('common.buttons.save') ?>
                </button>
            </div>
        </div>
    </div>
</div>
