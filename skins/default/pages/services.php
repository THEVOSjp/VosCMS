<?php
/**
 * RezlyX Default Theme - Services Page
 *
 * 서비스 목록 페이지 샘플
 */
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - 서비스';
$metaDescription = '다양한 서비스를 확인하고 예약하세요.';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';

// Breadcrumbs
$breadcrumbs = [
    ['label' => '홈', 'url' => '/'],
    ['label' => '서비스'],
];

// 샘플 서비스 데이터
$services = $services ?? [
    ['id' => 1, 'name' => '기본 상담', 'description' => '1:1 맞춤 상담 서비스입니다. 개인의 상황에 맞는 솔루션을 제공해 드립니다.', 'price' => 50000, 'duration' => 60, 'category' => '상담'],
    ['id' => 2, 'name' => '프리미엄 상담', 'description' => '심층 분석과 함께하는 프리미엄 상담입니다. 상세한 리포트가 제공됩니다.', 'price' => 100000, 'duration' => 90, 'category' => '상담'],
    ['id' => 3, 'name' => '그룹 세션', 'description' => '소규모 그룹을 위한 세션입니다. 최대 5명까지 참여 가능합니다.', 'price' => 30000, 'duration' => 120, 'category' => '그룹'],
    ['id' => 4, 'name' => '온라인 컨설팅', 'description' => '화상으로 진행되는 온라인 컨설팅입니다. 장소에 구애받지 않고 참여하세요.', 'price' => 40000, 'duration' => 45, 'category' => '온라인'],
    ['id' => 5, 'name' => '워크샵', 'description' => '실습 중심의 워크샵입니다. 직접 경험하며 배울 수 있습니다.', 'price' => 80000, 'duration' => 180, 'category' => '그룹'],
    ['id' => 6, 'name' => '긴급 상담', 'description' => '급한 문제 해결을 위한 긴급 상담 서비스입니다.', 'price' => 70000, 'duration' => 30, 'category' => '상담'],
];

// 카테고리 목록
$categories = array_unique(array_column($services, 'category'));

include __DIR__ . '/../components/cards.php';
?>

<!-- Page Header -->
<section class="bg-gradient-to-r from-blue-600 to-indigo-700 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">서비스</h1>
        <p class="text-lg text-blue-100 max-w-2xl mx-auto">
            다양한 서비스 중에서 원하시는 것을 선택하고 예약하세요
        </p>
    </div>
</section>

<!-- Main Content -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filter -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex flex-wrap items-center gap-2">
                <button class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                               bg-blue-600 text-white"
                        data-filter="all">
                    전체
                </button>
                <?php foreach ($categories as $category): ?>
                    <button class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                                   bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300
                                   hover:bg-zinc-200 dark:hover:bg-zinc-600"
                            data-filter="<?php echo htmlspecialchars($category); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="relative">
                <input type="text"
                       id="searchInput"
                       placeholder="서비스 검색..."
                       class="w-full sm:w-64 px-4 py-2 pl-10
                              border border-zinc-300 dark:border-zinc-600
                              bg-white dark:bg-zinc-700 dark:text-white
                              rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                              transition">
                <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>

        <!-- Services Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="servicesGrid">
            <?php foreach ($services as $service): ?>
                <div class="service-card" data-category="<?php echo htmlspecialchars($service['category']); ?>">
                    <?php renderServiceCard($service, $baseUrl); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="hidden py-12 text-center">
            <svg class="w-16 h-16 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-zinc-500 dark:text-zinc-400">검색 결과가 없습니다</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-16 bg-zinc-100 dark:bg-zinc-800/50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-4">
            원하는 서비스를 찾지 못하셨나요?
        </h2>
        <p class="text-zinc-600 dark:text-zinc-400 mb-6">
            맞춤 서비스에 대해 문의해 주세요. 최적의 솔루션을 제안해 드리겠습니다.
        </p>
        <a href="<?php echo $baseUrl; ?>/contact"
           class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
            문의하기
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('[data-filter]');
    const serviceCards = document.querySelectorAll('.service-card');
    const searchInput = document.getElementById('searchInput');
    const emptyState = document.getElementById('emptyState');
    const grid = document.getElementById('servicesGrid');

    let currentFilter = 'all';

    // 필터 버튼 클릭
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            console.log('[Services] Filter:', button.dataset.filter);

            // 버튼 스타일 업데이트
            filterButtons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-zinc-100', 'dark:bg-zinc-700', 'text-zinc-700', 'dark:text-zinc-300');
            });
            button.classList.remove('bg-zinc-100', 'dark:bg-zinc-700', 'text-zinc-700', 'dark:text-zinc-300');
            button.classList.add('bg-blue-600', 'text-white');

            currentFilter = button.dataset.filter;
            filterServices();
        });
    });

    // 검색
    searchInput.addEventListener('input', filterServices);

    function filterServices() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;

        serviceCards.forEach(card => {
            const category = card.dataset.category;
            const text = card.textContent.toLowerCase();
            const matchesFilter = currentFilter === 'all' || category === currentFilter;
            const matchesSearch = text.includes(searchTerm);

            if (matchesFilter && matchesSearch) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // 빈 상태 표시
        emptyState.classList.toggle('hidden', visibleCount > 0);
        grid.classList.toggle('hidden', visibleCount === 0);
    }
});
</script>
