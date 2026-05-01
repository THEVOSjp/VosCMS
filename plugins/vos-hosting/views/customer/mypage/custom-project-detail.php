<?php
/**
 * 마이페이지 — 제작 프로젝트 상세
 * 의뢰 내용 + 견적 리스트 (수락/거절 가능)
 */
use RzxLib\Core\Auth\Auth;

if (!$isLoggedIn) { header("Location: {$baseUrl}/login"); exit; }

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$user = Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$projectId = $customProjectId ?? 0;

$pSt = $pdo->prepare("SELECT * FROM {$prefix}custom_projects WHERE id = ? AND user_id = ?");
$pSt->execute([$projectId, $user['id']]);
$project = $pSt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    http_response_code(404);
    echo '<div class="max-w-7xl mx-auto px-4 py-16 text-center"><p class="text-zinc-400">' . htmlspecialchars(__('services.custom.not_found')) . '</p><a href="' . $baseUrl . '/mypage/custom-projects" class="text-blue-600 hover:underline text-sm mt-4 inline-block">' . htmlspecialchars(__('services.custom.back_to_list')) . '</a></div>';
    return;
}
$project['attachments'] = json_decode($project['attachments'] ?? '[]', true) ?: [];

// 견적 리스트 (sent/accepted/rejected/superseded 만 노출)
$qSt = $pdo->prepare("SELECT * FROM {$prefix}custom_quotes WHERE project_id = ? AND status IN ('sent','accepted','rejected','superseded') ORDER BY version DESC, id DESC");
$qSt->execute([$projectId]);
$quotes = $qSt->fetchAll(PDO::FETCH_ASSOC);

