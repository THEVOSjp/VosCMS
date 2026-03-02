<?php
/**
 * RezlyX Default Theme - Index
 *
 * 테마 정보 및 샘플 페이지 목록
 * 접속: http://localhost/rezlyx/skins/default/
 */

$config = require __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['name']; ?> Theme - RezlyX</title>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="<?php echo $config['fonts']['cdn']; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Pretendard', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-white min-h-screen" style="font-family: 'Pretendard', sans-serif;">
    <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-2xl mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2"><?php echo $config['name']; ?> Theme</h1>
            <p class="text-zinc-600 dark:text-zinc-400"><?php echo $config['description']; ?></p>
            <p class="text-sm text-zinc-500 dark:text-zinc-500 mt-2">
                버전 <?php echo $config['version']; ?> | <?php echo $config['author']; ?>
            </p>
        </div>

        <!-- Dark Mode Toggle -->
        <div class="flex justify-center mb-8">
            <button onclick="toggleDarkMode()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <span class="dark:hidden">다크 모드</span>
                <span class="hidden dark:inline">라이트 모드</span>
            </button>
        </div>

        <!-- Sample Pages -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">샘플 페이지</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">각 페이지를 클릭하여 미리보기</p>
            </div>
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php
                $pages = [
                    ['name' => 'home', 'title' => '홈페이지', 'desc' => '히어로, 기능, 서비스, 후기 섹션'],
                    ['name' => 'services', 'title' => '서비스', 'desc' => '서비스 목록, 필터, 검색'],
                    ['name' => 'about', 'title' => '소개', 'desc' => '회사 소개, 팀, 연혁'],
                    ['name' => 'contact', 'title' => '문의', 'desc' => '문의 폼, FAQ 아코디언'],
                ];
                foreach ($pages as $p):
                ?>
                <a href="preview.php?page=<?php echo $p['name']; ?>"
                   class="flex items-center justify-between px-6 py-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors group">
                    <div>
                        <h3 class="font-medium group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            <?php echo $p['title']; ?>
                        </h3>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400"><?php echo $p['desc']; ?></p>
                    </div>
                    <svg class="w-5 h-5 text-zinc-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Components -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">컴포넌트</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">재사용 가능한 UI 컴포넌트</p>
            </div>
            <div class="p-6 grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php
                $components = ['header', 'footer', 'breadcrumbs', 'alerts', 'cards'];
                foreach ($components as $comp):
                ?>
                <div class="px-4 py-3 bg-zinc-100 dark:bg-zinc-700 rounded-lg text-center">
                    <span class="text-sm font-medium"><?php echo $comp; ?>.php</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Theme Features -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">테마 특징</h2>
            </div>
            <div class="p-6 grid grid-cols-2 gap-4">
                <?php
                $features = [
                    ['icon' => 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z', 'title' => '다크 모드', 'enabled' => $config['features']['dark_mode']],
                    ['icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9', 'title' => '다국어', 'enabled' => $config['features']['language_selector']],
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => '브레드크럼', 'enabled' => $config['features']['breadcrumbs']],
                    ['icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'title' => 'PWA', 'enabled' => $config['features']['pwa']],
                ];
                foreach ($features as $feature):
                ?>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg <?php echo $feature['enabled'] ? 'bg-green-100 dark:bg-green-900/30' : 'bg-zinc-100 dark:bg-zinc-700'; ?> flex items-center justify-center">
                        <svg class="w-5 h-5 <?php echo $feature['enabled'] ? 'text-green-600' : 'text-zinc-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $feature['icon']; ?>"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-sm"><?php echo $feature['title']; ?></p>
                        <p class="text-xs <?php echo $feature['enabled'] ? 'text-green-600' : 'text-zinc-400'; ?>">
                            <?php echo $feature['enabled'] ? '활성화' : '비활성화'; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Color Palette -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold">컬러 팔레트</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Zinc 컬러 스킴</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-5 gap-2 mb-4">
                    <?php
                    $zincColors = [
                        '50' => '#FAFAFA', '100' => '#F4F4F5', '200' => '#E4E4E7',
                        '300' => '#D4D4D8', '400' => '#A1A1AA', '500' => '#71717A',
                        '600' => '#52525B', '700' => '#3F3F46', '800' => '#27272A', '900' => '#18181B'
                    ];
                    foreach ($zincColors as $shade => $hex):
                    ?>
                    <div class="text-center">
                        <div class="h-12 rounded-lg mb-1" style="background-color: <?php echo $hex; ?>"></div>
                        <span class="text-xs text-zinc-500"><?php echo $shade; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap-2">
                    <div class="flex-1 text-center">
                        <div class="h-10 rounded-lg mb-1 bg-blue-500"></div>
                        <span class="text-xs text-zinc-500">Primary</span>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="h-10 rounded-lg mb-1 bg-slate-500"></div>
                        <span class="text-xs text-zinc-500">Secondary</span>
                    </div>
                    <div class="flex-1 text-center">
                        <div class="h-10 rounded-lg mb-1 bg-amber-500"></div>
                        <span class="text-xs text-zinc-500">Accent</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-sm text-zinc-500 dark:text-zinc-500">
            <p>&copy; 2025 RezlyX. MIT License.</p>
        </div>
    </div>

    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
        }
    </script>
</body>
</html>
