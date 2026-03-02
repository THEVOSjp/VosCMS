<?php
/**
 * RezlyX Default Theme - Home Page
 *
 * 메인 홈페이지 (다국어 적용)
 */

// 다국어 시스템 로드
require_once __DIR__ . '/../../../rzxlib/Core/I18n/Translator.php';
use RzxLib\Core\I18n\Translator;

// 번역 초기화
$langPath = __DIR__ . '/../../../resources/lang';
Translator::init($langPath);

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('home.meta.title');
$metaDescription = __('home.meta.description');
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';

// 샘플 서비스 데이터 (번역 적용)
$sampleServices = Translator::get('home.sample_services');
$defaultServices = [
    ['id' => 1, 'name' => '기본 상담', 'description' => '1:1 맞춤 상담 서비스입니다.', 'price' => 50000, 'duration' => 60],
    ['id' => 2, 'name' => '프리미엄 상담', 'description' => '심층 분석과 함께하는 프리미엄 상담입니다.', 'price' => 100000, 'duration' => 90],
    ['id' => 3, 'name' => '그룹 세션', 'description' => '소규모 그룹을 위한 세션입니다.', 'price' => 30000, 'duration' => 120],
];

// 번역된 서비스 또는 기본 서비스 사용
if (is_string($sampleServices)) {
    $services = $services ?? $defaultServices;
} else {
    $services = $services ?? [
        ['id' => 1, 'name' => $sampleServices[0]['name'] ?? '기본 상담', 'description' => $sampleServices[0]['description'] ?? '', 'price' => 50000, 'duration' => 60],
        ['id' => 2, 'name' => $sampleServices[1]['name'] ?? '프리미엄 상담', 'description' => $sampleServices[1]['description'] ?? '', 'price' => 100000, 'duration' => 90],
        ['id' => 3, 'name' => $sampleServices[2]['name'] ?? '그룹 세션', 'description' => $sampleServices[2]['description'] ?? '', 'price' => 30000, 'duration' => 120],
    ];
}

// 샘플 후기 데이터
$sampleReviews = Translator::get('home.sample_reviews');
$defaultReviews = [
    ['name' => '김민수', 'content' => '예약이 정말 간편해요. 몇 번의 클릭으로 바로 완료!'],
    ['name' => '이영희', 'content' => '리마인더 알림 덕분에 예약을 놓친 적이 없어요.'],
    ['name' => '박준호', 'content' => '다양한 결제 옵션이 있어서 편리합니다.'],
];
$reviews = is_array($sampleReviews) ? $sampleReviews : $defaultReviews;

include __DIR__ . '/../components/cards.php';
?>

<!-- Hero Section -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800"></div>
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"30\" height=\"30\" viewBox=\"0 0 30 30\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cpath d=\"M1.22676 0C1.91374 0 2.45351 0.539773 2.45351 1.22676C2.45351 1.91374 1.91374 2.45351 1.22676 2.45351C0.539773 2.45351 0 1.91374 0 1.22676C0 0.539773 0.539773 0 1.22676 0Z\" fill=\"rgba(255,255,255,0.05)\"%3E%3C/path%3E%3C/svg%3E')] opacity-50"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                <?= __('home.hero.title_1') ?><br>
                <span class="text-blue-200"><?= __('home.hero.title_2') ?></span>
            </h1>
            <p class="text-lg md:text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                <?= __('home.hero.subtitle') ?>
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="<?= $baseUrl ?>/booking"
                   class="w-full sm:w-auto px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl
                          hover:bg-blue-50 transition-colors shadow-lg shadow-blue-900/30">
                    <?= __('home.hero.cta_booking') ?>
                </a>
                <a href="<?= $baseUrl ?>/services"
                   class="w-full sm:w-auto px-8 py-4 bg-blue-500/20 text-white font-semibold rounded-xl
                          hover:bg-blue-500/30 transition-colors border border-blue-400/30">
                    <?= __('home.hero.cta_services') ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Wave Divider -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z"
                  class="fill-zinc-50 dark:fill-zinc-900"/>
        </svg>
    </div>
</section>

<!-- Features Section -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
                <?= __('home.features.title') ?>
            </h2>
            <p class="text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                <?= __('home.features.subtitle') ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php
            renderFeatureCard(
                '<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                __('home.features.booking_24.title'),
                __('home.features.booking_24.desc')
            );
            renderFeatureCard(
                '<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>',
                __('home.features.secure_payment.title'),
                __('home.features.secure_payment.desc')
            );
            renderFeatureCard(
                '<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>',
                __('home.features.realtime_notification.title'),
                __('home.features.realtime_notification.desc')
            );
            renderFeatureCard(
                '<svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>',
                __('home.features.support.title'),
                __('home.features.support.desc')
            );
            ?>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="py-20 bg-zinc-100 dark:bg-zinc-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-12">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-2">
                    <?= __('home.services.title') ?>
                </h2>
                <p class="text-zinc-600 dark:text-zinc-400">
                    <?= __('home.services.subtitle') ?>
                </p>
            </div>
            <a href="<?= $baseUrl ?>/services"
               class="hidden md:inline-flex items-center text-blue-600 dark:text-blue-400 font-medium hover:underline">
                <?= __('home.services.view_all') ?>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($services as $service): ?>
                <?php renderServiceCard($service, $baseUrl); ?>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8 md:hidden">
            <a href="<?= $baseUrl ?>/services"
               class="inline-flex items-center text-blue-600 dark:text-blue-400 font-medium">
                <?= __('home.services.view_all_services') ?>
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            renderStatCard('10,000+', __('home.stats.total_bookings'),
                '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>');
            renderStatCard('98%', __('home.stats.satisfaction'),
                '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>');
            renderStatCard('500+', __('home.stats.partners'),
                '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>');
            renderStatCard('24/7', __('home.stats.support'),
                '<svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>');
            ?>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-20 bg-zinc-100 dark:bg-zinc-800/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
                <?= __('home.testimonials.title') ?>
            </h2>
            <p class="text-zinc-600 dark:text-zinc-400">
                <?= __('home.testimonials.subtitle') ?>
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            renderTestimonialCard($reviews[0]['name'] ?? '', $reviews[0]['content'] ?? '', 5);
            renderTestimonialCard($reviews[1]['name'] ?? '', $reviews[1]['content'] ?? '', 5);
            renderTestimonialCard($reviews[2]['name'] ?? '', $reviews[2]['content'] ?? '', 4);
            ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
            <?= __('home.cta.title') ?>
        </h2>
        <p class="text-lg text-zinc-600 dark:text-zinc-400 mb-8">
            <?= __('home.cta.subtitle') ?>
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="<?= $baseUrl ?>/register"
               class="w-full sm:w-auto px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl
                      transition-colors shadow-lg shadow-blue-500/30">
                <?= __('home.cta.start_free') ?>
            </a>
            <a href="<?= $baseUrl ?>/contact"
               class="w-full sm:w-auto px-8 py-4 border border-zinc-300 dark:border-zinc-600
                      text-zinc-700 dark:text-zinc-300 font-semibold rounded-xl
                      hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
                <?= __('home.cta.contact') ?>
            </a>
        </div>
    </div>
</section>
