<?php
/**
 * RezlyX Terms of Service Page
 */
// 로고 설정
$siteName = $siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX');
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

$pageTitle = $siteName . ' - 이용약관';
$appName = $siteName;

// baseUrl 경로만 추출
if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
    <?php if (!$isEmbed): ?>
    <!-- Header -->
    <header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?php echo $baseUrl; ?>/" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                    <?php if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-10 object-contain">
                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="" class="h-10 object-contain mr-2">
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php endif; ?>
                </a>
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <button id="langBtn" class="flex items-center space-x-1 px-3 py-2 text-sm font-medium text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <span>KO</span>
                        </button>
                        <div id="langDropdown" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 py-1 z-50">
                            <a href="?lang=ko" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">한국어</a>
                            <a href="?lang=en" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">English</a>
                            <a href="?lang=ja" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">日本語</a>
                        </div>
                    </div>
                    <button id="darkModeBtn" class="p-2 text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <a href="<?php echo $baseUrl; ?>/login" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400">로그인</a>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="<?php echo $isEmbed ? 'p-6' : 'max-w-4xl mx-auto px-4 py-12'; ?>">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg dark:shadow-zinc-900/50 p-8 md:p-12 transition-colors">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">이용약관</h1>
            <p class="text-gray-500 dark:text-zinc-400 mb-8">최종 수정일: <?php echo date('Y년 m월 d일'); ?></p>

            <div class="prose prose-gray dark:prose-invert max-w-none">
                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제1조 (목적)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 leading-relaxed">
                        본 약관은 <?php echo htmlspecialchars($appName); ?>(이하 "회사")가 제공하는 예약 서비스(이하 "서비스")의 이용과 관련하여 회사와 회원 간의 권리, 의무 및 책임사항, 기타 필요한 사항을 규정함을 목적으로 합니다.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제2조 (정의)</h2>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>"서비스"란 회사가 제공하는 온라인 예약 플랫폼 및 관련 부가서비스를 의미합니다.</li>
                        <li>"회원"이란 본 약관에 동의하고 회사와 서비스 이용계약을 체결한 자를 의미합니다.</li>
                        <li>"예약"이란 회원이 서비스를 통해 특정 일시에 서비스 제공을 신청하는 것을 의미합니다.</li>
                        <li>"적립금"이란 회사가 회원에게 제공하는 포인트로, 서비스 이용 시 사용할 수 있습니다.</li>
                    </ol>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제3조 (약관의 효력 및 변경)</h2>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>본 약관은 서비스 화면에 게시하거나 기타의 방법으로 회원에게 공지함으로써 효력이 발생합니다.</li>
                        <li>회사는 필요한 경우 관련 법령을 위배하지 않는 범위에서 본 약관을 변경할 수 있습니다.</li>
                        <li>변경된 약관은 공지 후 7일이 경과한 날부터 효력이 발생합니다.</li>
                    </ol>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제4조 (서비스 이용)</h2>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>서비스는 연중무휴, 1일 24시간 제공함을 원칙으로 합니다.</li>
                        <li>회사는 시스템 점검, 교체 및 고장, 통신 두절 등의 사유가 발생한 경우 서비스의 제공을 일시적으로 중단할 수 있습니다.</li>
                        <li>회원은 서비스를 이용하여 얻은 정보를 회사의 사전 승낙 없이 복제, 송신, 출판, 배포, 방송 등 기타 방법에 의하여 영리 목적으로 이용하거나 제3자에게 이용하게 하여서는 안 됩니다.</li>
                    </ol>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제5조 (예약 및 취소)</h2>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>회원은 서비스를 통해 예약을 진행할 수 있으며, 예약 확정 시 회원에게 확인 메시지가 발송됩니다.</li>
                        <li>예약 취소는 예약 시간 24시간 전까지 가능하며, 이후 취소 시 취소 수수료가 부과될 수 있습니다.</li>
                        <li>노쇼(No-show) 시 향후 서비스 이용에 제한이 있을 수 있습니다.</li>
                    </ol>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제6조 (회원의 의무)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 leading-relaxed mb-4">회원은 다음 행위를 하여서는 안 됩니다:</p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>타인의 정보 도용</li>
                        <li>회사가 게시한 정보의 변경</li>
                        <li>회사가 정한 정보 이외의 정보(컴퓨터 프로그램 등) 등의 송신 또는 게시</li>
                        <li>회사 및 기타 제3자의 저작권 등 지적재산권에 대한 침해</li>
                        <li>회사 및 기타 제3자의 명예를 손상시키거나 업무를 방해하는 행위</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제7조 (면책조항)</h2>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>회사는 천재지변, 전쟁, 기타 불가항력으로 인해 서비스를 제공할 수 없는 경우에는 서비스 제공에 관한 책임이 면제됩니다.</li>
                        <li>회사는 회원의 귀책사유로 인한 서비스 이용의 장애에 대하여 책임을 지지 않습니다.</li>
                        <li>회사는 회원이 서비스를 이용하여 기대하는 수익을 상실한 것에 대해 책임을 지지 않습니다.</li>
                    </ol>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제8조 (분쟁해결)</h2>
                    <ol class="list-decimal list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>회사와 회원 간에 발생한 분쟁에 관한 소송은 회사의 주소지를 관할하는 법원을 전속 관할법원으로 합니다.</li>
                        <li>회사와 회원 간에 제기된 소송에는 대한민국 법을 적용합니다.</li>
                    </ol>
                </section>

                <section class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">부칙</h2>
                    <p class="text-gray-600 dark:text-zinc-300">본 약관은 <?php echo date('Y년 m월 d일'); ?>부터 시행됩니다.</p>
                </section>
            </div>
        </div>

        <?php if (!$isEmbed): ?>
        <!-- Navigation -->
        <div class="flex justify-between items-center mt-8">
            <a href="<?php echo $baseUrl; ?>/" class="text-blue-600 dark:text-blue-400 hover:underline">← 홈으로</a>
            <a href="<?php echo $baseUrl; ?>/privacy" class="text-blue-600 dark:text-blue-400 hover:underline">개인정보처리방침 →</a>
        </div>
        <?php endif; ?>
    </main>

    <?php if (!$isEmbed): ?>
    <!-- Footer -->
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($appName); ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        const langBtn = document.getElementById('langBtn');
        const langDropdown = document.getElementById('langDropdown');
        langBtn.addEventListener('click', (e) => { e.stopPropagation(); langDropdown.classList.toggle('hidden'); });
        document.addEventListener('click', () => langDropdown.classList.add('hidden'));

        const darkModeBtn = document.getElementById('darkModeBtn');
        darkModeBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
        });
    </script>
    <?php endif; ?>
</body>
</html>
