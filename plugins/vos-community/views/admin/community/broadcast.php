<?php
/**
 * 어드민 - 공지 발송 페이지
 * /{ADMIN_PATH}/community/broadcast
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 4));
require_once BASE_PATH . '/plugins/vos-community/_init.php';
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;
if (!Auth::check() || !in_array(Auth::user()['role'] ?? '', ['admin','supervisor','owner'], true)) {
    http_response_code(403); echo '권한 없음'; return;
}
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$basePath = parse_url($baseUrl, PHP_URL_PATH) ?: '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pageTitle = __('community.broadcast.title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';

// 대상별 카운트 미리보기
$counts = [];
try {
    $counts['all']         = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE is_active = 1")->fetchColumn();
    $counts['hosting']     = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM {$prefix}subscriptions WHERE type='hosting' AND status='active' AND user_id IS NOT NULL")->fetchColumn();
    $counts['role_admin']  = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE is_active=1 AND role IN ('admin','supervisor','owner')")->fetchColumn();
    $counts['role_member'] = $counts['all'] - $counts['role_admin'];
} catch (\Throwable $e) { /* silent */ }
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($config['locale'] ?? 'ko') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php include BASE_PATH . '/resources/views/admin/partials/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
<div class="flex">
    <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
    <main class="flex-1 ml-64">
        <?php
        $pageHeaderTitle = __('community.broadcast.title');
        include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
        ?>
<div class="p-6 max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('community.broadcast.title')) ?></h1>
        <p class="text-sm text-zinc-500 mt-1"><?= htmlspecialchars(__('community.broadcast.description')) ?></p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-6">
        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('community.broadcast.audience_label')) ?></label>
        <div class="space-y-2 mb-4">
            <?php
            $audienceList = [
                'all'         => __('community.broadcast.audience_all'),
                'hosting'     => __('community.broadcast.audience_hosting'),
                'role_member' => __('community.broadcast.audience_member'),
                'role_admin'  => __('community.broadcast.audience_admin'),
            ];
            foreach ($audienceList as $key => $label):
                $checked = $key === 'all' ? 'checked' : '';
            ?>
            <label class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                <div class="flex items-center gap-2">
                    <input type="radio" name="audience" value="<?= $key ?>" <?= $checked ?>>
                    <span class="text-sm font-medium"><?= htmlspecialchars($label) ?></span>
                </div>
                <span class="text-xs text-zinc-500"><?= htmlspecialchars(__('community.broadcast.count_unit', ['count' => number_format($counts[$key] ?? 0)])) ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('community.broadcast.subject_label')) ?> <span class="text-red-500">*</span></label>
        <input type="text" id="bcTitle" maxlength="255" placeholder="<?= htmlspecialchars(__('community.broadcast.subject_placeholder')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white mb-4">

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('community.broadcast.body_label')) ?> <span class="text-red-500">*</span></label>
        <textarea id="bcBody" rows="6" maxlength="2000" placeholder="<?= htmlspecialchars(__('community.broadcast.body_placeholder')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-4"></textarea>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('community.broadcast.link_label')) ?></label>
        <input type="text" id="bcLink" placeholder="<?= htmlspecialchars(__('community.broadcast.link_placeholder')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white mb-4">

        <label class="flex items-center gap-2 mb-6 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg cursor-pointer">
            <input type="checkbox" id="bcSendPush" checked>
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(__('community.broadcast.send_push_label')) ?></p>
                <p class="text-[11px] text-zinc-500"><?= htmlspecialchars(__('community.broadcast.send_push_desc')) ?></p>
            </div>
        </label>

        <div class="flex gap-2 justify-end">
            <button type="button" onclick="window.history.back()" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700"><?= htmlspecialchars(__('community.broadcast.btn_cancel')) ?></button>
            <button type="button" id="btnSend" onclick="sendBroadcast()" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= htmlspecialchars(__('community.broadcast.btn_send')) ?></button>
        </div>
    </div>

    <div id="bcResult" class="hidden mt-4 p-4 rounded-lg"></div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;
    var I18N = <?= json_encode([
        'audience_all'    => __('community.broadcast.audience_all'),
        'audience_hosting'=> __('community.broadcast.audience_hosting'),
        'audience_member' => __('community.broadcast.audience_member'),
        'audience_admin'  => __('community.broadcast.audience_admin'),
        'btn_send'        => __('community.broadcast.btn_send'),
        'btn_sending'     => __('community.broadcast.btn_sending'),
        'required_required' => __('community.broadcast.required_required'),
        'confirm_send'    => __('community.broadcast.confirm_send'),
        'send_failed'     => __('community.broadcast.send_failed'),
        'send_success'    => __('community.broadcast.send_success'),
        'send_success_push' => __('community.broadcast.send_success_push'),
        'network_error'   => __('community.broadcast.network_error'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    window.sendBroadcast = function() {
        var title = document.getElementById('bcTitle').value.trim();
        var body  = document.getElementById('bcBody').value.trim();
        var link  = document.getElementById('bcLink').value.trim();
        var audience = document.querySelector('input[name="audience"]:checked').value;
        var sendPush = document.getElementById('bcSendPush').checked;

        if (!title || !body) { alert(I18N.required_required); return; }

        var audienceLabel = ({all: I18N.audience_all, hosting: I18N.audience_hosting, role_member: I18N.audience_member, role_admin: I18N.audience_admin})[audience];
        var confirmMsg = I18N.confirm_send.replace(':audience', audienceLabel).replace(':title', title);
        if (!confirm(confirmMsg)) return;

        var btn = document.getElementById('btnSend');
        btn.disabled = true; btn.textContent = I18N.btn_sending;

        var fd = new FormData();
        fd.append('title', title);
        fd.append('body', body);
        if (link) fd.append('link', link);
        fd.append('audience', audience);
        if (sendPush) fd.append('send_push', '1');

        fetch(BASE + '/api/community-broadcast.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false; btn.textContent = I18N.btn_send;
                var resultEl = document.getElementById('bcResult');
                resultEl.classList.remove('hidden');
                if (!d.success) {
                    resultEl.className = 'mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm';
                    resultEl.textContent = I18N.send_failed + ': ' + (d.message || '');
                    return;
                }
                var template = d.pushed > 0 ? I18N.send_success_push : I18N.send_success;
                var msg = template
                    .replace(':targets', d.targets || d.inserted)
                    .replace(':inserted', d.inserted)
                    .replace(':pushed', d.pushed || 0);
                resultEl.className = 'mt-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm';
                resultEl.textContent = msg;
                document.getElementById('bcTitle').value = '';
                document.getElementById('bcBody').value = '';
                document.getElementById('bcLink').value = '';
            }).catch(function(e){
                btn.disabled = false; btn.textContent = I18N.btn_send;
                alert(I18N.network_error + ': ' + e.message);
            });
    };
})();
</script>
    </main>
</div>
<?php include BASE_PATH . '/resources/views/admin/partials/pwa-scripts.php'; ?>
</body>
</html>
