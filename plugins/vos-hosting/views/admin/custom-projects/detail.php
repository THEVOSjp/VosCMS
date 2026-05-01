<?php
/**
 * 관리자 — 제작 프로젝트 상세 + 견적서 작성/발행
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

$projectId = $adminCustomProjectId ?? 0;
$pSt = $pdo->prepare("SELECT p.*, u.email AS user_email, u.name AS user_name FROM {$prefix}custom_projects p LEFT JOIN {$prefix}users u ON p.user_id = u.id WHERE p.id = ?");
$pSt->execute([$projectId]);
$project = $pSt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    http_response_code(404);
    echo '<div class="p-12 text-center"><p class="text-zinc-400">' . htmlspecialchars(__('services.custom.not_found')) . '</p></div>';
    return;
}
$project['attachments'] = json_decode($project['attachments'] ?? '[]', true) ?: [];
$project['user_name_decrypted'] = $project['user_name'] ? (decrypt($project['user_name']) ?: $project['user_name']) : '-';

$qSt = $pdo->prepare("SELECT * FROM {$prefix}custom_quotes WHERE project_id = ? ORDER BY version DESC, id DESC");
$qSt->execute([$projectId]);
$quotes = $qSt->fetchAll(PDO::FETCH_ASSOC);
foreach ($quotes as &$_q) {
    $_q['items'] = json_decode($_q['items'] ?? '[]', true) ?: [];
    $_qpSt = $pdo->prepare("SELECT id, sequence_no, label, amount, currency, due_date, status, paid_at, note FROM {$prefix}custom_project_payments WHERE quote_id = ? ORDER BY sequence_no ASC, id ASC");
    $_qpSt->execute([$_q['id']]);
    $_q['payments'] = $_qpSt->fetchAll(PDO::FETCH_ASSOC);
}
unset($_q);

// 프로젝트 채널 ticket
$_channelTicket = null;
try {
    $_chSt = $pdo->prepare("SELECT id, status FROM {$prefix}support_tickets WHERE custom_project_id = ? LIMIT 1");
    $_chSt->execute([$projectId]);
    $_channelTicket = $_chSt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (\Throwable $e) {}

// 마일스톤
$_milestones = [];
try {
    $_msSt = $pdo->prepare("SELECT * FROM {$prefix}custom_milestones WHERE project_id = ? ORDER BY sequence_no ASC, id ASC");
    $_msSt->execute([$projectId]);
    $_milestones = $_msSt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($_milestones as &$_m) { $_m['attachments'] = json_decode($_m['attachments'] ?? '[]', true) ?: []; }
    unset($_m);
} catch (\Throwable $e) {}
$_msTotal = count($_milestones);
$_msApproved = count(array_filter($_milestones, fn($m) => $m['status'] === 'approved'));
$_msProgress = $_msTotal > 0 ? round($_msApproved / $_msTotal * 100) : 0;


$pageTitle = $project['project_number'] . ' — ' . $project['title'];
$pageHeaderTitle = __('services.admin_custom.detail_header');
$pageSubTitle = '#' . $project['project_number'];
$pageSubDesc = $project['title'];

$siteTypeLabel = match ($project['site_type']) {
    'shop' => __('services.custom.site_shop'),
    'reservation' => __('services.custom.site_reservation'),
    'other' => __('services.custom.site_other'),
    default => __('services.custom.site_homepage'),
};
$budgetLabel = match ($project['budget_range']) {
    'lt100k' => __('services.custom.budget_lt100k'),
    '100k_300k' => __('services.custom.budget_100k_300k'),
    '300k_1m' => __('services.custom.budget_300k_1m'),
    'gt1m' => __('services.custom.budget_gt1m'),
    'discuss' => __('services.custom.budget_discuss'),
    default => '-',
};

// 연결 호스팅 정보
$_linkedHost = null;
if (!empty($project['linked_host_subscription_id'])) {
    $_lhSt = $pdo->prepare("SELECT s.id, s.label, o.domain, o.order_number FROM {$prefix}subscriptions s LEFT JOIN {$prefix}orders o ON s.order_id = o.id WHERE s.id = ?");
    $_lhSt->execute([$project['linked_host_subscription_id']]);
    $_linkedHost = $_lhSt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$domainOptionLabel = match ($project['domain_option']) {
    'addon' => __('services.custom.dom_addon'),
    'new' => __('services.custom.dom_new'),
    'existing' => __('services.custom.dom_existing'),
    'free' => __('services.custom.dom_free'),
    'discuss' => __('services.custom.dom_discuss'),
    default => '-',
};

$statusOpts = [
    'lead' => __('services.custom.st_lead'),
    'quoted' => __('services.custom.st_quoted'),
    'contracted' => __('services.custom.st_contracted'),
    'in_progress' => __('services.custom.st_in_progress'),
    'review' => __('services.custom.st_review'),
    'delivered' => __('services.custom.st_delivered'),
    'maintenance' => __('services.custom.st_maintenance'),
    'cancelled' => __('services.custom.st_cancelled'),
];

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="px-4 sm:px-6 lg:px-8 py-6 max-w-6xl mx-auto">
    <div class="mb-4">
        <a href="<?= $adminUrl ?>/custom-projects" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
            ← <?= htmlspecialchars(__('services.admin_custom.back_to_board')) ?>
        </a>
    </div>

    <!-- 헤더 + 상태 변경 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5 mb-4">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex-1 min-w-0">
                <p class="text-[10px] font-mono text-zinc-400 mb-1">#<?= htmlspecialchars($project['project_number']) ?></p>
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($project['title']) ?></h1>
                <p class="text-xs text-zinc-500 mt-1">
                    <span class="font-medium"><?= htmlspecialchars($project['user_name_decrypted']) ?></span>
                    · <?= htmlspecialchars($project['user_email']) ?>
                    · <?= htmlspecialchars(date('Y-m-d H:i', strtotime($project['created_at']))) ?>
                </p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <select id="statusSelect" class="px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg">
                    <?php foreach ($statusOpts as $sk => $sv): ?>
                    <option value="<?= $sk ?>" <?= $project['status'] === $sk ? 'selected' : '' ?>><?= htmlspecialchars($sv) ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="updateStatus()" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                    <?= htmlspecialchars(__('services.admin_custom.btn_update_status')) ?>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- 의뢰 내용 -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.custom.section_request')) ?></p>
                </div>
                <div class="p-5 space-y-3 text-sm">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_site_type')) ?></p>
                            <p class="text-zinc-900 dark:text-white"><?= htmlspecialchars($siteTypeLabel) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_budget')) ?></p>
                            <p class="text-zinc-900 dark:text-white"><?= htmlspecialchars($budgetLabel) ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_due_date')) ?></p>
                            <p class="text-zinc-900 dark:text-white"><?= $project['desired_due_date'] ? htmlspecialchars($project['desired_due_date']) : '-' ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_contact_hours')) ?></p>
                            <p class="text-zinc-900 dark:text-white"><?= htmlspecialchars($project['contact_hours'] ?: '-') ?></p>
                        </div>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.custom.f_requirements')) ?></p>
                        <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-3 text-xs whitespace-pre-wrap text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($project['requirements']) ?></div>
                    </div>
                    <?php if ($project['reference_urls']): ?>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.custom.f_reference_urls')) ?></p>
                        <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-3 text-xs whitespace-pre-wrap font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($project['reference_urls']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($project['attachments'])): ?>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1">📎 <?= htmlspecialchars(__('services.custom.f_attachments')) ?></p>
                        <div class="space-y-1">
                        <?php foreach ($project['attachments'] as $idx => $a): ?>
                            <div class="flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600">
                                <svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="truncate flex-1 text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($a['name']) ?></span>
                                <span class="text-zinc-400"><?= number_format($a['size']/1024, 1) ?> KB</span>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 도메인/호스팅 정보 -->
            <?php if ($project['domain_option'] || $_linkedHost || $project['need_new_hosting']): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">🌐 <?= htmlspecialchars(__('services.custom.section_domain')) ?></p>
                </div>
                <div class="p-5 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_domain_option')) ?></p>
                        <p class="text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($domainOptionLabel) ?></p>
                    </div>
                    <?php if ($project['domain_name']): ?>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_domain_name')) ?></p>
                        <p class="text-zinc-900 dark:text-white font-mono"><?= htmlspecialchars($project['domain_name']) ?></p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.custom.f_hosting_target')) ?></p>
                        <?php if ($_linkedHost): ?>
                        <p class="text-zinc-900 dark:text-white">
                            <a href="<?= $adminUrl ?>/service-orders/<?= htmlspecialchars($_linkedHost['order_number']) ?>" class="text-blue-600 hover:underline">
                                <?= htmlspecialchars($_linkedHost['domain'] ?: $_linkedHost['order_number']) ?>
                            </a>
                        </p>
                        <?php elseif ($project['need_new_hosting']): ?>
                        <p class="text-amber-600 dark:text-amber-400 font-medium">🆕 <?= htmlspecialchars(__('services.custom.host_target_new')) ?></p>
                        <?php else: ?>
                        <p class="text-zinc-400">-</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 프로젝트 채널 -->
            <?php if ($_channelTicket): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700" id="projectChannel">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white">💬 <?= htmlspecialchars(__('services.custom.section_channel')) ?></p>
                    <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.custom.channel_desc_admin')) ?></span>
                </div>
                <div id="channelMessages" class="p-5 space-y-3 max-h-[400px] overflow-y-auto">
                    <p class="text-xs text-zinc-400 text-center"><?= htmlspecialchars(__('services.custom.channel_loading')) ?></p>
                </div>
                <div class="px-5 py-4 border-t border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-700/30 rounded-b-xl">
                    <textarea id="channelReplyBody" rows="3" class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.channel_reply_ph_admin')) ?>"></textarea>
                    <div class="mt-2 flex items-center justify-between gap-2">
                        <input type="file" id="channelReplyFiles" multiple class="text-[11px] text-zinc-600 dark:text-zinc-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                        <button type="button" id="channelReplyBtn" onclick="channelSubmit()" class="px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                            <?= htmlspecialchars(__('services.custom.channel_btn_send')) ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 견적서 리스트 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                    <p class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.custom.section_quotes')) ?></p>
                    <button onclick="openQuoteEditor(0)" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                        + <?= htmlspecialchars(__('services.admin_custom.btn_new_quote')) ?>
                    </button>
                </div>
                <div class="p-5">
                    <?php if (empty($quotes)): ?>
                    <p class="text-sm text-zinc-400 text-center py-6"><?= htmlspecialchars(__('services.admin_custom.quotes_empty')) ?></p>
                    <?php else: foreach ($quotes as $q):
                        $qBg = match ($q['status']) {
                            'draft' => 'bg-gray-50 dark:bg-zinc-700/20 border-gray-300 dark:border-zinc-600',
                            'sent' => 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800',
                            'accepted' => 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-300 dark:border-emerald-700',
                            'rejected' => 'bg-red-50 dark:bg-red-900/10 border-red-300 dark:border-red-700',
                            'superseded' => 'bg-gray-50 dark:bg-zinc-700/20 border-gray-200 dark:border-zinc-600 opacity-60',
                            default => 'bg-gray-50 border-gray-200',
                        };
                        $qStatusLabel = match ($q['status']) {
                            'draft' => __('services.admin_custom.q_draft'),
                            'sent' => __('services.custom.q_sent'),
                            'accepted' => __('services.custom.q_accepted'),
                            'rejected' => __('services.custom.q_rejected'),
                            'superseded' => __('services.custom.q_superseded'),
                            default => $q['status'],
                        };
                    ?>
                    <div class="border <?= $qBg ?> rounded-lg p-4 mb-3 last:mb-0">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-zinc-900 dark:text-white">v<?= $q['version'] ?></span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-white dark:bg-zinc-700 text-zinc-800 dark:text-emerald-200 border border-current"><?= htmlspecialchars($qStatusLabel) ?></span>
                                <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(date('Y-m-d', strtotime($q['created_at']))) ?></span>
                            </div>
                            <div class="flex items-center gap-1">
                                <?php if ($q['status'] === 'draft'): ?>
                                <button onclick='openQuoteEditor(<?= json_encode($q) ?>)' class="px-2 py-1 text-[11px] text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded">
                                    <?= htmlspecialchars(__('services.admin_custom.btn_edit')) ?>
                                </button>
                                <button onclick="sendQuote(<?= (int)$q['id'] ?>)" class="px-3 py-1 text-[11px] font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded">
                                    📤 <?= htmlspecialchars(__('services.admin_custom.btn_send')) ?>
                                </button>
                                <?php endif; ?>
                                <button onclick='openQuoteView(<?= json_encode($q) ?>)' class="px-3 py-1 text-[11px] font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded">
                                    👁 <?= htmlspecialchars(__('services.admin_custom.btn_view')) ?>
                                </button>
                                <button onclick='openQuotePrint(<?= json_encode($q) ?>)' class="px-3 py-1 text-[11px] font-medium bg-gray-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-200 hover:bg-gray-200 dark:hover:bg-zinc-600 rounded">
                                    🖨 <?= htmlspecialchars(__('services.admin_custom.btn_print')) ?>
                                </button>
                            </div>
                        </div>
                        <div class="text-xs text-zinc-700 dark:text-emerald-100">
                            <?= count($q['items']) ?> <?= htmlspecialchars(__('services.admin_custom.items_unit')) ?>
                            · ¥<?= number_format($q['total']) ?>
                            <?php if ($q['valid_until']): ?>
                            · <?= htmlspecialchars(__('services.custom.q_valid_until', ['date' => $q['valid_until']])) ?>
                            <?php endif; ?>
                        </div>
                        <?php
                        $_qPays = $q['payments'] ?? [];
                        if (!empty($_qPays)):
                            $_paidSum = 0; $_totalSum = 0;
                            foreach ($_qPays as $_p) {
                                $_totalSum += (float)$_p['amount'];
                                if ($_p['status'] === 'paid') $_paidSum += (float)$_p['amount'];
                            }
                            $_progress = $_totalSum > 0 ? round($_paidSum / $_totalSum * 100) : 0;
                        ?>
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-600">
                            <div class="flex items-center justify-between text-[11px] mb-1">
                                <span class="text-zinc-500 dark:text-emerald-300/70"><?= htmlspecialchars(__('services.admin_custom.q_payments_label')) ?> (<?= count($_qPays) ?>)</span>
                                <span class="font-medium text-zinc-700 dark:text-emerald-100"><?= htmlspecialchars(__('services.admin_custom.q_payments_paid', ['paid' => '¥' . number_format($_paidSum), 'total' => '¥' . number_format($_totalSum)])) ?></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-1.5 mb-2">
                                <div class="bg-emerald-500 h-1.5 rounded-full transition-all" style="width: <?= $_progress ?>%"></div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                            <?php foreach ($_qPays as $_p):
                                $_pCls = match ($_p['status']) {
                                    'paid' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                                    'cancelled' => 'bg-gray-50 dark:bg-zinc-700/30 text-zinc-400 line-through',
                                    default => 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-300',
                                };
                            ?>
                                <div class="flex items-center justify-between gap-2 text-[11px] px-2 py-1 rounded <?= $_pCls ?>">
                                    <span class="truncate"><?= htmlspecialchars($_p['label']) ?><?php if ($_p['due_date']): ?> <span class="text-[10px] opacity-60">(<?= htmlspecialchars($_p['due_date']) ?>)</span><?php endif; ?></span>
                                    <span class="tabular-nums whitespace-nowrap">¥<?= number_format($_p['amount']) ?></span>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($q['status'] === 'rejected' && $q['reject_reason']): ?>
                        <p class="mt-2 text-[11px] text-red-600"><?= htmlspecialchars(__('services.custom.q_rejected_reason', ['reason' => $q['reject_reason']])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

            <!-- 마일스톤 / 시안 검수 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <p class="text-sm font-bold text-zinc-900 dark:text-white">📋 <?= htmlspecialchars(__('services.admin_custom.section_milestones')) ?></p>
                        <?php if ($_msTotal > 0): ?>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= $_msApproved ?>/<?= $_msTotal ?> · <?= $_msProgress ?>%</span>
                        <?php endif; ?>
                    </div>
                    <button onclick="openMilestoneNew()" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                        + <?= htmlspecialchars(__('services.admin_custom.btn_new_milestone')) ?>
                    </button>
                </div>
                <?php if ($_msTotal > 0): ?>
                <div class="px-5 pt-3">
                    <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: <?= $_msProgress ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
                <div class="p-5 space-y-3">
                    <?php if (empty($_milestones)): ?>
                    <p class="text-sm text-zinc-400 text-center py-6"><?= htmlspecialchars(__('services.admin_custom.milestones_empty')) ?></p>
                    <?php else: foreach ($_milestones as $_ms):
                        $_msCls = match ($_ms['status']) {
                            'pending' => 'bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-600',
                            'in_progress' => 'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800',
                            'submitted' => 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800',
                            'approved' => 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800',
                            'revision_requested' => 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800',
                            'cancelled' => 'bg-gray-50 dark:bg-zinc-700/20 border-gray-200 opacity-60',
                            default => 'bg-gray-50 border-gray-200',
                        };
                        $_msStatusLabel = match ($_ms['status']) {
                            'pending' => __('services.custom.ms_pending'),
                            'in_progress' => __('services.custom.ms_in_progress'),
                            'submitted' => __('services.custom.ms_submitted'),
                            'approved' => __('services.custom.ms_approved'),
                            'revision_requested' => __('services.custom.ms_revision'),
                            'cancelled' => __('services.custom.ms_cancelled'),
                            default => $_ms['status'],
                        };
                    ?>
                    <div class="border <?= $_msCls ?> rounded-lg p-4" id="milestone-<?= (int)$_ms['id'] ?>">
                        <div class="flex items-start justify-between gap-2 mb-2 flex-wrap">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-[11px] font-mono text-zinc-400">#<?= (int)$_ms['sequence_no'] ?></span>
                                <p class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_ms['title']) ?></p>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 border border-current"><?= htmlspecialchars($_msStatusLabel) ?></span>
                                <?php if ($_ms['due_date']): ?>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($_ms['due_date']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center gap-1">
                                <button onclick='openMilestoneEdit(<?= json_encode($_ms) ?>)' class="text-[11px] px-2 py-1 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded"><?= htmlspecialchars(__('services.admin_custom.btn_edit')) ?></button>
                                <?php if (in_array($_ms['status'], ['pending','cancelled'], true)): ?>
                                <button onclick="deleteMilestone(<?= (int)$_ms['id'] ?>)" class="text-[11px] px-2 py-1 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">×</button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($_ms['description']): ?>
                        <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap mb-2"><?= htmlspecialchars($_ms['description']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($_ms['attachments'])): ?>
                        <div class="mb-2 space-y-1">
                            <p class="text-[10px] text-zinc-400 uppercase">📎 <?= htmlspecialchars(__('services.admin_custom.ms_attachments')) ?></p>
                            <?php foreach ($_ms['attachments'] as $_idx => $_a):
                                $_isImg = preg_match('/^(jpg|jpeg|png|gif|webp|svg)$/i', $_a['ext']);
                            ?>
                            <div class="flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700/50 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600">
                                <svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                <span class="truncate flex-1 text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($_a['name']) ?></span>
                                <span class="text-zinc-400"><?= number_format($_a['size']/1024, 1) ?> KB</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($_ms['status'] === 'revision_requested' && $_ms['revision_note']): ?>
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-2 mb-2">
                            <p class="text-[10px] text-red-700 dark:text-red-300 font-bold mb-1">📝 <?= htmlspecialchars(__('services.admin_custom.ms_revision_note')) ?></p>
                            <p class="text-xs text-red-700 dark:text-red-200 whitespace-pre-wrap"><?= htmlspecialchars($_ms['revision_note']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($_ms['status'] === 'approved' && $_ms['approval_note']): ?>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-300">✓ <?= htmlspecialchars($_ms['approval_note']) ?></p>
                        <?php endif; ?>
                        <?php if (in_array($_ms['status'], ['pending','in_progress','revision_requested'], true)): ?>
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-600">
                            <button onclick='openMilestoneSubmit(<?= json_encode($_ms) ?>)' class="px-3 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">
                                📤 <?= htmlspecialchars(__('services.admin_custom.btn_submit_milestone')) ?>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- 사이드바 — 관리 패널 -->
        <div class="space-y-4">
            <!-- 계약 정보 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-4">
                <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-3"><?= htmlspecialchars(__('services.admin_custom.section_contract')) ?></p>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-zinc-400"><?= htmlspecialchars(__('services.admin_custom.contract_amount')) ?></span>
                        <span class="font-bold text-emerald-600"><?= $project['contract_amount'] ? '¥' . number_format($project['contract_amount']) : '-' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-400"><?= htmlspecialchars(__('services.admin_custom.estimated_amount')) ?></span>
                        <span class="text-zinc-700 dark:text-zinc-200"><?= $project['estimated_amount'] ? '¥' . number_format($project['estimated_amount']) : '-' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-400"><?= htmlspecialchars(__('services.admin_custom.started_at')) ?></span>
                        <span class="text-zinc-700 dark:text-zinc-200"><?= $project['started_at'] ? htmlspecialchars(date('Y-m-d', strtotime($project['started_at']))) : '-' ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-400"><?= htmlspecialchars(__('services.admin_custom.delivered_at')) ?></span>
                        <span class="text-zinc-700 dark:text-zinc-200"><?= $project['delivered_at'] ? htmlspecialchars(date('Y-m-d', strtotime($project['delivered_at']))) : '-' ?></span>
                    </div>
                </div>
            </div>

            <!-- 내부 메모 -->
            <div class="bg-emerald-50 dark:bg-emerald-900/10 rounded-xl border border-emerald-200 dark:border-emerald-800 p-4">
                <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 mb-2">📝 <?= htmlspecialchars(__('services.admin_custom.section_admin_note')) ?></p>
                <textarea id="adminNote" rows="6" class="w-full px-2 py-1.5 text-xs border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-zinc-800 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded" placeholder="<?= htmlspecialchars(__('services.admin_custom.admin_note_ph')) ?>"><?= htmlspecialchars($project['admin_note'] ?? '') ?></textarea>
                <button onclick="saveAdminNote()" class="mt-2 w-full px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded">
                    <?= htmlspecialchars(__('services.admin_custom.btn_save_note')) ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 견적서 편집 모달 -->
<style>
/* 견적 모달 입력 요소 — !important 로 user-agent stylesheet 강제 override */
.dark #quoteModal input,
.dark #quoteModal select,
.dark #quoteModal textarea {
    color: #ffffff !important;
    background-color: #3f3f46 !important;
    color-scheme: dark;
}
.dark #quoteModal input::placeholder,
.dark #quoteModal textarea::placeholder {
    color: #a1a1aa !important;
    opacity: 1 !important;
}
.dark #quoteModal select option {
    color: #ffffff !important;
    background-color: #3f3f46 !important;
}
/* 모달 내 텍스트 기본값을 흰색으로 (zinc-400/500 같이 명시된 회색 색상은 그대로 유지) */
.dark #quoteModal {
    color: #f4f4f5;
}
</style>
<div id="quoteModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeQuoteEditor()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_custom.quote_editor_title')) ?></h3>
            <button type="button" onclick="closeQuoteEditor()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="qe_quote_id" value="0">

            <?php if ($project['domain_option'] || $_linkedHost || $project['need_new_hosting']): ?>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg px-3 py-2 text-xs">
                <p class="font-bold text-blue-800 dark:text-blue-200 mb-1">🌐 <?= htmlspecialchars(__('services.admin_custom.qe_domain_hint')) ?></p>
                <p class="text-blue-700 dark:text-blue-300">
                    <?= htmlspecialchars(__('services.custom.f_domain_option')) ?>: <strong><?= htmlspecialchars($domainOptionLabel) ?></strong>
                    <?php if ($project['domain_name']): ?> · <span class="font-mono"><?= htmlspecialchars($project['domain_name']) ?></span><?php endif; ?>
                    <?php if ($_linkedHost): ?> · <?= htmlspecialchars(__('services.custom.f_hosting_target')) ?>: <strong><?= htmlspecialchars($_linkedHost['domain'] ?: $_linkedHost['order_number']) ?></strong><?php endif; ?>
                    <?php if ($project['need_new_hosting']): ?> · <strong class="text-amber-600 dark:text-amber-300">🆕 <?= htmlspecialchars(__('services.custom.host_target_new')) ?></strong><?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <div id="qe_items" class="space-y-2"></div>
            <button type="button" onclick="addQuoteItem()" class="w-full px-3 py-2 text-xs font-medium text-blue-600 border border-dashed border-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg">
                + <?= htmlspecialchars(__('services.admin_custom.btn_add_item')) ?>
            </button>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.qe_tax_rate')) ?></label>
                    <input type="number" id="qe_tax_rate" value="10" min="0" max="100" step="0.1" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.qe_valid_until')) ?></label>
                    <input type="date" id="qe_valid_until" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.qe_currency')) ?></label>
                    <select id="qe_currency" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg">
                        <option value="JPY">JPY (¥)</option>
                        <option value="USD">USD ($)</option>
                        <option value="KRW">KRW (₩)</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.qe_notes')) ?></label>
                <textarea id="qe_notes" rows="3" class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_custom.qe_notes_ph')) ?>"></textarea>
            </div>

            <!-- 결제 일정 -->
            <div class="border-t border-gray-200 dark:border-zinc-700 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-xs font-bold text-zinc-700 dark:text-zinc-200">💰 <?= htmlspecialchars(__('services.admin_custom.qe_payments_title')) ?></label>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="setPaymentPreset('lump')" class="text-[11px] px-2 py-1 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 rounded"><?= htmlspecialchars(__('services.admin_custom.qe_preset_lump')) ?></button>
                        <button type="button" onclick="setPaymentPreset('split2')" class="text-[11px] px-2 py-1 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 rounded"><?= htmlspecialchars(__('services.admin_custom.qe_preset_split2')) ?></button>
                        <button type="button" onclick="setPaymentPreset('split3')" class="text-[11px] px-2 py-1 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 rounded"><?= htmlspecialchars(__('services.admin_custom.qe_preset_split3')) ?></button>
                    </div>
                </div>
                <p class="text-[10px] text-zinc-400 mb-2"><?= htmlspecialchars(__('services.admin_custom.qe_payments_hint')) ?></p>
                <div id="qe_payments" class="space-y-2"></div>
                <div class="flex items-center justify-between mt-2 text-[11px]">
                    <button type="button" onclick="addPayment()" class="text-blue-600 hover:underline">+ <?= htmlspecialchars(__('services.admin_custom.qe_add_payment')) ?></button>
                    <div>
                        <span class="text-zinc-500"><?= htmlspecialchars(__('services.admin_custom.qe_payments_sum')) ?>:</span>
                        <span id="qe_payments_sum" class="tabular-nums ml-1 text-zinc-700 dark:text-zinc-100">¥0</span>
                        <span class="text-zinc-400">/</span>
                        <span id="qe_payments_target" class="tabular-nums text-zinc-700 dark:text-zinc-100">¥0</span>
                        <span id="qe_payments_match" class="ml-1"></span>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-3 text-xs">
                <div class="flex justify-between"><span class="text-zinc-500"><?= htmlspecialchars(__('services.custom.q_subtotal')) ?></span><span id="qe_subtotal" class="tabular-nums text-zinc-700 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between mt-1"><span class="text-zinc-500"><?= htmlspecialchars(__('services.admin_custom.qe_tax_amount')) ?></span><span id="qe_tax_amt" class="tabular-nums text-zinc-700 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between mt-1 pt-2 border-t border-gray-300 dark:border-zinc-600 font-bold text-zinc-900 dark:text-white"><span><?= htmlspecialchars(__('services.custom.q_total')) ?></span><span id="qe_total" class="tabular-nums text-base">¥0</span></div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2">
            <button type="button" onclick="closeQuoteEditor()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="qe_save" onclick="saveQuote()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                <?= htmlspecialchars(__('services.admin_custom.btn_save_draft')) ?>
            </button>
        </div>
    </div>
