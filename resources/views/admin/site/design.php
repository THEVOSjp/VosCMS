<?php
/**
 * RezlyX Admin - 레이아웃 관리 페이지
 * 좌측: 사이트 미리보기 + 메뉴 목록
 * 우측: 메뉴 클릭 시 스킨/레이아웃 목록 표시
 */
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$defaultLocale = $config['locale'] ?? 'ko';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // AJAX 저장
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        if (($input['action'] ?? '') === 'save_layout') {
            $fields = ['site_layout', 'site_page_skin', 'site_board_skin', 'site_member_skin'];
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            foreach ($fields as $f) {
                if (isset($input[$f])) $stmt->execute([$f, $input[$f]]);
            }
            echo json_encode(['success' => true]);
            exit;
        }
    }

    // 현재 설정 로드
    $settings = [];
    $rows = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('site_layout','site_page_skin','site_board_skin','site_member_skin')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $settings[$r['key']] = $r['value'];

    $currentLayout = $settings['site_layout'] ?? 'default';
    $currentPageSkin = $settings['site_page_skin'] ?? 'default';
    $currentBoardSkin = $settings['site_board_skin'] ?? 'default';
    $currentMemberSkin = $settings['site_member_skin'] ?? 'default';

    // 스킨 스캔 헬퍼
    function scanSkins($dir, $type, $baseUrl, $locale) {
        $skins = [];
        if (!is_dir($dir)) return $skins;
        $jsonFile = $type === 'layouts' ? 'layout.json' : 'skin.json';
        foreach (scandir($dir) as $d) {
            if ($d === '.' || $d === '..' || !is_dir($dir . $d)) continue;
            $jf = $dir . $d . '/' . $jsonFile;
            $j = file_exists($jf) ? json_decode(file_get_contents($jf), true) : null;
            $skins[$d] = [
                'title' => $j ? ($j['title'][$locale] ?? $j['title']['en'] ?? $d) : ucfirst($d),
                'desc' => $j ? ($j['description'][$locale] ?? $j['description']['en'] ?? '') : '',
                'version' => $j['version'] ?? '',
                'thumbnail' => file_exists($dir . $d . '/thumbnail.png') ? $baseUrl . '/skins/' . $type . '/' . $d . '/thumbnail.png' : '',
            ];
        }
        return $skins;
    }

    $layouts = scanSkins(BASE_PATH . '/skins/layouts/', 'layouts', $baseUrl, $defaultLocale);
    $pageSkins = scanSkins(BASE_PATH . '/skins/page/', 'page', $baseUrl, $defaultLocale);
    $boardSkins = scanSkins(BASE_PATH . '/skins/board/', 'board', $baseUrl, $defaultLocale);
    $memberSkins = scanSkins(BASE_PATH . '/skins/member/', 'member', $baseUrl, $defaultLocale);

} catch (PDOException $e) {
    die('DB 연결 실패');
}

