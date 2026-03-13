<?php
/**
 * RezlyX 공용 언어 선택기 컴포넌트
 * 관리자/프론트엔드 어디서든 include만 하면 동작
 *
 * 필요 변수: $siteSettings (DB rzx_settings 배열)
 * 선택 변수: $config (locale 정보)
 *
 * 사용법:
 *   include BASE_PATH . '/resources/views/components/language-selector.php';
 */

// LanguageModule 로드
require_once BASE_PATH . '/rzxlib/Core/Modules/LanguageModule.php';

// $siteSettings가 없거나 supported_languages가 없으면 DB에서 직접 로드
if (empty($siteSettings) || empty($siteSettings['supported_languages'])) {
    try {
        // .env에서 DB 정보 로드 (아직 로드 안됐을 수 있음)
        $_lsEnv = $_ENV;
        if (empty($_lsEnv['DB_DATABASE']) && file_exists(BASE_PATH . '/.env')) {
            $_lsEnvLines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($_lsEnvLines as $_lsLine) {
                if (str_starts_with(trim($_lsLine), '#')) continue;
                if (str_contains($_lsLine, '=')) {
                    [$_lsKey, $_lsVal] = explode('=', $_lsLine, 2);
                    $_lsEnv[trim($_lsKey)] = trim($_lsVal, " \t\n\r\"'");
                }
            }
        }
        $_lsPdo = new PDO(
            'mysql:host=' . ($_lsEnv['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_lsEnv['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
            $_lsEnv['DB_USERNAME'] ?? 'root',
            $_lsEnv['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $_lsStmt = $_lsPdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` IN ('supported_languages','custom_languages','default_language')");
        if (!isset($siteSettings) || !is_array($siteSettings)) {
            $siteSettings = [];
        }
        while ($_lsRow = $_lsStmt->fetch(PDO::FETCH_ASSOC)) {
            $siteSettings[$_lsRow['key']] = $_lsRow['value'];
        }
        unset($_lsPdo, $_lsStmt, $_lsEnv, $_lsEnvLines, $_lsLine, $_lsKey, $_lsVal, $_lsRow);
    } catch (Exception $e) {
        // DB 연결 실패 시 기본값 사용
    }
}

// 현재 로케일 결정
$_lsLocale = '';
if (!empty($_GET['lang'])) {
    $_lsLocale = $_GET['lang'];
} elseif (!empty($_SESSION['locale'])) {
    $_lsLocale = $_SESSION['locale'];
} elseif (!empty($_COOKIE['locale'])) {
    $_lsLocale = $_COOKIE['locale'];
} elseif (function_exists('current_locale')) {
    $_lsLocale = current_locale();
} else {
    $_lsLocale = ($siteSettings['default_language'] ?? ($config['locale'] ?? 'ko'));
}

// LanguageModule에서 데이터 가져오기 (admin-topbar.php와 동일한 구조)
$_lsData = \RzxLib\Core\Modules\LanguageModule::getData($siteSettings ?? [], $_lsLocale);
$_lsSupportedCodes = $_lsData['supportedCodes'];
$_lsAllLanguages   = $_lsData['allLanguages'];
$_lsCurrentLocale  = $_lsData['currentLocale'];
$_lsCurrentInfo    = $_lsData['currentLangInfo'];
?>
<div class="relative" id="rzxLangContainer">
    <button type="button" id="rzxLangBtn"
            class="flex items-center px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
        </svg>
        <span id="rzxCurrentLang"><?= htmlspecialchars($_lsCurrentInfo['native'] ?? strtoupper($_lsCurrentLocale)) ?></span>
        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>
    <div id="rzxLangDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50 max-h-64 overflow-y-auto">
        <?php foreach ($_lsSupportedCodes as $_lsCode): ?>
        <?php if (isset($_lsAllLanguages[$_lsCode])): ?>
        <a href="javascript:void(0)" onclick="rzxChangeLanguage('<?= htmlspecialchars($_lsCode) ?>')"
           class="flex items-center px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 <?= $_lsCurrentLocale === $_lsCode ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : '' ?>">
            <?php if ($_lsCurrentLocale === $_lsCode): ?>
            <svg class="w-4 h-4 mr-2 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <?php else: ?>
            <span class="w-4 h-4 mr-2"></span>
            <?php endif; ?>
            <?= htmlspecialchars($_lsAllLanguages[$_lsCode]['native']) ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('rzxLangBtn');
    var dropdown = document.getElementById('rzxLangDropdown');
    var container = document.getElementById('rzxLangContainer');

    if (btn && dropdown) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.toggle('hidden');
            console.log('[LangSelector] Dropdown toggled');
        });
    }

    document.addEventListener('click', function(e) {
        if (container && dropdown && !container.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    window.rzxChangeLanguage = function(lang) {
        console.log('[LangSelector] Changing language to:', lang);
        document.cookie = 'locale=' + lang + ';path=/;max-age=31536000';
        var url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    };
})();
</script>