</div>

<script>
var siteBaseUrl = <?= json_encode($baseUrl) ?>;
var projectId = <?= (int)$project['id'] ?>;

function api(action, payload) {
    payload = payload || {}; payload.action = action;
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/custom-project.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    }).then(function(r){ return r.json(); });
}

function updateStatus() {
    var newStatus = document.getElementById('statusSelect').value;
    var payload = { project_id: projectId, status: newStatus };
    if (newStatus === 'cancelled') {
        var r = prompt(<?= json_encode(__('services.admin_custom.prompt_cancel_reason'), JSON_UNESCAPED_UNICODE) ?>);
        if (r === null) return;
        payload.cancel_reason = r;
    }
    api('project_update_status', payload).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}

function saveAdminNote() {
    api('project_admin_note', { project_id: projectId, note: document.getElementById('adminNote').value }).then(function(d) {
        if (d.success) alert(<?= json_encode(__('services.admin_custom.note_saved'), JSON_UNESCAPED_UNICODE) ?>);
        else alert(d.message || 'error');
    });
}

// 견적 편집기
var qeItems = [];

var qePayments = [];

function openQuoteEditor(quoteOrZero) {
    qeItems = [];
    qePayments = [];
    document.getElementById('qe_tax_rate').value = 10;
    document.getElementById('qe_valid_until').value = '';
    document.getElementById('qe_notes').value = '';
    document.getElementById('qe_currency').value = 'JPY';
    document.getElementById('qe_quote_id').value = 0;

    if (quoteOrZero && typeof quoteOrZero === 'object') {
        document.getElementById('qe_quote_id').value = quoteOrZero.id || 0;
        qeItems = (quoteOrZero.items || []).map(function(it){ return {label:it.label||'', qty:it.qty||1, unit_price:it.unit_price||0, note:it.note||''}; });
        document.getElementById('qe_tax_rate').value = parseFloat(quoteOrZero.tax_rate) || 10;
        document.getElementById('qe_valid_until').value = quoteOrZero.valid_until || '';
        document.getElementById('qe_notes').value = quoteOrZero.notes || '';
        document.getElementById('qe_currency').value = quoteOrZero.currency || 'JPY';
        qePayments = (quoteOrZero.payments || []).map(function(p){
            return {label:p.label||'', amount:parseFloat(p.amount)||0, due_date:p.due_date||'', note:p.note||'', status:p.status||'pending'};
        });
    }
    if (qeItems.length === 0) qeItems.push({label:'', qty:1, unit_price:0, note:''});
    renderQuoteItems();
    renderPayments();

    var m = document.getElementById('quoteModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeQuoteEditor() {
    var m = document.getElementById('quoteModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function addQuoteItem() {
    qeItems.push({label:'', qty:1, unit_price:0, note:''});
    renderQuoteItems();
}
function removeQuoteItem(idx) {
    qeItems.splice(idx, 1);
    if (qeItems.length === 0) qeItems.push({label:'', qty:1, unit_price:0, note:''});
    renderQuoteItems();
}
function renderQuoteItems() {
    var html = '';
    qeItems.forEach(function(it, idx) {
        html += '<div class="grid grid-cols-12 gap-2 items-start">'
              + '<input type="text" class="col-span-5 px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded" placeholder="<?= htmlspecialchars(__('services.admin_custom.qe_item_label')) ?>" value="' + escAttr(it.label) + '" oninput="qeItems[' + idx + '].label=this.value">'
              + '<input type="number" min="0" step="0.01" class="col-span-2 px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded" placeholder="<?= htmlspecialchars(__('services.custom.q_col_qty')) ?>" value="' + (parseFloat(it.qty)||0) + '" oninput="qeItems[' + idx + '].qty=parseFloat(this.value)||0; recalcQuote()">'
              + '<input type="number" min="0" step="1" class="col-span-3 px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded" placeholder="<?= htmlspecialchars(__('services.custom.q_col_unit')) ?>" value="' + (parseFloat(it.unit_price)||0) + '" oninput="qeItems[' + idx + '].unit_price=parseFloat(this.value)||0; recalcQuote()">'
              + '<div class="col-span-1 text-xs text-right tabular-nums pt-1.5 text-zinc-700 dark:text-zinc-100">¥' + Math.round(((it.qty||0)*(it.unit_price||0))).toLocaleString() + '</div>'
              + '<button type="button" onclick="removeQuoteItem(' + idx + ')" class="col-span-1 text-red-500 hover:bg-red-50 rounded text-xs py-1.5">×</button>'
              + '<input type="text" class="col-span-12 px-2 py-1 text-[11px] border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded" placeholder="<?= htmlspecialchars(__('services.admin_custom.qe_item_note_ph')) ?>" value="' + escAttr(it.note) + '" oninput="qeItems[' + idx + '].note=this.value">'
              + '</div>';
    });
    document.getElementById('qe_items').innerHTML = html;
    recalcQuote();
}
function escAttr(s) { return String(s||'').replace(/"/g, '&quot;'); }
function recalcQuote() {
    var subtotal = 0;
    qeItems.forEach(function(it) { subtotal += (parseFloat(it.qty)||0) * (parseFloat(it.unit_price)||0); });
    var rate = parseFloat(document.getElementById('qe_tax_rate').value) || 0;
    var taxAmt = Math.round(subtotal * (rate/100));
    var total = Math.round(subtotal) + taxAmt;
    document.getElementById('qe_subtotal').textContent = '¥' + Math.round(subtotal).toLocaleString();
    document.getElementById('qe_tax_amt').textContent = '¥' + taxAmt.toLocaleString();
    document.getElementById('qe_total').textContent = '¥' + total.toLocaleString();
    recalcPayments(total);
}

// ==== 결제 일정 ====
function renderPayments() {
    var html = '';
    qePayments.forEach(function(p, idx) {
        var locked = p.status === 'paid';
        var lockMark = locked ? '<span class="text-[10px] px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded ml-1">PAID</span>' : '';
        html += '<div class="grid grid-cols-12 gap-2 items-start">'
              + '<div class="col-span-4">'
              + '<input type="text" ' + (locked?'disabled':'') + ' class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded disabled:bg-gray-100" placeholder="<?= htmlspecialchars(__('services.admin_custom.qe_payment_label_ph')) ?>" value="' + escAttr(p.label) + '" oninput="qePayments[' + idx + '].label=this.value">'
              + (lockMark ? '<div class="mt-0.5">' + lockMark + '</div>' : '')
              + '</div>'
              + '<input type="number" min="0" step="1" ' + (locked?'disabled':'') + ' class="col-span-3 px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded disabled:bg-gray-100" placeholder="<?= htmlspecialchars(__('services.custom.q_col_amount')) ?>" value="' + (parseFloat(p.amount)||0) + '" oninput="qePayments[' + idx + '].amount=parseFloat(this.value)||0; recalcPaymentSum()">'
              + '<input type="date" ' + (locked?'disabled':'') + ' class="col-span-3 px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded disabled:bg-gray-100" value="' + escAttr(p.due_date) + '" oninput="qePayments[' + idx + '].due_date=this.value">'
              + (locked
                 ? '<div class="col-span-2"></div>'
                 : '<button type="button" onclick="removePayment(' + idx + ')" class="col-span-2 text-red-500 hover:bg-red-50 rounded text-xs py-1.5">×</button>')
              + '</div>';
    });
    document.getElementById('qe_payments').innerHTML = html;
    recalcPaymentSum();
}
function addPayment() {
    qePayments.push({label:'', amount:0, due_date:'', note:'', status:'pending'});
    renderPayments();
}
function removePayment(idx) {
    if (qePayments[idx] && qePayments[idx].status === 'paid') return;
    qePayments.splice(idx, 1);
    renderPayments();
}
function setPaymentPreset(kind) {
    // 결제된 항목 보존
    var paid = qePayments.filter(function(p){ return p.status === 'paid'; });
    var totalEl = document.getElementById('qe_total').textContent || '¥0';
    var total = parseInt(totalEl.replace(/[^0-9]/g, ''), 10) || 0;
    var paidSum = paid.reduce(function(s,p){ return s + (parseFloat(p.amount)||0); }, 0);
    var remaining = Math.max(0, total - paidSum);
    var preset = [];
    if (kind === 'lump') {
        preset = [{label: <?= json_encode(__('services.admin_custom.qe_preset_lump_label'), JSON_UNESCAPED_UNICODE) ?>, amount: remaining, due_date: '', note: '', status: 'pending'}];
    } else if (kind === 'split2') {
        var half = Math.round(remaining / 2);
        preset = [
            {label: <?= json_encode(__('services.admin_custom.qe_preset_deposit'), JSON_UNESCAPED_UNICODE) ?>, amount: half, due_date: '', note: '', status: 'pending'},
            {label: <?= json_encode(__('services.admin_custom.qe_preset_balance'), JSON_UNESCAPED_UNICODE) ?>, amount: remaining - half, due_date: '', note: '', status: 'pending'},
        ];
    } else if (kind === 'split3') {
        var a = Math.round(remaining * 0.3);
        var b = Math.round(remaining * 0.4);
        preset = [
            {label: <?= json_encode(__('services.admin_custom.qe_preset_deposit'), JSON_UNESCAPED_UNICODE) ?>, amount: a, due_date: '', note: '', status: 'pending'},
            {label: <?= json_encode(__('services.admin_custom.qe_preset_interim'), JSON_UNESCAPED_UNICODE) ?>, amount: b, due_date: '', note: '', status: 'pending'},
            {label: <?= json_encode(__('services.admin_custom.qe_preset_balance'), JSON_UNESCAPED_UNICODE) ?>, amount: remaining - a - b, due_date: '', note: '', status: 'pending'},
        ];
    }
    qePayments = paid.concat(preset);
    renderPayments();
}
function recalcPaymentSum() {
    var sum = qePayments.reduce(function(s,p){ return s + (parseFloat(p.amount)||0); }, 0);
    var totalEl = document.getElementById('qe_total').textContent || '¥0';
    var total = parseInt(totalEl.replace(/[^0-9]/g, ''), 10) || 0;
    document.getElementById('qe_payments_sum').textContent = '¥' + Math.round(sum).toLocaleString();
    document.getElementById('qe_payments_target').textContent = '¥' + total.toLocaleString();
    var match = document.getElementById('qe_payments_match');
    if (qePayments.length === 0) {
        match.textContent = ''; match.className = 'ml-1';
    } else if (Math.abs(sum - total) <= 1) {
        match.textContent = '✓'; match.className = 'ml-1 text-emerald-600 font-bold';
    } else {
        match.textContent = '✗'; match.className = 'ml-1 text-red-500 font-bold';
    }
}
function recalcPayments(total) {
    document.getElementById('qe_payments_target').textContent = '¥' + total.toLocaleString();
    recalcPaymentSum();
}
document.addEventListener('input', function(e) {
    if (e.target && e.target.id === 'qe_tax_rate') recalcQuote();
});

function saveQuote() {
    var validItems = qeItems.filter(function(it){ return it.label.trim() !== ''; });
    if (validItems.length === 0) {
        alert(<?= json_encode(__('services.admin_custom.no_valid_items'), JSON_UNESCAPED_UNICODE) ?>);
        return;
    }
    var validPayments = qePayments
        .filter(function(p){ return p.label.trim() !== '' && parseFloat(p.amount) > 0; })
        .map(function(p){ return {label: p.label, amount: p.amount, due_date: p.due_date, note: p.note}; });
    var btn = document.getElementById('qe_save');
    btn.disabled = true;
    api('project_save_quote', {
        project_id: projectId,
        quote_id: parseInt(document.getElementById('qe_quote_id').value, 10) || 0,
        items: validItems,
        tax_rate: parseFloat(document.getElementById('qe_tax_rate').value) || 0,
        valid_until: document.getElementById('qe_valid_until').value,
        notes: document.getElementById('qe_notes').value,
        currency: document.getElementById('qe_currency').value,
        payments: validPayments,
    }).then(function(d) {
        btn.disabled = false;
        if (d.success) location.reload();
        else alert(d.message || 'error');
    }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
}

function sendQuote(quoteId) {
    if (!confirm(<?= json_encode(__('services.admin_custom.confirm_send'), JSON_UNESCAPED_UNICODE) ?>)) return;
    api('project_send_quote', { project_id: projectId, quote_id: quoteId }).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}

// 견적서 출력 (HTML 인쇄)
function openQuotePrint(quote) {
    var w = window.open('', '_blank', 'width=900,height=700');
    if (!w) { alert('Popup blocked'); return; }
    var customer = <?= json_encode($project['user_name_decrypted'] . ' / ' . $project['user_email'], JSON_UNESCAPED_UNICODE) ?>;
    var pNumber = <?= json_encode($project['project_number'], JSON_UNESCAPED_UNICODE) ?>;
    var pTitle = <?= json_encode($project['title'], JSON_UNESCAPED_UNICODE) ?>;
    var rows = quote.items.map(function(it) {
        return '<tr><td>' + escHtml(it.label) + (it.note ? '<div class="note">' + escHtml(it.note) + '</div>' : '') + '</td><td class="num">' + (parseFloat(it.qty)||0) + '</td><td class="num">¥' + Math.round(it.unit_price||0).toLocaleString() + '</td><td class="num">¥' + Math.round(it.amount||0).toLocaleString() + '</td></tr>';
    }).join('');
    w.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Quote v' + quote.version + ' — ' + pNumber + '</title><style>'
        + 'body{font-family:-apple-system,sans-serif;max-width:760px;margin:30px auto;padding:20px;color:#222;font-size:13px}'
        + 'h1{font-size:24px;margin:0 0 4px}h2{font-size:14px;color:#666;font-weight:normal;margin:0 0 24px}'
        + 'table{width:100%;border-collapse:collapse;margin:16px 0}th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}th{background:#f7f7f7;font-size:11px;text-transform:uppercase;color:#888}'
        + '.num{text-align:right;font-variant-numeric:tabular-nums}'
        + '.note{font-size:11px;color:#888;margin-top:2px}'
        + '.totals{margin-top:16px;display:flex;justify-content:flex-end}.totals table{width:280px}.totals td{padding:4px 8px;border:none}.totals .grand{font-weight:bold;font-size:16px;border-top:2px solid #222}'
        + '.notes-box{margin-top:24px;padding:12px;background:#fafafa;border-left:3px solid #ccc;font-size:12px;white-space:pre-wrap}'
        + '.head{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:2px solid #222;padding-bottom:12px;margin-bottom:20px}'
        + '@media print{body{margin:0;padding:10mm}}'
        + '</style></head><body>'
        + '<div class="head"><div><h1><?= htmlspecialchars(__('services.admin_custom.print_title')) ?></h1><h2>' + pTitle + '</h2></div>'
        + '<div style="text-align:right;font-size:11px;color:#666">'
        + '<?= htmlspecialchars(__('services.admin_custom.print_project_number')) ?>: <strong>' + pNumber + '</strong><br>'
        + '<?= htmlspecialchars(__('services.admin_custom.print_version')) ?>: v' + quote.version + '<br>'
        + '<?= htmlspecialchars(__('services.admin_custom.print_date')) ?>: ' + (quote.sent_at || quote.created_at).substring(0, 10)
        + (quote.valid_until ? '<br><?= htmlspecialchars(__('services.admin_custom.print_valid_until')) ?>: ' + quote.valid_until : '')
        + '</div></div>'
        + '<p><strong><?= htmlspecialchars(__('services.admin_custom.print_customer')) ?>:</strong> ' + escHtml(customer) + '</p>'
        + '<table><thead><tr><th><?= htmlspecialchars(__('services.custom.q_col_label')) ?></th><th class="num" style="width:60px"><?= htmlspecialchars(__('services.custom.q_col_qty')) ?></th><th class="num" style="width:100px"><?= htmlspecialchars(__('services.custom.q_col_unit')) ?></th><th class="num" style="width:100px"><?= htmlspecialchars(__('services.custom.q_col_amount')) ?></th></tr></thead><tbody>' + rows + '</tbody></table>'
        + '<div class="totals"><table>'
        + '<tr><td><?= htmlspecialchars(__('services.custom.q_subtotal')) ?></td><td class="num">¥' + Math.round(quote.subtotal||0).toLocaleString() + '</td></tr>'
        + '<tr><td><?= htmlspecialchars(__('services.admin_custom.qe_tax_amount')) ?> (' + (parseFloat(quote.tax_rate)||0) + '%)</td><td class="num">¥' + Math.round(quote.tax_amount||0).toLocaleString() + '</td></tr>'
        + '<tr class="grand"><td><?= htmlspecialchars(__('services.custom.q_total')) ?></td><td class="num">¥' + Math.round(quote.total||0).toLocaleString() + '</td></tr>'
        + '</table></div>'
        + (quote.notes ? '<div class="notes-box">' + escHtml(quote.notes) + '</div>' : '')
        + '<script>window.onload=function(){window.print()}<\/script>'
        + '</body></html>');
    w.document.close();
}
function escHtml(s) { return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

// ==== 프로젝트 채널 ====
<?php if ($_channelTicket): ?>
var channelTicketId = <?= (int)$_channelTicket['id'] ?>;
function channelEsc(s) { return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
function channelFmtDate(s) { try { return new Date(s).toLocaleString(); } catch(e) { return s||''; } }
function channelFmtSize(b) { b = parseInt(b,10)||0; if (b<1024) return b+' B'; if (b<1048576) return (b/1024).toFixed(1)+' KB'; return (b/1048576).toFixed(1)+' MB'; }

function channelLoad() {
    fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'support_get_ticket', ticket_id: channelTicketId }),
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (!d.success) { document.getElementById('channelMessages').innerHTML = '<p class="text-xs text-red-500 text-center">' + (d.message || 'error') + '</p>'; return; }
        var html = '';
        (d.messages || []).forEach(function(m) {
            var isAdmin = parseInt(m.is_admin, 10) === 1;
            var bgClass = isAdmin
                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800'
                : 'bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-700';
            var sender = isAdmin ? <?= json_encode(__('services.admin_support.admin_label'), JSON_UNESCAPED_UNICODE) ?> : (m.sender_name || m.sender_email || 'user');
            html += '<div class="border ' + bgClass + ' rounded-lg p-3">'
                  + '<div class="flex items-center justify-between mb-1.5 text-[10px] text-zinc-500 dark:text-zinc-400"><span class="font-medium">' + channelEsc(sender) + '</span><span>' + channelFmtDate(m.created_at) + '</span></div>'
                  + '<p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap">' + channelEsc(m.body) + '</p>';
            var atts = m.attachments || [];
            if (atts.length > 0) {
                html += '<div class="mt-2 pt-2 border-t border-gray-200 dark:border-zinc-700 space-y-1">';
                atts.forEach(function(a, idx) {
                    var isImg = /^(jpg|jpeg|png|gif|webp|svg)$/i.test(a.ext || '');
                    var dlUrl = siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=download&msg=' + m.id + '&idx=' + idx;
                    var inUrl = siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=inline&msg=' + m.id + '&idx=' + idx;
                    if (isImg) {
                        html += '<a href="' + dlUrl + '" target="_blank" class="block"><img src="' + inUrl + '" class="max-h-40 rounded border border-gray-200 dark:border-zinc-600" /></a>';
                    } else {
                        html += '<a href="' + dlUrl + '" class="flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700 hover:bg-blue-50 dark:hover:bg-zinc-600 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600">'
                             + '<svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                             + '<span class="truncate flex-1 text-zinc-700 dark:text-zinc-200">' + channelEsc(a.name) + '</span>'
                             + '<span class="text-zinc-400">' + channelFmtSize(a.size) + '</span></a>';
                    }
                });
                html += '</div>';
            }
            html += '</div>';
        });
        var box = document.getElementById('channelMessages');
        box.innerHTML = html || '<p class="text-xs text-zinc-400 text-center"><?= htmlspecialchars(__('services.custom.channel_empty')) ?></p>';
        box.scrollTop = box.scrollHeight;
    });
}

function channelUpload(fileInput) {
    var files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];
    if (files.length === 0) return Promise.resolve([]);
    var fd = new FormData();
    files.forEach(function(f){ fd.append('files[]', f); });
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=upload_pending', {
        method: 'POST', body: fd, credentials: 'same-origin'
    }).then(function(r){ return r.json(); }).then(function(d){
        if (!d.success) throw new Error(d.message || 'upload failed');
        return d.attachments || [];
    });
}

