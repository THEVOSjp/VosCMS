<?php
/**
 * 마이페이지 서비스 관리 — 웹 호스팅 탭
 */
$sub = $subs[0];
$st = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
$sc = $sub['service_class'] ?? 'recurring';
$meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
$capacity = $meta['capacity'] ?? $order['hosting_capacity'] ?? '-';
$server = $meta['server'] ?? [];
$ftp = $server['ftp'] ?? [];
$db = $server['db'] ?? [];
$env = $server['env'] ?? [];
$usage = $server['usage'] ?? [];

// 시스템 등록 도메인 — 한도 없음, 사용량만 표시
$_isSystemImported = (($meta['mail_provision']['origin'] ?? '') === 'system_imported');

// extra_storage 합산 — 부가서비스 결제분
$_capParseGB = function($s) {
    if (!preg_match('/([\d.]+)\s*(GB|TB|MB)/i', (string)$s, $m)) return 0.0;
    $n = (float)$m[1];
    $u = strtoupper($m[2]);
    return $u === 'TB' ? $n * 1024 : ($u === 'MB' ? $n / 1024 : $n);
};
$_capFormat = function($gb) {
    if ($gb >= 1024) return rtrim(rtrim(number_format($gb / 1024, 2, '.', ''), '0'), '.') . 'TB';
    return rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.') . 'GB';
};
$_extraGB = 0.0;
$_extraList = [];
foreach (($meta['extra_storage'] ?? []) as $_es) {
    $g = $_capParseGB($_es['capacity'] ?? '');
    if ($g > 0) { $_extraGB += $g; $_extraList[] = $_es['capacity']; }
}
$_baseGB = $_capParseGB($capacity);
$_totalGB = $_baseGB + $_extraGB;
?>

