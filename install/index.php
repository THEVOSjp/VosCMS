<?php
/**
 * RezlyX Installation Wizard
 *
 * @package RezlyX\Install
 */

define('BASE_PATH', dirname(__DIR__));
define('INSTALL_PATH', __DIR__);

// Check if already installed
if (file_exists(BASE_PATH . '/install/installed.lock')) {
    header('Location: /');
    exit;
}

// Session for installation data
session_start();

// ── Language detection ──
$supportedLangs = ['ko','en','ja','zh_CN','zh_TW','de','es','fr','id','mn','ru','tr','vi'];

// Priority: GET param > cookie > browser Accept-Language > default (en)
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs)) {
    $locale = $_GET['lang'];
    setcookie('install_lang', $locale, time() + 86400 * 30, '/');
} elseif (isset($_COOKIE['install_lang']) && in_array($_COOKIE['install_lang'], $supportedLangs)) {
    $locale = $_COOKIE['install_lang'];
} else {
    $locale = 'en'; // default
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    foreach ($supportedLangs as $lang) {
        $short = substr($lang, 0, 2);
        if (stripos($accept, $short) !== false) {
            $locale = $lang;
            break;
        }
    }
    setcookie('install_lang', $locale, time() + 86400 * 30, '/');
}

// Load translation
$langFile = INSTALL_PATH . '/lang/' . $locale . '.php';
$translations = file_exists($langFile) ? require $langFile : require INSTALL_PATH . '/lang/en.php';

/**
 * Translation helper
 */
function t(string $key, string $default = ''): string
{
    global $translations;
    return $translations[$key] ?? ($default ?: $key);
}

// Current step
$step = $_GET['step'] ?? 'welcome';
$validSteps = ['welcome', 'requirements', 'database', 'admin', 'complete'];

if (!in_array($step, $validSteps)) {
    $step = 'welcome';
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once INSTALL_PATH . '/steps/process.php';
    exit;
}

// Step labels (translated)
$stepLabels = [
    'welcome'      => t('step_welcome'),
    'requirements' => t('step_requirements'),
    'database'     => t('step_database'),
    'admin'        => t('step_admin'),
    'complete'     => t('step_complete'),
];

// Language display names for selector
$langNames = [
    'ko' => '한국어', 'en' => 'English', 'ja' => '日本語',
    'zh_CN' => '简体中文', 'zh_TW' => '繁體中文',
    'de' => 'Deutsch', 'es' => 'Español', 'fr' => 'Français',
    'id' => 'Bahasa Indonesia', 'mn' => 'Монгол',
    'ru' => 'Русский', 'tr' => 'Türkçe', 'vi' => 'Tiếng Việt',
];

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(substr($locale, 0, 2)) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('install_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css');
        body { font-family: 'Pretendard', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-blue-600">RezlyX</h1>
                    <p class="text-gray-500 text-sm"><?= t('install_wizard') ?></p>
                </div>
                <!-- Language Selector -->
                <div class="relative">
                    <select id="langSelector" onchange="changeLanguage(this.value)"
                            class="appearance-none bg-white border border-gray-300 rounded-lg pl-3 pr-8 py-2 text-sm text-gray-700 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                        <?php foreach ($langNames as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $code === $locale ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Progress Steps -->
        <div class="bg-white border-b">
            <div class="max-w-4xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <?php
                    $currentIndex = array_search($step, $validSteps);
                    foreach ($stepLabels as $key => $label):
                        $index = array_search($key, $validSteps);
                        $isActive = $key === $step;
                        $isComplete = $index < $currentIndex;
                    ?>
                    <div class="flex items-center <?= $index < count($stepLabels) - 1 ? 'flex-1' : '' ?>">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium <?php
                            echo $isActive ? 'bg-blue-600 text-white' :
                                ($isComplete ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-600');
                        ?>">
                            <?php if ($isComplete): ?>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            <?php else: ?>
                                <?= $index + 1 ?>
                            <?php endif; ?>
                        </div>
                        <span class="ml-2 text-sm <?= $isActive ? 'font-semibold text-gray-900' : 'text-gray-500' ?>">
                            <?= htmlspecialchars($label) ?>
                        </span>
                        <?php if ($index < count($stepLabels) - 1): ?>
                        <div class="flex-1 h-0.5 mx-4 <?= $isComplete ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Content -->
        <main class="flex-1 py-8">
            <div class="max-w-4xl mx-auto px-4">
                <?php include INSTALL_PATH . "/steps/{$step}.php"; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t py-4">
            <div class="max-w-4xl mx-auto px-4 text-center text-sm text-gray-500">
                &copy; <?= date('Y') ?> RezlyX. All rights reserved.
            </div>
        </footer>
    </div>

    <script>
    function changeLanguage(lang) {
        console.log('[Install] Language changed to:', lang);
        const url = new URL(window.location.href);
        url.searchParams.set('lang', lang);
        window.location.href = url.toString();
    }
    </script>
</body>
</html>