function channelSubmit() {
    var body = document.getElementById('channelReplyBody').value.trim();
    if (!body) return;
    var btn = document.getElementById('channelReplyBtn');
    btn.disabled = true;
    channelUpload(document.getElementById('channelReplyFiles')).then(function(atts) {
        return fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ action: 'support_post_message', ticket_id: channelTicketId, body: body, attachments: atts }),
        }).then(function(r){ return r.json(); });
    }).then(function(d) {
        btn.disabled = false;
        if (d.success) {
            document.getElementById('channelReplyBody').value = '';
            document.getElementById('channelReplyFiles').value = '';
            channelLoad();
        } else { alert(d.message || 'error'); }
    }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
}

document.addEventListener('DOMContentLoaded', channelLoad);
<?php endif; ?>

// ==== 마일스톤 ====
function openMilestoneNew() {
    document.getElementById('milestoneModalTitle').textContent = <?= json_encode(__('services.admin_custom.milestone_new'), JSON_UNESCAPED_UNICODE) ?>;
    document.getElementById('ms_id').value = 0;
    document.getElementById('ms_title').value = '';
    document.getElementById('ms_description').value = '';
    document.getElementById('ms_due_date').value = '';
    document.getElementById('ms_sequence_no').value = '';
    var m = document.getElementById('milestoneModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function openMilestoneEdit(ms) {
    document.getElementById('milestoneModalTitle').textContent = <?= json_encode(__('services.admin_custom.milestone_edit'), JSON_UNESCAPED_UNICODE) ?>;
    document.getElementById('ms_id').value = ms.id;
    document.getElementById('ms_title').value = ms.title || '';
    document.getElementById('ms_description').value = ms.description || '';
    document.getElementById('ms_due_date').value = ms.due_date || '';
    document.getElementById('ms_sequence_no').value = ms.sequence_no || '';
    var m = document.getElementById('milestoneModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeMilestoneModal() {
    var m = document.getElementById('milestoneModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function saveMilestone() {
    var id = parseInt(document.getElementById('ms_id').value, 10) || 0;
    var title = document.getElementById('ms_title').value.trim();
    if (!title) { alert(<?= json_encode(__('services.admin_custom.ms_title_required'), JSON_UNESCAPED_UNICODE) ?>); return; }
    var btn = document.getElementById('ms_save_btn');
    btn.disabled = true;
    var payload = {
        title: title,
        description: document.getElementById('ms_description').value,
        due_date: document.getElementById('ms_due_date').value,
    };
    var seq = document.getElementById('ms_sequence_no').value;
    if (seq !== '') payload.sequence_no = parseInt(seq, 10);
    if (id > 0) {
        payload.milestone_id = id;
        api('milestone_update', payload).then(function(d) {
            btn.disabled = false;
            if (d.success) location.reload();
            else alert(d.message || 'error');
        });
    } else {
        payload.project_id = projectId;
        api('milestone_create', payload).then(function(d) {
            btn.disabled = false;
            if (d.success) location.reload();
            else alert(d.message || 'error');
        });
    }
}
function deleteMilestone(id) {
    if (!confirm(<?= json_encode(__('services.admin_custom.confirm_delete_ms'), JSON_UNESCAPED_UNICODE) ?>)) return;
    api('milestone_delete', { milestone_id: id }).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}
function openMilestoneSubmit(ms) {
    document.getElementById('sub_ms_id').value = ms.id;
    document.getElementById('sub_ms_title').textContent = ms.title;
    document.getElementById('sub_files').value = '';
    var m = document.getElementById('submitModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeSubmitModal() {
    var m = document.getElementById('submitModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function submitMilestone() {
    var id = parseInt(document.getElementById('sub_ms_id').value, 10);
    var btn = document.getElementById('sub_btn');
    btn.disabled = true;
    var fileInput = document.getElementById('sub_files');
    var files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];
    var uploadPromise;
    if (files.length === 0) {
        uploadPromise = Promise.resolve([]);
    } else {
        var fd = new FormData();
        files.forEach(function(f){ fd.append('files[]', f); });
        uploadPromise = fetch(siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=upload_pending', {
            method: 'POST', body: fd, credentials: 'same-origin'
        }).then(function(r){ return r.json(); }).then(function(d){
            if (!d.success) throw new Error(d.message || 'upload failed');
            return d.attachments || [];
        });
    }
    uploadPromise.then(function(atts) {
        return api('milestone_submit', { milestone_id: id, attachments: atts });
    }).then(function(d) {
        btn.disabled = false;
        if (d.success) location.reload();
        else alert(d.message || 'error');
    }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
}

// ==== 견적 상세 보기 모달 ====
function openQuoteView(quote) {
    var statusLabel = {
        'draft': <?= json_encode(__('services.admin_custom.q_draft'), JSON_UNESCAPED_UNICODE) ?>,
        'sent': <?= json_encode(__('services.custom.q_sent'), JSON_UNESCAPED_UNICODE) ?>,
        'accepted': <?= json_encode(__('services.custom.q_accepted'), JSON_UNESCAPED_UNICODE) ?>,
        'rejected': <?= json_encode(__('services.custom.q_rejected'), JSON_UNESCAPED_UNICODE) ?>,
        'superseded': <?= json_encode(__('services.custom.q_superseded'), JSON_UNESCAPED_UNICODE) ?>,
    }[quote.status] || quote.status;
    var statusClass = {
        'draft': 'bg-gray-100 text-gray-700 dark:bg-zinc-700 dark:text-zinc-200',
        'sent': 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        'accepted': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200',
        'rejected': 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        'superseded': 'bg-gray-50 text-gray-500 dark:bg-zinc-700/30 dark:text-zinc-400',
    }[quote.status] || 'bg-gray-100 text-gray-700';

    var itemsHtml = '';
    (quote.items || []).forEach(function(it) {
        itemsHtml += '<tr class="border-b border-gray-100 dark:border-zinc-700/50">'
            + '<td class="py-2 text-zinc-900 dark:text-white">' + escHtml(it.label)
            + (it.note ? '<div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">' + escHtml(it.note) + '</div>' : '')
            + '</td>'
            + '<td class="py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-200">' + (parseFloat(it.qty)||0) + '</td>'
            + '<td class="py-2 text-right tabular-nums text-zinc-700 dark:text-zinc-200">¥' + Math.round(it.unit_price||0).toLocaleString() + '</td>'
            + '<td class="py-2 text-right tabular-nums font-medium text-zinc-900 dark:text-white">¥' + Math.round(it.amount||0).toLocaleString() + '</td>'
            + '</tr>';
    });

    var paymentsHtml = '';
    var pays = quote.payments || [];
    if (pays.length > 0) {
        pays.forEach(function(p) {
            var pCls = {
                'paid': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                'cancelled': 'bg-gray-50 dark:bg-zinc-700/30 text-zinc-400 line-through',
            }[p.status] || 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-300';
            paymentsHtml += '<div class="flex items-center justify-between gap-2 text-xs px-3 py-2 rounded ' + pCls + '">'
                + '<span class="flex items-center gap-2"><span class="font-medium">' + escHtml(p.label) + '</span>'
                + (p.due_date ? '<span class="text-[10px] opacity-60">(' + escHtml(p.due_date) + ')</span>' : '')
                + (p.status === 'paid' ? '<span class="text-[10px] px-1.5 py-0.5 bg-emerald-200 dark:bg-emerald-800 rounded">✓ PAID</span>' : '')
                + '</span>'
                + '<span class="tabular-nums whitespace-nowrap font-bold">¥' + Math.round(p.amount).toLocaleString() + '</span>'
                + '</div>';
        });
    }

    var html = '<div class="space-y-4">'
        + '<div class="flex items-center justify-between flex-wrap gap-2 pb-3 border-b border-gray-200 dark:border-zinc-700">'
        + '<div class="flex items-center gap-2">'
        + '<h4 class="text-base font-bold text-zinc-900 dark:text-white">v' + quote.version + '</h4>'
        + '<span class="text-[11px] px-2 py-0.5 rounded-full font-medium ' + statusClass + '">' + escHtml(statusLabel) + '</span>'
        + '</div>'
        + '<div class="text-[11px] text-zinc-500 dark:text-zinc-400">'
        + <?= json_encode(__('services.admin_custom.print_date'), JSON_UNESCAPED_UNICODE) ?> + ': ' + (quote.sent_at || quote.created_at).substring(0, 10)
        + (quote.valid_until ? ' · ' + <?= json_encode(__('services.admin_custom.print_valid_until'), JSON_UNESCAPED_UNICODE) ?> + ': ' + quote.valid_until : '')
        + '</div>'
        + '</div>'
        + '<table class="w-full text-sm">'
        + '<thead><tr class="border-b-2 border-gray-300 dark:border-zinc-600 text-[10px] text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">'
        + '<th class="text-left py-2 font-medium"><?= htmlspecialchars(__('services.custom.q_col_label')) ?></th>'
        + '<th class="text-right py-2 font-medium w-16"><?= htmlspecialchars(__('services.custom.q_col_qty')) ?></th>'
        + '<th class="text-right py-2 font-medium w-28"><?= htmlspecialchars(__('services.custom.q_col_unit')) ?></th>'
        + '<th class="text-right py-2 font-medium w-28"><?= htmlspecialchars(__('services.custom.q_col_amount')) ?></th>'
        + '</tr></thead><tbody>' + itemsHtml + '</tbody></table>'
        + '<div class="flex justify-end">'
        + '<div class="w-72 text-sm space-y-1.5">'
        + '<div class="flex justify-between"><span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.custom.q_subtotal')) ?></span><span class="tabular-nums text-zinc-700 dark:text-zinc-200">¥' + Math.round(quote.subtotal||0).toLocaleString() + '</span></div>'
        + '<div class="flex justify-between"><span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.admin_custom.qe_tax_amount')) ?> (' + (parseFloat(quote.tax_rate)||0) + '%)</span><span class="tabular-nums text-zinc-700 dark:text-zinc-200">¥' + Math.round(quote.tax_amount||0).toLocaleString() + '</span></div>'
        + '<div class="flex justify-between pt-2 border-t-2 border-gray-300 dark:border-zinc-600 font-bold text-zinc-900 dark:text-white">'
        + '<span><?= htmlspecialchars(__('services.custom.q_total')) ?></span>'
        + '<span class="tabular-nums text-lg">¥' + Math.round(quote.total||0).toLocaleString() + '</span>'
        + '</div>'
        + '</div></div>'
        + (paymentsHtml ? '<div class="pt-3 border-t border-gray-200 dark:border-zinc-700"><p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-2 font-medium"><?= htmlspecialchars(__('services.admin_custom.q_payments_label')) ?> (' + pays.length + ')</p><div class="space-y-1.5">' + paymentsHtml + '</div></div>' : '')
        + (quote.notes ? '<div class="pt-3 border-t border-gray-200 dark:border-zinc-700"><p class="text-[11px] text-zinc-500 dark:text-zinc-400 mb-1 font-medium"><?= htmlspecialchars(__('services.custom.q_notes')) ?></p><p class="text-xs whitespace-pre-wrap text-zinc-700 dark:text-zinc-200 bg-gray-50 dark:bg-zinc-700/30 rounded p-3">' + escHtml(quote.notes) + '</p></div>' : '')
        + (quote.status === 'rejected' && quote.reject_reason ? '<p class="text-xs text-red-600 dark:text-red-300"><?= htmlspecialchars(__('services.custom.q_rejected_reason', ['reason' => '\' + escHtml(quote.reject_reason) + \''])) ?></p>' : '')
        + '</div>';

    document.getElementById('viewModalContent').innerHTML = html;
    var m = document.getElementById('viewModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
    // 인쇄 버튼이 현재 quote 데이터를 알 수 있도록
    document.getElementById('viewModalPrint').onclick = function() { openQuotePrint(quote); };
}
function closeQuoteView() {
    var m = document.getElementById('viewModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
</script>

<!-- 마일스톤 편집/추가 모달 -->
<div id="milestoneModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeMilestoneModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 id="milestoneModalTitle" class="text-base font-bold text-zinc-900 dark:text-white"></h3>
            <button type="button" onclick="closeMilestoneModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <input type="hidden" id="ms_id" value="0">
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.ms_field_title')) ?> <span class="text-red-500">*</span></label>
                <input type="text" id="ms_title" maxlength="200" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_custom.ms_title_ph')) ?>">
            </div>
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.ms_field_desc')) ?></label>
                <textarea id="ms_description" rows="3" class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_custom.ms_desc_ph')) ?>"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.ms_field_due')) ?></label>
                    <input type="date" id="ms_due_date" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_custom.ms_field_seq')) ?></label>
                    <input type="number" id="ms_sequence_no" min="0" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg">
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2">
            <button type="button" onclick="closeMilestoneModal()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="ms_save_btn" onclick="saveMilestone()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                <?= htmlspecialchars(__('services.admin_custom.btn_save')) ?>
            </button>
        </div>
    </div>
