<?php
/**
 * 사이트 제작 의뢰 — 마케팅 랜딩 (비회원 접근 가능)
 *
 * 호스팅 사업자(voscms.com 등) 전용. vos-hosting 플러그인 활성 시에만 노출.
 * "신청하기" 버튼 → 비로그인이면 /login?next=/mypage/custom-projects/new 로 redirect.
 *                  로그인 상태면 /mypage/custom-projects/new 로 직행.
 */
use RzxLib\Core\Auth\Auth;

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$_isLoggedIn = Auth::check();
$_ctaUrl = $_isLoggedIn
    ? ($baseUrl . '/mypage/custom-projects/new')
    : ($baseUrl . '/login?redirect=' . urlencode($baseUrl . '/mypage/custom-projects/new'));

$pageTitle = __('services.lp.page_title') . ' | ' . ($config['app_name'] ?? 'VosCMS');
?>
<div class="min-h-screen bg-gradient-to-b from-white via-blue-50/30 to-white dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900">

    <!-- Hero -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-12 lg:pt-24 lg:pb-16 text-center">
        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 mb-4">
            <?= htmlspecialchars(__('services.lp.badge')) ?>
        </span>
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-zinc-900 dark:text-white leading-tight">
            <?= nl2br(htmlspecialchars(__('services.lp.hero_title'))) ?>
        </h1>
        <p class="mt-6 text-base sm:text-lg text-zinc-600 dark:text-zinc-300 max-w-2xl mx-auto">
            <?= htmlspecialchars(__('services.lp.hero_desc')) ?>
        </p>
        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <a href="<?= htmlspecialchars($_ctaUrl) ?>"
                class="inline-flex items-center gap-2 px-6 py-3 text-base font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-lg shadow-blue-500/30 transition">
                <?= htmlspecialchars(__('services.lp.btn_request')) ?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="#process" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-blue-600 px-4 py-2">
                <?= htmlspecialchars(__('services.lp.btn_how_it_works')) ?> ↓
            </a>
        </div>
    </section>

    <!-- 특징 4개 -->
    <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php
            $_features = [
                ['icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'title' => __('services.lp.feat1_title'), 'desc' => __('services.lp.feat1_desc')],
                ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => __('services.lp.feat2_title'), 'desc' => __('services.lp.feat2_desc')],
                ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'title' => __('services.lp.feat3_title'), 'desc' => __('services.lp.feat3_desc')],
                ['icon' => 'M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', 'title' => __('services.lp.feat4_title'), 'desc' => __('services.lp.feat4_desc')],
            ];
            foreach ($_features as $f): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-gray-200 dark:border-zinc-700 p-6">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $f['icon'] ?>"/></svg>
                </div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars($f['title']) ?></h3>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed"><?= htmlspecialchars($f['desc']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 진행 프로세스 5단계 -->
    <section id="process" class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-10">
            <h2 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white">
                <?= htmlspecialchars(__('services.lp.process_title')) ?>
            </h2>
            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.lp.process_desc')) ?></p>
        </div>
        <div class="space-y-3">
            <?php
            $_steps = [
                ['n' => '01', 'title' => __('services.lp.step1_title'), 'desc' => __('services.lp.step1_desc')],
                ['n' => '02', 'title' => __('services.lp.step2_title'), 'desc' => __('services.lp.step2_desc')],
                ['n' => '03', 'title' => __('services.lp.step3_title'), 'desc' => __('services.lp.step3_desc')],
                ['n' => '04', 'title' => __('services.lp.step4_title'), 'desc' => __('services.lp.step4_desc')],
                ['n' => '05', 'title' => __('services.lp.step5_title'), 'desc' => __('services.lp.step5_desc')],
            ];
            foreach ($_steps as $s): ?>
            <div class="flex items-start gap-4 p-5 bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white font-bold flex items-center justify-center"><?= $s['n'] ?></div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars($s['title']) ?></h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($s['desc']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- 마지막 CTA -->
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-3xl p-8 sm:p-12 text-center text-white shadow-2xl">
            <h2 class="text-2xl sm:text-3xl font-bold mb-3"><?= htmlspecialchars(__('services.lp.cta_title')) ?></h2>
            <p class="text-sm sm:text-base text-blue-100 mb-6"><?= htmlspecialchars(__('services.lp.cta_desc')) ?></p>
            <a href="<?= htmlspecialchars($_ctaUrl) ?>"
                class="inline-flex items-center gap-2 px-7 py-3 text-base font-bold text-blue-700 bg-white hover:bg-blue-50 rounded-xl shadow-lg transition">
                <?= htmlspecialchars(__('services.lp.btn_request')) ?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <?php if (!$_isLoggedIn): ?>
            <p class="mt-4 text-xs text-blue-200">
                <?= htmlspecialchars(__('services.lp.cta_login_hint')) ?>
            </p>
            <?php endif; ?>
        </div>
    </section>

</div>
