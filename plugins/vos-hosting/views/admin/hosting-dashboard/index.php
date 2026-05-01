<?php
/**
 * 호스팅 관리 — 대시보드
 * 호스팅 사업 핵심 KPI + 액션 필요 항목 + 최근 활동.
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$pageTitle = __('services.admin_dashboard.page_title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __('services.admin_dashboard.header_title');
$pageSubTitle = __('services.admin_dashboard.sub_title');
$pageSubDesc = __('services.admin_dashboard.sub_desc');

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

$thisMonthStart = date('Y-m-01 00:00:00');
$nextMonthStart = date('Y-m-01 00:00:00', strtotime('+1 month'));
$in30Days = date('Y-m-d 23:59:59', strtotime('+30 days'));

// ============================================================
// KPI 카드 6개
// ============================================================

// 1. 활성 호스팅 구독
$kpiActiveHosting = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}subscriptions WHERE type = 'hosting' AND status = 'active'")->fetchColumn();

// 2. 이번 달 매출 (호스팅 + 부가서비스 + 제작 = 모든 paid 결제)
$mSt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM {$prefix}payments WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?");
$mSt->execute([$thisMonthStart, $nextMonthStart]);
$kpiMonthRevenue = (float)$mSt->fetchColumn();

// 환불 차감
$rSt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM {$prefix}payments WHERE status = 'refund' AND paid_at >= ? AND paid_at < ?");
$rSt->execute([$thisMonthStart, $nextMonthStart]);
$kpiMonthRevenue -= (float)$rSt->fetchColumn();

// 3. 미확인 1:1 상담
$kpiUnreadSupport = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}support_tickets WHERE unread_by_admin = 1")->fetchColumn();

// 4. 견적 대기 프로젝트 (lead)
$kpiLeadProjects = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}custom_projects WHERE status = 'lead'")->fetchColumn();

// 5. 만료 임박 호스팅 (30일 내)
$eSt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}subscriptions WHERE type = 'hosting' AND status = 'active' AND expires_at IS NOT NULL AND expires_at <= ? AND expires_at > NOW()");
$eSt->execute([$in30Days]);
$kpiExpiringHosting = (int)$eSt->fetchColumn();

// 6. 결제 대기 분할금 (sum of pending custom_project_payments)
$pSt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(cpp.amount), 0)
    FROM {$prefix}custom_project_payments cpp
    JOIN {$prefix}custom_quotes q ON cpp.quote_id = q.id
    WHERE cpp.status = 'pending' AND q.status = 'accepted'");
$pSt->execute();
$pendingRow = $pSt->fetch(PDO::FETCH_NUM);
$kpiPendingPayments = (int)$pendingRow[0];
$kpiPendingPaymentsSum = (float)$pendingRow[1];

// ============================================================
// 액션 필요 항목
// ============================================================

// 답변 필요한 상담 (미확인 또는 open)
$actionSupport = $pdo->prepare("SELECT t.id, t.title, t.status, t.last_message_at, t.unread_by_admin, u.email AS user_email, u.name AS user_name
    FROM {$prefix}support_tickets t
    LEFT JOIN {$prefix}users u ON t.user_id = u.id
    WHERE t.unread_by_admin = 1 OR t.status = 'open'
    ORDER BY t.unread_by_admin DESC, t.last_message_at DESC LIMIT 5");
$actionSupport->execute();
$supportList = $actionSupport->fetchAll(PDO::FETCH_ASSOC);

// 견적 발행 대기 (lead) + 시안 검수 대기 (submitted milestones)
$actionProjects = $pdo->prepare("SELECT p.id, p.project_number, p.title, p.status, p.created_at, u.email AS user_email, u.name AS user_name,
    (SELECT COUNT(*) FROM {$prefix}custom_milestones m WHERE m.project_id = p.id AND m.status = 'submitted') AS submitted_ms
    FROM {$prefix}custom_projects p
    LEFT JOIN {$prefix}users u ON p.user_id = u.id
    WHERE p.status IN ('lead', 'in_progress', 'review')
    ORDER BY p.status = 'lead' DESC, p.created_at DESC LIMIT 5");
$actionProjects->execute();
$projectList = $actionProjects->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 최근 활동
// ============================================================

// 최근 결제 10건
$recentPayments = $pdo->prepare("SELECT p.id, p.amount, p.status, p.paid_at, p.metadata, u.email AS user_email, u.name AS user_name
    FROM {$prefix}payments p
    LEFT JOIN {$prefix}users u ON p.user_id = u.id
    WHERE p.status = 'paid'
    ORDER BY p.paid_at DESC LIMIT 10");
$recentPayments->execute();
$paymentsList = $recentPayments->fetchAll(PDO::FETCH_ASSOC);

// 최근 신규 의뢰 — 호스팅 주문 + 제작 의뢰 통합
$recentRequests = $pdo->prepare("(SELECT 'order' AS kind, o.id, o.order_number, o.domain AS subject, o.created_at, u.email AS user_email, u.name AS user_name, NULL AS extra
    FROM {$prefix}orders o
    LEFT JOIN {$prefix}users u ON o.user_id = u.id
    ORDER BY o.created_at DESC LIMIT 10)
    UNION ALL
    (SELECT 'project' AS kind, p.id, p.project_number AS order_number, p.title AS subject, p.created_at, u.email AS user_email, u.name AS user_name, p.status AS extra
    FROM {$prefix}custom_projects p
    LEFT JOIN {$prefix}users u ON p.user_id = u.id
    ORDER BY p.created_at DESC LIMIT 10)
    ORDER BY created_at DESC LIMIT 10");
$recentRequests->execute();
$requestsList = $recentRequests->fetchAll(PDO::FETCH_ASSOC);

// 월별 매출 추이 (12개월)
$monthlyRevenue = [];
for ($i = 11; $i >= 0; $i--) {
    $monthStart = date('Y-m-01 00:00:00', strtotime("-$i months"));
    $monthEnd = date('Y-m-01 00:00:00', strtotime("-" . ($i - 1) . " months"));
    $monthLabel = date('y/m', strtotime("-$i months"));
    $rs = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM {$prefix}payments WHERE status = 'paid' AND paid_at >= ? AND paid_at < ?");
    $rs->execute([$monthStart, $monthEnd]);
    $paid = (float)$rs->fetchColumn();
    $rr = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM {$prefix}payments WHERE status = 'refund' AND paid_at >= ? AND paid_at < ?");
    $rr->execute([$monthStart, $monthEnd]);
    $refund = (float)$rr->fetchColumn();
    $monthlyRevenue[] = ['label' => $monthLabel, 'amount' => $paid - $refund];
}
$maxMonthRevenue = max(array_map(fn($m) => $m['amount'], $monthlyRevenue)) ?: 1;

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="px-4 sm:px-6 lg:px-8 py-6">

    <!-- KPI 카드 6개 -->
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
        <a href="<?= $adminUrl ?>/service-orders" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700 hover:shadow-md transition">
            <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_dashboard.kpi_active_hosting')) ?></p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= number_format($kpiActiveHosting) ?></p>
        </a>
        <a href="<?= $adminUrl ?>/accounting" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800 hover:shadow-md transition">
            <p class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_dashboard.kpi_month_revenue')) ?></p>
            <p class="text-xl font-bold text-emerald-700 dark:text-emerald-400">¥<?= number_format($kpiMonthRevenue) ?></p>
        </a>
        <a href="<?= $adminUrl ?>/support-tickets" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border <?= $kpiUnreadSupport > 0 ? 'border-red-300 dark:border-red-700' : 'border-gray-200 dark:border-zinc-700' ?> hover:shadow-md transition">
            <p class="text-[10px] font-bold <?= $kpiUnreadSupport > 0 ? 'text-red-700 dark:text-red-400' : 'text-zinc-500 dark:text-zinc-400' ?> uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_dashboard.kpi_unread_support')) ?></p>
            <p class="text-2xl font-bold <?= $kpiUnreadSupport > 0 ? 'text-red-700 dark:text-red-400' : 'text-zinc-900 dark:text-white' ?>"><?= number_format($kpiUnreadSupport) ?></p>
        </a>
        <a href="<?= $adminUrl ?>/custom-projects" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border <?= $kpiLeadProjects > 0 ? 'border-amber-300 dark:border-amber-700' : 'border-gray-200 dark:border-zinc-700' ?> hover:shadow-md transition">
            <p class="text-[10px] font-bold <?= $kpiLeadProjects > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-zinc-500 dark:text-zinc-400' ?> uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_dashboard.kpi_lead_projects')) ?></p>
            <p class="text-2xl font-bold <?= $kpiLeadProjects > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-zinc-900 dark:text-white' ?>"><?= number_format($kpiLeadProjects) ?></p>
        </a>
        <a href="<?= $adminUrl ?>/service-orders" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border <?= $kpiExpiringHosting > 0 ? 'border-orange-300 dark:border-orange-700' : 'border-gray-200 dark:border-zinc-700' ?> hover:shadow-md transition">
            <p class="text-[10px] font-bold <?= $kpiExpiringHosting > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-zinc-500 dark:text-zinc-400' ?> uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_dashboard.kpi_expiring_hosting')) ?></p>
            <p class="text-2xl font-bold <?= $kpiExpiringHosting > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-zinc-900 dark:text-white' ?>"><?= number_format($kpiExpiringHosting) ?></p>
        </a>
        <a href="<?= $adminUrl ?>/custom-projects" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border <?= $kpiPendingPayments > 0 ? 'border-blue-300 dark:border-blue-700' : 'border-gray-200 dark:border-zinc-700' ?> hover:shadow-md transition">
            <p class="text-[10px] font-bold <?= $kpiPendingPayments > 0 ? 'text-blue-700 dark:text-blue-400' : 'text-zinc-500 dark:text-zinc-400' ?> uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_dashboard.kpi_pending_payments')) ?></p>
            <p class="text-2xl font-bold <?= $kpiPendingPayments > 0 ? 'text-blue-700 dark:text-blue-400' : 'text-zinc-900 dark:text-white' ?>"><?= number_format($kpiPendingPayments) ?></p>
            <?php if ($kpiPendingPayments > 0): ?>
            <p class="text-[10px] text-blue-600 dark:text-blue-400 mt-1">¥<?= number_format($kpiPendingPaymentsSum) ?></p>
            <?php endif; ?>
        </a>
    </div>

    <!-- 액션 필요 항목 (2열) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- 답변 필요 상담 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                <p class="text-sm font-bold text-zinc-900 dark:text-white">💬 <?= htmlspecialchars(__('services.admin_dashboard.action_support_title')) ?></p>
                <a href="<?= $adminUrl ?>/support-tickets" class="text-[11px] text-blue-600 hover:underline"><?= htmlspecialchars(__('services.admin_dashboard.view_all')) ?></a>
            </div>
            <?php if (empty($supportList)): ?>
            <p class="p-6 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('services.admin_dashboard.no_pending_support')) ?></p>
            <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <?php foreach ($supportList as $t):
                    $userName = $t['user_name'] ? (decrypt($t['user_name']) ?: $t['user_name']) : '-';
                ?>
                <a href="<?= $adminUrl ?>/support-tickets?ticket=<?= (int)$t['id'] ?>" class="block p-3 hover:bg-gray-50 dark:hover:bg-zinc-700/30">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <p class="text-xs font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($t['title']) ?></p>
                            <?php if ((int)$t['unread_by_admin'] === 1): ?><span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span><?php endif; ?>
                        </div>
                        <span class="text-[10px] text-zinc-400 whitespace-nowrap"><?= htmlspecialchars(date('m/d H:i', strtotime($t['last_message_at'] ?: 'now'))) ?></span>
                    </div>
                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5"><?= htmlspecialchars($userName) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 액션 필요 프로젝트 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                <p class="text-sm font-bold text-zinc-900 dark:text-white">📋 <?= htmlspecialchars(__('services.admin_dashboard.action_project_title')) ?></p>
                <a href="<?= $adminUrl ?>/custom-projects" class="text-[11px] text-blue-600 hover:underline"><?= htmlspecialchars(__('services.admin_dashboard.view_all')) ?></a>
            </div>
            <?php if (empty($projectList)): ?>
            <p class="p-6 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('services.admin_dashboard.no_pending_projects')) ?></p>
            <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <?php foreach ($projectList as $p):
                    $userName = $p['user_name'] ? (decrypt($p['user_name']) ?: $p['user_name']) : '-';
                    $statusKey = match ($p['status']) {
                        'lead' => 'lead', 'quoted' => 'quoted', 'contracted' => 'contracted',
                        'in_progress' => 'in_progress', 'review' => 'review', default => $p['status'],
                    };
                ?>
                <a href="<?= $adminUrl ?>/custom-projects/<?= (int)$p['id'] ?>" class="block p-3 hover:bg-gray-50 dark:hover:bg-zinc-700/30">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="text-[10px] font-mono text-zinc-400">#<?= htmlspecialchars($p['project_number']) ?></span>
                            <p class="text-xs font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($p['title']) ?></p>
                            <?php if ((int)$p['submitted_ms'] > 0): ?>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 whitespace-nowrap"><?= htmlspecialchars(__('services.admin_dashboard.review_pending', ['count' => (int)$p['submitted_ms']])) ?></span>
                            <?php endif; ?>
                            <?php if ($p['status'] === 'lead'): ?>
                            <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 whitespace-nowrap"><?= htmlspecialchars(__('services.custom.st_lead')) ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-[10px] text-zinc-400 whitespace-nowrap"><?= htmlspecialchars(date('m/d', strtotime($p['created_at']))) ?></span>
                    </div>
                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5"><?= htmlspecialchars($userName) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 최근 활동 (2열) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- 최근 결제 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
                <p class="text-sm font-bold text-zinc-900 dark:text-white">💰 <?= htmlspecialchars(__('services.admin_dashboard.recent_payments')) ?></p>
            </div>
            <?php if (empty($paymentsList)): ?>
            <p class="p-6 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('services.admin_dashboard.no_payments')) ?></p>
            <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <?php foreach ($paymentsList as $pay):
                    $meta = json_decode($pay['metadata'] ?? '{}', true) ?: [];
                    $source = $meta['source'] ?? '';
                    $sourceLabel = match (true) {
                        str_contains($source, 'addon') => __('services.admin_dashboard.src_addon'),
                        str_contains($source, 'domain') => __('services.admin_dashboard.src_domain'),
                        str_contains($source, 'custom_project') => __('services.admin_dashboard.src_project'),
                        str_contains($source, 'hosting') || str_contains($source, 'service_order') => __('services.admin_dashboard.src_hosting'),
                        default => $source ?: '-',
                    };
                    $userName = $pay['user_name'] ? (decrypt($pay['user_name']) ?: $pay['user_name']) : '-';
                ?>
                <div class="p-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300 whitespace-nowrap"><?= htmlspecialchars($sourceLabel) ?></span>
                            <p class="text-xs text-zinc-700 dark:text-zinc-200 truncate"><?= htmlspecialchars($userName) ?></p>
                        </div>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 tabular-nums whitespace-nowrap">¥<?= number_format($pay['amount']) ?></span>
                    </div>
                    <p class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($pay['paid_at']))) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 최근 신규 의뢰 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
                <p class="text-sm font-bold text-zinc-900 dark:text-white">🆕 <?= htmlspecialchars(__('services.admin_dashboard.recent_requests')) ?></p>
            </div>
            <?php if (empty($requestsList)): ?>
            <p class="p-6 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('services.admin_dashboard.no_requests')) ?></p>
            <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <?php foreach ($requestsList as $req):
                    $userName = $req['user_name'] ? (decrypt($req['user_name']) ?: $req['user_name']) : '-';
                    $isProject = $req['kind'] === 'project';
                    $url = $isProject
                        ? ($adminUrl . '/custom-projects/' . (int)$req['id'])
                        : ($adminUrl . '/service-orders/' . htmlspecialchars($req['order_number']));
                    $kindLabel = $isProject ? __('services.admin_dashboard.kind_project') : __('services.admin_dashboard.kind_hosting');
                ?>
                <a href="<?= $url ?>" class="block p-3 hover:bg-gray-50 dark:hover:bg-zinc-700/30">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="text-[9px] px-1.5 py-0.5 rounded <?= $isProject ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' ?> whitespace-nowrap"><?= htmlspecialchars($kindLabel) ?></span>
                            <span class="text-[10px] font-mono text-zinc-400 whitespace-nowrap">#<?= htmlspecialchars($req['order_number']) ?></span>
                            <p class="text-xs text-zinc-700 dark:text-zinc-200 truncate"><?= htmlspecialchars($req['subject'] ?: '-') ?></p>
                        </div>
                        <span class="text-[10px] text-zinc-400 whitespace-nowrap"><?= htmlspecialchars(date('m/d', strtotime($req['created_at']))) ?></span>
                    </div>
                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5"><?= htmlspecialchars($userName) ?></p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 월별 매출 추이 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">📊 <?= htmlspecialchars(__('services.admin_dashboard.monthly_revenue')) ?></p>
        </div>
        <div class="p-5">
            <div class="flex items-end gap-2 h-40">
                <?php foreach ($monthlyRevenue as $month):
                    $heightPct = $maxMonthRevenue > 0 ? round($month['amount'] / $maxMonthRevenue * 100) : 0;
                ?>
                <div class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400 tabular-nums">¥<?= number_format($month['amount']/1000, 0) ?>k</span>
                    <div class="w-full bg-emerald-500 dark:bg-emerald-400 rounded-t hover:opacity-80 transition" style="height: <?= max($heightPct, 1) ?>%; min-height: 2px;" title="<?= htmlspecialchars($month['label']) ?>: ¥<?= number_format($month['amount']) ?>"></div>
                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($month['label']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
