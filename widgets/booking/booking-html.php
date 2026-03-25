<?php
/**
 * Booking Widget - HTML 템플릿
 * render.php에서 include됨. 변수: $wTitle, $wSubtitle, $baseUrl, $services,
 * $bkCategories, $svcTranslations, $localeChain, $priceDisplay, $currencySymbol,
 * $isLoggedIn, $currentUser, $renderer, $config
 */
// $renderer가 없을 때 다국어 폴백 함수
if (!function_exists('_bwGetI18n')) {
    function _bwGetI18n($config, $key, $default) {
        $val = $config[$key] ?? $default;
        if (is_array($val)) {
            global $currentLocale;
            $loc = $currentLocale ?? 'ko';
            return $val[$loc] ?? $val['en'] ?? $val['ko'] ?? $default;
        }
        return $val ?: $default;
    }
}
$_bwT = function($cfg, $key, $default) {
    return _bwGetI18n($cfg, $key, $default);
};
?>
<style>
.bw-step-active{background-color:#2563eb;color:#fff}
.bw-step-completed{background-color:#22c55e;color:#fff}
.bw-step-inactive{background-color:#e5e7eb;color:#6b7280}
.dark .bw-step-inactive{background-color:#3f3f46;color:#a1a1aa}
</style>

<section class="py-8" id="bwRoot">
<div class="max-w-4xl mx-auto px-4">
    <?php if ($wTitle || $wSubtitle): ?>
    <div class="text-center mb-8">
        <?php if ($wTitle): ?><h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($_bwT($config, 'title', $wTitle)) ?></h2><?php endif; ?>
        <?php if ($wSubtitle): ?><p class="text-gray-600 dark:text-zinc-400 mt-2"><?= htmlspecialchars($_bwT($config, 'subtitle', $wSubtitle)) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="text-center mb-6">
        <p class="text-sm text-gray-500 dark:text-zinc-500"><?= __('booking.staff_designation_guide') ?></p>
        <a href="<?= $baseUrl ?>/staff" class="inline-flex items-center gap-2 mt-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <?= __('booking.go_staff_booking') ?>
        </a>
    </div>

    <div id="bwProgressBar" class="flex items-center justify-center mb-8"></div>

    <!-- Step 1: 서비스 선택 -->
    <div id="bwStepService" class="bw-step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.select_service') ?></h2>
        <?php if (empty($services)): ?>
        <div class="text-center py-12"><p class="text-gray-500 dark:text-zinc-400"><?= __('booking.no_services') ?></p></div>
        <?php else: ?>

        <?php if (!empty($bkCategories)): ?>
        <div id="bwCatFilter" class="flex flex-wrap gap-2 mb-4">
            <button type="button" class="bw-cat-btn px-4 py-1.5 text-xs font-medium rounded-full transition-all bg-blue-600 text-white" data-cat=""><?= __('common.all') ?></button>
            <?php foreach ($bkCategories as $cid => $cn): ?>
            <button type="button" class="bw-cat-btn px-4 py-1.5 text-xs font-medium rounded-full transition-all bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-zinc-300" data-cat="<?= htmlspecialchars($cid) ?>"><?= htmlspecialchars($cn) ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div id="bwSelectedSummary" class="hidden mb-4 items-center justify-between bg-blue-50 dark:bg-blue-900/20 rounded-lg px-4 py-2">
            <span id="bwSelectedCount" class="text-sm text-blue-700 dark:text-blue-300 font-medium">0</span>
            <div class="text-sm">
                <span class="text-gray-500 dark:text-zinc-400"><?= __('booking.total_duration') ?>:</span>
                <span id="bwTotalDuration" class="font-medium text-gray-900 dark:text-white ml-1">0<?= __('common.minutes') ?></span>
                <?php if ($priceDisplay === 'show'): ?>
                <span class="mx-2 text-gray-300">|</span>
                <span id="bwTotalPrice" class="font-bold text-blue-600 dark:text-blue-400"><?= $currencySymbol ?>0</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($services as $svc):
                $sn = _bwSvcTr($svc['id'], 'name', $svc['name'], $svcTranslations, $localeChain);
                $si = $svc['image'] ?? '';
                $catId = $svc['category_id'] ?? '';
                $catName = $svc['category_name'] ?? '';
                $sp = (float)($svc['price'] ?? 0);
                $sd = (int)($svc['duration'] ?? 60);
            ?>
            <label class="bw-svc-card cursor-pointer" data-cat="<?= htmlspecialchars($catId) ?>">
                <input type="checkbox" name="bw_service[]" value="<?= $svc['id'] ?>" class="hidden"
                       data-name="<?= htmlspecialchars($sn) ?>" data-price="<?= $sp ?>" data-duration="<?= $sd ?>">
                <div class="bw-card-inner group relative rounded-xl border-2 border-gray-200 dark:border-zinc-700 hover:border-gray-300 dark:hover:border-zinc-600 hover:shadow-md cursor-pointer transition-all overflow-hidden" style="min-height:150px;<?php if ($si): ?>background-image:url('<?= htmlspecialchars($baseUrl.'/'.ltrim($si,'/')) ?>');background-size:cover;background-position:center<?php endif; ?>">
                    <?php if (!$si): ?><div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-zinc-700 dark:to-zinc-800"></div><?php endif; ?>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                    <div class="absolute inset-0 bg-blue-500/20 hidden bw-overlay"></div>
                    <div class="absolute top-2 right-2 w-6 h-6 rounded-full border-2 border-white/70 bg-black/20 flex items-center justify-center transition-all shadow-sm z-10 bw-circle">
                        <svg class="w-3.5 h-3.5 text-white hidden bw-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <?php if ($catName): ?>
                    <div class="absolute top-2 left-2 z-10"><span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-black/40 text-white/90 backdrop-blur-sm"><?= htmlspecialchars($catName) ?></span></div>
                    <?php endif; ?>
                    <div class="absolute bottom-0 left-0 right-0 p-3 z-10">
                        <p class="text-sm font-bold text-white truncate drop-shadow-sm"><?= htmlspecialchars($sn) ?></p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-xs text-white/70 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= $sd ?><?= __('common.minutes') ?>
                            </span>
                            <?php if ($priceDisplay === 'show'): ?>
                            <span class="text-sm font-bold text-white drop-shadow-sm"><?= $currencySymbol ?><?= number_format($sp) ?></span>
                            <?php elseif ($priceDisplay === 'contact'): ?>
                            <span class="text-xs text-white/80"><?= __('services.settings.general.price_contact') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="flex justify-end mt-6">
            <button type="button" id="bwBtnServiceNext" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <?= __('common.buttons.next') ?> <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Step 2: 날짜/시간 -->
    <div id="bwStepDatetime" class="bw-step-panel bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 md:p-8 hidden">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6"><?= __('booking.select_datetime') ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('booking.select_date') ?></label>
                <input type="date" id="bwBookingDate" min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('booking.select_time') ?></label>
                <div id="bwTimeSlots" class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto"></div>
            </div>
        </div>
        <div class="flex justify-between mt-6">
            <button type="button" class="bw-prev-btn px-6 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg> <?= __('common.buttons.previous') ?>
            </button>
            <button type="button" id="bwBtnDatetimeNext" class="bw-next-btn px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                <?= __('common.buttons.next') ?> <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <?php include __DIR__ . '/booking-steps.php'; ?>
</div>
</section>

<script>
window.__bwConfig = {
    currencySymbol: '<?= $currencySymbol ?>',
    priceDisplay: '<?= $priceDisplay ?>',
    baseUrl: '<?= $baseUrl ?>',
    labels: {
        selectService: '<?= __('booking.select_service') ?>',
        selectDatetime: '<?= __('booking.select_datetime') ?>',
        enterInfo: '<?= __('booking.enter_info') ?>',
        confirmInfo: '<?= __('booking.confirm_info') ?>',
        itemsSelected: '<?= __('booking.items_selected') ?>',
        minutes: '<?= __('common.minutes') ?>',
        loadingSlots: '<?= __('booking.loading_slots') ?>',
        noSlots: '<?= __('booking.no_available_slots') ?>',
        requiredFields: '<?= __('booking.error.required_fields') ?>',
        priceContact: '<?= __('services.settings.general.price_contact') ?>',
        error: '<?= __('common.error') ?>',
        submitting: '<?= __('booking.submitting') ?>',
        completeBooking: '<?= __('booking.complete_booking') ?>'
    }
};
</script>
<script src="<?= $baseUrl ?>/widgets/booking/booking.js"></script>
