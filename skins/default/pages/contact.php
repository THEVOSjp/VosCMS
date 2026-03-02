<?php
/**
 * RezlyX Default Theme - Contact Page
 *
 * 문의 페이지 샘플
 */
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - 문의하기';
$metaDescription = '문의사항이 있으시면 언제든지 연락해 주세요.';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';

// Breadcrumbs
$breadcrumbs = [
    ['label' => '홈', 'url' => '/'],
    ['label' => '문의하기'],
];

// 폼 처리
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 여기서 실제 폼 처리 로직 구현
    // $success = true;
}
?>

<!-- Page Header -->
<section class="bg-gradient-to-r from-blue-600 to-indigo-700 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-4">문의하기</h1>
        <p class="text-lg text-blue-100 max-w-2xl mx-auto">
            궁금한 점이 있으시면 언제든지 연락해 주세요
        </p>
    </div>
</section>

<!-- Main Content -->
<section class="py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Contact Info -->
            <div class="lg:col-span-1 space-y-8">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-4">연락처 정보</h2>
                    <p class="text-zinc-600 dark:text-zinc-400">
                        평일 09:00 ~ 18:00 (토/일/공휴일 휴무)
                    </p>
                </div>

                <div class="space-y-6">
                    <!-- Phone -->
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-zinc-900 dark:text-white">전화</h3>
                            <p class="text-zinc-600 dark:text-zinc-400">02-1234-5678</p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-zinc-900 dark:text-white">이메일</h3>
                            <p class="text-zinc-600 dark:text-zinc-400">contact@rezlyx.com</p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="flex items-start space-x-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-medium text-zinc-900 dark:text-white">주소</h3>
                            <p class="text-zinc-600 dark:text-zinc-400">
                                서울특별시 강남구<br>
                                테헤란로 123, 5층
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Social Links -->
                <div>
                    <h3 class="font-medium text-zinc-900 dark:text-white mb-3">소셜 미디어</h3>
                    <div class="flex items-center space-x-4">
                        <a href="#" class="w-10 h-10 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center
                                          text-zinc-600 dark:text-zinc-400 hover:bg-blue-100 dark:hover:bg-blue-900/30
                                          hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center
                                          text-zinc-600 dark:text-zinc-400 hover:bg-blue-100 dark:hover:bg-blue-900/30
                                          hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073z"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg dark:shadow-zinc-900/50 p-8">
                    <h2 class="text-xl font-semibold text-zinc-900 dark:text-white mb-6">문의 양식</h2>

                    <?php if ($success): ?>
                        <div class="p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg mb-6">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-green-700 dark:text-green-300">문의가 성공적으로 접수되었습니다. 빠른 시일 내에 답변드리겠습니다.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="contactForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    이름 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" required
                                       class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600
                                              bg-white dark:bg-zinc-700 dark:text-white
                                              rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                       placeholder="홍길동">
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                    이메일 <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="email" name="email" required
                                       class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600
                                              bg-white dark:bg-zinc-700 dark:text-white
                                              rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                       placeholder="example@email.com">
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="mb-6">
                            <label for="phone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                전화번호
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600
                                          bg-white dark:bg-zinc-700 dark:text-white
                                          rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                   placeholder="010-1234-5678">
                        </div>

                        <!-- Subject -->
                        <div class="mb-6">
                            <label for="subject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                문의 유형 <span class="text-red-500">*</span>
                            </label>
                            <select id="subject" name="subject" required
                                    class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600
                                           bg-white dark:bg-zinc-700 dark:text-white
                                           rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition">
                                <option value="">선택해 주세요</option>
                                <option value="general">일반 문의</option>
                                <option value="service">서비스 관련</option>
                                <option value="partnership">제휴/파트너십</option>
                                <option value="technical">기술 지원</option>
                                <option value="other">기타</option>
                            </select>
                        </div>

                        <!-- Message -->
                        <div class="mb-6">
                            <label for="message" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                                문의 내용 <span class="text-red-500">*</span>
                            </label>
                            <textarea id="message" name="message" rows="5" required
                                      class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600
                                             bg-white dark:bg-zinc-700 dark:text-white
                                             rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition resize-none"
                                      placeholder="문의 내용을 자세히 작성해 주세요"></textarea>
                        </div>

                        <!-- Privacy Agreement -->
                        <div class="mb-6">
                            <label class="flex items-start space-x-3 cursor-pointer">
                                <input type="checkbox" name="privacy" required
                                       class="w-5 h-5 mt-0.5 text-blue-600 border-zinc-300 dark:border-zinc-600
                                              rounded focus:ring-blue-500 dark:bg-zinc-700">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">
                                    <a href="<?php echo $baseUrl; ?>/privacy" class="text-blue-600 dark:text-blue-400 hover:underline">개인정보처리방침</a>에 동의합니다.
                                    <span class="text-red-500">*</span>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit"
                                class="w-full px-6 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold
                                       rounded-lg transition-colors shadow-lg shadow-blue-500/30">
                            문의하기
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-16 bg-zinc-100 dark:bg-zinc-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-8 text-center">오시는 길</h2>
        <div class="aspect-video bg-zinc-200 dark:bg-zinc-700 rounded-2xl overflow-hidden shadow-lg">
            <!-- 실제 지도를 넣을 수 있습니다 (Google Maps, Kakao Maps 등) -->
            <div class="w-full h-full flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto text-zinc-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-zinc-500 dark:text-zinc-400">지도 영역</p>
                    <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-1">서울특별시 강남구 테헤란로 123</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white mb-8 text-center">자주 묻는 질문</h2>

        <div class="space-y-4" x-data="{ open: null }">
            <?php
            $faqs = [
                ['q' => '예약은 어떻게 하나요?', 'a' => '서비스 페이지에서 원하시는 서비스를 선택하고, 날짜와 시간을 지정한 후 예약하기 버튼을 클릭하시면 됩니다.'],
                ['q' => '예약 취소는 언제까지 가능한가요?', 'a' => '예약 시간 24시간 전까지 무료 취소가 가능합니다. 이후 취소 시 취소 수수료가 부과될 수 있습니다.'],
                ['q' => '결제는 어떤 방법으로 할 수 있나요?', 'a' => '신용카드, 체크카드, 계좌이체, 간편결제(카카오페이, 네이버페이 등) 등 다양한 결제 수단을 지원합니다.'],
            ];
            foreach ($faqs as $i => $faq):
            ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <button @click="open = open === <?php echo $i; ?> ? null : <?php echo $i; ?>"
                            class="w-full flex items-center justify-between px-6 py-4 text-left">
                        <span class="font-medium text-zinc-900 dark:text-white"><?php echo htmlspecialchars($faq['q']); ?></span>
                        <svg class="w-5 h-5 text-zinc-500 transform transition-transform"
                             :class="{ 'rotate-180': open === <?php echo $i; ?> }"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open === <?php echo $i; ?>" x-collapse
                         class="px-6 pb-4 text-zinc-600 dark:text-zinc-400">
                        <?php echo htmlspecialchars($faq['a']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo $baseUrl; ?>/faq"
               class="text-blue-600 dark:text-blue-400 font-medium hover:underline">
                더 많은 FAQ 보기 →
            </a>
        </div>
    </div>
</section>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    console.log('[Contact] Form submitted');
    // 실제 환경에서는 여기서 AJAX 요청 또는 기본 폼 제출
});
</script>
