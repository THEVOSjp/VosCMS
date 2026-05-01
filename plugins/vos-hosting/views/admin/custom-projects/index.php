<?php
/**
 * 관리자 — 제작 프로젝트 (칸반 보드)
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$pageTitle = __('services.admin_custom.page_title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __('services.admin_custom.header_title');
$pageSubTitle = __('services.admin_custom.sub_title');
$pageSubDesc = __('services.admin_custom.sub_desc');

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

// 검색
$searchKey = trim($_GET['q'] ?? '');
$searchSQL = '';
$searchParams = [];
if ($searchKey !== '') {
    $searchSQL = ' AND (p.title LIKE ? OR p.project_number LIKE ? OR u.email LIKE ?)';
    $like = '%' . $searchKey . '%';
    $searchParams = [$like, $like, $like];
}

// 칸반 컬럼 정의 — 보드 표시용 5개 그룹 (lead/quoted, contracted, in_progress/review, delivered/maintenance, cancelled)
$columns = [
    'pending' => ['label' => __('services.admin_custom.col_pending'), 'statuses' => ['lead','quoted'], 'cls' => 'border-blue-300 bg-blue-50/50 dark:bg-blue-900/10'],
    'contracted' => ['label' => __('services.admin_custom.col_contracted'), 'statuses' => ['contracted'], 'cls' => 'border-emerald-300 bg-emerald-50/50 dark:bg-emerald-900/10'],
    'building' => ['label' => __('services.admin_custom.col_building'), 'statuses' => ['in_progress','review'], 'cls' => 'border-indigo-300 bg-indigo-50/50 dark:bg-indigo-900/10'],
    'live' => ['label' => __('services.admin_custom.col_live'), 'statuses' => ['delivered','maintenance'], 'cls' => 'border-teal-300 bg-teal-50/50 dark:bg-teal-900/10'],
    'cancelled' => ['label' => __('services.admin_custom.col_cancelled'), 'statuses' => ['cancelled'], 'cls' => 'border-gray-300 bg-gray-50 dark:bg-zinc-700/30'],
];

$projectsByCol = [];
foreach ($columns as $colKey => $col) {
    $place = implode(',', array_fill(0, count($col['statuses']), '?'));
    $sql = "SELECT p.*, u.email AS user_email, u.name AS user_name
        FROM {$prefix}custom_projects p
        LEFT JOIN {$prefix}users u ON p.user_id = u.id
        WHERE p.status IN ($place) $searchSQL
        ORDER BY p.created_at DESC, p.id DESC";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge($col['statuses'], $searchParams));
    $projectsByCol[$colKey] = $st->fetchAll(PDO::FETCH_ASSOC);
}

// 통계
$stTotal = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}custom_projects")->fetchColumn();
$stPending = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}custom_projects WHERE status IN ('lead','quoted')")->fetchColumn();
$stActive = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}custom_projects WHERE status IN ('contracted','in_progress','review')")->fetchColumn();
$stRevenue = (float)$pdo->query("SELECT COALESCE(SUM(contract_amount), 0) FROM {$prefix}custom_projects WHERE status IN ('contracted','in_progress','review','delivered','maintenance')")->fetchColumn();

$statusLabel = function(string $s): string {
    return match ($s) {
        'lead' => __('services.custom.st_lead'),
        'quoted' => __('services.custom.st_quoted'),
        'contracted' => __('services.custom.st_contracted'),
        'in_progress' => __('services.custom.st_in_progress'),
        'review' => __('services.custom.st_review'),
        'delivered' => __('services.custom.st_delivered'),
        'maintenance' => __('services.custom.st_maintenance'),
        'cancelled' => __('services.custom.st_cancelled'),
        default => $s,
    };
};
$statusColor = fn(string $s) => match ($s) {
    'lead' => 'bg-blue-100 text-blue-700',
    'quoted' => 'bg-amber-100 text-amber-700',
    'contracted' => 'bg-emerald-100 text-emerald-700',
    'in_progress' => 'bg-indigo-100 text-indigo-700',
    'review' => 'bg-purple-100 text-purple-700',
    'delivered' => 'bg-teal-100 text-teal-700',
    'maintenance' => 'bg-cyan-100 text-cyan-700',
    'cancelled' => 'bg-gray-100 text-gray-500',
    default => 'bg-gray-100 text-gray-500',
};

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="px-4 sm:px-6 lg:px-8 py-6">
    <!-- 통계 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700">
            <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_custom.stats_total')) ?></p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $stTotal ?></p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_custom.stats_pending')) ?></p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-300"><?= $stPending ?></p>
        </div>
        <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 border border-indigo-200 dark:border-indigo-800">
            <p class="text-[10px] font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_custom.stats_active')) ?></p>
            <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-300"><?= $stActive ?></p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <p class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_custom.stats_revenue')) ?></p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-300">¥<?= number_format($stRevenue) ?></p>
        </div>
    </div>

    <!-- 검색 -->
    <form method="GET" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700 mb-4 flex flex-wrap items-center gap-2">
        <input type="text" name="q" value="<?= htmlspecialchars($searchKey) ?>" placeholder="<?= htmlspecialchars(__('services.admin_custom.search_placeholder')) ?>" class="flex-1 max-w-md px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        <button type="submit" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= htmlspecialchars(__('services.admin_support.btn_filter')) ?></button>
    </form>

    <!-- 칸반 보드 -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
        <?php foreach ($columns as $colKey => $col): $list = $projectsByCol[$colKey]; ?>
        <div class="border <?= $col['cls'] ?> rounded-xl">
            <div class="px-3 py-2.5 border-b border-current/20 flex items-center justify-between">
                <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($col['label']) ?></p>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 font-medium"><?= count($list) ?></span>
            </div>
            <div class="p-2 space-y-2 min-h-[200px]">
                <?php if (empty($list)): ?>
                <p class="text-[10px] text-zinc-400 text-center py-6"><?= htmlspecialchars(__('services.admin_custom.col_empty')) ?></p>
                <?php else: foreach ($list as $p):
                    $userName = $p['user_name'] ? (decrypt($p['user_name']) ?: $p['user_name']) : '-';
                ?>
                <a href="<?= $adminUrl ?>/custom-projects/<?= (int)$p['id'] ?>" class="block bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-700 p-3 hover:border-blue-400 hover:shadow-sm transition">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-[10px] font-mono text-zinc-400">#<?= htmlspecialchars($p['project_number']) ?></span>
                        <span class="text-[9px] px-1.5 py-0.5 rounded-full font-medium <?= $statusColor($p['status']) ?>"><?= htmlspecialchars($statusLabel($p['status'])) ?></span>
                    </div>
                    <p class="text-xs font-bold text-zinc-900 dark:text-white truncate mb-1"><?= htmlspecialchars($p['title']) ?></p>
                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate"><?= htmlspecialchars($userName) ?></p>
                    <div class="mt-2 flex items-center justify-between text-[10px]">
                        <span class="text-zinc-400"><?= htmlspecialchars(date('m/d', strtotime($p['created_at']))) ?></span>
                        <?php if ($p['contract_amount']): ?>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">¥<?= number_format((int)$p['contract_amount']) ?></span>
                        <?php elseif ($p['estimated_amount']): ?>
                        <span class="text-amber-600">¥<?= number_format((int)$p['estimated_amount']) ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
