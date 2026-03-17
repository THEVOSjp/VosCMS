<?php
/**
 * RezlyX Admin - 게시판 설정: 기본 설정 탭
 * boards-edit.php에서 include됨 ($board, $pdo, $prefix, $adminUrl, $boardId 사용 가능)
 */
?>
<form id="boardEditForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update">

    <!-- 기본 정보 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.section_basic') ?></h3>

        <!-- URL (slug) -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_url') ?> <span class="text-red-500">*</span></label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">/board/</span>
                <input type="text" name="slug" id="slug" required
                       pattern="[a-z0-9_-]+"
                       value="<?= htmlspecialchars($board['slug'] ?? '') ?>"
                       class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
            </div>
            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.field_url_help') ?></p>
        </div>

        <!-- 브라우저 제목 -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_title') ?> <span class="text-red-500">*</span></label>
            <input type="text" name="title" id="title" required
                   value="<?= htmlspecialchars($board['title'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
        </div>

        <!-- 모듈 분류 -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_category') ?></label>
            <select name="category" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                <?php
                $cats = ['board', 'notice', 'qna', 'faq', 'gallery'];
                foreach ($cats as $c):
                ?>
                <option value="<?= $c ?>" <?= ($board['category'] ?? '') === $c ? 'selected' : '' ?>><?= __('site.boards.cat_' . $c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 설명 -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_description') ?></label>
            <textarea name="description" rows="2"
                      class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400"
                      placeholder="<?= __('site.boards.field_description_placeholder') ?>"><?= htmlspecialchars($board['description'] ?? '') ?></textarea>
        </div>

        <!-- 활성 상태 -->
        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" <?= ($board['is_active'] ?? 1) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.field_is_active') ?></span>
            </label>
        </div>
    </div>

    <!-- SEO -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.section_seo') ?></h3>

        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_robots') ?></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="radio" name="robots_tag" value="all" <?= ($board['robots_tag'] ?? 'all') === 'all' ? 'checked' : '' ?> class="text-blue-600"> <?= __('admin.common.yes') ?>
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="radio" name="robots_tag" value="noindex" <?= ($board['robots_tag'] ?? '') === 'noindex' ? 'checked' : '' ?> class="text-blue-600"> <?= __('admin.common.no') ?>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_seo_keywords') ?></label>
            <input type="text" name="seo_keywords"
                   value="<?= htmlspecialchars($board['seo_keywords'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400"
                   placeholder="keyword1, keyword2">
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_seo_desc') ?></label>
            <input type="text" name="seo_description"
                   value="<?= htmlspecialchars($board['seo_description'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
        </div>
    </div>

    <!-- 표시 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.section_display') ?></h3>

        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_per_page') ?></label>
                <input type="number" name="per_page" value="<?= (int)($board['per_page'] ?? 20) ?>" min="1" max="100"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_search_per_page') ?></label>
                <input type="number" name="search_per_page" value="<?= (int)($board['search_per_page'] ?? 20) ?>" min="1" max="100"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_page_count') ?></label>
                <input type="number" name="page_count" value="<?= (int)($board['page_count'] ?? 10) ?>" min="1" max="20"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_header') ?></label>
            <textarea name="header_content" rows="3"
                      class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                      placeholder="HTML"><?= htmlspecialchars($board['header_content'] ?? '') ?></textarea>
            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.field_header_help') ?></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_footer') ?></label>
            <textarea name="footer_content" rows="3"
                      class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                      placeholder="HTML"><?= htmlspecialchars($board['footer_content'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex items-center justify-between">
        <a href="<?= $adminUrl ?>/site/boards"
           class="px-6 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
            <?= __('admin.buttons.cancel') ?>
        </a>
        <div class="flex gap-3">
            <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden self-center"></span>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </div>
</form>

<?php include __DIR__ . '/boards-edit-js.php'; ?>
