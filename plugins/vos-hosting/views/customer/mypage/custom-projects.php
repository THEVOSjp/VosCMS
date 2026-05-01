<?php
/**
 * 마이페이지 — 제작 프로젝트 목록
 */
use RzxLib\Core\Auth\Auth;

if (!$isLoggedIn) { header("Location: {$baseUrl}/login"); exit; }

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$user = Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

$listSt = $pdo->prepare("SELECT * FROM {$prefix}custom_projects WHERE user_id = ? ORDER BY created_at DESC, id DESC");
$listSt->execute([$user['id']]);
$projects = $listSt->fetchAll(PDO::FETCH_ASSOC);

$statusBadge = function(string $s): array {
    return match ($s) {
        'lead' => [__('services.custom.st_lead'), 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
        'quoted' => [__('services.custom.st_quoted'), 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
        'contracted' => [__('services.custom.st_contracted'), 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
        'in_progress' => [__('services.custom.st_in_progress'), 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'],
        'review' => [__('services.custom.st_review'), 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'],
        'delivered' => [__('services.custom.st_delivered'), 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400'],
        'maintenance' => [__('services.custom.st_maintenance'), 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400'],
        'cancelled' => [__('services.custom.st_cancelled'), 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-zinc-400'],
        default => [$s, 'bg-gray-100 text-gray-500'],
    };
};
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-8">
        <?php $sidebarActive = 'custom-projects'; include BASE_PATH . '/resources/views/components/mypage-sidebar.php'; ?>

        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.custom.page_title')) ?></h1>
                    <p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars(__('services.custom.page_desc')) ?></p>
                </div>
                <a href="<?= $baseUrl ?>/mypage/custom-projects/new"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <?= htmlspecialchars(__('services.custom.btn_new')) ?>
                </a>
            </div>

            <?php if (empty($projects)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-12 text-center">
                <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= htmlspecialchars(__('services.custom.empty')) ?></p>
                <a href="<?= $baseUrl ?>/mypage/custom-projects/new" class="inline-block px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                    <?= htmlspecialchars(__('services.custom.btn_first')) ?>
                </a>
            </div>
            <?php else: ?>
            <div class="space-y-3">
            <?php foreach ($projects as $p): $bg = $statusBadge($p['status']); ?>
            <a href="<?= $baseUrl ?>/mypage/custom-projects/<?= (int)$p['id'] ?>"
                class="block bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-700 transition p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-mono text-zinc-400">#<?= htmlspecialchars($p['project_number']) ?></span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $bg[1] ?>"><?= htmlspecialchars($bg[0]) ?></span>
                        </div>
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($p['title']) ?></h3>
                        <p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars(date('Y-m-d', strtotime($p['created_at']))) ?></p>
                    </div>
                    <?php if ($p['contract_amount']): ?>
                    <div class="text-right whitespace-nowrap">
                        <p class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.custom.contract_amount')) ?></p>
                        <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">¥<?= number_format((int)$p['contract_amount']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
