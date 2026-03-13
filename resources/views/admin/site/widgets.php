<?php
/**
 * RezlyX Admin - 위젯 관리
 */
$pageTitle = __('admin.site.widgets.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

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

$message = '';
$messageType = '';

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_widget') {
        $id = (int)($_POST['widget_id'] ?? 0);
        $active = (int)($_POST['is_active'] ?? 0);
        $stmt = $pdo->prepare("UPDATE rzx_widgets SET is_active = ? WHERE id = ?");
        $stmt->execute([$active, $id]);
        $message = __('admin.site.widgets.saved');
        $messageType = 'success';

    } elseif ($action === 'delete_widget') {
        $id = (int)($_POST['widget_id'] ?? 0);
        // 내장 위젯은 삭제 불가
        $stmt = $pdo->prepare("SELECT type FROM rzx_widgets WHERE id = ?");
        $stmt->execute([$id]);
        $w = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($w && $w['type'] !== 'builtin') {
            $pdo->prepare("DELETE FROM rzx_page_widgets WHERE widget_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM rzx_widgets WHERE id = ?")->execute([$id]);
            $message = __('admin.site.widgets.deleted');
            $messageType = 'success';
        }

    } elseif ($action === 'save_widget') {
        $id = (int)($_POST['widget_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = $_POST['category'] ?? 'general';
        $template = $_POST['template'] ?? '';
        $css = $_POST['css'] ?? '';
        $js = $_POST['js'] ?? '';
        $configSchema = $_POST['config_schema'] ?? '{}';

        if ($name && $slug) {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE rzx_widgets SET name=?, slug=?, description=?, category=?, template=?, css=?, js=?, config_schema=? WHERE id=? AND type='custom'");
                $stmt->execute([$name, $slug, $description, $category, $template, $css, $js, $configSchema, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO rzx_widgets (name, slug, description, type, category, template, css, js, config_schema, icon) VALUES (?, ?, ?, 'custom', ?, ?, ?, ?, ?, 'puzzle-piece')");
                $stmt->execute([$name, $slug, $description, $category, $template, $css, $js, $configSchema]);
            }
            $message = __('admin.site.widgets.saved');
            $messageType = 'success';
        }
    }
}

// 필터
$filterType = $_GET['type'] ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';

$sql = "SELECT * FROM rzx_widgets WHERE 1=1";
$params = [];
if ($filterType !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $filterType;
}
if ($filterCategory !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
}
$sql .= " ORDER BY type ASC, name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// 위젯 아이콘 매핑
$iconMap = [
    'sparkles' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    'grid' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    'briefcase' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'chart-bar' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'chat' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    'megaphone' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    'document-text' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'arrows-expand' => 'M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4',
    'puzzle-piece' => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
    'cube' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
];

function getWidgetIcon($icon, $iconMap) {
    return $iconMap[$icon] ?? $iconMap['cube'];
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
            $pageHeaderTitle = __('admin.site.widgets.title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>

            <div class="p-6">
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800' : 'bg-red-50 text-red-800 border border-red-200 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z';
                $headerTitle = __('admin.site.widgets.title');
                $headerDescription = __('admin.site.widgets.description');
                $headerIconColor = '';
                $headerActions = '<a href="' . $adminUrl . '/site/widgets/create" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>' . __('admin.site.widgets.create') . '</a>';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- Tabs -->
                <div class="flex items-center gap-2 mb-6">
                    <a href="<?= $adminUrl ?>/site/widgets" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filterType === 'all' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?> transition"><?= __('admin.site.widgets.categories.all') ?></a>
                    <a href="<?= $adminUrl ?>/site/widgets?type=builtin" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filterType === 'builtin' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?> transition"><?= __('admin.site.widgets.builtin') ?></a>
                    <a href="<?= $adminUrl ?>/site/widgets?type=custom" class="px-4 py-2 rounded-lg text-sm font-medium <?= $filterType === 'custom' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?> transition"><?= __('admin.site.widgets.custom') ?></a>
                    <a href="<?= $adminUrl ?>/site/widgets/marketplace" class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/></svg>
                        <?= __('admin.site.widgets.marketplace') ?>
                    </a>
                </div>

                <!-- Widget Grid -->
                <?php if (empty($widgets)): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $iconMap['puzzle-piece'] ?>"/>
                    </svg>
                    <p class="text-zinc-500 dark:text-zinc-400"><?= __('admin.site.widgets.no_widgets') ?></p>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($widgets as $w): ?>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm hover:shadow-md transition-all border border-zinc-200 dark:border-zinc-700">
                        <div class="p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= getWidgetIcon($w['icon'], $iconMap) ?>"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white text-sm">
                                            <?php
                                            $wKey = 'admin.site.widget_builder.w.' . $w['slug'];
                                            $translated = __($wKey);
                                            echo $translated !== $wKey ? htmlspecialchars($translated) : htmlspecialchars($w['name']);
                                            ?>
                                        </h3>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($w['slug']) ?></p>
                                    </div>
                                </div>
                                <!-- Type Badge -->
                                <span class="text-xs font-medium px-2 py-0.5 rounded <?= $w['type'] === 'builtin' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300' : ($w['type'] === 'custom' ? 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300' : 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300') ?>">
                                    <?= __('admin.site.widgets.types.' . $w['type']) ?>
                                </span>
                            </div>

                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4 line-clamp-2">
                                <?php
                                $descKey = 'admin.site.widget_builder.w.' . $w['slug'] . '_desc';
                                $descTranslated = __($descKey);
                                echo $descTranslated !== $descKey ? htmlspecialchars($descTranslated) : htmlspecialchars($w['description'] ?? '');
                                ?>
                            </p>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500"><?= __('admin.site.widgets.category') ?>: <?= __('admin.site.widgets.categories.' . ($w['category'] ?: 'general')) ?></span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <!-- Toggle Active -->
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle_widget">
                                        <input type="hidden" name="widget_id" value="<?= $w['id'] ?>">
                                        <input type="hidden" name="is_active" value="<?= $w['is_active'] ? 0 : 1 ?>">
                                        <button type="submit" class="p-1.5 rounded-lg transition <?= $w['is_active'] ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' : 'text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>" title="<?= $w['is_active'] ? __('admin.site.widgets.active') : __('admin.site.widgets.inactive') ?>">
                                            <svg class="w-4 h-4" fill="<?= $w['is_active'] ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <?php if ($w['type'] === 'custom'): ?>
                                    <!-- Edit -->
                                    <a href="<?= $adminUrl ?>/site/widgets/create?id=<?= $w['id'] ?>" class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition" title="<?= __('admin.site.widgets.edit') ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <!-- Delete -->
                                    <form method="POST" class="inline" onsubmit="return confirm('<?= __('admin.site.widgets.delete_confirm') ?>')">
                                        <input type="hidden" name="action" value="delete_widget">
                                        <input type="hidden" name="widget_id" value="<?= $w['id'] ?>">
                                        <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="<?= __('admin.site.widgets.delete') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