$pageTitle = __('site.design.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$pageHeaderTitle = __('site.design.title');
$pageSubTitle = __('site.design.title');
$pageSubDesc = __('site.design.description');

$menuItems = [
    'layout' => ['label' => __('site.design.layout_title') ?? '레이아웃', 'current' => $currentLayout, 'skins' => $layouts, 'dbKey' => 'site_layout'],
    'page' => ['label' => __('site.design.page_skin') ?? '페이지', 'current' => $currentPageSkin, 'skins' => $pageSkins, 'dbKey' => 'site_page_skin'],
    'board' => ['label' => __('site.design.board_skin') ?? '게시판', 'current' => $currentBoardSkin, 'skins' => $boardSkins, 'dbKey' => 'site_board_skin'],
    'member' => ['label' => __('site.design.member_skin') ?? '회원', 'current' => $currentMemberSkin, 'skins' => $memberSkins, 'dbKey' => 'site_member_skin'],
];
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>

    <div class="flex gap-6">
        <!-- 좌측: 미리보기 + 메뉴 -->
        <div class="w-80 shrink-0">
            <!-- 사이트 미리보기 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-4">
                <div class="h-52 bg-zinc-100 dark:bg-zinc-700 relative overflow-hidden">
                    <iframe src="<?= $baseUrl ?>/" class="w-full h-full border-0 pointer-events-none" style="transform:scale(0.33);transform-origin:top left;width:303%;height:303%" loading="lazy"></iframe>
                </div>
            </div>

            <!-- 메뉴 목록 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <?php foreach ($menuItems as $key => $item): ?>
                <div onclick="switchMenu('<?= $key ?>')" id="menu-<?= $key ?>"
                     class="flex items-center justify-between px-4 py-3.5 cursor-pointer transition border-b border-zinc-100 dark:border-zinc-700 last:border-b-0 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white"><?= $item['label'] ?></span>
                        <span class="text-xs text-zinc-400 ml-1" id="current-<?= $key ?>">[<?= htmlspecialchars($item['skins'][$item['current']]['title'] ?? $item['current']) ?>]</span>
                    </div>
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 저장 버튼 -->
            <div class="mt-4">
                <button onclick="saveLayout()" class="w-full px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    <?= __('site.design.save_layout') ?? '레이아웃 저장' ?>
                </button>
            </div>
        </div>

        <!-- 우측: 선택한 메뉴의 스킨 목록 (초기 숨김) -->
        <div class="flex-1 min-w-0" id="rightPanel" style="display:none">
            <?php foreach ($menuItems as $key => $item): ?>
            <div id="panel-<?= $key ?>" class="hidden">
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <!-- 패널 헤더 -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= $item['label'] ?></h3>
                        <button onclick="closePanel()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <!-- 스킨 목록 -->
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700 max-h-[75vh] overflow-y-auto">
                        <!-- 사용 안 함 -->
                        <?php $isNone = $item['current'] === 'none'; ?>
                        <div onclick="selectSkin('<?= $key ?>', 'none', '<?= __('site.design.not_use') ?? '사용 안 함' ?>')" id="skin-<?= $key ?>-none"
                             class="px-6 py-3 cursor-pointer transition <?= $isNone ? 'bg-yellow-50 dark:bg-yellow-900/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30' ?>">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="skin_<?= $key ?>" value="none" <?= $isNone ? 'checked' : '' ?> class="text-blue-600" readonly>
                                <span class="text-sm text-zinc-600 dark:text-zinc-300"><?= __('site.design.not_use') ?? ($item['label'] . ' 사용 안 함') ?></span>
                            </div>
                        </div>
                        <!-- 다른 스킨 설치 -->
                        <div class="px-6 py-3 text-sm text-zinc-400 dark:text-zinc-500 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span><?= __('site.design.install_other') ?? '다른 ' . $item['label'] . ' 설치' ?></span>
                            <span class="text-xs text-zinc-300 dark:text-zinc-600 ml-auto"><?= __('site.design.coming_soon') ?? '준비 중' ?></span>
                        </div>
                        <!-- 설치된 스킨 목록 -->
                        <?php foreach ($item['skins'] as $slug => $skin):
                            $isSelected = $slug === $item['current'];
                        ?>
                        <div onclick="selectSkin('<?= $key ?>', '<?= $slug ?>', '<?= htmlspecialchars($skin['title'], ENT_QUOTES) ?>')" id="skin-<?= $key ?>-<?= $slug ?>"
                             class="px-6 py-4 cursor-pointer transition <?= $isSelected ? 'bg-yellow-50 dark:bg-yellow-900/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30' ?>">
                            <!-- 라디오 + 타이틀 -->
                            <div class="flex items-center gap-2 mb-3">
                                <input type="radio" name="skin_<?= $key ?>" value="<?= $slug ?>" <?= $isSelected ? 'checked' : '' ?> class="text-blue-600" readonly>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($skin['title']) ?></span>
                            </div>
                            <!-- 썸네일 + 액션 링크 -->
                            <div class="flex gap-4 ml-6">
                                <div class="w-44 h-28 shrink-0 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                    <?php if (!empty($skin['thumbnail'])): ?>
                                    <img src="<?= htmlspecialchars($skin['thumbnail']) ?>" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                    <?php else: ?>
                                    <svg class="w-8 h-8 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col gap-1.5 text-sm pt-0.5">
                                    <a href="#" onclick="event.stopPropagation();openSkinSettings('<?= $key ?>','<?= $slug ?>')" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline flex items-center gap-1">
                                        <?= __('site.design.detail_settings') ?? '상세 설정' ?>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                    </a>
                                    <a href="#" onclick="event.stopPropagation()" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline">
                                        <?= __('site.design.duplicate') ?? '복사본 생성' ?>
                                    </a>
                                    <?php if ($slug !== 'default'): ?>
                                    <a href="#" onclick="event.stopPropagation()" class="text-red-500 hover:text-red-600 dark:text-red-400 hover:underline">
                                        <?= __('site.design.delete') ?? '삭제' ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 설정 패널 (상세 설정 클릭 시 표시) -->
        <div class="w-96 shrink-0" id="settingsPanel" style="display:none">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200" id="settingsPanelTitle"></h3>
                    <button onclick="closeSettings()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="settingsPanelContent" class="p-6">
                    <p class="text-sm text-zinc-400 text-center py-8"><?= __('site.design.coming_soon') ?? '준비 중' ?></p>
                </div>
            </div>
        </div>
    </div>

<script>
var selectedSkins = {
    layout: '<?= $currentLayout ?>',
    page: '<?= $currentPageSkin ?>',
    board: '<?= $currentBoardSkin ?>',
    member: '<?= $currentMemberSkin ?>'
};
var activeMenu = null;

function switchMenu(key) {
    console.log('[Layout] switchMenu:', key);

    // 같은 메뉴 다시 클릭 → 닫기
    if (activeMenu === key) { closePanel(); return; }
    activeMenu = key;

    // 메뉴 활성화
    document.querySelectorAll('[id^="menu-"]').forEach(function(el) {
        el.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
    });
    document.getElementById('menu-' + key)?.classList.add('bg-blue-50', 'dark:bg-blue-900/20');

    // 패널 표시
    document.getElementById('rightPanel').style.display = '';
    document.querySelectorAll('[id^="panel-"]').forEach(function(el) { el.classList.add('hidden'); });
    document.getElementById('panel-' + key)?.classList.remove('hidden');
}

function closePanel() {
    document.getElementById('rightPanel').style.display = 'none';
    closeSettings();
    document.querySelectorAll('[id^="menu-"]').forEach(function(el) {
        el.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
    });
    activeMenu = null;
}

function openSkinSettings(group, slug) {
    console.log('[Layout] openSkinSettings:', group, slug);
    var panel = document.getElementById('settingsPanel');
    var title = document.getElementById('settingsPanelTitle');
    var content = document.getElementById('settingsPanelContent');

    // 설정 URL 매핑
    var settingsUrls = {
        layout: '<?= $adminUrl ?>/site/design',
        page: '<?= $adminUrl ?>/site/pages/settings?slug=home&tab=skin',
        board: '<?= $adminUrl ?>/site/boards',
        member: '<?= $adminUrl ?>/site/design'
    };

    title.textContent = slug + ' <?= __('site.design.detail_settings') ?? '상세 설정' ?>';
    content.innerHTML = '<iframe src="' + settingsUrls[group] + '&skin_detail=' + slug + '" class="w-full border-0 min-h-[400px]" style="height:60vh"></iframe>';
    content.innerHTML = '<p class="text-sm text-zinc-500 text-center py-8"><?= __('site.design.coming_soon') ?? '준비 중' ?><br><br><a href="' + settingsUrls[group] + '" class="text-blue-600 hover:underline"><?= __('site.design.open_settings') ?? '설정 페이지로 이동' ?> →</a></p>';
    panel.style.display = '';
}

function closeSettings() {
    document.getElementById('settingsPanel').style.display = 'none';
}

function selectSkin(group, slug, title) {
    console.log('[Layout] selectSkin:', group, slug);
    selectedSkins[group] = slug;

    // 라디오 업데이트
    document.querySelectorAll('[name="skin_' + group + '"]').forEach(function(r) { r.checked = r.value === slug; });

    // 배경 업데이트
    document.querySelectorAll('#panel-' + group + ' [id^="skin-' + group + '-"]').forEach(function(el) {
        el.classList.remove('bg-yellow-50', 'dark:bg-yellow-900/10');
    });
    document.getElementById('skin-' + group + '-' + slug)?.classList.add('bg-yellow-50', 'dark:bg-yellow-900/10');

    // 좌측 메뉴 현재값 업데이트
    var cur = document.getElementById('current-' + group);
    if (cur) cur.textContent = '[' + title + ']';
}

async function saveLayout() {
    console.log('[Layout] Saving:', selectedSkins);
    try {
        var resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'save_layout',
                site_layout: selectedSkins.layout,
                site_page_skin: selectedSkins.page,
                site_board_skin: selectedSkins.board,
                site_member_skin: selectedSkins.member
            })
        });
        var data = await resp.json();
        showResultModal(data.success, data.success ? '' : (data.message || 'Error'));
    } catch (e) {
        showResultModal(false, e.message);
    }
}
</script>
<?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
    </div>
    </main>
</div>
</body>
</html>
