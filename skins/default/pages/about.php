<?php
/**
 * RezlyX Default Theme - About Page
 *
 * 회사 소개 페이지 샘플
 */
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - 소개';
$metaDescription = 'RezlyX는 스마트한 예약 솔루션을 제공하는 기업입니다.';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';

// Breadcrumbs
$breadcrumbs = [
    ['label' => '홈', 'url' => '/'],
    ['label' => '소개'],
];

include __DIR__ . '/../components/cards.php';
?>

<!-- Page Header -->
<section class="bg-gradient-to-r from-blue-600 to-indigo-700 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">회사 소개</h1>
        <p class="text-lg text-blue-100 max-w-2xl mx-auto">
            예약을 넘어, 더 나은 경험을 만들어갑니다
        </p>
    </div>
</section>

<!-- Mission Section -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-6">
                    우리의 미션
                </h2>
                <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-6 leading-relaxed">
                    RezlyX는 복잡한 예약 과정을 단순화하여 사업자와 고객 모두에게
                    최상의 경험을 제공하고자 합니다.
                </p>
                <p class="text-zinc-600 dark:text-zinc-400 mb-6 leading-relaxed">
                    2020년 설립 이래, 우리는 끊임없이 혁신하며 예약 시스템의 새로운 기준을
                    만들어가고 있습니다. 직관적인 인터페이스, 강력한 관리 도구, 그리고
                    신뢰할 수 있는 보안 시스템을 통해 파트너사와 함께 성장합니다.
                </p>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">간편한 예약</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">스마트 관리</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-zinc-700 dark:text-zinc-300">안전한 결제</span>
                    </div>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-video bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl
                            flex items-center justify-center">
                    <svg class="w-24 h-24 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-20 bg-zinc-100 dark:bg-zinc-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">핵심 가치</h2>
            <p class="text-zinc-600 dark:text-zinc-400">우리가 중요하게 생각하는 것들</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 shadow-lg dark:shadow-zinc-900/50 text-center">
                <div class="w-16 h-16 mx-auto mb-6 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">혁신</h3>
                <p class="text-zinc-600 dark:text-zinc-400">
                    끊임없이 새로운 기술과 방법을 탐구하여 더 나은 솔루션을 제공합니다.
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 shadow-lg dark:shadow-zinc-900/50 text-center">
                <div class="w-16 h-16 mx-auto mb-6 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">신뢰</h3>
                <p class="text-zinc-600 dark:text-zinc-400">
                    투명한 운영과 안전한 시스템으로 고객의 신뢰를 최우선으로 합니다.
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 shadow-lg dark:shadow-zinc-900/50 text-center">
                <div class="w-16 h-16 mx-auto mb-6 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white mb-3">협력</h3>
                <p class="text-zinc-600 dark:text-zinc-400">
                    파트너와 함께 성장하며, 고객의 성공이 곧 우리의 성공입니다.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">팀 소개</h2>
            <p class="text-zinc-600 dark:text-zinc-400">열정적인 전문가들이 함께합니다</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            $team = [
                ['name' => '김대표', 'role' => 'CEO', 'initial' => '김'],
                ['name' => '이개발', 'role' => 'CTO', 'initial' => '이'],
                ['name' => '박디자인', 'role' => 'Design Lead', 'initial' => '박'],
                ['name' => '최마케팅', 'role' => 'CMO', 'initial' => '최'],
            ];
            foreach ($team as $member):
            ?>
                <div class="text-center">
                    <div class="w-24 h-24 mx-auto mb-4 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full
                                flex items-center justify-center shadow-lg">
                        <span class="text-2xl font-bold text-white"><?php echo $member['initial']; ?></span>
                    </div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?php echo $member['name']; ?></h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><?php echo $member['role']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Timeline Section -->
<section class="py-20 bg-zinc-100 dark:bg-zinc-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4">연혁</h2>
            <p class="text-zinc-600 dark:text-zinc-400">우리의 성장 스토리</p>
        </div>

        <div class="space-y-8">
            <?php
            $milestones = [
                ['year' => '2020', 'title' => '회사 설립', 'desc' => 'RezlyX 서비스 런칭'],
                ['year' => '2021', 'title' => '첫 1,000 고객 달성', 'desc' => '빠른 성장과 함께 서비스 안정화'],
                ['year' => '2022', 'title' => '시리즈 A 투자 유치', 'desc' => '본격적인 사업 확장 시작'],
                ['year' => '2023', 'title' => '글로벌 진출', 'desc' => '일본, 동남아 시장 진출'],
                ['year' => '2024', 'title' => '10,000+ 예약 달성', 'desc' => '누적 예약 10,000건 돌파'],
            ];
            foreach ($milestones as $i => $m):
            ?>
                <div class="flex items-start space-x-4">
                    <div class="flex-shrink-0 w-20 text-right">
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400"><?php echo $m['year']; ?></span>
                    </div>
                    <div class="flex-shrink-0 w-4 h-4 mt-1 bg-blue-600 rounded-full ring-4 ring-blue-100 dark:ring-blue-900/50"></div>
                    <div class="flex-1 pb-8 <?php echo $i < count($milestones) - 1 ? 'border-l-2 border-zinc-200 dark:border-zinc-700 -ml-2 pl-6' : ''; ?>">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?php echo $m['title']; ?></h3>
                        <p class="text-zinc-600 dark:text-zinc-400"><?php echo $m['desc']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">
            함께 성장하고 싶으신가요?
        </h2>
        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
            RezlyX와 함께할 파트너를 찾고 있습니다
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?php echo $baseUrl; ?>/contact"
               class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                파트너 문의
            </a>
            <a href="<?php echo $baseUrl; ?>/careers"
               class="px-6 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300
                      font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                채용 정보
            </a>
        </div>
    </div>
</section>
