<?php
/**
 * RezlyX Member Skin - Modern
 * 헤더 컴포넌트 (로고, 언어선택, 다크모드 토글)
 */

// 옵션 기본값 설정
$showLangSelector = true;
$showDarkMode = true;
if (isset($config) && is_array($config)) {
    if (isset($config['options']['show_language_selector'])) {
        $showLangSelector = $config['options']['show_language_selector'];
    }
    if (isset($config['options']['show_dark_mode'])) {
        $showDarkMode = $config['options']['show_dark_mode'];
    }
}

$languages = ['ko' => '한국어', 'en' => 'English', 'ja' => '日本語'];
if (isset($config['languages']) && is_array($config['languages'])) {
    $languages = $config['languages'];
}
?>
<header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                <?php if (!empty($logoImage)): ?>
                    <img src="<?= htmlspecialchars(($baseUrl ?? '') . $logoImage) ?>" alt="<?= htmlspecialchars($siteName ?? 'RezlyX') ?>" class="h-10 object-contain">
                <?php else: ?>
                    <span><?= htmlspecialchars($siteName ?? 'RezlyX') ?></span>
                <?php endif; ?>
            </a>

            <div class="flex items-center space-x-3">
                <!-- 언어 선택 -->
                <?php if ($showLangSelector): ?>
                <div class="relative" id="langContainer">
                    <button type="button" onclick="toggleLangDropdown()" class="flex items-center space-x-1 px-3 py-2 text-sm font-medium text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span><?= strtoupper($currentLocale ?? 'KO') ?></span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="langDropdown" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 py-1 z-50">
                        <?php foreach ($languages as $code => $name): ?>
                        <a href="javascript:void(0)" onclick="changeLanguage('<?= $code ?>')" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <?= htmlspecialchars($name) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 다크모드 토글 -->
                <?php if ($showDarkMode): ?>
                <button type="button" onclick="toggleDarkMode()" class="p-2 text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700" title="다크모드 전환">
                    <!-- 해 아이콘 (다크모드일 때 표시) -->
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <!-- 달 아이콘 (라이트모드일 때 표시) -->
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<script>
// 언어 드롭다운 토글
function toggleLangDropdown() {
    var dropdown = document.getElementById('langDropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

// 언어 변경
function changeLanguage(lang) {
    document.cookie = 'locale=' + lang + ';path=/;max-age=31536000';
    var url = window.location.pathname + '?lang=' + lang;
    window.location.href = url;
}

// 다크모드 토글
function toggleDarkMode() {
    var html = document.documentElement;
    var isDark = html.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    console.log('Dark mode:', isDark);
}

// 외부 클릭 시 드롭다운 닫기
document.addEventListener('click', function(e) {
    var container = document.getElementById('langContainer');
    var dropdown = document.getElementById('langDropdown');
    if (container && dropdown && !container.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});
</script>
