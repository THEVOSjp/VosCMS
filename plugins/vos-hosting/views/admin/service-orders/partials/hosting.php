<?php
/**
 * 관리자 서비스 상세 — 웹 호스팅 탭
 * $subs: hosting 타입 구독 배열
 * $order, $statusLabels, $fmtPrice, $adminUrl, $pdo, $prefix 사용
 */
if (empty($subs)) {
    echo '<div class="px-5 py-12 text-center text-sm text-zinc-400">' . htmlspecialchars(__('services.admin_orders.empty_hosting')) . '</div>';
    return;
}
$sub = $subs[0];
$sst = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
$_pendingTitle = __('services.admin_orders.btn_pending');
$sc = $sub['service_class'] ?? 'recurring';
$meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
$capacity = $meta['capacity'] ?? $order['hosting_capacity'] ?? '-';

// 서버 접속정보 (metadata.server에 저장)
$server = $meta['server'] ?? [];
$ftp = $server['ftp'] ?? [];
$db = $server['db'] ?? [];
$env = $server['env'] ?? [];
$usage = $server['usage'] ?? [];

// 추가 경로 — provision 시 자동 채워짐. 누락 케이스 위해 fallback 계산
$_orderNum = $order['order_number'] ?? '';
$_username = $ftp['user'] ?? ('vos_' . preg_replace('/[^A-Za-z0-9]/', '', $_orderNum));
$_homeDir  = $server['home']      ?? ('/var/www/customers/' . $_orderNum);
$_docroot  = $server['docroot']   ?? ($_homeDir . '/public_html');
$_vhostFile = $server['vhost']    ?? ('/etc/nginx/sites-available/' . ($order['domain'] ?? '') . '.conf');
$_fpmPool   = $server['fpm_pool'] ?? ('/etc/php/8.3/fpm/pool.d/' . $_orderNum . '.conf');

// VosCMS 자동설치 정보 — install addon 의 metadata 에서 추출
$_installInfo = null;
$_installCompletedAt = null;
$_installAdminUrl = null;
foreach (($subscriptions ?? []) as $_s) {
    if (($_s['type'] ?? '') !== 'addon') continue;
    $_sm = json_decode($_s['metadata'] ?? '{}', true) ?: [];
    if (($_sm['addon_id'] ?? '') === 'install' && !empty($_sm['install_info'])) {
        $_installInfo = $_sm['install_info'];
        $_installCompletedAt = $_sm['install_completed_at'] ?? null;
        $_installAdminUrl = $_sm['install_admin_url'] ?? null;
        break;
    }
}
?>

<!-- 호스팅 정보 요약 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($_localizeLabel($sub)) ?></p>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium <?= $sst[1] ?>"><?= htmlspecialchars($sst[0]) ?></span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-400"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : __('services.order.summary.free') ?></span>
            <?php if ($sub['auto_renew']): ?>
            <span class="text-[10px] px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_capacity')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($capacity) ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_period')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_server_env')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($env['php'] ?? '-') ?> / <?= htmlspecialchars($env['mysql'] ?? '-') ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_id')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200 font-mono"><?= htmlspecialchars($ftp['user'] ?? '-') ?></p>
        </div>
    </div>
</div>

<!-- 실시간 호스팅 상태 (SSL / 디스크 / DB / nginx) -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">실시간 상태</p>
        <button type="button" onclick="loadHostingStatus()" class="text-xs text-blue-600 hover:underline">↻ 새로고침</button>
    </div>
    <div id="hostingStatusBox" class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div class="p-2.5 border border-gray-200 dark:border-zinc-700 rounded-lg">
            <p class="text-[10px] text-zinc-400 mb-0.5">SSL 인증서</p>
            <p class="font-medium text-zinc-700 dark:text-zinc-200" id="status_ssl">로딩…</p>
        </div>
        <div class="p-2.5 border border-gray-200 dark:border-zinc-700 rounded-lg">
            <p class="text-[10px] text-zinc-400 mb-0.5">디스크 사용량</p>
            <p class="font-medium text-zinc-700 dark:text-zinc-200" id="status_disk">로딩…</p>
        </div>
        <div class="p-2.5 border border-gray-200 dark:border-zinc-700 rounded-lg">
            <p class="text-[10px] text-zinc-400 mb-0.5">DB 사용량</p>
            <p class="font-medium text-zinc-700 dark:text-zinc-200" id="status_db">로딩…</p>
        </div>
        <div class="p-2.5 border border-gray-200 dark:border-zinc-700 rounded-lg">
            <p class="text-[10px] text-zinc-400 mb-0.5">nginx vhost</p>
            <p class="font-medium text-zinc-700 dark:text-zinc-200" id="status_nginx">로딩…</p>
        </div>
    </div>
