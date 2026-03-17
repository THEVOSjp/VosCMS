<?php
/**
 * RezlyX Admin - 게시판 생성
 */
$pageTitle = __('site.boards.create_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include __DIR__ . '/../partials/admin-sidebar.php'; ?>
        <main class="flex-1 ml-64">
            <?php
            $pageHeaderTitle = __('site.boards.create_title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>
            <div class="p-6">
                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M12 4v16m8-8H4';
                $headerTitle = __('site.boards.create_title');
                $headerDescription = __('site.boards.create_desc');
                $headerIconColor = '';
                $headerActions = '';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- Form -->
                <form id="boardCreateForm" class="space-y-6">
                    <!-- 기본 정보 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.section_basic') ?></h3>

                        <!-- URL -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_url') ?> <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">/board/</span>
                                <input type="text" name="slug" id="slug" required
                                       pattern="[a-z0-9_-]+"
                                       placeholder="notice"
                                       class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
                            </div>
                            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.field_url_help') ?></p>
                        </div>

                        <!-- 브라우저 제목 -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_title') ?> <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" required
                                   placeholder="<?= __('site.boards.field_title_placeholder') ?>"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
                        </div>

                        <!-- 모듈 분류 -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_category') ?></label>
                            <select name="category" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                                <option value="board"><?= __('site.boards.cat_board') ?></option>
                                <option value="notice"><?= __('site.boards.cat_notice') ?></option>
                                <option value="qna"><?= __('site.boards.cat_qna') ?></option>
                                <option value="faq"><?= __('site.boards.cat_faq') ?></option>
                                <option value="gallery"><?= __('site.boards.cat_gallery') ?></option>
                            </select>
                        </div>

                        <!-- 설명 -->
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_description') ?></label>
                            <textarea name="description" rows="2"
                                      class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400"
                                      placeholder="<?= __('site.boards.field_description_placeholder') ?>"></textarea>
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.section_seo') ?></h3>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_robots') ?></label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <input type="radio" name="robots_tag" value="all" checked class="text-blue-600"> <?= __('admin.common.yes') ?>
                                </label>
                                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                    <input type="radio" name="robots_tag" value="noindex" class="text-blue-600"> <?= __('admin.common.no') ?>
                                </label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_seo_keywords') ?></label>
                            <input type="text" name="seo_keywords"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400"
                                   placeholder="keyword1, keyword2">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_seo_desc') ?></label>
                            <input type="text" name="seo_description"
                                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
                        </div>
                    </div>

                    <!-- 표시 설정 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.section_display') ?></h3>

                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_per_page') ?></label>
                                <input type="number" name="per_page" value="20" min="1" max="100"
                                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_search_per_page') ?></label>
                                <input type="number" name="search_per_page" value="20" min="1" max="100"
                                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_page_count') ?></label>
                                <input type="number" name="page_count" value="10" min="1" max="20"
                                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_header') ?></label>
                            <textarea name="header_content" rows="3"
                                      class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                                      placeholder="HTML"></textarea>
                            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.field_header_help') ?></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_footer') ?></label>
                            <textarea name="footer_content" rows="3"
                                      class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                                      placeholder="HTML"></textarea>
                        </div>
                    </div>

                    <!-- 버튼 -->
                    <div class="flex items-center justify-end gap-3">
                        <a href="<?= $adminUrl ?>/site/boards"
                           class="px-6 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                            <?= __('admin.buttons.cancel') ?>
                        </a>
                        <button type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                            <?= __('site.boards.create_submit') ?>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    console.log('[BoardCreate] 게시판 생성 페이지 로드됨');
    const adminUrl = '<?= $adminUrl ?>';

    document.getElementById('boardCreateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('[BoardCreate] submit');

        const form = new FormData(this);
        form.append('action', 'create');

        try {
            const resp = await fetch(adminUrl + '/site/boards/api', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(form)
            });
            const data = await resp.json();
            console.log('[BoardCreate] response:', data);

            if (data.success) {
                alert(data.message);
                location.href = adminUrl + '/site/boards/edit?id=' + data.board_id;
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) {
            console.error('[BoardCreate] error:', err);
            alert('Error: ' + err.message);
        }
    });
    </script>
</body>
</html>
