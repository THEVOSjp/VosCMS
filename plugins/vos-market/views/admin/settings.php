<?php
include __DIR__ . '/_head.php';
$pageHeaderTitle = '마켓플레이스 설정';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$csrf = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;

$rows = $db->query("SELECT `key`,`value` FROM {$pfx}mkt_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$get = fn(string $k, mixed $d='') => $rows[$k] ?? $d;
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">마켓플레이스 설정</h1>
        <p class="text-sm text-zinc-500 mt-0.5">일반 환경설정</p>
    </div>
</div>

<form id="settingsForm" class="max-w-2xl space-y-6">
<input type="hidden" id="f_token" value="<?= htmlspecialchars($csrf) ?>">

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-5">
    <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">일반</h3>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">마켓 이름</label>
        <input type="text" name="market_name" value="<?= htmlspecialchars($get('market_name','VosCMS 마켓플레이스')) ?>"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">기본 통화</label>
        <select name="default_currency" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
            <?php foreach (['JPY'=>'엔 (JPY)','KRW'=>'원 (KRW)','USD'=>'달러 (USD)'] as $cv=>$cl): ?>
            <option value="<?= $cv ?>" <?= $get('default_currency','JPY')===$cv?'selected':'' ?>><?= $cl ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">기본 수수료율 (%)</label>
        <input type="number" name="default_commission_rate" value="<?= htmlspecialchars($get('default_commission_rate','30')) ?>" min="0" max="100" step="1"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
        <p class="text-xs text-zinc-400 mt-1">신규 파트너 가입 시 적용되는 기본 수수료율</p>
    </div>
</div>

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-5">
    <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">파트너 포털</h3>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">파트너 포털 URL</label>
        <input type="text" name="partner_portal_url" value="<?= htmlspecialchars($get('partner_portal_url','https://partner.21ces.com')) ?>"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="partner_registration_open" id="cb_reg" value="1" <?= $get('partner_registration_open','1')==='1'?'checked':'' ?> class="rounded">
        <label for="cb_reg" class="text-sm text-zinc-700 dark:text-zinc-300">파트너 신규 등록 허용</label>
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="auto_approve_partners" id="cb_auto" value="1" <?= $get('auto_approve_partners','0')==='1'?'checked':'' ?> class="rounded">
        <label for="cb_auto" class="text-sm text-zinc-700 dark:text-zinc-300">파트너 자동 승인</label>
    </div>
</div>

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-5">
    <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">다운로드 및 파일</h3>
    <div>
        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">업로드 최대 용량 (MB)</label>
        <input type="number" name="max_upload_mb" value="<?= htmlspecialchars($get('max_upload_mb','50')) ?>" min="1"
            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="require_license_for_download" id="cb_lic" value="1" <?= $get('require_license_for_download','1')==='1'?'checked':'' ?> class="rounded">
        <label for="cb_lic" class="text-sm text-zinc-700 dark:text-zinc-300">유료 아이템 다운로드 시 라이선스 검증</label>
    </div>
</div>

<div class="flex gap-3">
    <button type="button" onclick="saveSettings()"
        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">저장</button>
</div>
</form>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl.'/market/settings/api') ?>;
async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const fd = new FormData(form);
    fd.append('_token', CSRF);
    const r = await fetch(API_URL, {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) { alert('저장되었습니다.'); location.reload(); } else alert(d.msg);
}
</script>
<?php include __DIR__ . '/_foot.php'; ?>