</div>

<!-- 사용량 -->
<?php if (!empty($usage['hdd_used']) || !empty($usage['traffic_used'])): ?>
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-3"><?= htmlspecialchars(__('services.admin_orders.usage_section_admin')) ?></p>
    <div class="grid grid-cols-2 gap-4">
        <?php if (!empty($usage['hdd_total'])): ?>
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-zinc-500"><?= htmlspecialchars(__('services.detail.f_hdd_capacity')) ?></span>
                <span class="font-medium"><?= htmlspecialchars($usage['hdd_used'] ?? '0') ?> / <?= htmlspecialchars($usage['hdd_total']) ?></span>
            </div>
            <?php $hddPct = $usage['hdd_total'] ? round(((float)$usage['hdd_used'] / (float)$usage['hdd_total']) * 100, 1) : 0; ?>
            <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: <?= min(100, $hddPct) ?>%"></div>
            </div>
            <p class="text-[10px] text-zinc-400 mt-0.5 text-right"><?= $hddPct ?>%</p>
        </div>
        <?php endif; ?>
        <?php if (!empty($usage['traffic_total'])): ?>
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-zinc-500"><?= htmlspecialchars(__('services.admin_orders.f_traffic_capacity')) ?></span>
                <span class="font-medium"><?= htmlspecialchars($usage['traffic_used'] ?? '0') ?> / <?= htmlspecialchars($usage['traffic_total']) ?></span>
            </div>
            <?php $trafPct = $usage['traffic_total'] ? round(((float)$usage['traffic_used'] / (float)$usage['traffic_total']) * 100, 1) : 0; ?>
            <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: <?= min(100, $trafPct) ?>%"></div>
            </div>
            <p class="text-[10px] text-zinc-400 mt-0.5 text-right"><?= $trafPct ?>%</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
// 비밀번호 표시용 마스킹 + 토글 마크업 헬퍼
$_pwBox = function($id, $real) {
    if ($real === null || $real === '') return '<span class="text-zinc-400">-</span>';
    return '<span class="font-mono text-zinc-800 dark:text-zinc-200 select-all" data-real="'.htmlspecialchars($real).'" id="'.$id.'">••••••••</span>'
         . ' <button type="button" onclick="(function(b){var s=document.getElementById(\''.$id.'\');if(s.textContent===\'••••••••\'){s.textContent=s.dataset.real;b.textContent=\'🙈\';}else{s.textContent=\'••••••••\';b.textContent=\'👁\';}})(this)" class="text-xs hover:opacity-70" title="show/hide">👁</button>'
         . ' <button type="button" onclick="navigator.clipboard.writeText(\''.htmlspecialchars(addslashes($real)).'\').then(()=>{this.textContent=\'✓\';setTimeout(()=>this.textContent=\'📋\',1200);})" class="text-xs hover:opacity-70 ml-1" title="copy">📋</button>';
};
$_dbPass  = $db['db_pass']    ?? null;
$_dbUser  = $db['db_user']    ?? ($db['user'] ?? null);
$_dbName  = $db['db_name']    ?? ($db['name'] ?? null);
?>