<div class="space-y-4">
    <!-- 호스팅 요약 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_localizeLabel($sub)) ?></h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($sc === 'recurring' && $sub['status'] === 'active'): ?>
                <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                           onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                    <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
                <?php elseif ($sc === 'free' && $sub['status'] === 'active'): ?>
                <button onclick="serviceAction('request_renewal',{subscription_id:<?= $sub['id'] ?>}).then(function(d){alert(d.message||<?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>)})"
                        class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
                <?php endif; ?>
            </div>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_capacity')) ?></p>
                    <?php if ($_isSystemImported): ?>
                    <p class="font-semibold text-violet-600 dark:text-violet-400"><?= htmlspecialchars(__('services.mypage.system_unlimited')) ?></p>
                    <?php elseif ($_extraGB > 0): ?>
                    <p class="font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($_capFormat($_totalGB)) ?></p>
                    <p class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars($capacity) ?> + <?= htmlspecialchars(implode(' + ', $_extraList)) ?></p>
                    <?php else: ?>
                    <p class="font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($capacity) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_period')) ?></p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_price')) ?></p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['unit_price'], $sub['currency']) . __('services.order.hosting.price_per_month') : __('services.order.summary.free') ?></p>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_server_env')) ?></p>
                    <p class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(($env['php'] ?? '-') . ' / ' . ($env['mysql'] ?? '-')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- 사용량 (관리자가 설정한 경우에만 표시) -->
    <?php if (!empty($usage['hdd_total']) || !empty($usage['traffic_total'])): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= htmlspecialchars(__('services.detail.usage_section')) ?></p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php if (!empty($usage['hdd_total'])):
                $hddPct = round(((float)($usage['hdd_used'] ?? 0) / (float)$usage['hdd_total']) * 100, 1);
                $hddColor = $hddPct > 80 ? 'red' : ($hddPct > 60 ? 'amber' : 'blue');
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.detail.f_hdd_capacity')) ?></span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($usage['hdd_used'] ?? '0') ?> / <?= htmlspecialchars($usage['hdd_total']) ?></span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-zinc-700 rounded-full h-2.5">
                    <div class="bg-<?= $hddColor ?>-500 h-2.5 rounded-full transition-all" style="width: <?= min(100, $hddPct) ?>%"></div>
                </div>
                <p class="text-[10px] text-zinc-400 mt-1 text-right"><?= $hddPct ?>%</p>
            </div>
            <?php endif; ?>
            <?php if (!empty($usage['traffic_total'])):
                $trafPct = round(((float)($usage['traffic_used'] ?? 0) / (float)$usage['traffic_total']) * 100, 1);
                $trafColor = $trafPct > 80 ? 'red' : ($trafPct > 60 ? 'amber' : 'green');
            ?>
            <div>
                <div class="flex items-center justify-between text-xs mb-1.5">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.detail.f_traffic')) ?></span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($usage['traffic_used'] ?? '0') ?> / <?= htmlspecialchars($usage['traffic_total']) ?></span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-zinc-700 rounded-full h-2.5">
                    <div class="bg-<?= $trafColor ?>-500 h-2.5 rounded-full transition-all" style="width: <?= min(100, $trafPct) ?>%"></div>
                </div>
                <p class="text-[10px] text-zinc-400 mt-1 text-right"><?= $trafPct ?>%</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- DB 접속정보 + 백업 + phpMyAdmin -->
    <?php
    // 호스팅 프로비저닝으로 자동 생성된 DB 비밀번호
    $_dbPass = $db['db_pass'] ?? $db['pass'] ?? '';
    $_dbName = $db['db_name'] ?? $db['name'] ?? '';
    $_dbUser = $db['db_user'] ?? $db['user'] ?? '';
    $_dbHost = $db['db_host'] ?? $db['host'] ?? 'localhost';

    // phpMyAdmin 로케일 매핑 (VosCMS 로케일 → phpMyAdmin lang code)
    $_pmaLangMap = [
        'ko' => 'ko', 'en' => 'en', 'ja' => 'ja', 'de' => 'de', 'es' => 'es',
        'fr' => 'fr', 'id' => 'id', 'ru' => 'ru', 'tr' => 'tr', 'vi' => 'vi',
        'zh_CN' => 'zh_CN', 'zh_TW' => 'zh_TW', 'mn' => 'en', // mn 미지원 → en
    ];
    $_currentLocale = function_exists('current_locale') ? current_locale() : 'ko';
    $_pmaLang = $_pmaLangMap[$_currentLocale] ?? 'en';
    $_pmaUrl = 'https://pma.voscms.com/?lang=' . urlencode($_pmaLang);

    $_subId = $sub['id'] ?? null;
    ?>
    <?php if (!empty($_dbName) || $_subId): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= htmlspecialchars(__('services.detail.access_section')) ?></p>

        <!-- 액션 버튼 — 백업 + phpMyAdmin -->
        <div class="flex flex-wrap gap-2 mb-4">
            <?php if ($_subId): ?>
            <button type="button" onclick="hostingRequestBackup(<?= (int)$_subId ?>, this)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition disabled:opacity-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4"/></svg>
                <?= htmlspecialchars(__('services.detail.btn_site_backup')) ?>
            </button>
            <?php endif; ?>
            <?php if (!empty($_dbName)): ?>
            <a href="<?= htmlspecialchars($_pmaUrl) ?>" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
                <?= htmlspecialchars(__('services.detail.btn_open_phpmyadmin')) ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if ($_dbPass !== ''): ?>
        <!-- 비밀번호 안내 메시지 -->
        <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-[11px] text-blue-800 dark:text-blue-300 leading-relaxed">
                💡 <?= htmlspecialchars(__('services.detail.password_notice')) ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($_dbName !== ''): ?>
        <div>
            <p class="text-xs font-medium text-zinc-600 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('services.detail.f_database')) ?></p>
            <table class="w-full text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-20"><?= htmlspecialchars(__('services.detail.f_address')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_dbHost) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.f_db_name')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_dbName) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.f_id')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_dbUser ?: '-') ?></td></tr>
                    <?php if ($_dbPass !== ''): ?>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.f_password')) ?></td>
                        <td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200">
                            <span class="hosting-pw select-all" data-real="<?= htmlspecialchars($_dbPass) ?>">••••••••••••</span>
                            <button type="button" onclick="(function(b){var s=b.previousElementSibling;if(s.textContent==='••••••••••••'){s.textContent=s.dataset.real;b.textContent='🙈';}else{s.textContent='••••••••••••';b.textContent='👁';}})(this)" class="ml-1 text-xs hover:opacity-70" title="show/hide">👁</button>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($db['size'])): ?>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.f_capacity')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($db['size']) ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function hostingRequestBackup(subId, btn) {
        if (!confirm(<?= json_encode(__('services.detail.confirm_site_backup'), JSON_UNESCAPED_UNICODE) ?>)) return;
        btn.disabled = true;
        var origText = btn.innerHTML;
        btn.textContent = <?= json_encode(__('services.detail.backup_in_progress'), JSON_UNESCAPED_UNICODE) ?>;
        serviceAction('request_backup', { subscription_id: subId })
            .then(function(d) {
                if (d.success && d.download_url) {
                    var a = document.createElement('a');
                    a.href = d.download_url;
                    a.download = d.filename || '';
                    document.body.appendChild(a); a.click(); document.body.removeChild(a);
                    btn.textContent = <?= json_encode(__('services.detail.backup_ready'), JSON_UNESCAPED_UNICODE) ?>;
                    setTimeout(function() { btn.disabled = false; btn.innerHTML = origText; }, 5000);
                } else {
                    alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                    btn.disabled = false; btn.innerHTML = origText;
                }
            }).catch(function(e) {
                alert(e.message);
                btn.disabled = false; btn.innerHTML = origText;
            });
    }
    </script>
    <?php endif; ?>
</div>
