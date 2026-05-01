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
foreach ($quotes as &$_q) { $_q['items'] = json_decode($_q['items'] ?? '[]', true) ?: []; }
unset($_q);

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
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-3 text-xs whitespace-pre-wrap"><?= htmlspecialchars($project['requirements']) ?></div>
            </div>
            <?php if ($project['reference_urls']): ?>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.custom.f_reference_urls')) ?></p>
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-3 text-xs whitespace-pre-wrap font-mono"><?= htmlspecialchars($project['reference_urls']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($project['attachments'])): ?>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-1">📎 <?= htmlspecialchars(__('services.custom.f_attachments')) ?></p>
                <div class="space-y-1">
                <?php foreach ($project['attachments'] as $idx => $a):
                    $isImg = preg_match('/^(jpg|jpeg|png|gif|webp|svg)$/i', $a['ext']);
                    // 프로젝트 첨부는 별도 다운로드 핸들러 필요 — Phase 1 에서는 단순 표시 (다운로드 기능은 다음 phase)
                ?>
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
                'sent' => ['bg-amber-50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-700', __('services.custom.q_sent')],
                'accepted' => ['bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-700', __('services.custom.q_accepted')],
                'rejected' => ['bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-700', __('services.custom.q_rejected')],
                'superseded' => ['bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-600 opacity-60', __('services.custom.q_superseded')],
                default => ['bg-gray-50 border-gray-200', $q['status']],
            };
            ?>
            <div class="border <?= $qBg[0] ?> rounded-lg p-4 mb-3 last:mb-0">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-zinc-900 dark:text-white">v<?= $q['version'] ?></span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-white dark:bg-zinc-800 border border-current"><?= htmlspecialchars($qBg[1]) ?></span>
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
                            <td class="py-1.5 text-zinc-800 dark:text-zinc-200">
                                <?= htmlspecialchars($it['label']) ?>
                                <?php if (!empty($it['note'])): ?><div class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars($it['note']) ?></div><?php endif; ?>
                            </td>
                            <td class="py-1.5 text-right tabular-nums"><?= rtrim(rtrim(number_format($it['qty'], 2), '0'), '.') ?></td>
                            <td class="py-1.5 text-right tabular-nums">¥<?= number_format($it['unit_price']) ?></td>
                            <td class="py-1.5 text-right tabular-nums font-medium">¥<?= number_format($it['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="flex justify-end">
                    <div class="w-64 text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-zinc-500"><?= htmlspecialchars(__('services.custom.q_subtotal')) ?></span>
                            <span class="tabular-nums">¥<?= number_format($q['subtotal']) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500"><?= htmlspecialchars(__('services.custom.q_tax', ['rate' => rtrim(rtrim(number_format((float)$q['tax_rate'], 2), '0'), '.')])) ?></span>
                            <span class="tabular-nums">¥<?= number_format($q['tax_amount']) ?></span>
                        </div>
                        <div class="flex justify-between pt-1 border-t border-gray-300 dark:border-zinc-600 font-bold text-zinc-900 dark:text-white">
                            <span><?= htmlspecialchars(__('services.custom.q_total')) ?></span>
                            <span class="tabular-nums text-base">¥<?= number_format($q['total']) ?></span>
                        </div>
                    </div>
                </div>

                <?php if ($q['notes']): ?>
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-zinc-600">
                    <p class="text-[10px] text-zinc-400 mb-1"><?= htmlspecialchars(__('services.custom.q_notes')) ?></p>
                    <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap"><?= htmlspecialchars($q['notes']) ?></p>
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

function rejectQuote(quoteId) {
    var reason = prompt('<?= htmlspecialchars(__('services.custom.prompt_reject_reason')) ?>');
    if (reason === null) return;
    api('project_reject_quote', { project_id: projectId, quote_id: quoteId, reason: reason }).then(function(d){
        if (d.success) location.reload();
        else alert(d.message || 'error');
    });
}
</script>
