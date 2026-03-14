<?php
/**
 * RezlyX Admin - 커스텀 위젯 생성/편집
 */
$pageTitle = __('site.widgets.create') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB 연결 실패: ' . $e->getMessage());
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$message = '';
$messageType = '';

// 편집 모드
$editId = (int)($_GET['id'] ?? 0);
$widget = null;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM rzx_widgets WHERE id = ? AND type = 'custom'");
    $stmt->execute([$editId]);
    $widget = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($widget) {
        $pageTitle = __('site.widgets.edit') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
    }
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $template = $_POST['template'] ?? '';
    $css = $_POST['css'] ?? '';
    $js = $_POST['js'] ?? '';
    $configSchema = $_POST['config_schema'] ?? '{}';

    // slug 유효성
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

    if (!$name || !$slug) {
        $message = '이름과 슬러그는 필수입니다.';
        $messageType = 'error';
    } else {
        // slug 중복 확인
        $checkSql = "SELECT id FROM rzx_widgets WHERE slug = ?";
        $checkParams = [$slug];
        if ($editId > 0) {
            $checkSql .= " AND id != ?";
            $checkParams[] = $editId;
        }
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute($checkParams);
        if ($stmt->fetch()) {
            $message = 'Slug가 이미 사용 중입니다.';
            $messageType = 'error';
        } else {
            if ($editId > 0 && $widget) {
                $stmt = $pdo->prepare("UPDATE rzx_widgets SET name=?, slug=?, description=?, category=?, template=?, css=?, js=?, config_schema=? WHERE id=? AND type='custom'");
                $stmt->execute([$name, $slug, $description, $category, $template, $css, $js, $configSchema, $editId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rzx_widgets (name, slug, description, type, category, icon, template, css, js, config_schema) VALUES (?, ?, ?, 'custom', ?, 'puzzle-piece', ?, ?, ?, ?)");
                $stmt->execute([$name, $slug, $description, $category, $template, $css, $js, $configSchema]);
                $editId = (int)$pdo->lastInsertId();
            }
            $message = __('site.widgets.saved');
            $messageType = 'success';
            // 리로드
            $stmt = $pdo->prepare("SELECT * FROM rzx_widgets WHERE id = ?");
            $stmt->execute([$editId]);
            $widget = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
        .code-editor { font-family: 'Consolas', 'Monaco', monospace; font-size: 13px; tab-size: 4; }
    </style>
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
            $pageHeaderTitle = $widget ? __('site.widgets.edit') : __('site.widgets.create');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>

            <div class="p-6">
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Back link -->
                <a href="<?= $adminUrl ?>/site/widgets" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline mb-4">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <?= __('site.widgets.title') ?>
                </a>

                <form method="POST" id="widgetForm">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Basic Info -->
                        <div class="lg:col-span-1 space-y-6">
                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.widgets.form.name') ?></h2>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.widgets.form.name') ?></label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($widget['name'] ?? '') ?>" placeholder="<?= __('site.widgets.form.name_placeholder') ?>" required
                                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.widgets.form.slug') ?></label>
                                        <input type="text" name="slug" value="<?= htmlspecialchars($widget['slug'] ?? '') ?>" placeholder="<?= __('site.widgets.form.slug_placeholder') ?>" required
                                               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.widgets.form.description') ?></label>
                                        <textarea name="description" rows="3" placeholder="<?= __('site.widgets.form.description_placeholder') ?>"
                                                  class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($widget['description'] ?? '') ?></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.widgets.form.category') ?></label>
                                        <select name="category" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            <option value="general" <?= ($widget['category'] ?? '') === 'general' ? 'selected' : '' ?>><?= __('site.widgets.categories.general') ?></option>
                                            <option value="layout" <?= ($widget['category'] ?? '') === 'layout' ? 'selected' : '' ?>><?= __('site.widgets.categories.layout') ?></option>
                                            <option value="content" <?= ($widget['category'] ?? '') === 'content' ? 'selected' : '' ?>><?= __('site.widgets.categories.content') ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Config Schema -->
                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.widgets.form.config_schema') ?></h2>
                                <textarea name="config_schema" rows="8" placeholder="<?= __('site.widgets.form.config_schema_placeholder') ?>"
                                          class="code-editor w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($widget['config_schema'] ?? '{}') ?></textarea>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-2">JSON 형식으로 위젯 설정 필드를 정의합니다.</p>
                            </div>
                        </div>

                        <!-- Right: Code Editor -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- HTML Template -->
                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.widgets.form.template') ?></h2>
                                <textarea name="template" rows="12" placeholder="<?= htmlspecialchars(__('site.widgets.form.template_placeholder')) ?>"
                                          class="code-editor w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($widget['template'] ?? '') ?></textarea>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-2">{{변수명}} 구문으로 설정값을 삽입할 수 있습니다.</p>
                            </div>

                            <!-- CSS -->
                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.widgets.form.css') ?></h2>
                                <textarea name="css" rows="6" placeholder="<?= __('site.widgets.form.css_placeholder') ?>"
                                          class="code-editor w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($widget['css'] ?? '') ?></textarea>
                            </div>

                            <!-- JS -->
                            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
                                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.widgets.form.js') ?></h2>
                                <textarea name="js" rows="6" placeholder="<?= __('site.widgets.form.js_placeholder') ?>"
                                          class="code-editor w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?= htmlspecialchars($widget['js'] ?? '') ?></textarea>
                            </div>

                            <!-- Save Button -->
                            <div class="flex justify-end gap-3">
                                <a href="<?= $adminUrl ?>/site/widgets" class="px-6 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                                    <?= __('admin.buttons.cancel') ?>
                                </a>
                                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <?= __('admin.buttons.save') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    console.log('[Widget Create] Page loaded');
    // slug 자동 생성
    var nameInput = document.querySelector('input[name="name"]');
    var slugInput = document.querySelector('input[name="slug"]');
    if (nameInput && slugInput && !slugInput.value) {
        nameInput.addEventListener('input', function() {
            slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
            console.log('[Widget Create] Auto slug:', slugInput.value);
        });
    }
    </script>
</body>
</html>