// 프로젝트 채널 (계약 후 자동 생성된 ticket)
$_channelTicket = null;
try {
    $_chSt = $pdo->prepare("SELECT id, status, last_message_at FROM {$prefix}support_tickets WHERE custom_project_id = ? LIMIT 1");
    $_chSt->execute([$projectId]);
    $_channelTicket = $_chSt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (\Throwable $e) {}

// 마일스톤
$_milestones = [];
try {
    $_msSt = $pdo->prepare("SELECT * FROM {$prefix}custom_milestones WHERE project_id = ? AND status != 'cancelled' ORDER BY sequence_no ASC, id ASC");
    $_msSt->execute([$projectId]);
    $_milestones = $_msSt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($_milestones as &$_m) { $_m['attachments'] = json_decode($_m['attachments'] ?? '[]', true) ?: []; }
    unset($_m);
} catch (\Throwable $e) {}
$_msTotal = count($_milestones);
$_msApproved = count(array_filter($_milestones, fn($m) => $m['status'] === 'approved'));
$_msProgress = $_msTotal > 0 ? round($_msApproved / $_msTotal * 100) : 0;
foreach ($quotes as &$_q) {
    $_q['items'] = json_decode($_q['items'] ?? '[]', true) ?: [];
    $_qpSt = $pdo->prepare("SELECT id, sequence_no, label, amount, currency, due_date, status, paid_at, note FROM {$prefix}custom_project_payments WHERE quote_id = ? ORDER BY sequence_no ASC, id ASC");
    $_qpSt->execute([$_q['id']]);
    $_q['payments'] = $_qpSt->fetchAll(PDO::FETCH_ASSOC);
}
unset($_q);

// 수락된 견적의 결제 일정 (활성)
$_acceptedQuote = null;
foreach ($quotes as $_q) {
    if ($_q['status'] === 'accepted') { $_acceptedQuote = $_q; break; }
}

// PAY.JP public key (있을 때만 결제 모달 활성화)
//   payment_config JSON 에서 활성 gateway 의 public_key 추출
$_payPubKey = '';
try {
    $_pkSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'payment_config' LIMIT 1");
    $_pkSt->execute();
    $_payCfg = json_decode((string)$_pkSt->fetchColumn() ?: '{}', true) ?: [];
    $_gw = $_payCfg['gateway'] ?? 'payjp';
    $_payPubKey = (string)($_payCfg['gateways'][$_gw]['public_key'] ?? '');
} catch (\Throwable $e) {}

$statusLabel = function(string $s): array {
    return match ($s) {
        'lead' => [__('services.custom.st_lead'), 'bg-blue-100 text-blue-700'],
        'quoted' => [__('services.custom.st_quoted'), 'bg-amber-100 text-amber-700'],
        'contracted' => [__('services.custom.st_contracted'), 'bg-emerald-100 text-emerald-700'],
        'in_progress' => [__('services.custom.st_in_progress'), 'bg-indigo-100 text-indigo-700'],
        'review' => [__('services.custom.st_review'), 'bg-purple-100 text-purple-700'],
        'delivered' => [__('services.custom.st_delivered'), 'bg-teal-100 text-teal-700'],
        'maintenance' => [__('services.custom.st_maintenance'), 'bg-cyan-100 text-cyan-700'],
        'cancelled' => [__('services.custom.st_cancelled'), 'bg-gray-100 text-gray-500'],
        default => [$s, 'bg-gray-100 text-gray-500'],
    };
};
$st = $statusLabel($project['status']);

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

// 연결 호스팅 정보 fetch
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
?>
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="<?= $baseUrl ?>/mypage/custom-projects" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[10px] font-mono text-zinc-400">#<?= htmlspecialchars($project['project_number']) ?></span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= htmlspecialchars($st[0]) ?></span>
            </div>
            <h1 class="text-lg font-bold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($project['title']) ?></h1>
        </div>
    </div>

    <!-- 의뢰 내용 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
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
                <div class="space-y-1.5">
                <?php foreach ($project['attachments'] as $idx => $a):
                    $_isImg = preg_match('/^(jpg|jpeg|png|gif|webp|svg)$/i', $a['ext']);
                    $_dlUrl = $baseUrl . '/plugins/vos-hosting/api/support-attachment.php?action=download&type=project&project=' . $projectId . '&idx=' . $idx;
                    $_inUrl = $baseUrl . '/plugins/vos-hosting/api/support-attachment.php?action=inline&type=project&project=' . $projectId . '&idx=' . $idx;
                ?>
                    <?php if ($_isImg): ?>
                    <a href="<?= htmlspecialchars($_dlUrl) ?>" target="_blank" class="block">
                        <img src="<?= htmlspecialchars($_inUrl) ?>" alt="<?= htmlspecialchars($a['name']) ?>" class="max-h-40 rounded border border-gray-200 dark:border-zinc-600 hover:opacity-80 transition" />
                        <span class="text-[10px] text-zinc-400 mt-0.5 block"><?= htmlspecialchars($a['name']) ?></span>
                    </a>
                    <?php else: ?>
                    <div class="flex items-center gap-1.5">
                        <a href="<?= htmlspecialchars($_inUrl) ?>" target="_blank" class="flex-1 flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700 hover:bg-blue-50 dark:hover:bg-zinc-600 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600 transition">
                            <svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span class="truncate flex-1 text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($a['name']) ?></span>
                            <span class="text-zinc-400"><?= number_format($a['size']/1024, 1) ?> KB</span>
                        </a>
                        <a href="<?= htmlspecialchars($_dlUrl) ?>" class="px-2 py-1.5 text-[10px] text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded" title="<?= htmlspecialchars(__('services.custom.btn_download')) ?>">⬇</a>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 도메인/호스팅 정보 -->
    <?php if ($project['domain_option'] || $_linkedHost || $project['need_new_hosting']): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
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
                    <a href="<?= $baseUrl ?>/mypage/services/<?= htmlspecialchars($_linkedHost['order_number']) ?>" class="text-blue-600 hover:underline">
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

    <!-- 프로젝트 채널 (계약 후) -->
    <?php if ($_channelTicket): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4" id="projectChannel">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">💬 <?= htmlspecialchars(__('services.custom.section_channel')) ?></p>
            <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.custom.channel_desc')) ?></span>
        </div>
        <div id="channelMessages" class="p-5 space-y-3 max-h-[400px] overflow-y-auto">
            <p class="text-xs text-zinc-400 text-center"><?= htmlspecialchars(__('services.custom.channel_loading')) ?></p>
        </div>
        <div class="px-5 py-4 border-t border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-700/30 rounded-b-xl">
            <textarea id="channelReplyBody" rows="3" class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.custom.channel_reply_ph')) ?>"></textarea>
            <div class="mt-2 flex items-center justify-between gap-2">
                <input type="file" id="channelReplyFiles" multiple class="text-[11px] text-zinc-600 dark:text-zinc-300 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                <button type="button" id="channelReplyBtn" onclick="channelSubmit()" class="px-4 py-1.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                    <?= htmlspecialchars(__('services.custom.channel_btn_send')) ?>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 마일스톤 / 시안 검수 -->
    <?php if ($_msTotal > 0): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">📋 <?= htmlspecialchars(__('services.custom.section_milestones')) ?></p>
            <span class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= $_msApproved ?>/<?= $_msTotal ?> · <?= $_msProgress ?>%</span>
        </div>
        <div class="px-5 pt-3">
            <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                <div class="bg-emerald-500 h-2 rounded-full transition-all" style="width: <?= $_msProgress ?>%"></div>
            </div>
        </div>
        <div class="p-5 space-y-3">
            <?php foreach ($_milestones as $_ms):
                $_msCls = match ($_ms['status']) {
                    'pending' => 'bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-600',
                    'in_progress' => 'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800',
                    'submitted' => 'bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800',
                    'approved' => 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800',
                    'revision_requested' => 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800',
                    default => 'bg-gray-50 border-gray-200',
                };
                $_msStatusLabel = match ($_ms['status']) {
                    'pending' => __('services.custom.ms_pending'),
                    'in_progress' => __('services.custom.ms_in_progress'),
                    'submitted' => __('services.custom.ms_submitted'),
                    'approved' => __('services.custom.ms_approved'),
                    'revision_requested' => __('services.custom.ms_revision'),
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
                </div>
                <?php if ($_ms['description']): ?>
                <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap mb-2"><?= htmlspecialchars($_ms['description']) ?></p>
                <?php endif; ?>
                <?php if (!empty($_ms['attachments'])): ?>
                <div class="mb-2 space-y-1.5">
                    <p class="text-[10px] text-zinc-400 uppercase">📎 <?= htmlspecialchars(__('services.custom.ms_attachments')) ?></p>
                    <?php foreach ($_ms['attachments'] as $_idx => $_a):
                        $_isImg = preg_match('/^(jpg|jpeg|png|gif|webp|svg)$/i', $_a['ext']);
                        $_dlUrl = $baseUrl . '/plugins/vos-hosting/api/support-attachment.php?action=download&type=milestone&ms=' . (int)$_ms['id'] . '&idx=' . $_idx;
                        $_inUrl = $baseUrl . '/plugins/vos-hosting/api/support-attachment.php?action=inline&type=milestone&ms=' . (int)$_ms['id'] . '&idx=' . $_idx;
                    ?>
                    <?php if ($_isImg): ?>
                    <a href="<?= htmlspecialchars($_dlUrl) ?>" target="_blank" class="block">
                        <img src="<?= htmlspecialchars($_inUrl) ?>" alt="<?= htmlspecialchars($_a['name']) ?>" class="max-h-48 rounded border border-gray-200 dark:border-zinc-600 hover:opacity-80 transition" />
                        <span class="text-[10px] text-zinc-400 mt-0.5 block"><?= htmlspecialchars($_a['name']) ?> (<?= number_format($_a['size']/1024, 1) ?> KB)</span>
                    </a>
                    <?php else: ?>
                    <div class="flex items-center gap-1.5">
                        <a href="<?= htmlspecialchars($_inUrl) ?>" target="_blank" class="flex-1 flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700/50 hover:bg-blue-50 dark:hover:bg-zinc-600 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600 transition">
                            <svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span class="truncate flex-1 text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($_a['name']) ?></span>
                            <span class="text-zinc-400"><?= number_format($_a['size']/1024, 1) ?> KB</span>
                        </a>
                        <a href="<?= htmlspecialchars($_dlUrl) ?>" class="px-2 py-1.5 text-[10px] text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded" title="<?= htmlspecialchars(__('services.custom.btn_download')) ?>">⬇</a>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($_ms['status'] === 'revision_requested' && $_ms['revision_note']): ?>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-2 mb-2">
                    <p class="text-[10px] text-red-700 dark:text-red-300 font-bold mb-1">📝 <?= htmlspecialchars(__('services.custom.ms_revision_note_label')) ?></p>
                    <p class="text-xs text-red-700 dark:text-red-200 whitespace-pre-wrap"><?= htmlspecialchars($_ms['revision_note']) ?></p>
                </div>
                <?php endif; ?>
                <?php if ($_ms['status'] === 'approved' && $_ms['approval_note']): ?>
                <p class="text-[11px] text-emerald-700 dark:text-emerald-300">✓ <?= htmlspecialchars($_ms['approval_note']) ?></p>
                <?php endif; ?>
                <?php if ($_ms['status'] === 'submitted'): ?>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-600 flex items-center justify-end gap-2">
                    <button onclick="msRequestRevision(<?= (int)$_ms['id'] ?>)" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                        📝 <?= htmlspecialchars(__('services.custom.btn_request_revision')) ?>
                    </button>
                    <button onclick="msApprove(<?= (int)$_ms['id'] ?>)" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">
                        ✓ <?= htmlspecialchars(__('services.custom.btn_approve')) ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 견적서 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.custom.section_quotes')) ?></p>
        </div>
        <div class="p-5">
            <?php if (empty($quotes)): ?>
            <p class="text-sm text-zinc-400 text-center py-4"><?= htmlspecialchars(__('services.custom.quotes_pending')) ?></p>
            <?php else: foreach ($quotes as $q): ?>
            <?php
            $qBg = match ($q['status']) {
                'sent' => ['bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800', __('services.custom.q_sent')],
                'accepted' => ['bg-emerald-100 dark:bg-emerald-900/30 border-emerald-400 dark:border-emerald-600', __('services.custom.q_accepted')],
                'rejected' => ['bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-700', __('services.custom.q_rejected')],
                'superseded' => ['bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-600 opacity-60', __('services.custom.q_superseded')],
                default => ['bg-gray-50 border-gray-200', $q['status']],
            };
            ?>
            <div class="border <?= $qBg[0] ?> rounded-lg p-4 mb-3 last:mb-0">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-zinc-900 dark:text-white">v<?= $q['version'] ?></span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-white dark:bg-zinc-700 text-zinc-800 dark:text-emerald-200 border border-current"><?= htmlspecialchars($qBg[1]) ?></span>
                        <?php if ($q['valid_until']): ?>
                        <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.custom.q_valid_until', ['date' => $q['valid_until']])) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(date('Y-m-d', strtotime($q['sent_at'] ?: $q['created_at']))) ?></span>
                </div>

                <table class="w-full text-xs mb-3">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-zinc-600 text-[10px] text-zinc-500 uppercase tracking-wider">
                            <th class="text-left py-1.5 font-medium"><?= htmlspecialchars(__('services.custom.q_col_label')) ?></th>
                            <th class="text-right py-1.5 font-medium w-14"><?= htmlspecialchars(__('services.custom.q_col_qty')) ?></th>
                            <th class="text-right py-1.5 font-medium w-24"><?= htmlspecialchars(__('services.custom.q_col_unit')) ?></th>
                            <th class="text-right py-1.5 font-medium w-24"><?= htmlspecialchars(__('services.custom.q_col_amount')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($q['items'] as $it): ?>
                        <tr class="border-b border-gray-100 dark:border-zinc-700/50">
                            <td class="py-1.5 text-zinc-800 dark:text-emerald-100">
                                <?= htmlspecialchars($it['label']) ?>
                                <?php if (!empty($it['note'])): ?><div class="text-[10px] text-zinc-400 dark:text-emerald-300/70 mt-0.5"><?= htmlspecialchars($it['note']) ?></div><?php endif; ?>
                            </td>
                            <td class="py-1.5 text-right tabular-nums text-zinc-700 dark:text-emerald-200"><?= rtrim(rtrim(number_format($it['qty'], 2), '0'), '.') ?></td>
                            <td class="py-1.5 text-right tabular-nums text-zinc-700 dark:text-emerald-200">¥<?= number_format($it['unit_price']) ?></td>
                            <td class="py-1.5 text-right tabular-nums font-medium text-zinc-800 dark:text-emerald-100">¥<?= number_format($it['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="flex justify-end">
                    <div class="w-64 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-emerald-300/70"><?= htmlspecialchars(__('services.custom.q_subtotal')) ?></span>
                            <span class="tabular-nums text-zinc-700 dark:text-emerald-200">¥<?= number_format($q['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500 dark:text-emerald-300/70"><?= htmlspecialchars(__('services.custom.q_tax', ['rate' => rtrim(rtrim(number_format((float)$q['tax_rate'], 2), '0'), '.')])) ?></span>
                            <span class="tabular-nums text-zinc-700 dark:text-emerald-200">¥<?= number_format($q['tax_amount']) ?></span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-gray-300 dark:border-zinc-600 font-bold text-zinc-900 dark:text-emerald-50">
                            <span><?= htmlspecialchars(__('services.custom.q_total')) ?></span>
                            <span class="tabular-nums text-base">¥<?= number_format($q['total']) ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($q['notes']): ?>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-600">
                    <p class="text-[10px] text-zinc-400 dark:text-emerald-300/70 mb-1"><?= htmlspecialchars(__('services.custom.q_notes')) ?></p>
                    <p class="text-xs text-zinc-700 dark:text-emerald-100 whitespace-pre-wrap"><?= htmlspecialchars($q['notes']) ?></p>
                </div>
                <?php endif; ?>

                <?php if (!empty($q['payments']) && in_array($q['status'], ['sent','accepted'], true)): ?>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-600">
                    <p class="text-[10px] text-zinc-400 mb-1.5"><?= htmlspecialchars(__('services.custom.q_payment_schedule')) ?></p>
                    <div class="space-y-1">
                    <?php foreach ($q['payments'] as $_pay):
                        $_pCls = match ($_pay['status']) {
                            'paid' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-200',
                            'cancelled' => 'bg-gray-50 dark:bg-zinc-700/30 text-zinc-400 line-through',
                            default => 'bg-emerald-50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-300',
                        };
                        $_canPay = ($q['status'] === 'accepted' && $_pay['status'] === 'pending'
                                    && in_array($project['status'], ['contracted','in_progress','review','delivered','maintenance'], true));
                    ?>
                        <div class="flex items-center justify-between gap-2 text-xs px-2.5 py-1.5 rounded <?= $_pCls ?>">
                            <span class="flex items-center gap-1.5 flex-1 min-w-0">
                                <span class="font-medium truncate"><?= htmlspecialchars($_pay['label']) ?></span>
                                <?php if ($_pay['due_date']): ?><span class="text-[10px] opacity-60 whitespace-nowrap">(<?= htmlspecialchars($_pay['due_date']) ?>)</span><?php endif; ?>
                                <?php if ($_pay['status'] === 'paid'): ?><span class="text-[10px] px-1 py-0.5 bg-emerald-200 dark:bg-emerald-800 rounded whitespace-nowrap">✓ <?= htmlspecialchars(date('Y-m-d', strtotime($_pay['paid_at']))) ?></span><?php endif; ?>
                            </span>
                            <span class="tabular-nums whitespace-nowrap font-medium">¥<?= number_format($_pay['amount']) ?></span>
                            <?php if ($_canPay): ?>
                            <button type="button" onclick="openPayModal(<?= (int)$_pay['id'] ?>, <?= (int)$_pay['amount'] ?>, '<?= htmlspecialchars(addslashes($_pay['label'])) ?>')"
                                class="ml-1 px-2.5 py-1 text-[11px] font-bold text-white bg-blue-600 hover:bg-blue-700 rounded whitespace-nowrap">
                                💳 <?= htmlspecialchars(__('services.custom.btn_pay')) ?>
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($q['status'] === 'sent'): ?>
                <div class="mt-4 pt-3 border-t border-gray-200 dark:border-zinc-600 flex items-center justify-end gap-2">
                    <button onclick="rejectQuote(<?= (int)$q['id'] ?>)" class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                        <?= htmlspecialchars(__('services.custom.btn_reject')) ?>
                    </button>
                    <button onclick="acceptQuote(<?= (int)$q['id'] ?>)" class="px-4 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">
                        ✓ <?= htmlspecialchars(__('services.custom.btn_accept')) ?>
                    </button>
                </div>
                <?php elseif ($q['status'] === 'rejected' && $q['reject_reason']): ?>
                <p class="mt-3 text-[11px] text-red-600"><?= htmlspecialchars(__('services.custom.q_rejected_reason', ['reason' => $q['reject_reason']])) ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<script>
var siteBaseUrl = '<?= $baseUrl ?>';
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

function acceptQuote(quoteId) {
    if (!confirm('<?= htmlspecialchars(__('services.custom.confirm_accept')) ?>')) return;
    api('project_accept_quote', { project_id: projectId, quote_id: quoteId }).then(function(d){
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}

function msApprove(id) {
    var note = prompt('<?= htmlspecialchars(__('services.custom.ms_approve_prompt')) ?>') || '';
    if (note === null) return;
    api('milestone_approve', { milestone_id: id, note: note }).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}
function msRequestRevision(id) {
    var note = prompt('<?= htmlspecialchars(__('services.custom.ms_revision_prompt')) ?>');
    if (!note) return;
    api('milestone_request_revision', { milestone_id: id, note: note }).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}

function rejectQuote(quoteId) {
    var reason = prompt('<?= htmlspecialchars(__('services.custom.prompt_reject_reason')) ?>');
    if (reason === null) return;
    api('project_reject_quote', { project_id: projectId, quote_id: quoteId, reason: reason }).then(function(d){
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}

// ==== PAY.JP 결제 모달 ====
var payjpInstance = null;
var payjpElements = null;
var pjNumberEl = null, pjExpiryEl = null, pjCvcEl = null;
var currentPaymentId = 0;

function openPayModal(paymentId, amount, label) {
    currentPaymentId = paymentId;
    document.getElementById('payModalLabel').textContent = label;
    document.getElementById('payModalAmount').textContent = '¥' + amount.toLocaleString();
    document.getElementById('payModalError').classList.add('hidden');
    document.getElementById('payModalError').textContent = '';

    var m = document.getElementById('payModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';

    // PAY.JP 인스턴스 lazy 초기화
    var pubKey = <?= json_encode($_payPubKey) ?>;
    if (!pubKey) {
        document.getElementById('payModalError').textContent = 'PAY.JP not configured';
        document.getElementById('payModalError').classList.remove('hidden');
        return;
    }
    if (!payjpInstance) {
        if (typeof Payjp !== 'function') {
            document.getElementById('payModalError').textContent = 'Payment library not loaded';
            document.getElementById('payModalError').classList.remove('hidden');
            return;
        }
        payjpInstance = Payjp(pubKey);
        payjpElements = payjpInstance.elements();
        pjNumberEl = payjpElements.create('cardNumber');
        pjExpiryEl = payjpElements.create('cardExpiry');
        pjCvcEl = payjpElements.create('cardCvc');
        pjNumberEl.mount('#pj_number');
        pjExpiryEl.mount('#pj_expiry');
        pjCvcEl.mount('#pj_cvc');
    }
}
function closePayModal() {
    var m = document.getElementById('payModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function submitPayment() {
    if (!payjpInstance || !pjNumberEl) return;
    var btn = document.getElementById('payModalSubmit');
    btn.disabled = true;
    document.getElementById('payModalError').classList.add('hidden');

    payjpInstance.createToken(pjNumberEl).then(function(r) {
        if (r.error) {
            btn.disabled = false;
            document.getElementById('payModalError').textContent = r.error.message || 'Card error';
            document.getElementById('payModalError').classList.remove('hidden');
            return;
        }
        return api('project_pay_installment', {
            project_id: projectId,
            payment_id: currentPaymentId,
            card_token: r.id,
        }).then(function(d) {
            btn.disabled = false;
            if (d.success) {
                alert('<?= htmlspecialchars(__('services.custom.pay_done')) ?>');
                location.reload();
            } else {
                document.getElementById('payModalError').textContent = d.message || 'error';
                document.getElementById('payModalError').classList.remove('hidden');
            }
        });
    }).catch(function(e) {
        btn.disabled = false;
        document.getElementById('payModalError').textContent = (e && e.message) || 'error';
        document.getElementById('payModalError').classList.remove('hidden');
    });
}

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
            var sender = isAdmin ? <?= json_encode(__('services.detail.support_admin'), JSON_UNESCAPED_UNICODE) ?> : (m.sender_name || m.sender_email || 'me');
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
                        html += '<a href="' + dlUrl + '" target="_blank" class="block"><img src="' + inUrl + '" alt="" class="max-h-40 rounded border border-gray-200 dark:border-zinc-600" /></a>';
                    } else {
                        html += '<a href="' + dlUrl + '" class="flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700 hover:bg-blue-50 dark:hover:bg-zinc-600 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600 transition">'
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
        } else {
            alert(d.message || 'error');
        }
    }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
}

document.addEventListener('DOMContentLoaded', channelLoad);
<?php endif; ?>
</script>

<?php if ($_payPubKey): ?>
<script src="https://js.pay.jp/v2/pay.js"></script>
<?php endif; ?>

<!-- 결제 모달 -->
<div id="payModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closePayModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">💳 <?= htmlspecialchars(__('services.custom.pay_title')) ?></h3>
            <button type="button" onclick="closePayModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                <p class="text-[10px] text-zinc-500 uppercase mb-1"><?= htmlspecialchars(__('services.custom.pay_summary')) ?></p>
                <p id="payModalLabel" class="text-sm font-bold text-zinc-900 dark:text-white"></p>
                <p id="payModalAmount" class="text-2xl font-bold text-blue-700 dark:text-blue-300 mt-1 tabular-nums"></p>
            </div>
            <div id="payModalError" class="hidden p-2 bg-red-50 border border-red-200 text-red-700 text-[11px] rounded"></div>
            <div>
                <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_number')) ?></label>
                <div id="pj_number" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_expiry')) ?></label>
                    <div id="pj_expiry" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                </div>
                <div>
                    <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_cvc')) ?></label>
                    <div id="pj_cvc" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                </div>
            </div>
            <p class="text-[10px] text-zinc-400 text-center"><?= htmlspecialchars(__('services.custom.pay_secure_hint')) ?></p>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2">
            <button type="button" onclick="closePayModal()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="payModalSubmit" onclick="submitPayment()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                <?= htmlspecialchars(__('services.custom.btn_pay_now')) ?>
            </button>
        </div>
    </div>
</div>
