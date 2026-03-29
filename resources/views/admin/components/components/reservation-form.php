<?php
/**
 * 예약 생성 폼 공용 컴포넌트
 * create.php(페이지)와 캘린더 모달에서 공용 사용
 *
 * 필수 변수 ($resForm 배열):
 *   services         — 서비스 목록 [{id, name, description, price, duration}, ...]
 *   adminUrl         — 관리자 URL
 *   csrfToken        — CSRF 토큰
 *   currencySymbol   — 통화 기호
 *   currencyPosition — prefix | suffix
 *   formId           — 고유 폼 ID ('createForm' | 'rzxCalAddForm')
 *   mode             — 'page' | 'modal'
 *   defaultDate      — 기본 날짜 (Y-m-d)
 *   old              — 이전 입력값 (page 모드용)
 */

$fId = $resForm['formId'];
$fMode = $resForm['mode'];
$fOld = $resForm['old'] ?? [];
$fServices = $resForm['services'] ?? [];
$fSymbol = $resForm['currencySymbol'];
$fPosition = $resForm['currencyPosition'];
if (isset($baseUrl)) {
    $fBaseUrl = $baseUrl;
} elseif (isset($adminUrl)) {
    // 관리자 URL에서 baseUrl 추출: /rezlyx_salon/theadmin → /rezlyx_salon
    $fBaseUrl = preg_replace('#/[^/]+$#', '', $adminUrl);
} else {
    $appUrl = rtrim($config['app_url'] ?? '', '/');
    $fBaseUrl = parse_url($appUrl, PHP_URL_PATH) ?: '';
}

// 다국어: 서비스/카테고리/번들 번역 로드
$_rfLocale = $config['locale'] ?? 'ko';
$_rfDefLocale = $siteSettings['default_language'] ?? 'ko';
$_rfLocaleChain = array_unique(array_filter([$_rfLocale, 'en', $_rfDefLocale]));
$_rfTranslations = [];
if (isset($pdo)) {
    try {
        $_rfLcPH = implode(',', array_fill(0, count($_rfLocaleChain), '?'));
        $_rfPrefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $_rfTrStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$_rfPrefix}translations WHERE locale IN ({$_rfLcPH}) AND (lang_key LIKE 'service.%.name' OR lang_key LIKE 'category.%.name' OR lang_key LIKE 'bundle.%.name' OR lang_key LIKE 'bundle.%.description')");
        $_rfTrStmt->execute(array_values($_rfLocaleChain));
        while ($_rfTr = $_rfTrStmt->fetch(PDO::FETCH_ASSOC)) {
            $_rfTranslations[$_rfTr['lang_key']][$_rfTr['locale']] = $_rfTr['content'];
        }
    } catch (PDOException $e) {}
}
if (!function_exists('_rfTr')) {
    function _rfTr($type, $id, $field, $default, $translations, $chain) {
        $key = "{$type}.{$id}.{$field}";
        if (isset($translations[$key])) {
            foreach ($chain as $loc) {
                if (!empty($translations[$key][$loc])) return $translations[$key][$loc];
            }
        }
        return $default;
    }
}

// 포맷 헬퍼 (PHP)
if (!function_exists('resFormFormatPrice')) {
    function resFormFormatPrice(float $amount, string $symbol, string $position): string {
        $formatted = number_format($amount);
        return $position === 'suffix' ? $formatted . $symbol : $symbol . $formatted;
    }
}
?>