</div>

<!-- 시안 제출 모달 -->
<div id="submitModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeSubmitModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">📤 <?= htmlspecialchars(__('services.admin_custom.btn_submit_milestone')) ?></h3>
            <button type="button" onclick="closeSubmitModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <input type="hidden" id="sub_ms_id" value="0">
            <p id="sub_ms_title" class="text-sm font-bold text-zinc-900 dark:text-white"></p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.admin_custom.submit_modal_hint')) ?></p>
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1">📎 <?= htmlspecialchars(__('services.admin_custom.ms_attachments')) ?></label>
                <input type="file" id="sub_files" multiple class="block w-full text-xs text-zinc-600 dark:text-zinc-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2">
            <button type="button" onclick="closeSubmitModal()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="sub_btn" onclick="submitMilestone()" class="px-5 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg disabled:opacity-50">
                📤 <?= htmlspecialchars(__('services.admin_custom.btn_submit_milestone')) ?>
            </button>
        </div>
    </div>
</div>

<!-- 견적 상세 보기 모달 -->
<div id="viewModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeQuoteView()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">📋 <?= htmlspecialchars(__('services.admin_custom.view_modal_title')) ?></h3>
            <button type="button" onclick="closeQuoteView()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="viewModalContent" class="p-6"></div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2 sticky bottom-0 bg-white dark:bg-zinc-800">
            <button type="button" onclick="closeQuoteView()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="viewModalPrint" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">
                🖨 <?= htmlspecialchars(__('services.admin_custom.btn_print')) ?>
            </button>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
