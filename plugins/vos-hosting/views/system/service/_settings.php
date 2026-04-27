<?php
/**
 * 서비스 신청 페이지 — 관리자 설정 (탭 기반)
 *
 * 탭 정의: config/service-settings-tabs.php
 * 탭 파셜: resources/views/system/service/settings/{key}.php
 *
 * pages-settings.php에서 include되며, 다음 변수를 사용:
 *   $pdo, $prefix, $baseUrl, $adminUrl, $pageSlug, $serviceSettings, $config
 */
$_inp = 'w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500';
$_sel = 'px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500';
$_curSymbol = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$_dispCur = $serviceSettings['service_currency'] ?? 'KRW';
$_dispSym = $_curSymbol[$_dispCur] ?? $_dispCur;

// 탭 정의 로드
$_settingsTabs = include BASE_PATH . '/config/service-settings-tabs.php';
$_activeTab = $_GET['stab'] ?? $_settingsTabs[0]['key'] ?? 'general';

// 유효한 탭인지 확인
$_validKeys = array_column($_settingsTabs, 'key');
if (!in_array($_activeTab, $_validKeys)) $_activeTab = $_validKeys[0] ?? 'general';
?>

<!-- 탭 네비게이션 -->
<div class="border-b border-zinc-200 dark:border-zinc-700 mb-6">
    <nav class="flex gap-1 -mb-px overflow-x-auto">
        <?php foreach ($_settingsTabs as $_tab): ?>
        <button onclick="switchSettingsTab('<?= $_tab['key'] ?>')" id="stab_<?= $_tab['key'] ?>"
                class="svc-settings-tab flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition <?= $_tab['key'] === $_activeTab ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_tab['icon'] ?>"/></svg>
            <?= htmlspecialchars($_tab['label']) ?>
        </button>
        <?php endforeach; ?>
    </nav>
</div>

<!-- 탭 콘텐츠 -->
<?php foreach ($_settingsTabs as $_tab): ?>
<div id="stab_panel_<?= $_tab['key'] ?>" class="svc-settings-panel <?= $_tab['key'] !== $_activeTab ? 'hidden' : '' ?>">
    <?php
    $_tabFile = __DIR__ . '/settings/' . $_tab['key'] . '.php';
    if (file_exists($_tabFile)) {
        include $_tabFile;
    } else {
        echo '<div class="p-8 text-center text-zinc-400 text-sm">탭 파일이 없습니다: settings/' . htmlspecialchars($_tab['key']) . '.php</div>';
    }
    ?>
</div>
<?php endforeach; ?>

<!-- 저장 버튼 + JS (공통) -->
<?php include __DIR__ . '/settings/_footer.php'; ?>

<script>
function switchSettingsTab(key) {
    document.querySelectorAll('.svc-settings-tab').forEach(function(t) {
        t.classList.remove('border-blue-600', 'text-blue-600', 'dark:text-blue-400');
        t.classList.add('border-transparent', 'text-zinc-400');
    });
    document.querySelectorAll('.svc-settings-panel').forEach(function(p) { p.classList.add('hidden'); });
    var tab = document.getElementById('stab_' + key);
    var panel = document.getElementById('stab_panel_' + key);
    if (tab) { tab.classList.add('border-blue-600', 'text-blue-600', 'dark:text-blue-400'); tab.classList.remove('border-transparent', 'text-zinc-400'); }
    if (panel) panel.classList.remove('hidden');
}
</script>