<form method="POST" action="<?= $resForm['adminUrl'] ?>/reservations" id="<?= $fId ?>">
    <input type="hidden" name="_token" value="<?= $resForm['csrfToken'] ?>">
    <input type="hidden" name="user_id" id="<?= $fId ?>_userId" value="">
    <input type="hidden" name="source" id="<?= $fId ?>_source" value="<?= htmlspecialchars($resForm['source'] ?? $fOld['source'] ?? 'phone') ?>">

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- 좌측: 서비스 선택 (3/5) -->
        <div class="lg:col-span-3">
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 h-full' : '' ?>">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('reservations.form_service_select') ?></h3>
                    <div class="flex items-center gap-2">
                        <input type="text" id="<?= $fId ?>_svcSearch" placeholder="<?= __('reservations.form_search') ?>"
                               class="px-3 py-1.5 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 w-36">
                        <span id="<?= $fId ?>_selectedCount" class="text-sm text-zinc-500 dark:text-zinc-400"><?= str_replace(':count', '0', __('reservations.form_selected_count')) ?></span>
                    </div>
                </div>

                <!-- 추천 패키지 -->
                <?php $fBundles = $resForm['bundles'] ?? []; ?>
                <?php if (!empty($fBundles)): ?>
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2 flex items-center gap-1">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                        <?php
                        $__bdnRaw = $siteSettings['bundle_display_name'] ?? '';
                        $__bdnJson = json_decode($__bdnRaw, true);
                        if (is_array($__bdnJson)) {
                            $__bdn = $__bdnJson[$_rfLocale] ?? $__bdnJson['en'] ?? $__bdnJson['ko'] ?? __('bundles.set_service') ?? '세트 서비스';
                        } else {
                            $__bdn = $__bdnRaw ?: (__('bundles.set_service') ?? '세트 서비스');
                        }
                        ?>
                        <?= __('bundles.recommended') ?? '추천' ?> <?= htmlspecialchars($__bdn) ?>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <?php foreach ($fBundles as $bdl):
                            $bdlImg = $bdl['image'] ?? '';
                            if ($bdlImg && !str_starts_with($bdlImg, 'http')) $bdlImg = $fBaseUrl . '/' . ltrim($bdlImg, '/');
                            $bdlPrice = (float)$bdl['display_price'];
                            $bdlOriginal = (float)$bdl['bundle_price'];
                            $bdlSvcIds = implode(',', $bdl['svc_id_list'] ?? []);
                        ?>
                        <div class="rf-bundle-card cursor-pointer rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-400 hover:shadow transition-all overflow-hidden"
                             data-services="<?= htmlspecialchars($bdlSvcIds) ?>" data-form-id="<?= $fId ?>" data-bundle-price="<?= $bdlPrice ?>" data-bundle-name="<?= htmlspecialchars(_rfTr('bundle', $bdl['id'], 'name', $bdl['name'], $_rfTranslations, $_rfLocaleChain)) ?>">
                            <div class="flex items-center gap-3 p-2">
                                <?php if ($bdlImg): ?>
                                <img src="<?= htmlspecialchars($bdlImg) ?>" class="w-16 h-12 rounded object-cover flex-shrink-0" alt="">
                                <?php endif; ?>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate"><?= htmlspecialchars(_rfTr('bundle', $bdl['id'], 'name', $bdl['name'], $_rfTranslations, $_rfLocaleChain)) ?></p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] text-zinc-400"><?= $bdl['svc_count'] ?>건 · <?= (int)$bdl['total_duration'] ?>분</span>
                                        <?php if ($bdl['is_event'] && $bdlOriginal > $bdlPrice): ?>
                                        <span class="text-[10px] text-zinc-400 line-through"><?= $fSymbol ?><?= number_format($bdlOriginal) ?></span>
                                        <?php endif; ?>
                                        <span class="text-xs font-bold text-blue-600"><?= $fSymbol ?><?= number_format($bdlPrice) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="border-b border-zinc-200 dark:border-zinc-700 mt-3 mb-1"></div>
                </div>
                <?php endif; ?>

                <!-- 카테고리 필터 -->
                <?php
                $fCategories = [];
                foreach ($fServices as $s) {
                    $cid = $s['category_id'] ?? '';
                    $cname = $s['category_name'] ?? '';
                    if ($cid && $cname && !isset($fCategories[$cid])) {
                        $fCategories[$cid] = $cname;
                    }
                }
                ?>
                <?php if (!empty($fCategories)): ?>
                <div id="<?= $fId ?>_catFilter" class="flex flex-wrap gap-1.5 mb-3">
                    <button type="button" class="rf-cat-btn px-3 py-1 text-xs font-medium rounded-full transition-all bg-blue-600 text-white" data-cat=""><?= __('reservations.form_all') ?></button>
                    <?php foreach ($fCategories as $cid => $cname): ?>
                    <button type="button" class="rf-cat-btn px-3 py-1 text-xs font-medium rounded-full transition-all bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600" data-cat="<?= htmlspecialchars($cid) ?>"><?= htmlspecialchars(_rfTr('category', $cid, 'name', $cname, $_rfTranslations, $_rfLocaleChain)) ?></button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div id="<?= $fId ?>_svcList" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-[420px] overflow-y-auto pr-1">
                    <?php foreach ($fServices as $svc):
                        $oldIds = $fOld['service_ids'] ?? [];
                        $isSelected = in_array($svc['id'], $oldIds);
                        $price = (float)($svc['price'] ?? 0);
                        $duration = (int)($svc['duration'] ?? 30);
                        $svcImage = $svc['image'] ?? '';
                        $hasImage = !empty($svcImage);
                        $svcCatId = $svc['category_id'] ?? '';
                        $svcCatName = $svc['category_name'] ?? '';
                    ?>
                    <div class="rf-card group relative rounded-xl border-2 cursor-pointer transition-all overflow-hidden
                        <?= $isSelected ? 'border-blue-500 ring-2 ring-blue-500/30' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600 hover:shadow-md' ?>"
                        data-name="<?= htmlspecialchars(strtolower($svc['name'])) ?>"
                        data-cat="<?= htmlspecialchars($svcCatId) ?>"
                        data-price="<?= $price ?>" data-duration="<?= $duration ?>"
                        style="min-height:140px;<?php if ($hasImage): ?>background-image:url('<?= htmlspecialchars($fBaseUrl . '/' . $svcImage) ?>');background-size:cover;background-position:center<?php endif; ?>">
                        <input type="checkbox" name="service_ids[]" value="<?= $svc['id'] ?>"
                               class="sr-only rf-check" <?= $isSelected ? 'checked' : '' ?>
                               data-price="<?= $price ?>" data-duration="<?= $duration ?>">
                        <?php if (!$hasImage): ?>
                        <div class="absolute inset-0 bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                        <div class="absolute inset-0 bg-blue-500/20 <?= $isSelected ? '' : 'hidden' ?> rf-overlay"></div>
                        <!-- 선택 체크 -->
                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all shadow-sm z-10
                            <?= $isSelected ? 'border-blue-500 bg-blue-500' : 'border-white/70 bg-black/20 group-hover:bg-black/40' ?>">
                            <svg class="w-3.5 h-3.5 text-white <?= $isSelected ? '' : 'hidden' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <?php if ($svcCatName): ?>
                        <div class="absolute top-2 left-2 z-10">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-black/40 text-white/90 backdrop-blur-sm"><?= htmlspecialchars(_rfTr('category', $svcCatId, 'name', $svcCatName, $_rfTranslations, $_rfLocaleChain)) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 left-0 right-0 p-3 z-10">
                            <p class="text-sm font-bold text-white truncate drop-shadow-sm"><?= htmlspecialchars(_rfTr('service', $svc['id'], 'name', $svc['name'], $_rfTranslations, $_rfLocaleChain)) ?></p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-white/70 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= str_replace(':min', $duration, __('reservations.form_min')) ?>
                                </span>
                                <span class="text-sm font-bold text-white drop-shadow-sm"><?= resFormFormatPrice($price, $fSymbol, $fPosition) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 선택 요약 -->
                <div id="<?= $fId ?>_summary" class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 hidden">
                    <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2"><?= __('reservations.selected_services') ?? '선택한 서비스' ?></h4>
                    <div id="<?= $fId ?>_selectedList" class="space-y-1 mb-3 max-h-[120px] overflow-y-auto"></div>
                    <div class="flex items-center justify-between text-sm pt-2 border-t border-zinc-100 dark:border-zinc-700">
                        <span class="text-zinc-500 dark:text-zinc-400"><?= __('reservations.total_duration') ?? '총 소요시간' ?></span>
                        <span id="<?= $fId ?>_totalDuration" class="font-medium text-zinc-900 dark:text-white">0분</span>
                    </div>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('reservations.total_amount') ?? '총 금액' ?></span>
                        <span id="<?= $fId ?>_totalPrice" class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= resFormFormatPrice(0, $fSymbol, $fPosition) ?></span>
                    </div>
                    <!-- 번들 적용 최종 결제금액 -->
                    <div id="<?= $fId ?>_bundleDiscount" class="hidden mt-2 pt-2 border-t border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 -mx-1">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-blue-600 dark:text-blue-400 font-medium" id="<?= $fId ?>_bundleName"></span>
                            <span class="text-zinc-400 line-through text-sm" id="<?= $fId ?>_bundleOriginal"></span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-sm font-semibold text-blue-700 dark:text-blue-300"><?= __('reservations.bundle_final_price') ?? '최종 결제금액' ?></span>
                            <span id="<?= $fId ?>_bundlePrice" class="text-xl font-bold text-blue-700 dark:text-blue-300"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 우측: 예약 정보 입력 (2/5) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 일시 -->
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : '' ?>">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.form_datetime_select') ?></h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.form_date') ?> <span class="text-red-500">*</span></label>
                        <input type="date" name="reservation_date" id="<?= $fId ?>_date"
                               value="<?= $fOld['reservation_date'] ?? $resForm['defaultDate'] ?>" required
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.form_start') ?> <span class="text-red-500">*</span></label>
                            <input type="time" name="start_time" id="<?= $fId ?>_startTime" value="<?= $fOld['start_time'] ?? '09:00' ?>" required
                                   class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                        </div>
                        <div>
                            <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.form_end') ?></label>
                            <input type="time" name="end_time" id="<?= $fId ?>_endTime" value="<?= $fOld['end_time'] ?? '10:00' ?>"
                                   class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 예약 경로 -->
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : '' ?>">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3"><?= __('reservations.source_label') ?? '예약 경로' ?></h3>
                <?php
                $_phoneEnabled = ($siteSettings['booking_phone_enabled'] ?? $settings['booking_phone_enabled'] ?? '0') === '1';
                $_srcVal = $resForm['source'] ?? $fOld['source'] ?? ($_phoneEnabled ? 'phone' : 'walk_in');
                $_sources = [];
                if ($_phoneEnabled) {
                    $_sources['phone'] = ['label' => __('reservations.source_phone') ?? '전화예약', 'icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'color' => 'blue'];
                }
                $_sources['walk_in'] = ['label' => __('reservations.source_walkin') ?? '현장접수', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'emerald'];
                $_sources['online'] = ['label' => __('reservations.source_online') ?? '온라인', 'icon' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9', 'color' => 'violet'];
                // 전화예약 비활성인데 기존 값이 phone이면 walk_in으로 변경
                if (!$_phoneEnabled && $_srcVal === 'phone') $_srcVal = 'walk_in';
                ?>
                <div class="grid grid-cols-<?= count($_sources) ?> gap-2">
                    <?php foreach ($_sources as $sKey => $s): ?>
                    <button type="button" onclick="ResFormSource('<?= $fId ?>', '<?= $sKey ?>')"
                            id="<?= $fId ?>_src_<?= $sKey ?>"
                            class="py-2.5 text-sm font-medium rounded-lg border-2 transition flex flex-col items-center gap-1
                            <?= $_srcVal === $sKey
                                ? 'border-' . $s['color'] . '-500 bg-' . $s['color'] . '-50 text-' . $s['color'] . '-700 dark:bg-' . $s['color'] . '-900/20 dark:text-' . $s['color'] . '-400'
                                : 'border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:border-zinc-300 dark:hover:border-zinc-600' ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $s['icon'] ?>"/></svg>
                        <?= $s['label'] ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 고객 정보 -->
            <div class="<?= $fMode === 'page' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : '' ?>">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.customer_info') ?? '고객 정보' ?></h3>
                <div class="space-y-3">
                    <div class="relative">
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.form_name') ?> <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" id="<?= $fId ?>_name" value="<?= htmlspecialchars($fOld['customer_name'] ?? '') ?>" required autocomplete="off"
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                        <div id="<?= $fId ?>_nameDropdown" class="absolute z-50 left-0 right-0 top-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-48 overflow-y-auto hidden"></div>
                    </div>
                    <div>
                        <?php
                        $phoneInputConfig = [
                            'name' => 'customer_phone',
                            'id' => $fId . '_phone',
                            'label' => __('reservations.form_phone'),
                            'value' => $fOld['customer_phone'] ?? '',
                            'country_code' => $fOld['customer_phone_country'] ?? '',
                            'phone_number' => $fOld['customer_phone_number'] ?? '',
                            'required' => true,
                            'show_label' => true,
                        ];
                        include BASE_PATH . '/resources/views/components/phone-input.php';
                        ?>
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.form_email') ?></label>
                        <input type="email" name="customer_email" value="<?= htmlspecialchars($fOld['customer_email'] ?? '') ?>"
                               class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                    </div>
                    <div>
                        <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.form_notes') ?></label>
                        <textarea name="notes" rows="2"
                            class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"
                            placeholder="<?= __('reservations.form_notes_placeholder') ?>"><?= htmlspecialchars($fOld['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/reservation-form-staff.php'; ?>

            <!-- 버튼 -->
            <div class="flex gap-3">
                <?php if ($fMode === 'page'): ?>
                <a href="<?= $resForm['adminUrl'] ?>/reservations" class="flex-1 text-center px-4 py-2.5 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('reservations.form_cancel') ?></a>
                <?php else: ?>
                <button type="button" onclick="rzxCalCloseAdd()" class="flex-1 px-4 py-2.5 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('reservations.form_cancel') ?></button>
                <?php endif; ?>
                <button type="submit" id="<?= $fId ?>_submitBtn" class="flex-1 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    <?= __('reservations.form_submit') ?>
                </button>
            </div>
        </div>
    </div>
</form>

<?php
// 스태프 데이터 (활성 스태프 목록)
$rfStaff = $pdo->query("SELECT id, name, avatar, designation_fee FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
window._rfStaff = window._rfStaff || {};
window._rfStaff['<?= $fId ?>'] = <?= json_encode($rfStaff, JSON_UNESCAPED_UNICODE) ?>;
</script>