<!-- FTP/SFTP 접속정보 + DB 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- FTP/SFTP -->
        <div>
            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">FTP / SFTP</p>
            <table class="w-full text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.admin_orders.f_ftp_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['host'] ?? $order['domain'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_ftp_ip')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['ip'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_ftp_id')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_username) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">FTP <?= htmlspecialchars(__('services.admin_orders.f_port_num')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars((string)($ftp['port'] ?? '21')) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">SFTP host</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['sftp_host'] ?? '-') ?> : <?= htmlspecialchars((string)($ftp['sftp_port'] ?? '-')) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">SSH/SFTP <?= htmlspecialchars(__('services.detail.f_password') ?: '비밀번호') ?></td><td class="py-1.5 text-xs"><?= $_pwBox('ftp_pw_'.$sub['id'], $ftp['password'] ?? null) ?></td></tr>
                </tbody>
            </table>
        </div>
        <!-- DB -->
        <div>
            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.admin_orders.db_section_title')) ?></p>
            <table class="w-full text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.admin_orders.f_db_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($db['db_host'] ?? $db['host'] ?? 'localhost') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.f_db_name')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_dbName ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_db_id')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_dbUser ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">DB <?= htmlspecialchars(__('services.detail.f_password') ?: '비밀번호') ?></td><td class="py-1.5 text-xs"><?= $_pwBox('db_pw_'.$sub['id'], $_dbPass) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_db_size')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200" id="dbSizeCell_<?= $sub['id'] ?>"><?= htmlspecialchars($db['size'] ?? __('services.detail.db_unlimited')) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 시스템 경로 + 빠른 명령 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2">시스템 경로 / 빠른 명령</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">홈 디렉토리</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_homeDir) ?></span>
            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($_homeDir)) ?>').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='📋',1200);})" class="text-xs hover:opacity-70 shrink-0">📋</button>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">docroot</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_docroot) ?></span>
            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($_docroot)) ?>').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='📋',1200);})" class="text-xs hover:opacity-70 shrink-0">📋</button>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">nginx vhost</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_vhostFile) ?></span>
            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($_vhostFile)) ?>').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='📋',1200);})" class="text-xs hover:opacity-70 shrink-0">📋</button>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">php-fpm pool</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_fpmPool) ?></span>
            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($_fpmPool)) ?>').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='📋',1200);})" class="text-xs hover:opacity-70 shrink-0">📋</button>
        </div>
    </div>
    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px]">
        <?php
        $_sshCmd   = "sudo -u {$_username} -i";
        $_mysqlCmd = "mysql -u " . ($_dbUser ?? '') . " -p " . ($_dbName ?? '');
        $_logCmd   = "sudo tail -f /var/log/nginx/access.log | grep " . ($order['domain'] ?? '');
        $_quickCmds = [
            'SSH 사용자 전환'    => $_sshCmd,
            'MySQL 접속'         => $_mysqlCmd,
            'nginx 접근 로그 tail' => $_logCmd,
        ];
        foreach ($_quickCmds as $_lbl => $_cmd): ?>
        <div class="flex items-center gap-2 px-2 py-1 bg-zinc-50 dark:bg-zinc-700/30 rounded">
            <span class="text-zinc-500 dark:text-zinc-400 shrink-0 w-32"><?= htmlspecialchars($_lbl) ?></span>
            <code class="font-mono text-[10px] text-zinc-700 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_cmd) ?></code>
            <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars(addslashes($_cmd)) ?>').then(()=>{this.textContent='✓';setTimeout(()=>this.textContent='📋',1200);})" class="text-xs hover:opacity-70 shrink-0">📋</button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- VosCMS 자동설치 정보 (설치 부가서비스 포함 시) -->
