<?php
/**
 * 관리자 — 도메인 관리
 *
 * 도메인 작업 요청서 일람:
 *   1. 서비스 신청서로 들어온 도메인 sub (type='domain' + registrar_pending/manual_attach_pending)
 *   2. 도메인 신청서(마이페이지 모달) 로 들어온 호스팅 sub.added_domains[]
 *
 * 컬럼: 사용자 / 도메인 / 옵션(신규·보유) / 신청일 / 상태 / 액션
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$pageTitle = __('services.admin_domains.page_title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __('services.admin_domains.header_title');
$pageSubTitle = __('services.admin_domains.sub_title');
$pageSubDesc = __('services.admin_domains.sub_desc');

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

// 필터
$filterStatus = $_GET['status'] ?? 'pending';  // pending|done|all
$filterOption = $_GET['option'] ?? '';  // ''|new|existing|free
$search = trim($_GET['q'] ?? '');

// 1. 서비스 신청서 — type='domain' subs
$domainSubs = [];
$st = $pdo->prepare("SELECT s.*, o.order_number, u.email AS user_email, u.name AS user_name
    FROM {$prefix}subscriptions s
    JOIN {$prefix}orders o ON s.order_id = o.id
    JOIN {$prefix}users u ON s.user_id = u.id
    WHERE s.type = 'domain' AND s.status IN ('paid','active','pending')
    ORDER BY s.created_at DESC");
$st->execute();
while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
    $meta = json_decode($r['metadata'] ?? '{}', true) ?: [];
    $isFree = !empty($meta['free_subdomain']);
    $isExisting = !empty($meta['existing']);
    $opt = $isFree ? 'free' : ($isExisting ? 'existing' : 'new');
    foreach ($meta['domains'] ?? [] as $dm) {
        $domainSubs[] = [
            'source' => 'order',
            'sub_id' => $r['id'],
            'host_sub_id' => null,
            'order_id' => $r['order_id'],
            'order_number' => $r['order_number'],
            'user_id' => $r['user_id'],
            'user_email' => $r['user_email'],
            'user_name' => $r['user_name'],
            'domain' => $dm,
            'option' => $opt,
            'started_at' => $r['started_at'],
            'expires_at' => $r['expires_at'],
            'requested_at' => $r['created_at'],
            'registrar_pending' => !empty($meta['registrar_pending']),
            'manual_attach_pending' => !empty($meta['manual_attach_pending']),
            'is_free' => $isFree,
            'is_existing' => $isExisting,
        ];
    }
}

// 2. 도메인 신청서 — 호스팅 sub.added_domains[]
$st2 = $pdo->prepare("SELECT s.*, o.order_number, u.email AS user_email, u.name AS user_name
    FROM {$prefix}subscriptions s
    JOIN {$prefix}orders o ON s.order_id = o.id
    JOIN {$prefix}users u ON s.user_id = u.id
    WHERE s.type = 'hosting' AND JSON_EXTRACT(s.metadata, '$.added_domains') IS NOT NULL
    ORDER BY s.id DESC");
$st2->execute();
while ($r = $st2->fetch(PDO::FETCH_ASSOC)) {
    $meta = json_decode($r['metadata'] ?? '{}', true) ?: [];
    foreach ($meta['added_domains'] ?? [] as $ad) {
        if (empty($ad['domain'])) continue;
        $domainSubs[] = [
            'source' => 'mypage',
            'sub_id' => null,
            'host_sub_id' => $r['id'],
            'order_id' => $r['order_id'],
            'order_number' => $r['order_number'],
            'user_id' => $r['user_id'],
            'user_email' => $r['user_email'],
            'user_name' => $r['user_name'],
            'domain' => $ad['domain'],
            'option' => $ad['option'] ?? 'new',
            'started_at' => $ad['started_at'] ?? null,
            'expires_at' => $ad['expires_at'] ?? null,
            'requested_at' => $ad['paid_at'] ?? $ad['started_at'] ?? null,
            'registrar_pending' => !empty($ad['registrar_pending']),
            'manual_attach_pending' => !empty($ad['manual_attach_pending']),
            'is_free' => ($ad['option'] ?? '') === 'free',
            'is_existing' => ($ad['option'] ?? '') === 'existing',
        ];
    }
}

// 필터 적용
$filtered = array_filter($domainSubs, function($d) use ($filterStatus, $filterOption, $search) {
    $isPending = $d['registrar_pending'] || $d['manual_attach_pending'];
    if ($filterStatus === 'pending' && !$isPending) return false;
    if ($filterStatus === 'done' && $isPending) return false;
    if ($filterOption !== '' && $d['option'] !== $filterOption) return false;
    if ($search !== '') {
        $hay = strtolower($d['domain'] . ' ' . $d['user_email'] . ' ' . $d['user_name'] . ' ' . $d['order_number']);
        if (strpos($hay, strtolower($search)) === false) return false;
    }
    return true;
});

usort($filtered, function($a, $b) {
    return strcmp($b['requested_at'] ?? '', $a['requested_at'] ?? '');
});

$totalCount = count($filtered);
$pendingCount = count(array_filter($domainSubs, fn($d) => $d['registrar_pending'] || $d['manual_attach_pending']));

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="px-4 sm:px-6 lg:px-8 py-6">
    <!-- 통계 카드 -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700">
            <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_domains.stats_total')) ?></p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= count($domainSubs) ?></p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_domains.stats_pending')) ?></p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-300"><?= $pendingCount ?></p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <p class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_domains.stats_done')) ?></p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-300"><?= count($domainSubs) - $pendingCount ?></p>
        </div>
    </div>

    <!-- 필터 -->
    <form method="GET" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700 mb-4 flex flex-wrap items-center gap-2">
        <select name="status" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.admin_domains.filter_pending')) ?></option>
            <option value="done" <?= $filterStatus === 'done' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.admin_domains.filter_done')) ?></option>
            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.admin_domains.filter_all')) ?></option>
        </select>
        <select name="option" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            <option value=""><?= htmlspecialchars(__('services.admin_domains.opt_all')) ?></option>
            <option value="new" <?= $filterOption === 'new' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.detail.dm_modal_opt_new')) ?></option>
            <option value="existing" <?= $filterOption === 'existing' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.detail.dm_modal_opt_existing')) ?></option>
            <option value="free" <?= $filterOption === 'free' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.detail.dm_modal_opt_free')) ?></option>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="<?= htmlspecialchars(__('services.admin_domains.search_placeholder')) ?>" class="flex-1 max-w-md px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        <button type="submit" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= htmlspecialchars(__('services.admin_domains.btn_filter')) ?></button>
    </form>

    <!-- 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.admin_domains.list_title', ['count' => $totalCount])) ?></p>
        </div>
        <?php if (empty($filtered)): ?>
        <div class="p-12 text-center text-sm text-zinc-400"><?= htmlspecialchars(__('services.admin_domains.empty')) ?></div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-zinc-700/50">
                    <tr class="text-[10px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_user')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_domain')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_option')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_source')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_order')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_requested_at')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_status')) ?></th>
                        <th class="px-4 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_domains.col_action')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                    <?php foreach ($filtered as $d):
                        $isPending = $d['registrar_pending'] || $d['manual_attach_pending'];
                    ?>
                    <tr class="<?= $isPending ? 'bg-amber-50/30 dark:bg-amber-900/10' : '' ?>">
                        <td class="px-4 py-2.5">
                            <?php $_dispName = decrypt($d['user_name'] ?: '') ?: ($d['user_name'] ?: '-'); ?>
                            <p class="font-medium text-zinc-900 dark:text-white truncate max-w-[150px]" title="<?= htmlspecialchars($_dispName) ?>"><?= htmlspecialchars($_dispName) ?></p>
                            <p class="text-[10px] text-zinc-400 truncate max-w-[150px]"><?= htmlspecialchars($d['user_email']) ?></p>
                        </td>
                        <td class="px-4 py-2.5 font-mono font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($d['domain']) ?></td>
                        <td class="px-4 py-2.5">
                            <?php if ($d['option'] === 'new'): ?>
                            <span class="px-2 py-0.5 text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded"><?= htmlspecialchars(__('services.detail.dm_modal_opt_new')) ?></span>
                            <?php elseif ($d['option'] === 'existing'): ?>
                            <span class="px-2 py-0.5 text-[10px] font-medium bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400 rounded"><?= htmlspecialchars(__('services.detail.dm_modal_opt_existing')) ?></span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 text-[10px] font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded"><?= htmlspecialchars(__('services.detail.dm_modal_opt_free')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5 text-zinc-500">
                            <?= htmlspecialchars($d['source'] === 'order' ? __('services.admin_domains.source_order') : __('services.admin_domains.source_mypage')) ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <a href="<?= htmlspecialchars($adminUrl) ?>/service-orders/<?= htmlspecialchars($d['order_number']) ?>" class="font-mono text-[11px] text-blue-600 hover:underline"><?= htmlspecialchars($d['order_number']) ?></a>
                        </td>
                        <td class="px-4 py-2.5 text-[11px] text-zinc-500">
                            <?= $d['requested_at'] ? date('Y-m-d H:i', strtotime($d['requested_at'])) : '-' ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <?php if ($d['registrar_pending']): ?>
                            <span class="px-2 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded"><?= htmlspecialchars(__('services.detail.dom_registrar_pending')) ?></span>
                            <?php elseif ($d['manual_attach_pending']): ?>
                            <span class="px-2 py-0.5 text-[10px] font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded"><?= htmlspecialchars(__('services.detail.dom_attach_pending')) ?></span>
                            <?php else: ?>
                            <span class="px-2 py-0.5 text-[10px] font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded">✓ <?= htmlspecialchars(__('services.admin_domains.status_done')) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-2.5">
                            <?php if ($isPending): ?>
                            <div class="flex items-center gap-1.5">
                                <button type="button"
                                    onclick="markDomainDone(<?= $d['source'] === 'mypage' ? "'mypage'" : "'order'" ?>, <?= (int)($d['host_sub_id'] ?? 0) ?>, <?= (int)($d['sub_id'] ?? 0) ?>, '<?= htmlspecialchars($d['domain']) ?>')"
                                    class="px-2.5 py-1 text-[11px] font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded">
                                    <?= htmlspecialchars(__('services.admin_domains.btn_mark_done')) ?>
                                </button>
                                <button type="button"
                                    onclick="cancelDomain(<?= $d['source'] === 'mypage' ? "'mypage'" : "'order'" ?>, <?= (int)($d['host_sub_id'] ?? 0) ?>, <?= (int)($d['sub_id'] ?? 0) ?>, '<?= htmlspecialchars($d['domain']) ?>')"
                                    class="px-2.5 py-1 text-[11px] font-medium text-white bg-red-600 hover:bg-red-700 rounded">
                                    <?= htmlspecialchars(__('services.admin_domains.btn_cancel')) ?>
                                </button>
                            </div>
                            <?php else: ?>
                            <span class="text-[11px] text-zinc-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
var siteBaseUrl = <?= json_encode($baseUrl) ?>;
function markDomainDone(source, hostSubId, domainSubId, domain) {
    if (!confirm(<?= json_encode(__('services.admin_domains.confirm_mark_done'), JSON_UNESCAPED_UNICODE) ?>.replace(':domain', domain))) return;
    var payload = { action: 'admin_domain_complete', source: source, domain: domain };
    if (hostSubId) payload.host_sub_id = hostSubId;
    if (domainSubId) payload.domain_sub_id = domainSubId;
    fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(d => {
        if (d.success) { alert(<?= json_encode(__('services.admin_domains.alert_done'), JSON_UNESCAPED_UNICODE) ?>); location.reload(); }
        else alert(d.message || 'Failed');
    });
}
function cancelDomain(source, hostSubId, domainSubId, domain) {
    if (!confirm(<?= json_encode(__('services.admin_domains.confirm_cancel'), JSON_UNESCAPED_UNICODE) ?>.replace(':domain', domain))) return;
    var payload = { action: 'admin_domain_cancel', source: source, domain: domain };
    if (hostSubId) payload.host_sub_id = hostSubId;
    if (domainSubId) payload.domain_sub_id = domainSubId;
    fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload)
    }).then(r => r.json()).then(d => {
        if (d.success) {
            alert(<?= json_encode(__('services.admin_domains.alert_cancelled'), JSON_UNESCAPED_UNICODE) ?>.replace(':amount', d.refunded || 0));
            location.reload();
        } else {
            alert(d.message || 'Failed');
        }
    });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
