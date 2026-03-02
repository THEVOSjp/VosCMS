<?php
/**
 * RezlyX Privacy Policy Page
 */
// 로고 설정
$siteName = $siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX');
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

$pageTitle = $siteName . ' - 개인정보처리방침';
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
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">개인정보처리방침</h1>
            <p class="text-gray-500 dark:text-zinc-400 mb-8">최종 수정일: <?php echo date('Y년 m월 d일'); ?></p>

            <div class="prose prose-gray dark:prose-invert max-w-none">
                <section class="mb-8">
                    <p class="text-gray-600 dark:text-zinc-300 leading-relaxed">
                        <?php echo htmlspecialchars($appName); ?>(이하 "회사")는 개인정보보호법에 따라 이용자의 개인정보 보호 및 권익을 보호하고 개인정보와 관련한 이용자의 고충을 원활하게 처리할 수 있도록 다음과 같은 처리방침을 두고 있습니다.
                    </p>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제1조 (수집하는 개인정보 항목)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">회사는 서비스 제공을 위해 다음과 같은 개인정보를 수집합니다:</p>

                    <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4 mb-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">필수 수집 항목</h3>
                        <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-1">
                            <li>이름, 이메일 주소, 비밀번호</li>
                            <li>휴대폰 번호 (예약 확인 및 알림용)</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4 mb-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">선택 수집 항목</h3>
                        <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-1">
                            <li>생년월일, 성별</li>
                            <li>프로필 사진</li>
                            <li>결제 정보 (결제 시)</li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">자동 수집 항목</h3>
                        <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-1">
                            <li>IP 주소, 쿠키, 서비스 이용 기록</li>
                            <li>접속 로그, 방문 일시</li>
                            <li>기기 정보 (브라우저 종류, OS 등)</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제2조 (개인정보의 수집 및 이용 목적)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">회사는 수집한 개인정보를 다음의 목적을 위해 활용합니다:</p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li><strong>서비스 제공:</strong> 예약 서비스 제공, 예약 확인 및 안내</li>
                        <li><strong>회원 관리:</strong> 회원제 서비스 이용에 따른 본인 확인, 개인 식별, 불량회원 부정이용 방지</li>
                        <li><strong>마케팅 및 광고:</strong> 신규 서비스 개발, 이벤트 정보 및 참여기회 제공 (동의 시)</li>
                        <li><strong>서비스 개선:</strong> 접속 빈도 파악, 회원의 서비스 이용에 대한 통계</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제3조 (개인정보의 보유 및 이용 기간)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">
                        회사는 개인정보 수집 및 이용목적이 달성된 후에는 해당 정보를 지체 없이 파기합니다. 단, 관계 법령의 규정에 의하여 보존할 필요가 있는 경우 회사는 아래와 같이 관계 법령에서 정한 일정한 기간 동안 회원정보를 보관합니다.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-gray-600 dark:text-zinc-300 border dark:border-zinc-700 rounded-lg overflow-hidden">
                            <thead class="bg-gray-100 dark:bg-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">보존 항목</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">보존 기간</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">근거 법령</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-zinc-700">
                                <tr>
                                    <td class="px-4 py-3">계약 또는 청약철회 등에 관한 기록</td>
                                    <td class="px-4 py-3">5년</td>
                                    <td class="px-4 py-3">전자상거래법</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">대금결제 및 재화 등의 공급에 관한 기록</td>
                                    <td class="px-4 py-3">5년</td>
                                    <td class="px-4 py-3">전자상거래법</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">소비자의 불만 또는 분쟁처리에 관한 기록</td>
                                    <td class="px-4 py-3">3년</td>
                                    <td class="px-4 py-3">전자상거래법</td>
                                </tr>
                                <tr>
                                    <td class="px-4 py-3">웹사이트 방문 기록</td>
                                    <td class="px-4 py-3">3개월</td>
                                    <td class="px-4 py-3">통신비밀보호법</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제4조 (개인정보의 제3자 제공)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 leading-relaxed">
                        회사는 이용자의 개인정보를 원칙적으로 외부에 제공하지 않습니다. 다만, 아래의 경우에는 예외로 합니다:
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2 mt-4">
                        <li>이용자들이 사전에 동의한 경우</li>
                        <li>법령의 규정에 의거하거나, 수사 목적으로 법령에 정해진 절차와 방법에 따라 수사기관의 요구가 있는 경우</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제5조 (개인정보의 파기절차 및 방법)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">
                        회사는 원칙적으로 개인정보 수집 및 이용목적이 달성된 후에는 해당 정보를 지체 없이 파기합니다.
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li><strong>파기절차:</strong> 회원님이 회원가입 등을 위해 입력하신 정보는 목적이 달성된 후 별도의 DB로 옮겨져(종이의 경우 별도의 서류함) 내부 방침 및 기타 관련 법령에 의한 정보보호 사유에 따라(보유 및 이용기간 참조) 일정 기간 저장된 후 파기됩니다.</li>
                        <li><strong>파기방법:</strong> 전자적 파일 형태로 저장된 개인정보는 기록을 재생할 수 없는 기술적 방법을 사용하여 삭제합니다. 종이에 출력된 개인정보는 분쇄기로 분쇄하거나 소각을 통하여 파기합니다.</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제6조 (이용자 및 법정대리인의 권리와 행사방법)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">
                        이용자 및 법정 대리인은 언제든지 등록되어 있는 자신 혹은 당해 만 14세 미만 아동의 개인정보를 조회하거나 수정할 수 있으며 가입해지를 요청할 수도 있습니다.
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>개인정보 조회/수정: 마이페이지 > 회원정보 수정</li>
                        <li>회원 탈퇴: 마이페이지 > 회원 탈퇴 또는 고객센터 문의</li>
                        <li>개인정보 처리 정지 요구: 고객센터 문의</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제7조 (쿠키의 운용 및 거부)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">
                        회사는 이용자에게 개별적인 맞춤서비스를 제공하기 위해 이용 정보를 저장하고 수시로 불러오는 "쿠키(cookie)"를 사용합니다.
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>쿠키의 사용 목적: 회원과 비회원의 접속 빈도나 방문 시간 등을 분석, 이용자의 관심분야 파악</li>
                        <li>쿠키의 설치/운영 및 거부: 브라우저 설정에서 쿠키 허용 여부를 선택할 수 있습니다</li>
                    </ul>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제8조 (개인정보 보호책임자)</h2>
                    <div class="bg-blue-50 dark:bg-blue-900/30 rounded-lg p-6">
                        <p class="text-gray-600 dark:text-zinc-300 mb-4">
                            회사는 개인정보 처리에 관한 업무를 총괄해서 책임지고, 개인정보 처리와 관련한 정보주체의 불만처리 및 피해구제 등을 위하여 아래와 같이 개인정보 보호책임자를 지정하고 있습니다.
                        </p>
                        <ul class="text-gray-700 dark:text-zinc-300 space-y-1">
                            <li><strong>개인정보 보호책임자:</strong> 홍길동</li>
                            <li><strong>연락처:</strong> privacy@rezlyx.com</li>
                            <li><strong>부서:</strong> 개인정보보호팀</li>
                        </ul>
                    </div>
                </section>

                <section class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">제9조 (권익침해 구제방법)</h2>
                    <p class="text-gray-600 dark:text-zinc-300 mb-4">
                        정보주체는 개인정보침해로 인한 구제를 받기 위하여 개인정보분쟁조정위원회, 한국인터넷진흥원 개인정보침해신고센터 등에 분쟁해결이나 상담 등을 신청할 수 있습니다.
                    </p>
                    <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2">
                        <li>개인정보분쟁조정위원회: 1833-6972 (www.kopico.go.kr)</li>
                        <li>개인정보침해신고센터: 118 (privacy.kisa.or.kr)</li>
                        <li>대검찰청 사이버범죄수사단: 1301 (www.spo.go.kr)</li>
                        <li>경찰청 사이버안전국: 182 (cyberbureau.police.go.kr)</li>
                    </ul>
                </section>

                <section class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">부칙</h2>
                    <p class="text-gray-600 dark:text-zinc-300">본 개인정보처리방침은 <?php echo date('Y년 m월 d일'); ?>부터 시행됩니다.</p>
                </section>
            </div>
        </div>

        <?php if (!$isEmbed): ?>
        <!-- Navigation -->
        <div class="flex justify-between items-center mt-8">
            <a href="<?php echo $baseUrl; ?>/terms" class="text-blue-600 dark:text-blue-400 hover:underline">← 이용약관</a>
            <a href="<?php echo $baseUrl; ?>/" class="text-blue-600 dark:text-blue-400 hover:underline">홈으로 →</a>
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