<?php if ($_installInfo): ?>
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-2">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">VosCMS 자동 설치</p>
        <?php if ($_installCompletedAt): ?>
        <span class="text-[10px] px-2 py-0.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 rounded">✓ 설치 완료 — <?= htmlspecialchars(date('Y-m-d H:i', strtotime($_installCompletedAt))) ?></span>
        <?php else: ?>
        <span class="text-[10px] px-2 py-0.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 rounded">⏳ 설치 대기/진행 중</span>
        <?php endif; ?>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1.5 text-xs">
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">관리자 URL</span>
            <?php if ($_installAdminUrl): ?>
            <a href="<?= htmlspecialchars($_installAdminUrl) ?>" target="_blank" class="font-mono text-blue-600 hover:underline break-all flex-1"><?= htmlspecialchars($_installAdminUrl) ?></a>
            <?php else: ?>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1">https://<?= htmlspecialchars($order['domain'] ?? '') ?>/admin</span>
            <?php endif; ?>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">사이트 제목</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_installInfo['site_title'] ?? '-') ?></span>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">관리자 ID</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_installInfo['admin_id'] ?? '-') ?></span>
        </div>
        <div class="flex items-baseline gap-2">
            <span class="text-zinc-400 w-24 shrink-0">관리자 이메일</span>
            <span class="font-mono text-zinc-800 dark:text-zinc-200 break-all flex-1"><?= htmlspecialchars($_installInfo['admin_email'] ?? '-') ?></span>
        </div>
        <div class="flex items-baseline gap-2 md:col-span-2">
            <span class="text-zinc-400 w-24 shrink-0">관리자 비번</span>
            <span class="text-xs flex-1"><?= $_pwBox('voscms_admin_pw_'.$sub['id'], $_installInfo['admin_pw'] ?? null) ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 서버 접속정보 편집 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider"><?= htmlspecialchars(__('services.admin_orders.server_setup_section')) ?></p>
        <button onclick="toggleServerEdit()" id="btnServerEdit" class="text-xs text-blue-600 hover:underline"><?= htmlspecialchars(__('services.admin_orders.btn_edit')) ?></button>
    </div>
    <div id="serverEditForm" class="hidden space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_ftp_host')) ?></label><input id="sf_ftp_host" value="<?= htmlspecialchars($ftp['host'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs" placeholder="<?= htmlspecialchars($order['domain'] ?? '') ?>"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_ftp_ip')) ?></label><input id="sf_ftp_ip" value="<?= htmlspecialchars($ftp['ip'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs" placeholder="0.0.0.0"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_ftp_id')) ?></label><input id="sf_ftp_user" value="<?= htmlspecialchars($ftp['user'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.f_port')) ?></label><input id="sf_ftp_port" value="<?= htmlspecialchars($ftp['port'] ?? '21') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_db_host')) ?></label><input id="sf_db_host" value="<?= htmlspecialchars($db['host'] ?? 'localhost') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.f_db_name')) ?></label><input id="sf_db_name" value="<?= htmlspecialchars($db['name'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_db_id')) ?></label><input id="sf_db_user" value="<?= htmlspecialchars($db['user'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_db_size')) ?></label><input id="sf_db_size" value="<?= htmlspecialchars($db['size'] ?? __('services.detail.db_unlimited')) ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_php_ver')) ?></label><input id="sf_env_php" value="<?= htmlspecialchars($env['php'] ?? 'PHP 8.3') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_mysql_ver')) ?></label><input id="sf_env_mysql" value="<?= htmlspecialchars($env['mysql'] ?? 'MySQL 8.0') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_hdd_total')) ?></label><input id="sf_hdd_total" value="<?= htmlspecialchars($usage['hdd_total'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs" placeholder="1GB"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_traffic_total')) ?></label><input id="sf_traf_total" value="<?= htmlspecialchars($usage['traffic_total'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs" placeholder="10GB"></div>
        </div>
        <div class="flex justify-end gap-2">
            <button onclick="toggleServerEdit()" class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700"><?= htmlspecialchars(__('services.admin_orders.btn_cancel') ?: __('services.detail.btn_cancel')) ?></button>
            <button onclick="saveServerInfo(<?= $sub['id'] ?>)" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"><?= htmlspecialchars(__('services.admin_orders.btn_save')) ?></button>
        </div>
    </div>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button onclick="sendSetupEmail(<?= (int)$order['id'] ?>)" class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition"><?= htmlspecialchars(__('services.admin_orders.btn_setup_email')) ?></button>
    <button id="btnToggleVhost" data-next="0" onclick="toggleVhost()" class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition">강제 차단</button>
    <button onclick="renewSsl()" class="px-3 py-1.5 text-xs font-medium text-emerald-600 border border-emerald-200 rounded-lg hover:bg-emerald-50 transition">SSL 갱신</button>
    <button onclick="resetDbPassword()" class="px-3 py-1.5 text-xs font-medium text-amber-600 border border-amber-200 rounded-lg hover:bg-amber-50 transition">DB 비번 재설정</button>
    <button onclick="reprovision()" class="px-3 py-1.5 text-xs font-medium text-zinc-700 border border-zinc-300 rounded-lg hover:bg-zinc-50 transition">재프로비저닝</button>
</div>

<script>
function toggleServerEdit() {
    var form = document.getElementById('serverEditForm');
    form.classList.toggle('hidden');
    document.getElementById('btnServerEdit').textContent = form.classList.contains('hidden') ? <?= json_encode(__('services.admin_orders.btn_edit'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(__('services.admin_orders.btn_collapse'), JSON_UNESCAPED_UNICODE) ?>;
}

function saveServerInfo(subId) {
    var data = {
        action: 'update_server_info',
        subscription_id: subId,
        server: {
            ftp: { host: document.getElementById('sf_ftp_host').value, ip: document.getElementById('sf_ftp_ip').value, user: document.getElementById('sf_ftp_user').value, port: document.getElementById('sf_ftp_port').value },
            db: { host: document.getElementById('sf_db_host').value, name: document.getElementById('sf_db_name').value, user: document.getElementById('sf_db_user').value, size: document.getElementById('sf_db_size').value },
            env: { php: document.getElementById('sf_env_php').value, mysql: document.getElementById('sf_env_mysql').value },
            usage: { hdd_total: document.getElementById('sf_hdd_total').value, traffic_total: document.getElementById('sf_traf_total').value }
        }
    };
    ajaxPost(data).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || <?= json_encode(__('services.admin_orders.alert_save_failed'), JSON_UNESCAPED_UNICODE) ?>);
    });
}

function sendSetupEmail(orderId) {
    if (!confirm(<?= json_encode(__('services.admin_orders.confirm_send_setup_email'), JSON_UNESCAPED_UNICODE) ?>)) return;
    ajaxPost({ action: 'send_setup_email', order_id: orderId }).then(function(d) {
        alert(d.message || (d.success ? <?= json_encode(__('services.admin_orders.alert_send_done'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(__('services.admin_orders.alert_send_failed'), JSON_UNESCAPED_UNICODE) ?>));
    });
}

// === 실시간 호스팅 상태 조회 ===
function loadHostingStatus() {
    ['status_ssl','status_disk','status_db','status_nginx'].forEach(function(id){
        var e = document.getElementById(id); if (e) e.textContent = '로딩…';
    });
    ajaxPost({ action: 'hosting_status', order_id: orderId }).then(function(d) {
        if (!d.success) {
            ['status_ssl','status_disk','status_db','status_nginx'].forEach(function(id){
                var e = document.getElementById(id); if (e) e.textContent = '조회 실패';
            });
            return;
        }
        var sslEl = document.getElementById('status_ssl');
        if (d.ssl && d.ssl.present) {
            if (d.ssl.expired) sslEl.innerHTML = '<span class="text-red-600">만료</span>';
            else if (d.ssl.days_left <= 14) sslEl.innerHTML = '<span class="text-amber-600">' + d.ssl.days_left + '일 남음</span>';
            else sslEl.innerHTML = '<span class="text-emerald-600">유효 ' + d.ssl.days_left + '일</span>';
        } else {
            sslEl.innerHTML = '<span class="text-zinc-400">없음</span>';
        }
        document.getElementById('status_disk').textContent = (d.disk && d.disk.human) ? d.disk.human : '-';
        var dbEl = document.getElementById('status_db');
        if (d.db && d.db.bytes !== null) {
            dbEl.innerHTML = d.db.human + ' <span class="text-[10px] text-zinc-400">(' + (d.db.table_count || 0) + ' tables)</span>';
        } else dbEl.textContent = '-';
        var ngEl = document.getElementById('status_nginx');
        if (d.nginx && d.nginx.enabled) ngEl.innerHTML = '<span class="text-emerald-600">활성</span>';
        else if (d.nginx && d.nginx.vhost_exists) ngEl.innerHTML = '<span class="text-amber-600">비활성 (vhost 존재)</span>';
        else ngEl.innerHTML = '<span class="text-red-600">vhost 없음</span>';

        // 토글 버튼 라벨 갱신
        var tBtn = document.getElementById('btnToggleVhost');
        if (tBtn) {
            tBtn.textContent = (d.nginx && d.nginx.enabled) ? '강제 차단' : '활성화';
            tBtn.dataset.next = (d.nginx && d.nginx.enabled) ? '0' : '1';
        }
    });
}

// === 관리 액션 ===
function toggleVhost() {
    var btn = document.getElementById('btnToggleVhost');
    var next = btn.dataset.next === '1';
    if (!confirm(next ? '호스팅을 활성화합니까?' : '호스팅을 강제 차단합니까? (사이트 접속 불가)')) return;
    ajaxPost({ action: 'toggle_vhost', order_id: orderId, enable: next }).then(function(d) {
        alert(d.success ? '완료' : (d.message || '실패'));
        loadHostingStatus();
    });
}

function renewSsl() {
    if (!confirm('SSL 인증서를 갱신합니까?')) return;
    ajaxPost({ action: 'renew_ssl', order_id: orderId }).then(function(d) {
        alert(d.success ? 'SSL 갱신 시도 완료\n' + (d.output || '') : 'SSL 갱신 실패\n' + (d.output || d.message || ''));
        loadHostingStatus();
    });
}

function resetDbPassword() {
    if (!confirm('DB 비밀번호를 재설정합니까? 새 비번이 화면에 표시됩니다.')) return;
    ajaxPost({ action: 'reset_db_password', order_id: orderId }).then(function(d) {
        if (d.success) {
            alert('새 DB 비번:\n' + d.new_password + '\n\n(상단 DB 정보에서 다시 확인 가능)');
            location.reload();
        } else {
            alert('실패: ' + (d.message || ''));
        }
    });
}

function reprovision() {
    if (!confirm('호스팅을 재프로비저닝합니까? 손상된 nginx/php-fpm/SSL 등을 재구축합니다. 약 1~2분 소요.')) return;
    ajaxPost({ action: 'reprovision', order_id: orderId }).then(function(d) {
        alert(d.message || (d.success ? '재프로비저닝 시작' : '실패'));
    });
}

// 페이지 로드 시 자동 호출
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadHostingStatus);
} else {
    loadHostingStatus();
}
</script>
