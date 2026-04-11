<?php
/**
 * Developer - 로그인 / 회원가입 페이지
 * 다국어 + 다크모드 + 언어변환기
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['developer_id'])) {
    header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/dashboard');
    exit;
}
if (str_ends_with(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/logout')) {
    unset($_SESSION['developer_id'], $_SESSION['developer_name'], $_SESSION['developer_email'], $_SESSION['developer_type']);
    header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login');
    exit;
}

$baseUrl = $_ENV['APP_URL'] ?? '';
$locale = $_SESSION['locale'] ?? ($_COOKIE['locale'] ?? ($_ENV['APP_LOCALE'] ?? 'ko'));
$_mpLangFile = BASE_PATH . '/resources/lang/' . $locale . '/marketplace.php';
if (!file_exists($_mpLangFile)) $_mpLangFile = BASE_PATH . '/resources/lang/en/marketplace.php';
$_mpLang = file_exists($_mpLangFile) ? require $_mpLangFile : [];
if (!function_exists('__mp')) {
    function __mp(string $key, string $default = ''): string {
        global $_mpLang;
        return $_mpLang[$key] ?? $default ?: $key;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __mp('dev_login') ?> - VosCMS Developer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, sans-serif; } [x-cloak]{display:none!important;}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13/dist/cdn.min.js"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen flex items-center justify-center p-4 transition-colors">

<!-- 우상단 컨트롤 -->
<div class="fixed top-4 right-4 flex items-center gap-2">
    <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>
    <button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
    </button>
</div>

<div class="w-full max-w-md" x-data="{ mode: 'login' }">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-white"><span class="text-indigo-600">Vos</span>CMS <?= __mp('developer_portal') ?></h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __mp('dev_tagline') ?></p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex mb-6 border-b border-zinc-200 dark:border-zinc-700">
            <button @click="mode='login'" :class="mode==='login' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-400'" class="flex-1 pb-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('dev_login') ?></button>
            <button @click="mode='register'" :class="mode==='register' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-400'" class="flex-1 pb-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('dev_register') ?></button>
        </div>

        <div id="message" class="hidden mb-4 p-3 rounded-lg text-sm"></div>

        <!-- 로그인 -->
        <form x-show="mode==='login'" @submit.prevent="doLogin()" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_email') ?></label>
                <input type="email" id="login-email" required class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_password') ?></label>
                <input type="password" id="login-password" required class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm"><?= __mp('dev_login') ?></button>
        </form>

        <!-- 회원가입 -->
        <form x-show="mode==='register'" x-cloak @submit.prevent="doRegister()" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_name') ?> *</label>
                <input type="text" id="reg-name" required class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_email') ?> *</label>
                <input type="email" id="reg-email" required class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_password') ?> * (<?= __mp('dev_password_min') ?>)</label>
                <input type="password" id="reg-password" required minlength="8" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_company') ?></label>
                <input type="text" id="reg-company" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __mp('dev_website') ?></label>
                <input type="url" id="reg-website" class="w-full px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 text-sm" placeholder="https://">
            </div>
            <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm"><?= __mp('dev_register') ?></button>
        </form>
    </div>
    <p class="text-center text-xs text-zinc-400 dark:text-zinc-500 mt-4">
        <a href="<?= $baseUrl ?>/marketplace" class="hover:underline"><?= __mp('marketplace') ?></a>
    </p>
</div>

<script>
const API = '<?= $baseUrl ?>/api/developer';
function showMsg(text, ok) {
    const el = document.getElementById('message');
    el.className = 'mb-4 p-3 rounded-lg text-sm ' + (ok ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400');
    el.textContent = text; el.style.display = 'block';
}
async function doLogin() {
    const res = await fetch(API+'/login', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({email:document.getElementById('login-email').value, password:document.getElementById('login-password').value})}).then(r=>r.json());
    if (res.success) window.location.replace('<?= $baseUrl ?>/developer/dashboard'); else showMsg(res.message||'Login failed',false);
}
async function doRegister() {
    const res = await fetch(API+'/register', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({name:document.getElementById('reg-name').value, email:document.getElementById('reg-email').value, password:document.getElementById('reg-password').value, company:document.getElementById('reg-company').value, website:document.getElementById('reg-website').value})}).then(r=>r.json());
    if (res.success) window.location.replace('<?= $baseUrl ?>/developer/dashboard'); else showMsg(res.message||'Registration failed',false);
}
document.getElementById('darkModeBtn')?.addEventListener('click',()=>{const d=document.documentElement.classList.toggle('dark');localStorage.setItem('darkMode',d);});
</script>
</body>
</html>
