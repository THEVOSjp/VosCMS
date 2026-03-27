<?php
/**
 * RezlyX Admin - 게시판 설정 (탭 컨테이너)
 */
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// 게시판 ID
$boardId = (int)($_GET['id'] ?? 0);

// embed 모드에서는 $pdo, $board가 이미 설정됨
if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        die('DB connection error');
    }
}
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

if (!$boardId && !empty($board['id'])) $boardId = (int)$board['id'];
if (!$boardId) {
    header('Location: ' . $adminUrl . '/site/boards');
    exit;
}

// 게시판 데이터 로드 (embed 모드에서 이미 있으면 스킵)
if (!isset($board) || empty($board)) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE id = ?");
    $stmt->execute([$boardId]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$board) {
    header('Location: ' . $adminUrl . '/site/boards');
    exit;
}

// 카테고리 로드
$catStmt = $pdo->prepare("SELECT * FROM {$prefix}board_categories WHERE board_id = ? ORDER BY sort_order ASC, id ASC");
$catStmt->execute([$boardId]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// 현재 탭
$currentTab = $_GET['tab'] ?? 'basic';
$validTabs = ['basic', 'categories', 'extra_vars', 'permissions', 'addition', 'skin'];
if (!in_array($currentTab, $validTabs)) $currentTab = 'basic';

// 게시판 제목 다국어
$_beLocale = $config['locale'] ?? 'ko';
$_beDefLocale = $siteSettings['default_language'] ?? 'ko';
$_beChain = array_unique(array_filter([$_beLocale, 'en', $_beDefLocale]));
$_beTrTitle = $board['title'];
try {
    $_bePH = implode(',', array_fill(0, count($_beChain), '?'));
    $_beStmt = $pdo->prepare("SELECT locale, content FROM {$prefix}translations WHERE lang_key = ? AND locale IN ({$_bePH})");
    $_beStmt->execute(array_merge(["board.{$boardId}.title"], array_values($_beChain)));
    $_beTrData = [];
    while ($_bt = $_beStmt->fetch(PDO::FETCH_ASSOC)) { $_beTrData[$_bt['locale']] = $_bt['content']; }
    foreach ($_beChain as $lc) { if (!empty($_beTrData[$lc])) { $_beTrTitle = $_beTrData[$lc]; break; } }
} catch (PDOException $e) {}

$pageTitle = htmlspecialchars($_beTrTitle) . ' ' . __('site.boards.settings') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// 탭 정의
$tabs = [
    'basic' => [
        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'icon2' => 'M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'label' => __('site.boards.tab_basic'),
    ],
    'categories' => [
        'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z',
        'label' => __('site.boards.tab_categories'),
    ],
    'extra_vars' => [
        'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        'label' => __('site.boards.tab_extra_vars'),
    ],
    'permissions' => [
        'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'label' => __('site.boards.tab_permissions'),
    ],
    'addition' => [
        'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
        'label' => __('site.boards.tab_addition'),
    ],
    'skin' => [
        'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01',
        'label' => __('site.boards.tab_skin'),
    ],
];


$pageHeaderTitle = htmlspecialchars($_beTrTitle) . ' ' . __('site.boards.settings');
?>
<?php $embedMode = !empty($_GET['embed']); ?>
<?php if (!$embedMode): ?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
    <script>
        // 섹션 접기/펼치기 (인라인 onclick에서 사용)
        function toggleSection(btn) {
            var section = btn.closest('[data-section]');
            var body = section.querySelector('.section-body');
            var chevron = btn.querySelector('.section-chevron');
            body.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }
    </script>
<?php else: ?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
        function toggleSection(btn) {
            var section = btn.closest('[data-section]');
            var body = section.querySelector('.section-body');
            var chevron = btn.querySelector('.section-chevron');
            body.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="p-4">
<?php endif; ?>
                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z';
                $headerTitle = htmlspecialchars($_beTrTitle) . ' - ' . __('site.boards.edit_title');
                $headerDescription = '/board/' . htmlspecialchars($board['slug']);
                $headerIconColor = '';
                $trashCount = 0;
if ($board['use_trash'] ?? 0) {
    $tc = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_posts WHERE board_id = ? AND status = 'trash'");
    $tc->execute([$boardId]);
    $trashCount = (int)$tc->fetchColumn();
}
$headerActions = '';
if ($board['use_trash'] ?? 0) {
    $headerActions .= '<a href="' . $adminUrl . '/site/boards/trash?id=' . $boardId . '" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">' . __('site.boards.trash') . ($trashCount ? ' (' . $trashCount . ')' : '') . '</a> ';
}
$headerActions .= '<a href="' . $adminUrl . '/site/boards" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">' . __('site.boards.back_to_list') . '</a>';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- 탭 네비게이션 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mb-6">
                    <div class="border-b border-zinc-200 dark:border-zinc-700">
                        <nav class="flex -mb-px overflow-x-auto">
                            <?php foreach ($tabs as $tabKey => $tab): ?>
                            <?php
                            $tabUrl = $embedMode
                                ? $baseUrl . '/board/' . htmlspecialchars($board['slug']) . '/settings?tab=' . $tabKey
                                : $adminUrl . '/site/boards/edit?id=' . $boardId . '&tab=' . $tabKey;
                            ?>
                            <a href="<?= $tabUrl ?>"
                               class="px-5 py-3.5 text-sm font-medium border-b-2 whitespace-nowrap transition <?= $currentTab === $tabKey
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300' ?>">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tab['icon'] ?>"/>
                                        <?php if (!empty($tab['icon2'])): ?>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tab['icon2'] ?>"/>
                                        <?php endif; ?>
                                    </svg>
                                    <?= $tab['label'] ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>

                <!-- 탭 내용 -->
                <?php
                $tabFile = __DIR__ . '/boards-edit-' . $currentTab . '.php';
                if (file_exists($tabFile)) {
                    include $tabFile;
                } else {
                    echo '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">';
                    echo '<p class="text-zinc-500 dark:text-zinc-400">' . __('site.boards.tab_coming_soon') . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
    <?php if (!$embedMode): ?>
        </main>
    </div>
    <?php else: ?>
    </div>
    <?php endif; ?>
    <?php include __DIR__ . '/../components/multilang-modal.php'; ?>
<?php if (!$embedMode): ?>
</body>
</html>
<?php endif; ?>
