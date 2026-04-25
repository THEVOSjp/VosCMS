<?php
/**
 * VosCMS 설치 마법사
 * 웹 브라우저에서 실행하여 VosCMS를 설치합니다.
 *
 * 설치 완료 후 이 파일은 자동으로 비활성화됩니다.
 */

// 이미 설치된 경우 차단
if (file_exists(__DIR__ . '/.env') && file_exists(__DIR__ . '/storage/.installed')) {
    die('<h1>VosCMS is already installed.</h1><p>Delete <code>storage/.installed</code> to reinstall.</p>');
}

// localhost / 사설 IP 차단 — 실제 도메인에서만 설치 가능
$_installHost = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$_blockedHosts = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
$_blockedSuffixes = ['.local', '.test', '.example', '.invalid', '.localhost'];
$_isBlocked = in_array($_installHost, $_blockedHosts);
foreach ($_blockedSuffixes as $_sfx) {
    if (str_ends_with($_installHost, $_sfx)) $_isBlocked = true;
}
if (!$_isBlocked && filter_var($_installHost, FILTER_VALIDATE_IP) !== false) {
    // 사설 IP 대역 차단 (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
    if (filter_var($_installHost, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        $_isBlocked = true;
    }
}
if ($_isBlocked) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>VosCMS</title>
    <style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#f4f4f5}
    .box{background:#fff;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:480px;text-align:center}
    h1{color:#dc2626;font-size:1.5rem}p{color:#71717a;line-height:1.6}</style></head>
    <body><div class="box"><h1>Installation Blocked</h1>
    <p>VosCMS can only be installed on a public domain with SSL.<br>
    <code>localhost</code>, private IPs, and test domains are not allowed.</p>
    <p style="margin-top:20px;font-size:.85rem;color:#a1a1aa">Current host: <code>' . htmlspecialchars($_installHost) . '</code></p>
    </div></body></html>');
}

define('BASE_PATH', __DIR__);

// 세션 시작 (언어 선택 저장용)
if (session_status() === PHP_SESSION_NONE) session_start();

// 번역 시스템 로드
$_langData = file_exists(BASE_PATH . '/resources/lang/install.php')
    ? include(BASE_PATH . '/resources/lang/install.php')
    : ['languages' => ['en' => 'English'], 'translations' => ['en' => []]];
$_installLangs = $_langData['languages'];
$_it = $_langData['translations'];

// .env 없이 최초 접속 = 새 설치 → 이전 세션 전체 초기화
if (!isset($_POST['step']) && !isset($_GET['step']) && !file_exists(BASE_PATH . '/.env')) {
    session_destroy();
    session_start();
}

$installLocale = $_SESSION['install_locale'] ?? null;
$step = $_POST['step'] ?? $_GET['step'] ?? ($installLocale ? '1' : '0');
$errors = [];
$success = '';

// 번역 헬퍼
function __t(string $key): string {
    global $_it, $installLocale;
    return $_it[$installLocale][$key] ?? $_it['en'][$key] ?? $key;
}

// Step 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case '0': // 언어 선택
            $installLocale = $_POST['install_locale'] ?? 'en';
            if (!isset($_installLangs[$installLocale])) $installLocale = 'en';
            $_SESSION['install_locale'] = $installLocale;
            $step = '1';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($installLocale ?? 'en') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VosCMS Install</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }</style>
</head>
<body class="bg-zinc-100 min-h-screen flex items-center justify-center p-4">
<div class="w-full max-w-lg">

    <!-- 로고 -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-zinc-800">Vos<span class="text-blue-600">CMS</span></h1>
        <p class="text-zinc-500 mt-1">Value Of Style CMS</p>
    </div>

    <?php if ($step !== '0'): // Step 0에서는 프로그레스 바 숨김 ?>
    <!-- 프로그레스 -->
    <div class="flex items-center justify-center gap-2 mb-8">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <div class="flex items-center">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                <?= $i < $step ? 'bg-blue-600 text-white' : ($i == $step ? 'bg-blue-600 text-white ring-4 ring-blue-200' : 'bg-zinc-300 text-zinc-500') ?>">
                <?= $i < $step ? '&#10003;' : $i ?>
            </div>
            <?php if ($i < 5): ?><div class="w-8 h-0.5 <?= $i < $step ? 'bg-blue-600' : 'bg-zinc-300' ?>"></div><?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl shadow-lg p-8">

    <?php if ($errors): ?>
    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
        <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($step === '0'): // 언어 선택 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-2 text-center">Select Language</h2>
    <p class="text-sm text-zinc-500 mb-6 text-center">Choose your installation language</p>
    <form method="POST" id="install-lang-form">
        <input type="hidden" name="step" value="0">
        <div class="grid grid-cols-2 gap-3">
            <?php foreach ($_installLangs as $_lCode => $_lName): ?>
            <label class="flex items-center gap-3 p-3 border border-zinc-200 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                <input type="radio" name="install_locale" value="<?= $_lCode ?>" <?= $_lCode === 'ko' ? 'checked' : '' ?> class="text-blue-600 focus:ring-blue-500" data-lang="<?= $_lCode ?>">
                <span class="text-sm font-medium text-zinc-700"><?= $_lName ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <button type="button" id="btn-start-install" class="w-full mt-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition">
            Start Installation &rarr;
        </button>
    </form>

    <!-- 백업 안내 모달 (언어 선택 후 표시) -->
    <div id="backup-warning-modal" class="fixed inset-0 bg-black/60 backdrop-blur-sm items-center justify-center p-4 z-50 hidden" style="display:none;">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6 sm:p-8">
                <h3 id="bw-title" class="text-xl sm:text-2xl font-bold text-amber-700 mb-4"></h3>
                <div id="bw-body" class="text-sm text-zinc-700 leading-relaxed mb-4"></div>
                <ul class="space-y-2 mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <li class="flex items-start gap-2 text-sm"><span class="text-amber-600 font-bold">•</span><span id="bw-item-env"></span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-amber-600 font-bold">•</span><span id="bw-item-db"></span></li>
                    <li class="flex items-start gap-2 text-sm"><span class="text-amber-600 font-bold">•</span><span id="bw-item-uploads"></span></li>
                </ul>
                <div id="bw-warn" class="text-sm bg-red-50 border border-red-200 text-red-700 rounded-lg p-3 mb-5"></div>

                <label class="flex items-start gap-3 p-3 border border-zinc-300 rounded-lg cursor-pointer hover:bg-zinc-50 mb-4 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                    <input type="checkbox" id="bw-check" class="mt-0.5 w-4 h-4 text-blue-600 focus:ring-blue-500 rounded">
                    <span id="bw-check-label" class="text-sm text-zinc-700"></span>
                </label>

                <div class="flex gap-3">
                    <button type="button" id="bw-cancel" class="flex-1 py-2.5 border border-zinc-300 rounded-lg text-zinc-700 hover:bg-zinc-50 font-medium"></button>
                    <button type="button" id="bw-confirm" disabled class="flex-1 py-2.5 bg-blue-600 text-white rounded-lg font-bold disabled:bg-zinc-300 disabled:cursor-not-allowed hover:bg-blue-700 transition"></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function(){
        // 언어별 번역 (각 언어 선택에 맞춰 모달 내용 교체)
        const i18n = <?= json_encode(array_map(function($lang) use ($_it) {
            return [
                'title'        => $_it[$lang]['backup_title'] ?? $_it['en']['backup_title'] ?? '',
                'body'         => $_it[$lang]['backup_body'] ?? $_it['en']['backup_body'] ?? '',
                'item_env'     => $_it[$lang]['backup_item_env'] ?? $_it['en']['backup_item_env'] ?? '',
                'item_db'      => $_it[$lang]['backup_item_db'] ?? $_it['en']['backup_item_db'] ?? '',
                'item_uploads' => $_it[$lang]['backup_item_uploads'] ?? $_it['en']['backup_item_uploads'] ?? '',
                'warn'         => $_it[$lang]['backup_warn'] ?? $_it['en']['backup_warn'] ?? '',
                'check'        => $_it[$lang]['backup_check'] ?? $_it['en']['backup_check'] ?? '',
                'confirm'      => $_it[$lang]['backup_confirm'] ?? $_it['en']['backup_confirm'] ?? '',
                'cancel'       => $_it[$lang]['backup_cancel'] ?? $_it['en']['backup_cancel'] ?? '',
            ];
        }, array_combine(array_keys($_installLangs), array_keys($_installLangs))), JSON_UNESCAPED_UNICODE) ?>;

        const form     = document.getElementById('install-lang-form');
        const modal    = document.getElementById('backup-warning-modal');
        const check    = document.getElementById('bw-check');
        const btnOK    = document.getElementById('bw-confirm');
        const btnNo    = document.getElementById('bw-cancel');
        const btnOpen  = document.getElementById('btn-start-install');

        function applyLang(code) {
            const t = i18n[code] || i18n['en'] || {};
            document.getElementById('bw-title').innerHTML        = t.title || '';
            document.getElementById('bw-body').innerHTML         = t.body || '';
            document.getElementById('bw-item-env').innerHTML     = t.item_env || '';
            document.getElementById('bw-item-db').innerHTML      = t.item_db || '';
            document.getElementById('bw-item-uploads').innerHTML = t.item_uploads || '';
            document.getElementById('bw-warn').innerHTML         = t.warn || '';
            document.getElementById('bw-check-label').innerHTML  = t.check || '';
            btnOK.textContent = t.confirm || 'Confirm';
            btnNo.textContent = t.cancel || 'Cancel';
        }

        // 선택된 언어로 모달 내용 준비
        function getSelectedLang() {
            const r = document.querySelector('input[name="install_locale"]:checked');
            return r ? r.value : 'ko';
        }

        btnOpen.addEventListener('click', function() {
            applyLang(getSelectedLang());
            modal.style.display = 'flex';
            check.checked = false;
            btnOK.disabled = true;
        });

        // 언어 변경 시 모달 열려있으면 즉시 반영
        document.querySelectorAll('input[name="install_locale"]').forEach(r => {
            r.addEventListener('change', () => {
                if (modal.style.display === 'flex') applyLang(r.value);
            });
        });

        check.addEventListener('change', () => { btnOK.disabled = !check.checked; });

        btnNo.addEventListener('click', () => { modal.style.display = 'none'; });

        btnOK.addEventListener('click', () => {
            if (!check.checked) return;
            form.submit();
        });

        // 배경 클릭 시 닫기
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });

        // ESC 키 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') modal.style.display = 'none';
        });
    })();
    </script>

    <?php elseif ($step === '1' || $step === 1): // 환경 체크 ?>
    <h2 class="text-xl font-bold text-zinc-800 mb-6">1. <?= __t('step1') ?></h2>
    <?php
    // ionCube Loader 감지 (확장 이름 변형 대응: 'ionCube Loader' / 'ioncube_loader' / 'ionCube')
    $_ic_loaded = extension_loaded('ionCube Loader')
               || extension_loaded('ionCube')
               || extension_loaded('ioncube_loader')
               || function_exists('ioncube_loader_version');
    $_ic_version = '';
    if ($_ic_loaded && function_exists('ioncube_loader_version')) {
        $_ic_version = @ioncube_loader_version();
    }
    $_ic_label = 'ionCube Loader' . ($_ic_version ? ' v' . $_ic_version : '');

    $checks = [
        ['PHP Version >= 8.1', version_compare(PHP_VERSION, '8.1.0', '>=')],
        [$_ic_label, $_ic_loaded],
        ['PDO MySQL', extension_loaded('pdo_mysql')],
        ['mbstring', extension_loaded('mbstring')],
        ['json', extension_loaded('json')],
        ['fileinfo', extension_loaded('fileinfo')],
        ['openssl', extension_loaded('openssl')],
        ['curl', extension_loaded('curl')],
        [__t('storage_perm'), is_writable(BASE_PATH . '/storage')],
        [__t('env_perm'), is_writable(BASE_PATH)],
    ];
    $allPassed = true;
    foreach ($checks as [$label, $ok]):
        if (!$ok) $allPassed = false;
    ?>
    <div class="flex items-center justify-between py-2 border-b border-zinc-100">
        <span class="text-sm text-zinc-700"><?= $label ?></span>
        <?php if ($ok): ?>
        <span class="text-green-600 text-sm font-bold">&#10003; OK</span>
        <?php else: ?>
        <span class="text-red-600 text-sm font-bold">&#10007; FAIL</span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="mt-4 text-xs text-zinc-400">PHP <?= PHP_VERSION ?></div>

    <?php if (!$_ic_loaded): ?>
    <!-- ionCube Loader 설치 안내 -->
    <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
        <div class="flex items-start gap-2">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            <div class="text-sm text-amber-800">
                <p class="font-semibold mb-1"><?= __t('ic_required_title') ?? 'ionCube Loader가 필요합니다' ?></p>
                <p class="mb-2"><?= __t('ic_required_desc') ?? 'VosCMS 코어 보안 모듈이 ionCube로 인코딩되어 있어, ionCube Loader 확장 모듈이 PHP에 설치되어 있어야 동작합니다.' ?></p>
                <ol class="list-decimal pl-5 space-y-1 text-xs">
                    <li><a href="https://www.ioncube.com/loaders.php" target="_blank" rel="noopener" class="text-blue-600 underline">ionCube Loader 다운로드</a> (PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?> · <?= PHP_OS_FAMILY ?> · <?= PHP_INT_SIZE === 8 ? 'x86_64' : 'x86' ?>)</li>
                    <li><code class="bg-amber-100 px-1 rounded">php.ini</code> 에 <code class="bg-amber-100 px-1 rounded">zend_extension = /path/to/ioncube_loader_lin_<?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?>.so</code> 추가</li>
                    <li>웹 서버 (php-fpm / apache) 재시작</li>
                    <li>이 페이지 새로고침 → 설치 마법사 자동 통과</li>
                </ol>
                <p class="mt-2 text-xs text-amber-700">ⓘ 호스팅 환경이라면 호스팅사에 ionCube Loader 활성화를 요청하세요. 대부분 공유 호스팅에서 무료로 제공됩니다.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($allPassed): ?>
    <form method="GET" action="install-core.php" class="mt-6">
        <input type="hidden" name="step" value="2">
        <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition"><?= __t('next') ?> &rarr;</button>
    </form>
    <?php else: ?>
    <p class="mt-6 text-red-600 text-sm font-bold"><?= __t('env_fail') ?></p>
    <?php endif; ?>

    <?php endif; ?>

    </div>

    <p class="text-center text-xs text-zinc-400 mt-6">VosCMS &mdash; Value Of Style CMS</p>
    <p class="text-center text-xs text-zinc-300 mt-1">Powered by <a href="https://thevos.com" target="_blank" class="hover:text-zinc-500 transition">THEVOS</a></p>
</div>
</body>
</html>
